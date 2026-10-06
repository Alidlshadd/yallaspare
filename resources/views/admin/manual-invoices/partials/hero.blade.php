{{-- The page header every screen in this section opens with.
     $actions is an optional array of ['href', 'label', 'primary' => bool]. --}}
<style>
    .bento-stripes { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.06) 0 1px, transparent 1px 14px); }
    .bento-shadow { box-shadow: 0 1px 2px rgba(7,7,64,0.04), 0 4px 16px rgba(7,7,64,0.06); }
</style>
<div data-animate="fade-up" class="relative overflow-hidden rounded-2xl mb-5 p-6 text-white"
     style="background: linear-gradient(135deg, #04041f 0%, #070740 50%, #070740 100%);">
    <div class="absolute inset-0 bento-stripes pointer-events-none opacity-50"></div>
    <div class="absolute top-0 bottom-0 left-0 w-[3px]" style="background: linear-gradient(180deg, #ff8a3d 0%, #e65c00 100%);"></div>

    <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <div class="font-mono text-[10px] font-bold uppercase tracking-[0.28em] text-accent">{{ $eyebrow }}</div>
            <h1 class="text-2xl font-bold mt-2 leading-tight">{{ $title }}</h1>
            @if (! empty($subtitle))
                <p class="text-sm text-white/65 mt-1.5">{{ $subtitle }}</p>
            @endif
        </div>
        @if (! empty($actions))
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($actions as $action)
                    @if (! empty($action['primary']))
                        <a href="{{ $action['href'] }}"
                           class="inline-flex items-center gap-2 h-10 px-5 rounded-xl text-xs font-bold text-navy-deep shadow-md transition hover:brightness-105"
                           style="background: linear-gradient(180deg, #ff8a3d, #e65c00);">
                            {{ $action['label'] }}
                        </a>
                    @else
                        <a href="{{ $action['href'] }}"
                           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl text-xs font-bold text-white bg-white/10 border border-white/15 hover:bg-white/15 transition">
                            {{ $action['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
