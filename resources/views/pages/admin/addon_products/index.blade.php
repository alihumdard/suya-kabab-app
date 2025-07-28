@include('includes.head')
@include('includes.script')

<style>
/* Prevent delete modal flash during page load/navigation */
[x-cloak] { display: none !important; }
.delete-modal-hidden {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}
</style>

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]" x-data="{ 
        showModal: false, 
        deleteModal: false, 
        addonToDelete: null,
        init() {
            // Force modal to be hidden on initialization
            this.deleteModal = false;
            this.addonToDelete = null;
            @if($errors->any()) 
                this.showModal = true; 
            @endif
        },
        openDeleteModal(id) {
            this.addonToDelete = id;
            this.deleteModal = true;
        },
        closeDeleteModal() {
            this.deleteModal = false;
            this.addonToDelete = null;
        }
    }"
    x-init="init()">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ product addons</span>
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
                    <input type="text" id="addonSearchInput" value="{{ request('search') }}"
                        placeholder="Search product addons..." class="flex-1 outline-none text-sm"
                        onkeypress="if(event.key === 'Enter') performAddonSearch()" />
                </div>
                
                <!-- Search Button -->
                <button onclick="performAddonSearch()"
                    class="bg-[#E73C36] text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-600 transition w-full sm:w-auto">
                    Search
                </button>
                
                <!-- Clear Button -->
                @if(request('search'))
                    <a href="{{ route('admin.product_addons.index') }}"
                        class="bg-gray-500 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-600 transition w-full sm:w-auto">
                        Clear
                    </a>
                @endif
                
                <!-- Add Product Addon Button -->
                <button @click="showModal = true"
                    class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition w-full sm:w-auto">
                    + Add Product Addon
                </button>
                
                <!-- Hidden Form for Search -->
                <form id="addonSearchForm" action="{{ route('admin.product_addons.index') }}" method="GET" style="display: none;">
                    <input type="hidden" name="search" id="addonSearchValue">
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                </form>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.product_addons.index') }}" method="GET"
                    class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="search" value="{{ request('search') }}" />
                    <select name="sort_by" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-[#E73C36] focus:border-transparent">
                        <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="alphabetical" {{ request('sort_by') == 'alphabetical' ? 'selected' : '' }}>A-Z
                        </option>
                        <option value="price_low_high" {{ request('sort_by') == 'price_low_high' ? 'selected' : '' }}>
                            Price: Low to High</option>
                        <option value="price_high_low" {{ request('sort_by') == 'price_high_low' ? 'selected' : '' }}>
                            Price: High to Low</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Search Results Info -->
        @if(request('search'))
            <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Found {{ $productAddons->count() }} product addon(s) for "<strong>{{ request('search') }}</strong>"
                </p>
            </div>
        @endif

        <!-- Product Addons Table -->
        <div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-6 md:p-8 overflow-x-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Product Addons</h2>
                
                <!-- Per Page Selector -->
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Show:</span>
                    <form method="GET" action="{{ route('admin.product_addons.index') }}" class="inline">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                        <select name="per_page" onchange="this.form.submit()" 
                                class="px-3 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>
                    <span class="text-sm text-gray-600">entries</span>
                </div>
            </div>

            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-700">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">#</th>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Category</th>
                        <th class="px-4 py-3 text-left font-medium">Price</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($productAddons as $index => $addon)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-3">
                                    @if($addon->image)
                                        <img src="{{ $addon->image }}" alt="{{ $addon->name }}"
                                            class="w-10 h-10 rounded-full object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                            <i class="fas fa-utensils text-gray-400 text-sm"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $addon->name }}</div>
                                        <div class="text-gray-500 text-xs">{{ $addon->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $addon->category->name ?? 'No Category' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-green-600">${{ number_format($addon->price, 2) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $addon->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($addon->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="text-gray-600">{{ Str::limit($addon->description, 40) ?: 'No description' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2 items-start">
                                    <a href="{{ route('admin.product_addons.show', $addon) }}"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs w-16 text-center inline-flex items-center justify-center"
                                        style="height: 28px;" title="View">
                                        View
                                    </a>
                                    <a href="{{ route('admin.product_addons.edit', $addon) }}"
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs w-16 text-center inline-flex items-center justify-center"
                                        style="height: 28px;" title="Edit">
                                        Edit
                                    </a>
                                    <button @click="openDeleteModal({{ $addon->id }})"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs w-16 flex items-center justify-center"
                                        style="height: 28px;" title="Delete">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-utensils text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Product Addons Found</h3>
                                    <p class="text-gray-500 mb-4">Start by creating your first product addon.</p>
                                    <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                                        data-bs-toggle="modal" data-bs-target="#createAddonModal">
                                        Add New Addon
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <!-- Pagination -->
            @if($productAddons->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $productAddons->firstItem() }} to {{ $productAddons->lastItem() }} of {{ $productAddons->total() }} results
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        {{-- Previous Page Link --}}
                        @if ($productAddons->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">Previous</span>
                        @else
                            <a href="{{ $productAddons->previousPageUrl() }}" 
                               class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</a>
                        @endif
                        
                        {{-- Pagination Elements --}}
                        @foreach ($productAddons->getUrlRange(1, $productAddons->lastPage()) as $page => $url)
                            @if ($page == $productAddons->currentPage())
                                <span class="px-3 py-2 text-sm text-white bg-blue-500 rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" 
                                   class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        {{-- Next Page Link --}}
                        @if ($productAddons->hasMorePages())
                            <a href="{{ $productAddons->nextPageUrl() }}" 
                               class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</a>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 rounded cursor-not-allowed">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Add Product Addon Modal -->
        <div x-show="showModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div @click.away="showModal = false"
                class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <button @click="showModal = false" class="float-right text-gray-500 hover:text-gray-700 text-xl">
                        &times;
                    </button>

                    <h2 class="text-lg font-semibold mb-4">Add New Product Addon</h2>

                    <form action="{{ route('admin.product_addons.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium">Name *</label>
                                    <input type="text" name="name" id="addon-name" required
                                        class="w-full mt-1 px-4 py-2 border rounded-md" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Slug</label>
                                    <input type="text" name="slug" id="addon-slug" readonly
                                        class="w-full mt-1 px-4 py-2 border rounded-md bg-gray-50" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Category</label>
                                    <select name="addon_category_id" class="w-full mt-1 px-4 py-2 border rounded-md"
                                        required>
                                        <option value="">Select Category</option>
                                        @foreach(\App\Models\AddonCategory::active()->orderBy('name')->get() as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Price (₦)</label>
                                    <input type="number" step="0.01" min="0" name="price" placeholder="200.00"
                                        class="w-full mt-1 px-4 py-2 border rounded-md" required />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Description</label>
                                    <textarea name="description" placeholder="Addon description"
                                        class="w-full mt-1 px-4 py-2 border rounded-md" rows="3"></textarea>
                                </div>
                            </div>

                            <!-- Right Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium">Image URL</label>
                                    <input type="text" name="image" placeholder="/images/addons/sauce.jpg"
                                        class="w-full mt-1 px-4 py-2 border rounded-md" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Status</label>
                                    <select name="status" class="w-full mt-1 px-4 py-2 border rounded-md" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
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
            'title' => 'Delete Product Addon',
            'message' => 'Are you sure you want to delete this product addon? This action cannot be undone.',
            'deleteRoute' => '/admin/product_addons',
            'showModal' => 'deleteModal',
            'entityIdVariable' => 'addonToDelete'
        ])

    </div>
</div>

<script>
// Search functionality for Product Addons
function performAddonSearch() {
    const searchInput = document.getElementById('addonSearchInput');
    const searchValue = document.getElementById('addonSearchValue');
    const searchForm = document.getElementById('addonSearchForm');
    
    searchValue.value = searchInput.value;
    searchForm.submit();
}

// Ensure delete modal is hidden on page load
document.addEventListener('DOMContentLoaded', function() {
    // Force hide delete modal on page load
    const deleteModal = document.querySelector('[x-show="deleteModal"]');
    if (deleteModal) {
        deleteModal.style.display = 'none';
    }
    
    // Auto-generate slug from name
    const nameInput = document.getElementById('addon-name');
    const slugInput = document.getElementById('addon-slug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
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
        slugInput.addEventListener('input', function() {
            this.dataset.userModified = 'true';
        });
    }
});
</script>

<!-- Delete Confirmation Modal -->
<div x-show="deleteModal" 
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 delete-modal-hidden"
     :class="{ 'delete-modal-hidden': !deleteModal }"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none !important;"
     :style="deleteModal ? 'display: flex !important;' : 'display: none !important;'">
    <div class="bg-white w-full max-w-md mx-4 p-6 rounded-lg" @click.away="closeDeleteModal()">
        <div class="text-center">
            <!-- Icon -->
            <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-trash text-red-500 text-xl"></i>
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Product Addon</h3>
            
            <!-- Message -->
            <p class="text-gray-600 mb-6">Are you sure you want to delete this product addon? This action cannot be undone.</p>
            
            <!-- Action Buttons -->
            <div class="flex gap-4 justify-center items-start">
                <!-- Cancel Button -->
                <button @click="closeDeleteModal()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-md transition-colors font-medium text-sm min-w-[90px] h-12 flex items-center justify-center">
                    Cancel
                </button>
                
                <!-- Delete Form -->
                <form x-bind:action="'{{ route('admin.product_addons.index') }}/' + addonToDelete" method="POST" class="inline-block m-0 p-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-md transition-colors font-medium text-sm min-w-[90px] h-12 flex items-center justify-center">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
