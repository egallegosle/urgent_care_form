<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms Submitted Successfully - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .success-icon {
            text-align: center;
            font-size: 80px;
            color: var(--success-color);
            margin: 20px 0;
        }
        .success-message {
            text-align: center;
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: var(--border-radius);
            padding: var(--spacing-lg);
            margin: var(--spacing-lg) 0;
        }
        .next-steps {
            margin-top: var(--spacing-lg);
        }
        .next-steps li {
            margin-bottom: var(--spacing-md);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>

        <div class="success-message">
            <h1 style="color: var(--success-color); margin-bottom: 10px;">Thank You!</h1>
            <h2 style="color: var(--text-color); font-weight: normal; font-size: 20px;">Your forms have been submitted successfully</h2>
        </div>

        <div class="info-box">
            <p style="margin: 0; font-size: 16px;">
                <strong>Confirmation:</strong> All your patient intake forms have been received and saved to our system.
                Your information will be reviewed by our staff before your visit.
            </p>
        </div>

        <div class="form-section">
            <h2>What Happens Next?</h2>

            <div class="next-steps">
                <ol style="margin-left: 20px;">
                    <li>
                        <strong>Verification:</strong> Our staff will review your submitted information and verify your insurance coverage (if applicable).
                    </li>
                    <li>
                        <strong>DrChrono Integration:</strong> Your information will be synchronized with our electronic health records system.
                    </li>
                    <li>
                        <strong>Arrival:</strong> When you arrive for your appointment, please check in at the front desk. Since you've already completed these forms, your check-in will be much faster!
                    </li>
                    <li>
                        <strong>Questions:</strong> If our staff has any questions about the information you provided, they will contact you using the contact information you provided.
                    </li>
                </ol>
            </div>
        </div>

        <div class="form-section" style="background-color: var(--bg-light);">
            <h2>Important Reminders</h2>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Please arrive <strong>15 minutes early</strong> for your appointment</li>
                <li>Bring a valid <strong>photo ID</strong></li>
                <li>Bring your <strong>insurance card</strong> (if applicable)</li>
                <li>Bring a <strong>list of current medications</strong> (or the bottles)</li>
                <li>Bring any <strong>relevant medical records</strong> from other providers</li>
            </ul>
        </div>

        <div class="form-section">
            <h2>Need to Make Changes?</h2>
            <p>If you need to update any information you provided, please:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Call us at: <strong>(555) 123-4567</strong></li>
                <li>Email us at: <strong>info@primehealthurgentcare.com</strong></li>
                <li>Speak with staff when you arrive for your appointment</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: var(--spacing-lg);">
            <a href="index.php" class="btn btn-primary">Return to Home</a>
        </div>

        <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background-color: #f8f9fa; border-radius: var(--border-radius); text-align: center; font-size: 14px; color: #666;">
            <p style="margin: 0;">
                <strong>Privacy Notice:</strong> Your information is protected and secure. We comply with HIPAA regulations to ensure your medical information remains confidential.
            </p>
        </div>
    </div>
</body>
</html>
