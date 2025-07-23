<!-- Delete Confirmation Modal Component -->
@props([
    'title' => 'Delete Item',
    'message' => 'Are you sure you want to delete this item? This action cannot be undone.',
    'entityName' => 'item',
    'deleteRoute' => '',
    'entityId' => null,
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'entityToDelete'
])

<div x-show="{{ $showModal }}" 
     class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
     x-transition>
    <div class="bg-white w-full max-w-md mx-4 p-6 rounded-lg" @click.away="{{ $showModal }} = false">
        <div class="text-center">
            <!-- Icon -->
            <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-trash text-red-500 text-xl"></i>
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $title }}</h3>
            
            <!-- Message -->
            <p class="text-gray-600 mb-6">{{ $message }}</p>
            
            <!-- Action Buttons -->
            <div class="flex gap-4 justify-center items-start">
                <!-- Cancel Button -->
                <button @click="{{ $showModal }} = false"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-md transition-colors font-medium text-sm min-w-[90px] h-12 flex items-center justify-center">
                    Cancel
                </button>
                
                <!-- Delete Form -->
                <form :action="'{{ $deleteRoute }}/' + {{ $entityIdVariable }}" method="POST" class="inline-block m-0 p-0">
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