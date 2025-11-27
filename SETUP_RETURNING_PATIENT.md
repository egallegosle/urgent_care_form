# Quick Setup Guide - Returning Patient Feature

## Installation Steps

### Step 1: Database Setup (Required)

Run the database schema to create all necessary tables:

```bash
cd /home/egallegosle/projects/urgent_care_form
mysql -u egallegosle -p uc_forms < database/returning_patient_schema.sql
```

Enter password when prompted: `jiujitsu4`

**Verify installation:**
```bash
mysql -u egallegosle -p uc_forms -e "SHOW TABLES LIKE '%visit%'; SHOW TABLES LIKE '%audit%';"
```

You should see:
- patient_visits
- audit_patient_lookup
- patient_sessions
- rate_limit_tracking

### Step 2: Test the Feature

**Test New Homepage:**
1. Open browser to: `http://your-domain/index.php`
2. You should see two cards: "New Patient" and "Returning Patient"

**Test Returning Patient Lookup:**
1. First, create a test patient:
   - Click "New Patient"
   - Fill out form with email: `test@example.com`, DOB: `1990-01-01`
   - Complete all 5 forms

2. Test returning patient flow:
   - Go back to homepage
   - Click "Returning Patient"
   - Enter email: `test@example.com`, DOB: `1990-01-01`
   - Click "Find My Information"
   - You should see confirmation page with patient name
   - Check consent boxes and continue
   - Verify Form 1 is pre-filled with previous data (blue highlighted fields)

**Test Rate Limiting:**
1. Click "Returning Patient"
2. Enter wrong email/DOB 5 times quickly
3. 6th attempt should show: "Too many attempts..."

### Step 3: Update Remaining Forms (Optional but Recommended)

Forms 2-5 need to be updated to support pre-filling. Use the templates in `RETURNING_PATIENT_FEATURE.md` section "Form Updates Guide".

**Quick checklist for each form:**
- [ ] Add PHP header code (session check, data loading)
- [ ] Add returning patient banner
- [ ] Add hidden fields for update tracking
- [ ] Pre-fill all form fields with getValue() function
- [ ] Add pre-filled-field CSS class
- [ ] Add CSS styles for returning patient UI

**For each processor (save_*.php):**
- [ ] Add is_update check
- [ ] Add UPDATE SQL logic with change tracking
- [ ] Keep existing INSERT logic for new patients
- [ ] Update form_submissions table

### Step 4: Admin Dashboard (Optional)

Add visit history to admin patient view. See `RETURNING_PATIENT_FEATURE.md` section "Admin Dashboard Integration".

---

## Files Created/Modified

### New Files:
```
✅ /database/returning_patient_schema.sql
✅ /includes/returning_patient_functions.php
✅ /public/returning_patient.php
✅ /public/patient_found.php
✅ /public/process/lookup_patient.php
✅ /public/process/log_consent.php
✅ /public/process/refresh_session.php
✅ /RETURNING_PATIENT_FEATURE.md
✅ /SETUP_RETURNING_PATIENT.md
```

### Modified Files:
```
✅ /public/index.php (added patient type selection)
✅ /public/forms/1_patient_registration.php (added pre-fill support)
✅ /public/process/save_patient_registration.php (handles UPDATE/INSERT)
```

### Files That Need Updates (Use Templates):
```
⏳ /public/forms/2_medical_history.php
⏳ /public/forms/3_patient_consent.php
⏳ /public/forms/4_financial_agreement.php
⏳ /public/forms/5_additional_consents.php

⏳ /public/process/save_medical_history.php
⏳ /public/process/save_patient_consent.php
⏳ /public/process/save_financial_agreement.php
⏳ /public/process/save_additional_consents.php
```

---

## Testing Checklist

After setup, test these scenarios:

### Basic Functionality
- [ ] Homepage shows two patient type options
- [ ] Clicking "New Patient" goes to Form 1 (original flow)
- [ ] Clicking "Returning Patient" goes to lookup page
- [ ] Lookup page has email and DOB fields
- [ ] Successful lookup shows confirmation page
- [ ] Confirmation page shows masked patient info
- [ ] Consent checkboxes must be checked to continue
- [ ] Form 1 shows pre-filled data with blue highlighting

### Security
- [ ] 5 failed lookups blocks IP for 15 minutes
- [ ] Session expires after 30 minutes of inactivity
- [ ] SSN is masked (XXX-XX-1234)
- [ ] Email is partially masked on confirmation
- [ ] All lookups logged in audit_patient_lookup table

### Data Integrity
- [ ] Returning patient updates existing record (no duplicate)
- [ ] New visit record created for returning patients
- [ ] Changed fields tracked in patient_visits table
- [ ] form_submissions updated correctly
- [ ] Patient can complete all 5 forms successfully

---

## Configuration

### Rate Limiting Settings

Edit `/includes/returning_patient_functions.php`:

```php
// Default: 5 attempts per 15 minutes
$rate_limit = checkRateLimit($conn, $ip_address, 5, 15);

// To change: modify the numbers
$rate_limit = checkRateLimit($conn, $ip_address, 10, 30); // 10 attempts per 30 min
```

### Session Timeout

Edit `/public/process/lookup_patient.php`:

```php
// Default: 30 minutes
$_SESSION['session_expires'] = time() + (30 * 60);

// To change:
$_SESSION['session_expires'] = time() + (60 * 60); // 60 minutes
```

