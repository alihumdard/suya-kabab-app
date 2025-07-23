# Admin Components

## Delete Modal Component

A reusable delete confirmation modal component that provides a consistent user experience across all admin panels.

### Usage

```blade
@include('pages.admin.components.delete-modal', [
    'title' => 'Delete Category',
    'message' => 'Are you sure you want to delete this category? This action cannot be undone.',
    'deleteRoute' => '/admin/categories',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'categoryToDelete'
])
```

### Required Alpine.js Data

Your parent component must include these Alpine.js data properties:

```html
<div x-data="{ deleteModal: false, categoryToDelete: null }">
    <!-- Your content -->
    
    <!-- Delete button that triggers modal -->
    <button @click="categoryToDelete = 123; deleteModal = true">
        Delete
    </button>
    
    <!-- Include the modal -->
    @include('pages.admin.components.delete-modal', [...])
</div>
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `title` | string | No | 'Delete Item' | Modal title text |
| `message` | string | No | 'Are you sure...' | Confirmation message |
| `deleteRoute` | string | Yes | - | Base route for deletion (without ID) |
| `showModal` | string | No | 'deleteModal' | Alpine.js variable name for modal visibility |
| `entityIdVariable` | string | No | 'entityToDelete' | Alpine.js variable holding entity ID |

### Examples

#### Categories
```blade
@include('pages.admin.components.delete-modal', [
    'title' => 'Delete Category',
    'message' => 'Are you sure you want to delete this category? This action cannot be undone.',
    'deleteRoute' => '/admin/categories',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'categoryToDelete'
])
```

#### Products
```blade
@include('pages.admin.components.delete-modal', [
    'title' => 'Delete Product',
    'message' => 'Are you sure you want to delete this product? This action cannot be undone.',
    'deleteRoute' => '/admin/products',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'productToDelete'
])
```

#### Users
```blade
@include('pages.admin.components.delete-modal', [
    'title' => 'Delete User',
    'message' => 'Are you sure you want to delete this user account? This action cannot be undone.',
    'deleteRoute' => '/admin/users',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'userToDelete'
])
```

### Features

- ✅ **Consistent Design** - Matches your admin panel styling
- ✅ **Perfect Button Alignment** - Both Cancel and Delete buttons are identical
- ✅ **Smooth Animations** - Alpine.js transitions
- ✅ **Click Outside to Close** - Better UX
- ✅ **Responsive** - Works on all devices
- ✅ **Reusable** - One component for all delete operations
- ✅ **Customizable** - All text and routes configurable 