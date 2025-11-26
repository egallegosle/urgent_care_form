# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Project Name:** Urgent Care Form System

**Description:** A web-based form system to digitalize patient intake forms for urgent care facilities. Patients fill out web forms that are stored in a MySQL database and integrated with practice management systems.

**Current Status:** In Development

## Tech Stack

**Primary Languages:**
- PHP (backend/server-side logic)
- HTML (form structure)
- CSS (styling with responsive web design)
- SQL (database)

**Database:**
- MySQL

**External Integrations:**
- DrChrono API - for patient creation, data updates, and synchronization

## UI/UX Requirements

**Responsive Web Design:**
- Must be fully responsive and adapt to any screen size
- Support smartphones (portrait and landscape)
- Support tablets (portrait and landscape)
- Support desktop screens
- Use mobile-first approach
- Implement CSS media queries for different breakpoints
- Forms should be easy to fill out on touch devices
- Buttons and form inputs should be appropriately sized for mobile interaction

**Recommended CSS Approach:**
- Mobile-first breakpoints
- Flexbox or CSS Grid for layouts
- Viewport meta tag for proper mobile rendering
- Touch-friendly form controls (minimum 44px touch targets)

## Project Structure

```
urgent_care_form/
├── config/
│   ├── database.php        # Database configuration
│   └── drchrono.php        # DrChrono API credentials and config
├── public/
│   ├── index.php           # Main entry point
│   ├── css/
│   │   └── styles.css      # Main stylesheet (responsive)
│   └── js/
│       └── forms.js        # Client-side form validation
├── forms/
│   ├── patient_registration.php
│   ├── medical_history.php
│   └── insurance_info.php
├── includes/
│   ├── db.php              # Database connection handler
│   ├── functions.php       # Common utility functions
│   └── drchrono_api.php    # DrChrono API integration functions
├── process/
│   ├── save_form.php       # Form submission handler
│   └── sync_drchrono.php   # Sync data with DrChrono
└── sql/
    └── schema.sql          # Database schema
```

## Development Commands

**Database Setup:**
```bash
mysql -u root -p < sql/schema.sql
```

**Local Development Server:**
```bash
# Using PHP built-in server
php -S localhost:8000 -t public/
```

**Access the application:**
```
http://localhost:8000
```

**Test on different devices:**
- Use browser developer tools for responsive testing
- Test on actual mobile devices when possible

## Database Schema

The MySQL database stores patient form data before/during DrChrono synchronization.

**Key Tables:**
- `patients` - Patient demographic information
- `medical_history` - Patient medical history responses
- `insurance_info` - Insurance details
- `form_submissions` - Tracks form submission status and DrChrono sync status

## DrChrono API Integration

**API Documentation:** https://docs.drchrono.com/

**Key Endpoints:**
- `POST /api/patients` - Create new patient
- `PATCH /api/patients/{id}` - Update patient information
- `GET /api/patients` - Search/retrieve patients

**Authentication:** OAuth 2.0
- Store access tokens securely
- Handle token refresh
- Never commit API credentials to git

## Workflow

1. Patient fills out web form (HTML/CSS/JavaScript validation)
2. Form data submitted to PHP backend
3. Data validated and sanitized (server-side)
4. Data stored in MySQL database
5. Background process syncs with DrChrono API
6. Update local database with DrChrono patient ID

## Security Considerations

- Sanitize all user inputs (prevent SQL injection)
- Use prepared statements for database queries
- Validate and escape data before DrChrono API calls
- Store API credentials in environment variables or config files (not in git)
- Use HTTPS in production
- Implement CSRF protection for forms
- Comply with HIPAA requirements for patient data handling

## Configuration Files

Create these files locally (add to .gitignore):
- `config/database.php` - MySQL connection details
- `config/drchrono.php` - DrChrono API credentials
- `.env` - Environment-specific variables

## Code Guidelines

- Use procedural or simple OOP PHP (keep it straightforward)
- Comment complex logic, especially API integrations
- Use meaningful variable names
- Separate concerns (forms, processing, API calls)
- Follow consistent indentation (4 spaces)
- Use mysqli or PDO with prepared statements
- Handle errors gracefully with user-friendly messages
- Write mobile-first CSS with appropriate breakpoints
- Ensure form elements are touch-friendly on mobile devices

## Future Integration Considerations

While starting with DrChrono, structure the code to allow integration with other practice management systems in the future.
