<?php /** @var App\Models\Product $product */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Generate Barcode']]" />

    <div class="content mt-4 lg:mt-0">
        @if($errors->any())
        <div class="bg-error/10 border border-error rounded-xl p-4 mb-4">
            <p class="text-error font-body-sm font-semibold">Terjadi kesalahan:</p>
            <ul class="list-disc list-inside text-error text-sm mt-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Form --}}
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code</span>
                Generate QR Code
            </h3>
            <form method="POST" action="{{ route('wms-barcode.postGenerate') }}">
                @csrf
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="col-span-2">
                        <x-select label="Product" name="product_id" :options="$products" :default="$selectedProduct ?? ''" required />
                    </div>

                    <div>
                        <x-input label="Qty" name="qty" type="number" step="0.01" min="0.01" :value="$selectedQty ?? '1'" required />
                    </div>

                    <div>
                        <x-input label="Jumlah" name="jumlah" type="number" min="1" max="100" :value="$selectedJumlah ?? '1'" required />
                    </div>

                    <div class="col-span-2">
                        <x-input label="Expired Date" name="expired_date" type="date" :value="$selectedExpired ?? ''" />
                    </div>

                    <div class="col-span-2 md:col-span-1 flex items-end">
                        <x-button variant="primary" type="submit" class="w-full">Generate</x-button>
                    </div>
                </div>
            </form>
        </div>

        {{-- QR Code Results --}}
        @if(!empty($qrcodes))
        <div class="qr-print-area bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <input type="hidden" id="pdf-product-id" value="{{ $selectedProduct ?? '' }}" />
            <input type="hidden" id="pdf-qty" value="{{ $selectedQty ?? '' }}" />
            <input type="hidden" id="pdf-expired" value="{{ $selectedExpired ?? '' }}" />
            <input type="hidden" id="pdf-jumlah" value="{{ $selectedJumlah ?? '' }}" />
            <h3 class="qr-header font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">qr_code_2</span>
                <span class="min-w-0 truncate">List QR</span>
                <span class="text-sm font-normal text-on-surface-variant shrink-0">({{ count($qrcodes) }})</span>
                <button type="button" onclick="downloadPdf()" class="ml-auto shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors text-sm">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    <span class="hidden sm:inline">Print PDF</span>
                    <span class="sm:hidden">PDF</span>
                </button>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
                @foreach($qrcodes as $qr)
                <div class="border border-outline-variant rounded-lg p-2 md:p-3 text-center bg-white qr-item aspect-55/30 flex flex-col items-center justify-center">
                    <p style="font-size: 5px;" class="qr-label md:text-xs mb-1 md:mb-2 text-on-surface font-medium truncate" title="{{ $qr['content'] }}">{{ $qr['content'] }}</p>
                    <img src="data:image/png;base64,{{ $qr['image'] }}" alt="QR Code" class="mx-auto mb-1 md:mb-2" style="width:120px;height:120px;" />
                    <p class="qr-label text-[10px] md:text-xs text-on-surface font-medium truncate" title="{{ $qr['content'] }}">{{ $product->product_nama }}</p>
                    <p class="qr-label text-[10px] md:text-xs text-on-surface-variant">Qty: {{ (float) $qty }}</p>
                    @if($expired)
                    <p class="qr-label text-[10px] md:text-xs text-on-surface-variant">Exp: {{ \Carbon\Carbon::parse($expired)->format('d M Y') }}</p>
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
    <script>
        function downloadPdf() {
            var pid = document.getElementById('pdf-product-id');
            var productId = pid ? pid.value : document.getElementById('select-product_id').value;
            var qtyEl = document.getElementById('pdf-qty');
            var qty = qtyEl ? qtyEl.value : document.querySelector('[name="qty"]').value;
            var expEl = document.getElementById('pdf-expired');
            var expired = expEl ? expEl.value : document.querySelector('[name="expired_date"]').value;
            var jmlEl = document.getElementById('pdf-jumlah');
            var jumlah = jmlEl ? jmlEl.value : document.querySelector('[name="jumlah"]').value;
            var f = document.createElement('form');
            f.method = 'POST';
            f.target = '_blank';
            f.action = '{{ route("wms-barcode.pdf") }}';
            document.body.appendChild(f);
            f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="print" value="1"><input type="hidden" name="product_id" value="' + productId + '"><input type="hidden" name="qty" value="' + qty + '"><input type="hidden" name="expired_date" value="' + expired + '"><input type="hidden" name="jumlah" value="' + jumlah + '">';
            f.submit();
            f.remove();
        }
    </script>
</x-layouts::app>
