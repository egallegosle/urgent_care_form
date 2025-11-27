<?php
/**
 * Settings Dashboard - Main Settings Page
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Get all settings grouped by category
$sql = "SELECT * FROM clinic_settings ORDER BY category, display_name";
$settings_result = $conn->query($sql);

$settings_by_category = [];
while ($setting = $settings_result->fetch_assoc()) {
    $settings_by_category[$setting['category']][] = $setting;
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $success_count = 0;
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8); // Remove 'setting_' prefix
            $setting_value = sanitizeInput($value);

            $sql = "UPDATE clinic_settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sis", $setting_value, $admin_id, $setting_key);
            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }

    logAdminAction($admin_id, 'UPDATE', 'clinic_settings', null, "Updated {$success_count} settings");

    $message = "Settings updated successfully! ({$success_count} settings saved)";
    $message_type = 'success';

    // Refresh settings
    $settings_result = $conn->query("SELECT * FROM clinic_settings ORDER BY category, display_name");
    $settings_by_category = [];
    while ($setting = $settings_result->fetch_assoc()) {
        $settings_by_category[$setting['category']][] = $setting;
    }
}

$page_title = 'Settings';
include __DIR__ . '/../../../includes/admin_header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Clinic Settings</h1>
    <p>Configure clinic information, branding, and system settings</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Settings Navigation Tabs -->
<div class="settings-nav">
    <a href="#branding" class="active">Branding</a>
    <a href="#contact">Contact Info</a>
    <a href="#operations">Operations</a>
    <a href="#system">System</a>
    <a href="#notifications">Notifications</a>
</div>

<form method="POST" action="">
    <input type="hidden" name="action" value="update_settings">

    <!-- Branding Settings -->
    <div class="dashboard-card" id="branding">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-palette"></i> Branding Settings</h2>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <?php if (isset($settings_by_category['branding'])): ?>
                    <?php foreach ($settings_by_category['branding'] as $setting): ?>
                        <div>
                            <label for="setting_<?php echo $setting['setting_key']; ?>">
                                <?php echo htmlspecialchars($setting['display_name']); ?>
                            </label>
                            <?php if ($setting['setting_type'] === 'color'): ?>
                                <input
                                    type="color"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php elseif ($setting['setting_type'] === 'url'): ?>
                                <input
                                    type="url"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                    placeholder="https://"
                                >
                            <?php else: ?>
                                <input
                                    type="text"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php endif; ?>
                            <?php if ($setting['description']): ?>
                                <small class="help-text"><?php echo htmlspecialchars($setting['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="dashboard-card" id="contact">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-address-card"></i> Contact Information</h2>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <?php if (isset($settings_by_category['contact'])): ?>
                    <?php foreach ($settings_by_category['contact'] as $setting): ?>
                        <div>
                            <label for="setting_<?php echo $setting['setting_key']; ?>">
                                <?php echo htmlspecialchars($setting['display_name']); ?>
                            </label>
                            <input
                                type="<?php echo $setting['setting_type'] === 'email' ? 'email' : 'text'; ?>"
                                id="setting_<?php echo $setting['setting_key']; ?>"
                                name="setting_<?php echo $setting['setting_key']; ?>"
                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                class="form-control"
                            >
                            <?php if ($setting['description']): ?>
                                <small class="help-text"><?php echo htmlspecialchars($setting['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Operating Hours -->
    <div class="dashboard-card" id="operations">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-clock"></i> Operating Hours</h2>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <?php if (isset($settings_by_category['operations'])): ?>
                    <?php foreach ($settings_by_category['operations'] as $setting): ?>
                        <div>
                            <label for="setting_<?php echo $setting['setting_key']; ?>">
                                <?php echo htmlspecialchars($setting['display_name']); ?>
                            </label>
                            <input
                                type="text"
                                id="setting_<?php echo $setting['setting_key']; ?>"
                                name="setting_<?php echo $setting['setting_key']; ?>"
                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                class="form-control"
                                placeholder="e.g., 8:00 AM - 8:00 PM"
                            >
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Settings -->
    <div class="dashboard-card" id="system">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-server"></i> System Settings</h2>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <?php if (isset($settings_by_category['system'])): ?>
                    <?php foreach ($settings_by_category['system'] as $setting): ?>
                        <div>
                            <label for="setting_<?php echo $setting['setting_key']; ?>">
                                <?php echo htmlspecialchars($setting['display_name']); ?>
                            </label>
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    class="form-control"
                                >
                                    <option value="true" <?php echo $setting['setting_value'] === 'true' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="false" <?php echo $setting['setting_value'] === 'false' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            <?php elseif ($setting['setting_type'] === 'number'): ?>
                                <input
                                    type="number"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php else: ?>
                                <input
                                    type="text"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php endif; ?>
                            <?php if ($setting['description']): ?>
                                <small class="help-text"><?php echo htmlspecialchars($setting['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="dashboard-card" id="notifications">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-bell"></i> Notification Settings</h2>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <?php if (isset($settings_by_category['notifications'])): ?>
                    <?php foreach ($settings_by_category['notifications'] as $setting): ?>
                        <div>
                            <label for="setting_<?php echo $setting['setting_key']; ?>">
                                <?php echo htmlspecialchars($setting['display_name']); ?>
                            </label>
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    class="form-control"
                                >
                                    <option value="true" <?php echo $setting['setting_value'] === 'true' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="false" <?php echo $setting['setting_value'] === 'false' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                            <?php elseif ($setting['setting_type'] === 'email'): ?>
                                <input
                                    type="email"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php else: ?>
                                <input
                                    type="text"
                                    id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>"
                                    value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                    class="form-control"
                                >
                            <?php endif; ?>
                            <?php if ($setting['description']): ?>
                                <small class="help-text"><?php echo htmlspecialchars($setting['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-save"></i> Save All Settings
        </button>
    </div>
</form>

<script>
// Smooth scroll for navigation
document.querySelectorAll('.settings-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Update active state
            document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
            this.classList.add('active');
        }
    });
});

// Update active nav on scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const id = entry.target.getAttribute('id');
            document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
            document.querySelector(`.settings-nav a[href="#${id}"]`)?.classList.add('active');
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.dashboard-card[id]').forEach(section => {
    observer.observe(section);
});
</script>

<?php
$conn->close();
include __DIR__ . '/../../../includes/admin_footer.php';
?>
