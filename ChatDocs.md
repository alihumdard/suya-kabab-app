# Chat System Implementation Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [Database Design](#database-design)
3. [Models & Relationships](#models--relationships)
4. [API Endpoints](#api-endpoints)
5. [Real-time Implementation](#real-time-implementation)
6. [Controllers & Methods](#controllers--methods)
7. [Authentication & Security](#authentication--security)
8. [Message Types & Features](#message-types--features)
9. [Frontend Integration](#frontend-integration)
10. [Implementation Steps](#implementation-steps)

---

## 🎯 Overview

The chat system will enable real-time communication between:
- **Customers** ↔ **Admin/Support**
- **Customers** ↔ **Delivery Personnel**
- **Admin** ↔ **Delivery Personnel**

### Key Features:
- Real-time messaging
- Message history
- Online/offline status
- Typing indicators
- File/image sharing
- Message read receipts
- Order-related chat context

---

## 🗄️ Database Design

### 1. **Conversations Table**
```sql
CREATE TABLE conversations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('customer_support', 'customer_delivery', 'admin_delivery') NOT NULL,
    participants JSON, -- Array of user IDs
    order_id BIGINT NULL, -- Reference to orders table
    status ENUM('active', 'closed', 'archived') DEFAULT 'active',
    last_message_id BIGINT NULL,
    last_message_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (type),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_last_message_at (last_message_at),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
```

### 2. **Conversation Participants Table**
```sql
CREATE TABLE conversation_participants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL,
    user_id BIGINT NULL, -- For customers
    admin_id BIGINT NULL, -- For admin
    user_type ENUM('customer', 'admin', 'delivery') NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    last_read_at TIMESTAMP NULL,
    
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_user_type (user_type),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);
```

### 3. **Messages Table**
```sql
CREATE TABLE messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL,
    sender_id BIGINT NOT NULL,
    sender_type ENUM('customer', 'admin', 'delivery') NOT NULL,
    message_type ENUM('text', 'image', 'file', 'system', 'order_update') DEFAULT 'text',
    content TEXT,
    file_url VARCHAR(500) NULL,
    file_name VARCHAR(255) NULL,
    file_size INT NULL,
    metadata JSON NULL, -- For system messages, order updates, etc.
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_sender_type (sender_type),
    INDEX idx_message_type (message_type),
    INDEX idx_created_at (created_at),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
);
```

### 4. **Message Read Receipts Table**
```sql
CREATE TABLE message_read_receipts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    message_id BIGINT NOT NULL,
    user_id BIGINT NULL,
    admin_id BIGINT NULL,
    user_type ENUM('customer', 'admin', 'delivery') NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_message_id (message_id),
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    UNIQUE KEY unique_message_user (message_id, user_id, admin_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);
```

### 5. **User Presence Table**
```sql
CREATE TABLE user_presence (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NULL,
    admin_id BIGINT NULL,
    user_type ENUM('customer', 'admin', 'delivery') NOT NULL,
    status ENUM('online', 'away', 'offline') DEFAULT 'offline',
    last_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_typing BOOLEAN DEFAULT FALSE,
    typing_in_conversation_id BIGINT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_status (status),
    INDEX idx_last_seen_at (last_seen_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (typing_in_conversation_id) REFERENCES conversations(id) ON DELETE SET NULL
);
```

---

## 📊 Models & Relationships

### 1. **Conversation Model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = [
        'type', 'participants', 'order_id', 'status', 
        'last_message_id', 'last_message_at'
    ];

    protected $casts = [
        'participants' => 'array',
        'last_message_at' => 'datetime'
    ];

    // Relationships
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id');
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'conversation_participants', 'conversation_id', 'admin_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, $userId, $userType = 'customer')
    {
        return $query->whereHas('participants', function ($q) use ($userId, $userType) {
            if ($userType === 'customer') {
                $q->where('user_id', $userId);
            } else {
                $q->where('admin_id', $userId);
            }
        });
    }

    // Helper Methods
    public function addParticipant($userId, $userType)
    {
        return $this->participants()->create([
            'user_id' => $userType === 'customer' ? $userId : null,
            'admin_id' => $userType === 'admin' ? $userId : null,
            'user_type' => $userType
        ]);
    }

    public function updateLastMessage($messageId)
    {
        $this->update([
            'last_message_id' => $messageId,
            'last_message_at' => now()
        ]);
    }
}
```

### 2. **Message Model**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id', 'sender_id', 'sender_type', 
        'message_type', 'content', 'file_url', 'file_name', 
        'file_size', 'metadata', 'is_read', 'read_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        if ($this->sender_type === 'customer') {
            return $this->belongsTo(User::class, 'sender_id');
        } else {
            return $this->belongsTo(Admin::class, 'sender_id');
        }
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    // Helper Methods
    public function markAsRead($userId, $userType)
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
        
        return $this->readReceipts()->create([
            'user_id' => $userType === 'customer' ? $userId : null,
            'admin_id' => $userType === 'admin' ? $userId : null,
            'user_type' => $userType
        ]);
    }

    public function isReadBy($userId, $userType)
    {
        return $this->readReceipts()
            ->where('user_type', $userType)
            ->where($userType === 'customer' ? 'user_id' : 'admin_id', $userId)
            ->exists();
    }
}
```

---

## 🔌 API Endpoints

### **Authentication Required for All Endpoints**

### 1. **Conversation Management**
```php
// Get user's conversations
GET /api/conversations
Response: {
    "success": true,
    "data": {
        "conversations": [
            {
                "id": 1,
                "type": "customer_support",
                "order_id": 123,
                "status": "active",
                "last_message": {...},
                "unread_count": 5,
                "participants": [...]
            }
        ]
    }
}

// Create new conversation
POST /api/conversations
Body: {
    "type": "customer_support",
    "order_id": 123,
    "message": "Hello, I need help with my order"
}

// Get specific conversation
GET /api/conversations/{id}
Response: {
    "success": true,
    "data": {
        "conversation": {...},
        "messages": [...],
        "participants": [...]
    }
}

// Close conversation
PUT /api/conversations/{id}/close
```

### 2. **Message Management**
```php
// Send message
POST /api/conversations/{id}/messages
Body: {
    "content": "Hello, how can I help you?",
    "message_type": "text"
}

// Upload file/image
POST /api/conversations/{id}/messages/upload
Body: FormData {
    "file": File,
    "message_type": "image"
}

// Get messages with pagination
GET /api/conversations/{id}/messages?page=1&limit=50

// Mark messages as read
PUT /api/conversations/{id}/messages/read
Body: {
    "message_ids": [1, 2, 3]
}
```

### 3. **Presence & Typing**
```php
// Update user presence
PUT /api/presence
Body: {
    "status": "online"
}

// Set typing indicator
POST /api/conversations/{id}/typing
Body: {
    "is_typing": true
}

// Get online users
GET /api/presence/online
```

### 4. **Admin Endpoints**
```php
// Get all conversations (Admin)
GET /api/admin/conversations

// Assign conversation to admin
PUT /api/admin/conversations/{id}/assign
Body: {
    "admin_id": 1
}

// Get conversation statistics
GET /api/admin/conversations/stats
```

---

## ⚡ Real-time Implementation

### **🏆 Laravel Reverb (Recommended - Official Laravel Solution)**

#### **Important Note:**
⚠️ **Laravel WebSockets is deprecated** (archived Feb 2024). Laravel now officially recommends **Laravel Reverb**.

#### **Why Laravel Reverb is Better for You:**

✅ **Officially Maintained** - By the Laravel team, not a third-party package
✅ **Built on ReactPHP** - Same reliable foundation as Laravel WebSockets
✅ **Horizontal Scaling** - Better performance for large applications
✅ **Drop-in Replacement** - Works with existing Pusher/broadcasting setup
✅ **Active Development** - Regular updates and long-term support
✅ **Production Ready** - Designed for enterprise-level applications
✅ **Native Laravel Integration** - Built specifically for Laravel
✅ **Same Language** - Pure PHP, no Node.js needed
✅ **Sanctum Integration** - Works perfectly with your existing auth

#### **Installation & Setup:**
```bash
# Install Laravel Reverb (Official Laravel Package)
composer require laravel/reverb

# Install and configure Reverb
php artisan reverb:install

# Generate broadcasting events
php artisan make:event MessageSent
php artisan make:event UserTyping
php artisan make:event UserPresenceUpdated
```

#### **Configuration:**
```php
// config/broadcasting.php (automatically configured by reverb:install)
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
],

// .env (automatically added by reverb:install)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=http
```

#### **Start Reverb Server:**
```bash
php artisan reverb:start
```

#### **Production Deployment:**
```bash
# Run Reverb as a daemon
php artisan reverb:start --host=0.0.0.0 --port=8080

# With SSL (recommended for production)
php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=yourdomain.com
```

### **🔄 Laravel WebSockets (Deprecated - For Reference Only)**

⚠️ **Note**: Laravel WebSockets is no longer maintained. Use Laravel Reverb instead.

For legacy projects still using Laravel WebSockets:
```bash
# Legacy installation (not recommended for new projects)
composer require pusher/pusher-php-server
composer require beyondcode/laravel-websockets

# Start server
php artisan websockets:serve
```

### **Option 2: Socket.IO with Node.js (Alternative)**
```javascript
// Basic Socket.IO setup
const io = require('socket.io')(server);

io.on('connection', (socket) => {
    socket.on('join_conversation', (conversationId) => {
        socket.join(`conversation_${conversationId}`);
    });

    socket.on('send_message', (data) => {
        socket.to(`conversation_${data.conversation_id}`).emit('new_message', data);
    });

    socket.on('typing', (data) => {
        socket.to(`conversation_${data.conversation_id}`).emit('user_typing', data);
    });
});
```

### **Broadcasting Events**
```php
// MessageSent Event
class MessageSent implements ShouldBroadcast
{
    public $message;
    public $conversation;

    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->conversation->id);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}

// Usage in Controller
broadcast(new MessageSent($message, $conversation));
```

---

## 🎮 Controllers & Methods

### **ChatController**
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function getConversations(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::forUser($user->id, 'customer')
            ->with(['lastMessage', 'participants'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('is_read', false)
                      ->where('sender_id', '!=', $user->id);
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ['conversations' => $conversations]
        ]);
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:customer_support,customer_delivery',
            'order_id' => 'nullable|exists:orders,id',
            'message' => 'required|string|max:1000'
        ]);

        $conversation = Conversation::create([
            'type' => $validated['type'],
            'order_id' => $validated['order_id'] ?? null,
            'participants' => [$request->user()->id]
        ]);

        $conversation->addParticipant($request->user()->id, 'customer');

        $message = $this->sendMessage($conversation, $request->user()->id, 'customer', $validated['message']);

        return response()->json([
            'success' => true,
            'data' => ['conversation' => $conversation, 'message' => $message]
        ], 201);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'message_type' => 'in:text,image,file'
        ]);

        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'customer',
            'message_type' => $validated['message_type'] ?? 'text',
            'content' => $validated['content']
        ]);

        $conversation->updateLastMessage($message->id);

        // Broadcast real-time
        broadcast(new MessageSent($message, $conversation));

        return response()->json([
            'success' => true,
            'data' => ['message' => $message]
        ], 201);
    }

    public function uploadFile(Request $request, $conversationId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'message_type' => 'required|in:image,file'
        ]);

        $conversation = Conversation::findOrFail($conversationId);
        $file = $request->file('file');
        
        $path = $file->store('chat-files', 'public');
        $url = Storage::url($path);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => 'customer',
            'message_type' => $request->message_type,
            'content' => 'File uploaded',
            'file_url' => $url,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize()
        ]);

        $conversation->updateLastMessage($message->id);

        broadcast(new MessageSent($message, $conversation));

        return response()->json([
            'success' => true,
            'data' => ['message' => $message]
        ], 201);
    }

    public function getMessages(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => ['messages' => $messages]
        ]);
    }

    public function markAsRead(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id'
        ]);

        $user = $request->user();
        
        Message::whereIn('id', $validated['message_ids'])
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read'
        ]);
    }

    public function setTyping(Request $request, $conversationId)
    {
        $validated = $request->validate([
            'is_typing' => 'required|boolean'
        ]);

        $user = $request->user();
        
        // Update user presence
        UserPresence::updateOrCreate(
            ['user_id' => $user->id, 'user_type' => 'customer'],
            [
                'is_typing' => $validated['is_typing'],
                'typing_in_conversation_id' => $validated['is_typing'] ? $conversationId : null
            ]
        );

        // Broadcast typing event
        broadcast(new UserTyping($user, $conversationId, $validated['is_typing']));

        return response()->json(['success' => true]);
    }
}
```

---

## 🔐 Authentication & Security

### **API Authentication**
```php
// All chat endpoints require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('conversations', ChatController::class);
    Route::post('conversations/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::post('conversations/{id}/upload', [ChatController::class, 'uploadFile']);
});
```

### **Authorization Middleware**
```php
class ChatParticipantMiddleware
{
    public function handle($request, Closure $next)
    {
        $conversationId = $request->route('conversation') ?? $request->route('id');
        $user = $request->user();
        
        $conversation = Conversation::findOrFail($conversationId);
        
        if (!$conversation->participants()
            ->where('user_id', $user->id)
            ->where('user_type', 'customer')
            ->exists()) {
            
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return $next($request);
    }
}
```

### **File Upload Security**
```php
// Validate file types
$request->validate([
    'file' => 'required|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240'
]);

// Sanitize file names
$fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
```

---

## 📱 Message Types & Features

### **Message Types**
1. **Text Messages** - Basic text communication
2. **Images** - Photo sharing with preview
3. **Files** - Document sharing
4. **System Messages** - Order updates, status changes
5. **Order Context** - Order-related information

### **Advanced Features**
```php
// System message for order updates
public function sendOrderUpdateMessage($orderId, $status)
{
    $conversation = Conversation::where('order_id', $orderId)->first();
    
    if ($conversation) {
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 0, // System user
            'sender_type' => 'system',
            'message_type' => 'order_update',
            'content' => "Order status updated to: {$status}",
            'metadata' => [
                'order_id' => $orderId,
                'previous_status' => $previousStatus,
                'new_status' => $status
            ]
        ]);
    }
}

