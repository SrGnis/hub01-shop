{{--
    Cookie Consent Banner Component

    A simple cookie consent banner that displays in the bottom right corner.
    Uses Alpine.js for state management and stores consent in a browser cookie.

    Usage:
        <x-cookie-consent />

    The banner will only display if the user has not previously accepted cookies.
--}}

<div
    x-data="{
        show: false,
        cookieName: 'cookie_consent',
        cookieExpireDays: 365,

        init() {
            // Check if cookie consent has been given
            if (!this.getCookie(this.cookieName)) {
                // Small delay to ensure smooth animation
                setTimeout(() => this.show = true, 500);
            }
        },

        acceptCookies() {
            this.setCookie(this.cookieName, 'accepted', this.cookieExpireDays);
            this.show = false;
        },

        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },

        setCookie(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = `expires=${date.toUTCString()}`;
            document.cookie = `${name}=${value};${expires};path=/;SameSite=Lax`;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
    x-cloak
    class="fixed bottom-4 right-4 z-[999] max-w-sm w-full sm:w-96"
    role="dialog"
    aria-live="polite"
    aria-label="Cookie consent banner"
>
    <div class="alert bg-base-100 shadow-lg rounded-lg">
        <div class="flex-1">
            <div class="flex items-start gap-3">
                <x-icon name="lucide-cookie" class="w-6 h-6 flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <h3 class="font-bold text-sm mb-1">Cookie Notice</h3>
                    <p class=" leading-relaxed mb-3">
                        We use essential cookies to make this site work.
                        By continuing, you agree to their use.
                        <a
                            href="{{ route('page.show', ['pageName' => 'privacy-policy']) }}"
                            class="link link-hover underline font-medium"
                            wire:navigate
                        >
                            Learn more
                        </a>
                    </p>
                    <div class="flex gap-2">
                        <button
                            @click="acceptCookies()"
                            class="btn btn-sm btn-primary"
                            type="button"
                        >
                            <x-icon name="lucide-check" class="w-4 h-4" />
                            Accept
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

