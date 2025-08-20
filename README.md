# 📊 Proficiency Tracker System

A comprehensive web-based student grade tracking and analytics system designed for educational institutions. This system allows teachers to manage sections, input student grades, and analyze proficiency data, while providing administrators with powerful oversight and reporting capabilities.

## 🎯 Features

### 👨‍🏫 Teacher Dashboard
- **Section Management**: Create, view, and delete classroom sections
- **Grade Input**: Input student grades by gender (boys/girls) for each quarter
- **Proficiency Analytics**: View detailed grade distributions across 7 proficiency levels
- **Visual Charts**: Real-time charts showing grade distributions and trends
- **User Isolation**: Each teacher only sees their own sections and data

### 🛡️ Admin Dashboard (Master Account)
- **Subject Overview**: Analyze performance by subject and grade level across quarters
- **Teacher Analytics**: Comprehensive table view of all teacher performance data
- **System Reports**: Overall system statistics and insights
- **Data Export**: Export filtered data in CSV or JSON formats
- **Visual Analytics**: Charts showing subject distribution, grade levels, and overall performance

### 🔐 Security Features
- **Role-based Access Control**: Separate teacher and admin interfaces
- **Session Management**: Secure login/logout with session tracking
- **Data Isolation**: Teachers can only access their own data
- **Admin Oversight**: Admins excluded from teacher statistics

## 🏗️ System Architecture

### Technology Stack
- **Backend**: PHP 8.2+ with MySQLi
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Database**: MySQL 8.0+
- **Charts**: Chart.js library
- **Server**: Apache (XAMPP)

### Database Structure
```sql
users
├── id (Primary Key)
├── username (Unique)
├── password (Hashed)
├── role (enum: 'admin', 'teacher')
├── fullname
├── subject_taught
└── grade_level (enum: 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10')

sections
├── id (Primary Key)
├── section_name
├── created_by (Foreign Key → users.id)
└── created_at

grades
├── id (Primary Key)
├── section_id (Foreign Key → sections.id)
├── quarter (1-4)
├── student_grade (0-100)
├── gender (enum: 'Male', 'Female')
├── created_by (Foreign Key → users.id)
└── created_at
```

## ⚙️ Installation & Setup

### Prerequisites
- XAMPP (or similar LAMP/WAMP stack)
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Step 1: Server Setup
1. Install and start XAMPP
2. Start Apache and MySQL services
3. Place project files in `C:\xampp\htdocs\proficiency\`

### Step 2: Database Setup
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Create database named `proficiency_tracker`
3. Run the following SQL commands:

```sql
-- Create database
CREATE DATABASE proficiency_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE proficiency_tracker;

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher') NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    subject_taught VARCHAR(50) NOT NULL,
    grade_level ENUM('Grade 7', 'Grade 8', 'Grade 9', 'Grade 10') NOT NULL
);

-- Create sections table
CREATE TABLE sections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_name VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create grades table
CREATE TABLE grades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_id INT NOT NULL,
    quarter INT NOT NULL CHECK (quarter BETWEEN 1 AND 4),
    student_grade DECIMAL(5,2) NOT NULL CHECK (student_grade BETWEEN 0 AND 100),
    gender ENUM('Male', 'Female') NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### Step 3: Create Admin Account
Run this PHP script to create an admin account:

```php
<?php
$password = 'your_admin_password';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=proficiency_tracker', 'root', '');

// Insert admin user
$stmt = $pdo->prepare("INSERT INTO users (username, password, role, fullname, subject_taught, grade_level) VALUES (?, ?, 'admin', 'Administrator', 'Administration', 'Grade 7')");
$stmt->execute(['admin', $hashedPassword]);

echo "Admin account created successfully!";
?>
```

### Step 4: Configuration
1. Update database credentials in `backend/db.php` if needed:
```php
$host = 'localhost';
$db = 'proficiency_tracker';
$user = 'root';
$pass = ''; // Update if you have a MySQL password
```

## 🚀 Usage Guide

### For Teachers

#### 1. Registration & Login
- Visit: `http://localhost/proficiency/views/register.html`
- Fill in required information (username, password, full name, subject, grade level)
- Login at: `http://localhost/proficiency/views/login.html`

#### 2. Managing Sections
- Navigate to "Manage Sections" tab
- Add new sections with unique names
- View and delete existing sections

#### 3. Inputting Grades
- Go to "Input Grades" tab
- Select section and quarter
- Enter grades for boys and girls (comma-separated)
- Grades must be between 0-100

#### 4. Viewing Analytics
- Visit "Proficiency Data" tab
- Filter by section and/or quarter
- View grade distributions across 7 proficiency levels:
  - **Excellent**: 98-100%
  - **Very Good**: 95-97%
  - **Good**: 90-94%
  - **Satisfactory**: 85-89%
  - **Fair**: 80-84%
  - **Needs Improvement**: 75-79%
  - **Poor**: Below 75%

### For Administrators

#### 1. Admin Login
- Use admin credentials at login page
- Automatically redirected to admin dashboard

#### 2. Subject Overview
- View performance by subject across quarters
- Filter by subject, grade level, or quarter
- See proficiency breakdowns for each subject

#### 3. Teacher Analytics
- Comprehensive table of all teacher performance
- View detailed proficiency distributions
- Filter by subject or grade level

#### 4. System Reports & Export
- View overall system statistics
- Export data in multiple formats:
  - **All Data**: Complete system export
  - **By Subject**: Specific subject data
  - **By Grade Level**: Grade-specific data
  - **Combined Filters**: Subject + Grade Level
- Choose CSV (Excel) or JSON format

