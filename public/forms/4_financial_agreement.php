<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Financial Agreement - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Patient Financial Agreement</h1>
            <p>Financial Responsibility and Payment Terms</p>
        </div>

        <form id="financialAgreementForm" method="POST" action="../process/save_financial_agreement.php">

            <!-- Financial Responsibility Section -->
            <div class="form-section">
                <h2>Financial Responsibility</h2>

                <div class="alert alert-info">
                    Please read the following carefully before signing.
                </div>

                <div style="line-height: 1.8; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">
                        <strong>1. Payment Responsibility:</strong> I understand that I am financially responsible for all charges for services provided to me or my dependent(s) by PrimeHealth Urgent Care, regardless of my insurance coverage or benefits. I agree to pay all charges in full.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>2. Insurance Assignment:</strong> I authorize PrimeHealth Urgent Care to bill my insurance company for services rendered. I assign all insurance benefits to be paid directly to PrimeHealth Urgent Care. I understand that I am responsible for any deductibles, co-payments, co-insurance, or non-covered services as determined by my insurance company.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>3. Insurance Information:</strong> I certify that the insurance information I have provided is correct and current. I understand that it is my responsibility to notify PrimeHealth Urgent Care of any changes to my insurance coverage.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>4. Payment Terms:</strong> Payment for services is expected at the time of service. I understand that:
                    </p>

                    <ul style="margin-left: 20px; margin-bottom: 15px;">
                        <li>Co-payments are due at check-in</li>
                        <li>Payment for non-covered services is due at time of service</li>
                        <li>If I do not have insurance, payment in full is expected at time of service</li>
                        <li>We accept cash, credit cards, and debit cards</li>
                    </ul>

                    <p style="margin-bottom: 15px;">
                        <strong>5. Insurance Claim Denials:</strong> If my insurance company does not pay for services within 60 days, or if my claim is denied or partially paid, I agree to pay the balance in full within 30 days of being notified by PrimeHealth Urgent Care.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>6. Self-Pay Patients:</strong> If I do not have insurance coverage, I understand that I am responsible for payment in full at the time of service. I may request information about payment plans or financial assistance programs.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>7. Missed Appointments:</strong> I understand that PrimeHealth Urgent Care may charge a fee for missed appointments without 24-hour advance notice, and that this charge is my responsibility and will not be billed to my insurance.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>8. Collection Costs:</strong> I agree that if my account is turned over to a collection agency or attorney for collection, I will be responsible for all collection costs, including but not limited to attorney fees, court costs, and collection agency fees.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>9. Medical Records Release:</strong> I authorize PrimeHealth Urgent Care to release any medical information necessary to process my insurance claims and to receive payment for services rendered.
                    </p>

                    <p style="margin-bottom: 15px;">
                        <strong>10. Credit Card on File:</strong> I authorize PrimeHealth Urgent Care to keep my credit card information on file and to charge my card for any outstanding balances, co-payments, deductibles, or non-covered services.
                    </p>
                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="form-section">
                <h2>Payment Method</h2>

                <div class="form-group">
                    <label>Preferred Payment Method <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="paymentCash" name="paymentMethod" value="Cash" required>
                            <label for="paymentCash">Cash</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="paymentCredit" name="paymentMethod" value="Credit Card" required>
                            <label for="paymentCredit">Credit Card</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="paymentDebit" name="paymentMethod" value="Debit Card" required>
                            <label for="paymentDebit">Debit Card</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="paymentInsurance" name="paymentMethod" value="Insurance" required>
                            <label for="paymentInsurance">Insurance (co-pay/deductible will be collected)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acknowledgment Section -->
            <div class="form-section">
                <h2>Patient Acknowledgment</h2>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="financeReadUnderstood" name="financeReadUnderstood" value="Yes" required>
                        <label for="financeReadUnderstood">
                            <strong>I have read and understand this financial agreement <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="agreeToTerms" name="agreeToTerms" value="Yes" required>
                        <label for="agreeToTerms">
                            <strong>I agree to the payment terms outlined above <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="authorizeInsurance" name="authorizeInsurance" value="Yes" required>
                        <label for="authorizeInsurance">
                            <strong>I authorize insurance benefits to be paid directly to PrimeHealth Urgent Care <span class="required">*</span></strong>
                        </label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="responsibleForBalance" name="responsibleForBalance" value="Yes" required>
                        <label for="responsibleForBalance">
                            <strong>I understand that I am responsible for any balance not covered by insurance <span class="required">*</span></strong>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div class="form-section">
                <h2>Signature</h2>

                <div class="form-group">
                    <label for="financialSignatureName">Patient/Responsible Party Name (Printed) <span class="required">*</span></label>
                    <input type="text" id="financialSignatureName" name="financialSignatureName" required>
                </div>

                <div class="form-group">
                    <label for="financialSignature">Signature <span class="required">*</span></label>
                    <div class="signature-pad" id="financialSignaturePad">
                        <p style="padding: 20px; text-align: center; color: #999;">
                            Signature functionality will be implemented<br>
                            For now, please type your full name below to sign
                        </p>
                    </div>
                    <input type="text" id="financialSignature" name="financialSignature" placeholder="Type your full name to sign" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="financialSignatureDate">Date <span class="required">*</span></label>
                        <input type="date" id="financialSignatureDate" name="financialSignatureDate" required>
                    </div>

                    <div class="form-group">
                        <label for="relationshipToPatient">Relationship to Patient <span class="required">*</span></label>
                        <select id="relationshipToPatient" name="relationshipToPatient" required>
                            <option value="">Select...</option>
                            <option value="Self">Self</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Parent">Parent</option>
                            <option value="Legal Guardian">Legal Guardian</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='3_patient_consent.php'">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">Continue to Additional Consents</button>
            </div>

        </form>
    </div>

    <script>
        // Auto-populate current date
        window.addEventListener('load', function() {
            const today = new Date();
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('financialSignatureDate').value = dateStr;
        });
    </script>
</body>
</html>
