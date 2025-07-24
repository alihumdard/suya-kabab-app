@include('includes.head')
@include('includes.script')

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Layout Wrapper -->
<div class="flex min-h-screen relative" x-data="{ sidebarOpen: false }">

    <!-- Sidebar -->
    <aside id="sidebar"
        class="w-64 bg-white border-r border-gray-200 z-50 fixed md:static top-0 left-0 h-full transform -translate-x-full md:translate-x-0 transition-transform duration-300 flex flex-col overflow-y-auto"
        :class="{ 'translate-x-0': sidebarOpen }">

        <!-- Close Button (Mobile) -->
        <button @click="sidebarOpen = false"
            class="sidebar-close-button hidden md:hidden absolute top-4 right-4 z-60 bg-[#E73C36] text-white p-2 rounded-md">
            <i class="fas fa-times"></i>
        </button>

        <!-- Logo -->
        <div class="p-4 border-b">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo" class="w-28 h-auto">
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-6 space-y-2">
            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-home w-5 mr-3"></i> Dashboard
            </a>

            <a href="{{ route('admin.orders') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list w-5 mr-3"></i> Orders
            </a>

            <a href="{{ route('admin.menu') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.menu') ? 'active' : '' }}">
                <i class="fas fa-utensils w-5 mr-3"></i> Menu
            </a>

            <a href="{{ route('admin.products.index') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="fas fa-box-open w-5 mr-3"></i> Products
            </a>

            <a href="{{ route('admin.category') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.category') ? 'active' : '' }}">
                <i class="fas fa-th-large w-5 mr-3"></i> Category
            </a>

            <a href="{{ route('admin.promotions.index') }}"
                class="sidebar-link flex items-center px-10 py-3 rounded-lg text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}">
                <i class="fas fa-bullhorn w-5 mr-3"></i> Promotions
            </a>

            <!-- Components Dropdown -->
            <div x-data="{ open: {{ request()->routeIs('admin.form') || request()->routeIs('admin.table') || request()->routeIs('admin.card') ? 'true' : 'false' }} }"
                class="">
                <div class=" hover:bg-red-100 hover:text-[#E73C36] px-6">
                    <button @click="open = !open"
                        class="w-full flex items-center justify-between py-3 px-4 rounded-lg text-gray-700 transition duration-200 focus:outline-none">
                        <span class="flex items-center">
                            <i class="fas fa-layer-group w-5 mr-3"></i>
                            Components
                        </span>
                        <svg class="w-4 h-4 transform transition-transform duration-200"
                            :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>
                </div>
                <!-- Dropdown Items -->
                <div x-show="open" x-collapse class="mt-2 pl-8 space-y-1 text-sm">
                    <a href="{{ route('admin.form') }}"
                        class="sidebar-link block px-4 py-2 rounded-md text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.form') ? 'active' : '' }}">
                        Form
                    </a>
                    <a href="{{ route('admin.table') }}"
                        class="sidebar-link block px-4 py-2 rounded-md text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.table') ? 'active' : '' }}">
                        Table
                    </a>
                    <a href="{{ route('admin.card') }}"
                        class="sidebar-link block px-4 py-2 rounded-md text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.card') ? 'active' : '' }}">
                        Card
                    </a>
                    <a href="{{ route('admin.delete-modal') }}"
                        class="sidebar-link block px-4 py-2 rounded-md text-gray-700 hover:bg-red-100 hover:text-[#E73C36] transition duration-200 {{ request()->routeIs('admin.delete-modal') ? 'active' : '' }}">
                        Delete Modal
                    </a>
                </div>
            </div>
        </nav>

        <div>
            <img src="{{ asset('assets/images/banner.png') }}" style="padding: 0px 15px;" alt="">
        </div>

        <div>
            <p style="color: #969BA0; text-align: center; padding-bottom: 20px;">© 2025 All Rights Reserved</p>
        </div>
    </aside>

    <!-- Overlay (Mobile) -->
    <div class="overlay fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 z-40 hidden md:hidden"
        :class="{ 'block': sidebarOpen, 'hidden': !sidebarOpen }" @click="sidebarOpen = false"></div>

    <!-- Mobile Toggle Button -->
    <button @click="sidebarOpen = true"
        class="mobile-menu-button fixed top-4 left-4 z-60 p-2 bg-[#E73C36] text-white rounded-md md:hidden">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sidebar Styles -->
<style>
    html,
    body {
        height: 100%;
        background-color: white;
    }

    .sidebar-link.active {
        background-color: #FDF7F2;
        color: #E73C36;
        font-weight: 600;
    }

    .sidebar-link.active svg {
        stroke: #E73C36;
    }

    @media (max-width: 768px) {
        #sidebar.open {
            transform: translateX(0);
        }

        .overlay.open {
            display: block !important;
        }

        .sidebar-close-button {
            display: block !important;
        }
    }
</style>