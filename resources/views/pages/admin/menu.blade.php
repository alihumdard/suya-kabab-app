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
                <span class="text-gray-500">/ Menu</span>
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

        <!-- Search & Add -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <div class="flex items-center border rounded-md px-3 py-2 w-full sm:w-72 bg-white">
                    <i class="fas fa-search text-gray-400 mr-2"></i>
                    <input type="text" placeholder="Search for menu" class="flex-1 outline-none text-sm" />
                </div>
                <button
                    class="bg-[#E73C36] text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-red-600 transition w-full sm:w-auto">Search</button>
                <button @click="showModal = true"
                    class="bg-green-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-green-700 transition w-full sm:w-auto">
                    + Add Menu
                </button>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <label class="text-gray-400 text-sm whitespace-nowrap">Sort by:</label>
                <select class="text-sm border rounded-md px-3 py-2 w-full sm:w-auto">
                    <option>Popular</option>
                    <option>Latest</option>
                    <option>Price: Low to High</option>
                </select>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @for($i = 0; $i < 8; $i++) <div
                class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <img src="{{ asset('assets/images/kabab.png') }}" alt="Special Kebab"
                    class="w-full h-36 object-cover" />
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 text-base truncate">Special Kebab</h3>
                    <p class="text-sm text-[#E73C36]">Chicken</p>
                    <p class="text-base font-bold text-[#E73C36] mt-1">₦18.00</p>
                </div>
        </div>
        @endfor
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white w-full max-w-5xl mx-4 p-6 rounded-lg relative" @click.away="showModal = false">
            <!-- Close Button -->
            <button @click="showModal = false" class="absolute top-3 right-3 text-red-500 text-xl">
                &times;
            </button>

            <h2 class="text-lg font-semibold mb-4">Add New Menu Item</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left Form -->
                <div class="md:col-span-2 space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Item Name</label>
                        <input type="text" placeholder="Special Kebab"
                            class="w-full mt-1 px-4 py-2 border rounded-md" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Category</label>
                        <select class="w-full mt-1 px-4 py-2 border rounded-md">
                            <option>Kebab</option>
                            <option>Burger</option>
                        </select>
                    </div>

                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label class="block text-sm font-medium">Price (₦)</label>
                            <input type="text" placeholder="19.90" class="w-full mt-1 px-4 py-2 border rounded-md" />
                        </div>
                        <div class="w-1/2">
                            <label class="block text-sm font-medium">Discount Price (₦)</label>
                            <input type="text" placeholder="17.90" class="w-full mt-1 px-4 py-2 border rounded-md" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Item Tags</label>
                        <div class="flex flex-wrap gap-4">
                            <label><input type="checkbox" class="mr-2">Lamb</label>
                            <label><input type="checkbox" class="mr-2">Spicy</label>
                            <label><input type="checkbox" class="mr-2">Vegan</label>
                            <label><input type="checkbox" class="mr-2">Vegetarian</label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Promotion Badges</label>
                        <div class="flex flex-wrap gap-3">
                            <span class="bg-pink-100 text-pink-600 px-3 py-1 rounded-full text-sm">Best Seller</span>
                            <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-sm">New Added</span>
                            <span class="bg-purple-100 text-purple-600 px-3 py-1 rounded-full text-sm">Popular</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Description</label>
                        <input type="text" placeholder="Text" class="w-full mt-1 px-4 py-2 border rounded-md" />
                    </div>
                </div>

                <!-- Right Image Upload -->
                <div class="flex flex-col justify-start">
                    <label class="block text-sm font-medium mb-2">Dish Image</label>
                    <div
                        class="flex items-center justify-center h-full border-2 border-dashed border-red-300 bg-red-50 rounded-lg p-6 text-center">
                        <div>
                            <i class="fas fa-upload text-red-500 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">Upload kebab image</p>
                            <p class="text-xs text-gray-400">JPG, PNG up to 2MB</p>
                            <input type="file" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="mt-6 text-right">
                <button class="bg-[#E73C36] text-white px-6 py-2 rounded-md hover:bg-red-600">Save</button>
            </div>
        </div>
    </div>

</div>
</div>