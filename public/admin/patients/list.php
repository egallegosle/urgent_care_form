<?php
/**
 * Patient List - Searchable, Filterable Table
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Log page view
logAdminAction($admin_id, 'VIEW', 'patients', null, 'Viewed patient list');

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sync_filter = $_GET['sync'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$filter_preset = $_GET['filter'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR cell_phone LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if ($status_filter) {
    $where_conditions[] = "current_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($sync_filter) {
    $where_conditions[] = "drchrono_sync_status = ?";
    $params[] = $sync_filter;
    $types .= 's';
}

// Preset filters
if ($filter_preset === 'pending_sync') {
    $where_conditions[] = "drchrono_sync_status = 'pending'";
} elseif ($filter_preset === 'failed_sync') {
    $where_conditions[] = "drchrono_sync_status = 'failed'";
} elseif ($filter_preset === 'incomplete_forms') {
    $where_conditions[] = "all_forms_completed = FALSE";
}

if ($date_from) {
    $where_conditions[] = "DATE(registration_date) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $where_conditions[] = "DATE(registration_date) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM vw_admin_patients {$where_clause}";
if (count($params) > 0) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// Get patients
$sql = "SELECT
            patient_id,
            full_name,
            email,
            cell_phone,
            age,
            gender,
            reason_for_visit,
            registration_date,
            all_forms_completed,
            current_status,
            priority,
            drchrono_sync_status,
            insurance_provider
        FROM vw_admin_patients
        {$where_clause}
        ORDER BY registration_date DESC
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$patients = $stmt->get_result();

$page_title = 'Patient Management';
include __DIR__ . '/../../../includes/admin_header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-users"></i> Patient Management</h1>
        <p>Search, filter, and manage patient registrations</p>
    </div>
    <div class="card-actions">
        <button onclick="ExportUtils.exportTableToCSV('patientsTable', 'patients_<?php echo date('Y-m-d'); ?>.csv')" class="btn btn-success">
            <i class="fas fa-file-csv"></i> Export CSV
        </button>
        <button onclick="ExportUtils.printPage()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" action="" id="filterForm">
        <div class="filter-row">
            <!-- Search -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    name="search"
                    id="searchInput"
                    placeholder="Search name, email, phone..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </div>

            <!-- Date From -->
            <div>
                <label for="date_from">From Date</label>
                <input
                    type="date"
                    name="date_from"
                    id="date_from"
                    value="<?php echo htmlspecialchars($date_from); ?>"
                >
            </div>

            <!-- Date To -->
            <div>
                <label for="date_to">To Date</label>
                <input
                    type="date"
                    name="date_to"
                    id="date_to"
                    value="<?php echo htmlspecialchars($date_to); ?>"
                >
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>
                    <option value="registered" <?php echo $status_filter === 'registered' ? 'selected' : ''; ?>>Registered</option>
                    <option value="checked_in" <?php echo $status_filter === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>

            <!-- Sync Filter -->
            <div>
                <label for="sync">DrChrono Sync</label>
                <select name="sync" id="sync">
                    <option value="">All Sync Status</option>
                    <option value="pending" <?php echo $sync_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="synced" <?php echo $sync_filter === 'synced' ? 'selected' : ''; ?>>Synced</option>
                    <option value="failed" <?php echo $sync_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 10px; align-items: end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Quick Filter Buttons -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="list.php?filter=pending_sync" class="btn btn-sm btn-warning">
        <i class="fas fa-clock"></i> Pending Sync
    </a>
    <a href="list.php?filter=failed_sync" class="btn btn-sm btn-danger">
        <i class="fas fa-exclamation-circle"></i> Failed Sync
    </a>
    <a href="list.php?filter=incomplete_forms" class="btn btn-sm btn-info">
        <i class="fas fa-file-alt"></i> Incomplete Forms
    </a>
    <a href="list.php?status=checked_in" class="btn btn-sm btn-success">
        <i class="fas fa-check"></i> Checked In
    </a>
</div>

<!-- Results Summary -->
<div style="margin-bottom: 15px;">
    <p style="color: #666;">
        Showing <strong><?php echo min($offset + 1, $total_records); ?></strong> to
        <strong><?php echo min($offset + $per_page, $total_records); ?></strong> of
        <strong><?php echo $total_records; ?></strong> patients
    </p>
</div>

<!-- Patient Table -->
<div class="dashboard-card">
    <div class="card-body" style="padding: 0;">
        <?php if ($patients->num_rows > 0): ?>
            <table class="data-table" id="patientsTable">
                <thead>
                    <tr>
                        <th>Patient Name</th>
                        <th>Age/Gender</th>
                        <th>Contact</th>
                        <th>Registered</th>
                        <th>Forms</th>
                        <th>Status</th>
                        <th>Sync</th>
                        <th>Insurance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($patient = $patients->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong><br>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(substr($patient['reason_for_visit'], 0, 50)); ?>
                                <?php echo strlen($patient['reason_for_visit']) > 50 ? '...' : ''; ?>
                            </small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($patient['age']); ?> /
                            <?php echo htmlspecialchars($patient['gender']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($patient['email']); ?><br>
                            <small><?php echo htmlspecialchars($patient['cell_phone']); ?></small>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($patient['registration_date'])); ?><br>
                            <small><?php echo date('g:i A', strtotime($patient['registration_date'])); ?></small>
                        </td>
                        <td>
                            <?php if ($patient['all_forms_completed']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Complete
                                </span>
                            <?php else: ?>
                                <span class="badge badge-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Incomplete
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php
                                echo $patient['current_status'] === 'completed' ? 'success' :
                                    ($patient['current_status'] === 'in_progress' ? 'info' :
                                    ($patient['current_status'] === 'checked_in' ? 'success' : 'secondary'));
                            ?>">
                                <?php echo ucfirst($patient['current_status'] ?? 'registered'); ?>
                            </span>
                            <?php if ($patient['priority'] === 'urgent' || $patient['priority'] === 'emergency'): ?>
                                <span class="badge badge-danger">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo strtoupper($patient['priority']); ?>
                                </span>
                            <?php endif; ?>
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
                            <?php if ($patient['insurance_provider']): ?>
                                <span class="badge badge-info">
                                    <i class="fas fa-shield-alt"></i> Insurance
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Self-Pay</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No patients found</h3>
                <p>Try adjusting your search or filter criteria</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
    <?php endif; ?>

    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
            Next <i class="fas fa-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Real-time search (debounced)
document.getElementById('searchInput').addEventListener('input', AdminUtils.debounce(function(e) {
    document.getElementById('filterForm').submit();
}, 500));
</script>

<?php
$conn->close();
include __DIR__ . '/../../../includes/admin_footer.php';
?>
