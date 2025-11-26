<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - PrimeHealth Urgent Care</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1>Medical History</h1>
            <p>Please provide accurate information about your medical history</p>
        </div>

        <form id="medicalHistoryForm" method="POST" action="../process/save_medical_history.php">

            <!-- Lifestyle Section -->
            <div class="form-section">
                <h2>Lifestyle Information</h2>

                <div class="form-group">
                    <label>Do you smoke? <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="smokeYes" name="smoke" value="Yes" required>
                            <label for="smokeYes">Yes</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="smokeNo" name="smoke" value="No" required>
                            <label for="smokeNo">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="smokingFrequency">If yes, how many per day?</label>
                    <input type="text" id="smokingFrequency" name="smokingFrequency">
                </div>

                <div class="form-group">
                    <label>Do you drink alcohol? <span class="required">*</span></label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="alcoholYes" name="alcohol" value="Yes" required>
                            <label for="alcoholYes">Yes</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="alcoholNo" name="alcohol" value="No" required>
                            <label for="alcoholNo">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="alcoholFrequency">If yes, how often?</label>
                    <select id="alcoholFrequency" name="alcoholFrequency">
                        <option value="">Select...</option>
                        <option value="Rarely">Rarely</option>
                        <option value="Socially">Socially</option>
                        <option value="Weekly">Weekly</option>
                        <option value="Daily">Daily</option>
                    </select>
                </div>
            </div>

            <!-- Medical Conditions Section -->
            <div class="form-section">
                <h2>Please check all that apply to your medical history:</h2>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="diabetes" name="conditions[]" value="Diabetes">
                        <label for="diabetes">Diabetes</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="highBloodPressure" name="conditions[]" value="High Blood Pressure">
                        <label for="highBloodPressure">High Blood Pressure</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="heartDisease" name="conditions[]" value="Heart Disease">
                        <label for="heartDisease">Heart Disease</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="asthma" name="conditions[]" value="Asthma">
                        <label for="asthma">Asthma</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="copd" name="conditions[]" value="COPD">
                        <label for="copd">COPD (Chronic Obstructive Pulmonary Disease)</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="cancer" name="conditions[]" value="Cancer">
                        <label for="cancer">Cancer</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="stroke" name="conditions[]" value="Stroke">
                        <label for="stroke">Stroke</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="seizures" name="conditions[]" value="Seizures">
                        <label for="seizures">Seizures/Epilepsy</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="kidneyDisease" name="conditions[]" value="Kidney Disease">
                        <label for="kidneyDisease">Kidney Disease</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="liverDisease" name="conditions[]" value="Liver Disease">
                        <label for="liverDisease">Liver Disease</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="thyroid" name="conditions[]" value="Thyroid Disease">
                        <label for="thyroid">Thyroid Disease</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="depression" name="conditions[]" value="Depression/Anxiety">
                        <label for="depression">Depression/Anxiety</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="arthritis" name="conditions[]" value="Arthritis">
                        <label for="arthritis">Arthritis</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="bloodClots" name="conditions[]" value="Blood Clots">
                        <label for="bloodClots">Blood Clots</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="anemia" name="conditions[]" value="Anemia">
                        <label for="anemia">Anemia</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="hivAids" name="conditions[]" value="HIV/AIDS">
                        <label for="hivAids">HIV/AIDS</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="hepatitis" name="conditions[]" value="Hepatitis">
                        <label for="hepatitis">Hepatitis</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="tuberculosis" name="conditions[]" value="Tuberculosis">
                        <label for="tuberculosis">Tuberculosis</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="osteoporosis" name="conditions[]" value="Osteoporosis">
                        <label for="osteoporosis">Osteoporosis</label>
                    </div>

                    <div class="checkbox-item">
                        <input type="checkbox" id="noneConditions" name="conditions[]" value="None">
                        <label for="noneConditions">None of the above</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="otherConditions">Other Medical Conditions (please specify):</label>
                    <textarea id="otherConditions" name="otherConditions" rows="3"></textarea>
                </div>
            </div>

            <!-- Surgical History Section -->
            <div class="form-section">
                <h2>Surgical History</h2>

                <div class="form-group">
                    <label>Have you had any previous surgeries?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="surgeriesYes" name="previousSurgeries" value="Yes">
                            <label for="surgeriesYes">Yes</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="surgeriesNo" name="previousSurgeries" value="No">
                            <label for="surgeriesNo">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="surgeryDetails">If yes, please list surgeries and approximate dates:</label>
                    <textarea id="surgeryDetails" name="surgeryDetails" rows="4" placeholder="Example: Appendectomy - 2015, Knee Surgery - 2018"></textarea>
                </div>
            </div>

            <!-- Medications Section -->
            <div class="form-section">
                <h2>Current Medications</h2>

                <div class="alert alert-info">
                    Please list ALL medications you are currently taking, including over-the-counter medications, vitamins, and supplements.
                </div>

                <div class="form-group">
                    <label for="currentMedications">Medication Name, Dosage, and Frequency</label>
                    <textarea id="currentMedications" name="currentMedications" rows="5" placeholder="Example:&#10;Lisinopril 10mg - once daily&#10;Aspirin 81mg - once daily&#10;Vitamin D 1000IU - once daily"></textarea>
                </div>
            </div>

            <!-- Allergies Section -->
            <div class="form-section">
                <h2>Allergies</h2>

                <div class="alert alert-info">
                    List ALL known allergies including medications, foods, latex, etc.
                </div>

                <div class="form-group">
                    <label>Do you have any known allergies?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" id="allergiesYes" name="hasAllergies" value="Yes">
                            <label for="allergiesYes">Yes</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" id="allergiesNo" name="hasAllergies" value="No">
                            <label for="allergiesNo">No</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="allergyDetails">If yes, please list all allergies and reactions:</label>
                    <textarea id="allergyDetails" name="allergyDetails" rows="4" placeholder="Example:&#10;Penicillin - rash&#10;Peanuts - anaphylaxis&#10;Latex - hives"></textarea>
                </div>
            </div>

            <!-- Family History Section -->
            <div class="form-section">
                <h2>Family Medical History</h2>

                <div class="form-group">
                    <label for="familyHistory">Please list any significant medical conditions in your immediate family (parents, siblings):</label>
                    <textarea id="familyHistory" name="familyHistory" rows="4" placeholder="Example: Father - Heart Disease, Mother - Diabetes"></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='1_patient_registration.php'">Previous</button>
                <button type="submit" class="btn btn-primary btn-block">Continue to Consent Forms</button>
            </div>

        </form>
    </div>

    <script>
        // Uncheck "None" if other conditions are selected
        const noneCheckbox = document.getElementById('noneConditions');
        const conditionCheckboxes = document.querySelectorAll('input[name="conditions[]"]:not(#noneConditions)');

        noneCheckbox.addEventListener('change', function() {
            if (this.checked) {
                conditionCheckboxes.forEach(cb => cb.checked = false);
            }
        });

        conditionCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    noneCheckbox.checked = false;
                }
            });
        });
    </script>
</body>
</html>
