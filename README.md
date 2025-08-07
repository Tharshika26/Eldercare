# Elderly Care Management System

A simple PHP-based management system for elderly care facilities with role-based access control for administrators and caretakers.

## Features

### Admin Features
- View all elders and their details
- View all caretakers and their assignments
- Allocate caretakers to elders
- Dashboard with statistics
- Session-based authentication

### Caretaker Features
- View assigned elders with detailed information
- Access medical information, medications, and special notes
- View emergency contacts
- Personal information dashboard

## File Structure

```
Eldercare/
├── index.html              # Home page with navigation
├── config.php              # Configuration and sample data
├── login.php               # Login page with authentication
├── register.php            # User registration page
├── admin_dashboard.php     # Admin dashboard
├── caretaker_dashboard.php # Caretaker dashboard
└── README.md              # This file
```

## Setup Instructions

1. **Server Requirements:**
   - PHP 7.0 or higher
   - Web server (Apache/Nginx) or XAMPP/WAMP
   - No database required (uses hard-coded data)

2. **Installation:**
   - Place all files in your web server directory
   - Ensure PHP is enabled
   - Access via `http://localhost/Eldercare/`

3. **Demo Credentials:**

   **Admin:**
   - Username: `admin`
   - Password: `admin123`

   **Caretakers:**
   - Username: `jennifer.martinez`
   - Password: `care123`
   
   - Username: `michael.thompson`
   - Password: `care123`
   
   - Username: `lisa.rodriguez`
   - Password: `care123`

## Sample Data

The system includes sample data for:

### Elders (5)
- Margaret Johnson (Room 101) - Assigned to Jennifer Martinez
- Robert Smith (Room 102) - Assigned to Michael Thompson
- Helen Davis (Room 103) - Assigned to Jennifer Martinez
- James Wilson (Room 104) - Assigned to Lisa Rodriguez
- Dorothy Brown (Room 105) - Assigned to Michael Thompson

### Caretakers (4)
- Jennifer Martinez (Morning shift) - 2 elders assigned
- Michael Thompson (Afternoon shift) - 2 elders assigned
- Lisa Rodriguez (Night shift) - 1 elder assigned
- David Chen (Morning shift) - 0 elders assigned

## Features Overview

### Authentication System
- Session-based login/logout
- Role-based access control
- Secure password validation
- User registration (demo mode)

### Admin Dashboard
- Statistics overview (total elders, caretakers, unassigned elders, available caretakers)
- Complete elders list with assignment status
- Complete caretakers list with workload
- Caretaker allocation system

### Caretaker Dashboard
- Personal information display
- Assigned elders with detailed information
- Medical information and emergency contacts
- Special care notes and requirements

### Data Management
- Hard-coded sample data for demonstration
- Helper functions for data retrieval
- Easy to extend with database integration

## Security Features

- Session-based authentication
- Input validation and sanitization
- Role-based access control
- XSS protection with htmlspecialchars()
- CSRF protection through session management

## Customization

### Adding New Elders
Edit the `$elders` array in `config.php`:

```php
[
    'id' => 6,
    'name' => 'New Elder Name',
    'age' => 80,
    'gender' => 'Female',
    'room_number' => '106',
    'medical_conditions' => 'Condition 1, Condition 2',
    'medications' => 'Medication 1, Medication 2',
    'emergency_contact' => 'Contact Name - Phone Number',
    'caretaker_id' => null, // or caretaker ID
    'admission_date' => '2024-01-01',
    'special_notes' => 'Special care instructions'
]
```

### Adding New Caretakers
Edit the `$caretakers` array in `config.php`:

```php
[
    'id' => 5,
    'name' => 'New Caretaker Name',
    'email' => 'email@eldercare.com',
    'phone' => '555-0205',
    'shift' => 'Shift description',
    'specialization' => 'Specialization area',
    'assigned_elders' => [],
    'hire_date' => '2024-01-01',
    'status' => 'Active'
]
```

### Adding New Users
Edit the `$users` array in `config.php`:

```php
[
    'id' => 5,
    'username' => 'newuser',
    'password' => 'password123',
    'role' => 'caretaker', // or 'admin'
    'name' => 'Full Name',
    'caretaker_id' => 5 // only for caretakers
]
```

## Future Enhancements

- Database integration (MySQL/PostgreSQL)
- CRUD operations for elders and caretakers
- Activity logging and audit trails
- Medical records management
- Scheduling and shift management
- Reporting and analytics
- Mobile-responsive design improvements
- Email notifications
- File upload for documents

## Technical Notes

- Uses pure PHP without frameworks
- Minimal CSS for simple, functional design
- Session-based state management
- Hard-coded data for demonstration
- Easy to extend and modify
- Cross-browser compatible
- Mobile-friendly responsive design

## Support

This is a demonstration system. For production use, consider:
- Implementing proper database storage
- Adding input validation and sanitization
- Implementing proper security measures
- Adding error handling and logging
- Using HTTPS for secure communication 