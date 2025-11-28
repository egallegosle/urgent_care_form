# Document Upload Feature - Implementation Guide

## Overview

The Document Upload feature allows patients to upload insurance cards and photo IDs during registration, streamlining the check-in process and reducing manual data entry.

## Features Implemented

### Patient-Facing Features
✅ Upload insurance card (front and back)
✅ Upload photo ID (front and back)
✅ Drag-and-drop file upload
✅ Real-time file validation (type, size)
✅ Image preview before upload
✅ Progress indicator during upload
✅ Mobile-responsive design

### Admin Dashboard Features
✅ View all patient documents
✅ Download documents
✅ Verify/reject documents
✅ Delete documents (soft delete)
✅ Document status tracking
✅ HIPAA-compliant access logging

### Security Features
✅ File type validation (JPG, PNG, PDF only)
✅ File size limits (5MB max)
✅ MIME type verification
✅ Secure file storage outside web root
✅ Access control via PHP (no direct file access)
✅ Unique filename generation
✅ HIPAA audit logging

## Installation Instructions

### Step 1: Create Database Tables

Run the SQL schema to create required tables:

```bash
mysql -u [username] -p uc_forms < database/document_upload_schema.sql
```

This creates:
- `patient_documents` - Stores document metadata
- `document_access_log` - HIPAA audit trail
- `document_settings` - Configuration settings
- 3 views for reporting
- 2 stored procedures
- 1 trigger for access tracking

### Step 2: Create Upload Directory

Create the uploads directory with proper permissions:

```bash
# From project root
mkdir -p uploads/patient_documents
chmod 750 uploads/patient_documents
```

**IMPORTANT:** The uploads directory should be:
1. Outside the web root (recommended), OR
2. Protected with .htaccess (already created automatically)

### Step 3: Configure .htaccess Protection

If uploads are within web root, ensure .htaccess protection:

```apache
# uploads/patient_documents/.htaccess (created automatically)
Order Deny,Allow
Deny from all
```

This prevents direct HTTP access to uploaded files.

### Step 4: Update PHP Settings (if needed)

If you need to allow larger uploads, update `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
max_file_uploads = 20
```

Restart your web server after changes.

### Step 5: Test the Feature

1. **Test Patient Upload:**
   - Navigate to patient registration form
   - Scroll to "Upload Insurance Card & Photo ID" section
   - Try uploading different file types (should accept JPG, PNG, PDF)
   - Try uploading a file larger than 5MB (should reject)
   - Verify upload progress and success message

2. **Test Admin Dashboard:**
   - Log into admin dashboard
   - Navigate to Patients → View Patient
   - Verify "Uploaded Documents" section appears
   - Test viewing a document
   - Test downloading a document
   - Test verify/reject/delete actions

## File Structure

```
urgent_care_form/
├── database/
│   └── document_upload_schema.sql          # Database schema
├── includes/
│   └── document_functions.php              # Core upload functions
├── public/
│   ├── css/
│   │   └── document_upload.css             # Upload UI styles
│   ├── js/
│   │   └── document_upload.js              # Upload JavaScript
│   ├── forms/
│   │   └── 1_patient_registration.php      # Updated with upload UI
│   ├── process/
│   │   └── upload_document.php             # Upload processor
│   └── admin/
│       ├── patients/
│       │   └── view.php                    # Updated with document display
│       └── documents/
│           ├── view_document.php           # Serve documents
│           └── manage.php                  # Verify/reject/delete
└── uploads/
    └── patient_documents/                  # Upload storage
        └── [patient_id]/                   # Per-patient folders
            └── .htaccess                   # Auto-generated protection
```

## Database Schema

### patient_documents Table

