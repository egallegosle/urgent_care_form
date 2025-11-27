# Admin Dashboard - Complete Feature Documentation

## Table of Contents
1. [Dashboard Overview](#dashboard-overview)
2. [Patient Management](#patient-management)
3. [Reports & Analytics](#reports--analytics)
4. [Clinic Settings](#clinic-settings)
5. [Data Export](#data-export)
6. [Security & Audit](#security--audit)
7. [Keyboard Shortcuts & Tips](#keyboard-shortcuts--tips)

---

## Dashboard Overview

### Accessing the Dashboard

**URL:** `http://YOUR_DOMAIN/admin/`

**Login Credentials:**
- Default username: `admin`
- Default password: `ChangeMe123!` (change immediately)

### Main Dashboard Features

The dashboard home (`/admin/index.php`) provides real-time healthcare KPIs:

#### Today's Metrics
- **Patients Today:** Total registrations for current day
- **Checked In:** Patients currently checked in
- **Waiting:** Patients in waiting status
- **Completion Rate:** Percentage of completed forms

#### Period Statistics
- **This Week:** Weekly patient count
- **This Month:** Monthly patient count
- **Average Form Time:** Mean time to complete all forms
- **Insurance Mix:** Ratio of insurance vs self-pay patients

#### DrChrono Sync Status
- **Synced:** Successfully synced to DrChrono
- **Pending:** Awaiting sync (with action button if > 0)
- **Failed:** Sync failures requiring attention

#### Visual Analytics
- **7-Day Patient Trend:** Line chart showing daily registrations
- **Insurance Distribution:** Pie chart of insurance vs self-pay
- **Patients Needing Attention:** Actionable list of issues
- **Recent Patients:** Latest 10 patient registrations

### Auto-Refresh

The dashboard can auto-refresh (commented out by default):
```javascript
// Enable in index.php
startAutoRefresh(60); // Refresh every 60 seconds
```

---

## Patient Management

### Patient List (`/admin/patients/list.php`)

Comprehensive patient management with advanced filtering.

#### Search & Filter Options

**Search Bar:**
- Real-time search (debounced)
- Searches: name, email, phone number
- Updates automatically after 500ms of typing

**Date Range Filters:**
- **From Date:** Start of date range
- **To Date:** End of date range

**Status Filter:**
- Registered
- Checked In
- In Progress
- Completed
- Cancelled

**DrChrono Sync Filter:**
- Pending
- Synced
- Failed

**Quick Filter Buttons:**
- Pending Sync
- Failed Sync
- Incomplete Forms
- Checked In

#### Patient List Columns

| Column | Description |
|--------|-------------|
| Patient Name | Full name + reason for visit preview |
| Age/Gender | Patient demographics |
| Contact | Email and phone number |
| Registered | Registration date and time |
| Forms | Complete/Incomplete badge |
| Status | Current workflow status |
| Sync | DrChrono sync status |
| Insurance | Insurance or Self-Pay badge |
| Actions | View patient details button |

#### Pagination

- **Records per page:** 25
- **Navigation:** Previous/Next + page numbers
- **Total count:** Shows "X to Y of Z patients"

#### Export Options

- **Export CSV:** Download filtered patient list
- **Print:** Print-friendly view

### Patient Detail View (`/admin/patients/view.php?id=X`)

Complete patient record with all form data consolidated.

#### Quick Actions Panel

**Status Management:**
- View current status badge
- Change status dropdown:
  - Check In
  - Mark In Progress
  - Mark Completed
- Tracks admin who performed action

**Forms Status:**
- Complete/Incomplete indicator
- Completion timestamp

**DrChrono Sync:**
- Current sync status
- DrChrono Patient ID (if synced)

**Priority Management:**
- Current priority level
- Change priority dropdown:
  - Normal
  - Urgent
  - Emergency

#### Information Sections

1. **Patient Demographics**
   - Full name, DOB, age, gender
   - SSN (if provided)
   - Marital status

2. **Contact Information**
   - Email (clickable mailto link)
   - Cell phone (clickable tel link)
   - Home phone
   - Full mailing address

3. **Emergency Contact**
   - Name, phone, relationship
   - Quick-dial capability

4. **Visit Information**
   - Reason for visit
   - Current medications
   - Known allergies

5. **Insurance Information**
   - Provider name
   - Policy and group numbers
   - Policy holder information
   - Self-pay indicator if no insurance

6. **Primary Care Physician**
   - PCP name and contact

7. **Medical History**
   - Smoking status and frequency
   - Alcohol consumption
   - Medical conditions
   - Previous surgeries
   - Allergy details
   - Family history

8. **Financial Agreement**
   - Payment method
   - Signed by and relationship
   - All acknowledgments

9. **Communication Preferences**
   - HIPAA acknowledgment
   - Voicemail authorization
   - Patient portal access
   - Preferred contact methods
   - Authorized caregivers

10. **Admin Notes**
    - Internal notes visible only to staff
    - Editable text area
    - Save functionality

#### Action Buttons

- **Back to List:** Return to patient list
- **Print:** Print patient record
- **Export PDF:** Download as PDF

### Patient Status Workflow

The system tracks patient progression through the urgent care visit:

```
Registered → Checked In → In Progress → Completed
     ↓
  Cancelled (optional)
```

**Status Definitions:**
- **Registered:** Form submitted, awaiting check-in
- **Checked In:** Patient arrived, waiting for provider
- **In Progress:** Currently being seen by provider
- **Completed:** Visit finished, patient discharged
- **Cancelled:** Appointment cancelled

**Automatic Timestamps:**
- Each status change records timestamp
- Tracks which admin performed the action
- Visible in patient_status table

### Priority Levels

Manage patient urgency:
- **Normal:** Standard visit (default)
- **Urgent:** Needs prompt attention
- **Emergency:** Critical, immediate care required

Priority badges display prominently in patient list and detail views.

---

## Reports & Analytics

### Daily Reports (`/admin/reports/daily.php`)

Detailed daily statistics and operational metrics.

#### Features

**Date Range Selection:**
- Custom from/to dates
- Quick filters: Last 7 Days, Last 30 Days

**Summary Statistics:**
- Total patients in period
- Daily average patient count

**Peak Hours Chart:**
- Hourly distribution (0-23 hours)
- Bar chart showing patient registrations by hour
- Identifies busy times for staffing

**Busiest Days Chart:**
- Day of week distribution
- Shows which days are busiest
- Helps with scheduling

**Daily Breakdown Table:**

| Metric | Description |
|--------|-------------|
| Date | Calendar date |
| Total Registrations | Patients registered |
| Forms Completed | Completed form count |
| Checked In | Patients checked in |
| Visits Completed | Finished visits |
| Insurance | Insurance patient count |
| Self-Pay | Self-pay patient count |
| Avg Form Time | Average minutes to complete |
| Synced | DrChrono sync success count |
| Failed Sync | Sync failures |

**Export Options:**
- Export to CSV with date range in filename

### Monthly Reports (`/admin/reports/monthly.php`)

Long-term trends and demographic analysis.

#### Charts & Analytics

1. **12-Month Patient Trend**
   - Line chart showing monthly totals
   - Identifies seasonal patterns
   - Growth/decline trends

2. **Insurance vs Self-Pay Trend**
   - Dual-line chart
   - Tracks payment mix over time
   - Revenue planning insights

3. **Age Distribution**
   - Age groups: Under 18, 18-29, 30-44, 45-64, 65+
   - Bar chart visualization
   - Last 3 months of data

4. **Gender Distribution**
   - Pie chart
   - Male/Female/Other breakdown
   - Last 3 months of data

5. **Top Reasons for Visit**
   - Top 10 chief complaints
   - Count and percentage bars
   - Visual percentage indicators
   - Last 3 months of data

6. **Top Insurance Providers**
   - Top 10 insurance companies
   - Patient count per provider
   - Distribution visualization
   - Helps with contract negotiations

#### Use Cases

- **Staffing:** Peak hours/days inform scheduling
- **Revenue:** Insurance mix guides billing strategy
- **Marketing:** Demographics inform outreach
- **Quality:** Top complaints guide care protocols
- **Contracts:** Insurance data supports negotiations

---

## Clinic Settings

### Settings Dashboard (`/admin/settings/index.php`)

Centralized configuration management.

#### Settings Categories

1. **Branding Settings**
   - Clinic Name
   - Logo URL
   - Primary Color (color picker)
   - Secondary Color (color picker)
   - *Applies to public forms*

2. **Contact Information**
   - Phone Number
   - Email Address
   - Street Address
   - City
   - State
   - ZIP Code
   - *Displayed on forms and communications*

3. **Operating Hours**
   - Monday - Sunday hours
   - Free-text format (e.g., "8:00 AM - 8:00 PM")
   - "Closed" for days off
   - *Shown on website/forms*

4. **System Settings**
   - Session Timeout (minutes)
   - Data Retention Period (days)
   - Enable DrChrono Sync (boolean)
   - *Internal system configuration*

5. **Notification Settings**
   - New Patient Notifications (enable/disable)
   - Notification Email Address
   - Failed Sync Notifications (enable/disable)
   - *Admin alert preferences*

#### How to Use

1. Navigate to Settings from main navigation
2. Click category tab or scroll to section
3. Edit values in form fields
4. Click "Save All Settings" button
5. Success message confirms save
6. Changes apply immediately

#### Settings Storage

Settings stored in `clinic_settings` table:
- Key-value pairs
- Type validation (text, number, boolean, email, url, color)
- Category organization
- Public/private flag
- Update tracking (who/when)

#### Adding Custom Settings

To add new settings, insert into database:

```sql
INSERT INTO clinic_settings (
    setting_key,
    setting_value,
    setting_type,
    category,
    display_name,
    description,
    is_public
) VALUES (
    'custom_setting',
    'default_value',
    'text',
    'custom',
    'Custom Setting Display Name',
    'Description of what this controls',
    FALSE
);
```

Then add to settings page UI.

---

## Data Export

### Export Options

#### 1. Patient List CSV Export

**Location:** Patient List page
**Button:** "Export CSV"

**Features:**
- Exports current filtered view
- Includes all visible columns
- UTF-8 with BOM (Excel compatible)
- Filename: `patients_export_YYYY-MM-DD_HHMMSS.csv`

**Columns Exported:**
- Patient ID
- Full Name
- Date of Birth
- Age, Gender
- Contact info (email, phone)
- Address (full)
- Reason for visit
- Insurance provider
- Registration date
- Current status
- DrChrono sync status

**Use Cases:**
- Billing reports
- Insurance submissions
- Compliance audits
- Data analysis in Excel

#### 2. Single Patient PDF Export

**Location:** Patient Detail View
**Button:** "Export PDF"

**Features:**
- Complete patient record
- Print-friendly format
- HIPAA confidentiality notice
- Includes all form sections
- Header with clinic info
- Footer with PHI warning

**Sections Included:**
- Demographics
- Contact information
- Emergency contact
- Visit information
- Insurance details
- Medical history
- Financial agreement
- Communication preferences

**Use Cases:**
- Chart documentation
- Referrals to specialists
- Insurance claims
- Legal records
- Patient records requests

#### 3. Daily Report CSV Export

**Location:** Daily Reports page
**Button:** "Export CSV"

**Features:**
- Date range in filename
- Detailed daily metrics
- Business intelligence ready

**Use Cases:**
- Monthly summaries
- Board reporting
- Trend analysis
- Performance metrics

#### 4. Table to CSV (JavaScript)

Any data table can be exported using:
```javascript
ExportUtils.exportTableToCSV('tableId', 'filename.csv');
```

### Export Logging

All exports are logged in `export_log` table:
- Export type (patient_list, patient_details, etc.)
- Export format (csv, pdf, excel)
- Admin who exported
- Filters applied
- Record count
- Date range
- PHI indicator
- Timestamp

**View export history:**
```sql
SELECT
    admin_id,
    export_type,
    export_format,
    record_count,
    created_at
FROM export_log
ORDER BY created_at DESC
LIMIT 100;
```

---

## Security & Audit

### Authentication System

#### Login Security

**Features:**
- Bcrypt password hashing
- Failed login tracking
- Account lockout after 5 attempts
- 15-minute lockout duration
- Session regeneration on login
- IP address logging

**Password Requirements:**
- Minimum 8 characters (recommend 12+)
- Mix of cases, numbers, symbols
- No common passwords

#### Session Management

**Features:**
- 30-minute timeout (configurable)
- Activity tracking
- Automatic session cleanup
- Concurrent session limiting
- Session invalidation on logout

**Session Data Stored:**
- Admin ID and username
- Full name and email
- Role (super_admin, admin, staff)
- Last activity timestamp
- Session creation time
- IP address
- User agent

#### Role-Based Access

**Roles:**
- **Super Admin:** Full access to all features
- **Admin:** Standard admin access
- **Staff:** Limited access (future implementation)

**Role Checking:**
```php
if (hasRole('super_admin')) {
    // Super admin only features
}
```

### Audit Logging

Every admin action is logged for HIPAA compliance.

#### Logged Actions

- **LOGIN:** Successful login
- **LOGOUT:** User logout
- **FAILED_LOGIN:** Failed login attempt
- **VIEW:** Viewing patient data (PHI access)
- **CREATE:** Creating records
- **UPDATE:** Modifying data
- **DELETE:** Deleting records
- **EXPORT:** Exporting data

#### Audit Log Fields

- Admin ID and username
- Action type
- Table and record ID
- Description
- Old/new values (JSON)
- IP address
- User agent
- Patient ID (if PHI accessed)
- PHI accessed flag
- Timestamp

#### Viewing Audit Logs

**Recent PHI access:**
```sql
SELECT
    admin_username,
    action_type,
    description,
    ip_address,
    created_at
FROM admin_audit_log
WHERE phi_accessed = TRUE
ORDER BY created_at DESC
LIMIT 100;
```

**Admin activity summary:**
```sql
SELECT
    admin_username,
    action_type,
    COUNT(*) as action_count,
    MAX(created_at) as last_action
FROM admin_audit_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY admin_username, action_type
ORDER BY admin_username, action_count DESC;
```

**Failed login attempts:**
```sql
SELECT
    admin_username,
    description,
    ip_address,
    created_at
FROM admin_audit_log
WHERE action_type = 'FAILED_LOGIN'
ORDER BY created_at DESC
LIMIT 50;
```

### HIPAA Compliance Features

1. **Access Logging:** All PHI access tracked
2. **Session Timeout:** Automatic logout
3. **Audit Trail:** Complete action history
4. **Secure Authentication:** Strong passwords + lockout
5. **Data Encryption:** HTTPS for transmission
6. **Export Tracking:** Who exported what data
7. **No PHI in URLs:** Patient IDs only
8. **Role-Based Access:** Permission controls

### Security Best Practices

1. **Use HTTPS:** Always encrypt in production
2. **Strong Passwords:** Enforce complexity
3. **Regular Audits:** Review logs weekly
4. **Limit Access:** Minimum necessary users
5. **Update Software:** Keep PHP/MySQL current
6. **IP Whitelisting:** Restrict to office IPs
7. **Backup Regularly:** Daily database backups
8. **Monitor Sessions:** Check for anomalies

---

## Keyboard Shortcuts & Tips

### Navigation Shortcuts

- **Ctrl/Cmd + Click** on patient: Open in new tab
- **Esc:** Close modals/alerts
- **Tab:** Navigate form fields efficiently

### Search Tips

**Patient Search supports:**
- Full name: "John Doe"
- Partial name: "Joh"
- Email: "john@email.com"
- Phone: "555-1234" or "(555) 123-4567"
- Multiple terms: Searches all fields

**Best Practices:**
- Use date filters to narrow results
- Combine search with status filters
- Export filtered views for offline analysis

### Performance Tips

1. **Use Date Ranges:** Limit large queries
2. **Close Unused Tabs:** Reduce browser memory
3. **Clear Old Data:** Archive after retention period
4. **Index Database:** Keep database optimized
5. **Monitor Sessions:** Clean up expired sessions

### Browser Compatibility

**Tested Browsers:**
- Chrome 90+ ✓
- Firefox 88+ ✓
- Safari 14+ ✓
- Edge 90+ ✓

**Not Supported:**
- Internet Explorer (use Edge instead)

### Mobile Access

The admin dashboard is fully responsive:
- **Mobile Menu:** Hamburger icon appears < 768px
- **Touch Friendly:** All buttons 44px+ touch target
- **Scrollable Tables:** Horizontal scroll on small screens
- **Optimized Forms:** Single column on mobile

**Mobile Tips:**
- Rotate to landscape for tables
- Use native date pickers
- Touch and hold for tooltips

---

## Advanced Features

### Custom Filters

Create custom filter presets by bookmarking URLs:

**Examples:**
- Urgent patients: `/admin/patients/list.php?status=checked_in&priority=urgent`
- Failed syncs today: `/admin/patients/list.php?sync=failed&date_from=2025-11-27`
- Incomplete insurance: `/admin/patients/list.php?filter=incomplete_forms`

### Bulk Operations (Future)

Planned features:
- Bulk status updates
- Batch DrChrono sync
- Mass email/SMS
- Multi-patient export

### API Integration (Future)

Planned REST API for:
- Mobile app access
- Third-party integrations
- Automated reporting
- Real-time dashboards

---

## Getting Help

### Common Questions

**Q: How do I add a new admin user?**
A: Insert into `admin_users` table with bcrypt password hash.

**Q: Can I customize the dashboard KPIs?**
A: Yes, edit `/home/egallegosle/projects/urgent_care_form/public/admin/index.php` queries.

**Q: How long is data retained?**
A: Default 7 years (2555 days), configurable in settings.

**Q: Can I recover deleted data?**
A: Only from database backups. No soft-delete currently.

**Q: How do I change session timeout?**
A: Edit `SESSION_TIMEOUT` constant in `/home/egallegosle/projects/urgent_care_form/includes/auth.php`.

### Support Resources

- **Setup Guide:** `ADMIN_SETUP.md`
- **Database Schema:** `database/admin_schema.sql`
- **Error Logs:** Check PHP and MySQL error logs
- **Audit Logs:** Review `admin_audit_log` table

---

## Changelog & Updates

**Version 1.0.0** (Initial Release)
- Complete admin dashboard
- Patient management system
- Reports and analytics
- Clinic settings
- Data export
- Security and audit logging
- HIPAA compliance features
- Responsive design
- Mobile support

**Future Enhancements:**
- Two-factor authentication
- Bulk operations
- Advanced reporting
- Custom dashboards
- SMS notifications
- Appointment scheduling
- Provider management
- Billing integration

---

For setup instructions, see `ADMIN_SETUP.md`.