// Rich message with order details
public function sendOrderContextMessage($conversation, $order)
{
    return Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => 0,
        'sender_type' => 'system',
        'message_type' => 'order_context',
        'content' => 'Order Details',
        'metadata' => [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_amount' => $order->total_amount,
            'status' => $order->status,
            'items' => $order->items->map(function($item) {
                return [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price
                ];
            })
        ]
    ]);
}
```

---

## 🖥️ Frontend Integration

### **React/Vue.js Integration**
```javascript
// Chat Component Structure
components/
├── ChatWindow.vue
├── ConversationList.vue
├── MessageBubble.vue
├── FileUpload.vue
├── TypingIndicator.vue
└── OnlineStatus.vue

// WebSocket connection
const socket = io('http://localhost:6001');

socket.on('connect', () => {
    socket.emit('join_conversation', conversationId);
});

socket.on('message.sent', (message) => {
    addMessageToConversation(message);
});

socket.on('user_typing', (data) => {
    showTypingIndicator(data.user, data.is_typing);
});
```

### **API Service**
```javascript
class ChatService {
    async getConversations() {
        const response = await api.get('/conversations');
        return response.data;
    }
    
    async sendMessage(conversationId, message) {
        const response = await api.post(`/conversations/${conversationId}/messages`, {
            content: message,
            message_type: 'text'
        });
        return response.data;
    }
    
