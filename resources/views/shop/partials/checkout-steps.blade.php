@props(['current' => 1])

@php
    $steps = [
        1 => ['label' => __('Cart'), 'icon' => 'cart'],
        2 => ['label' => __('Delivery'), 'icon' => 'truck'],
        3 => ['label' => __('Review'), 'icon' => 'check'],
    ];
@endphp

<nav aria-label="{{ __('Checkout') }}" class="flex items-center justify-center py-1">
    @foreach ($steps as $number => $step)
        @php
            $isDone = $number < $current;
            $isActive = $number === $current;
        @endphp

        @if ($number > 1)
            <span class="mx-3 h-0.5 w-10 rounded-full transition-colors duration-500 sm:w-16 {{ $number <= $current ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}" aria-hidden="true"></span>
        @endif

        <div class="flex items-center gap-2" @if ($isActive) aria-current="step" @endif>
            <span
                @class([
                    'checkout-step-dot relative flex h-9 w-9 items-center justify-center rounded-full border-2 transition-colors duration-300',
                    'border-primary bg-primary text-white checkout-step-active' => $isActive,
                    'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300' => $isDone,
                    'border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-500' => ! $isDone && ! $isActive,
                ])
            >
                @if ($isDone)
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7" />
                    </svg>
                @elseif ($step['icon'] === 'cart')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                        <circle cx="9" cy="20" r="1.4" />
                        <circle cx="17" cy="20" r="1.4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 12h10.2L20 8H6" />
                    </svg>
                @elseif ($step['icon'] === 'truck')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v9H3zM14 10h4l3 3v3h-7" />
                        <circle cx="7" cy="18" r="1.6" />
                        <circle cx="17.5" cy="18" r="1.6" />
                    </svg>
                @else
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-7 3h8a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1Zm-2 0H5a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-1M9 14l2 2 4-4" />
                    </svg>
                @endif
            </span>
            <span
                @class([
                    'hidden text-sm font-semibold sm:block',
                    'text-primary dark:text-white' => $isActive,
                    'text-emerald-600 dark:text-emerald-300' => $isDone,
                    'text-slate-400 dark:text-slate-500' => ! $isDone && ! $isActive,
                ])
            >{{ $step['label'] }}</span>
        </div>
    @endforeach
</nav>
