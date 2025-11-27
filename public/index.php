<?php
/**
 * Homepage - Patient Entry Point
 * Choose between New Patient or Returning Patient
 */
session_start();

// Clear any existing patient session when visiting homepage
if (isset($_SESSION['patient_id']) || isset($_SESSION['returning_patient_id'])) {
    session_unset();
    session_destroy();
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimeHealth Urgent Care - Patient Forms</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .patient-type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }

        .patient-type-card {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .patient-type-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .patient-type-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .patient-type-card:hover::before {
            transform: scaleX(1);
        }

        .patient-type-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }

        .patient-type-title {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .patient-type-description {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .patient-type-features {
            text-align: left;
            margin: 20px 0;
            padding: 0 10px;
        }

        .patient-type-features li {
            color: #555;
            font-size: 14px;
            margin: 8px 0;
            padding-left: 25px;
            position: relative;
        }

        .patient-type-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
            font-size: 16px;
        }

        .patient-type-button {
            display: inline-block;
            padding: 15px 40px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .patient-type-button:hover {
            background: var(--secondary-color);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        .patient-type-button.secondary {
            background: #6c757d;
        }

        .patient-type-button.secondary:hover {
            background: #5a6268;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 40px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-section h1 {
            color: white;
            font-size: 42px;
            margin-bottom: 15px;
        }

        .welcome-section p {
            font-size: 18px;
            opacity: 0.95;
        }

        .info-banner {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }

        .info-banner-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .patient-type-selector {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 40px 20px;
            }

            .welcome-section h1 {
                font-size: 32px;
            }

            .patient-type-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome to PrimeHealth Urgent Care</h1>
            <p>Quality healthcare when you need it most</p>
        </div>

        <!-- Patient Type Selection -->
        <div class="form-section">
            <h2 style="text-align: center; margin-bottom: 15px;">Let's Get Started</h2>
            <p style="text-align: center; color: #666; margin-bottom: 40px; font-size: 16px;">
                Please select the option that applies to you
            </p>

            <div class="patient-type-selector">
                <!-- New Patient Card -->
                <div class="patient-type-card">
                    <span class="patient-type-icon">📋</span>
                    <h3 class="patient-type-title">New Patient</h3>
                    <p class="patient-type-description">
                        First time visiting us? Start here to complete all required forms.
                    </p>
                    <ul class="patient-type-features">
                        <li>Complete registration form</li>
                        <li>Medical history questionnaire</li>
                        <li>Insurance information</li>
                        <li>Consent forms</li>
                    </ul>
                    <a href="forms/1_patient_registration.php" class="patient-type-button">
                        Start Registration
                    </a>
                    <p style="margin-top: 15px; font-size: 13px; color: #999;">
                        Estimated time: 20-25 minutes
                    </p>
                </div>

                <!-- Returning Patient Card -->
                <div class="patient-type-card">
                    <span class="patient-type-icon">🔄</span>
                    <h3 class="patient-type-title">Returning Patient</h3>
                    <p class="patient-type-description">
                        Been here before? Look up your information and update any changes.
                    </p>
                    <ul class="patient-type-features">
                        <li>Quick information lookup</li>
                        <li>Review previous details</li>
                        <li>Update only what changed</li>
                        <li>Faster check-in process</li>
                    </ul>
                    <a href="returning_patient.php" class="patient-type-button">
                        Find My Information
                    </a>
                    <p style="margin-top: 15px; font-size: 13px; color: #999;">
                        Estimated time: 5-10 minutes
                    </p>
                </div>
            </div>
        </div>

        <!-- Information Banner -->
        <div class="info-banner">
            <div class="info-banner-title">Important Information</div>
            <ul style="margin: 10px 0 0 20px; line-height: 1.8; color: #555;">
                <li>Please arrive 15 minutes before your scheduled appointment</li>
                <li>Bring a valid photo ID and insurance card</li>
                <li>All forms must be completed before your appointment</li>
                <li>If you're unsure whether you've visited us before, choose "Returning Patient" to check</li>
            </ul>
        </div>

        <!-- Help Section -->
        <div class="form-section" style="background-color: var(--bg-light); border-left: 4px solid var(--primary-color);">
            <h2>Need Help?</h2>
            <p style="margin-bottom: 12px;">If you have questions or need assistance, please:</p>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Call us at: <strong>(555) 123-4567</strong></li>
                <li>Email us at: <strong>info@primehealthurgentcare.com</strong></li>
                <li>Visit our location during business hours</li>
            </ul>
        </div>

        <!-- Privacy Notice -->
        <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background-color: #f8f9fa; border-radius: var(--border-radius); font-size: 14px; color: #666;">
            <p style="margin-bottom: 8px;">
                <strong>🔒 Privacy & Security:</strong> Your information is protected and secure. We comply with HIPAA regulations to ensure your medical information remains confidential.
            </p>
            <p>
                By completing these forms, you acknowledge that the information provided is accurate to the best of your knowledge.
            </p>
        </div>

    </div>
</body>
</html>
