# Database Setup Verification Checklist

## Prerequisites

Before running the verification, ensure you have:
- [ ] Access to GoDaddy hosting account
- [ ] phpMyAdmin access or SSH access to the server
- [ ] Database credentials (confirmed in config/database.php)

## Step 1: Access Database

### Option A: Using phpMyAdmin (Recommended)
1. Log into your GoDaddy hosting control panel
2. Navigate to "Databases" → "MySQL Databases"
3. Click "phpMyAdmin" next to the `uc_forms` database
4. Login with your credentials (egallegosle / jiujitsu4)

### Option B: Using MySQL Command Line (if available)
```bash
mysql -h 68.178.244.46 -u egallegosle -p uc_forms
# Enter password: jiujitsu4
```

## Step 2: Verify Database Exists

In phpMyAdmin or MySQL CLI, run:
```sql
SHOW DATABASES LIKE 'uc_forms';
```

**Expected Result:** Should return one row showing `uc_forms`

## Step 3: Check Existing Tables

```sql
USE uc_forms;
SHOW TABLES;
```

**Expected Result:** Should show these 8 tables:
- additional_consents
- audit_log
- drchrono_sync_log
- financial_agreements
- form_submissions
- medical_history
- patient_consents
- patients

## Step 4: Create Tables (if missing)

If tables don't exist or are incomplete:

1. **In phpMyAdmin:**
   - Click "Import" tab
   - Choose file: `/home/egallegosle/projects/urgent_care_form/database/schema.sql`
   - Click "Go" to execute

2. **In MySQL CLI:**
   ```bash
   mysql -h 68.178.244.46 -u egallegosle -p uc_forms < /path/to/schema.sql
   ```

## Step 5: Verify Table Structure

Run these commands to verify each table:

### Verify patients table
```sql
DESCRIBE patients;
```
Should have columns: patient_id, first_name, last_name, date_of_birth, etc. (28 columns total)

### Verify medical_history table
```sql
DESCRIBE medical_history;
```
Should have foreign key to patients table

### Verify patient_consents table
```sql
DESCRIBE patient_consents;
```

### Verify financial_agreements table
```sql
DESCRIBE financial_agreements;
```

### Verify additional_consents table
```sql
DESCRIBE additional_consents;
```

### Verify form_submissions table
```sql
DESCRIBE form_submissions;
```

### Verify drchrono_sync_log table
```sql
DESCRIBE drchrono_sync_log;
```

### Verify audit_log table
```sql
DESCRIBE audit_log;
```

## Step 6: Verify Views

```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
```

**Expected Result:** Should show these 3 views:
- vw_patient_complete
- vw_patients_pending_sync
- vw_recent_submissions

If views are missing, they'll be created by schema.sql

## Step 7: Test Database Connection from Application

### Option A: Using Web Browser
1. Upload the project to GoDaddy web hosting
2. Navigate to: `https://yourdomain.com/test_connection.php`
3. Verify connection shows green checkmarks

### Option B: Using CLI Test Script (if PHP available)
```bash
php /home/egallegosle/projects/urgent_care_form/test_db_cli.php
```

## Step 8: Check Permissions

Verify user permissions:
```sql
SHOW GRANTS FOR 'egallegosle'@'%';
```

**Required permissions:**
- SELECT, INSERT, UPDATE on uc_forms database
- DELETE (optional, for development)
- CREATE, ALTER (if you need to modify schema)

## Step 9: Test Data Insertion

Insert a test record:
```sql
INSERT INTO patients (
    first_name, last_name, date_of_birth, age, gender,
    address, city, state, zip_code, cell_phone, email,
    emergency_contact_name, emergency_contact_phone, emergency_relationship,
    reason_for_visit
) VALUES (
    'Test', 'Patient', '1990-01-01', 34, 'Male',
    '123 Test St', 'Los Angeles', 'CA', '90001', '555-1234', 'test@example.com',
    'Test Contact', '555-5678', 'Spouse',
    'Database connection test'
);
```

Verify insertion:
```sql
SELECT * FROM patients ORDER BY patient_id DESC LIMIT 1;
```

Delete test record:
```sql
DELETE FROM patients WHERE first_name = 'Test' AND last_name = 'Patient';
```

## Step 10: Verify Foreign Key Constraints

```sql
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = 'uc_forms'
    AND REFERENCED_TABLE_NAME IS NOT NULL;
```

**Expected Result:** Should show foreign keys from:
- medical_history → patients
- patient_consents → patients
- financial_agreements → patients
- additional_consents → patients
- form_submissions → patients
- drchrono_sync_log → patients

## Troubleshooting

### Connection Failed
**Issue:** Cannot connect to database
**Solutions:**
1. Verify IP address (68.178.244.46) is correct
2. Check username and password in config/database.php
3. Verify GoDaddy firewall allows remote MySQL connections
4. Check if remote MySQL access is enabled in GoDaddy control panel

### Tables Missing
**Issue:** Tables don't exist
**Solution:** Import schema.sql file (see Step 4)

### Permission Denied
**Issue:** Cannot create/insert/update
**Solution:** Contact GoDaddy support to grant necessary permissions to user 'egallegosle'

### Foreign Key Errors
**Issue:** Cannot insert data due to foreign key constraints
**Solution:** Ensure parent records exist in patients table before inserting child records

## Security Checklist

After database setup:
- [ ] Change database password from default (currently: jiujitsu4)
- [ ] Remove or secure test_connection.php from production
- [ ] Remove or secure test_db_cli.php from production
- [ ] Verify DB_DEBUG is set to false in production
- [ ] Ensure database is only accessible from application server
- [ ] Set up regular automated backups
- [ ] Enable MySQL slow query log for optimization
- [ ] Review and restrict user permissions (least privilege principle)

## Post-Setup Verification

Run this query to see a summary:
```sql
SELECT
    'patients' as table_name, COUNT(*) as row_count FROM patients
UNION ALL
SELECT 'medical_history', COUNT(*) FROM medical_history
UNION ALL
SELECT 'patient_consents', COUNT(*) FROM patient_consents
UNION ALL
SELECT 'financial_agreements', COUNT(*) FROM financial_agreements
UNION ALL
SELECT 'additional_consents', COUNT(*) FROM additional_consents
UNION ALL
SELECT 'form_submissions', COUNT(*) FROM form_submissions
UNION ALL
SELECT 'drchrono_sync_log', COUNT(*) FROM drchrono_sync_log
UNION ALL
SELECT 'audit_log', COUNT(*) FROM audit_log;
```

All tables should show 0 rows initially (or test data if you've been testing).