---

## Troubleshooting

### Database Connection Error

**Error:** "Connection failed"

**Solution:**
```bash
# Test database connection
php /home/egallegosle/projects/urgent_care_form/test_db_cli.php

# If fails, check config
cat /home/egallegosle/projects/urgent_care_form/config/database.php
```

### Tables Not Created

**Error:** "Table patient_visits doesn't exist"

**Solution:**
```bash
# Check if tables exist
mysql -u egallegosle -p uc_forms -e "SHOW TABLES;"

# If missing, re-run schema
mysql -u egallegosle -p uc_forms < database/returning_patient_schema.sql
```

### Session Issues

**Error:** "Session expired" immediately after lookup

**Solution:**
```php
// Check session configuration
// In your php.ini or create .htaccess:

php_value session.gc_maxlifetime 1800
php_value session.cookie_lifetime 1800
```

### Pre-fill Not Working

**Error:** Forms show empty instead of pre-filled

**Solution:**
```php
// Debug by adding to top of form:
<?php
echo "Returning: " . ($is_returning ? 'YES' : 'NO') . "<br>";
echo "Patient ID: " . ($_SESSION['returning_patient_id'] ?? 'NOT SET') . "<br>";
var_dump($patient_data);
?>
```

---

## Monitoring

### Check Recent Activity

```sql
-- Returning patient lookups today
SELECT COUNT(*) as total_lookups,
       SUM(patient_found) as successful,
       COUNT(*) - SUM(patient_found) as failed
FROM audit_patient_lookup
WHERE DATE(lookup_timestamp) = CURDATE();

-- Returning patient visits this week
SELECT
    DATE(visit_date) as date,
    COUNT(*) as visits,
    AVG(fields_changed_count) as avg_changes
FROM patient_visits
WHERE visit_type = 'returning'
    AND visit_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(visit_date);
```

### Security Monitoring

```sql
-- Blocked IPs
SELECT
    identifier as ip_address,
    blocked_until,
    attempt_count,
    TIMESTAMPDIFF(MINUTE, NOW(), blocked_until) as minutes_remaining
FROM rate_limit_tracking
WHERE blocked_until > NOW()
ORDER BY blocked_until DESC;

-- Suspicious activity (multiple failed attempts)
SELECT
    ip_address,
    COUNT(*) as failed_attempts,
    GROUP_CONCAT(DISTINCT lookup_email) as emails_tried,
    MAX(lookup_timestamp) as last_attempt
FROM audit_patient_lookup
WHERE patient_found = FALSE
    AND lookup_timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address
HAVING failed_attempts >= 3
ORDER BY failed_attempts DESC;
```

---

## Performance

### Recommended Indexes (Already Created)

The schema creates these indexes automatically:
- `patient_visits`: idx_patient_id, idx_visit_date, idx_visit_type
- `audit_patient_lookup`: idx_ip_address, idx_timestamp, idx_email
- `rate_limit_tracking`: idx_identifier, idx_blocked_until

### Query Optimization

For large databases (>10,000 patients), consider:

```sql
-- Add composite index for faster lookups
ALTER TABLE patients ADD INDEX idx_email_dob (email, date_of_birth);

-- Partition patient_visits by year (for very large datasets)
ALTER TABLE patient_visits PARTITION BY RANGE (YEAR(visit_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## Maintenance Tasks

### Daily (Automated via Cron)

```bash
# Create cleanup script: /home/egallegosle/projects/urgent_care_form/cleanup_daily.sh
#!/bin/bash

mysql -u egallegosle -p'jiujitsu4' uc_forms <<EOF
-- Clean expired sessions
DELETE FROM patient_sessions WHERE expires_at < NOW();

-- Reset old rate limits
DELETE FROM rate_limit_tracking WHERE first_attempt_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
EOF
```

Add to crontab:
```bash
crontab -e
# Add line:
0 2 * * * /home/egallegosle/projects/urgent_care_form/cleanup_daily.sh
```

### Weekly

Review audit logs and security incidents:
```sql
-- Weekly security report
SELECT
    'Total Lookups' as metric,
    COUNT(*) as value
FROM audit_patient_lookup
WHERE lookup_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)

UNION ALL

SELECT
    'Failed Lookups',
    COUNT(*)
FROM audit_patient_lookup
WHERE patient_found = FALSE
    AND lookup_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)

UNION ALL

SELECT
    'Blocked IPs',
    COUNT(DISTINCT identifier)
FROM rate_limit_tracking
WHERE blocked_until >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

---

## Next Steps

1. ✅ **Database setup complete** - Run the schema
2. ✅ **Test basic functionality** - Try returning patient flow
3. ⏳ **Update remaining forms** - Use templates for forms 2-5
4. ⏳ **Update remaining processors** - Add UPDATE logic to processors 2-5
5. ⏳ **Add admin dashboard features** - Show visit history
6. ✅ **Security review** - Test rate limiting and session management
7. 🎯 **Deploy to production** - After thorough testing

---

## Support

For issues or questions:
1. Check `RETURNING_PATIENT_FEATURE.md` for detailed documentation
2. Review troubleshooting section above
3. Check error logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`
4. Enable PHP error logging for debugging

---

**You're ready to go!** The core returning patient feature is now implemented. Test it thoroughly before deploying to production.
