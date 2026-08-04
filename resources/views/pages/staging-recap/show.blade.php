<?php /** @var string $palletCode */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[
        ['url' => '/dashboard', 'label' => 'Home'],
        ['url' => route('wms-staging-recap.index'), 'label' => 'Staging Recap'],
        ['url' => '', 'label' => 'Rekap'],
    ]" />

    <div class="content mt-4 lg:mt-0">
        <livewire:staging-recap-scan :lokasiCode="$lokasiCode" />
    </div>
</x-layouts::app>