    async uploadFile(conversationId, file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('message_type', 'file');
        
        const response = await api.post(`/conversations/${conversationId}/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return response.data;
    }
}
```

---

## 🚀 Implementation Steps

### **Phase 1: Database Setup**
1. Create migrations for all tables
2. Create models with relationships
3. Run seeders for initial data

### **Phase 2: Basic API**
1. Implement ChatController
2. Create API routes
3. Add authentication middleware
4. Test basic CRUD operations

### **Phase 3: Real-time Features**
1. Set up Laravel WebSockets or Socket.IO
2. Create broadcasting events
3. Implement typing indicators
4. Add presence system

### **Phase 4: File Handling**
1. Implement file upload
2. Add image preview
3. File type validation
4. Storage optimization

### **Phase 5: Advanced Features**
1. Message search
2. Conversation archiving
3. Admin management panel
4. Analytics and reporting

### **Phase 6: Frontend Integration**
1. Create chat components
2. Implement WebSocket client
3. Add responsive design
4. Testing and optimization

---

## 🧪 Testing Endpoints

### **Postman Collection**
```json
{
    "info": {
        "name": "Chat API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Get Conversations",
            "request": {
                "method": "GET",
                "url": "{{base_url}}/api/conversations",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{auth_token}}"
                    }
                ]
            }
        },
        {
            "name": "Send Message",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/conversations/1/messages",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{auth_token}}"
                    },
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "raw": "{\n    \"content\": \"Hello, how can I help you?\",\n    \"message_type\": \"text\"\n}"
                }
            }
        }
    ]
}
```

---

## 📊 Performance Considerations

### **Database Optimization**
1. **Indexes** - On frequently queried fields
2. **Pagination** - For message lists
3. **Caching** - Redis for active conversations
4. **Archiving** - Move old conversations to archive tables

### **Real-time Optimization**
1. **Connection Pooling** - Limit concurrent connections
2. **Message Queuing** - Use Redis for message delivery
3. **Rate Limiting** - Prevent spam messages
4. **Compression** - Gzip WebSocket messages

### **File Storage**
1. **CDN Integration** - AWS S3, Cloudflare
2. **Image Optimization** - Resize and compress
3. **Cleanup Jobs** - Remove orphaned files
4. **Backup Strategy** - Regular file backups

---

## 🔧 Configuration

### **Environment Variables**
```env
# WebSocket Configuration
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# File Upload
FILESYSTEM_DISK=public
MAX_FILE_SIZE=10240
ALLOWED_FILE_TYPES=jpeg,png,jpg,gif,pdf,doc,docx

# Chat Settings
CHAT_MESSAGE_LIMIT=1000
CHAT_FILE_RETENTION_DAYS=365
CHAT_TYPING_TIMEOUT=3000
```

### **Configuration Files**
```php
// config/chat.php
return [
    'message_limit' => env('CHAT_MESSAGE_LIMIT', 1000),
    'file_retention_days' => env('CHAT_FILE_RETENTION_DAYS', 365),
    'typing_timeout' => env('CHAT_TYPING_TIMEOUT', 3000),
    'max_file_size' => env('MAX_FILE_SIZE', 10240),
    'allowed_file_types' => explode(',', env('ALLOWED_FILE_TYPES', 'jpeg,png,jpg,gif,pdf')),
    'auto_assign_support' => env('CHAT_AUTO_ASSIGN_SUPPORT', true),
    'enable_read_receipts' => env('CHAT_ENABLE_READ_RECEIPTS', true),
];
```

---

## 📈 Monitoring & Analytics

### **Key Metrics**
1. **Response Time** - Average admin response time
2. **Resolution Rate** - Percentage of resolved conversations
3. **User Satisfaction** - Post-chat ratings
4. **Message Volume** - Daily/weekly message counts
5. **File Usage** - File sharing statistics

### **Logging**
```php
// Log important chat events
Log::channel('chat')->info('Message sent', [
    'user_id' => $user->id,
    'conversation_id' => $conversation->id,
    'message_type' => $message->message_type
]);
```

# Product Customization API Flow Documentation

## Complete Customer Journey for Product Customization

### 1. **Browse Products** 
**Endpoint:** `GET /api/products`
- Customer sees list of all available products
- Each product shows basic info (name, price, image, category)
- Customer can filter by category, search, or sort

### 2. **View Product Details with Customization Options**
**Endpoint:** `GET /api/products/{id}`
- Shows complete product information
- **NEW**: Now includes `customization_options` grouped by categories
- **NEW**: Shows `has_customization` flag to indicate if product can be customized

**Enhanced Response:**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "Special Suya Kabab",
      "price": 15.99,
      "description": "Delicious grilled kabab with special spices",
      "images": [...]
    },
    "customization_options": [
      {
        "category_name": "Toppings",
        "category_id": 1,
        "category_icon": "toppings-icon.png",
        "addons": [
          {
            "id": 1,
            "name": "Extra Onions",
            "description": "Fresh sliced onions",
            "price": 1.50,
            "image": "onions.jpg",
            "is_required": false,
            "min_quantity": 0,
            "max_quantity": 3,
            "in_stock": true,
            "available_quantity": 100
          },
          {
            "id": 2,
            "name": "Spicy Sauce",
            "description": "Our signature spicy sauce",
            "price": 2.00,
            "image": "sauce.jpg",
            "is_required": true,
            "min_quantity": 1,
            "max_quantity": 2,
            "in_stock": true,
            "available_quantity": null
          }
        ]
      },
      {
        "category_name": "Fries Section",
        "category_id": 2,
        "category_icon": "fries-icon.png",
        "addons": [
          {
            "id": 3,
            "name": "Regular Fries",
            "price": 3.99,
            "is_required": false,
            "min_quantity": 0,
            "max_quantity": 2
          },
          {
            "id": 4,
            "name": "Cheese Fries",
            "price": 5.99,
            "is_required": false,
            "min_quantity": 0,
            "max_quantity": 1
          }
        ]
      },
      {
        "category_name": "Soft Drinks",
        "category_id": 3,
        "category_icon": "drinks-icon.png",
        "addons": [
          {
            "id": 5,
            "name": "Coca Cola",
            "price": 2.50,
            "is_required": false,
            "min_quantity": 0,
            "max_quantity": 3
          }
        ]
      }
    ],
    "has_customization": true,
    "average_rating": 4.5,
    "total_reviews": 124,
    "in_stock": true
  }
}
```

### 3. **Get Detailed Customization Options** (Optional)
**Endpoint:** `GET /api/products/{id}/customizations`
- **NEW**: Dedicated endpoint for getting only customization options
- Useful for modal/popup display of customization choices
- More detailed information about each add-on category

### 4. **Add Customized Product to Cart**
**Endpoint:** `POST /api/cart`
- Customer selects main product + desired add-ons
- System validates add-on requirements and quantity limits
- Calculates total price (base product + selected add-ons)

**Request Example:**
```json
{
  "product_id": 1,
  "quantity": 2,
  "customizations": [
    {
      "id": 1,
      "quantity": 2
    },
    {
      "id": 2,
      "quantity": 1
    },
    {
      "id": 3,
      "quantity": 1
    }
  ],
  "special_instructions": "Extra spicy please"
}
```

### 5. **View Cart with Customizations**
**Endpoint:** `GET /api/cart`
- Shows all cart items with their customizations
- Displays addon costs separately
- Shows total price breakdown

### 6. **Complete Order**
**Endpoint:** `POST /api/orders`
- Customer proceeds to checkout
- System creates order with all customizations
- Customizations are saved in order items for fulfillment

---

## Key Features of the Customization System

### **🎯 Smart Categorization**
- Add-ons are grouped by categories (Toppings, Fries, Drinks)
- Each category has its own icon and description
- Categories are sorted by display order

### **🛡️ Validation Rules**
- **Required Add-ons**: Some add-ons must be selected (e.g., sauce choice)
- **Quantity Limits**: Each add-on has min/max quantity constraints
- **Stock Management**: Real-time inventory tracking for add-ons

### **💰 Dynamic Pricing**
- Add-on prices are clearly displayed
- Total price calculated automatically
- Addon costs shown separately in cart

### **🎨 Rich UI Support**
- Category icons for better visual presentation
- Add-on images for better selection experience
- Detailed descriptions for each customization option

### **📱 Mobile-Friendly**
- Optimized data structure for mobile apps
- Minimal API calls needed
- Efficient data loading

---

## Business Logic Implementation

### **Add-on Requirements**
```php
// Example validation in backend
if ($addon->pivot->is_required && $selectedQuantity < $addon->pivot->min_quantity) {
    throw new Exception("This add-on is required");
}

