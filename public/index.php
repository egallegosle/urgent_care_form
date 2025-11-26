<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimeHealth Urgent Care - Patient Forms</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Welcome to PrimeHealth Urgent Care</h1>
            <p>Please complete the following patient forms before your visit</p>
        </div>

        <div class="alert alert-info">
            <strong>Important:</strong> Please complete all forms in order. Each form should take approximately 5-10 minutes to complete. All forms must be completed before your appointment.
        </div>

        <!-- Form Navigation -->
        <div class="form-section">
            <h2>Patient Registration Forms</h2>
            <p style="margin-bottom: 20px;">Click on each form below to begin. We recommend completing them in the order listed.</p>

            <!-- Form 1 -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: var(--spacing-md); margin-bottom: var(--spacing-md); background-color: white;">
                <h3 style="color: var(--primary-color); margin-bottom: 8px;">1. Patient Registration</h3>
                <p style="color: #666; margin-bottom: 12px;">Provide your personal information, contact details, insurance information, and reason for visit.</p>
                <p style="font-size: 14px; color: #999; margin-bottom: 12px;">Estimated time: 5-7 minutes</p>
                <a href="forms/1_patient_registration.php" class="btn btn-primary">Start Form</a>
            </div>

            <!-- Form 2 -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: var(--spacing-md); margin-bottom: var(--spacing-md); background-color: white;">
                <h3 style="color: var(--primary-color); margin-bottom: 8px;">2. Medical History</h3>
                <p style="color: #666; margin-bottom: 12px;">Share your medical history, current medications, allergies, and lifestyle information.</p>
                <p style="font-size: 14px; color: #999; margin-bottom: 12px;">Estimated time: 7-10 minutes</p>
                <a href="forms/2_medical_history.php" class="btn btn-primary">Start Form</a>
            </div>

            <!-- Form 3 -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: var(--spacing-md); margin-bottom: var(--spacing-md); background-color: white;">
                <h3 style="color: var(--primary-color); margin-bottom: 8px;">3. Patient Consent for Treatment</h3>
                <p style="color: #666; margin-bottom: 12px;">Review and sign the consent form for medical treatment and procedures.</p>
                <p style="font-size: 14px; color: #999; margin-bottom: 12px;">Estimated time: 3-5 minutes</p>
                <a href="forms/3_patient_consent.php" class="btn btn-primary">Start Form</a>
            </div>

            <!-- Form 4 -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: var(--spacing-md); margin-bottom: var(--spacing-md); background-color: white;">
                <h3 style="color: var(--primary-color); margin-bottom: 8px;">4. Financial Agreement</h3>
                <p style="color: #666; margin-bottom: 12px;">Review and acknowledge your financial responsibility and payment terms.</p>
                <p style="font-size: 14px; color: #999; margin-bottom: 12px;">Estimated time: 3-5 minutes</p>
                <a href="forms/4_financial_agreement.php" class="btn btn-primary">Start Form</a>
            </div>

            <!-- Form 5 -->
            <div style="border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: var(--spacing-md); margin-bottom: var(--spacing-md); background-color: white;">
                <h3 style="color: var(--primary-color); margin-bottom: 8px;">5. Additional Consents</h3>
                <p style="color: #666; margin-bottom: 12px;">Set your communication preferences and authorize information sharing.</p>
                <p style="font-size: 14px; color: #999; margin-bottom: 12px;">Estimated time: 3-5 minutes</p>
                <a href="forms/5_additional_consents.php" class="btn btn-primary">Start Form</a>
            </div>
        </div>

        <!-- Help Section -->
        <div class="form-section" style="background-color: var(--bg-light); border-left: 4px solid var(--primary-color);">
            <h2>Need Help?</h2>
            <p style="margin-bottom: 12px;">If you have questions or need assistance completing these forms, please:</p>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li>Call us at: <strong>(555) 123-4567</strong></li>
                <li>Email us at: <strong>info@primehealthurgentcare.com</strong></li>
                <li>Visit our location during business hours</li>
            </ul>
        </div>

        <!-- Privacy Notice -->
        <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background-color: #f8f9fa; border-radius: var(--border-radius); font-size: 14px; color: #666;">
            <p style="margin-bottom: 8px;">
                <strong>Privacy & Security:</strong> Your information is protected and secure. We comply with HIPAA regulations to ensure your medical information remains confidential.
            </p>
            <p>
                By completing these forms, you acknowledge that the information provided is accurate to the best of your knowledge.
            </p>
        </div>

    </div>
</body>
</html>
