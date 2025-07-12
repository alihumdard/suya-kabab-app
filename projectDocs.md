# 📖 Suya Kabab Application - Complete Documentation

---

## 📋 **Table of Contents**

1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Multi-Auth System](#multi-auth-system)
4. [Database Schema](#database-schema)
5. [API Documentation](#api-documentation)
6. [Authentication System](#authentication-system)
7. [Route Structure](#route-structure)
8. [Laravel Configuration](#laravel-configuration)
9. [Models](#models)
10. [Middleware](#middleware)
11. [Controllers](#controllers)
12. [Installation Guide](#installation-guide)
13. [Configuration](#configuration)
14. [Security Features](#security-features)
15. [Testing](#testing)
16. [Deployment](#deployment)
17. [Troubleshooting](#troubleshooting)

---

## 🎯 **Project Overview**

**Project Name:** Suya Kabab Application  
**Type:** Food Delivery & Restaurant Management System  
**Framework:** Laravel 11+  
**Authentication:** Custom Multi-Auth System with Laravel Sanctum  
**Database:** MySQL  
**Frontend:** Blade Templates + HTML/CSS/JavaScript  

### **Key Features:**
- **Multi-Auth System** with isolated tables for admins and users
- **Admin Dashboard** for food management and order tracking
- **User Interface** for browsing and ordering food
- **Email Verification** and OTP-based password reset
- **Payment Integration** with Stripe
- **Order Management** system
- **Role-based Access Control**
- **API-first Architecture** with Sanctum authentication

### **Figma Design Reference:**
[https://www.figma.com/design/nhQM1fdZ5jZbu3mOPLQ5TC/Suya-kabab-Application?node-id=0-1&p](https://www.figma.com/design/nhQM1fdZ5jZbu3mOPLQ5TC/Suya-kabab-Application?node-id=0-1&p=f)

---

## 🖥️ **System Requirements**

### **Server Requirements:**
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer 2.0+
- Node.js 18+ and npm
- Apache/Nginx web server

### **Laravel Extensions:**
- OpenSSL PHP Extension
- PDO PHP Extension
- Mbstring PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension
- Ctype PHP Extension
- JSON PHP Extension
- BCMath PHP Extension

---

## 🔐 **Multi-Auth System**

### **Architecture Overview**
The application implements a **custom multi-authentication system** with the following characteristics:

1. **Isolated Tables**: Separate tables for `admins` and `users`
2. **Web Routes**: Admin dashboard accessible via `web.php` routes
3. **API Routes**: User APIs accessible via `api.php` routes with Sanctum authentication
4. **Role-Based Access**: Different authentication guards for different user types

### **Authentication Guards**

```php
// config/auth.php
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
```

---

## 🗄️ **Database Schema**

### **1. Admins Table**
```sql
CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **2. Users Table**
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,
    date_of_birth DATE NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'Nigeria',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **3. OTP Verifications Table**
```sql
CREATE TABLE otp_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    type ENUM('email_verification', 'password_reset') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email_type (email, type),
    INDEX idx_otp_expires (otp, expires_at)
);
```

### **4. Categories Table**
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    image VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **5. Products Table**
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    short_description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2) NULL,
    cost_price DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE NULL,
    barcode VARCHAR(100) NULL,
    track_quantity BOOLEAN DEFAULT TRUE,
    quantity INT DEFAULT 0,
    allow_backorder BOOLEAN DEFAULT FALSE,
    weight DECIMAL(8,2) NULL,
    dimensions VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
```

### **6. Product Images Table**
```sql
CREATE TABLE product_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    image VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### **7. Orders Table**
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_number UUID UNIQUE NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'NGN',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    payment_reference VARCHAR(255) NULL,
    notes TEXT NULL,
    delivery_address TEXT NULL,
    delivery_phone VARCHAR(20) NULL,
    delivery_instructions TEXT NULL,
    estimated_delivery_time TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### **8. Order Items Table**
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### **9. Cart Table**
```sql
CREATE TABLE cart (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);
```

### **10. Reviews Table**
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) NULL,
    comment TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
```

### **11. Addon Categories Table**
```sql
CREATE TABLE addon_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    icon VARCHAR(255) NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **12. Product Addons Table**
```sql
CREATE TABLE product_addons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    addon_category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) NULL,
    sku VARCHAR(100) UNIQUE NULL,
    track_quantity BOOLEAN DEFAULT FALSE,
    quantity INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (addon_category_id) REFERENCES addon_categories(id) ON DELETE CASCADE
);
```

### **13. Product Addon Pivot Table**
```sql
CREATE TABLE product_addon_pivot (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    product_addon_id BIGINT UNSIGNED NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    min_quantity INT DEFAULT 0,
    max_quantity INT DEFAULT 10,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (product_addon_id) REFERENCES product_addons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_addon (product_id, product_addon_id)
);
```

**Note**: The `cart` and `order_items` tables have been updated to include:
- `customizations` (JSON) - stores selected add-ons with quantities
- `special_instructions` (TEXT) - stores custom notes from user
- `addon_total` (DECIMAL) - total price of selected add-ons

---

## 🍽️ **Add-On System**

### **System Overview**
The add-on system allows products to have customizable options like:
- **Toppings**: Tomato, Cucumber, Chicken Chunks, Pickles, Cabbage
- **Fries**: Regular Fries, Curley Fries, Saucy Fries
- **Drinks**: Coca Cola, Fanta, Sprite, Pepsi

### **Data Structure Example**

**Addon Categories:**
```json
[
  {"id": 1, "name": "Toppings", "slug": "toppings", "sort_order": 1},
  {"id": 2, "name": "Fries Section", "slug": "fries", "sort_order": 2},
  {"id": 3, "name": "Soft Drinks", "slug": "drinks", "sort_order": 3}
]
```

**Product Addons:**
```json
[
  {"id": 1, "category_id": 1, "name": "Tomato", "price": 20.00},
  {"id": 2, "category_id": 1, "name": "Cucumber", "price": 20.00},
  {"id": 3, "category_id": 2, "name": "Regular Fries", "price": 20.00},
  {"id": 4, "category_id": 3, "name": "Coca Cola", "price": 200.00}
]
```

**Cart Customizations:**
```json
{
  "customizations": [
    {"id": 1, "name": "Tomato", "price": 20.00, "quantity": 1},
    {"id": 3, "name": "Regular Fries", "price": 20.00, "quantity": 1},
    {"id": 4, "name": "Coca Cola", "price": 200.00, "quantity": 1}
  ],
  "special_instructions": "Extra spicy please",
  "addon_total": 240.00
}
```

---

## 🔌 **API Documentation**

### **Base URL**
```
https://your-domain.com/api/v1/
```

### **Authentication Headers**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## **Authentication Endpoints**

### **1. User Registration**
```
POST /api/v1/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response:**
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
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    }
}
```

### **2. User Login**
```
POST /api/v1/auth/login
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com"
        },
        "token": "1|abc123...token"
    }
}
```

### **3. Email Verification**
```
POST /api/v1/auth/verify-email
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

### **4. Forgot Password**
```
POST /api/v1/auth/forgot-password
```

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

### **5. Reset Password**
```
POST /api/v1/auth/reset-password
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### **6. Logout**
```
POST /api/v1/auth/logout
```

**Headers:**
```
Authorization: Bearer {token}
```

## **Product Endpoints**

### **1. Get All Products**
```
GET /api/v1/products
```

**Query Parameters:**
- `category_id` (optional)
- `featured` (optional)
- `search` (optional)
- `sort` (optional): price_asc, price_desc, name_asc, name_desc
- `page` (optional)
- `per_page` (optional)

### **2. Get Product Details**
```
GET /api/v1/products/{id}
```

### **3. Get Categories**
```
GET /api/v1/categories
```

## **Cart Endpoints**

### **1. Get Cart**
```
GET /api/v1/cart
```

### **2. Add to Cart**
```
POST /api/v1/cart
```

**Request Body:**
```json
{
    "product_id": 1,
    "quantity": 2
}
```

### **3. Update Cart Item**
```
PUT /api/v1/cart/{id}
```

**Request Body:**
```json
{
    "quantity": 3
}
```

### **4. Remove from Cart**
```
DELETE /api/v1/cart/{id}
```

## **Order Endpoints**

### **1. Get Orders**
```
GET /api/v1/orders
```

### **2. Create Order**
```
POST /api/v1/orders
```

**Request Body:**
```json
{
    "items": [
        {
            "product_id": 1,
            "quantity": 2
        }
    ],
    "delivery_address": "123 Main St, Lagos",
    "delivery_phone": "+2348012345678",
    "notes": "Please call when you arrive"
}
```

### **3. Get Order Details**
```
GET /api/v1/orders/{id}
```

### **4. Cancel Order**
```
POST /api/v1/orders/{id}/cancel
```

---

## 🔐 **Authentication System**

### **Multi-Auth Configuration**

**1. Admin Authentication (Web Routes)**
- Uses session-based authentication
- Accessed via `/admin/login`
- Protected by `auth:admin` middleware
- Redirects to admin dashboard

**2. User Authentication (API Routes)**
- Uses Laravel Sanctum for API authentication
- Token-based authentication
- Accessed via `/api/v1/auth/*`
- Protected by `auth:sanctum` middleware

### **OTP System**
- **Email Verification**: 6-digit OTP sent to user's email
- **Password Reset**: 6-digit OTP for password reset
- **Expiry**: OTPs expire after 15 minutes
- **Rate Limiting**: Maximum 5 OTP requests per hour per email

---

## 🛣️ **Route Structure**

### **Web Routes (web.php)**
```php
// Admin Authentication Routes
Route::prefix('admin')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    
    // Protected Admin Routes
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::resource('products', AdminProductController::class);
        Route::resource('categories', AdminCategoryController::class);
        Route::resource('orders', AdminOrderController::class);
        Route::resource('users', AdminUserController::class);
    });
});

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [MenuController::class, 'index'])->name('menu');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
```

### **API Routes (api.php)**
```php
Route::prefix('v1')->group(function () {
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('resend-otp', [AuthController::class, 'resendOTP']);
        
        // Protected Routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('profile', [AuthController::class, 'profile']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
        });
    });
    
    // Public API Routes
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::get('categories', [CategoryController::class, 'index']);
    
    // Protected API Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('cart', CartController::class);
        Route::apiResource('orders', OrderController::class);
        Route::post('orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::apiResource('reviews', ReviewController::class);
    });
});
```

---

## ⚙️ **Laravel Configuration**

### **Required Packages**
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "intervention/image": "^3.0",
        "stripe/stripe-php": "^10.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^11.0"
    }
}
```

### **Environment Configuration**
```env
APP_NAME="Suya Kabab"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=suya_kabab
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@suyakabab.com
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
```

---

## 🏗️ **Models**

### **User Model**
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
        'avatar',
        'date_of_birth',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'date_of_birth' => 'date',
        'password' => 'hashed',
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

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
```

### **Admin Model**
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
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']);
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

        $admin = Auth::guard('admin')->user();
        
        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')
                ->with('error', 'Your account has been deactivated.');
        }

        return $next($request);
    }
}
```

### **API Response Middleware**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        if ($request->is('api/*')) {
            $response->header('Content-Type', 'application/json');
        }
        
        return $response;
    }
}
```

---

## 🎮 **Controllers**

### **Auth Controller (API)**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Send OTP for email verification
        $this->sendOTP($user->email, 'email_verification');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'data' => [
                'user' => $user->only(['id', 'first_name', 'last_name', 'email'])
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated'
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => Carbon::now()]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'email_verified_at']),
                'token' => $token
            ]
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $otpRecord = OtpVerification::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('type', 'email_verification')
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('used_at')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Mark email as verified
        $user->update(['email_verified_at' => Carbon::now()]);

        // Mark OTP as used
        $otpRecord->update(['used_at' => Carbon::now()]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    }

    private function sendOTP($email, $type)
    {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        OtpVerification::create([
            'email' => $email,
            'otp' => $otp,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        // Send email with OTP
        Mail::send('emails.otp_verification', ['otp' => $otp], function($message) use ($email) {
            $message->to($email);
            $message->subject('Your OTP Code');
        });
    }
}
```
