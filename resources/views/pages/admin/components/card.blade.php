@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]">

    <!-- Sidebar -->
    @include('includes.sidebar')

    <!-- Page Content -->
    <main class="flex-1 p-6 md:p-10">
        <!-- Grid of Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @for($i = 0; $i < 8; $i++)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
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
    </main>

</div>
