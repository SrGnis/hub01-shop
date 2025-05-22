<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 lg:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('user.profile', $user) }}" class="inline-flex items-center text-indigo-400 hover:text-indigo-300">
                @svg('lucide-arrow-left', 'w-4 h-4 mr-1')
                Back to Profile
            </a>
        </div>
        
        <h1 class="text-2xl font-bold text-white mb-6">Edit Profile</h1>
        
        @if (session('message'))
            <div class="bg-green-600 text-white p-4 rounded-md mb-6">
                {{ session('message') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="bg-red-600 text-white p-4 rounded-md mb-6">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Profile Information -->
            <div class="bg-zinc-800 shadow-md overflow-hidden rounded-lg">
                <div class="border-b border-zinc-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Profile Information</h2>
                </div>
                <div class="p-6">
                    <form wire:submit="updateProfile" class="space-y-4">
                        <!-- Username -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                            <input type="text" id="name" wire:model="name" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                            <input type="email" id="email" wire:model="email" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Bio -->
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-400 mb-1">Bio</label>
                            <textarea id="bio" wire:model="bio" rows="4" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md"></textarea>
                            @error('bio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="bg-zinc-800 shadow-md overflow-hidden rounded-lg">
                <div class="border-b border-zinc-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white">Change Password</h2>
                </div>
                <div class="p-6">
                    <form wire:submit="updatePassword" class="space-y-4">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-400 mb-1">Current Password</label>
                            <input type="password" id="current_password" wire:model="current_password" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            @error('current_password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-400 mb-1">New Password</label>
                            <input type="password" id="password" wire:model="password" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                            @error('password') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1">Confirm Password</label>
                            <input type="password" id="password_confirmation" wire:model="password_confirmation" class="bg-zinc-700 border border-zinc-600 text-white block w-full px-3 py-2 rounded-md">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
