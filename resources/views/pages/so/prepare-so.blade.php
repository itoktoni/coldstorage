<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so-prepare.index'), 'label' => 'Prepare SO'], ['url' => '', 'label' => 'Scan Stock']]" />

    <livewire:so-prepare-scan :soId="$soId" />
</x-layouts::app>
