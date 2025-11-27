<?php
/**
 * Admin Dashboard Home - KPIs and Metrics
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Require authentication
requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Get today's date
$today = date('Y-m-d');
$this_month_start = date('Y-m-01');
$this_week_start = date('Y-m-d', strtotime('monday this week'));

// ===================================
// Today's Metrics
// ===================================

// Patients registered today
$sql = "SELECT COUNT(*) as count FROM patients WHERE DATE(created_at) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$patients_today = $stmt->get_result()->fetch_assoc()['count'];

// Patients checked in today
$sql = "SELECT COUNT(*) as count FROM patient_status WHERE DATE(checked_in_at) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$checked_in_today = $stmt->get_result()->fetch_assoc()['count'];

// Forms completed today
$sql = "SELECT COUNT(*) as count FROM form_submissions WHERE DATE(completed_at) = ? AND all_forms_completed = TRUE";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$forms_completed_today = $stmt->get_result()->fetch_assoc()['count'];

// Current patients in waiting
$sql = "SELECT COUNT(*) as count FROM patient_status WHERE current_status IN ('registered', 'checked_in')";
$waiting_count = $conn->query($sql)->fetch_assoc()['count'];

// ===================================
// This Week's Metrics
// ===================================

$sql = "SELECT COUNT(*) as count FROM patients WHERE created_at >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $this_week_start);
$stmt->execute();
$patients_this_week = $stmt->get_result()->fetch_assoc()['count'];

// ===================================
// This Month's Metrics
// ===================================

$sql = "SELECT COUNT(*) as count FROM patients WHERE created_at >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $this_month_start);
$stmt->execute();
$patients_this_month = $stmt->get_result()->fetch_assoc()['count'];

// ===================================
// DrChrono Sync Status
// ===================================

$sql = "SELECT drchrono_sync_status, COUNT(*) as count FROM patients GROUP BY drchrono_sync_status";
$sync_result = $conn->query($sql);
$sync_stats = [];
while ($row = $sync_result->fetch_assoc()) {
    $sync_stats[$row['drchrono_sync_status']] = $row['count'];
}

$pending_sync = $sync_stats['pending'] ?? 0;
$failed_sync = $sync_stats['failed'] ?? 0;
$synced = $sync_stats['synced'] ?? 0;

// ===================================
// Form Completion Rate
// ===================================

$sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN all_forms_completed = TRUE THEN 1 ELSE 0 END) as completed
        FROM form_submissions";
$result = $conn->query($sql)->fetch_assoc();
$total_submissions = $result['total'];
$completed_submissions = $result['completed'];
$completion_rate = $total_submissions > 0 ? round(($completed_submissions / $total_submissions) * 100, 1) : 0;

// ===================================
// Average Form Completion Time
// ===================================

$sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, p.created_at, fs.completed_at)) as avg_minutes
        FROM patients p
        JOIN form_submissions fs ON p.patient_id = fs.patient_id
        WHERE fs.all_forms_completed = TRUE
        AND DATE(p.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$avg_time = $conn->query($sql)->fetch_assoc()['avg_minutes'];
$avg_time = $avg_time ? round($avg_time, 1) : 0;

// ===================================
// Insurance vs Self-Pay
// ===================================

$sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN insurance_provider IS NOT NULL AND insurance_provider != '' THEN 1 ELSE 0 END) as with_insurance
        FROM patients
        WHERE created_at >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $this_month_start);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$insurance_patients = $result['with_insurance'];
$self_pay_patients = $result['total'] - $result['with_insurance'];

// ===================================
// Patients Needing Attention
// ===================================

$sql = "SELECT * FROM vw_patients_needing_attention LIMIT 10";
$patients_needing_attention = $conn->query($sql);

// ===================================
// Recent Patients
// ===================================

$sql = "SELECT
            patient_id,
            full_name,
            email,
            cell_phone,
            registration_date,
            current_status,
            drchrono_sync_status,
            all_forms_completed
        FROM vw_admin_patients
        ORDER BY registration_date DESC
        LIMIT 10";
$recent_patients = $conn->query($sql);

// ===================================
// Daily Trend (Last 7 Days)
// ===================================

$sql = "SELECT
            DATE(created_at) as date,
            COUNT(*) as count
        FROM patients
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC";
$daily_trend = $conn->query($sql);
$trend_labels = [];
$trend_data = [];
while ($row = $daily_trend->fetch_assoc()) {
    $trend_labels[] = date('M j', strtotime($row['date']));
    $trend_data[] = $row['count'];
}

$page_title = 'Dashboard';
include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-dashboard"></i> Dashboard</h1>
    <p>Overview of patient registrations, form completions, and system status</p>
</div>

<!-- Today's Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Patients Today</span>
            <i class="fas fa-user-plus stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $patients_today; ?></div>
        <div class="stat-description">Registered today</div>
    </div>

    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">Checked In</span>
            <i class="fas fa-check-circle stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $checked_in_today; ?></div>
        <div class="stat-description">Patients checked in today</div>
    </div>

    <div class="stat-card warning">
        <div class="stat-header">
            <span class="stat-title">Waiting</span>
            <i class="fas fa-clock stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $waiting_count; ?></div>
        <div class="stat-description">Currently waiting</div>
    </div>

    <div class="stat-card info">
        <div class="stat-header">
            <span class="stat-title">Completion Rate</span>
            <i class="fas fa-chart-line stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $completion_rate; ?>%</div>
        <div class="stat-description"><?php echo $completed_submissions; ?> of <?php echo $total_submissions; ?> forms</div>
    </div>
</div>

<!-- Period Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">This Week</span>
            <i class="fas fa-calendar-week stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $patients_this_week; ?></div>
        <div class="stat-description">Patient registrations</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">This Month</span>
            <i class="fas fa-calendar-alt stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $patients_this_month; ?></div>
        <div class="stat-description">Patient registrations</div>
    </div>

    <div class="stat-card info">
        <div class="stat-header">
            <span class="stat-title">Avg. Form Time</span>
            <i class="fas fa-stopwatch stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $avg_time; ?><small style="font-size: 16px;">min</small></div>
        <div class="stat-description">Average completion time</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Insurance Mix</span>
            <i class="fas fa-shield-alt stat-icon"></i>
        </div>
        <div class="stat-value"><?php echo $insurance_patients; ?> / <?php echo $self_pay_patients; ?></div>
        <div class="stat-description">Insurance vs Self-Pay</div>
    </div>
</div>

<!-- DrChrono Sync Status -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-sync"></i>
            DrChrono Sync Status
        </h2>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-header">
                    <span class="stat-title">Synced</span>
                    <i class="fas fa-check-circle stat-icon"></i>
                </div>
                <div class="stat-value"><?php echo $synced; ?></div>
                <div class="stat-description">Successfully synced</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <span class="stat-title">Pending</span>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
                <div class="stat-value"><?php echo $pending_sync; ?></div>
                <div class="stat-description">Waiting to sync</div>
                <?php if ($pending_sync > 0): ?>
                    <a href="/admin/patients/list.php?filter=pending_sync" class="btn btn-sm btn-warning" style="margin-top: 10px;">
                        <i class="fas fa-eye"></i> View Pending
                    </a>
                <?php endif; ?>
            </div>

            <div class="stat-card danger">
                <div class="stat-header">
                    <span class="stat-title">Failed</span>
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                </div>
                <div class="stat-value"><?php echo $failed_sync; ?></div>
                <div class="stat-description">Sync failures</div>
                <?php if ($failed_sync > 0): ?>
                    <a href="/admin/patients/list.php?filter=failed_sync" class="btn btn-sm btn-danger" style="margin-top: 10px;">
                        <i class="fas fa-exclamation-circle"></i> Fix Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout for Charts and Tables -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Patient Trend Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-chart-line"></i>
                7-Day Patient Trend
            </h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Insurance Distribution -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-chart-pie"></i>
                Insurance Distribution
            </h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="insuranceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Patients Needing Attention -->
<?php if ($patients_needing_attention->num_rows > 0): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-exclamation-triangle"></i>
            Patients Needing Attention
        </h2>
        <span class="badge badge-danger"><?php echo $patients_needing_attention->num_rows; ?></span>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Contact</th>
                    <th>Registered</th>
                    <th>Issue</th>
                    <th>Priority</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($patient = $patients_needing_attention->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($patient['email']); ?><br>
                        <small><?php echo htmlspecialchars($patient['cell_phone']); ?></small>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($patient['created_at'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $patient['priority_level'] === 'high' ? 'danger' : 'warning'; ?>">
                            <?php echo htmlspecialchars($patient['issue_type']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php
                            echo $patient['priority_level'] === 'high' ? 'danger' :
                                ($patient['priority_level'] === 'medium' ? 'warning' : 'info');
                        ?>">
                            <?php echo strtoupper($patient['priority_level']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="/admin/patients/view.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Recent Patients -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-user-clock"></i>
            Recent Patients
        </h2>
        <a href="/admin/patients/list.php" class="btn btn-sm btn-primary">
            <i class="fas fa-list"></i> View All
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Contact</th>
                    <th>Registered</th>
                    <th>Forms</th>
                    <th>Status</th>
                    <th>Sync</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($patient = $recent_patients->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                    <td>
                        <?php echo htmlspecialchars($patient['email']); ?><br>
                        <small><?php echo htmlspecialchars($patient['cell_phone']); ?></small>
                    </td>
                    <td><?php echo date('M j, g:i A', strtotime($patient['registration_date'])); ?></td>
                    <td>
                        <?php if ($patient['all_forms_completed']): ?>
                            <span class="badge badge-success">Complete</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Incomplete</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php
                            echo $patient['current_status'] === 'completed' ? 'success' :
                                ($patient['current_status'] === 'in_progress' ? 'info' : 'secondary');
                        ?>">
                            <?php echo ucfirst($patient['current_status'] ?? 'registered'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php
                            echo $patient['drchrono_sync_status'] === 'synced' ? 'success' :
                                ($patient['drchrono_sync_status'] === 'failed' ? 'danger' : 'warning');
                        ?>">
                            <?php echo ucfirst($patient['drchrono_sync_status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="/admin/patients/view.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Patient Trend Chart
    ChartUtils.createLineChart('trendChart',
        <?php echo json_encode($trend_labels); ?>,
        [{
            label: 'Patients',
            data: <?php echo json_encode($trend_data); ?>,
            borderColor: ChartUtils.colors.primary,
            backgroundColor: ChartUtils.colors.primary
        }]
    );

    // Insurance Distribution Chart
    ChartUtils.createDoughnutChart('insuranceChart',
        ['Insurance', 'Self-Pay'],
        [<?php echo $insurance_patients; ?>, <?php echo $self_pay_patients; ?>]
    );

    // Auto-refresh every 60 seconds
    // startAutoRefresh(60);
});
</script>

<?php
$conn->close();
include __DIR__ . '/../../includes/admin_footer.php';
?>
