@extends('layouts.user')

@section('title', __('Privacy Policy & SSL Security'))
@section('meta_description', __('Privacy policy, SSL security, cookies, and data protection information for Yalla Spare.'))

@section('content')
    <section class="mx-auto w-full max-w-[900px] space-y-8">
        <header class="rounded-3xl border border-slate-200/70 bg-gradient-to-br from-white via-slate-50 to-slate-100/70 p-8 shadow-sm dark:border-slate-800/70 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800/60">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Legal') }}</p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl dark:text-slate-100">{{ __('Privacy Policy & SSL Security') }}</h1>
            <p class="mt-5 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('We value the privacy of our customers and are committed to protecting your personal information while providing a safe and reliable shopping experience.') }}
            </p>
        </header>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('1. Protection of Your Personal Information') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('We value the privacy of our customers and are committed to protecting your personal information. Any information you provide to us is handled with care and is not sold, rented, or shared with third parties for commercial purposes.') }}</p>
                <p>{{ __('Your personal information is collected only for the purpose of providing better service, processing orders, and improving your shopping experience.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('2. Browsing Our Website') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('You may visit our website and browse products without providing personal information.') }}</p>
                <p>{{ __('During normal browsing, we do not require personal identity details unless you choose to contact us, create an account, or place an order.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('3. Shopping and Data Protection') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('When you place an order on our website, we may ask for certain personal details such as your name, phone number, address, and email address in order to process and deliver your order.') }}</p>
                <p>{{ __('This information is stored securely and is used only for order processing, customer support, and service improvement.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('4. Payment Information') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Currently, our website does not process online payments.') }}</p>
                <p>{{ __('All orders are placed through the website and payment is completed through cash on delivery or direct agreement with the customer.') }}</p>
                <p>{{ __('Since online payment is not used, no credit card or online banking information is collected or stored on our website.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('5. SSL Security') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Our website uses SSL (Secure Sockets Layer) technology to help protect your personal information during data transmission.') }}</p>
                <p>{{ __('SSL helps encrypt the connection between your browser and our website, reducing the risk of unauthorized access to your data.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('6. Cookies') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Our website may use cookies to improve user experience, remember preferences, and help us understand how visitors use the site.') }}</p>
                <p>{{ __('Cookies are small data files stored on your device by your browser. You may disable cookies in your browser settings, but some parts of the website may not function properly if cookies are disabled.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('7. Copyright') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('All content on this website, including text, images, graphics, logos, and other materials, belongs to the website owner or the original content creator where applicable.') }}</p>
                <p>{{ __('These materials may not be copied, reproduced, distributed, or used for commercial purposes without permission.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('8. Data Security') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('We use commercially reasonable security measures to protect your personal information.') }}</p>
                <p>{{ __('However, no method of transmission over the internet or electronic storage is completely secure, and we cannot guarantee absolute security.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('9. Third-Party Links') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('Our website may contain links to external websites. Please note that we are not responsible for the privacy practices or content of third-party websites.') }}</p>
                <p>{{ __('We recommend reviewing the privacy policies of any external websites you visit.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/60">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('10. Changes to This Privacy Policy') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('We may update this Privacy Policy from time to time.') }}</p>
                <p>{{ __('Any changes will be posted on this page, and the updated version will become effective once published.') }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200/70 bg-slate-50 p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900/70">
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{{ __('11. Contact Us') }}</h2>
            <div class="mt-4 space-y-3 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                <p>{{ __('If you have any questions or suggestions regarding this Privacy Policy, please contact us through our Contact Page or customer support channels.') }}</p>
            </div>
            <a
                href="{{ route('legal.contact') }}"
                class="mt-5 inline-flex items-center rounded-xl bg-[#070740] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0d1156] focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-slate-500 dark:focus-visible:ring-offset-slate-950"
            >
                {{ __('Contact Support') }}
            </a>
        </section>
    </section>
@endsection
