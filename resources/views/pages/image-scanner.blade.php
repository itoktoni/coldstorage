<x-layouts::app title="Image Scanner">
    <x-breadcrumb :items="[['url' => route('dashboard'), 'label' => 'Dashboard'], ['url' => '', 'label' => 'Image Scanner']]" />

    <div class="mt-5">
        @livewire('image-scanner')
    </div>
</x-layouts::app>
