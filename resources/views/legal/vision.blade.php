@extends('layouts.user')

@section('title', __('Our Vision'))
@section('meta_description', __('Where YallaSpare is headed: our vision and the roadmap of what we are building next for drivers across Iraq.'))

@section('content')
    @php
        $milestones = [
            [
                'done' => true,
                'year' => __('2024 — Done'),
                'title' => __('YallaSpare launches'),
                'text' => __('The marketplace goes live: genuine parts, vehicle compatibility checks and delivery in Erbil.'),
                'tag' => __('Completed'),
            ],
            [
                'done' => true,
                'year' => __('2026 — Today'),
                'title' => __('A trusted platform'),
                'text' => __('Thousands of parts, multi-language storefront, secure checkout and a growing customer base across Iraq.'),
                'tag' => __('Completed'),
            ],
            [
                'done' => false,
                'year' => __('Next'),
                'title' => __('Mobile app for iOS & Android'),
                'text' => __('Order, track and reorder from your pocket — with notifications the moment your part ships.'),
                'tag' => __('In development'),
            ],
            [
                'done' => false,
                'year' => __('Next'),
                'title' => __('VIN-based part matching'),
                'text' => __('Type or scan your VIN and see only the parts that fit your exact car. No more guesswork.'),
                'tag' => __('Planned'),
            ],
            [
                'done' => false,
                'year' => '2027',
                'title' => __('Same-day delivery network'),
                'text' => __('Local hubs in every major city so urgent parts arrive the same day you order them.'),
                'tag' => __('Planned'),
            ],
            [
                'done' => false,
                'year' => '2027',
                'title' => __('Certified workshop network'),
                'text' => __('Buy the part, book the fitting — trusted mechanics install what you order, in one flow.'),
                'tag' => __('Planned'),
            ],
            [
                'done' => false,
                'year' => __('Beyond'),
                'title' => __('Marketplace for local sellers'),
                'text' => __('Verified suppliers across Iraq list their inventory on YallaSpare and reach the whole country.'),
                'tag' => __('Exploring'),
            ],
        ];
    @endphp

    <div class="vs-page" data-vision-page>
        <section class="vs-hero">
            <div class="vs-hero-inner">
                <p class="vs-kick">{{ __('Our Vision') }}</p>
                <h1 class="vs-title">{{ __('The road ahead is') }} <span class="vs-beam">{{ __('just getting started.') }}</span></h1>
                <p class="vs-lead">{{ __('YallaSpare set out to make finding the right auto part in Iraq as easy as ordering food. Here is where that road goes next.') }}</p>
            </div>
            <span class="vs-hero-road" aria-hidden="true"></span>
            <span class="vs-hero-dash" aria-hidden="true"></span>
            <span class="vs-scroll-hint">{{ __('Scroll') }}</span>
        </section>

        <div class="vs-roadwrap">
            <section class="vs-stats" aria-label="{{ __('Platform statistics') }}">
                @foreach ($stats as $stat)
                    <div class="vs-stat" data-vision-reveal>
                        <b><span data-vision-count="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}</b>
                        <span>{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </section>

            <section class="vs-timeline">
                <header class="vs-tl-head" data-vision-reveal>
                    <p class="vs-kick">{{ __('Roadmap') }}</p>
                    <h2>{{ __('Milestones on the road') }}</h2>
                </header>

                @foreach ($milestones as $milestone)
                    <article class="vs-ms {{ $milestone['done'] ? 'is-done' : 'is-future' }}" data-vision-reveal>
                        <div class="vs-ms-card">
                            <span class="vs-ms-year">{{ $milestone['year'] }}</span>
                            <h3>{{ $milestone['title'] }}</h3>
                            <p>{{ $milestone['text'] }}</p>
                            <span class="vs-tag">{{ $milestone['tag'] }}</span>
                        </div>
                        <span class="vs-node" aria-hidden="true"></span>
                    </article>
                @endforeach
            </section>
        </div>

        <section class="vs-cta">
            <p class="vs-kick" data-vision-reveal>{{ __('Join the journey') }}</p>
            <h2 data-vision-reveal>{{ __('Every car in Iraq deserves the right part.') }}</h2>
            <p class="vs-cta-lead" data-vision-reveal>{{ __('Have an idea, a partnership or a part we should stock? We build this platform with our customers.') }}</p>
            <a href="{{ route('legal.contact') }}" class="vs-cta-btn" data-vision-reveal>{{ __('Get in touch') }}</a>
        </section>
    </div>
@endsection