## 📁 File Structure

```
proficiency/
├── backend/
│   ├── db.php                          # Database connection
│   ├── login.php                       # User authentication
│   ├── register.php                    # User registration
│   ├── logout.php                      # Session termination
│   ├── get_sections.php               # Teacher sections API
│   ├── add_section.php                # Add new section
│   ├── delete_section.php             # Remove section
│   ├── input_proficiency.php          # Grade input API
│   ├── get_data.php                   # Teacher grade data
│   ├── admin_get_teachers.php         # Admin teacher data
│   ├── admin_get_subject_proficiency.php # Subject proficiency data
│   ├── admin_get_analytics.php        # Teacher analytics
│   ├── admin_get_system_stats.php     # System statistics
│   ├── admin_get_subjects.php         # Subject list
│   ├── admin_get_grade_distribution.php # Grade distribution
│   └── admin_export_data.php          # Data export functionality
├── public/
│   ├── styles.css                      # Main stylesheet
│   ├── script.js                       # Login/Register scripts
│   ├── script-teacher-dashboard.js     # Teacher dashboard logic
│   └── script-admin-dashboard.js       # Admin dashboard logic
├── views/
│   ├── login.html                      # Login page
│   ├── register.html                   # Registration page
│   ├── teacher-dashboard.html          # Teacher interface
│   └── admin-dashboard.html            # Admin interface
└── README.md                           # This documentation
```

## 🎨 Proficiency Level Color Coding

The system uses consistent color coding across all interfaces:

| Level | Range | Color | Usage |
|-------|-------|-------|--------|
| Excellent | 98-100% | Bright Cyan (#00D2FF) | Highest achievement |
| Very Good | 95-97% | Indigo (#3F51B5) | Above average |
| Good | 90-94% | Green (#4CAF50) | Good performance |
| Satisfactory | 85-89% | Yellow (#FFEB3B) | Meeting standards |
| Fair | 80-84% | Orange (#FF9800) | Below average |
| Needs Improvement | 75-79% | Deep Orange (#FF5722) | Requires attention |
| Poor | <75% | Red (#F44336) | Immediate intervention needed |

## 🔧 API Endpoints

### Teacher APIs
- `GET /backend/get_sections.php` - Get user's sections
- `POST /backend/add_section.php` - Create new section
- `POST /backend/delete_section.php` - Remove section
- `POST /backend/input_proficiency.php` - Submit grades
- `GET /backend/get_data.php` - Get grade data

### Admin APIs
- `GET /backend/admin_get_teachers.php` - Get teacher data
- `GET /backend/admin_get_subject_proficiency.php` - Subject performance
- `GET /backend/admin_get_analytics.php` - Teacher analytics
- `GET /backend/admin_get_system_stats.php` - System statistics
- `GET /backend/admin_get_subjects.php` - Available subjects
- `GET /backend/admin_export_data.php` - Export data

### Authentication APIs
- `POST /backend/login.php` - User login
- `POST /backend/register.php` - User registration
- `GET /backend/logout.php` - User logout

## 🛠️ Customization

### Adding New Proficiency Levels
1. Update the categorization logic in `backend/admin_get_grade_distribution.php`
2. Modify the JavaScript display functions in both dashboard scripts
3. Update the color schemes in the CSS files

### Modifying Grade Levels
1. Update the ENUM in the database `users` table
2. Modify the dropdown options in the HTML files
3. Update validation in the backend PHP files

### Changing Color Themes
1. Update CSS custom properties in the dashboard HTML files
2. Modify chart colors in the JavaScript dashboard files
3. Ensure consistent branding across all interfaces

## 🔍 Troubleshooting

### Common Issues

**Database Connection Errors**
- Check XAMPP MySQL service is running
- Verify database credentials in `backend/db.php`
- Ensure database exists and has proper permissions

**Login Issues**
- Verify user exists in database
- Check password hashing (use `password_hash()` for new users)
- Clear browser cache and cookies

**Grade Input Problems**
- Ensure grades are numeric values between 0-100
- Check that section exists and belongs to the user
- Verify quarter is between 1-4

**Chart Display Issues**
- Check browser console for JavaScript errors
- Ensure Chart.js CDN is accessible
- Verify data format in API responses

### Error Logging
Enable PHP error logging in `php.ini`:
```ini
log_errors = On
error_log = C:\xampp\logs\php_error.log
```

## 📊 Performance Considerations

### Database Optimization
- Indexes are automatically created on foreign keys
- Consider adding indexes on frequently queried columns
- Regularly clean up old session data

### Frontend Performance
- Charts are cached and updated only when data changes
- API calls are optimized with proper filtering
- Responsive design reduces mobile loading times

## 🔒 Security Best Practices

### Implemented Security Measures
- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Data isolation between users

### Additional Recommendations
- Use HTTPS in production
- Implement rate limiting for API endpoints
- Regular security updates for PHP and MySQL
- Input validation and sanitization
- CSRF protection for forms

## 🤝 Contributing

To contribute to this project:
1. Fork the repository
2. Create a feature branch
3. Follow the existing code style
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is designed for educational institutions. Please ensure compliance with your organization's data privacy policies when handling student information.

## 🆘 Support

For technical support or feature requests:
1. Check the troubleshooting section
2. Review the API documentation
3. Examine browser console for errors
4. Contact the development team

---

**Version**: 1.0  
**Last Updated**: August 20, 2025  
**Compatibility**: PHP 8.2+, MySQL 8.0+, Modern Browsers

---

*This system was designed to help educational institutions track and analyze student proficiency data effectively while maintaining security and user privacy.*
