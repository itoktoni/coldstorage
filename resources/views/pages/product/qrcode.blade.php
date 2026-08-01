<?php /** @var App\Models\Product $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/wms/product/getTable', 'label' => 'Product'], ['url' => '', 'label' => 'QR Code - ' . $model->product_code]]" />

    <div class="content mt-4 lg:mt-0">
        {{-- Form --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code</span>
                Generate QR Code
            </h3>
            <form method="POST" action="{{ route('wms-product.postQrcode', $model->product_id) }}">
                @csrf
                <div class="grid grid-cols-12 gap-5">
                    <div class="col-span-12 md:col-span-3">
                        <x-input label="Product Code" :value="$model->product_code" disabled />
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <x-input label="Product Name" :value="$model->product_nama" disabled />
                    </div>
                    <div class="col-span-12 md:col-span-2">
                        <x-input label="Qty" name="qty" type="number" step="0.01" min="0.01" :value="old('qty', '1')" required />
                    </div>
                    <div class="col-span-12 md:col-span-2">
                        <x-input label="Expired Date" name="expired_date" type="date" :value="old('expired_date')" />
                    </div>
                    <div class="col-span-12 md:col-span-2">
                        <x-input label="Jumlah" name="jumlah" type="number" min="1" max="100" :value="old('jumlah', '1')" required />
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <x-button variant="primary" type="submit">Generate</x-button>
                    @if(!empty($qrcodes))
                    <x-button variant="soft" type="button" onclick="window.print()">
                        <span class="material-symbols-outlined text-lg mr-1">print</span> Cetak
                    </x-button>
                    @endif
                </div>
            </form>
        </div>

        {{-- QR Code Results --}}
        @if(!empty($qrcodes))
        <div class="qr-print-area bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_2</span>
                QR Codes ({{ count($qrcodes) }})
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($qrcodes as $index => $qr)
                <div class="border border-outline-variant rounded-lg p-3 text-center bg-white qr-item">
                    <img src="data:image/png;base64,{{ $qr['image'] }}" alt="QR Code" class="mx-auto mb-2" style="width:150px;height:150px;" />
                    <p class="text-xs text-on-surface font-medium truncate" title="{{ $qr['content'] }}">{{ $model->product_nama }}</p>
                    <p class="text-xs text-on-surface-variant">Qty: {{ (float) $qty }}</p>
                    @if($expired)
                    <p class="text-xs text-on-surface-variant">Exp: {{ \Carbon\Carbon::parse($expired)->format('d M Y') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <style>
        @media print {
            body > *:not(main) { display: none !important; }
            main .form-card:not(.qr-print-area) { display: none !important; }
            main nav[aria-label="Breadcrumb"] { display: none !important; }
            .qr-print-area { border: none !important; padding: 0 !important; background: none !important; margin: 0 !important; }
            .qr-print-area .qr-header { display: none !important; }
            .qr-print-area .qr-item { page-break-inside: avoid; border: 1px solid #ccc !important; padding: 10px; display: inline-block; text-align: center; margin: 4px; width: 140px; }
            .qr-print-area img { width: 120px; height: 120px; }
            .qr-print-area .qr-label { font-size: 10px; margin-top: 4px; }
        }
    </style>
</x-layouts::app>
