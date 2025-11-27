# Admin Dashboard Setup Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [First Login](#first-login)
6. [Security Recommendations](#security-recommendations)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before setting up the admin dashboard, ensure you have:

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx)
- Existing Urgent Care Form System installed
- Database access credentials

---

## Installation

The admin dashboard files are already included in your project structure:

```
/home/egallegosle/projects/urgent_care_form/
├── public/
│   ├── admin/                    # Admin dashboard files
│   │   ├── index.php            # Dashboard home
│   │   ├── login.php            # Login page
│   │   ├── logout.php           # Logout handler
│   │   ├── patients/            # Patient management
│   │   ├── settings/            # Settings pages
│   │   └── reports/             # Reports and analytics
│   ├── css/
│   │   └── admin.css            # Admin-specific styles
│   └── js/
│       └── admin.js             # Admin JavaScript
├── includes/
│   ├── auth.php                 # Authentication system
│   ├── admin_header.php         # Admin header/navigation
│   └── admin_footer.php         # Admin footer
└── database/
    └── admin_schema.sql         # Admin database tables
```

No file copying is required - all files are in place.

---

## Database Setup

### Step 1: Run the Admin Schema

Connect to your MySQL database and run the admin schema script:

```bash
# Using MySQL command line
mysql -u DB_USER -p DB_NAME < /home/egallegosle/projects/urgent_care_form/database/admin_schema.sql

# Or using the credentials from your config
mysql -u egallegosle -p uc_forms < /home/egallegosle/projects/urgent_care_form/database/admin_schema.sql
```

When prompted, enter your database password: `jiujitsu4`

### Step 2: Verify Tables Created

The script creates the following tables:
- `admin_users` - Admin user accounts
- `admin_sessions` - Active admin sessions
- `clinic_settings` - Clinic configuration
- `admin_audit_log` - Audit trail for HIPAA compliance
- `patient_status` - Patient workflow status
- `form_field_settings` - Form customization
- `export_log` - Data export tracking

Verify tables:

```sql
SHOW TABLES LIKE 'admin_%';
SHOW TABLES LIKE 'patient_status';
SHOW TABLES LIKE 'clinic_settings';
```

You should see 7 new tables listed.

### Step 3: Verify Default Data

The script automatically creates:
- Default admin user (username: `admin`, password: `ChangeMe123!`)
- Default clinic settings
- Database triggers for automatic patient status creation

Verify the default admin user:

```sql
SELECT admin_id, username, email, first_name, last_name, role FROM admin_users;
```

---

## Configuration

### Database Connection

The admin dashboard uses the same database configuration as your main application:

**File:** `/home/egallegosle/projects/urgent_care_form/config/database.php`

```php
define('DB_HOST', '68.178.244.46');
define('DB_PORT', 3306);
define('DB_USER', 'egallegosle');
define('DB_PASS', 'jiujitsu4');
define('DB_NAME', 'uc_forms');
```

No changes needed - it's already configured.

### Web Server Configuration

#### Apache (.htaccess)

Create `/home/egallegosle/projects/urgent_care_form/public/admin/.htaccess`:

```apache
# Prevent directory browsing
Options -Indexes

# Enable rewrite engine
RewriteEngine On

# Redirect to login if accessing admin directory without file
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^$ login.php [L]
```

#### Nginx

Add to your server block:

```nginx
location /admin {
    # Prevent access to sensitive files
    location ~ /admin/\.htaccess {
        deny all;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## First Login

### Step 1: Access the Login Page

Navigate to:
```
http://YOUR_DOMAIN/admin/login.php
```

Or if using the direct path:
```
http://68.178.244.46/admin/login.php
```

### Step 2: Use Default Credentials

**Default Login:**
- Username: `admin`
- Password: `ChangeMe123!`

### Step 3: Change Default Password (CRITICAL)

**IMPORTANT:** You must change the default password immediately after first login!

1. After logging in, go to Settings or run this SQL command:

```sql
-- Generate a new password hash for 'YourSecurePassword123!'
-- Use PHP to generate:
-- php -r "echo password_hash('YourSecurePassword123!', PASSWORD_BCRYPT);"

