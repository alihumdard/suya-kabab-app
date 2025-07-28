@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]" x-data="{ showModal: false, deleteModal: false, promotionToDelete: null }"
    x-init="@if($errors->any()) showModal = true @endif">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ promotions</span>
            </div>

            <!-- Top Right Icons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Icons -->
                <div class="flex items-center gap-2">
                    <div class="relative p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-bell text-blue-500"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">3</span>
                    </div>
                    <div class="relative p-2 bg-indigo-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-indigo-500"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-indigo-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">4</span>
                    </div>
                    <div class="relative p-2 bg-sky-100 rounded-lg">
                        <i class="fas fa-comment-alt text-sky-500"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-sky-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">2</span>
                    </div>
                    <div class="relative p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-cog text-[#E73C36]"></i>
                        <span
                            class="absolute -top-1 -right-1 bg-[#E73C36] text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">1</span>
                    </div>
                </div>

                <!-- User -->
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-800">Samantha</div>
                        <div class="text-xs text-gray-500">Admin</div>
                    </div>
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Profile"
                        class="w-8 h-8 rounded-full">
                </div>
            </div>

            <!-- Additional JavaScript for Form Enhancement -->
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Auto-focus first error field when modal opens with errors
                    @if($errors->any())
                        setTimeout(function () {
                            const firstError = document.querySelector('.border-red-500');
                            if (firstError) {
                                firstError.focus();
                                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 500);
                    @endif

                    // File input validation preview
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

                                console.log('Valid image selected:', file.name);
                            }
                        });
                    }

                    // Form submission loading state
                    const form = document.querySelector('form[action*="promotions.store"]');
                    if (form) {
                        form.addEventListener('submit', function () {
                            const submitButton = this.querySelector('button[type="submit"]');
                            if (submitButton) {
                                submitButton.disabled = true;
                                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                            }
                        });
                    }

                    // Add validation feedback on blur
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
                
                // Search function for promotions
                function performPromotionSearch() {
                    const searchInput = document.getElementById('promotionSearchInput');
                    const searchValue = searchInput.value.trim();
                    
                    document.getElementById('promotionSearchValue').value = searchValue;
                    document.getElementById('promotionSearchForm').submit();
                }
            </script>

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

        <!-- Search & Add -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <!-- Search Input -->
                <div class="flex items-center border rounded-md px-3 py-2 w-full sm:w-72 bg-white">
                    <i class="fas fa-search text-gray-400 mr-2"></i>
                    <input type="text" id="promotionSearchInput" value="{{ request('search') }}"
                        placeholder="Search for promotions" class="flex-1 outline-none text-sm"
                        onkeypress="if(event.key === 'Enter') performPromotionSearch()" />
                </div>
                
                <!-- Search Button -->
                <button onclick="performPromotionSearch()"
                    class="bg-[#E73C36] text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-600 transition w-full sm:w-auto">
                    Search
                </button>
                
                <!-- Clear Button -->
                @if(request('search'))
                    <a href="{{ route('admin.promotions.index') }}"
                        class="bg-gray-500 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-600 transition w-full sm:w-auto">
                        Clear
                    </a>
                @endif
                
                <!-- Add Promotion Button -->
                <button @click="showModal = true"
                    class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition w-full sm:w-auto">
                    + Add Promotion
                </button>
                
                <!-- Hidden Form for Search -->
                <form id="promotionSearchForm" action="{{ route('admin.promotions.index') }}" method="GET" style="display: none;">
                    <input type="hidden" name="search" id="promotionSearchValue">
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                    <input type="hidden" name="status" value="{{ request('status') }}">
                </form>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.promotions.index') }}" method="GET"
                    class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <label class="text-gray-400 text-sm whitespace-nowrap">Status:</label>
                    <select name="status" onchange="this.form.submit()"
                        class="text-sm border rounded-md px-3 py-2 w-full sm:w-auto h-10">
                        <option value="" {{ request('status') == '' ? 'selected' : '' }}>All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    </select>
                    <label class="text-gray-400 text-sm whitespace-nowrap">Sort by:</label>
                    <select name="sort_by" onchange="this.form.submit()"
                        class="text-sm border rounded-md px-3 py-2 w-full sm:w-auto h-10">
                        <option value="" {{ request('sort_by') == '' ? 'selected' : '' }}>Default</option>
                        <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="alphabetical" {{ request('sort_by') == 'alphabetical' ? 'selected' : '' }}>A-Z
                        </option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Search Results Info -->
        @if(request('search'))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Found {{ $promotions->total() }} promotion(s) for "<strong>{{ request('search') }}</strong>"
                </p>
            </div>
        @endif

        <!-- Promotions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($promotions as $promotion)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow relative group">
                    <!-- Action buttons (show on hover) -->
                    <div
                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex gap-1">
                        <a href="{{ route('admin.promotions.edit', $promotion->id) }}"
                            class="bg-blue-500 hover:bg-blue-600 text-white w-8 h-8 rounded-full text-xs transition-colors flex items-center justify-center"
                            title="Edit Promotion">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button @click="promotionToDelete = {{ $promotion->id }}; deleteModal = true"
                            class="bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full text-xs transition-colors flex items-center justify-center"
                            title="Delete Promotion">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>



                    <img src="{{ $promotion->images->first()?->url ?: asset('assets/images/banner.png') }}"
                        alt="{{ $promotion->title }}" class="w-full h-36 object-cover" />
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 text-base truncate">{{ $promotion->title }}</h3>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ Str::limit($promotion->description, 80) }}</p>
                        <div class="flex justify-between items-center mt-2">
                            <span
                                class="inline-block px-2 py-1 text-xs rounded-full {{ $promotion->status == 'active' ? 'bg-green-100 text-green-800' : ($promotion->status == 'expired' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ ucfirst($promotion->status) }}
                            </span>
                            <!-- Mobile action buttons -->
                            <div class="flex gap-1 sm:hidden">
                                <a href="{{ route('admin.promotions.edit', $promotion->id) }}"
                                    class="bg-blue-500 hover:bg-blue-600 text-white w-7 h-7 rounded text-xs flex items-center justify-center">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button @click="promotionToDelete = {{ $promotion->id }}; deleteModal = true"
                                    class="bg-red-500 hover:bg-red-600 text-white w-7 h-7 rounded text-xs flex items-center justify-center">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No promotions found.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $promotions->links() }}
        </div>

        <!-- Modal -->
        <div x-show="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto"
            style="padding-top: 300px;">
            <div class="bg-white w-full max-w-5xl mx-4 p-6 rounded-lg relative" @click.away="showModal = false">
                <!-- Close Button -->
                <button @click="showModal = false" class="absolute top-3 right-3 text-red-500 text-xl">
                    &times;
                </button>

                <h2 class="text-lg font-semibold mb-4">Add New Promotion</h2>

                <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left Form -->
                        <div class="md:col-span-2 space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium">Title *</label>
                                <input type="text" name="title" placeholder="Summer Sale" value="{{ old('title') }}"
                                    class="w-full mt-1 px-4 py-2 border rounded-md @error('title') border-red-500 @enderror"
                                    required />
                                @error('title')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium">Description *</label>
                                <textarea name="description" placeholder="Promotion description" rows="3"
                                    class="w-full mt-1 px-4 py-2 border rounded-md @error('description') border-red-500 @enderror"
                                    required>{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium">Status *</label>
                                <select name="status"
                                    class="w-full mt-1 px-4 py-2 border rounded-md @error('status') border-red-500 @enderror"
                                    required>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                    </option>
                                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired
                                    </option>
                                </select>
                                @error('status')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Right Image Upload -->
                        <div class="flex flex-col justify-start">
                            <label class="block text-sm font-medium mb-2">Promotion Image</label>
                            <div
                                class="flex items-center justify-center h-64 border-2 border-dashed border-red-300 bg-red-50 rounded-lg p-6 text-center @error('image') border-red-500 bg-red-100 @enderror">
                                <div>
                                    <i class="fas fa-upload text-red-500 text-3xl mb-2"></i>
                                    <p class="text-sm text-gray-500">Upload promotion banner</p>
                                    <p class="text-xs text-gray-400">JPG, PNG, GIF up to 2MB</p>
                                    <p class="text-xs text-gray-400">Recommended: 800x600 pixels</p>
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif"
                                        class="mt-3 text-xs @error('image') text-red-600 @enderror" />
                                </div>
                            </div>
                            @error('image')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
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
                    <div class="mt-6 flex justify-between">
                        <button type="button" @click="showModal = false"
                            class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600">Cancel</button>
                        <button type="submit"
                            class="bg-[#E73C36] text-white px-6 py-2 rounded-md hover:bg-red-600 flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Save Promotion
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        @include('pages.admin.components.delete-modal', [
            'title' => 'Delete Promotion',
            'message' => 'Are you sure you want to delete this promotion? This action cannot be undone.',
            'deleteRoute' => '/admin/promotions',
            'showModal' => 'deleteModal',
            'entityIdVariable' => 'promotionToDelete'
        ])

    </div>
</div>
</div>