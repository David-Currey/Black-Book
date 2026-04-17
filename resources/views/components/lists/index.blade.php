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
        UserList::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->name = '';
        $this->description = '';
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
    <div class="mb-4">
        <input
            type="text"
            wire:model="name"
            placeholder="List name"
            class="border p-2 mr-2"
        >

        <input
            type="text"
            wire:model="description"
            placeholder="Description"
            class="border p-2 mr-2"
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
            <li class="mb-2">
                <strong>{{ $list->name }}</strong>
                <p>{{ $list->description }}</p>
            </li>
        @endforeach
    </ul>
</div>