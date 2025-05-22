<div class="min-h-screen flex flex-col lg:justify-center items-center pt-6 lg:pt-0">
    <div class="w-full lg:max-w-md mt-6 px-6 py-4 bg-zinc-800 shadow-md overflow-hidden lg:rounded-lg">
        <div class="mb-6 text-center">
            <h2 class="text-2xl font-bold text-white">
                Verify Your Email Address
            </h2>
        </div>

        <div class="mb-4 text-gray-300">
            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 font-medium text-sm text-green-400">
                    A new verification link has been sent to the email address you provided during registration.
                </div>
            @endif

            <p class="mb-4">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
            </p>
        </div>

        <div class="flex items-center justify-between mt-4">
            <form wire:submit="sendVerificationEmail">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Resend Verification Email
                </button>
            </form>

            <form wire:submit="logout">
                <button type="submit" class="underline text-sm text-gray-400 hover:text-gray-300">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</div>
