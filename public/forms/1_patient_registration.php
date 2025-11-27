<?php
/**
 * Form 1: Patient Registration
 * Supports both new patients and returning patients (with pre-fill)
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/returning_patient_functions.php';

// Check if this is a returning patient
$is_returning = isset($_SESSION['returning_patient_id']) && isset($_SESSION['is_returning_patient']);
$patient_data = null;
$last_visit = null;

if ($is_returning) {
    $conn = getDBConnection();
    $data = loadPatientData($conn, $_SESSION['returning_patient_id']);
    $patient_data = $data['patient'] ?? null;
    $last_visit = getLastVisit($conn, $_SESSION['returning_patient_id']);
    closeDBConnection($conn);
}

// Helper function to get value (pre-filled or empty)
function getValue($patient_data, $field, $default = '') {
    return $patient_data[$field] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .returning-patient-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }

        .returning-patient-banner h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
        }

        .returning-patient-banner p {
            margin: 0;
            opacity: 0.95;
            font-size: 14px;
        }

        .pre-filled-field {
            background-color: #f0f8ff !important;
            border-left: 3px solid #4a90e2;
        }

        .field-status-indicator {
            display: inline-block;
            margin-left: 8px;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e3f2fd;
            color: #1976d2;
        }

        .help-tooltip {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: #2196F3;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            cursor: help;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_returning && $patient_data): ?>
        <!-- Returning Patient Banner -->
        <div class="returning-patient-banner">
            <h3>📋 Reviewing Your Information</h3>
            <p>
                Information from your last visit on
                <?php echo $last_visit ? formatDateDisplay($last_visit['visit_date'], 'F j, Y') : 'your previous visit'; ?>.
                Please review and update any changes.
            </p>
        </div>
        <?php endif; ?>

        <div class="form-header">
            <h1>Patient Registration</h1>
            <p>Please fill out this form completely and accurately</p>
            <?php if ($is_returning): ?>
            <p style="color: #2196F3; font-size: 14px; margin-top: 10px;">
                <strong>Note:</strong> Fields with blue background contain your previous information.
                Update only what has changed.
            </p>
            <?php endif; ?>
        </div>

        <form id="patientRegistrationForm" method="POST" action="../process/save_patient_registration.php">

            <?php if ($is_returning): ?>
            <!-- Hidden field to indicate this is an update -->
            <input type="hidden" name="is_update" value="1">
            <input type="hidden" name="existing_patient_id" value="<?php echo htmlspecialchars($_SESSION['returning_patient_id']); ?>">
            <?php endif; ?>

            <!-- Patient Information Section -->
            <div class="form-section">
                <h2>Patient Information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lastName">
                            Last Name <span class="required">*</span>
                            <?php if ($is_returning): ?>
                            <span class="field-status-indicator">From last visit</span>
                            <?php endif; ?>
                        </label>
                        <input
                            type="text"
                            id="lastName"
                            name="lastName"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'last_name')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="firstName">
                            First Name <span class="required">*</span>
                            <?php if ($is_returning): ?>
                            <span class="field-status-indicator">From last visit</span>
                            <?php endif; ?>
                        </label>
                        <input
                            type="text"
                            id="firstName"
                            name="firstName"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'first_name')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="middleName">Middle Name</label>
                        <input
                            type="text"
                            id="middleName"
                            name="middleName"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'middle_name')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['middle_name']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="dateOfBirth">
                            Date of Birth <span class="required">*</span>
                            <?php if ($is_returning): ?>
                            <span class="field-status-indicator">From last visit</span>
                            <?php endif; ?>
                        </label>
                        <input
                            type="date"
                            id="dateOfBirth"
                            name="dateOfBirth"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'date_of_birth')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-row three-col">
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input
                            type="number"
                            id="age"
                            name="age"
                            min="0"
                            max="150"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'age')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['age']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select
                            id="gender"
                            name="gender"
                            required
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                            <option value="">Select...</option>
                            <option value="Male" <?php echo getValue($patient_data, 'gender') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo getValue($patient_data, 'gender') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo getValue($patient_data, 'gender') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ssn">Social Security Number</label>
                        <input
                            type="text"
                            id="ssn"
                            name="ssn"
                            placeholder="XXX-XX-XXXX"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'ssn')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['ssn']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="form-section">
                <h2>Contact Information</h2>

                <div class="form-group">
                    <label for="address">Street Address <span class="required">*</span></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        required
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'address')); ?>"
                        class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'city')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="state">State <span class="required">*</span></label>
                        <input
                            type="text"
                            id="state"
                            name="state"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'state')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="zipCode">ZIP Code <span class="required">*</span></label>
                        <input
                            type="text"
                            id="zipCode"
                            name="zipCode"
                            pattern="[0-9]{5}"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'zip_code')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="homePhone">Home Phone</label>
                        <input
                            type="tel"
                            id="homePhone"
                            name="homePhone"
                            placeholder="(XXX) XXX-XXXX"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'home_phone')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['home_phone']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="cellPhone">Cell Phone <span class="required">*</span></label>
                        <input
                            type="tel"
                            id="cellPhone"
                            name="cellPhone"
                            placeholder="(XXX) XXX-XXXX"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'cell_phone')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'email')); ?>"
                        class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="maritalStatus">Marital Status</label>
                    <select
                        id="maritalStatus"
                        name="maritalStatus"
                        class="<?php echo $is_returning && !empty($patient_data['marital_status']) ? 'pre-filled-field' : ''; ?>"
                    >
                        <option value="">Select...</option>
                        <option value="Single" <?php echo getValue($patient_data, 'marital_status') === 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo getValue($patient_data, 'marital_status') === 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Divorced" <?php echo getValue($patient_data, 'marital_status') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Widowed" <?php echo getValue($patient_data, 'marital_status') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    </select>
                </div>
            </div>

            <!-- Emergency Contact Section -->
            <div class="form-section">
                <h2>Emergency Contact</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emergencyContactName">Emergency Contact Name <span class="required">*</span></label>
                        <input
                            type="text"
                            id="emergencyContactName"
                            name="emergencyContactName"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'emergency_contact_name')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="emergencyContactPhone">Emergency Contact Phone <span class="required">*</span></label>
                        <input
                            type="tel"
                            id="emergencyContactPhone"
                            name="emergencyContactPhone"
                            placeholder="(XXX) XXX-XXXX"
                            required
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'emergency_contact_phone')); ?>"
                            class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="emergencyRelationship">Relationship to Patient <span class="required">*</span></label>
                    <input
                        type="text"
                        id="emergencyRelationship"
                        name="emergencyRelationship"
                        required
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'emergency_relationship')); ?>"
                        class="<?php echo $is_returning ? 'pre-filled-field' : ''; ?>"
                    >
                </div>
            </div>

            <!-- Insurance Information Section -->
            <div class="form-section">
                <h2>Insurance Information</h2>

                <div class="form-group">
                    <label for="insuranceProvider">Insurance Provider</label>
                    <input
                        type="text"
                        id="insuranceProvider"
                        name="insuranceProvider"
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'insurance_provider')); ?>"
                        class="<?php echo $is_returning && !empty($patient_data['insurance_provider']) ? 'pre-filled-field' : ''; ?>"
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="policyNumber">Policy Number</label>
                        <input
                            type="text"
                            id="policyNumber"
                            name="policyNumber"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'policy_number')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['policy_number']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="groupNumber">Group Number</label>
                        <input
                            type="text"
                            id="groupNumber"
                            name="groupNumber"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'group_number')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['group_number']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="policyHolderName">Policy Holder Name (if different from patient)</label>
                    <input
                        type="text"
                        id="policyHolderName"
                        name="policyHolderName"
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'policy_holder_name')); ?>"
                        class="<?php echo $is_returning && !empty($patient_data['policy_holder_name']) ? 'pre-filled-field' : ''; ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="policyHolderDOB">Policy Holder Date of Birth</label>
                    <input
                        type="date"
                        id="policyHolderDOB"
                        name="policyHolderDOB"
                        value="<?php echo htmlspecialchars(getValue($patient_data, 'policy_holder_dob')); ?>"
                        class="<?php echo $is_returning && !empty($patient_data['policy_holder_dob']) ? 'pre-filled-field' : ''; ?>"
                    >
                </div>
            </div>

            <!-- Primary Care Physician Section -->
            <div class="form-section">
                <h2>Primary Care Physician</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pcpName">Physician Name</label>
                        <input
                            type="text"
                            id="pcpName"
                            name="pcpName"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'pcp_name')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['pcp_name']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="pcpPhone">Physician Phone</label>
                        <input
                            type="tel"
                            id="pcpPhone"
                            name="pcpPhone"
                            placeholder="(XXX) XXX-XXXX"
                            value="<?php echo htmlspecialchars(getValue($patient_data, 'pcp_phone')); ?>"
                            class="<?php echo $is_returning && !empty($patient_data['pcp_phone']) ? 'pre-filled-field' : ''; ?>"
                        >
                    </div>
                </div>
            </div>

            <!-- Visit Information Section -->
            <div class="form-section">
                <h2>Visit Information</h2>

                <div class="form-group">
                    <label for="reasonForVisit">
                        Reason for Visit <span class="required">*</span>
                        <?php if ($is_returning): ?>
                        <span class="help-tooltip" title="Enter your reason for TODAY'S visit">?</span>
                        <?php endif; ?>
                    </label>
                    <textarea
                        id="reasonForVisit"
                        name="reasonForVisit"
                        rows="4"
                        required
                        placeholder="<?php echo $is_returning ? 'Describe your reason for visiting us today' : ''; ?>"
                    ><?php echo $is_returning && $last_visit ? htmlspecialchars($last_visit['reason_for_visit'] ?? '') : ''; ?></textarea>
                    <?php if ($is_returning && $last_visit && !empty($last_visit['reason_for_visit'])): ?>
                    <p style="font-size: 13px; color: #666; margin-top: 5px;">
                        <strong>Last visit:</strong> <?php echo htmlspecialchars($last_visit['reason_for_visit']); ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="allergies">Known Allergies</label>
                    <textarea
                        id="allergies"
                        name="allergies"
                        rows="3"
                        placeholder="List all known allergies, or write 'None'"
                        class="<?php echo $is_returning && !empty($patient_data['allergies']) ? 'pre-filled-field' : ''; ?>"
                    ><?php echo htmlspecialchars(getValue($patient_data, 'allergies')); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="currentMedications">Current Medications</label>
                    <textarea
                        id="currentMedications"
                        name="currentMedications"
                        rows="3"
                        placeholder="List all current medications, or write 'None'"
                        class="<?php echo $is_returning && !empty($patient_data['current_medications']) ? 'pre-filled-field' : ''; ?>"
                    ><?php echo htmlspecialchars(getValue($patient_data, 'current_medications')); ?></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo $is_returning ? '../patient_found.php' : '../index.php'; ?>'">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">
                    <?php echo $is_returning ? 'Save & Continue to Medical History' : 'Continue to Medical History'; ?>
                </button>
            </div>

        </form>
    </div>

    <script>
        // Auto-calculate age from date of birth
        document.getElementById('dateOfBirth').addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();

            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }

            document.getElementById('age').value = age;
        });

        // Track field changes for returning patients
        <?php if ($is_returning): ?>
        const originalValues = {};
        let changedFields = [];

        // Store original values
        document.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.name && field.name !== 'is_update' && field.name !== 'existing_patient_id') {
                originalValues[field.name] = field.value;
            }
        });

        // Track changes
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('change', function() {
                if (this.name && originalValues[this.name] !== undefined) {
                    if (this.value !== originalValues[this.name]) {
                        if (!changedFields.includes(this.name)) {
                            changedFields.push(this.name);
                        }
                        // Remove pre-filled styling if changed
                        this.classList.remove('pre-filled-field');
                        this.style.borderLeft = '3px solid #28a745'; // Green for changed
                    } else {
                        // Remove from changed list if reverted
                        changedFields = changedFields.filter(f => f !== this.name);
                        this.classList.add('pre-filled-field');
                        this.style.borderLeft = '3px solid #4a90e2';
                    }
                }
            });
        });

        // Add changed fields to form submission
        document.getElementById('patientRegistrationForm').addEventListener('submit', function() {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'changed_fields';
            hiddenInput.value = JSON.stringify(changedFields);
            this.appendChild(hiddenInput);
        });
        <?php endif; ?>
    </script>
</body>
</html>
