<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => __('Welcome')])
    </head>
    <body class="marketing-body antialiased">
        <main class="marketing-shell">
            <section>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-semibold" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-md bg-[var(--app-accent)] text-[var(--app-accent-contrast)]">
                        <x-app-logo-icon class="size-7" />
                    </span>
                    <span>{{ config('app.name', 'Dossier') }}</span>
                </a>

                <div class="mt-14 max-w-2xl">
                    <p class="eyebrow">Private, searchable lists</p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight text-[var(--app-text)] md:text-5xl">
                        Keep a sharper record of the people and notes you need to remember.
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7 text-[var(--app-text-muted)]">
                        Build focused lists, add tagged entries, and keep structured notes in a quiet workspace that stays out of your way.
                    </p>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary" wire:navigate>Go to Dashboard</a>
                        <a href="{{ route('lists.index') }}" class="btn-secondary" wire:navigate>Open Lists</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary" wire:navigate>Log In</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-secondary" wire:navigate>Create Account</a>
                        @endif
                    @endauth
                </div>
            </section>

            <section class="marketing-preview">
                <div class="flex items-center justify-between border-b border-[var(--app-border)] pb-4">
                    <div>
                        <p class="eyebrow">Preview</p>
                        <h2 class="mt-2 text-xl font-semibold">Watch List</h2>
                    </div>
                    <span class="card-meta">3 entries</span>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="card-link">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="card-title">Example Entry</h3>
                                <p class="card-text">Category and notes kept together.</p>
                            </div>
                            <span class="rounded-full border border-[var(--app-teal)] px-3 py-1 text-xs text-[var(--app-teal)]">Trusted</span>
                        </div>
                    </div>

                    <div class="card-link">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="card-title">Second Contact</h3>
                                <p class="card-text">Tagged for quick review later.</p>
                            </div>
                            <span class="rounded-full border border-[var(--app-accent)] px-3 py-1 text-xs text-[var(--app-accent)]">Review</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
