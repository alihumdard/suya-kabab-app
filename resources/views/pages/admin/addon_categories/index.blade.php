@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]" x-data="{ showModal: false, deleteModal: false, addonCategoryToDelete: null }">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ addon categories</span>
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
                    <img src="https://randomuser.me/api/portraits/women/44.jpg"
                        class="w-10 h-10 rounded-full border border-gray-300" />
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded relative"
                x-data="{ show: true }" x-show="show">
                <button @click="show = false" class="absolute top-2 right-2 text-green-700 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded relative" x-data="{ show: true }"
                x-show="show">
                <button @click="show = false" class="absolute top-2 right-2 text-red-700 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
                <ul>
                    @foreach ($errors->all() as $error)
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
                    <input type="text" id="addonCategorySearchInput" value="{{ request('search') }}"
                        placeholder="Search addon categories..." class="flex-1 outline-none text-sm"
                        onkeypress="if(event.key === 'Enter') performAddonCategorySearch()" />
                </div>
                
                <!-- Search Button -->
                <button onclick="performAddonCategorySearch()"
                    class="bg-[#E73C36] text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-600 transition w-full sm:w-auto">
                    Search
                </button>
                
                <!-- Clear Button -->
                @if(request('search'))
                    <a href="{{ route('admin.addon_categories.index') }}"
                        class="bg-gray-500 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-600 transition w-full sm:w-auto">
                        Clear
                    </a>
                @endif
                
                <!-- Add Addon Category Button -->
                <button @click="showModal = true"
                    class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition w-full sm:w-auto">
                    + Add Addon Category
                </button>
                
                <!-- Hidden Form for Search -->
                <form id="addonCategorySearchForm" action="{{ route('admin.addon_categories.index') }}" method="GET" style="display: none;">
                    <input type="hidden" name="search" id="addonCategorySearchValue">
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                </form>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.addon_categories.index') }}" method="GET"
                    class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="search" value="{{ request('search') }}" />
                    <select name="sort_by" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-[#E73C36] focus:border-transparent">
                        <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>Latest</option>
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
                    Found {{ $addonCategories->count() }} addon category(ies) for "<strong>{{ request('search') }}</strong>"
                </p>
            </div>
        @endif

        <!-- Addon Category Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($addonCategories as $addonCategory)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow relative group">
                    <!-- Action buttons (show on hover) -->
                    <div
                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex gap-1">
                        <button @click="addonCategoryToDelete = {{ $addonCategory->id }}; deleteModal = true"
                            class="bg-red-500 text-white p-1.5 rounded-full hover:bg-red-600 transition text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                        <a href="{{ route('admin.addon_categories.edit', $addonCategory->id) }}"
                            class="bg-blue-500 text-white p-1.5 rounded-full hover:bg-blue-600 transition text-xs">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>

                    <img src="{{ $addonCategory->image ? asset($addonCategory->image) : asset('assets/images/kabab.png') }}"
                        alt="{{ $addonCategory->name }}" class="w-full h-36 object-cover" />

                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 mb-1">{{ $addonCategory->name }}</h3>
                        <p class="text-xs text-gray-500 mb-2">{{ $addonCategory->slug }}</p>
                        
                        @if($addonCategory->description)
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ Str::limit($addonCategory->description, 60) }}</p>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $addonCategory->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($addonCategory->status) }}
                            </span>
                            <span class="text-xs text-gray-500">Order: {{ $addonCategory->sort_order ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <div class="text-gray-400">
                        <i class="fas fa-tags text-6xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No addon categories found</h3>
                        <p class="text-gray-500 mb-4">Get started by creating your first addon category.</p>
                        <button @click="showModal = true"
                            class="bg-[#E73C36] text-white px-4 py-2 rounded-md hover:bg-red-600 transition">
                            + Add Addon Category
                        </button>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Add Category Modal -->
        <div x-show="showModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div @click.away="showModal = false" class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <button @click="showModal = false" class="float-right text-gray-500 hover:text-gray-700 text-xl">
                        &times;
                    </button>

                    <h2 class="text-lg font-semibold mb-4">Add New Addon Category</h2>

                    <form action="{{ route('admin.addon_categories.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Left Form -->
                            <div class="md:col-span-2 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium">Category Name</label>
                                    <input type="text" name="name" id="addonCategoryName" placeholder="Drinks, Sauces, etc."
                                        class="w-full mt-1 px-4 py-2 border rounded-md" required />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Slug</label>
                                    <input type="text" name="slug" id="addonCategorySlug" placeholder="drinks-category"
                                        class="w-full mt-1 px-4 py-2 border rounded-md bg-gray-50" readonly />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Status</label>
                                    <select name="status" class="w-full mt-1 px-4 py-2 border rounded-md" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Sort Order</label>
                                    <input type="number" name="sort_order" placeholder="0"
                                        class="w-full mt-1 px-4 py-2 border rounded-md" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Description</label>
                                    <textarea name="description" placeholder="Category description"
                                        class="w-full mt-1 px-4 py-2 border rounded-md" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Right Image Upload -->
                            <div class="flex flex-col justify-start">
                                <label class="block text-sm font-medium mb-2">Category Image</label>
                                <div
                                    class="flex items-center justify-center h-full border-2 border-dashed border-red-300 bg-red-50 rounded-lg p-6 text-center">
                                    <div>
                                        <i class="fas fa-upload text-red-500 text-3xl mb-2"></i>
                                        <p class="text-sm text-gray-500">Upload category image</p>
                                        <p class="text-xs text-gray-400">JPG, PNG up to 2MB</p>
                                        <input type="file" name="image" accept="image/*" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="mt-6 text-right">
                            <button type="submit"
                                class="bg-[#E73C36] text-white px-6 py-2 rounded-md hover:bg-red-600">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        @include('pages.admin.components.delete-modal', [
            'title' => 'Delete Addon Category',
            'message' => 'Are you sure you want to delete this addon category? This action cannot be undone.',
            'deleteRoute' => '/admin/addon_categories',
            'showModal' => 'deleteModal',
            'entityIdVariable' => 'addonCategoryToDelete'
        ])

    </div>
</div>

<script>
// Search functionality for Addon Categories
function performAddonCategorySearch() {
    const searchInput = document.getElementById('addonCategorySearchInput');
    const searchValue = document.getElementById('addonCategorySearchValue');
    const searchForm = document.getElementById('addonCategorySearchForm');
    
    searchValue.value = searchInput.value;
    searchForm.submit();
}

// Auto-generate slug from addon category name
const addonCategoryNameInput = document.getElementById('addonCategoryName');
const addonCategorySlugInput = document.getElementById('addonCategorySlug');

if (addonCategoryNameInput && addonCategorySlugInput) {
    addonCategoryNameInput.addEventListener('input', function () {
        if (!addonCategorySlugInput.dataset.userModified) {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '') // Remove invalid characters
                .replace(/\s+/g, '-') // Replace spaces with dashes
                .replace(/-+/g, '-') // Replace multiple dashes with single dash
                .trim('-'); // Remove leading/trailing dashes
            addonCategorySlugInput.value = slug;
        }
    });

    // Mark slug as user-modified if user types in it
    addonCategorySlugInput.addEventListener('input', function () {
        this.dataset.userModified = 'true';
    });
}
</script>