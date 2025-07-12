@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]">

    <!-- Sidebar -->
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-6 md:p-10">
        <div class="max-w-full mx-auto bg-white rounded-lg shadow-md p-6 md:p-8 overflow-x-auto">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Submitted Form Data</h2>

            <table class="min-w-full divide-y divide-gray-200 text-sm text-gray-700">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">#</th>
                        <th class="px-4 py-3 text-left font-medium">Full Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Password</th>
                        <th class="px-4 py-3 text-left font-medium">Message</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
                        <th class="px-4 py-3 text-left font-medium">Gender</th>
                        <th class="px-4 py-3 text-left font-medium">Interests</th>
                        <th class="px-4 py-3 text-left font-medium">File</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <!-- Example Row -->
                    @for($i = 1; $i <= 5; $i++)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $i }}</td>
                        <td class="px-4 py-3">John Doe</td>
                        <td class="px-4 py-3">john@example.com</td>
                        <td class="px-4 py-3">••••••••</td>
                        <td class="px-4 py-3">This is a sample message.</td>
                        <td class="px-4 py-3">Admin</td>
                        <td class="px-4 py-3">Male</td>
                        <td class="px-4 py-3">Coding, Music</td>
                        <td class="px-4 py-3"><a href="#" class="text-blue-600 underline">file.pdf</a></td>
                        <td class="px-4 py-3 flex gap-2">
                            <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs">Edit</button>
                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs">Delete</button>
                        </td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
