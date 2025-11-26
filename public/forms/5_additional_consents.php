<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Additional Consents - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Additional Patient Consents</h1>
            <p>Privacy, Communication, and Additional Authorizations</p>
        </div>

        <form id="additionalConsentsForm" method="POST" action="../process/save_additional_consents.php">

            <!-- HIPAA Privacy Notice Section -->
            <div class="form-section">
                <h2>HIPAA Privacy Notice Acknowledgment</h2>

                <div style="line-height: 1.8; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">
                        I acknowledge that I have received and reviewed the Notice of Privacy Practices of PrimeHealth Urgent Care. This notice describes how my medical information may be used and disclosed, and how I can access my medical information.
                    </p>

                    <p style="margin-bottom: 15px;">
                        I understand that I have the right to:
                    </p>

                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>Request restrictions on certain uses and disclosures of my health information</li>
                        <li>Receive confidential communications of my health information</li>
                        <li>Inspect and copy my health information</li>
                        <li>Amend my health information</li>
                        <li>Receive an accounting of disclosures of my health information</li>
                        <li>Revoke my authorization in writing</li>
                    </ul>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" id="hipaaAcknowledged" name="hipaaAcknowledged" value="Yes" required>
                    <label for="hipaaAcknowledged">
                        <strong>I acknowledge receipt of the HIPAA Privacy Notice <span class="required">*</span></strong>
                    </label>
                </div>
            </div>

            <!-- Communication Preferences Section -->
            <div class="form-section">
                <h2>Communication Authorization</h2>

                <div style="line-height: 1.8; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">
                        I authorize PrimeHealth Urgent Care to contact me regarding:
                    </p>
                </div>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="commAppointments" name="communicationPrefs[]" value="Appointment Reminders">
                        <label for="commAppointments">Appointment reminders and confirmations</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="commTestResults" name="communicationPrefs[]" value="Test Results">
                        <label for="commTestResults">Test results and follow-up care</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="commBilling" name="communicationPrefs[]" value="Billing">
                        <label for="commBilling">Billing and payment information</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="commHealth" name="communicationPrefs[]" value="Health Information">
                        <label for="commHealth">Health and wellness information</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="commSurveys" name="communicationPrefs[]" value="Satisfaction Surveys">
                        <label for="commSurveys">Patient satisfaction surveys</label>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Preferred Method of Contact <span class="required">*</span></label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="contactPhone" name="contactMethods[]" value="Phone">
                            <label for="contactPhone">Phone Call</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="contactText" name="contactMethods[]" value="Text Message">
                            <label for="contactText">Text Message (SMS)</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="contactEmail" name="contactMethods[]" value="Email">
                            <label for="contactEmail">Email</label>
                        </div>

                        <div class="checkbox-item">
                            <input type="checkbox" id="contactMail" name="contactMethods[]" value="Mail">
                            <label for="contactMail">Postal Mail</label>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info" style="margin-top: 15px;">
                    <strong>Important:</strong> Text messages and emails are not secure forms of communication. By selecting these methods, you acknowledge that your health information may be transmitted via non-secure methods.
                </div>
            </div>

            <!-- Authorization to Leave Message Section -->
            <div class="form-section">
                <h2>Voicemail and Message Authorization</h2>

                <div class="form-group">
                    <label>May we leave detailed voicemail messages regarding your care?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="voicemailYes" name="voicemailAuthorization" value="Yes">
                            <label for="voicemailYes">Yes, you may leave detailed messages</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="voicemailGeneral" name="voicemailAuthorization" value="General Only">
                            <label for="voicemailGeneral">Only leave general messages (call back request)</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="voicemailNo" name="voicemailAuthorization" value="No">
                            <label for="voicemailNo">No, do not leave any messages</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Portal Access Section -->
            <div class="form-section">
                <h2>Patient Portal Access</h2>

                <div style="line-height: 1.8; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">
                        PrimeHealth Urgent Care offers a secure online patient portal where you can:
                    </p>

                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>View your medical records and test results</li>
                        <li>Request prescription refills</li>
                        <li>Communicate securely with your healthcare team</li>
                        <li>Update your contact information</li>
                        <li>View and pay bills</li>
                    </ul>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" id="portalAccess" name="portalAccess" value="Yes">
                    <label for="portalAccess">
                        <strong>I would like to register for patient portal access</strong>
                    </label>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label for="portalEmail">Email for Portal Registration</label>
                    <input type="email" id="portalEmail" name="portalEmail" placeholder="Enter email address for portal access">
                </div>
            </div>

            <!-- Family Member Access Section -->
            <div class="form-section">
                <h2>Family Member/Caregiver Access</h2>

                <div class="alert alert-info">
                    Complete this section if you would like to authorize a family member or caregiver to access your medical information.
                </div>

                <div class="form-group">
                    <label for="authorizedPersonName">Authorized Person's Name</label>
                    <input type="text" id="authorizedPersonName" name="authorizedPersonName" placeholder="Leave blank if not applicable">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="authorizedPersonRelation">Relationship to Patient</label>
                        <input type="text" id="authorizedPersonRelation" name="authorizedPersonRelation">
                    </div>

                    <div class="form-group">
                        <label for="authorizedPersonPhone">Phone Number</label>
                        <input type="tel" id="authorizedPersonPhone" name="authorizedPersonPhone" placeholder="(XXX) XXX-XXXX">
                    </div>
                </div>

                <div class="checkbox-item">
                    <input type="checkbox" id="authorizeDiscussion" name="authorizeDiscussion" value="Yes">
                    <label for="authorizeDiscussion">
                        I authorize PrimeHealth Urgent Care to discuss my medical information with the person named above
                    </label>
                </div>
            </div>

            <!-- Final Acknowledgment Section -->
            <div class="form-section">
                <h2>Final Acknowledgment</h2>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="allFormsComplete" name="allFormsComplete" value="Yes" required>
                        <label for="allFormsComplete">
                            <strong>I certify that all information provided in these forms is accurate and complete <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="consentToAll" name="consentToAll" value="Yes" required>
                        <label for="consentToAll">
                            <strong>I have read, understood, and agree to all consents and agreements <span class="required">*</span></strong>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="form-section">
                <h2>Signature</h2>

                <div class="form-group">
                    <label for="finalSignatureName">Patient Name (Printed) <span class="required">*</span></label>
                    <input type="text" id="finalSignatureName" name="finalSignatureName" required>
                </div>

                <div class="form-group">
                    <label for="finalSignature">Patient Signature <span class="required">*</span></label>
                    <div class="signature-pad" id="finalSignaturePad">
                        <p style="padding: 20px; text-align: center; color: #999;">
                            Signature functionality will be implemented<br>
                            For now, please type your full name below to sign
                        </p>
                    </div>
                    <input type="text" id="finalSignature" name="finalSignature" placeholder="Type your full name to sign" required>
                </div>

                <div class="form-group">
                    <label for="finalSignatureDate">Date <span class="required">*</span></label>
                    <input type="date" id="finalSignatureDate" name="finalSignatureDate" required>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='4_financial_agreement.php'">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">Submit All Forms</button>
            </div>

        </form>
    </div>

    <script>
        // Auto-populate current date
        window.addEventListener('load', function() {
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('finalSignatureDate').value = dateStr;
        });

        // Ensure at least one contact method is selected
        const contactCheckboxes = document.querySelectorAll('input[name="contactMethods[]"]');
        const form = document.getElementById('additionalConsentsForm');

        form.addEventListener('submit', function(e) {
            let atLeastOneChecked = false;
            contactCheckboxes.forEach(cb => {
                if (cb.checked) atLeastOneChecked = true;
            });

            if (!atLeastOneChecked) {
                e.preventDefault();
                alert('Please select at least one preferred method of contact.');
            }
        });
    </script>
</body>
</html>
