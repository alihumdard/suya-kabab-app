# 📖 Suya Kabab Application - Complete Documentation

---

## 📋 **Table of Contents**

1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Multi-Auth System](#multi-auth-system)
4. [Database Schema](#database-schema)
5. [API Documentation](#api-documentation)
6. [Authentication System](#authentication-system)
7. [Admin Panel Features](#admin-panel-features)
8. [User Interface Features](#user-interface-features)
9. [Installation Guide](#installation-guide)
10. [Configuration](#configuration)
11. [Security Features](#security-features)
12. [Testing](#testing)
13. [Deployment](#deployment)
14. [Troubleshooting](#troubleshooting)

---

## 🎯 **Project Overview**

**Project Name:** Suya Kabab Application  
**Type:** Food Delivery & Restaurant Management System  
**Framework:** Laravel 12 (Backend) + Vue.js 3 (Frontend)  
**Database:** MySQL 8.0+  
**Authentication:** Multi-Auth System with Sanctum  
**Design Reference:** [Figma Design](https://www.figma.com/design/nhQM1fdZ5jZbu3mOPLQ5TC/Suya-kabab-Application?node-id=0-1&p=f)

### **Core Features:**
- Custom multi-authentication system
- Isolated admin and user management
- User authentication with email verification
- OTP-based password reset
- Admin dashboard (Web Routes)
- API endpoints (Sanctum Authentication)
- Real-time order tracking
- Payment integration
- Review and rating system

---

## 🖥️ **System Requirements**

### **Server Requirements:**
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Node.js 18.0 or higher
- Redis (for caching and sessions)
- Apache/Nginx web server

### **Development Tools:**
- Composer 2.0+
- npm/yarn
- Git
- Postman (for API testing)

---

## 🔐 **Multi-Auth System**

### **Authentication Architecture**
- **Separate Tables**: `users` and `admins` tables with isolated authentication
- **Web Routes**: Admin dashboard using session-based authentication
- **API Routes**: User APIs using Laravel Sanctum token authentication
- **Custom Guards**: Separate authentication guards for admin and user

### **Authentication Flow**
```
┌─────────────────┐    ┌─────────────────┐
│   Admin Panel   │    │   User Mobile   │
│   (Web Routes)  │    │   (API Routes)  │
└─────────────────┘    └─────────────────┘
         │                       │
         │                       │
    ┌─────────┐              ┌─────────┐
    │ Session │              │ Sanctum │
    │  Auth   │              │  Token  │
    └─────────┘              └─────────┘
         │                       │
         │                       │
    ┌─────────┐              ┌─────────┐
    │ Admins  │              │  Users  │
    │  Table  │              │  Table  │
    └─────────┘              └─────────┘
```

---

## 🗄️ **Database Schema**

### **1. Users Table (Customer Authentication)**
```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    otp VARCHAR(6) NULL,
    otp_expires_at TIMESTAMP NULL,
    otp_attempts INT DEFAULT 0,
    reset_password_token VARCHAR(255) NULL,
    reset_password_expires_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_phone (phone)
);
```

### **2. Admins Table (Admin Authentication)**
```sql
CREATE TABLE admins (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'admin',
    permissions JSON NULL,
    profile_image VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_by BIGINT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);
```

### **3. Personal Access Tokens Table (Sanctum)**
```sql
CREATE TABLE personal_access_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_token (token)
);
```

### **4. Categories Table**
```sql
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order),
    INDEX idx_slug (slug)
);
```

### **5. Products Table**
```sql
CREATE TABLE products (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    short_description VARCHAR(500) NULL,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE NULL,
    image VARCHAR(255) NULL,
    gallery_images JSON NULL,
    ingredients TEXT NULL,
    nutrition_info JSON NULL,
    allergen_info TEXT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_vegetarian BOOLEAN DEFAULT FALSE,
    is_vegan BOOLEAN DEFAULT FALSE,
    is_halal BOOLEAN DEFAULT TRUE,
    preparation_time INT NULL COMMENT 'in minutes',
    calories INT NULL,
    serving_size VARCHAR(50) NULL,
    spice_level ENUM('mild', 'medium', 'hot', 'very_hot') DEFAULT 'medium',
    stock_quantity INT DEFAULT 0,
    min_order_quantity INT DEFAULT 1,
    max_order_quantity INT DEFAULT 10,
    weight DECIMAL(8,2) NULL COMMENT 'in grams',
    dimensions VARCHAR(100) NULL,
    sort_order INT DEFAULT 0,
    views_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    rating_average DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_available (is_available),
    INDEX idx_featured (is_featured),
    INDEX idx_price (price),
    INDEX idx_slug (slug),
    INDEX idx_sku (sku)
);
```

### **6. Orders Table**
```sql
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    tip_amount DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partially_refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'online', 'wallet') DEFAULT 'cash',
    payment_reference VARCHAR(255) NULL,
    delivery_type ENUM('pickup', 'delivery') DEFAULT 'pickup',
    delivery_address TEXT NULL,
    delivery_phone VARCHAR(20) NULL,
    delivery_instructions TEXT NULL,
    estimated_preparation_time INT NULL COMMENT 'in minutes',
    estimated_delivery_time TIMESTAMP NULL,
    actual_delivery_time TIMESTAMP NULL,
    special_instructions TEXT NULL,
    cancelled_reason TEXT NULL,
    cancelled_by ENUM('user', 'admin', 'system') NULL,
    refund_amount DECIMAL(10,2) DEFAULT 0,
    refund_reason TEXT NULL,
    delivery_person_name VARCHAR(255) NULL,
    delivery_person_phone VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_number (order_number),
    INDEX idx_created_at (created_at)
);
```

### **7. Order Items Table**
```sql
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255) NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    special_instructions TEXT NULL,
    customizations JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
);
```

### **8. Shopping Cart Table**
```sql
CREATE TABLE cart (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    quantity INT NOT NULL,
    special_instructions TEXT NULL,
    customizations JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user (user_id)
);
```

### **9. Reviews Table**
```sql
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    order_id BIGINT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    comment TEXT NULL,
    images JSON NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    admin_response TEXT NULL,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
);
```

### **10. Notifications Table**
```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    type ENUM('order_update', 'promotion', 'system', 'review_request') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_read (is_read)
);
```

### **11. Settings Table**
```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NULL,
    description TEXT NULL,
    type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key_name),
    INDEX idx_public (is_public)
);
```

---

## 🚀 **API Documentation**

### **Base URL**
```
Development: http://localhost:8000/api
Production: https://your-domain.com/api
```

### **Authentication**
All protected API endpoints require a Bearer token in the header:
```
Authorization: Bearer {sanctum-token}
```

---

## 🔐 **Authentication System**

### **1. User Authentication (API Routes - Sanctum)**

#### **User Registration**
```http
POST /api/auth/register
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Registration successful. Please verify your email.",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "status": "active",
            "created_at": "2024-01-20T10:00:00Z"
        }
    }
}
```

#### **User Login**
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "status": "active"
        },
        "token": "1|sanctum-token-here",
        "expires_at": "2024-01-21T10:25:00Z"
    }
}
```

#### **User Logout**
```http
POST /api/auth/logout
Authorization: Bearer {sanctum-token}
```

### **2. Admin Authentication (Web Routes - Session)**

#### **Admin Login Form**
```http
GET /admin/login
```

#### **Admin Login**
```http
POST /admin/login
Content-Type: application/x-www-form-urlencoded

email=admin@example.com&password=admin123&_token=csrf_token
```

#### **Admin Dashboard**
```http
GET /admin/dashboard
```

#### **Admin Logout**
```http
POST /admin/logout
```

---

## 📱 **Route Structure**

### **API Routes (api.php)**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('send-otp', [AuthController::class, 'sendOTP']);
    Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('reset-password/confirm', [AuthController::class, 'resetPasswordConfirm']);
});

// Public product routes
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::get('categories', [ProductController::class, 'categories']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    
    // Cart routes
    Route::apiResource('cart', CartController::class);
    Route::delete('cart', [CartController::class, 'clear']);
    
    // Order routes
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
    
    // User profile
    Route::get('profile', [AuthController::class, 'profile']);
    Route::put('profile', [AuthController::class, 'updateProfile']);
});
```

### **Web Routes (web.php)**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\ProductController;
use App\Http\Controllers\Web\Admin\OrderController;
use App\Http\Controllers\Web\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Admin authentication routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    });
    
    // Protected admin routes
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Product management
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        
        // Order management
        Route::resource('orders', OrderController::class);
        Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        
        // User management
        Route::resource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        
        // Settings
        Route::get('settings', [DashboardController::class, 'settings'])->name('settings');
        Route::post('settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
    });
});
```

---

## ⚙️ **Laravel Configuration**

### **Auth Configuration (config/auth.php)**
```php
<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
];
```

### **Models**

#### **User Model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'profile_image',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'reset_password_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
        'otp_expires_at' => 'datetime',
        'reset_password_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
```

#### **Admin Model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'permissions',
        'profile_image',
        'status',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
```

---

## 🛡️ **Middleware**

### **Admin Middleware**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
```

### **API Authentication Middleware**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}
```

---

## 📥 **Installation Guide**

### **1. Clone Repository**
```bash
git clone https://github.com/yourusername/suya-kabab-app.git
cd suya-kabab-app
```

### **2. Backend Setup**
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=suya_kabab
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# Install Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

# Seed database
php artisan db:seed

# Create admin user
php artisan make:admin

# Start development server
php artisan serve
```

### **3. Frontend Setup**
```bash
# Install Node.js dependencies
npm install

# Build assets
npm run build

# For development
npm run dev
```

---

## 🔒 **Security Features**

### **Multi-Auth Security**
- **Separate Authentication Tables**: Users and admins have isolated authentication
- **Guard-based Access Control**: Different guards for web and API routes
- **Session vs Token Authentication**: Sessions for admin panel, tokens for API
- **Role-based Permissions**: Admin roles with specific permissions

### **Sanctum Token Security**
- **Token Expiration**: Configurable token expiration times
- **Token Abilities**: Scope-based token permissions
- **Token Revocation**: Ability to revoke tokens on logout
- **Rate Limiting**: API rate limiting per user

### **Password Security**
- **Bcrypt Hashing**: Secure password hashing
- **Password Policies**: Minimum requirements for passwords
- **Account Lockout**: Temporary lockout after failed attempts
- **OTP Verification**: Two-factor authentication for sensitive operations

---

## 📊 **Admin Panel Features**

### **Dashboard**
- Order statistics and analytics
- Revenue tracking
- User metrics
- Product performance
- Real-time notifications

### **User Management**
- View all users
- User status management
- Account verification
- User activity logs
- Support ticket management

### **Product Management**
- Add/Edit/Delete products
- Category management
- Inventory tracking
- Image gallery management
- Bulk operations

### **Order Management**
- View all orders
- Order status updates
- Payment tracking
- Refund processing
- Delivery management

---

## 🧪 **Testing**

### **Authentication Tests**
```bash
# Test user authentication
php artisan test --filter=UserAuthTest

# Test admin authentication
php artisan test --filter=AdminAuthTest

# Test API authentication
php artisan test --filter=ApiAuthTest
```

### **API Tests**
```bash
# Test product APIs
php artisan test --filter=ProductApiTest

# Test order APIs
php artisan test --filter=OrderApiTest

# Test cart APIs
php artisan test --filter=CartApiTest
```

---

## 🚀 **Deployment**

### **Environment Configuration**
```env
# Multi-Auth Configuration
AUTH_GUARD_WEB=web
AUTH_GUARD_ADMIN=admin
AUTH_GUARD_API=sanctum

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,your-domain.com
SESSION_DRIVER=redis
```

### **Production Deployment**
```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Configure environment
cp .env.example .env

# 3. Generate keys
php artisan key:generate

# 4. Run migrations
php artisan migrate --force

# 5. Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## 🛠️ **Troubleshooting**

### **Authentication Issues**

#### **Sanctum Token Problems**
```bash
# Clear config cache
php artisan config:clear

# Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Check Sanctum middleware
php artisan route:list --name=sanctum
```

#### **Admin Login Issues**
```bash
# Check admin guard configuration
php artisan tinker
Auth::guard('admin')->attempt(['email' => 'admin@example.com', 'password' => 'password']);
```

#### **Session Problems**
```bash
# Clear sessions
php artisan session:table
php artisan migrate

# Check session configuration
php artisan config:show session
```

---

## 📞 **Support**

### **Documentation**
- API Reference: `/docs/api`
- Admin Guide: `/docs/admin`
- Multi-Auth Guide: `/docs/auth`

### **Contact**
- Email: support@suyakabab.com
- GitHub: https://github.com/yourusername/suya-kabab-app

---

**Last Updated:** January 20, 2024  
**Version:** 1.0.0 - Multi-Auth System

