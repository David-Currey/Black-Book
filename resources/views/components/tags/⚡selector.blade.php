<?php

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modelable;
use Livewire\Component;

new class extends Component
{
    #[Modelable]
    public array $selectedTags = [];

    public string $tagName = '';
    public string $tagColor = '#0e639c';

    /**
     * Check if the selected tags have reached the max limit
     */
    public function getHasMaxTagsProperty(): bool
    {
        return count($this->selectedTags) >= 5;
    }

    /**
     * Get existing user tags that match the current tag input
     */
    public function getTagSuggestionsProperty()
    {
        if (trim($this->tagName) === '') {
            return collect();
        }

        $selectedTagIds = collect($this->selectedTags)
            ->pluck('tag_id')
            ->filter()
            ->all();

        return Auth::user()
            ->tags()
            ->where('name', 'like', '%' . trim($this->tagName) . '%')
            ->when(!empty($selectedTagIds), function ($query) use ($selectedTagIds) {
                $query->whereNotIn('id', $selectedTagIds);
            })
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    /**
     * Add a new tag to the selected tags list
     */
    public function addTag(): void
    {
        $this->validate([
            'tagName' => 'required|string|max:50',
            'tagColor' => 'required|string|size:7',
        ], [
            'tagName.required' => 'Tag name is required.',
        ]);

        if ($this->hasMaxTags) {
            $this->addError('tagName', 'A person can have a maximum of 5 tags.');
            return;
        }

        $cleanName = trim($this->tagName);

        $existingTag = Auth::user()->tags()
            ->where('name', $cleanName)
            ->first();

        if ($existingTag) {
            $alreadySelected = collect($this->selectedTags)
                ->contains(fn ($tag) => ($tag['tag_id'] ?? null) === $existingTag->id);

            if ($alreadySelected) {
                $this->addError('tagName', 'This tag is already selected.');
                return;
            }

            $this->selectedTags[] = [
                'tag_id' => $existingTag->id,
                'name' => $existingTag->name,
                'color' => $existingTag->color,
            ];
        } else {
            $alreadySelected = collect($this->selectedTags)
                ->contains(fn ($tag) => strtolower($tag['name']) === strtolower($cleanName));

            if ($alreadySelected) {
                $this->addError('tagName', 'This tag is already selected.');
                return;
            }

            $this->selectedTags[] = [
                'tag_id' => null,
                'name' => $cleanName,
                'color' => $this->tagColor,
            ];
        }

        $this->reset('tagName');
        $this->tagColor = '#0e639c';
    }

    /**
     * Attach an existing tag suggestion to the selected tags list
     */
    public function attachExistingTag(int $tagId): void
    {
        if ($this->hasMaxTags) {
            $this->addError('tagName', 'A person can have a maximum of 5 tags.');
            return;
        }

        $tag = Auth::user()->tags()->findOrFail($tagId);

        $alreadySelected = collect($this->selectedTags)
            ->contains(fn ($selectedTag) => ($selectedTag['tag_id'] ?? null) === $tag->id);

        if ($alreadySelected) {
            $this->addError('tagName', 'This tag is already selected.');
            return;
        }

        $this->selectedTags[] = [
            'tag_id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ];

        $this->reset('tagName');
        $this->tagColor = '#0e639c';
    }

    /**
     * Remove a selected tag by its array index
     */
    public function removeTag(int $index): void
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }
};

?>

<div>
    <label for="tag-name" class="app-label">
        Tags ({{ count($this->selectedTags) }}/5)
    </label>

    <div class="relative">
        <div class="flex gap-2 mb-3 items-center">
            <input
                id="tag-name"
                name="tag-input"
                type="text"
                wire:model.live="tagName"
                wire:keydown.enter="addTag"
                placeholder="Add a tag"
                class="app-input"
                autocomplete="new-password"
                @disabled($this->hasMaxTags)
            >

            <input
                id="tag-color"
                type="color"
                wire:model.live="tagColor"
                class="h-10 w-12 cursor-pointer rounded border border-[var(--app-border)] bg-[var(--app-surface-2)] p-1"
                title="Choose tag color"
                @disabled($this->hasMaxTags)
            >

            <button
                wire:click="addTag"
                class="btn-primary flex items-center justify-center w-10 h-10 disabled:opacity-50 disabled:cursor-not-allowed"
                title="Add tag"
                @disabled($this->hasMaxTags)
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>

        @if (!$this->hasMaxTags && $this->tagSuggestions->isNotEmpty())
            <div class="absolute z-20 mt-1 w-full rounded-xl border border-[var(--app-border)] bg-[var(--app-surface)] shadow-lg overflow-hidden">
                @foreach ($this->tagSuggestions as $tag)
                    <button
                        type="button"
                        wire:click="attachExistingTag({{ $tag->id }})"
                        class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-[var(--app-surface-2)] transition"
                    >
                        <span class="flex items-center gap-2 min-w-0">
                            <span
                                class="inline-block w-3 h-3 rounded-full shrink-0"
                                style="background-color: {{ $tag->color }};"
                            ></span>
                            <span class="truncate">{{ $tag->name }}</span>
                        </span>

                        <span class="text-xs text-[var(--app-text-muted)] shrink-0">
                            existing
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @error('tagName')
        <p class="validation-error">{{ $message }}</p>
    @enderror

    @if ($this->hasMaxTags)
        <p class="text-sm text-[var(--app-text-muted)] mt-2">
            Maximum of 5 tags reached.
        </p>
    @endif

    @if (empty($this->selectedTags))
        <p class="text-muted mt-2">No tags selected yet.</p>
    @else
        <div class="flex flex-wrap gap-2 mt-3">
            @foreach ($this->selectedTags as $index => $tag)
                <span
                    class="flex items-center gap-2 rounded-full px-3 py-1 text-sm"
                    style="
                        background-color: {{ $tag['color'] }}20;
                        border: 1px solid {{ $tag['color'] }};
                        color: {{ $tag['color'] }};
                    "
                >
                    {{ $tag['name'] }}

                    <button
                        wire:click="removeTag({{ $index }})"
                        type="button"
                        class="text-xs hover:opacity-80"
                    >
                        &times;
                    </button>
                </span>
            @endforeach
        </div>
    @endif
</div>
