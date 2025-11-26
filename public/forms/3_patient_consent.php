<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Consent for Treatment - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Patient Consent for Treatment</h1>
            <p>Please read carefully and sign below</p>
        </div>

        <form id="patientConsentForm" method="POST" action="../process/save_patient_consent.php">

            <!-- Consent Text Section -->
            <div class="form-section">
                <h2>Consent for Medical Treatment</h2>

                <div class="alert alert-info">
                    Please read the following carefully before signing.
                </div>

                <div style="line-height: 1.8; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">
                        I voluntarily request and consent to medical treatment and procedures performed by the healthcare providers at PrimeHealth Urgent Care. I understand that this consent includes, but is not limited to:
                    </p>

                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>Examination and evaluation by healthcare providers</li>
                        <li>Laboratory tests, x-rays, and other diagnostic procedures</li>
                        <li>Medical treatment and procedures deemed necessary</li>
                        <li>Administration of medications and injections</li>
                        <li>Minor surgical procedures</li>
                    </ul>

                    <p style="margin-bottom: 15px;">
                        <strong>Acknowledgment of Risks:</strong> I understand that all medical treatment involves risks and that no guarantees have been made to me regarding the results of treatments or procedures. I have been given the opportunity to ask questions, and my questions have been answered to my satisfaction.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>Alternative Treatments:</strong> I understand that I have the right to refuse any treatment, test, or procedure, and that I may withdraw this consent at any time. I understand that alternative treatment options and their risks have been explained to me when applicable.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>Medical Students and Observers:</strong> I understand that PrimeHealth Urgent Care is committed to medical education, and I consent to the presence of medical students, residents, or other healthcare professionals observing or participating in my care under appropriate supervision.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>Photography and Recording:</strong> I understand that photographs, videos, or digital images may be taken for medical documentation purposes and will be kept confidential as part of my medical record.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>Release of Information:</strong> I authorize PrimeHealth Urgent Care to release my medical information to:
                    </p>

                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>My primary care physician and other healthcare providers involved in my care</li>
                        <li>My insurance company for billing and payment purposes</li>
                        <li>Other parties as required by law</li>
                    </ul>

                    <p style="margin-bottom: 15px;">
                        <strong>Emergency Treatment:</strong> In the event of a medical emergency where I am unable to give consent, I authorize the healthcare providers at PrimeHealth Urgent Care to provide necessary emergency medical treatment.
                    </p>
                </div>
            </div>

            <!-- Acknowledgment Section -->
            <div class="form-section">
                <h2>Patient Acknowledgment</h2>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="readAndUnderstood" name="readAndUnderstood" value="Yes" required>
                        <label for="readAndUnderstood">
                            <strong>I have read and understand this consent form <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="questionsAnswered" name="questionsAnswered" value="Yes" required>
                        <label for="questionsAnswered">
                            <strong>I have had the opportunity to ask questions and my questions have been answered <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="voluntaryConsent" name="voluntaryConsent" value="Yes" required>
                        <label for="voluntaryConsent">
                            <strong>I am giving this consent voluntarily <span class="required">*</span></strong>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="form-section">
                <h2>Signature</h2>

                <div class="form-group">
                    <label for="patientSignatureName">Patient Name (Printed) <span class="required">*</span></label>
                    <input type="text" id="patientSignatureName" name="patientSignatureName" required>
                </div>

                <div class="form-group">
                    <label for="patientSignature">Patient Signature <span class="required">*</span></label>
                    <div class="signature-pad" id="patientSignaturePad">
                        <p style="padding: 20px; text-align: center; color: #999;">
                            Signature functionality will be implemented<br>
                            For now, please type your full name below to sign
                        </p>
                    </div>
                    <input type="text" id="patientSignature" name="patientSignature" placeholder="Type your full name to sign" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="signatureDate">Date <span class="required">*</span></label>
                        <input type="date" id="signatureDate" name="signatureDate" required>
                    </div>

                    <div class="form-group">
                        <label for="signatureTime">Time <span class="required">*</span></label>
                        <input type="time" id="signatureTime" name="signatureTime" required>
                    </div>
                </div>
            </div>

            <!-- Legal Guardian Section (if applicable) -->
            <div class="form-section">
                <h2>Legal Guardian/Representative (if applicable)</h2>

                <div class="alert alert-info">
                    Complete this section only if the patient is a minor or unable to consent for themselves.
                </div>

                <div class="form-group">
                    <label for="guardianName">Guardian/Representative Name (Printed)</label>
                    <input type="text" id="guardianName" name="guardianName">
                </div>

                <div class="form-group">
                    <label for="guardianRelationship">Relationship to Patient</label>
                    <select id="guardianRelationship" name="guardianRelationship">
                        <option value="">Select...</option>
                        <option value="Parent">Parent</option>
                        <option value="Legal Guardian">Legal Guardian</option>
                        <option value="Power of Attorney">Power of Attorney</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="guardianSignature">Guardian Signature</label>
                    <input type="text" id="guardianSignature" name="guardianSignature" placeholder="Type full name to sign">
                </div>

                <div class="form-group">
                    <label for="guardianDate">Date</label>
                    <input type="date" id="guardianDate" name="guardianDate">
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='2_medical_history.php'">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">Continue to Financial Agreement</button>
            </div>

        </form>
    </div>

    <script>
        // Auto-populate current date and time
        window.addEventListener('load', function() {
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            const timeStr = today.toTimeString().slice(0, 5);

            document.getElementById('signatureDate').value = dateStr;
            document.getElementById('signatureTime').value = timeStr;
        });
    </script>
</body>
</html>
