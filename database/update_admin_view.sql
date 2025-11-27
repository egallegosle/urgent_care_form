-- =====================================================
-- Update Admin Patient View to Include All Fields
-- =====================================================

-- Replace the complete patient view with all necessary fields
CREATE OR REPLACE VIEW vw_admin_patients AS
SELECT
    -- Patient Basic Info
    p.patient_id,
    p.first_name,
    p.last_name,
    CONCAT(p.first_name, ' ', p.last_name) as full_name,
    p.date_of_birth,
    p.age,
    p.gender,
    p.ssn,
    p.marital_status,

    -- Contact Information
    p.email,
    p.cell_phone,
    p.home_phone,
    p.address,
    p.city,
    p.state,
    p.zip_code,

    -- Emergency Contact
    p.emergency_contact_name,
    p.emergency_contact_phone,
    p.emergency_relationship,

    -- Insurance Information
    p.insurance_provider,
    p.policy_number,
    p.group_number,
    p.policy_holder_name,
    p.policy_holder_dob,

    -- Primary Care Physician
    p.pcp_name,
    p.pcp_phone,

    -- Visit Information
    p.reason_for_visit,
    p.current_medications,
    p.allergies,

    -- DrChrono Integration
    p.drchrono_patient_id,
    p.drchrono_sync_status,
    p.drchrono_sync_date,

    -- Timestamps
    p.created_at as registration_date,
    p.updated_at,

    -- Form Status
    fs.all_forms_completed,
    fs.completed_at as forms_completed_date,
    fs.registration_completed,
    fs.medical_history_completed,
    fs.consent_completed,
    fs.financial_completed,
    fs.additional_consents_completed,

    -- Patient Status
    ps.current_status,
    ps.priority,
    ps.checked_in_at,
    ps.in_progress_at,
    ps.completed_at as visit_completed_at,
    ps.admin_notes,

    -- Financial
    fa.payment_method

FROM patients p
LEFT JOIN form_submissions fs ON p.patient_id = fs.patient_id
LEFT JOIN patient_status ps ON p.patient_id = ps.patient_id
LEFT JOIN financial_agreements fa ON p.patient_id = fa.patient_id;

SELECT 'vw_admin_patients view updated successfully!' as status;
