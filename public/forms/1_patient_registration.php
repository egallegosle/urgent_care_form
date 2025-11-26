<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Patient Registration</h1>
            <p>Please fill out this form completely and accurately</p>
        </div>

        <form id="patientRegistrationForm" method="POST" action="../process/save_patient_registration.php">

            <!-- Patient Information Section -->
            <div class="form-section">
                <h2>Patient Information</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>

                    <div class="form-group">
                        <label for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="middleName">Middle Name</label>
                        <input type="text" id="middleName" name="middleName">
                    </div>

                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                    </div>
                </div>

                <div class="form-row three-col">
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" min="0" max="150">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ssn">Social Security Number</label>
                        <input type="text" id="ssn" name="ssn" placeholder="XXX-XX-XXXX">
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="form-section">
                <h2>Contact Information</h2>

                <div class="form-group">
                    <label for="address">Street Address <span class="required">*</span></label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City <span class="required">*</span></label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State <span class="required">*</span></label>
                        <input type="text" id="state" name="state" required>
                    </div>

                    <div class="form-group">
                        <label for="zipCode">ZIP Code <span class="required">*</span></label>
                        <input type="text" id="zipCode" name="zipCode" pattern="[0-9]{5}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="homePhone">Home Phone</label>
                        <input type="tel" id="homePhone" name="homePhone" placeholder="(XXX) XXX-XXXX">
                    </div>

                    <div class="form-group">
                        <label for="cellPhone">Cell Phone <span class="required">*</span></label>
                        <input type="tel" id="cellPhone" name="cellPhone" placeholder="(XXX) XXX-XXXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="maritalStatus">Marital Status</label>
                    <select id="maritalStatus" name="maritalStatus">
                        <option value="">Select...</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
            </div>

            <!-- Emergency Contact Section -->
            <div class="form-section">
                <h2>Emergency Contact</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emergencyContactName">Emergency Contact Name <span class="required">*</span></label>
                        <input type="text" id="emergencyContactName" name="emergencyContactName" required>
                    </div>

                    <div class="form-group">
                        <label for="emergencyContactPhone">Emergency Contact Phone <span class="required">*</span></label>
                        <input type="tel" id="emergencyContactPhone" name="emergencyContactPhone" placeholder="(XXX) XXX-XXXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="emergencyRelationship">Relationship to Patient <span class="required">*</span></label>
                    <input type="text" id="emergencyRelationship" name="emergencyRelationship" required>
                </div>
            </div>

            <!-- Insurance Information Section -->
            <div class="form-section">
                <h2>Insurance Information</h2>

                <div class="form-group">
                    <label for="insuranceProvider">Insurance Provider</label>
                    <input type="text" id="insuranceProvider" name="insuranceProvider">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="policyNumber">Policy Number</label>
                        <input type="text" id="policyNumber" name="policyNumber">
                    </div>

                    <div class="form-group">
                        <label for="groupNumber">Group Number</label>
                        <input type="text" id="groupNumber" name="groupNumber">
                    </div>
                </div>

                <div class="form-group">
                    <label for="policyHolderName">Policy Holder Name (if different from patient)</label>
                    <input type="text" id="policyHolderName" name="policyHolderName">
                </div>

                <div class="form-group">
                    <label for="policyHolderDOB">Policy Holder Date of Birth</label>
                    <input type="date" id="policyHolderDOB" name="policyHolderDOB">
                </div>
            </div>

            <!-- Primary Care Physician Section -->
            <div class="form-section">
                <h2>Primary Care Physician</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pcpName">Physician Name</label>
                        <input type="text" id="pcpName" name="pcpName">
                    </div>

                    <div class="form-group">
                        <label for="pcpPhone">Physician Phone</label>
                        <input type="tel" id="pcpPhone" name="pcpPhone" placeholder="(XXX) XXX-XXXX">
                    </div>
                </div>
            </div>

            <!-- Visit Information Section -->
            <div class="form-section">
                <h2>Visit Information</h2>

                <div class="form-group">
                    <label for="reasonForVisit">Reason for Visit <span class="required">*</span></label>
                    <textarea id="reasonForVisit" name="reasonForVisit" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="allergies">Known Allergies</label>
                    <textarea id="allergies" name="allergies" rows="3" placeholder="List all known allergies, or write 'None'"></textarea>
                </div>

                <div class="form-group">
                    <label for="currentMedications">Current Medications</label>
                    <textarea id="currentMedications" name="currentMedications" rows="3" placeholder="List all current medications, or write 'None'"></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.history.back()">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">Continue to Medical History</button>
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
    </script>
</body>
</html>
