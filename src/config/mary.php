<?php

return [
    /**
     * Default component prefix.
     *
     * Make sure to clear view cache after renaming with `php artisan view:clear`
     *
     *    prefix => ''
     *              <x-button />
     *              <x-card />
     *
     *    prefix => 'mary-'
     *               <x-mary-button />
     *               <x-mary-card />
     *
     */
    'prefix' => '',

    /**
     * Default route prefix.
     *
     * Some maryUI components make network request to its internal routes.
     *
     *      route_prefix => ''
     *          - Spotlight: '/mary/spotlight'
     *          - Editor: '/mary/upload'
     *          - ...
     *
     *      route_prefix => 'my-components'
     *          - Spotlight: '/my-components/mary/spotlight'
     *          - Editor: '/my-components/mary/upload'
     *          - ...
     */
    'route_prefix' => '',

    /**
     * Icon settings
     */
    'icons' => [
        /**
         * Icon package name
         *
         * The full Composer package name of the icon package you want to use.
         * This should be in vendor/package format.
         *
         * Examples:
         *   - 'blade-ui-kit/blade-heroicons' for Heroicons (default)
         *   - 'blade-ui-kit/blade-lucide-icons' for Lucide icons
         *   - 'blade-ui-kit/blade-phosphor-icons' for Phosphor icons
         *   - 'blade-ui-kit/blade-tabler-icons' for Tabler icons
         *   - 'custom/my-icons' for custom packages
         */
        'package' => 'mallardduck/blade-lucide-icons',

        /**
         * Icon prefix
         *
         * The prefix used by your chosen icon package.
         * This will be prepended to icon names when rendering.
         *
         * Examples:
         *   - 'heroicon' for Heroicons (default)
         *   - 'lucide' for Lucide icons
         *   - 'phosphor' for Phosphor icons
         *   - 'tabler' for Tabler icons
         */
        'prefix' => 'lucide',

        /**
         * Common icon mappings
         *
         * These are commonly used icons throughout MaryUI components.
         * You can override these when using different icon packages.
         *
         * Note: These should be the icon names WITHOUT the prefix.
         * The prefix will be automatically added based on your icon package.
         */
        'common' => [
            // Close/dismiss/clear actions
            'close' => 'x',

            // Navigation and separators
            'chevron-right' => 'chevron-right',
            'chevron-down' => 'chevron-down',
            'chevron-left' => 'chevron-left',
            'chevron-up' => 'chevron-up',
            'chevron-up-down' => 'chevrons-up-down',

            // Password visibility toggle
            'eye' => 'eye',
            'eye-slash' => 'eye-off',

            // Theme toggle
            'sun' => 'sun',
            'moon' => 'moon',

            // Actions and controls
            'x-circle' => 'circle-x',
            'scissors' => 'scissors',
            'plus-circle' => 'circle-plus',
            'backspace' => 'delete',
            'bars-3-bottom-right' => 'menu',
        ],
    ],

    /**
     * Components settings
     */
    'components' => [
        'spotlight' => [
            'class' => 'App\Support\Spotlight',
        ]
    ]
];
