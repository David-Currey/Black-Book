<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => __('Log in')])
    </head>
    <body class="auth-login-body antialiased">
        <main class="auth-login-shell">
            <section class="auth-login-panel">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-semibold" wire:navigate>
                    <span class="flex size-11 items-center justify-center rounded-md bg-[var(--app-accent)] text-[var(--app-accent-contrast)]">
                        <x-app-logo-icon class="size-7" />
                    </span>
                    <span>{{ config('app.name', 'Black Book') }}</span>
                </a>

                <div class="mt-16 max-w-xl">
                    <p class="eyebrow">Private list intelligence</p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight text-[var(--app-text)]">
                        Keep the names, notes, and context that matter close at hand.
                    </h1>
                    <p class="mt-5 text-base leading-7 text-[var(--app-text-muted)]">
                        Black Book gives you a focused workspace for building curated lists, tagging entries, and keeping markdown notes without the noise of a spreadsheet.
                    </p>
                </div>

                <div class="auth-feature-list max-w-lg">
                    <div class="auth-feature-item">
                        <span class="auth-feature-marker"></span>
                        <span>Organize lists by category, project, or community.</span>
                    </div>
                    <div class="auth-feature-item">
                        <span class="auth-feature-marker"></span>
                        <span>Attach color-coded tags for fast scanning.</span>
                    </div>
                    <div class="auth-feature-item">
                        <span class="auth-feature-marker"></span>
                        <span>Import and export JSON when you need a backup.</span>
                    </div>
                </div>
            </section>

            <section class="auth-login-card">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-md bg-[var(--app-accent)] text-[var(--app-accent-contrast)]">
                        <x-app-logo-icon class="size-6" />
                    </span>
                    <span class="text-lg font-semibold">{{ config('app.name', 'Black Book') }}</span>
                </div>

                <div>
                    <p class="eyebrow">Welcome back</p>
                    <h2 class="mt-3 text-2xl font-semibold text-[var(--app-text)]">{{ __('Sign in to your account') }}</h2>
                    <p class="mt-2 text-sm text-[var(--app-text-muted)]">
                        {{ __('Use your email and password to get back to your lists.') }}
                    </p>
                </div>

                <x-auth-session-status class="mt-6 text-center" :status="session('status')" />

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 flex flex-col gap-5">
                    @csrf

                    <flux:input
                        name="email"
                        :label="__('Email address')"
                        :value="old('email')"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="you@example.com"
                    />

                    <div class="relative">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="current-password"
                            :placeholder="__('Password')"
                            viewable
                        />

                        @if (Route::has('password.request'))
                            <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                                {{ __('Forgot?') }}
                            </flux:link>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
                    </div>

                    <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                        {{ __('Log in') }}
                    </flux:button>
                </form>

                @if (Route::has('register'))
                    <div class="mt-7 text-center text-sm text-[var(--app-text-muted)]">
                        <span>{{ __("Don't have an account?") }}</span>
                        <flux:link :href="route('register')" wire:navigate>{{ __('Create one') }}</flux:link>
                    </div>
                @endif
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
