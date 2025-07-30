@include('includes.head')
@include('includes.script')

<style>
[x-cloak] { display: none !important; }
</style>

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Order</span>
                <span class="text-gray-500">/ #{{ $order->order_number }}</span>
            </div>

            <div class="flex items-center gap-4 mt-4 sm:mt-0">
                <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Orders</span>
                </a>
                <button @click="window.print()" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-print"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Order Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Order Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Order Number</h3>
                            <p class="mt-1">#{{ $order->order_number }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Order Date</h3>
                            <p class="mt-1">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Status</h3>
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'dispatch' => 'bg-blue-100 text-blue-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ][$order->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <div class="relative inline-block text-left" x-data="{ open: false, status: '{{ $order->status }}', loading: false }" x-init="$watch('status', value => updateStatus(value))" @click.away="open = false">
                                <div>
                                    <button type="button" @click="open = !open" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-5 font-medium rounded-md focus:outline-none transition ease-in-out duration-150" :class="{
                                        'bg-yellow-100 text-yellow-800': status === 'pending',
                                        'bg-blue-100 text-blue-800': status === 'dispatch',
                                        'bg-red-100 text-red-800': status === 'rejected' || status === 'cancelled',
                                        'bg-green-100 text-green-800': status === 'completed',
                                        'bg-gray-100 text-gray-800': !['pending', 'dispatch', 'rejected', 'completed', 'cancelled'].includes(status)
                                    }" :disabled="loading">
                                        <span x-text="status.charAt(0).toUpperCase() + status.slice(1)"></span>
                                        <svg class="ml-2 -mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-32 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                    <div class="py-1" role="none">
                                        <button @click="status = 'pending'" class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-0">Pending</button>
                                        <button @click="status = 'dispatch'" class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-1">Dispatch</button>
                                        <button @click="status = 'rejected'" class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-2">Rejected</button>
                                        <button @click="status = 'completed'" class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-3">Completed</button>
                                        <button @click="status = 'cancelled'" class="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-red-600" role="menuitem" tabindex="-1" id="menu-item-4">Cancelled</button>
                                    </div>
                                </div>
                            </div>
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Payment Method</h3>
                            <p class="mt-1">{{ ucfirst($order->payment_method ?? 'N/A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Items</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $item->product->name ?? 'Product' }}</div>
                                                    @if($item->addons && count($item->addons) > 0)
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            @foreach($item->addons as $addon)
                                                                <span class="inline-block bg-gray-100 rounded-full px-2 py-0.5 text-xs text-gray-700 mr-1 mb-1">
                                                                    {{ $addon->name }} (+${{ number_format($addon->price, 2) }})
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    @if($item->special_instructions)
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <span class="font-medium">Note:</span> {{ $item->special_instructions }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            ${{ number_format($item->price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            ${{ number_format($item->price * $item->quantity, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        @if($order->discount_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Discount</span>
                                <span class="text-red-500">-${{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span>${{ number_format($order->shipping_amount, 2) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 mt-3">
                            <div class="flex justify-between font-semibold text-lg">
                                <span>Total</span>
                                <span>${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Customer Information</h2>
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Name</h3>
                            <p class="mt-1">{{ $order->user->name ?? 'Guest' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Email</h3>
                            <p class="mt-1">{{ $order->user->email ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Phone</h3>
                            <p class="mt-1">{{ $order->phone ?? 'N/A' }}</p>
                        </div>
                        @if($order->delivery_address)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Delivery Address</h3>
                                <p class="mt-1 whitespace-pre-line">{{ $order->delivery_address }}</p>
                            </div>
                        @endif
                        @if($order->delivery_instructions)
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Delivery Instructions</h3>
                                <p class="mt-1">{{ $order->delivery_instructions }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Order Actions -->
                <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Order Actions</h2>
                    <div class="space-y-3">
                        <div class="flex flex-col space-y-2">
                            <label for="status" class="block text-sm font-medium text-gray-700">Update Status</label>
                            <select id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-[#E73C36] focus:border-[#E73C36] sm:text-sm rounded-md">
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ $order->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <button type="button" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#E73C36] hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#E73C36]">
                            Update Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateStatus(newStatus) {
        const orderId = {{ $order->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch(`/admin/orders/${orderId}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const event = new CustomEvent('toast', { 
                    detail: { 
                        type: 'success',
                        message: data.message || 'Order status updated successfully.'
                    } 
                });
                window.dispatchEvent(event);
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const event = new CustomEvent('toast', { 
                detail: { 
                    type: 'error',
                    message: 'Failed to update order status: ' + error.message
                } 
            });
            window.dispatchEvent(event);
            // Revert to original status on error
            this.status = '{{ $order->status }}';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Status update functionality can be added here
        document.getElementById('status').addEventListener('change', function() {
            // Add AJAX call to update order status
            console.log('Status changed to:', this.value);
        });
    });
</script>
@endpush
