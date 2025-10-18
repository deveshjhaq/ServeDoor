# 🍕 ServeDoor - Food Delivery Platform

![ServeDoor](https://img.shields.io/badge/Version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![License](https://img.shields.io/badge/License-MIT-green)

**ServeDoor** is a comprehensive food delivery platform that connects customers with their favorite restaurants. The platform provides a seamless ordering experience with real-time order tracking, secure payments, and a robust management system for restaurants and delivery personnel.

> **Tagline:** *"Groceries, Tiffin, Chai - Bas order karo, Tension bye-bye!!"*

---

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Project Structure](#-project-structure)
- [User Roles](#-user-roles)
- [API Integration](#-api-integration)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### 🛒 Customer Features

- **User Registration & Login**
  - Secure OTP-based authentication via SMS/WhatsApp
  - Email verification
  - Profile management with address book

- **Restaurant & Menu Browsing**
  - Browse restaurants by category
  - Advanced filtering (Veg/Non-Veg/Both)
  - Search functionality for dishes and restaurants
  - Real-time restaurant availability status

- **Smart Shopping Cart**
  - Add/remove items from cart
  - Quantity management
  - Real-time price calculation
  - Single restaurant per order enforcement (configurable)

- **Order Management**
  - Multiple payment options:
    - Online payment via Cashfree Gateway
    - Cash on Delivery (COD)
    - Wallet payment
  - Coupon code support with automatic validation
  - Order tracking with real-time status updates
  - Order history with detailed invoice
  - PDF invoice generation
  - Order cancellation (before preparation)
  - Rating and review system

- **Digital Wallet**
  - Add money to wallet
  - Use wallet balance for orders
  - Transaction history
  - Secure payment integration

- **Additional Features**
  - Multiple delivery addresses
  - Order tracking with Google Maps integration
  - Email notifications
  - WhatsApp notifications
  - Contact us form
  - Responsive design for mobile and desktop

### 🍴 Restaurant Owner Features

- **Restaurant Management**
  - Self-registration portal
  - Restaurant profile management
  - Logo and banner image upload
  - Operating hours configuration
  - Restaurant status control (Open/Closed)

- **Menu Management**
  - Add/edit/delete dishes
  - Category-based organization
  - Dish images
  - Price management
  - Veg/Non-Veg classification
  - Stock availability control

- **Order Processing**
  - Incoming order notifications
  - Order status management
  - Order acceptance/rejection
  - Preparation time estimation
  - Order history and analytics

- **Financial Management**
  - Payout requests
  - Transaction history
  - Earnings dashboard

### 🚴 Delivery Boy Features

- **Delivery Management**
  - Login portal with authentication
  - Assigned order notifications
  - Order details with pickup and delivery addresses
  - Google Maps integration for navigation
  - Order status updates:
    - Picked up from restaurant
    - Out for delivery
    - Delivered
  - Delivery history
  - Earnings tracking

### 👨‍💼 Admin Panel Features

- **Dashboard**
  - Real-time statistics
  - Order analytics
  - Revenue tracking
  - User growth metrics

- **User Management**
  - Customer list and details
  - Restaurant owner management
  - Delivery boy management
  - Status control (Active/Inactive)

- **Restaurant Management**
  - Approve/reject new restaurant registrations
  - Restaurant details management
  - Commission settings
  - Performance monitoring

- **Order Management**
  - View all orders
  - Filter by status, date, restaurant
  - Assign delivery boys
  - Manual order status updates
  - Order tracking

- **Content Management**
  - Banner management (promotional)
  - Category management
  - Coupon code creation and management
  - Settings configuration

- **Financial Management**
  - Payout approval system
  - Transaction monitoring
  - Revenue reports

- **System Management**
  - Error log monitoring
  - Contact us submissions
  - Website open/close toggle
  - SMTP settings
  - Payment gateway configuration

---

## 🛠️ Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **PHPMailer** - Email functionality
- **mPDF** - PDF invoice generation

### Frontend
- **HTML5 & CSS3** - Markup and styling
- **Tailwind CSS** - Utility-first CSS framework
- **JavaScript (ES6+)** - Client-side interactivity
- **Material Symbols** - Icon library

### Payment Integration
- **Cashfree Payment Gateway** - Secure online payments
- Production-ready with webhook support

### APIs & Services
- **Fast2SMS** - SMS/OTP delivery
- **WhatsApp Business API** - Order notifications
- **Google Maps API** - Location services and tracking
- **Google Places API** - Address autocomplete

### Security Features
- Prepared statements for SQL injection prevention
- XSS protection
- CSRF token implementation
- Password hashing
- OTP-based authentication
- Secure session management
- SSL/HTTPS enforcement

---

## 📦 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)
- SSL certificate (recommended for production)

### Step 1: Clone Repository

```bash
git clone https://github.com/deveshjhaq/ServeDoor.git
cd ServeDoor
```

### Step 2: Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE servedoor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema (if available):
```bash
mysql -u your_username -p servedoor < database/schema.sql
```

3. Update database credentials in `database.inc.php`:
```php
$con = mysqli_connect('localhost', 'your_username', 'your_password', 'servedoor');
```

### Step 3: Configuration

1. Copy and configure `constant.inc.php`:
```php
define('SITE_NAME','ServeDoor Admin');
define('FRONT_SITE_NAME','ServeDoor');
define('FRONT_SITE_PATH','https://yourdomain.com/');
```

2. Configure SMTP settings for email:
```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'your-email@yourdomain.com');
define('SMTP_PASS', 'your-password');
```

3. Configure SMS API (Fast2SMS):
```php
define('FAST2SMS_AUTH','your-api-key');
define('FAST2SMS_SENDER','YOUR_SENDER_ID');
```

4. Configure Payment Gateway (Cashfree):
```php
define('CASHFREE_ENVIRONMENT','production'); // or 'sandbox'
define('CASHFREE_APP_ID', 'your-app-id');
define('CASHFREE_SECRET_KEY', 'your-secret-key');
```

5. Add Google Maps API key:
```php
define('GOOGLE_API_KEY','your-google-api-key');
```

### Step 4: File Permissions

```bash
chmod 755 /path/to/ServeDoor
chmod 777 /path/to/ServeDoor/media/dish
chmod 777 /path/to/ServeDoor/media/banner
chmod 777 /path/to/ServeDoor/media/restaurants
```

### Step 5: Install Dependencies

```bash
# Install PHPMailer and other dependencies via vendor
composer install
```

Or manually include libraries from the `vendor` directory (already included).

### Step 6: Web Server Configuration

#### Apache (.htaccess already configured)
Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

#### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Step 7: Access the Application

- **Customer Portal:** `https://yourdomain.com/`
- **Admin Panel:** `https://yourdomain.com/admin/`
- **Restaurant Portal:** `https://yourdomain.com/restaurant/`
- **Delivery Portal:** `https://yourdomain.com/delivery_boy/`

---

## ⚙️ Configuration

### Essential Settings

#### 1. Database Configuration (`database.inc.php`)
```php
$con = mysqli_connect('localhost', 'username', 'password', 'database_name');
date_default_timezone_set('Asia/Kolkata'); // Set your timezone
```

#### 2. Site Constants (`constant.inc.php`)
```php
define('FRONT_SITE_PATH','https://yourdomain.com/');
define('SERVER_IMAGE', $_SERVER['DOCUMENT_ROOT'] . "/");
```

#### 3. Payment Gateway
- Register at [Cashfree](https://www.cashfree.com/)
- Get API credentials
- Configure webhook URL: `https://yourdomain.com/cashfree/cashfree_webhook.php`

#### 4. SMS/WhatsApp Integration
- Register at [Fast2SMS](https://www.fast2sms.com/)
- Get API key and configure templates
- Enable WhatsApp Business API

#### 5. Google Maps API
- Enable Maps JavaScript API
- Enable Places API
- Enable Geocoding API
- Add API key restrictions

---

## 📁 Project Structure

```
ServeDoor/
├── admin/                    # Admin panel
│   ├── index.php            # Dashboard
│   ├── order.php            # Order management
│   ├── restaurant.php       # Restaurant management
│   ├── delivery_boy.php     # Delivery boy management
│   ├── manage_dish.php      # Dish management
│   ├── banner.php           # Banner management
│   ├── coupon_code.php      # Coupon management
│   └── setting.php          # Settings
├── restaurant/              # Restaurant owner portal
│   ├── index.php            # Dashboard
│   ├── orders.php           # Order management
│   ├── manage_dish.php      # Menu management
│   └── profile.php          # Restaurant profile
├── delivery_boy/            # Delivery boy portal
│   ├── index.php            # Dashboard
│   └── auth.php             # Authentication
├── cashfree/                # Payment gateway integration
│   ├── createorder.php      # Order creation
│   ├── verify.php           # Payment verification
│   └── cashfree_webhook.php # Webhook handler
├── ajax/                    # AJAX endpoints
│   ├── send_otp.php         # OTP sending
│   └── verify_otp.php       # OTP verification
├── media/                   # Uploaded media
│   ├── dish/                # Dish images
│   ├── banner/              # Banner images
│   └── restaurants/         # Restaurant logos
├── assets/                  # Static assets
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── images/              # Static images
├── vendor/                  # Third-party libraries
│   ├── PHPMailer/           # Email library
│   ├── mpdf/                # PDF generation
│   └── ...
├── database.inc.php         # Database connection
├── constant.inc.php         # Configuration constants
├── function.inc.php         # Helper functions
├── header.php               # Common header
├── footer.php               # Common footer
├── index.html               # Landing page
├── shop.php                 # Restaurant listing
├── cart.php                 # Shopping cart
├── checkout.php             # Checkout page
├── my_order.php             # Order history
├── wallet.php               # Wallet management
├── profile.php              # User profile
└── README.md                # This file
```

---

## 👥 User Roles

### 1. Customer
- Browse restaurants and menus
- Place orders
- Track deliveries
- Manage wallet
- Rate and review

### 2. Restaurant Owner
- Manage restaurant profile
- Update menu items
- Process orders
- Request payouts

### 3. Delivery Boy
- View assigned deliveries
- Update delivery status
- Navigate to locations
- Track earnings

### 4. Admin
- Full system control
- User management
- Financial oversight
- System configuration

---

## 🔌 API Integration

### Payment Gateway (Cashfree)

```php
// Create order
$order_data = [
    'order_amount' => $amount,
    'order_currency' => 'INR',
    'customer_details' => [
        'customer_id' => $user_id,
        'customer_email' => $email,
        'customer_phone' => $phone
    ]
];
```

### SMS API (Fast2SMS)

```php
// Send OTP
$url = "https://www.fast2sms.com/dev/bulkV2";
$data = [
    'sender_id' => FAST2SMS_SENDER,
    'message' => $message,
    'route' => 'v3',
    'numbers' => $mobile
];
```

### Google Maps Integration

```javascript
// Initialize map
const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: latitude, lng: longitude },
    zoom: 15
});
```

---

## 🖼️ Screenshots

### Customer Portal
- Landing page with hero section
- Restaurant browsing with filters
- Shopping cart and checkout
- Order tracking with live map
- User profile and wallet

### Admin Panel
- Comprehensive dashboard with analytics
- Order management interface
- Restaurant approval system
- Financial reports

### Restaurant Portal
- Order management dashboard
- Menu item management
- Earnings overview

### Delivery Portal
- Active delivery list
- Navigation interface
- Delivery history

---

## 🚀 Features Highlights

### Why Choose ServeDoor?

✅ **Fast Delivery** - Get your food delivered in under 30 minutes, hot and fresh

✅ **Top Restaurants** - A curated selection of the best local and national restaurants

✅ **Secure Payments** - Your payment information is always safe and secure

✅ **User Rewards** - Earn points and get exclusive discounts on every order

### Security Features

- SQL Injection prevention via prepared statements
- XSS protection on all user inputs
- CSRF token implementation
- Secure password hashing (bcrypt)
- OTP-based two-factor authentication
- SSL/HTTPS enforcement
- Session security with secure cookies
- Input validation and sanitization
- Error logging system

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 📞 Support

For support, email **deveshjhaq@gmail.com** or visit our [Contact Page](https://servedoor.com/contact-us.php).

---

## 🙏 Acknowledgments

- **PHPMailer** - Email functionality
- **mPDF** - PDF generation
- **Tailwind CSS** - UI framework
- **Cashfree** - Payment gateway
- **Fast2SMS** - SMS service
- **Google Maps** - Location services

---

## 🔄 Version History

### Version 1.0.0 (Current)
- Initial release
- Customer ordering system
- Restaurant management
- Delivery boy tracking
- Admin panel
- Payment integration
- Wallet system
- Multi-language support

---

## 📊 System Requirements

### Minimum Requirements
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+ or Nginx 1.18+
- 2GB RAM
- 10GB Storage
- SSL Certificate

### Recommended Requirements
- PHP 8.0+
- MySQL 8.0+
- 4GB RAM
- 20GB SSD Storage
- CDN for static assets
- Redis for caching (optional)

---

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## 📚 Documentation

For detailed documentation, please refer to:
- [User Guide](docs/user-guide.md) *(coming soon)*
- [Admin Guide](docs/admin-guide.md) *(coming soon)*
- [API Documentation](docs/api-docs.md) *(coming soon)*
- [Developer Guide](docs/developer-guide.md) *(coming soon)*



## 📞 Contact

For business inquiries, support, or collaboration, contact:

- **Devesh Jha**  
  [LinkedIn: deveshjhaq](https://www.linkedin.com/in/deveshjhaq)

---
<!--
╔════════════════════════════════════════════════════════════════╗
║                        COPYRIGHT NOTICE                        ║
╚════════════════════════════════════════════════════════════════╝

All code, scripts, configuration files, and database structures in this repository (ServeDoor) are the intellectual property of the owner (deveshjhaq).

Unauthorized copying, distribution, modification, or use of any part of this codebase, in whole or in part, is strictly prohibited.

Any violation of this copyright notice may result in legal action and is punishable under applicable laws.
-->
**Made with ❤️**

© 2025 ServeDoor. All rights reserved.

---
