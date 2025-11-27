<?php
/**
 * Patient Detail View - Complete Patient Information
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Get patient ID
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id === 0) {
    header('Location: list.php');
    exit();
}

// Log PHI access
logAdminAction($admin_id, 'VIEW', 'patients', $patient_id, 'Viewed patient details', $patient_id);

// Get complete patient data
$sql = "SELECT * FROM vw_admin_patients WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    header('Location: list.php');
    exit();
}

// Get medical history
$sql = "SELECT * FROM medical_history WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_history = $stmt->get_result()->fetch_assoc();

// Get patient consents
$sql = "SELECT * FROM patient_consents WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$consent = $stmt->get_result()->fetch_assoc();

// Get financial agreement
$sql = "SELECT * FROM financial_agreements WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$financial = $stmt->get_result()->fetch_assoc();

// Get additional consents
$sql = "SELECT * FROM additional_consents WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$additional = $stmt->get_result()->fetch_assoc();

// Get form submission status
$sql = "SELECT * FROM form_submissions WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$form_status = $stmt->get_result()->fetch_assoc();

// Get patient status
$sql = "SELECT * FROM patient_status WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$status = $stmt->get_result()->fetch_assoc();

$page_title = 'Patient Details - ' . $patient['full_name'];
include __DIR__ . '/../../../includes/admin_header.php';
?>

<div class="page-header">
    <div>
        <h1>
            <i class="fas fa-user"></i>
            <?php echo htmlspecialchars($patient['full_name']); ?>
        </h1>
        <p>Patient ID: <?php echo $patient['patient_id']; ?> | Registered: <?php echo date('F j, Y g:i A', strtotime($patient['registration_date'])); ?></p>
    </div>
    <div class="card-actions">
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print
        </button>
        <a href="export.php?id=<?php echo $patient_id; ?>&format=pdf" class="btn btn-danger">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Status and Quick Actions -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-tasks"></i> Status & Actions</h2>
    </div>
    <div class="card-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Current Status</span>
                    <i class="fas fa-clipboard-check stat-icon"></i>
                </div>
                <div class="stat-value" style="font-size: 20px;">
                    <span class="badge badge-<?php
                        echo $status['current_status'] === 'completed' ? 'success' :
                            ($status['current_status'] === 'in_progress' ? 'info' :
                            ($status['current_status'] === 'checked_in' ? 'success' : 'secondary'));
                    ?>" style="font-size: 16px;">
                        <?php echo ucfirst($status['current_status'] ?? 'registered'); ?>
                    </span>
                </div>
                <div style="margin-top: 10px;">
                    <select id="statusSelect" class="form-control" style="width: 100%;">
                        <option value="">Change Status...</option>
                        <option value="checked_in">Check In</option>
                        <option value="in_progress">Mark In Progress</option>
                        <option value="completed">Mark Completed</option>
                    </select>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Forms Status</span>
                    <i class="fas fa-file-alt stat-icon"></i>
                </div>
                <div class="stat-value" style="font-size: 20px;">
                    <?php if ($patient['all_forms_completed']): ?>
                        <span class="badge badge-success" style="font-size: 16px;">
                            <i class="fas fa-check-circle"></i> Complete
                        </span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="font-size: 16px;">
                            <i class="fas fa-exclamation-triangle"></i> Incomplete
                        </span>
                    <?php endif; ?>
                </div>
                <div class="stat-description">
                    Completed: <?php echo $patient['forms_completed_date'] ? date('M j, Y g:i A', strtotime($patient['forms_completed_date'])) : 'Not completed'; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">DrChrono Sync</span>
                    <i class="fas fa-sync stat-icon"></i>
                </div>
                <div class="stat-value" style="font-size: 20px;">
                    <span class="badge badge-<?php
                        echo $patient['drchrono_sync_status'] === 'synced' ? 'success' :
                            ($patient['drchrono_sync_status'] === 'failed' ? 'danger' : 'warning');
                    ?>" style="font-size: 16px;">
                        <?php echo ucfirst($patient['drchrono_sync_status']); ?>
                    </span>
                </div>
                <div class="stat-description">
                    <?php if ($patient['drchrono_patient_id']): ?>
                        DrChrono ID: <?php echo htmlspecialchars($patient['drchrono_patient_id']); ?>
                    <?php else: ?>
                        Not synced yet
                    <?php endif; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Priority</span>
                    <i class="fas fa-flag stat-icon"></i>
                </div>
                <div class="stat-value" style="font-size: 20px;">
                    <span class="badge badge-<?php
                        echo $status['priority'] === 'emergency' ? 'danger' :
                            ($status['priority'] === 'urgent' ? 'warning' : 'secondary');
                    ?>" style="font-size: 16px;">
                        <?php echo ucfirst($status['priority'] ?? 'normal'); ?>
                    </span>
                </div>
                <div style="margin-top: 10px;">
                    <select id="prioritySelect" class="form-control" style="width: 100%;">
                        <option value="">Change Priority...</option>
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Patient Demographics -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-circle"></i> Patient Demographics</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Full Name</label>
                <p><strong><?php echo htmlspecialchars($patient['full_name']); ?></strong></p>
            </div>
            <div>
                <label>Date of Birth</label>
                <p><?php echo date('F j, Y', strtotime($patient['date_of_birth'])); ?> (<?php echo $patient['age']; ?> years old)</p>
            </div>
            <div>
                <label>Gender</label>
                <p><?php echo htmlspecialchars($patient['gender']); ?></p>
            </div>
            <div>
                <label>SSN</label>
                <p><?php echo $patient['ssn'] ? htmlspecialchars($patient['ssn']) : 'Not provided'; ?></p>
            </div>
            <div>
                <label>Marital Status</label>
                <p><?php echo $patient['marital_status'] ? htmlspecialchars($patient['marital_status']) : 'Not specified'; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Contact Information -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-address-card"></i> Contact Information</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Email</label>
                <p><a href="mailto:<?php echo htmlspecialchars($patient['email']); ?>"><?php echo htmlspecialchars($patient['email']); ?></a></p>
            </div>
            <div>
                <label>Cell Phone</label>
                <p><a href="tel:<?php echo htmlspecialchars($patient['cell_phone']); ?>"><?php echo htmlspecialchars($patient['cell_phone']); ?></a></p>
            </div>
            <div>
                <label>Home Phone</label>
                <p><?php echo $patient['home_phone'] ? htmlspecialchars($patient['home_phone']) : 'Not provided'; ?></p>
            </div>
            <div class="full-width">
                <label>Address</label>
                <p>
                    <?php echo htmlspecialchars($patient['address'] ?? 'Not provided'); ?><br>
                    <?php echo htmlspecialchars($patient['city'] ?? ''); ?><?php echo ($patient['city'] ?? '') && ($patient['state'] ?? '') ? ', ' : ''; ?><?php echo htmlspecialchars($patient['state'] ?? ''); ?> <?php echo htmlspecialchars($patient['zip_code'] ?? ''); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contact -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-phone-alt"></i> Emergency Contact</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Name</label>
                <p><?php echo htmlspecialchars($patient['emergency_contact_name'] ?? 'Not provided'); ?></p>
            </div>
            <div>
                <label>Phone</label>
                <p><?php echo ($patient['emergency_contact_phone'] ?? false) ? '<a href="tel:' . htmlspecialchars($patient['emergency_contact_phone']) . '">' . htmlspecialchars($patient['emergency_contact_phone']) . '</a>' : 'Not provided'; ?></p>
            </div>
            <div>
                <label>Relationship</label>
                <p><?php echo htmlspecialchars($patient['emergency_relationship'] ?? 'Not specified'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Visit Information -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-notes-medical"></i> Visit Information</h2>
    </div>
    <div class="card-body">
        <div>
            <label>Reason for Visit</label>
            <p><?php echo nl2br(htmlspecialchars($patient['reason_for_visit'])); ?></p>
        </div>
        <div style="margin-top: 15px;">
            <label>Current Medications</label>
            <p><?php echo $patient['current_medications'] ? nl2br(htmlspecialchars($patient['current_medications'])) : 'None reported'; ?></p>
        </div>
        <div style="margin-top: 15px;">
            <label>Allergies</label>
            <p><?php echo $patient['allergies'] ? nl2br(htmlspecialchars($patient['allergies'])) : 'None reported'; ?></p>
        </div>
    </div>
</div>

<!-- Insurance Information -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-shield-alt"></i> Insurance Information</h2>
    </div>
    <div class="card-body">
        <?php if ($patient['insurance_provider']): ?>
            <div class="form-grid">
                <div>
                    <label>Insurance Provider</label>
                    <p><?php echo htmlspecialchars($patient['insurance_provider'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <label>Policy Number</label>
                    <p><?php echo htmlspecialchars($patient['policy_number'] ?? 'Not provided'); ?></p>
                </div>
                <div>
                    <label>Group Number</label>
                    <p><?php echo ($patient['group_number'] ?? false) ? htmlspecialchars($patient['group_number']) : 'Not provided'; ?></p>
                </div>
                <div>
                    <label>Policy Holder Name</label>
                    <p><?php echo ($patient['policy_holder_name'] ?? false) ? htmlspecialchars($patient['policy_holder_name']) : 'Self'; ?></p>
                </div>
                <div>
                    <label>Policy Holder DOB</label>
                    <p><?php echo ($patient['policy_holder_dob'] ?? false) ? date('F j, Y', strtotime($patient['policy_holder_dob'])) : 'Not provided'; ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Patient is self-pay (no insurance information provided)
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Primary Care Physician -->
<?php if ($patient['pcp_name']): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-user-md"></i> Primary Care Physician</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Physician Name</label>
                <p><?php echo htmlspecialchars($patient['pcp_name'] ?? 'Not provided'); ?></p>
            </div>
            <div>
                <label>Phone</label>
                <p><?php echo ($patient['pcp_phone'] ?? false) ? htmlspecialchars($patient['pcp_phone']) : 'Not provided'; ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Medical History -->
<?php if ($medical_history): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-heartbeat"></i> Medical History</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Smoking Status</label>
                <p>
                    <?php echo htmlspecialchars($medical_history['smokes'] ?? 'Not specified'); ?>
                    <?php if (($medical_history['smokes'] ?? '') === 'Yes' && ($medical_history['smoking_frequency'] ?? false)): ?>
                        <br><small>Frequency: <?php echo htmlspecialchars($medical_history['smoking_frequency']); ?></small>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <label>Alcohol Consumption</label>
                <p>
                    <?php echo htmlspecialchars($medical_history['drinks_alcohol'] ?? 'Not specified'); ?>
                    <?php if (($medical_history['drinks_alcohol'] ?? '') === 'Yes' && ($medical_history['alcohol_frequency'] ?? false)): ?>
                        <br><small>Frequency: <?php echo htmlspecialchars($medical_history['alcohol_frequency']); ?></small>
                    <?php endif; ?>
                </p>
            </div>
            <div class="full-width">
                <label>Medical Conditions</label>
                <p><?php echo ($medical_history['medical_conditions'] ?? false) ? htmlspecialchars($medical_history['medical_conditions']) : 'None reported'; ?></p>
                <?php if ($medical_history['other_conditions'] ?? false): ?>
                    <p><strong>Other:</strong> <?php echo htmlspecialchars($medical_history['other_conditions']); ?></p>
                <?php endif; ?>
            </div>
            <div class="full-width">
                <label>Previous Surgeries</label>
                <p>
                    <?php echo htmlspecialchars($medical_history['previous_surgeries'] ?? 'Not specified'); ?>
                    <?php if (($medical_history['previous_surgeries'] ?? '') === 'Yes' && ($medical_history['surgery_details'] ?? false)): ?>
                        <br><?php echo nl2br(htmlspecialchars($medical_history['surgery_details'])); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="full-width">
                <label>Allergies</label>
                <p>
                    <?php echo htmlspecialchars($medical_history['has_allergies'] ?? 'Not specified'); ?>
                    <?php if (($medical_history['has_allergies'] ?? '') === 'Yes' && ($medical_history['allergy_details'] ?? false)): ?>
                        <br><?php echo nl2br(htmlspecialchars($medical_history['allergy_details'])); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($medical_history['family_history'] ?? false): ?>
            <div class="full-width">
                <label>Family History</label>
                <p><?php echo nl2br(htmlspecialchars($medical_history['family_history'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Financial Agreement -->
<?php if ($financial): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-dollar-sign"></i> Financial Agreement</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>Payment Method</label>
                <p><?php echo htmlspecialchars($financial['payment_method'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label>Signed By</label>
                <p><?php echo htmlspecialchars($financial['signature_name'] ?? 'Not provided'); ?></p>
            </div>
            <div>
                <label>Relationship to Patient</label>
                <p><?php echo htmlspecialchars($financial['relationship_to_patient'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label>Date Signed</label>
                <p><?php echo ($financial['signature_date'] ?? false) ? date('F j, Y', strtotime($financial['signature_date'])) : 'Not signed'; ?></p>
            </div>
        </div>
        <div style="margin-top: 15px;">
            <p><strong>Acknowledgments:</strong></p>
            <ul style="margin-left: 20px;">
                <li>Read and Understood: <?php echo $financial['read_understood'] ?? 'Not specified'; ?></li>
                <li>Agree to Terms: <?php echo $financial['agree_to_terms'] ?? 'Not specified'; ?></li>
                <li>Authorize Insurance: <?php echo $financial['authorize_insurance'] ?? 'Not specified'; ?></li>
                <li>Responsible for Balance: <?php echo $financial['responsible_for_balance'] ?? 'Not specified'; ?></li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Communication Preferences -->
<?php if ($additional): ?>
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-comments"></i> Communication Preferences</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div>
                <label>HIPAA Acknowledged</label>
                <p><?php echo htmlspecialchars($additional['hipaa_acknowledged'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label>Voicemail Authorization</label>
                <p><?php echo htmlspecialchars($additional['voicemail_authorization'] ?? 'Not specified'); ?></p>
            </div>
            <div>
                <label>Patient Portal Access</label>
                <p>
                    <?php echo htmlspecialchars($additional['portal_access'] ?? 'Not specified'); ?>
                    <?php if (($additional['portal_access'] ?? '') === 'Yes' && ($additional['portal_email'] ?? false)): ?>
                        <br><small>Email: <?php echo htmlspecialchars($additional['portal_email']); ?></small>
                    <?php endif; ?>
                </p>
            </div>
            <div class="full-width">
                <label>Communication Preferences</label>
                <p><?php echo ($additional['communication_preferences'] ?? false) ? htmlspecialchars($additional['communication_preferences']) : 'Not specified'; ?></p>
            </div>
            <div class="full-width">
                <label>Preferred Contact Methods</label>
                <p><?php echo ($additional['contact_methods'] ?? false) ? htmlspecialchars($additional['contact_methods']) : 'Not specified'; ?></p>
            </div>
            <?php if ($additional['authorized_person_name'] ?? false): ?>
            <div class="full-width">
                <label>Authorized Person/Caregiver</label>
                <p>
                    <strong>Name:</strong> <?php echo htmlspecialchars($additional['authorized_person_name']); ?><br>
                    <strong>Relationship:</strong> <?php echo htmlspecialchars($additional['authorized_person_relation'] ?? 'Not specified'); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($additional['authorized_person_phone'] ?? 'Not provided'); ?><br>
                    <strong>Authorized to Discuss:</strong> <?php echo htmlspecialchars($additional['authorize_discussion'] ?? 'Not specified'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Admin Notes -->
<div class="dashboard-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-sticky-note"></i> Admin Notes</h2>
    </div>
    <div class="card-body">
        <form id="notesForm">
            <textarea
                id="adminNotes"
                class="form-control"
                rows="5"
                placeholder="Add notes about this patient (visible only to admin staff)..."
            ><?php echo htmlspecialchars($status['admin_notes'] ?? ''); ?></textarea>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                <i class="fas fa-save"></i> Save Notes
            </button>
        </form>
    </div>
</div>

<script>
// Status update
document.getElementById('statusSelect').addEventListener('change', function() {
    const newStatus = this.value;
    if (!newStatus) return;

    AdminUtils.confirm(`Change patient status to "${newStatus}"?`, () => {
        StatusManager.updatePatientStatus(<?php echo $patient_id; ?>, newStatus, (data) => {
            location.reload();
        });
    });
});

// Priority update
document.getElementById('prioritySelect').addEventListener('change', function() {
    const newPriority = this.value;
    if (!newPriority) return;

    fetch('/admin/patients/update_priority.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: <?php echo $patient_id; ?>,
            priority: newPriority
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            AdminUtils.showAlert('Priority updated successfully', 'success');
            location.reload();
        } else {
            AdminUtils.showAlert('Failed to update priority', 'error');
        }
    });
});

// Notes form
document.getElementById('notesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const notes = document.getElementById('adminNotes').value;

    fetch('/admin/patients/update_notes.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            patient_id: <?php echo $patient_id; ?>,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            AdminUtils.showAlert('Notes saved successfully', 'success');
        } else {
            AdminUtils.showAlert('Failed to save notes', 'error');
        }
    });
});
</script>

<?php
$conn->close();
include __DIR__ . '/../../../includes/admin_footer.php';
?>
