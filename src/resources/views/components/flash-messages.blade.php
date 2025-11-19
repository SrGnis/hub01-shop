{{--
    Flash Messages Component

    Displays Laravel session flash messages using MaryUI Alert components.
    Automatically checks for common flash message keys: success, error, warning, info

    Usage in controllers:
        return redirect('/')->with('success', 'Operation completed successfully!');
        return redirect('/')->with('error', 'An error occurred.');
        return redirect('/')->with('warning', 'Please be careful.');
        return redirect('/')->with('info', 'Here is some information.');

    The component is dismissible and automatically clears after being displayed.
--}}

@php
    $messages = [
        'success' => [
            'icon' => 'circle-check',
            'class' => 'alert-success',
        ],
        'error' => [
            'icon' => 'circle-alert',
            'class' => 'alert-error',
        ],
        'warning' => [
            'icon' => 'triangle-alert',
            'class' => 'alert-warning',
        ],
        'info' => [
            'icon' => 'info',
            'class' => 'alert-info',
        ],
    ];
@endphp

<div class="space-y-3">
    @foreach ($messages as $type => $config)
        @if (session()->has($type))
            <x-alert
                :title="session($type)"
                :icon="$config['icon']"
                dismissible
                {{ $attributes->class([$config['class']]) }}
            />
        @endif
    @endforeach
</div>

