@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]" x-data="{ deleteModal: false, entityToDelete: null, demoEntityType: 'Category' }">
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-10">
        <!-- Topbar -->
        <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between py-3 px-4 sm:px-6 rounded-md shadow-sm mb-6">
            <div class="text-lg sm:text-xl font-semibold">
                <span class="text-[#E73C36]">Dashboard</span>
                <span class="text-gray-500">/ components</span>
                <span class="text-gray-500">/ delete modal</span>
            </div>
        </div>

        <!-- Component Header -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Delete Modal Component</h1>
            <p class="text-gray-600 mb-4">A reusable, beautiful delete confirmation modal with perfect button alignment and smooth animations.</p>
            
            <!-- Features -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Perfect Button Alignment</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Smooth Animations</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Fully Customizable</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Mobile Responsive</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Click Outside to Close</span>
                </div>
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Consistent Design</span>
                </div>
            </div>
        </div>

        <!-- Interactive Demo -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Interactive Demo</h2>
            <p class="text-gray-600 mb-4">Try the delete modal with different entity types:</p>
            
            <!-- Demo Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="border rounded-lg p-4 text-center">
                    <h3 class="font-semibold mb-2">Category Example</h3>
                    <button @click="demoEntityType = 'Category'; entityToDelete = 123; deleteModal = true"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Category
                    </button>
                </div>
                
                <div class="border rounded-lg p-4 text-center">
                    <h3 class="font-semibold mb-2">Product Example</h3>
                    <button @click="demoEntityType = 'Product'; entityToDelete = 456; deleteModal = true"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Product
                    </button>
                </div>
                
                <div class="border rounded-lg p-4 text-center">
                    <h3 class="font-semibold mb-2">User Example</h3>
                    <button @click="demoEntityType = 'User'; entityToDelete = 789; deleteModal = true"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md transition-colors">
                        <i class="fas fa-trash mr-2"></i>
                        Delete User
                    </button>
                </div>
            </div>
        </div>

        <!-- Usage Examples -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Usage Examples</h2>
            
            <!-- Category Example -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Category Delete Modal</h3>
                <div class="bg-gray-100 rounded-lg p-4 overflow-x-auto">
                    <pre class="text-sm text-gray-800"><code>@include('pages.admin.components.delete-modal', [
    'title' => 'Delete Category',
    'message' => 'Are you sure you want to delete this category? This action cannot be undone.',
    'deleteRoute' => '/admin/categories',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'categoryToDelete'
])</code></pre>
                </div>
            </div>

            <!-- Product Example -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Product Delete Modal</h3>
                <div class="bg-gray-100 rounded-lg p-4 overflow-x-auto">
                    <pre class="text-sm text-gray-800"><code>@include('pages.admin.components.delete-modal', [
    'title' => 'Delete Product',
    'message' => 'Are you sure you want to delete this product? This action cannot be undone.',
    'deleteRoute' => '/admin/products',
    'showModal' => 'deleteModal',
    'entityIdVariable' => 'productToDelete'
])</code></pre>
                </div>
            </div>

            <!-- Alpine.js Data -->
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Required Alpine.js Data</h3>
                <div class="bg-gray-100 rounded-lg p-4 overflow-x-auto">
                    <pre class="text-sm text-gray-800"><code>&lt;div x-data="{ deleteModal: false, categoryToDelete: null }"&gt;
    &lt;!-- Delete button --&gt;
    &lt;button @click="categoryToDelete = 123; deleteModal = true"&gt;
        Delete Category
    &lt;/button&gt;
    
    &lt;!-- Include modal --&gt;
    &#64;include('pages.admin.components.delete-modal', [parameters])
&lt;/div&gt;</code></pre>
                </div>
            </div>
        </div>

        <!-- Parameters Table -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Parameters</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 px-4">Parameter</th>
                            <th class="text-left py-2 px-4">Type</th>
                            <th class="text-left py-2 px-4">Required</th>
                            <th class="text-left py-2 px-4">Default</th>
                            <th class="text-left py-2 px-4">Description</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600">
                        <tr class="border-b">
                            <td class="py-2 px-4 font-mono bg-gray-50">title</td>
                            <td class="py-2 px-4">string</td>
                            <td class="py-2 px-4">No</td>
                            <td class="py-2 px-4">'Delete Item'</td>
                            <td class="py-2 px-4">Modal title text</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-mono bg-gray-50">message</td>
                            <td class="py-2 px-4">string</td>
                            <td class="py-2 px-4">No</td>
                            <td class="py-2 px-4">'Are you sure...'</td>
                            <td class="py-2 px-4">Confirmation message</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-mono bg-gray-50">deleteRoute</td>
                            <td class="py-2 px-4">string</td>
                            <td class="py-2 px-4 text-red-600 font-semibold">Yes</td>
                            <td class="py-2 px-4">-</td>
                            <td class="py-2 px-4">Base route for deletion (without ID)</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-mono bg-gray-50">showModal</td>
                            <td class="py-2 px-4">string</td>
                            <td class="py-2 px-4">No</td>
                            <td class="py-2 px-4">'deleteModal'</td>
                            <td class="py-2 px-4">Alpine.js variable name for modal visibility</td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 px-4 font-mono bg-gray-50">entityIdVariable</td>
                            <td class="py-2 px-4">string</td>
                            <td class="py-2 px-4">No</td>
                            <td class="py-2 px-4">'entityToDelete'</td>
                            <td class="py-2 px-4">Alpine.js variable holding entity ID</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Demo Modal -->
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-transition>
        <div class="bg-white w-full max-w-md mx-4 p-6 rounded-lg" @click.away="deleteModal = false">
            <div class="text-center">
                <!-- Icon -->
                <div class="w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-trash text-red-500 text-xl"></i>
                </div>
                
                <!-- Dynamic Title -->
                <h3 class="text-lg font-semibold text-gray-800 mb-2" x-text="'Delete ' + demoEntityType"></h3>
                
                <!-- Dynamic Message -->
                <p class="text-gray-600 mb-6" x-text="'Are you sure you want to delete this ' + demoEntityType.toLowerCase() + '? This action cannot be undone.'"></p>
                
                <!-- Action Buttons -->
                <div class="flex gap-4 justify-center items-start">
                    <!-- Cancel Button -->
                    <button @click="deleteModal = false"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-md transition-colors font-medium text-sm min-w-[90px] h-12 flex items-center justify-center">
                        Cancel
                    </button>
                    
                    <!-- Delete Form (Demo) -->
                    <button @click="alert('Demo mode: ' + demoEntityType + ' deletion prevented'); deleteModal = false"
                        class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-md transition-colors font-medium text-sm min-w-[90px] h-12 flex items-center justify-center">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div> 