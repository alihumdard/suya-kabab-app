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
                <span class="text-gray-500">/ promotions</span>
                <span class="text-gray-500">/ edit</span>
            </div>

            <!-- Back Button -->
            <a href="{{ route('admin.promotions.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Promotions
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

        <!-- Edit Promotion Form -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Edit Promotion: {{ $promotion->title }}</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Promotion ID: #{{ $promotion->id }}</span>
                    <span
                        class="inline-block px-2 py-1 text-xs rounded-full {{ $promotion->status == 'active' ? 'bg-green-100 text-green-800' : ($promotion->status == 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ ucfirst($promotion->status) }}
                    </span>
                </div>
            </div>

            <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Left Form -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                            <input type="text" name="title" placeholder="Summer Sale"
                                value="{{ old('title', $promotion->title) }}"
                                class="w-full px-4 py-3 border rounded-lg @error('title') border-red-500 @enderror"
                                required />
                            @error('title')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                            <textarea name="description" placeholder="Promotion description" rows="4"
                                class="w-full px-4 py-3 border rounded-lg @error('description') border-red-500 @enderror"
                                required>{{ old('description', $promotion->description) }}</textarea>
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
                                <option value="active" {{ old('status', $promotion->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $promotion->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="expired" {{ old('status', $promotion->status) == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Image Section -->
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Promotion Image</label>

                        <!-- Current Image Preview -->
                        @if($promotion->images->first())
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                                <div class="border rounded-lg p-2 bg-gray-50">
                                    <img src="{{ $promotion->images->first()->url }}" alt="{{ $promotion->title }}"
                                        class="w-full h-48 object-cover rounded">
                                </div>
                            </div>
                        @endif

                        <!-- Upload New Image -->
                        <div
                            class="flex items-center justify-center h-64 border-2 border-dashed border-red-300 bg-red-50 rounded-lg p-6 text-center @error('image') border-red-500 bg-red-100 @enderror">
                            <div>
                                <i class="fas fa-upload text-red-500 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">
                                    {{ $promotion->images->first() ? 'Replace' : 'Upload' }}
                                    promotion image
                                </p>
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
                        Last updated: {{ $promotion->updated_at->format('M d, Y \a\t g:i A') }}
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('admin.promotions.index') }}"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg text-center transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="bg-[#E73C36] hover:bg-red-600 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Promotion
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>