<?php
/**
 * Returning Patient Lookup Page
 * Allows patients to find their existing information
 */
session_start();

// Clear any previous session data
unset($_SESSION['returning_patient_id']);
unset($_SESSION['visit_id']);
unset($_SESSION['patient_id']);

// Get error/success messages from URL
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$info_message = isset($_GET['info']) ? htmlspecialchars($_GET['info']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returning Patient Lookup - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .lookup-container {
            max-width: 600px;
            margin: 40px auto;
        }

        .lookup-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .lookup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .lookup-icon {
            font-size: 72px;
            margin-bottom: 20px;
            display: block;
        }

        .lookup-title {
            color: var(--primary-color);
            font-size: 32px;
            margin-bottom: 10px;
        }

        .lookup-subtitle {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }

        .security-badge {
            background: #f0f8ff;
            border: 1px solid #4a90e2;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
            text-align: center;
        }

        .security-badge-icon {
            font-size: 24px;
            margin-right: 10px;
        }

        .security-badge-text {
            color: #2c5282;
            font-size: 14px;
            font-weight: 500;
        }

        .form-help-text {
            font-size: 14px;
            color: #666;
            margin-top: 8px;
            line-height: 1.5;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 30px 0;
        }

        .new-patient-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }

        .new-patient-text {
            color: #555;
            margin-bottom: 15px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.6;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
        }

        .alert-icon {
            font-size: 20px;
            margin-right: 10px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .lookup-container {
                margin: 20px auto;
            }

            .lookup-card {
                padding: 25px 20px;
            }

            .lookup-title {
                font-size: 26px;
            }

            .lookup-icon {
                font-size: 56px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lookup-container">
            <!-- Back to Home Link -->
            <div style="margin-bottom: 20px;">
                <a href="index.php" style="color: var(--primary-color); text-decoration: none; font-size: 14px;">
                    &larr; Back to Home
                </a>
            </div>

            <div class="lookup-card">
                <!-- Header -->
                <div class="lookup-header">
                    <span class="lookup-icon">🔍</span>
                    <h1 class="lookup-title">Find Your Information</h1>
                    <p class="lookup-subtitle">
                        We'll look up your previous visit information so you can quickly update your forms.
                    </p>
                </div>

                <!-- Security Badge -->
                <div class="security-badge">
                    <span class="security-badge-icon">🔒</span>
                    <span class="security-badge-text">
                        Your information is secure and HIPAA-compliant
                    </span>
                </div>

                <!-- Error Messages -->
                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠</span>
                    <strong>Error:</strong> <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Info Messages -->
                <?php if ($info_message): ?>
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ</span>
                    <?php echo $info_message; ?>
                </div>
                <?php endif; ?>

                <!-- Lookup Form -->
                <form method="POST" action="process/lookup_patient.php" id="lookupForm">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            placeholder="your.email@example.com"
                            autocomplete="email"
                            value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"
                        >
                        <p class="form-help-text">
                            Enter the email address you used when you last visited us.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                        <input
                            type="date"
                            id="dateOfBirth"
                            name="dateOfBirth"
                            required
                            autocomplete="bday"
                            max="<?php echo date('Y-m-d'); ?>"
                        >
                        <p class="form-help-text">
                            Enter your date of birth for verification (MM/DD/YYYY).
                        </p>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">
                            Find My Information
                        </button>
                    </div>
                </form>

                <div class="divider"></div>

                <!-- Troubleshooting Tips -->
                <div style="background: #fffbf0; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="color: #d97706; font-size: 16px; margin-bottom: 12px;">
                        Can't find your information?
                    </h3>
                    <ul style="margin-left: 20px; color: #666; font-size: 14px; line-height: 1.8;">
                        <li>Double-check your email address for typos</li>
                        <li>Make sure you're using the same email from your last visit</li>
                        <li>Verify your date of birth is entered correctly</li>
                        <li>If you've never visited us before, please register as a new patient</li>
                    </ul>
                </div>

                <!-- New Patient Section -->
                <div class="new-patient-section">
                    <p class="new-patient-text">
                        <strong>First time visiting us?</strong><br>
                        If you're a new patient, please start with our new patient registration.
                    </p>
                    <a href="forms/1_patient_registration.php" class="btn-secondary">
                        Register as New Patient
                    </a>
                </div>
            </div>

            <!-- Help Section -->
            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <p style="color: #666; margin-bottom: 10px; font-size: 14px;">
                    <strong>Need assistance?</strong>
                </p>
                <p style="color: #666; font-size: 14px;">
                    Call us at <strong>(555) 123-4567</strong> or email
                    <strong>info@primehealthurgentcare.com</strong>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('lookupForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const dob = document.getElementById('dateOfBirth').value;

            if (!email || !dob) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }

            // Validate date is not in future
            const dobDate = new Date(dob);
            const today = new Date();
            if (dobDate > today) {
                e.preventDefault();
                alert('Date of birth cannot be in the future.');
                return false;
            }

            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Searching...';
        });
    </script>
</body>
</html>