UPDATE admin_users
SET password_hash = '$2y$10$YOUR_NEW_HASH_HERE'
WHERE username = 'admin';
```

Or use the password change interface (if implemented):
- Navigate to Settings
- Change password
- Use a strong password (minimum 12 characters, mixed case, numbers, symbols)

### Step 4: Create Additional Admin Users (Optional)

```sql
INSERT INTO admin_users (username, password_hash, email, first_name, last_name, role)
VALUES (
    'your_username',
    '$2y$10$YOUR_PASSWORD_HASH',  -- Generate using password_hash()
    'email@clinic.com',
    'First',
    'Last',
    'admin'  -- or 'staff' or 'super_admin'
);
```

---

## Security Recommendations

### 1. Password Security

- **Minimum Requirements:**
  - At least 12 characters
  - Mix of uppercase and lowercase
  - Include numbers and symbols
  - No common words or patterns

- **Change passwords:**
  - Immediately after setup
  - Every 90 days
  - After any security incident

### 2. Session Security

The system includes built-in security features:
- **Session timeout:** 30 minutes (configurable in `/home/egallegosle/projects/urgent_care_form/includes/auth.php`)
- **Automatic lockout:** 5 failed login attempts = 15-minute lockout
- **Session regeneration:** New session ID on login
- **IP tracking:** All sessions logged with IP address

### 3. HTTPS Configuration

**CRITICAL:** Always use HTTPS in production!

Configure SSL certificate:
```bash
# Using Let's Encrypt (recommended)
sudo certbot --apache -d your-domain.com

