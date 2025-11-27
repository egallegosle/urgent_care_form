<?php
/**
 * Monthly Reports - Monthly patient statistics and analytics
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Get last 12 months of data
$sql = "SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_patients,
            COUNT(DISTINCT CASE WHEN drchrono_sync_status = 'synced' THEN patient_id END) as synced_patients,
            COUNT(DISTINCT CASE WHEN insurance_provider IS NOT NULL AND insurance_provider != '' THEN patient_id END) as insurance_count,
            COUNT(DISTINCT CASE WHEN insurance_provider IS NULL OR insurance_provider = '' THEN patient_id END) as self_pay_count
        FROM patients
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC";

$monthly_stats = $conn->query($sql);

// Prepare data for charts
$months = [];
$patient_counts = [];
$insurance_counts = [];
$self_pay_counts = [];

$temp_data = [];
while ($row = $monthly_stats->fetch_assoc()) {
    $temp_data[] = $row;
}

// Reverse for chronological order in charts
$temp_data = array_reverse($temp_data);
foreach ($temp_data as $row) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $patient_counts[] = $row['total_patients'];
    $insurance_counts[] = $row['insurance_count'];
    $self_pay_counts[] = $row['self_pay_count'];
}

// Get top reasons for visit
$sql = "SELECT
            reason_for_visit,
            COUNT(*) as count
        FROM patients
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND reason_for_visit IS NOT NULL
        AND reason_for_visit != ''
        GROUP BY reason_for_visit
        ORDER BY count DESC
        LIMIT 10";

$top_reasons = $conn->query($sql);

// Get insurance provider distribution
$sql = "SELECT
            insurance_provider,
            COUNT(*) as count
        FROM patients
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        AND insurance_provider IS NOT NULL
        AND insurance_provider != ''
        GROUP BY insurance_provider
        ORDER BY count DESC
        LIMIT 10";

$insurance_distribution = $conn->query($sql);

// Get age distribution
$sql = "SELECT
            CASE
                WHEN age < 18 THEN 'Under 18'
                WHEN age >= 18 AND age < 30 THEN '18-29'
                WHEN age >= 30 AND age < 45 THEN '30-44'
                WHEN age >= 45 AND age < 65 THEN '45-64'
                ELSE '65+'
            END as age_group,
            COUNT(*) as count
        FROM patients
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY age_group
        ORDER BY
            CASE
                WHEN age < 18 THEN 1
                WHEN age >= 18 AND age < 30 THEN 2
                WHEN age >= 30 AND age < 45 THEN 3
                WHEN age >= 45 AND age < 65 THEN 4
                ELSE 5
            END";

$age_distribution = $conn->query($sql);
$age_labels = [];
$age_data = [];
while ($row = $age_distribution->fetch_assoc()) {
    $age_labels[] = $row['age_group'];
    $age_data[] = $row['count'];
}

// Gender distribution
$sql = "SELECT
            gender,
            COUNT(*) as count
        FROM patients
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY gender";

$gender_distribution = $conn->query($sql);
$gender_labels = [];
$gender_data = [];
while ($row = $gender_distribution->fetch_assoc()) {
    $gender_labels[] = $row['gender'];
    $gender_data[] = $row['count'];
}

$page_title = 'Monthly Reports';
include __DIR__ . '/../../../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chart-pie"></i> Monthly Reports & Analytics</h1>
    <p>Monthly trends and patient demographics</p>
</div>

<!-- Monthly Trend Chart -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-line"></i> 12-Month Patient Trend</h2>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height: 400px;">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- Insurance vs Self-Pay Trend -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-chart-area"></i> Insurance vs Self-Pay Trend</h2>
    </div>
    <div class="card-body">
        <div class="chart-container" style="height: 400px;">
            <canvas id="paymentTrendChart"></canvas>
        </div>
    </div>
</div>

<!-- Demographics Charts -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- Age Distribution -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-users"></i> Age Distribution (Last 3 Months)</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="ageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Gender Distribution -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-venus-mars"></i> Gender Distribution (Last 3 Months)</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Top Reasons for Visit -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-notes-medical"></i> Top Reasons for Visit (Last 3 Months)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($top_reasons->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Reason for Visit</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    $total = 0;
                    $reasons_data = [];
                    while ($row = $top_reasons->fetch_assoc()) {
                        $reasons_data[] = $row;
                        $total += $row['count'];
                    }
                    foreach ($reasons_data as $row):
                        $percentage = ($row['count'] / $total) * 100;
                    ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars(substr($row['reason_for_visit'], 0, 100)); ?></td>
                        <td><strong><?php echo $row['count']; ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                                    <div style="background: #0066cc; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <span><?php echo round($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-notes-medical"></i>
                <h3>No data available</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Top Insurance Providers -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-shield-alt"></i> Top Insurance Providers (Last 3 Months)</h2>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if ($insurance_distribution->num_rows > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Insurance Provider</th>
                        <th>Patient Count</th>
                        <th>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    $total_insurance = 0;
                    $insurance_data = [];
                    while ($row = $insurance_distribution->fetch_assoc()) {
                        $insurance_data[] = $row;
                        $total_insurance += $row['count'];
                    }
                    foreach ($insurance_data as $row):
                        $percentage = ($row['count'] / $total_insurance) * 100;
                    ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($row['insurance_provider']); ?></td>
                        <td><strong><?php echo $row['count']; ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden;">
                                    <div style="background: #28a745; height: 100%; width: <?php echo $percentage; %>%;"></div>
                                </div>
                                <span><?php echo round($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shield-alt"></i>
                <h3>No insurance data available</h3>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly trend line chart
    ChartUtils.createLineChart('monthlyTrendChart',
        <?php echo json_encode($months); ?>,
        [{
            label: 'Total Patients',
            data: <?php echo json_encode($patient_counts); ?>,
            borderColor: ChartUtils.colors.primary,
            backgroundColor: ChartUtils.colors.primary
        }]
    );

    // Insurance vs Self-Pay stacked area chart
    const ctx2 = document.getElementById('paymentTrendChart');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Insurance',
                    data: <?php echo json_encode($insurance_counts); ?>,
                    borderColor: ChartUtils.colors.success,
                    backgroundColor: 'rgba(40, 167, 69, 0.3)',
                    fill: true
                },
                {
                    label: 'Self-Pay',
                    data: <?php echo json_encode($self_pay_counts); ?>,
                    borderColor: ChartUtils.colors.info,
                    backgroundColor: 'rgba(23, 162, 184, 0.3)',
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    stacked: false
                }
            }
        }
    });

    // Age distribution
    ChartUtils.createBarChart(
        'ageChart',
        <?php echo json_encode($age_labels); ?>,
        <?php echo json_encode($age_data); ?>,
        'Patients'
    );

    // Gender distribution
    ChartUtils.createPieChart(
        'genderChart',
        <?php echo json_encode($gender_labels); ?>,
        <?php echo json_encode($gender_data); ?>
    );
});
</script>

<?php
$conn->close();
include __DIR__ . '/../../../includes/admin_footer.php';
?>
