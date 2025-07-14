@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]" x-data="{ showModal: false }">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ category</span>
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
                <form action="{{ route('admin.category') }}" method="GET"
                    class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <div class="flex items-center border rounded-md px-3 py-2 w-full sm:w-72 bg-white">
                        <i class="fas fa-search text-gray-400 mr-2"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Search for categories" class="flex-1 outline-none text-sm"
                            onkeypress="if(event.key === 'Enter') this.form.submit()" />
                    </div>
                    <button type="submit"
                        class="bg-[#E73C36] text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-600 transition w-full sm:w-auto">Search</button>
                    @if(request('search'))
                        <a href="{{ route('admin.category') }}"
                            class="bg-gray-500 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-gray-600 transition w-full sm:w-auto text-center">
                            Clear
                        </a>
                    @endif
                </form>
                <button @click="showModal = true"
                    class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition w-full sm:w-auto">
                    + Add Category
                </button>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <form action="{{ route('admin.category') }}" method="GET"
                    class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <label class="text-gray-400 text-sm whitespace-nowrap">Sort by:</label>
                    <select name="sort_by" onchange="this.form.submit()"
                        class="text-sm border rounded-md px-3 py-2 w-full sm:w-auto">
                        <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>Latest</option>
                        <option value="popular" {{ request('sort_by') == 'popular' ? 'selected' : '' }}>Popular</option>
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
                    Found {{ $categories->total() }} category(ies) for "<strong>{{ request('search') }}</strong>"
                </p>
            </div>
        @endif

        <!-- Category Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($categories as $category)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <img src="{{ $category->image ? asset('storage/' . $category->image) : asset('assets/images/kabab.png') }}"
                        alt="{{ $category->name }}" class="w-full h-36 object-cover" />
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 text-base truncate">{{ $category->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $category->description }}</p>
                        <p class="text-sm text-[#E73C36] mt-1">{{ $category->products_count }} products</p>
                        <span
                            class="inline-block mt-2 px-2 py-1 text-xs rounded-full {{ $category->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($category->status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500">No categories found.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $categories->links() }}
        </div>

        <!-- Modal -->
        <div x-show="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 overflow-y-auto"
            style="padding-top: 100px;">
            <div class="bg-white w-full max-w-5xl mx-4 p-6 rounded-lg relative" @click.away="showModal = false">
                <!-- Close Button -->
                <button @click="showModal = false" class="absolute top-3 right-3 text-red-500 text-xl">
                    &times;
                </button>

                <h2 class="text-lg font-semibold mb-4">Add New Category</h2>

                <form action="{{ route('admin.category.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Left Form -->
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Category Name</label>
                                <input type="text" name="name" placeholder="Kebab Category"
                                    class="w-full mt-1 px-4 py-2 border rounded-md" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Slug</label>
                                <input type="text" name="slug" placeholder="kebab-category"
                                    class="w-full mt-1 px-4 py-2 border rounded-md" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <select name="status" class="w-full mt-1 px-4 py-2 border rounded-md" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            {{-- <div>
                                <label class="block text-sm font-medium">Sort Order</label>
                                <input type="number" name="sort_order" placeholder="0"
                                    class="w-full mt-1 px-4 py-2 border rounded-md" />
                            </div> --}}

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
</div>
</div>