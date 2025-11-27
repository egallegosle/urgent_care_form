#!/bin/bash

###############################################################################
# Returning Patient Feature - Installation Script
# Run this script to set up the database and verify the installation
###############################################################################

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Database credentials
DB_USER="egallegosle"
DB_NAME="uc_forms"
DB_HOST="68.178.244.46"
DB_PORT="3306"

echo -e "${BLUE}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Returning Patient Feature - Installation        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "database/returning_patient_schema.sql" ]; then
    echo -e "${RED}Error: Schema file not found!${NC}"
    echo "Please run this script from the project root directory:"
    echo "cd /home/egallegosle/projects/urgent_care_form"
    echo "./install_returning_patient.sh"
    exit 1
fi

echo -e "${YELLOW}This script will:${NC}"
echo "  1. Create new database tables"
echo "  2. Create database views"
echo "  3. Create stored procedures"
echo "  4. Verify installation"
echo ""
echo -e "${YELLOW}Database: ${NC}$DB_NAME"
echo -e "${YELLOW}Server: ${NC}$DB_HOST:$DB_PORT"
echo ""

read -p "Continue with installation? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Installation cancelled."
    exit 0
fi

echo ""
echo -e "${BLUE}Step 1: Installing database schema...${NC}"

# Run the schema
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" < database/returning_patient_schema.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Schema installed successfully${NC}"
else
    echo -e "${RED}✗ Schema installation failed${NC}"
    echo "Please check your database credentials and try again."
    exit 1
fi

echo ""
echo -e "${BLUE}Step 2: Verifying tables...${NC}"

# Check if tables were created
TABLES=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" -sN -e "SHOW TABLES LIKE 'patient_visits'; SHOW TABLES LIKE 'audit_patient_lookup'; SHOW TABLES LIKE 'patient_sessions'; SHOW TABLES LIKE 'rate_limit_tracking';" 2>/dev/null | wc -l)

if [ "$TABLES" -eq 4 ]; then
    echo -e "${GREEN}✓ All 4 tables created successfully${NC}"
    echo "  • patient_visits"
    echo "  • audit_patient_lookup"
    echo "  • patient_sessions"
    echo "  • rate_limit_tracking"
else
    echo -e "${YELLOW}⚠ Warning: Expected 4 tables, found $TABLES${NC}"
    echo "Some tables may not have been created. Check for errors above."
fi

echo ""
echo -e "${BLUE}Step 3: Verifying views...${NC}"

# Check if views were created
VIEWS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" -sN -e "SHOW FULL TABLES WHERE table_type = 'VIEW';" 2>/dev/null | grep -E 'vw_patient_visit_history|vw_returning_patients_summary|vw_recent_lookup_attempts|vw_failed_lookup_attempts' | wc -l)

if [ "$VIEWS" -eq 4 ]; then
    echo -e "${GREEN}✓ All 4 views created successfully${NC}"
else
    echo -e "${YELLOW}⚠ Warning: Expected 4 views, found $VIEWS${NC}"
fi

echo ""
echo -e "${BLUE}Step 4: Verifying stored procedures...${NC}"

# Check if procedures were created
PROCS=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" -sN -e "SHOW PROCEDURE STATUS WHERE Db='$DB_NAME' AND Name IN ('cleanup_expired_sessions', 'check_rate_limit');" 2>/dev/null | wc -l)

if [ "$PROCS" -eq 2 ]; then
    echo -e "${GREEN}✓ All 2 stored procedures created successfully${NC}"
else
    echo -e "${YELLOW}⚠ Warning: Expected 2 procedures, found $PROCS${NC}"
fi

echo ""
echo -e "${BLUE}Step 5: Checking file permissions...${NC}"

# Check if PHP files are readable
if [ -r "includes/returning_patient_functions.php" ]; then
    echo -e "${GREEN}✓ Helper functions file is readable${NC}"
else
    echo -e "${RED}✗ Cannot read includes/returning_patient_functions.php${NC}"
fi

if [ -r "public/returning_patient.php" ]; then
    echo -e "${GREEN}✓ Lookup page is readable${NC}"
else
    echo -e "${RED}✗ Cannot read public/returning_patient.php${NC}"
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║          Installation Complete!                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}Next Steps:${NC}"
echo ""
echo "1. Test the feature:"
echo "   • Visit: http://your-domain/index.php"
echo "   • Click 'Returning Patient'"
echo "   • Try looking up a test patient"
echo ""
echo "2. Create a test patient:"
echo "   • Click 'New Patient'"
echo "   • Use email: test@example.com"
echo "   • Use DOB: 1990-01-01"
echo "   • Complete all forms"
echo ""
echo "3. Test returning patient flow:"
echo "   • Go back to homepage"
echo "   • Click 'Returning Patient'"
echo "   • Look up: test@example.com, 1990-01-01"
echo "   • Verify forms are pre-filled"
echo ""
echo "4. Review documentation:"
echo "   • RETURNING_PATIENT_FEATURE.md (complete guide)"
echo "   • SETUP_RETURNING_PATIENT.md (quick start)"
echo "   • IMPLEMENTATION_SUMMARY.md (overview)"
echo ""

echo -e "${YELLOW}Optional:${NC} Update forms 2-5 using templates in documentation"
echo ""

echo -e "${BLUE}Database Statistics:${NC}"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" -e "
SELECT
    TABLE_NAME as 'Table',
    TABLE_ROWS as 'Rows',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024, 2) as 'Size (KB)'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '$DB_NAME'
AND TABLE_NAME IN ('patient_visits', 'audit_patient_lookup', 'patient_sessions', 'rate_limit_tracking')
ORDER BY TABLE_NAME;
" 2>/dev/null

echo ""
echo -e "${GREEN}Ready to use! 🎉${NC}"
echo ""
