<?php

use App\Models\Person;
use App\Models\Tag;
use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * Get matching lists owned by the current user.
     */
    public function getListResultsProperty()
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return collect();
        }

        return UserList::query()
            ->withCount('people')
            ->where(function ($query) {
                $query
                    ->where('user_id', Auth::id())
                    ->orWhereHas('shares', fn ($shareQuery) => $shareQuery->where('user_id', Auth::id()));
            })
            ->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * Get matching entries from lists owned by the current user.
     */
    public function getEntryResultsProperty()
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return collect();
        }

        return Person::query()
            ->with(['list', 'tags'])
            ->whereHas('list', function ($query) {
                $query
                    ->where('user_id', Auth::id())
                    ->orWhereHas('shares', fn ($shareQuery) => $shareQuery->where('user_id', Auth::id()));
            })
            ->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('game', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhereHas('timelineNotes', fn ($noteQuery) => $noteQuery->where('note', 'like', '%' . $search . '%'))
                    ->orWhereHas('reminders', fn ($reminderQuery) => $reminderQuery->where('note', 'like', '%' . $search . '%'))
                    ->orWhereHas('customFieldValues', fn ($fieldQuery) => $fieldQuery->where('value', 'like', '%' . $search . '%'))
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', '%' . $search . '%'));
            })
            ->orderBy('name')
            ->limit(12)
            ->get();
    }

    /**
     * Get matching tags owned by the current user.
     */
    public function getTagResultsProperty()
    {
        $search = $this->normalizedSearch();

        if ($search === '') {
            return collect();
        }

        return Tag::query()
            ->where('user_id', Auth::id())
            ->where('name', 'like', '%' . $search . '%')
            ->withCount('people')
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * Check whether the current search has no results.
     */
    public function getHasNoResultsProperty(): bool
    {
        return $this->normalizedSearch() !== ''
            && $this->listResults->isEmpty()
            && $this->entryResults->isEmpty()
            && $this->tagResults->isEmpty();
    }

    private function normalizedSearch(): string
    {
        return trim($this->search);
    }
};

?>

<div class="app-shell space-y-6">
    <div class="page-header">
        <p class="eyebrow">Global search</p>
        <h1 class="page-title mt-2">Find Anything</h1>
        <p class="page-subtitle">Search across your lists, entries, notes, categories, and tags.</p>
    </div>

    <div class="panel">
        <div class="panel-inner">
            <label for="global-search" class="app-label">Search Dossier</label>
            <input
                id="global-search"
                type="search"
                wire:model.live.debounce.250ms="search"
                class="app-input"
                placeholder="Search names, notes, categories, tags..."
                autofocus
            >
        </div>
    </div>

    @if (trim($search) === '')
        <div class="panel">
            <div class="panel-inner">
                <h2 class="panel-title">Start typing to search</h2>
                <p class="text-muted">Use search when you remember a name, a note fragment, a category, or a tag but not where you saved it.</p>
            </div>
        </div>
    @elseif ($this->hasNoResults)
        <div class="panel">
            <div class="panel-inner">
                <h2 class="panel-title">No results found</h2>
                <p class="text-muted">Nothing matched "{{ trim($search) }}". Try a shorter word or a tag name.</p>
            </div>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
            <div class="space-y-6">
                <section class="panel">
                    <div class="panel-inner">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="panel-title !mb-0">Lists</h2>
                            <span class="card-meta">{{ $this->listResults->count() }}</span>
                        </div>

                        @if ($this->listResults->isEmpty())
                            <p class="text-muted">No matching lists.</p>
                        @else
                            <ul class="grid gap-3">
                                @foreach ($this->listResults as $list)
                                    <li>
                                        <a href="{{ route('lists.show', $list) }}" class="card-link min-h-0" wire:navigate>
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <h3 class="card-title">{{ $list->name }}</h3>
                                                    <p class="card-text">{{ $list->description ?: 'No description provided.' }}</p>
                                                </div>
                                                <span class="card-meta shrink-0">{{ $list->people_count }} entries</span>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-inner">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="panel-title !mb-0">Tags</h2>
                            <span class="card-meta">{{ $this->tagResults->count() }}</span>
                        </div>

                        @if ($this->tagResults->isEmpty())
                            <p class="text-muted">No matching tags.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->tagResults as $tag)
                                    <span
                                        class="flex items-center gap-2 rounded-full px-3 py-1 text-sm"
                                        style="
                                            background-color: {{ $tag->color }}20;
                                            border: 1px solid {{ $tag->color }};
                                            color: {{ $tag->color }};
                                        "
                                    >
                                        {{ $tag->name }}
                                        <span class="text-xs opacity-75">{{ $tag->people_count }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            <section class="panel">
                <div class="panel-inner">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="panel-title !mb-0">Entries</h2>
                        <span class="card-meta">{{ $this->entryResults->count() }}</span>
                    </div>

                    @if ($this->entryResults->isEmpty())
                        <p class="text-muted">No matching entries.</p>
                    @else
                        <ul class="grid gap-3">
                            @foreach ($this->entryResults as $person)
                                <li>
                                    <a href="{{ route('people.show', $person) }}" class="card-link" wire:navigate>
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <h3 class="card-title">{{ $person->name }}</h3>
                                                <p class="card-text">
                                                    {{ $person->list->name }}
                                                    @if ($person->game)
                                                        <span class="text-[var(--app-text-muted)]">/ {{ $person->game }}</span>
                                                    @endif
                                                </p>

                                                <div class="mt-3 flex flex-wrap gap-2">
                                                    <span class="card-meta">{{ $person->statusLabel() }}</span>

                                                    @if ($person->rating)
                                                        <span class="card-meta">{{ $person->rating }}/5</span>
                                                    @endif
                                                </div>

                                                @if ($person->notes)
                                                    <p class="card-text line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($person->notes), 140) }}</p>
                                                @endif

                                                @if ($person->tags->isNotEmpty())
                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        @foreach ($person->tags as $tag)
                                                            <span
                                                                class="rounded-full px-3 py-1 text-xs"
                                                                style="
                                                                    background-color: {{ $tag->color }}20;
                                                                    border: 1px solid {{ $tag->color }};
                                                                    color: {{ $tag->color }};
                                                                "
                                                            >
                                                                {{ $tag->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <span class="text-sm text-[var(--app-text-muted)] shrink-0">Open &rarr;</span>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>
    @endif
</div>
