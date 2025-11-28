# Document Upload Feature - Installation Steps

## ✅ Step 1: Upload Directory - COMPLETE

The upload directory has been created successfully:
- Location: `uploads/patient_documents/`
- Permissions: 750 (correct)
- Status: ✅ **READY**

---

## 📋 Step 2: Import Database Schema - TO DO

Since you're using GoDaddy hosting, you'll need to import the database schema via phpMyAdmin.

### Option A: Using phpMyAdmin (Recommended for GoDaddy)

1. **Access phpMyAdmin:**
   - Log into your GoDaddy hosting control panel (cPanel)
   - Click on "phpMyAdmin" in the Databases section
   - Select your database: `uc_forms`

2. **Import the Schema:**
   - Click on the "Import" tab at the top
   - Click "Choose File" button
   - Navigate to: `/home/egallegosle/projects/urgent_care_form/database/document_upload_schema.sql`
   - Click "Go" at the bottom
   - Wait for success message

3. **Verify Import:**
   - Click on the database name `uc_forms` in the left sidebar
   - You should see 3 new tables:
     - `patient_documents`
     - `document_access_log`
     - `document_settings`

### Option B: Using Command Line (If you have SSH access)

If you have SSH access to your GoDaddy server:

```bash
mysql -h 68.178.244.46 -u egallegosle -p uc_forms < database/document_upload_schema.sql
```

Enter your password when prompted.

### Option C: Copy/Paste SQL (If file upload doesn't work)

1. Open the file `database/document_upload_schema.sql` in a text editor
2. Copy all the contents (Ctrl+A, Ctrl+C)
3. In phpMyAdmin, click on the "SQL" tab
4. Paste the contents into the SQL query box
5. Click "Go"

---

## 🚀 Step 3: Deploy to GoDaddy Server

Your GitHub Actions workflow should automatically deploy the new files when you commit and push:

### Files to Commit:

```bash
cd /home/egallegosle/projects/urgent_care_form

# Add all new files
git add database/document_upload_schema.sql
git add includes/document_functions.php
git add public/css/document_upload.css
git add public/js/document_upload.js
git add public/process/upload_document.php
git add public/admin/documents/
git add uploads/

# Add modified files
git add public/forms/1_patient_registration.php
git add public/admin/patients/view.php

# Add documentation
git add DOCUMENT_UPLOAD_FEATURE.md
git add DOCUMENT_UPLOAD_SUMMARY.md
git add INSTALL_STEPS.md
git add setup_document_upload.sh

# Commit
git commit -m "Add document upload feature for insurance cards and photo IDs

Features:
- Patient can upload insurance cards and photo IDs during registration
- Admin dashboard integration for viewing and managing documents
- HIPAA-compliant audit logging
- Secure file storage with access control
- Mobile-responsive drag-and-drop upload UI
- File validation (type, size, MIME)

Database changes:
- Added patient_documents table
- Added document_access_log table
- Added document_settings table
- Added 3 views, 2 procedures, 1 trigger

🤖 Generated with Claude Code"

# Push to deploy
git push origin main
```

### After Deployment:

1. **Verify Upload Directory on Server:**
   - Use cPanel File Manager or FTP
   - Check that `uploads/patient_documents/` exists
   - Verify permissions are 750

2. **Create .htaccess Protection:**
   - If not already created, add this file: `uploads/patient_documents/.htaccess`
   - Contents:
     ```apache
     Order Deny,Allow
     Deny from all
     ```

---

## ✅ Step 4: Verification Checklist

After importing the database and deploying files:

### Database Verification:

In phpMyAdmin, run these queries:

```sql
-- Check tables were created
SHOW TABLES LIKE '%document%';
-- Should show: patient_documents, document_access_log, document_settings

-- Check settings were inserted
SELECT * FROM document_settings;
-- Should show 7 rows with upload configuration

-- Check views were created
SHOW FULL TABLES WHERE Table_type = 'VIEW';
-- Should include: vw_patient_documents_summary, vw_documents_pending_verification, vw_document_access_stats

-- Check procedures were created
SHOW PROCEDURE STATUS WHERE Db = 'uc_forms';
-- Should include: cleanup_rejected_documents, get_patient_documents
```

### File System Verification:

Using cPanel File Manager or FTP:

```
✅ /public/css/document_upload.css exists
✅ /public/js/document_upload.js exists
✅ /public/process/upload_document.php exists
✅ /public/admin/documents/view_document.php exists
✅ /public/admin/documents/manage.php exists
✅ /includes/document_functions.php exists
✅ /uploads/patient_documents/ exists (permissions 750)
✅ /uploads/patient_documents/.htaccess exists
```

### PHP Requirements:

SSH into server or use cPanel Terminal:

```bash
# Check PHP version (need 7.4+)
php -v

# Check fileinfo extension is enabled
php -m | grep fileinfo

# Check upload settings
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

If `upload_max_filesize` is less than 5M, you may need to update `php.ini` or `.htaccess`:

```apache
# Add to .htaccess in project root
php_value upload_max_filesize 10M
php_value post_max_size 12M
php_value max_file_uploads 20
```

---

## 🧪 Step 5: Testing

### Test Patient Upload:

1. **Navigate to registration form:**
   - URL: `https://your-domain.com/forms/1_patient_registration.php`

2. **Fill out the form** (at minimum):
   - First name, Last name
   - Date of birth (triggers session creation)
   - Email (required for session)
   - Reason for visit

