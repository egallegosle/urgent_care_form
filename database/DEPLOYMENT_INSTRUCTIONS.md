# Database Deployment Instructions for GoDaddy

## Prerequisites

- Access to GoDaddy cPanel
- MySQL database created in GoDaddy
- Database username and password

## Step-by-Step Deployment

### Option 1: Using phpMyAdmin (Recommended)

1. **Login to GoDaddy cPanel**
   - Go to your GoDaddy hosting control panel
   - Find and click on "phpMyAdmin"

2. **Select Your Database**
   - In phpMyAdmin, select your database from the left sidebar
   - If you haven't created a database yet, create one first through cPanel → MySQL Databases

3. **Import the Schema**
   - Click on the "SQL" tab at the top
   - Open `schema.sql` file in a text editor
   - Copy all the contents
   - Paste into the SQL query box
   - Click "Go" to execute

4. **Verify Tables Created**
   - You should see these tables in the left sidebar:
     - patients
     - medical_history
     - patient_consents
     - financial_agreements
     - additional_consents
     - form_submissions
     - drchrono_sync_log
     - audit_log

### Option 2: Using MySQL Command Line (Advanced)

If you have SSH access to your GoDaddy server:

```bash
mysql -u your_username -p your_database_name < schema.sql
```

### Option 3: Upload and Import

1. **Upload schema.sql**
   - Use FTP/SFTP to upload `schema.sql` to your server

2. **Import via phpMyAdmin**
   - In phpMyAdmin, click "Import" tab
   - Choose the uploaded `schema.sql` file
   - Click "Go"

## Database Configuration

After creating the database, create a configuration file:

### Create: `config/database.php`

```php
<?php
// Database Configuration
define('DB_HOST', 'your-godaddy-mysql-host');  // Usually something like: yourdomain.com
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');
define('DB_CHARSET', 'utf8mb4');

// Create connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }

    $conn->set_charset(DB_CHARSET);
    return $conn;
}

// Test connection (remove in production)
// $conn = getDBConnection();
// echo "Connected successfully!";
// $conn->close();
?>
```

## GoDaddy-Specific Notes

### Finding Your Database Details

1. **Login to cPanel**
2. **Go to "MySQL Databases"**
3. **Note down:**
   - Database name (usually has your cPanel username as prefix)
   - Database username
   - Database host (found in "Remote MySQL" section)

### Database Hostname

GoDaddy typically uses:
- `localhost` (if your site and database are on the same server)
- Or a specific hostname like: `your-domain.com` or `123.456.789.012`

### Common GoDaddy Issues

**Issue 1: "Table already exists" error**
- Solution: The script uses `CREATE TABLE IF NOT EXISTS`, so this is safe to ignore
- Or drop the database and recreate it

**Issue 2: "Access denied" error**
- Solution: Make sure your database user has ALL PRIVILEGES
- Go to cPanel → MySQL Databases → Add User to Database
- Select ALL PRIVILEGES

**Issue 3: Views not created**
- Solution: Some GoDaddy shared hosting plans don't allow CREATE VIEW
- You can comment out or skip the views section - they're optional

## Security Recommendations

1. **Never commit database credentials to git**
   - The `config/database.php` file is already in `.gitignore`

2. **Use strong passwords**
   - Generate a strong password for your database user

3. **Restrict database access**
   - Only allow connections from your web server IP

4. **Enable SSL for database connections** (if available)

## Testing the Database

After deployment, create a test file:

### Create: `public/test_db.php`

```php
<?php
require_once '../config/database.php';

try {
    $conn = getDBConnection();
    echo "✓ Database connection successful!<br>";

    // Test query
    $result = $conn->query("SHOW TABLES");
    echo "✓ Tables found: " . $result->num_rows . "<br>";

    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "<br>";
    }

    $conn->close();
    echo "<br>✓ All tests passed!";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

**Important:** Delete `test_db.php` after testing!

## Backup Recommendations

1. **Before deployment:** Backup any existing database
2. **After deployment:** Create a backup schedule in cPanel
3. **Regular backups:** GoDaddy provides automatic daily backups

## Need Help?

If you encounter issues:
1. Check GoDaddy's MySQL documentation
2. Contact GoDaddy support with the specific error message
3. Check PHP error logs in cPanel

## Next Steps

After database deployment:
1. Create `config/database.php` with your credentials
2. Test database connection using `test_db.php`
3. Update form processing files to save data to database
4. Implement DrChrono API integration
