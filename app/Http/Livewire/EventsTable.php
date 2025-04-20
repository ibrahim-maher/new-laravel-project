<?php

namespace App\Http\Livewire;

use App\Models\Event;
use Livewire\Component;
use Livewire\WithPagination;

class EventsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'start_date';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $categoryFilter = '';
    public $statusFilter = '';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        
        $this->sortField = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $events = Event::query()
            ->with(['venue', 'category'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('venue', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('category_id', $this->categoryFilter);
            })
            ->when($this->statusFilter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->statusFilter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->when($this->statusFilter === 'upcoming', function ($query) {
                $query->where('start_date', '>', now());
            })
            ->when($this->statusFilter === 'past', function ($query) {
                $query->where('end_date', '<', now());
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.events-table', [
            'events' => $events,
            'categories' => \App\Models\Category::all(),
        ]);
    }
}