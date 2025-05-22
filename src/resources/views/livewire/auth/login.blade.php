<div class="min-h-screen flex flex-col lg:justify-center items-center pt-6 lg:pt-0">
    <div class="w-full lg:max-w-md mt-6 px-6 py-4 bg-zinc-800 shadow-md overflow-hidden lg:rounded-lg">
        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-white">
                Login to <span class="text-indigo-500">HUB01</span> Shop
            </h2>
        </div>

        <form wire:submit="login">
            <!-- Email Address -->
            <div>
                <label for="email" class="block font-medium text-sm text-gray-300">Email</label>
                <input wire:model="form.email" id="email" class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" required autofocus autocomplete="username" />
                @error('form.email')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div class="mt-4">
                <label for="password" class="block font-medium text-sm text-gray-300">Password</label>
                <input wire:model="form.password" id="password" class="block mt-1 w-full rounded-md bg-zinc-700 border-zinc-600 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="password" name="password" required autocomplete="current-password" />
                @error('form.password')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input wire:model="form.remember" id="remember_me" type="checkbox" class="rounded bg-zinc-700 border-zinc-600 text-indigo-600 focus:ring-indigo-500" name="remember">
                    <span class="ml-2 text-sm text-gray-300">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="underline text-sm text-gray-400 hover:text-gray-300" href="{{ route('register') }}">
                    Need an account?
                </a>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Log in
                </button>
            </div>
        </form>
    </div>
</div>
