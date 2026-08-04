<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-keluar-detail.getTable'), 'label' => 'Keluar Detail'], ['url' => '', 'label' => 'Realisasi Pick']]" />

    <livewire:keluar-realisasi-scan :detailId="$detailId" />
</x-layouts::app>
