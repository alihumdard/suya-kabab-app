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
        orderToDelete: null,
        init() {
            // Force modal to be hidden on initialization
            this.deleteModal = false;
            this.orderToDelete = null;
            @if($errors->any()) 
                this.showModal = true; 
            @endif
        },
        openDeleteModal(id) {
            this.orderToDelete = id;
            this.deleteModal = true;
        },
        closeDeleteModal() {
            this.deleteModal = false;
            this.orderToDelete = null;
        }
    }"
    x-init="init()">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ orders</span>
            </div>

            <!-- Top Right Icons -->
            <div class="flex items-center gap-2 sm:gap-4">
                <!-- Icons -->
                <div class="flex items-center gap-2">
                    <div class="relative p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-bell text-blue-500"></i>
                        <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">3</span>
                    </div>
                    <div class="relative p-2 bg-indigo-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-indigo-500"></i>
                        <span class="absolute -top-1 -right-1 bg-indigo-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">4</span>
                    </div>
                    <div class="relative p-2 bg-sky-100 rounded-lg">
                        <i class="fas fa-comment-alt text-sky-500"></i>
                        <span class="absolute -top-1 -right-1 bg-sky-500 text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">2</span>
                    </div>
                    <div class="relative p-2 bg-red-100 rounded-lg">
                        <i class="fas fa-cog text-[#E73C36]"></i>
                        <span class="absolute -top-1 -right-1 bg-[#E73C36] text-white text-xs w-4 h-4 rounded-full flex items-center justify-center">1</span>
                    </div>
                </div>

                <!-- User -->
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-800">Admin</div>
                        <div class="text-xs text-gray-500">Admin</div>
                    </div>
                    <img src="https://randomuser.me/api/portraits/women/44.jpg" class="w-10 h-10 rounded-full border border-gray-300" />
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r relative" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-green-500 hover:text-green-600 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r relative" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        @foreach($errors->all() as $error)
                            <p class="text-sm font-medium">{{ $error }}</p>
                        @endforeach
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-red-500 hover:text-red-600 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Search and Filter Section -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                <!-- Search Input -->
                <div class="flex items-center border rounded-md px-3 py-2 w-full sm:w-72 bg-white">
                    <i class="fas fa-search text-gray-400 mr-2"></i>
                    <input type="text" id="searchInput" placeholder="Search orders..." class="w-full focus:outline-none text-sm" onkeyup="performOrderSearch()">
                </div>

                <!-- Filter Dropdown -->
                <div class="relative w-full sm:w-40">
                    <select class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E73C36] focus:border-transparent bg-white">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Date Range Picker -->
            <div class="flex items-center gap-2 w-full lg:w-auto">
                <div class="relative w-full sm:w-40">
                    <input type="date" class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E73C36] focus:border-transparent bg-white">
                </div>
                <span class="text-gray-500">to</span>
                <div class="relative w-full sm:w-40">
                    <input type="date" class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E73C36] focus:border-transparent bg-white">
                </div>
                <button class="bg-[#E73C36] text-white px-4 py-2 rounded-md hover:bg-red-600 transition-colors text-sm">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-6 md:p-8 overflow-x-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Orders</h2>
                
                <!-- Export Button -->
                <div class="flex items-center gap-2">
                    <button class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-file-export"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Order #</th>
                            <th class="px-4 py-3 text-left font-medium">Customer</th>
                            <th class="px-4 py-3 text-left font-medium">Date</th>
                            <th class="px-4 py-3 text-left font-medium">Total</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($orders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">#{{ $order->order_number }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $order->user->name ?? 'Guest' }}</div>
                                    <div class="text-gray-500 text-xs">{{ $order->user->email ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3">${{ number_format($order->total_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'shipped' => 'bg-indigo-100 text-indigo-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                        ][$order->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClasses }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" class="p-2 text-gray-500 hover:text-[#E73C36] transition-colors">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button @click="openDeleteModal({{ $order->id }})" class="p-2 text-gray-500 hover:text-red-600 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                                        <p class="text-lg">No orders found</p>
                                        <p class="text-sm mt-2">When you receive orders, they'll appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($orders->hasPages())
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} results
                    </div>
                    <div class="flex items-center gap-2">
                        @if($orders->onFirstPage())
                            <span class="px-3 py-1 border rounded text-gray-400 cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $orders->previousPageUrl() }}" class="px-3 py-1 border rounded text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        @endif

                        @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                            @if($page == $orders->currentPage())
                                <span class="px-3 py-1 border rounded bg-[#E73C36] text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 border rounded text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($orders->hasMorePages())
                            <a href="{{ $orders->nextPageUrl() }}" class="px-3 py-1 border rounded text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="px-3 py-1 border rounded text-gray-400 cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Delete Confirmation Modal -->
        <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-transition>
            <div class="bg-white w-full max-w-md mx-4 p-6 rounded-lg" @click.away="deleteModal = false">
                <div class="text-center">
                    <!-- Icon -->
                    <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-trash text-red-500 text-xl"></i>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Order</h3>
                    
                    <!-- Message -->
                    <p class="text-gray-600 mb-6">Are you sure you want to delete this order? This action cannot be undone.</p>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-4 justify-center">
                        <!-- Cancel Button -->
                        <button @click="deleteModal = false"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-md transition-colors font-medium">
                            Cancel
                        </button>
                        
                        <!-- Delete Button with Form -->
                        <button @click="
                            if(confirm('Are you sure you want to delete this order?')) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '{{ url('admin/orders') }}/' + orderToDelete;
                                
                                const csrfToken = document.createElement('input');
                                csrfToken.type = 'hidden';
                                csrfToken.name = '_token';
                                csrfToken.value = '{{ csrf_token() }}';
                                
                                const methodInput = document.createElement('input');
                                methodInput.type = 'hidden';
                                methodInput.name = '_method';
                                methodInput.value = 'DELETE';
                                
                                form.appendChild(csrfToken);
                                form.appendChild(methodInput);
                                document.body.appendChild(form);
                                form.submit();
                            }
                        " class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-md transition-colors font-medium">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality for Orders
function performOrderSearch() {
    let input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.querySelector("table");
    tr = table.getElementsByTagName("tr");

    // Loop through all table rows, and hide those that don't match the search query
    for (i = 0; i < tr.length; i++) {
        // Skip the header row
        if (i === 0) continue;
        
        let found = false;
        // Check each cell in the row (except the last one which contains action buttons)
        for (let j = 0; j < tr[i].cells.length - 1; j++) {
            td = tr[i].cells[j];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        if (found) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

// Initialize any other JavaScript functionality here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips, datepickers, etc.
    
    // Example: Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
