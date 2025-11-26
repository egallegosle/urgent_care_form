# Database Schema Documentation

## Overview

The urgent care form system uses a relational MySQL database with 8 main tables to store patient information, medical history, consents, and track DrChrono API synchronization.

## Entity Relationship Diagram

```
┌─────────────────┐
│    PATIENTS     │ ◄─────┐
│  (patient_id)   │       │
└────────┬────────┘       │
         │                │
         │ 1              │
         │                │
         │ N              │ 1
    ┌────▼────────────┐   │
    │ MEDICAL_HISTORY │   │
    │   (history_id)  │   │
    └─────────────────┘   │
                          │
    ┌─────────────────┐   │
    │ PATIENT_CONSENTS│◄──┤
    │  (consent_id)   │   │
    └─────────────────┘   │
                          │
    ┌─────────────────┐   │
    │   FINANCIAL_    │   │
    │   AGREEMENTS    │◄──┤
    │ (agreement_id)  │   │
    └─────────────────┘   │
                          │
    ┌─────────────────┐   │
    │   ADDITIONAL_   │   │
    │    CONSENTS     │◄──┤
    │  (consent_id)   │   │
    └─────────────────┘   │
                          │
    ┌─────────────────┐   │
    │     FORM_       │   │
    │   SUBMISSIONS   │◄──┤
    │ (submission_id) │   │
    └─────────────────┘   │
                          │
    ┌─────────────────┐   │
    │  DRCHRONO_SYNC_ │   │
    │      LOG        │◄──┘
    │    (log_id)     │
    └─────────────────┘

    ┌─────────────────┐
    │   AUDIT_LOG     │ (Independent)
    │   (audit_id)    │
    └─────────────────┘
```

## Table Descriptions

### 1. PATIENTS (Main Table)
**Purpose:** Stores all patient demographic and contact information

**Key Fields:**
- `patient_id` (PK) - Unique identifier
- Personal info: name, DOB, age, gender, SSN
- Contact: address, phone, email
- Emergency contact information
- Insurance details
- Primary care physician
- Visit information
- DrChrono integration fields

**Relationships:**
- One patient has many medical history records (1:N)
- One patient has many consents (1:N)
- One patient has one form submission (1:1)

---

### 2. MEDICAL_HISTORY
**Purpose:** Stores patient medical history and health information

**Key Fields:**
- `history_id` (PK)
- `patient_id` (FK) → patients
- Lifestyle: smoking, alcohol consumption
- Medical conditions (stored as text/CSV)
- Surgical history
- Current medications
- Allergies
- Family medical history

**Relationships:**
- Belongs to one patient (N:1)

---

### 3. PATIENT_CONSENTS
**Purpose:** Stores treatment consent forms and signatures

**Key Fields:**
- `consent_id` (PK)
- `patient_id` (FK) → patients
- Consent acknowledgments (3 checkboxes)
- Patient signature information
- Guardian information (if applicable)
- Date and time stamps

**Relationships:**
- Belongs to one patient (N:1)

---

### 4. FINANCIAL_AGREEMENTS
**Purpose:** Stores financial responsibility agreements

**Key Fields:**
- `agreement_id` (PK)
- `patient_id` (FK) → patients
- Payment method
- Financial acknowledgments (4 checkboxes)
- Signature information
- Relationship to patient

**Relationships:**
- Belongs to one patient (N:1)

---

### 5. ADDITIONAL_CONSENTS
**Purpose:** Stores HIPAA acknowledgment and communication preferences

**Key Fields:**
- `consent_id` (PK)
- `patient_id` (FK) → patients
- HIPAA acknowledgment
- Communication preferences (CSV)
- Contact methods (CSV)
- Voicemail authorization
- Portal access preferences
- Authorized caregiver information
- Final signature

**Relationships:**
- Belongs to one patient (N:1)

---

### 6. FORM_SUBMISSIONS
**Purpose:** Tracks form completion status and workflow

**Key Fields:**
- `submission_id` (PK)
- `patient_id` (FK) → patients
- Completion status for each form (5 booleans)
- Completion timestamps for each form
- Overall completion status
- Session information (session_id, IP, user agent)

**Relationships:**
- Belongs to one patient (N:1)

**Use Case:** Track which forms have been completed and when

---

### 7. DRCHRONO_SYNC_LOG
**Purpose:** Audit trail for DrChrono API synchronization

**Key Fields:**
- `log_id` (PK)
- `patient_id` (FK) → patients
- Sync type (create, update, retrieve)
- Sync status (success, failed, pending)
- API endpoint and HTTP status
- Request/response data
- Error messages
- DrChrono patient ID

