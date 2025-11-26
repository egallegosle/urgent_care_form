# Urgent Care Form System

Web-based patient intake forms for urgent care facilities with responsive design and DrChrono integration capabilities.

## 📋 Forms Included

1. **Patient Registration** - Demographics, contact info, insurance, and emergency contacts
2. **Medical History** - Health conditions, medications, allergies, and lifestyle information
3. **Patient Consent** - Treatment consent and authorization
4. **Financial Agreement** - Payment terms and financial responsibility
5. **Additional Consents** - HIPAA acknowledgment, communication preferences, and portal access

## 🚀 Quick Start

### Running Locally

1. Start the PHP development server:
```bash
cd /home/egallegosle/projects/urgent_care_form
php -S localhost:8000 -t public/
```

2. Open your browser to:
```
http://localhost:8000
```

3. You can also test on mobile devices by accessing:
```
http://YOUR_COMPUTER_IP:8000
```

### Testing on Different Devices

The forms are fully responsive and optimized for:
- 📱 Smartphones (portrait and landscape)
- 📲 Tablets (portrait and landscape)
- 💻 Desktop computers

Use browser developer tools (F12) to test responsive design, or access from actual mobile devices.

## 📁 Project Structure

```
urgent_care_form/
├── public/                 # DocumentRoot - All web-accessible files
│   ├── index.php           # Landing page with form navigation
│   ├── css/
│   │   └── styles.css      # Responsive CSS framework
│   ├── js/
│   └── forms/
│       ├── 1_patient_registration.php
│       ├── 2_medical_history.php
│       ├── 3_patient_consent.php
│       ├── 4_financial_agreement.php
│       └── 5_additional_consents.php
├── docs/
│   └── form_images/        # Original form images for reference
├── config/                 # Configuration files (to be created)
├── includes/               # PHP helper functions (to be created)
└── CLAUDE.md               # AI assistant project context
```

## ✨ Features

- ✅ Mobile-first responsive design
- ✅ Touch-friendly form controls (44px minimum)
- ✅ Auto-calculated age from date of birth
- ✅ Form validation
- ✅ Clean, professional UI
- ✅ HIPAA-compliant design considerations
- ✅ Accessible form labels and structure

## 🔜 Next Steps

### Phase 2: Backend Implementation
- [ ] Create MySQL database schema
- [ ] Implement form processing (save_form.php)
- [ ] Add server-side validation
- [ ] Create database connection handler

### Phase 3: DrChrono Integration
- [ ] Set up DrChrono API credentials
- [ ] Implement OAuth authentication
- [ ] Create patient sync functionality
- [ ] Map form fields to DrChrono API

### Phase 4: Enhancements
- [ ] Digital signature capture
- [ ] PDF generation
- [ ] Email notifications
- [ ] Admin dashboard
- [ ] Form analytics

## 🎨 Customization

### Updating Colors
Edit `public/css/styles.css` and modify the CSS variables:
```css
:root {
    --primary-color: #0066cc;    /* Main brand color */
    --secondary-color: #004d99;  /* Secondary brand color */
    /* ... other variables */
}
```

### Adding Your Logo
Replace the text "PrimeHealth Urgent Care" with your logo image in each form's header section.

## 📱 Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (iOS Safari, Chrome Mobile)

## 📝 Notes

- Forms are currently UI-only (no database connection yet)
- Signature pads are placeholders for future implementation
- Form submission will be implemented in Phase 2

## 🔒 Security Considerations

- All forms use HTTPS in production
- Input validation on both client and server side
- SQL injection prevention through prepared statements
- CSRF protection required for production
- Sensitive config files excluded from version control

## 📄 License

Proprietary - All rights reserved

## 👨‍💻 Development

For detailed development guidelines, see [CLAUDE.md](CLAUDE.md)
