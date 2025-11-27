<?php
/**
 * Daily Reports - Daily patient statistics and metrics
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Date range (default to last 30 days)
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get daily statistics
$sql = "SELECT
            DATE(p.created_at) as stat_date,
            COUNT(DISTINCT p.patient_id) as total_registrations,
            COUNT(DISTINCT CASE WHEN fs.all_forms_completed = TRUE THEN p.patient_id END) as completed_forms,
            COUNT(DISTINCT CASE WHEN ps.current_status = 'checked_in' THEN p.patient_id END) as checked_in_count,
            COUNT(DISTINCT CASE WHEN ps.current_status = 'completed' THEN p.patient_id END) as completed_visits,
            COUNT(DISTINCT CASE WHEN p.insurance_provider IS NOT NULL AND p.insurance_provider != '' THEN p.patient_id END) as insurance_patients,
            COUNT(DISTINCT CASE WHEN p.insurance_provider IS NULL OR p.insurance_provider = '' THEN p.patient_id END) as self_pay_patients,
            AVG(TIMESTAMPDIFF(MINUTE, p.created_at, fs.completed_at)) as avg_form_completion_minutes,
            COUNT(DISTINCT CASE WHEN p.drchrono_sync_status = 'synced' THEN p.patient_id END) as synced_count,
            COUNT(DISTINCT CASE WHEN p.drchrono_sync_status = 'failed' THEN p.patient_id END) as failed_sync
        FROM patients p
        LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id
        LEFT JOIN patient_status ps ON p.patient_id = ps.patient_id
        WHERE DATE(p.created_at) >= ? AND DATE(p.created_at) <= ?
        GROUP BY DATE(p.created_at)
        ORDER BY stat_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$daily_stats = $stmt->get_result();

// Get hourly distribution for selected date range
$sql = "SELECT
            HOUR(created_at) as hour,
            COUNT(*) as count
        FROM patients
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        GROUP BY HOUR(created_at)
        ORDER BY hour";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$hourly_result = $stmt->get_result();

$hourly_data = array_fill(0, 24, 0);
while ($row = $hourly_result->fetch_assoc()) {
    $hourly_data[$row['hour']] = $row['count'];
}

// Get day of week distribution
$sql = "SELECT
            DAYNAME(created_at) as day_name,
            DAYOFWEEK(created_at) as day_num,
            COUNT(*) as count
        FROM patients
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at)
        ORDER BY day_num";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$dow_result = $stmt->get_result();

$dow_labels = [];
$dow_data = [];
while ($row = $dow_result->fetch_assoc()) {
    $dow_labels[] = $row['day_name'];
    $dow_data[] = $row['count'];
}

// Calculate totals
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM patients WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['total'];

$page_title = 'Daily Reports';
include __DIR__ . '/../../../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Daily Reports</h1>
    <p>Daily patient statistics and trends</p>
</div>

<!-- Date Range Filter -->
<div class="filter-bar">
    <form method="GET" action="">
        <div class="filter-row">
            <div>
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>" class="form-control">
            </div>
            <div>
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>" class="form-control">
            </div>
            <div style="display: flex; gap: 10px; align-items: end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <button type="button" onclick="setDateRange(7)" class="btn btn-secondary">Last 7 Days</button>
                <button type="button" onclick="setDateRange(30)" class="btn btn-secondary">Last 30 Days</button>
            </div>
        </div>
    </form>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Patients</span>
            <i class="fas fa-users stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $total_patients; ?></div>
        <div class="stat-description">In selected period</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Daily Average</span>
            <i class="fas fa-chart-line stat-icon"></i>
        </div>
        <div class="stat-value">
            <?php
            $days_diff = max(1, (strtotime($date_to) - strtotime($date_from)) / 86400 + 1);
            echo round($total_patients / $days_diff, 1);
            ?>
        </div>
        <div class="stat-description">Patients per day</div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Peak Hours Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> Peak Hours</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Day of Week Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-calendar-week"></i> Busiest Days</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="dowChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Daily Statistics Table -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-table"></i> Daily Breakdown</h2>
        <button onclick="ExportUtils.exportTableToCSV('dailyTable', 'daily_report_<?php echo $date_from; ?>_to_<?php echo $date_to; ?>.csv')" class="btn btn-sm btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($daily_stats->num_rows > 0): ?>
            <table class="data-table" id="dailyTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Registrations</th>
                        <th>Forms Completed</th>
                        <th>Checked In</th>
                        <th>Visits Completed</th>
                        <th>Insurance</th>
                        <th>Self-Pay</th>
                        <th>Avg Form Time (min)</th>
                        <th>Synced</th>
                        <th>Failed Sync</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $daily_stats->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('D, M j, Y', strtotime($row['stat_date'])); ?></td>
                        <td><strong><?php echo $row['total_registrations']; ?></strong></td>
                        <td><?php echo $row['completed_forms']; ?></td>
                        <td><?php echo $row['checked_in_count']; ?></td>
                        <td><?php echo $row['completed_visits']; ?></td>
                        <td><?php echo $row['insurance_patients']; ?></td>
                        <td><?php echo $row['self_pay_patients']; ?></td>
                        <td><?php echo $row['avg_form_completion_minutes'] ? round($row['avg_form_completion_minutes'], 1) : 'N/A'; ?></td>
                        <td><span class="badge badge-success"><?php echo $row['synced_count']; ?></span></td>
                        <td>
                            <?php if ($row['failed_sync'] > 0): ?>
                                <span class="badge badge-danger"><?php echo $row['failed_sync']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary">0</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <h3>No data for selected period</h3>
                <p>Try selecting a different date range</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Helper function to set date range
function setDateRange(days) {
    const today = new Date();
    const from = new Date(today);
    from.setDate(today.getDate() - days);

    document.getElementById('date_from').value = from.toISOString().split('T')[0];
    document.getElementById('date_to').value = today.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Hourly distribution chart
    const hourLabels = [];
    for (let i = 0; i < 24; i++) {
        const hour = i % 12 || 12;
        const ampm = i < 12 ? 'AM' : 'PM';
        hourLabels.push(`${hour} ${ampm}`);
    }

    ChartUtils.createBarChart(
        'hourlyChart',
        hourLabels,
        <?php echo json_encode($hourly_data); ?>,
        'Patient Registrations'
    );

    // Day of week chart
    ChartUtils.createBarChart(
        'dowChart',
        <?php echo json_encode($dow_labels); ?>,
        <?php echo json_encode($dow_data); ?>,
        'Patient Registrations'
    );
});
</script>

<?php
$conn->close();
include __DIR__ . '/../../../includes/admin_footer.php';
?>
