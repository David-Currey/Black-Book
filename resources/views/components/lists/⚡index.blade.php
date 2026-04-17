<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $name = '';
    public string $description = '';

    /**
     * Create a new list for the logged-in user
     */
    public function createList(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        UserList::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->reset('name', 'description');
    }

    /**
     * Get all lists for the logged-in user
     */
    public function getListsProperty()
    {
        return Auth::user()->lists;
    }
};

?>

<div class="p-4">
    <h1 class="text-xl font-bold mb-4">Your Lists</h1>

    <!-- Create List Form -->
    <div class="mb-4 flex items-start gap-2">
        <div>
            <input 
                type="text" 
                wire:model.live="name" 
                placeholder="List name"
                class="border p-2"
            >

            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <input
            type="text"
            wire:model.live="description"
            placeholder="Description"
            class="border p-2"
        >

        <button
            wire:click="createList"
            class="bg-blue-500 text-white px-4 py-2"
        >
            Create
        </button>
    </div>

    <!-- List Display -->
    <ul>
        @foreach($this->lists as $list)
            <li class="mb-4">
                <a
                    href="{{ route('lists.show', $list) }}"
                    class="block border p-3 rounded hover:bg-gray-800"
                >
                    <strong>{{ $list->name }}</strong>
                    <p>{{ $list->description }}</p>
                </a>
            </li>
        @endforeach 
    </ul>
</div>