| Column | Type | Description |
|--------|------|-------------|
| document_id | INT | Primary key |
| patient_id | INT | Foreign key to patients |
| document_type | ENUM | Type (insurance_card_front, photo_id_front, etc.) |
| document_category | ENUM | Category (insurance, identification, medical) |
| original_filename | VARCHAR | Original uploaded filename |
| stored_filename | VARCHAR | Unique stored filename |
| file_path | VARCHAR | Full file path |
| file_size | INT | Size in bytes |
| mime_type | VARCHAR | MIME type |
| status | ENUM | pending, verified, rejected, archived |
| upload_date | DATETIME | Upload timestamp |
| uploaded_by | ENUM | patient, staff, admin |
| is_deleted | BOOLEAN | Soft delete flag |

### document_access_log Table

Tracks all document access for HIPAA compliance:

| Column | Type | Description |
|--------|------|-------------|
| log_id | INT | Primary key |
| document_id | INT | Document accessed |
| patient_id | INT | Patient whose document was accessed |
| action | ENUM | upload, view, download, delete, verify, reject |
| accessed_by | ENUM | patient, staff, admin, system |
| user_id | INT | Admin user ID (if applicable) |
| ip_address | VARCHAR | IP address |
| user_agent | VARCHAR | Browser info |
| access_date | DATETIME | Access timestamp |

## Configuration

Default settings (stored in `document_settings` table):

```php
max_file_size: 5242880 (5MB)
allowed_extensions: ["jpg", "jpeg", "png", "pdf"]
allowed_mime_types: ["image/jpeg", "image/png", "application/pdf"]
storage_path: "uploads/patient_documents/"
require_verification: true
max_documents_per_patient: 20
```

To update settings:

```sql
UPDATE document_settings
SET setting_value = '10485760'  -- 10MB
WHERE setting_key = 'max_file_size';
```

## API Endpoints

### Patient Upload (POST)
**Endpoint:** `/public/process/upload_document.php`

**Parameters:**
- `document` (file) - The uploaded file
- `document_type` (string) - Type of document
- `description` (string, optional) - Document description

**Response:**
```json
{
  "success": true,
  "document_id": 123,
  "filename": "1234_insurance_card_front_1234567890_abc123.jpg",
  "status": "pending"
}
```

### Admin View Document (GET)
**Endpoint:** `/admin/documents/view_document.php?id={document_id}`

Serves the document with proper headers and access logging.

### Admin Manage Document (POST)
**Endpoint:** `/admin/documents/manage.php`

**Actions:**
- `verify` - Mark document as verified
- `reject` - Mark document as rejected (with reason)
- `delete` - Soft delete document

**Parameters:**
- `action` (string) - Action to perform
- `document_id` (int) - Document ID
- `reason` (string, for reject) - Rejection reason

## Usage Examples

### Patient Upload Flow

1. Patient fills out registration form
2. Scrolls to document upload section
3. Clicks upload area or drags file
4. File is validated client-side
5. File uploads with progress indicator
6. Success message displays
7. Document ID stored in hidden input
8. Form submission includes document references

### Admin Workflow

1. Staff logs into admin dashboard
2. Views patient details
3. Scrolls to "Uploaded Documents" section
4. Sees list of documents with status
5. Clicks "View" to preview document in new tab
6. Clicks "Verify" if document is acceptable
7. Clicks "Reject" if document needs to be re-uploaded
8. All actions are logged for HIPAA compliance

## Security Considerations

### File Upload Security

1. **File Type Validation**
   - Client-side: Accept attribute on file input
   - Server-side: Extension whitelist + MIME type check
   - Prevents malicious file uploads

2. **File Size Limits**
   - Prevents DoS attacks via large files
   - Configurable per deployment

3. **Unique Filenames**
   - Format: `{patient_id}_{doc_type}_{timestamp}_{random}.{ext}`
   - Prevents filename conflicts
   - Prevents file overwrites

4. **Access Control**
   - No direct file access via HTTP
   - All requests go through PHP
   - Authentication required
   - Access logged for audit

5. **Storage Security**
   - Files stored in protected directory
   - .htaccess denies direct access
   - File permissions: 0640
   - Directory permissions: 0750

### HIPAA Compliance

1. **Access Logging**
   - Every document access logged
   - Includes who, when, what, why
   - Logs retained indefinitely

2. **Audit Trail**
   - Document status changes tracked
   - Verification/rejection logged
   - Deletion logged (soft delete)

