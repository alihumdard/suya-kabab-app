@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ categories</span>
                <span class="text-gray-500">/ edit</span>
            </div>

            <!-- Back Button -->
            <a href="{{ route('admin.categories.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Categories
            </a>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Summary (Global errors) -->
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <strong class="font-bold">Please fix the following errors:</strong>
                <ul class="list-disc list-inside mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Edit Category Form -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Edit Category: {{ $category->name }}</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Category ID: #{{ $category->id }}</span>
                    <span
                        class="inline-block px-2 py-1 text-xs rounded-full {{ $category->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($category->status) }}
                    </span>
                </div>
            </div>

            <form action="{{ route('admin.categories.update', $category->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Left Form -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Category Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                            <input type="text" name="name" placeholder="Food Category"
                                value="{{ old('name', $category->name) }}"
                                class="w-full px-4 py-3 border rounded-lg @error('name') border-red-500 @enderror"
                                required />
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                            <input type="text" name="slug" placeholder="food-category"
                                value="{{ old('slug', $category->slug) }}"
                                class="w-full px-4 py-3 border rounded-lg @error('slug') border-red-500 @enderror" />
                            @error('slug')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-500 text-sm mt-1">Leave empty to auto-generate from category name</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" placeholder="Category description" rows="4"
                                class="w-full px-4 py-3 border rounded-lg @error('description') border-red-500 @enderror">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status"
                                class="w-full px-4 py-3 border rounded-lg @error('status') border-red-500 @enderror"
                                required>
                                <option value="active" {{ old('status', $category->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $category->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Image Section -->
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Image</label>

                        <!-- Current Image Preview -->
                        @if($category->images->first())
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                                <div class="border rounded-lg p-2 bg-gray-50">
                                    <img src="{{ $category->images->first()->url }}" alt="{{ $category->name }}"
                                        class="w-full h-48 object-cover rounded">
                                </div>
                            </div>
                        @endif

                        <!-- Upload New Image -->
                        <div
                            class="flex items-center justify-center h-64 border-2 border-dashed border-red-300 bg-red-50 rounded-lg p-6 text-center @error('image') border-red-500 bg-red-100 @enderror">
                            <div>
                                <i class="fas fa-upload text-red-500 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">{{ $category->images->first() ? 'Replace' : 'Upload' }}
                                    category image</p>
                                <p class="text-xs text-gray-400">JPG, PNG, GIF up to 2MB</p>
                                <p class="text-xs text-gray-400">Recommended: 800x600 pixels</p>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif"
                                    class="mt-3 text-xs @error('image') text-red-600 @enderror" />
                            </div>
                        </div>
                        @error('image')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror

                        <div class="mt-2 text-xs text-gray-500">
                            <p><strong>Image Requirements:</strong></p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Formats: JPEG, PNG, JPG, GIF</li>
                                <li>Maximum size: 2MB</li>
                                <li>Recommended: 800x600 pixels</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t">
                    <div class="flex items-center text-sm text-gray-500">
                        <i class="fas fa-clock mr-2"></i>
                        Last updated: {{ $category->updated_at->format('M d, Y \a\t g:i A') }}
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('admin.categories.index') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg text-center transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-[#E73C36] hover:bg-red-600 text-white px-6 py-3 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Update Category
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Form Enhancement -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-focus first error field when page loads with errors
        @if($errors->any())
            setTimeout(function () {
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
        @endif

        // Auto-generate slug from category name
        const nameInput = document.querySelector('input[name="name"]');
        const slugInput = document.querySelector('input[name="slug"]');

        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function () {
                if (!slugInput.dataset.userModified) {
                    const slug = this.value.toLowerCase()
                        .replace(/[^a-z0-9 -]/g, '') // Remove invalid characters
                        .replace(/\s+/g, '-') // Replace spaces with dashes
                        .replace(/-+/g, '-') // Replace multiple dashes with single dash
                        .trim('-'); // Remove leading/trailing dashes
                    slugInput.value = slug;
                }
            });

            // Mark slug as user-modified if user types in it
            slugInput.addEventListener('input', function () {
                this.dataset.userModified = true;
            });
        }

        // File input validation
        const fileInput = document.querySelector('input[name="image"]');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    // Validate file size
                    if (file.size > 2 * 1024 * 1024) { // 2MB
                        alert('File size must be less than 2MB');
                        this.value = '';
                        return;
                    }

                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPEG, PNG, or GIF)');
                        this.value = '';
                        return;
                    }
                }
            });
        }

        // Form submission loading state
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function () {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
                }
            });
        }

        // Validation feedback on blur
        const inputs = document.querySelectorAll('input[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function () {
                if (this.value.trim() === '') {
                    this.classList.add('border-red-300');
                } else {
                    this.classList.remove('border-red-300', 'border-red-500');
                    this.classList.add('border-green-300');
                }
            });

            input.addEventListener('input', function () {
                this.classList.remove('border-red-300', 'border-red-500');
            });
        });
    });
</script>