if ($selectedQuantity > $addon->pivot->max_quantity) {
    throw new Exception("Maximum quantity exceeded");
}
```

### **Price Calculation**
```php
// Total calculation
$basePrice = $product->price * $quantity;
$addonTotal = 0;

foreach ($customizations as $customization) {
    $addon = ProductAddon::find($customization['id']);
    $addonTotal += $addon->price * $customization['quantity'];
}

$total = $basePrice + $addonTotal;
```

### **Stock Management**
```php
// Inventory check
if ($addon->track_quantity && $addon->quantity < $requestedQuantity) {
    throw new Exception("Add-on out of stock");
}
```

---

## Example Customer Flow

1. **Customer browses products** → Sees "Special Suya Kabab - $15.99"
2. **Clicks on product** → API shows product details + customization options
3. **Customer sees:**
   - **Toppings**: Extra Onions (+$1.50), Spicy Sauce (+$2.00) *Required*
   - **Fries**: Regular Fries (+$3.99), Cheese Fries (+$5.99)
   - **Drinks**: Coca Cola (+$2.50)
4. **Customer selects:**
   - 1x Extra Onions = $1.50
   - 1x Spicy Sauce = $2.00 (required)
   - 1x Regular Fries = $3.99
   - 1x Coca Cola = $2.50
5. **Total calculation:**
   - Base Product: $15.99
   - Add-ons: $9.99
   - **Final Total: $25.98**
6. **Adds to cart** → Item saved with all customizations
7. **Proceeds to checkout** → Order created with full customization details

This flow ensures a smooth, intuitive experience for customers while maintaining robust business logic and inventory management.

This comprehensive documentation covers all aspects of implementing a robust chat system for your Suya Kabab application. Follow the phases step by step for a successful implementation! 🚀 