3. **Minimum Necessary**
   - Role-based access control
   - Only authorized staff can view
   - Patient documents isolated per patient

4. **Encryption**
   - HTTPS required in production
   - Files encrypted at rest (recommended)
   - Database credentials secured

## Performance Considerations

### File Storage

- Documents stored on filesystem (not database)
- Faster retrieval than BLOB storage
- Easier backup and migration
- Standard filesystem permissions

### Caching

- Browser caching disabled for security
- Each view/download re-authenticated
- Headers set to prevent caching

### Cleanup

- Rejected documents auto-deleted after 30 days
- Run monthly cleanup procedure:
  ```sql
  CALL cleanup_rejected_documents();
  ```

## Troubleshooting

### Upload Fails with "Failed to create upload directory"

**Solution:** Check directory permissions
```bash
chmod 750 uploads/patient_documents
chown www-data:www-data uploads/patient_documents
```

### Upload Fails with "File size exceeds maximum"

**Solutions:**
1. Check `php.ini` settings
2. Check `document_settings` table
3. Restart web server after php.ini changes

### Documents Not Displaying in Admin

**Solutions:**
1. Verify database tables created: `SHOW TABLES LIKE 'patient_documents'`
2. Check if documents exist: `SELECT COUNT(*) FROM patient_documents`
3. Verify `document_functions.php` is included
4. Check error logs

### "Permission Denied" When Viewing Document

**Solutions:**
1. Check file permissions: `ls -la uploads/patient_documents/*/*`
2. Ensure web server has read access
3. Verify .htaccess is not blocking PHP access

### MIME Type Errors

**Solution:** Ensure `fileinfo` PHP extension is enabled
```bash
php -m | grep fileinfo
```

If not installed:
```bash
# Ubuntu/Debian
sudo apt-get install php-fileinfo

# CentOS/RHEL
sudo yum install php-fileinfo
```

## Future Enhancements

### Planned Features
- [ ] Bulk document upload
- [ ] OCR text extraction (insurance info auto-fill)
- [ ] Image quality validation
- [ ] Document expiration tracking
- [ ] Email notifications for pending verification
- [ ] Document templates (what documents are required)
- [ ] Patient portal for document management
- [ ] Integration with DrChrono
- [ ] Document versioning
- [ ] Digital signature verification

### Optional Security Enhancements
- [ ] Virus scanning (ClamAV integration)
- [ ] Encryption at rest (AES-256)
- [ ] Two-factor authentication for document access
- [ ] Watermarking for printed documents
- [ ] Document retention policies
- [ ] Automatic PII redaction

## Maintenance

### Regular Tasks

**Daily:**
- Monitor upload errors in logs
- Check pending document count

**Weekly:**
- Review rejected documents
- Audit access logs for anomalies

**Monthly:**
- Run cleanup procedure for old rejected documents
- Review storage usage
- Backup uploaded documents

**Quarterly:**
- Security audit of access logs
- Review and update document settings
- Test disaster recovery procedures

### Backup Strategy

**Files:**
```bash
# Daily backup
tar -czf backups/documents_$(date +%Y%m%d).tar.gz uploads/patient_documents/

# Sync to remote storage
rsync -avz uploads/patient_documents/ remote:/backups/documents/
```

**Database:**
```bash
mysqldump -u [user] -p uc_forms patient_documents document_access_log > backups/documents_$(date +%Y%m%d).sql
```

## Support & Documentation

### Related Documentation
- [HIPAA Security Rule](https://www.hhs.gov/hipaa/for-professionals/security/)
- [PHP File Upload Best Practices](https://www.php.net/manual/en/features.file-upload.php)
- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)

### Code Documentation
- See inline comments in `document_functions.php`
- See JSDoc in `document_upload.js`
- See SQL comments in `document_upload_schema.sql`

## License & Credits

Part of the Urgent Care Form System
Built with security and HIPAA compliance in mind

---

**Last Updated:** November 28, 2025
**Version:** 1.0.0
**Status:** Production Ready (after testing)
