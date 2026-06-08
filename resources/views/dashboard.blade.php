<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $totalLists = $user->lists()->count();
        $totalEntries = \App\Models\Person::whereHas('list', fn ($query) => $query->where('user_id', $user->id))->count();
        $totalTags = $user->tags()->count();
        $recentLists = $user->lists()->withCount('people')->latest()->take(4)->get();
    @endphp

    <div class="app-shell space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="page-header !mb-0">
                <p class="eyebrow">Overview</p>
                <h1 class="page-title mt-2">Your Black Book</h1>
                <p class="page-subtitle">
                    A quick read on your lists, entries, and tagging activity.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('lists.index') }}" class="btn-primary" wire:navigate>
                    Open Lists
                </a>
                <a href="{{ route('lists.index') }}" class="btn-secondary" wire:navigate>
                    Create List
                </a>
            </div>
        </div>

        <div class="metric-grid">
            <div class="metric-card">
                <p class="app-label">Lists</p>
                <p class="metric-value">{{ $totalLists }}</p>
                <p class="card-text">Collections you are actively maintaining.</p>
            </div>

            <div class="metric-card">
                <p class="app-label">Entries</p>
                <p class="metric-value">{{ $totalEntries }}</p>
                <p class="card-text">People, accounts, or items captured so far.</p>
            </div>

            <div class="metric-card">
                <p class="app-label">Tags</p>
                <p class="metric-value">{{ $totalTags }}</p>
                <p class="card-text">Reusable labels for scanning your notes.</p>
            </div>
        </div>

        <div class="panel">
            <div class="panel-inner">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="panel-title !mb-1">Recent Lists</h2>
                        <p class="text-sm text-[var(--app-text-muted)]">Jump back into the lists you touched most recently.</p>
                    </div>
                </div>

                @if ($recentLists->isEmpty())
                    <div class="rounded-md border border-dashed border-[var(--app-border)] bg-[var(--app-surface-2)] p-6">
                        <h3 class="card-title">Start your first list</h3>
                        <p class="card-text">Create a private collection, add entries, and tag them for fast retrieval.</p>
                        <a href="{{ route('lists.index') }}" class="btn-primary mt-5" wire:navigate>
                            Create a List
                        </a>
                    </div>
                @else
                    <ul class="grid gap-3">
                        @foreach ($recentLists as $list)
                            <li>
                                <a href="{{ route('lists.show', $list) }}" class="card-link" wire:navigate>
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3 class="card-title">{{ $list->name }}</h3>
                                            <p class="card-text">{{ $list->description ?: 'No description provided.' }}</p>
                                        </div>

                                        <span class="card-meta">
                                            {{ $list->people_count }} {{ \Illuminate\Support\Str::plural('entry', $list->people_count) }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>
