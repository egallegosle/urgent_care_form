<?php
/**
 * Patient Found Confirmation Page
 * Shows patient their information was found and provides consent to proceed
 */

session_start();
require_once '../config/database.php';
require_once '../includes/returning_patient_functions.php';

// Check if patient session exists
if (!isset($_SESSION['returning_patient_id']) || !isset($_SESSION['visit_id'])) {
    header('Location: returning_patient.php?error=' . urlencode('Session expired. Please search again.'));
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['session_expires']) && time() > $_SESSION['session_expires']) {
    session_unset();
    session_destroy();
    header('Location: returning_patient.php?error=' . urlencode('Your session has expired. Please search again.'));
    exit;
}

// Get database connection
$conn = getDBConnection();

// Load patient data
$patient_id = $_SESSION['returning_patient_id'];
$patient = loadPatientData($conn, $patient_id);

if (!$patient || !$patient['patient']) {
    session_unset();
    session_destroy();
    header('Location: returning_patient.php?error=' . urlencode('Unable to load patient data. Please try again.'));
    exit;
}

$patient_info = $patient['patient'];

// Get last visit information
$last_visit = getLastVisit($conn, $patient_id);

// Format display data
$patient_name = $patient_info['first_name'] . ' ' . $patient_info['last_name'];
$email_masked = substr($patient_info['email'], 0, 2) . '***@' . substr(strstr($patient_info['email'], '@'), 1);
$ssn_masked = maskSSN($patient_info['ssn'] ?? '');

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Back - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 40px auto;
        }

        .confirmation-card {
            background: white;
            border: 2px solid #28a745;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            display: block;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            color: #28a745;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .success-subtitle {
            color: #666;
            font-size: 16px;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-item {
            padding: 10px 0;
        }

        .info-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .last-visit-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }

        .last-visit-date {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .last-visit-time {
            font-size: 14px;
            opacity: 0.9;
        }

        .instructions-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .instructions-title {
            color: #856404;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .instructions-list {
            margin: 0;
            padding-left: 20px;
        }

        .instructions-list li {
            color: #856404;
            margin: 8px 0;
            line-height: 1.6;
        }

        .consent-box {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }

        .consent-checkbox {
            display: flex;
            align-items: flex-start;
            margin: 15px 0;
        }

        .consent-checkbox input[type="checkbox"] {
            margin-top: 4px;
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .consent-checkbox label {
            cursor: pointer;
            line-height: 1.6;
            color: #333;
            font-size: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 15px;
            font-size: 16px;
            text-align: center;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .privacy-note {
            text-align: center;
            font-size: 13px;
            color: #666;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px auto;
            }

            .confirmation-card {
                padding: 25px 20px;
            }

            .success-title {
                font-size: 26px;
            }

            .success-icon {
                font-size: 64px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .patient-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-card">
                <!-- Success Header -->
                <div class="success-header">
                    <span class="success-icon">✅</span>
                    <h1 class="success-title">Welcome Back, <?php echo htmlspecialchars($patient_info['first_name']); ?>!</h1>
                    <p class="success-subtitle">We found your information in our system</p>
                </div>

                <!-- Last Visit Info -->
                <?php if ($last_visit): ?>
                <div class="last-visit-banner">
                    <div class="last-visit-date">
                        Last Visit: <?php echo formatDateDisplay($last_visit['visit_date'], 'F j, Y'); ?>
                    </div>
                    <div class="last-visit-time">
                        <?php echo timeSinceVisit($last_visit['visit_date']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Patient Information Display -->
                <div class="patient-info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient_name); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Email (Verified)</div>
                        <div class="info-value"><?php echo htmlspecialchars($email_masked); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo formatDateDisplay($patient_info['date_of_birth'], 'm/d/Y'); ?></div>
                    </div>

                    <?php if (!empty($ssn_masked) && $ssn_masked !== 'XXX-XX-XXXX'): ?>
                    <div class="info-item">
                        <div class="info-label">SSN (Last 4)</div>
                        <div class="info-value"><?php echo htmlspecialchars($ssn_masked); ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Instructions -->
                <div class="instructions-box">
                    <div class="instructions-title">What Happens Next?</div>
                    <ul class="instructions-list">
                        <li>Your information from your last visit is ready to review</li>
                        <li>You'll go through each form to verify your details</li>
                        <li>Update anything that has changed (address, phone, insurance, etc.)</li>
                        <li>Forms you don't change will keep your previous information</li>
                        <li>This typically takes 5-10 minutes</li>
                    </ul>
                </div>

                <!-- Consent Box -->
                <div class="consent-box">
                    <h3 style="color: #0c5460; margin-bottom: 15px; font-size: 18px;">
                        Required Consent
                    </h3>

                    <div class="consent-checkbox">
                        <input type="checkbox" id="consentData" name="consentData" required>
                        <label for="consentData">
                            I confirm this is my information and I consent to reviewing and updating my stored medical records for this visit.
                        </label>
                    </div>

                    <div class="consent-checkbox">
                        <input type="checkbox" id="consentAccuracy" name="consentAccuracy" required>
                        <label for="consentAccuracy">
                            I understand that I am responsible for ensuring all information I provide is accurate and up-to-date.
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="returning_patient.php" class="btn btn-cancel">
                        Cancel
                    </a>
                    <button id="continueBtn" class="btn btn-primary" disabled onclick="proceedToForms()">
                        Continue to Review Forms
                    </button>
                </div>

                <!-- Privacy Note -->
                <div class="privacy-note">
                    🔒 Your privacy is protected. All data is encrypted and HIPAA-compliant.
                </div>
            </div>

            <!-- Help Section -->
            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <p style="color: #666; margin-bottom: 10px; font-size: 14px;">
                    This doesn't look like your information?
                </p>
                <a href="returning_patient.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                    Search Again
                </a>
                <span style="color: #999; margin: 0 10px;">|</span>
                <a href="forms/1_patient_registration.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                    Register as New Patient
                </a>
            </div>
        </div>
    </div>

    <script>
        // Enable continue button only when both checkboxes are checked
        const consentData = document.getElementById('consentData');
        const consentAccuracy = document.getElementById('consentAccuracy');
        const continueBtn = document.getElementById('continueBtn');

        function checkConsent() {
            if (consentData.checked && consentAccuracy.checked) {
                continueBtn.disabled = false;
                continueBtn.style.opacity = '1';
                continueBtn.style.cursor = 'pointer';
            } else {
                continueBtn.disabled = true;
                continueBtn.style.opacity = '0.5';
                continueBtn.style.cursor = 'not-allowed';
            }
        }

        consentData.addEventListener('change', checkConsent);
        consentAccuracy.addEventListener('change', checkConsent);

        function proceedToForms() {
            if (!consentData.checked || !consentAccuracy.checked) {
                alert('Please check both consent boxes to continue.');
                return false;
            }

            // Log consent timestamp in session
            fetch('process/log_consent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    consent_type: 'returning_patient_data_review',
                    timestamp: new Date().toISOString()
                })
            }).then(() => {
                // Redirect to first form
                window.location.href = 'forms/1_patient_registration.php';
            }).catch(() => {
                // Still allow proceeding if logging fails
                window.location.href = 'forms/1_patient_registration.php';
            });
        }

        // Session timeout warning (after 25 minutes)
        setTimeout(function() {
            if (confirm('Your session will expire in 5 minutes. Would you like to continue?')) {
                // Refresh session
                fetch('process/refresh_session.php', { method: 'POST' });
            }
        }, 25 * 60 * 1000);
    </script>
</body>
</html>
