<div class="min-h-screen flex flex-col lg:justify-center items-center pt-6 lg:pt-0">
    <div class="w-full lg:max-w-md mt-6 px-6 py-4 bg-zinc-800 shadow-md overflow-hidden lg:rounded-lg">
        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-white">
                Register for <span class="text-indigo-500">HUB01</span> Shop
            </h2>
        </div>

        <form wire:submit="register">
            <!-- Name -->
            <div>
                <div class="flex justify-between items-center">
                    <label for="name" class="block font-medium text-sm text-gray-300">Username</label>
                </div>
                <input
                    wire:model.live.debounce.500ms="form.name"
                    id="name"
                    class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    type="text"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <div class="mt-1 text-xs text-gray-400">
                    <p>Username can only contain letters, numbers, periods, hyphens, and underscores.</p>
                    <div class="mt-1">
                        <span class="font-medium">Examples:</span>
                        <span class="italic">{{ implode(', ', $usernameExamples) }}</span>
                    </div>
                </div>
                @error('form.name')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <div class="flex justify-between items-center">
                    <label for="email" class="block font-medium text-sm text-gray-300">Email</label>
                </div>
                <input
                    wire:model.live.debounce.500ms="form.email"
                    id="email"
                    class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    type="email"
                    name="email"
                    required
                    autocomplete="username"
                />
                @error('form.email')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div class="mt-4">
                <div class="flex justify-between items-center">
                    <label for="password" class="block font-medium text-sm text-gray-300">Password</label>
                </div>
                <input
                    wire:model.live.debounce.500ms="form.password"
                    id="password"
                    class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <div class="mt-1 text-xs text-gray-400">
                    <p>Password must be at least 8 characters long.</p>
                </div>
                @error('form.password')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <label for="password_confirmation" class="block font-medium text-sm text-gray-300">Confirm Password</label>
                <input wire:model.live.debounce.500ms="form.password_confirmation" id="password_confirmation" class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-400 hover:text-gray-300" href="{{ route('login') }}">
                    Already registered?
                </a>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Register
                </button>
            </div>
        </form>
    </div>
</div>
