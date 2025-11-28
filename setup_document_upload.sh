#!/bin/bash

###############################################################################
# Document Upload Feature - Setup Script
# Automates the installation of the document upload feature
###############################################################################

echo "=========================================="
echo "Document Upload Feature - Setup Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running from project root
if [ ! -f "database/document_upload_schema.sql" ]; then
    echo -e "${RED}Error: Please run this script from the project root directory${NC}"
    exit 1
fi

echo "Step 1: Creating upload directory..."
if [ ! -d "uploads/patient_documents" ]; then
    mkdir -p uploads/patient_documents
    chmod 750 uploads/patient_documents
    echo -e "${GREEN}✓ Created uploads/patient_documents with permissions 750${NC}"
else
    echo -e "${YELLOW}⚠ Directory uploads/patient_documents already exists${NC}"
fi

echo ""
echo "Step 2: Database setup..."
echo "This will create the following tables:"
echo "  - patient_documents"
echo "  - document_access_log"
echo "  - document_settings"
echo ""

read -p "Enter MySQL username (default: egallegosle): " DB_USER
DB_USER=${DB_USER:-egallegosle}

read -sp "Enter MySQL password: " DB_PASS
echo ""

read -p "Enter database name (default: uc_forms): " DB_NAME
DB_NAME=${DB_NAME:-uc_forms}

read -p "Enter MySQL host (default: 68.178.244.46): " DB_HOST
DB_HOST=${DB_HOST:-68.178.244.46}

echo ""
echo "Importing database schema..."

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/document_upload_schema.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database schema imported successfully${NC}"
else
    echo -e "${RED}✗ Failed to import database schema${NC}"
    echo "Please check your database credentials and try again"
    exit 1
fi

echo ""
echo "Step 3: Verifying installation..."

# Check if tables were created
TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME' AND table_name IN ('patient_documents', 'document_access_log', 'document_settings')" -s -N)

if [ "$TABLE_COUNT" -eq "3" ]; then
    echo -e "${GREEN}✓ All 3 tables created successfully${NC}"
else
    echo -e "${RED}✗ Only $TABLE_COUNT/3 tables were created${NC}"
fi

echo ""
echo "Step 4: Checking PHP requirements..."

# Check if fileinfo extension is enabled
if php -m | grep -q fileinfo; then
    echo -e "${GREEN}✓ PHP fileinfo extension is enabled${NC}"
else
    echo -e "${RED}✗ PHP fileinfo extension is NOT enabled${NC}"
    echo "Please install it: sudo apt-get install php-fileinfo"
fi

# Check upload limits
MAX_UPLOAD=$(php -r "echo ini_get('upload_max_filesize');")
MAX_POST=$(php -r "echo ini_get('post_max_size');")

echo ""
echo "Current PHP upload settings:"
echo "  upload_max_filesize: $MAX_UPLOAD"
echo "  post_max_size: $MAX_POST"

# Parse and compare (assuming MB values)
MAX_UPLOAD_NUM=$(echo $MAX_UPLOAD | sed 's/M//')
if [ "$MAX_UPLOAD_NUM" -lt 5 ]; then
    echo -e "${YELLOW}⚠ Warning: upload_max_filesize is less than 5MB${NC}"
    echo "  Consider increasing in php.ini"
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test patient upload at: /public/forms/1_patient_registration.php"
echo "2. Test admin dashboard at: /public/admin/patients/view.php"
echo "3. Review documentation: DOCUMENT_UPLOAD_FEATURE.md"
echo ""
echo "Security checklist:"
echo "  [ ] Enable HTTPS in production"
echo "  [ ] Verify .htaccess protection on uploads directory"
echo "  [ ] Set appropriate file/directory permissions"
echo "  [ ] Review document_settings table for security settings"
echo "  [ ] Test file upload with different file types and sizes"
echo ""
echo -e "${GREEN}Installation complete!${NC}"