# Or for Nginx
sudo certbot --nginx -d your-domain.com
```

Enforce HTTPS in `.htaccess`:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 4. File Permissions

Set proper permissions:
```bash
# Admin files should not be world-writable
chmod 755 /home/egallegosle/projects/urgent_care_form/public/admin
chmod 644 /home/egallegosle/projects/urgent_care_form/public/admin/*.php

# Config files should be restricted
chmod 600 /home/egallegosle/projects/urgent_care_form/config/database.php
```

### 5. Database Security

- Use strong database passwords
- Limit database user permissions
- Enable MySQL audit log
- Regular backups

### 6. HIPAA Compliance

The admin dashboard includes HIPAA-compliant features:
- **Audit logging:** All PHI access logged in `admin_audit_log`
- **Session timeout:** Automatic logout after 30 minutes
- **Access control:** Role-based permissions
- **Data encryption:** Use HTTPS for data in transit

**Review audit logs regularly:**
```sql
-- View recent PHI access
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

### 7. IP Whitelisting (Recommended)

Restrict admin access to specific IP addresses:

**.htaccess:**
```apache
<Files "login.php">
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.0/24  # Your office network
    Allow from YOUR.IP.ADDRESS.HERE
</Files>
```

### 8. Two-Factor Authentication (Future Enhancement)

Consider implementing 2FA for enhanced security.

---

## Troubleshooting

### Cannot Access Admin Login Page

**Problem:** 404 Not Found when accessing `/admin/login.php`

**Solution:**
1. Verify files exist:
   ```bash
   ls -la /home/egallegosle/projects/urgent_care_form/public/admin/
   ```

2. Check web server configuration
3. Verify document root points to `/public` directory

### Cannot Login - "Invalid username or password"

**Problem:** Default credentials not working

**Solution:**
1. Verify admin user exists:
   ```sql
   SELECT * FROM admin_users WHERE username = 'admin';
   ```

2. If no results, re-run admin schema:
   ```bash
   mysql -u egallegosle -p uc_forms < database/admin_schema.sql
   ```

3. Try resetting password:
   ```sql
   UPDATE admin_users
   SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       failed_login_attempts = 0,
       locked_until = NULL
   WHERE username = 'admin';
   ```
   (This resets password to `ChangeMe123!`)

### Account Locked

**Problem:** "Account is locked due to multiple failed login attempts"

**Solution:**
```sql
UPDATE admin_users
SET failed_login_attempts = 0,
    locked_until = NULL
WHERE username = 'admin';
```

### Session Expires Too Quickly

**Problem:** Session timeout is too short

**Solution:**
Edit `/home/egallegosle/projects/urgent_care_form/includes/auth.php`:
```php
// Change from 30 to desired minutes
define('SESSION_TIMEOUT', 60);  // 60 minutes instead of 30
```

Update clinic settings:
```sql
UPDATE clinic_settings
SET setting_value = '60'
WHERE setting_key = 'session_timeout';
```

### Database Connection Errors

**Problem:** "Connection failed" errors

**Solution:**
1. Verify database credentials in `config/database.php`
2. Test database connection:
   ```bash
   mysql -h 68.178.244.46 -P 3306 -u egallegosle -p uc_forms
   ```
3. Check database server is accessible
4. Verify user has proper permissions

### Missing Tables

**Problem:** "Table doesn't exist" errors

**Solution:**
Re-run admin schema:
```bash
mysql -u egallegosle -p uc_forms < database/admin_schema.sql
```

### Permission Denied Errors

**Problem:** Cannot access certain pages

**Solution:**
1. Check file permissions:
   ```bash
   ls -la /home/egallegosle/projects/urgent_care_form/public/admin/
   ```

2. Fix permissions:
   ```bash
   chmod -R 755 /home/egallegosle/projects/urgent_care_form/public/admin/
   chmod 644 /home/egallegosle/projects/urgent_care_form/public/admin/*.php
   ```

### Charts Not Displaying

**Problem:** Dashboard charts are blank

**Solution:**
1. Check browser console for JavaScript errors
2. Verify Chart.js CDN is loading:
   - Open browser developer tools
   - Check Network tab
   - Ensure `chart.umd.min.js` loads successfully

3. Try clearing browser cache

### Styles Not Loading

**Problem:** Admin dashboard looks unstyled

**Solution:**
1. Verify CSS files exist:
   ```bash
   ls -la /home/egallegosle/projects/urgent_care_form/public/css/admin.css
   ```

2. Check CSS path in header:
   - Open `/home/egallegosle/projects/urgent_care_form/includes/admin_header.php`
   - Verify: `<link rel="stylesheet" href="/css/admin.css">`

3. Clear browser cache

---

## Support and Maintenance

### Regular Maintenance Tasks

1. **Weekly:**
   - Review audit logs for unusual activity
   - Check for failed sync attempts
   - Monitor patient data quality

2. **Monthly:**
   - Review user accounts and permissions
   - Check database size and performance
   - Backup database
   - Review and archive old data

3. **Quarterly:**
   - Change admin passwords
   - Review security settings
   - Update PHP and MySQL if needed
   - Test disaster recovery procedures

### Backup Recommendations

**Database Backup:**
```bash
# Daily automated backup
mysqldump -u egallegosle -p uc_forms > backup_$(date +%Y%m%d).sql

# Weekly full backup
mysqldump -u egallegosle -p uc_forms --all-databases > full_backup_$(date +%Y%m%d).sql
```

**File Backup:**
```bash
# Backup entire project
tar -czf urgent_care_backup_$(date +%Y%m%d).tar.gz /home/egallegosle/projects/urgent_care_form/
```

### Getting Help

For technical support:
1. Check this documentation
2. Review error logs:
   - PHP error log: `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`
   - MySQL error log: `/var/log/mysql/error.log`
3. Check admin audit log for security issues
4. Contact your system administrator

---

## Next Steps

After successful setup:

1. **Configure Clinic Settings:**
   - Navigate to Settings
   - Update clinic name, contact info, branding
   - Set operating hours
   - Configure notification preferences

2. **Test Patient Workflow:**
   - View existing patients
   - Test status changes (checked in, in progress, completed)
   - Export sample data
   - Review reports

3. **Train Staff:**
   - Create user accounts for staff
   - Provide training on patient management
   - Establish workflows for daily operations
   - Review HIPAA compliance procedures

4. **Setup Monitoring:**
   - Configure email notifications
   - Set up automated backups
   - Establish security review schedule
   - Monitor DrChrono sync status

For detailed feature documentation, see `ADMIN_FEATURES.md`.
