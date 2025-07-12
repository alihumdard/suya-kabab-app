@include('includes.head')
@include('includes.script')

<!-- Wrapper -->
<div class="flex min-h-screen bg-[#FDF7F2]">

    <!-- Sidebar -->
    @include('includes.sidebar')

    <!-- Page Content -->
    <div class="flex-1 p-6 md:p-10">
        <div class="max-w-4xl w-full mx-auto bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Complete Form</h2>
            <form class="space-y-6">

                <!-- Full Name -->
                <div>
                    <label for="fullName" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" id="fullName"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-orange-500 focus:border-orange-500"
                        placeholder="John Doe" />
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" id="email"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-orange-500 focus:border-orange-500"
                        placeholder="john@example.com" />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-orange-500 focus:border-orange-500" />
                </div>

                <!-- Textarea -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea id="message" rows="4"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-orange-500 focus:border-orange-500"
                        placeholder="Type your message here..."></textarea>
                </div>

                <!-- Select -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Select Role</label>
                    <select id="role"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-4 py-2 focus:ring-orange-500 focus:border-orange-500">
                        <option>Admin</option>
                        <option>User</option>
                        <option>Editor</option>
                    </select>
                </div>

                <!-- Radio Buttons -->
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Gender</p>
                    <div class="flex gap-6">
                        <label class="flex items-center">
                            <input type="radio" name="gender" value="male"
                                class="text-orange-500 focus:ring-orange-500" />
                            <span class="ml-2 text-sm text-gray-700">Male</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="gender" value="female"
                                class="text-orange-500 focus:ring-orange-500" />
                            <span class="ml-2 text-sm text-gray-700">Female</span>
                        </label>
                    </div>
                </div>

                <!-- Checkboxes -->
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Interests</p>
                    <div class="flex gap-6 flex-wrap">
                        <label class="flex items-center">
                            <input type="checkbox" value="coding" class="text-orange-500 focus:ring-orange-500" />
                            <span class="ml-2 text-sm text-gray-700">Coding</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="music" class="text-orange-500 focus:ring-orange-500" />
                            <span class="ml-2 text-sm text-gray-700">Music</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="travel" class="text-orange-500 focus:ring-orange-500" />
                            <span class="ml-2 text-sm text-gray-700">Travel</span>
                        </label>
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Upload File</label>
                    <input type="file"
                        class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer focus:outline-none focus:ring-orange-500 focus:border-orange-500" />
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                        class="w-full bg-[#E73C36] text-white font-semibold py-2 px-4 rounded-md hover:bg-red-600 transition">
                        Submit
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