3. **Scroll to "Upload Insurance Card & Photo ID" section**

4. **Test upload:**
   - Click or drag a JPG/PNG image
   - Should see preview
   - Should see progress bar
   - Should see "✓ Uploaded successfully" message

5. **Try invalid files:**
   - Upload a .txt or .exe file → Should reject
   - Upload a 10MB file → Should reject ("File size exceeds 5MB maximum")

### Test Admin Dashboard:

1. **Log into admin:**
   - URL: `https://your-domain.com/admin/login.php`
   - Default: admin / ChangeMe123!

2. **Find a patient with uploaded documents:**
   - Go to Patients → List
   - Click "View" on any patient who uploaded documents

3. **Scroll to "Uploaded Documents" section**

4. **Test actions:**
   - Click "View" eye icon → Should open document in new tab
   - Click "Download" → Should download file
   - Click "Verify" checkmark → Should mark as verified and reload
   - Click "Reject" X → Should prompt for reason and mark rejected
   - Click "Delete" trash icon → Should confirm and soft delete

### Test Security:

1. **Test direct file access:**
   - Try accessing: `https://your-domain.com/uploads/patient_documents/1/somefile.jpg`
   - Should get "403 Forbidden" or "Access Denied"

2. **Test authentication:**
   - Log out of admin
   - Try accessing: `https://your-domain.com/admin/documents/view_document.php?id=1`
   - Should redirect to login page

3. **Check audit logs:**
   - In phpMyAdmin, run:
     ```sql
     SELECT * FROM document_access_log ORDER BY access_date DESC LIMIT 10;
     ```
   - Should see entries for your test uploads and views

---

## 📊 Expected Results After Installation

### Database Tables Created:

| Table Name | Rows | Purpose |
|------------|------|---------|
| patient_documents | 0 | Document metadata |
| document_access_log | 0 | HIPAA audit trail |
| document_settings | 7 | Configuration |

### Views Created:

- `vw_patient_documents_summary` - Document counts per patient
- `vw_documents_pending_verification` - Admin workflow
- `vw_document_access_stats` - Reporting

### Procedures Created:

- `cleanup_rejected_documents()` - Monthly maintenance
- `get_patient_documents(patient_id)` - Data retrieval

### Triggers Created:

- `after_document_access` - Auto-updates access counts

---

## 🔧 Troubleshooting

### "Upload failed" Error:

**Check:**
1. Upload directory exists and has correct permissions
2. PHP upload settings are sufficient
3. Session contains patient_id
4. Database tables exist

**Debug:**
```php
// Add to upload_document.php temporarily
error_log("Session patient_id: " . ($_SESSION['patient_id'] ?? 'NOT SET'));
error_log("File info: " . print_r($_FILES, true));
```

### "Table doesn't exist" Error:

**Solution:**
- Import the database schema via phpMyAdmin
- Verify table names match exactly (case-sensitive on Linux servers)

### "Permission denied" When Uploading:

**Solution:**
```bash
# Set correct permissions via SSH
chmod 750 uploads/patient_documents
chown www-data:www-data uploads/patient_documents  # Or appropriate user
```

### Documents Not Showing in Admin:

**Check:**
1. Database tables exist
2. `document_functions.php` is included in view.php
3. Patient has actually uploaded documents
4. No PHP errors in error log

---

## 📞 Support

### Quick Checks:

```sql
-- Check if documents exist
SELECT COUNT(*) FROM patient_documents;

-- Check recent uploads
SELECT * FROM patient_documents ORDER BY upload_date DESC LIMIT 5;

-- Check access logs
SELECT * FROM document_access_log ORDER BY access_date DESC LIMIT 10;

-- Check settings
SELECT * FROM document_settings;
```

### Log Files to Check:

- PHP Error Log: Usually in `/var/log/php/error.log` or via cPanel
- Apache Error Log: Usually in `/var/log/apache2/error.log`
- Application Error Log: Check `error_log` file in project root

---

## ✅ Installation Complete Checklist

Before marking as complete, verify:

- [ ] Upload directory created with 750 permissions
- [ ] Database tables imported (3 tables)
- [ ] Database views created (3 views)
- [ ] Database procedures created (2 procedures)
- [ ] All PHP files deployed to server
- [ ] All CSS/JS files deployed to server
- [ ] .htaccess protection in place
- [ ] Patient upload tested successfully
- [ ] Admin view/download tested successfully
- [ ] Admin verify/reject tested successfully
- [ ] Audit logs are being created
- [ ] Direct file access is blocked
- [ ] PHP fileinfo extension enabled
- [ ] Upload size limits are adequate

---

## 🎉 You're Done!

Once all checkboxes are complete, the document upload feature is fully installed and ready for production use!

**Next Steps:**
1. Import database schema via phpMyAdmin (Step 2)
2. Commit and push to deploy files (Step 3)
3. Test the feature (Step 5)
4. Train staff on using the feature

**Documentation:**
- Full details: `DOCUMENT_UPLOAD_FEATURE.md`
- Quick reference: `DOCUMENT_UPLOAD_SUMMARY.md`

---

**Installation Started:** November 28, 2025
**Upload Directory:** ✅ Complete
**Database Import:** ⏳ Pending (Do via phpMyAdmin)
**Deployment:** ⏳ Pending (Git push)
**Testing:** ⏳ Pending