**Relationships:**
- Belongs to one patient (N:1)

**Use Case:** Debug API issues and track sync history

---

### 8. AUDIT_LOG
**Purpose:** Track all data modifications for compliance

**Key Fields:**
- `audit_id` (PK)
- Table name and record ID
- Action (INSERT, UPDATE, DELETE)
- Old and new values (JSON)
- User information
- Timestamp

**Relationships:**
- Independent (no foreign keys)

**Use Case:** HIPAA compliance, security auditing

---

## Views

### vw_patient_complete
Complete patient information with all form data joined

**Use Case:** Display full patient profile in admin dashboard

### vw_patients_pending_sync
List of patients waiting for DrChrono synchronization

**Use Case:** Background job to sync pending patients

### vw_recent_submissions
Recent form submissions (last 7 days)

**Use Case:** Dashboard recent activity widget

---

## Data Flow

1. **Patient fills out forms** → Data saved to respective tables
2. **Form completion tracked** → form_submissions table updated
3. **All forms complete** → Trigger DrChrono sync
4. **DrChrono API called** → Log saved to drchrono_sync_log
5. **Patient record updated** → drchrono_patient_id saved in patients table

---

## Indexes

### Performance Indexes
- `patients.last_name` - Fast patient name searches
- `patients.email` - Email lookups
- `patients.cell_phone` - Phone number searches
- `patients.drchrono_patient_id` - DrChrono sync queries
- `form_submissions.all_forms_completed` - Find incomplete forms
- `drchrono_sync_log.sync_status` - Find failed syncs

### Foreign Key Indexes
All foreign keys automatically indexed for join performance

---

## Data Types

### ENUM Fields
Used for predefined options to ensure data integrity:
- Gender: Male, Female, Other
- Marital Status: Single, Married, Divorced, Widowed
- Yes/No fields: Consistent across tables
- Sync Status: pending, synced, failed, not_synced

### TEXT Fields
Used for variable-length content:
- Medical conditions (comma-separated)
- Medications, allergies (multi-line text)
- Signatures (base64 or typed signatures)
- API responses (JSON data)

### VARCHAR Lengths
- Names: 100-200 characters
- Email: 255 characters
- Phone: 20 characters (includes formatting)
- Addresses: 255 characters

---

## Character Set

**utf8mb4** - Full Unicode support including emojis and special characters

**Collation:** utf8mb4_unicode_ci - Case-insensitive sorting

---

## Security Considerations

### HIPAA Compliance
1. **Audit Trail** - All changes logged in audit_log
2. **Access Control** - Database user permissions restricted
3. **Encryption** - SSL/TLS for connections (configure in production)
4. **Data Retention** - Implement retention policies per HIPAA

### SQL Injection Prevention
- Use prepared statements for all queries
- Sanitize all user inputs
- Validate data types before insertion

### Sensitive Data
- SSN stored as VARCHAR (consider encryption at application level)
- Signatures stored as TEXT (consider secure storage)
- API credentials NEVER stored in database

---

## Backup Strategy

1. **Daily Backups** - Automated via GoDaddy cPanel
2. **Pre-Migration Backups** - Before any schema changes
3. **Off-site Backups** - Download copies regularly
4. **Retention** - Keep backups for 7+ years (HIPAA requirement)

---

## Migration Strategy

When updating schema in production:

1. **Test in development** - Always test changes locally first
2. **Backup production** - Full database backup before changes
3. **Run migration** - Apply changes during low-traffic hours
4. **Verify data** - Check data integrity after migration
5. **Rollback plan** - Keep backup ready for quick restore

---

## Storage Estimates

**Per Patient Record:**
- Patient table: ~1-2 KB
- Medical history: ~2-3 KB
- Consents: ~1-2 KB each (3 tables)
- Total per patient: ~8-12 KB

**For 10,000 patients:** ~100-120 MB
**For 100,000 patients:** ~1-1.2 GB

**Note:** Audit logs and sync logs will grow over time. Plan for regular archival.

---

## Future Enhancements

### Potential Additions
1. **Users Table** - For admin panel authentication
2. **Appointments Table** - Track scheduled visits
3. **Documents Table** - Store uploaded files
4. **Notifications Table** - Track sent communications
5. **Settings Table** - System configuration

### Performance Optimizations
1. **Partitioning** - For large audit_log and sync_log tables
2. **Archival** - Move old records to archive tables
3. **Caching** - Implement Redis/Memcached layer
4. **Read Replicas** - For high-traffic scenarios
