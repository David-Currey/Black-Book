<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $totalLists = $user->lists()->count();
        $totalEntries = \App\Models\Person::whereHas('list', fn ($query) => $query->where('user_id', $user->id))->count();
        $totalTags = $user->tags()->count();
        $recentLists = $user->lists()->withCount('people')->latest()->take(4)->get();
        $upcomingReminders = \App\Models\EntryReminder::query()
            ->with('person.list')
            ->whereNull('completed_at')
            ->whereHas('person.list', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('remind_on')
            ->take(5)
            ->get();
    @endphp

    <div class="app-shell space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="page-header !mb-0">
                <p class="eyebrow">Overview</p>
                <h1 class="page-title mt-2">Your Dossier</h1>
                <p class="page-subtitle">
                    A quick read on your lists, entries, and tagging activity.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('search.index') }}" class="btn-secondary" wire:navigate>
                    Search
                </a>
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

        <div class="panel">
            <div class="panel-inner">
                <div class="mb-5">
                    <h2 class="panel-title !mb-1">Upcoming Reminders</h2>
                    <p class="text-sm text-[var(--app-text-muted)]">Open follow-ups sorted by due date.</p>
                </div>

                @if ($upcomingReminders->isEmpty())
                    <p class="text-muted">No open reminders yet.</p>
                @else
                    <ul class="grid gap-3">
                        @foreach ($upcomingReminders as $reminder)
                            <li>
                                <a href="{{ route('people.show', $reminder->person) }}" class="card-link min-h-0" wire:navigate>
                                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <h3 class="card-title">{{ $reminder->note }}</h3>
                                            <p class="card-text">{{ $reminder->person->name }} / {{ $reminder->person->list->name }}</p>
                                        </div>

                                        <span class="card-meta">{{ $reminder->remind_on->format('M j, Y') }}</span>
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
