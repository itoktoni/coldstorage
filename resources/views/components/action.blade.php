@props(['cancel' => url()->previous(), 'model' => null, 'action' => []])
@php
    $showAction = function($actionName) use ($action) {
        return empty($action) || in_array($actionName, $action);
    };
@endphp
<style>
    @media (min-width: 768px) { .action-bar { bottom: 0 !important; } }
</style>
<div class="action-bar fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 md:px-6 py-3 z-[45]" style="bottom: 4rem">
    <div class="flex items-center justify-between max-w-full mx-auto gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            {{ $slot }}
        </div>
        <div class="flex items-center gap-2 md:gap-3 flex-wrap">
            @if($showAction('create'))
                @can('create', $model)
                <a href="{{ moduleRoute('getCreate') }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 md:px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">add</span>
                    <span class="hidden sm:inline">Create</span>
                </a>
                @endcan
            @endif
            @if($showAction('save'))
                @can('save', $model)
                <x-button type="submit" icon="save">Save</x-button>
                @endcan
            @endif
            @if($showAction('update'))
                @can('update', $model)
                <x-button type="submit" icon="save">Update</x-button>
                @endcan
            @endif
            @if($showAction('delete'))
                @can('delete', $model)
                <x-button type="button" variant="error" icon="delete" onclick="deleteSelected()">Delete</x-button>
                @endcan
            @endif
            <a href="{{ $cancel }}" class="inline-flex items-center justify-center gap-2 h-10 px-4 md:px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                Cancel
            </a>
        </div>
    </div>
</div>
