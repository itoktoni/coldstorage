<div class="flex flex-col h-screen bg-base-200">
    <!-- Nav -->
    <div class="bg-base-100 border-b border-base-300 px-3 py-2 flex items-center justify-between shrink-0">
        <a href="{{ route('pos.table') }}" class="btn btn-xs btn-soft">
            <span class="icon-[tabler--arrow-left] size-3.5"></span>
        </a>
        <strong class="text-sm">POS</strong>
        <span class="text-xs text-base-content/60" id="clk"></span>
    </div>

    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        <!-- Products -->
        <div class="flex-1 flex flex-col p-2 pb-14 lg:pb-2 min-h-0">
            <!-- Categories -->
            <div class="flex items-center gap-1.5 mb-1.5 shrink-0">
                <div class="flex-1 flex gap-1 overflow-x-scroll" style="-webkit-overflow-scrolling:touch">
                    <button class="cat btn btn-xs btn-primary shrink-0" wire:click="$set('cat', 'All')">All</button>
                    @foreach($categories as $cat)
                    <button class="cat btn btn-xs btn-outline shrink-0" wire:click="$set('cat', '{{ $cat }}')">{{ $cat }}</button>
                    @endforeach
                </div>
                <button class="btn btn-xs btn-primary shrink-0" wire:click="$set('view', 'grid')">
                    <span class="icon-[tabler--layout-grid] size-3.5"></span>
                </button>
                <button class="btn btn-xs btn-outline shrink-0" wire:click="$set('view', 'list')">
                    <span class="icon-[tabler--list] size-3.5"></span>
                </button>
            </div>
            <!-- Search -->
            <input type="text" class="input input-sm w-full mb-2 shrink-0" placeholder="Cari produk..."
                wire:model.live="search">

            <!-- Products Grid/List -->
            <div class="flex-1 overflow-y-scroll min-h-0" style="-webkit-overflow-scrolling:touch">
                @if($view === 'grid')
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-4 gap-1.5 content-start">
                    @foreach($products as $product)
                    <div class="pc card bg-base-100 border border-base-300 cursor-pointer"
                        wire:click="addToCart({{ $product->product_id }})">
                        <figure class="px-1 pt-1">
                            <img src="https://placehold.co/100x70/f1f5f9/475569?text={{ urlencode(explode(' ', $product->product_nama)[0]) }}"
                                class="rounded w-full h-14 object-cover">
                        </figure>
                        <div class="p-1.5 pb-0">
                            <p class="text-[11px] font-medium truncate">{{ $product->product_nama }}</p>
                            <p class="text-[11px] font-bold">Rp {{ number_format($product->product_harga) }}</p>
                        </div>
                        <div class="border-t border-base-200 mt-1.5 px-1.5 py-1">
                            <button class="btn btn-xs btn-soft btn-block gap-0.5"
                                wire:click="openNote({{ $product->product_id }})">
                                <span class="icon-[tabler--note] size-3"></span>
                                <span class="varian text-[9px]">Catatan</span>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="space-y-1">
                    @foreach($products as $product)
                    <div class="pc flex items-center gap-2 bg-base-100 border border-base-300 rounded p-1.5 cursor-pointer"
                        wire:click="addToCart({{ $product->product_id }})">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium truncate">{{ $product->product_nama }}</p>
                            <span class="text-[10px] text-base-content/60">{{ $product->has_category?->category_nama }}</span>
                        </div>
                        <span class="text-xs font-bold shrink-0 mr-1">Rp {{ number_format($product->product_harga) }}</span>
                        <button class="btn btn-xs btn-soft btn-circle shrink-0"
                            wire:click="openNote({{ $product->product_id }})">
                            <span class="icon-[tabler--note] size-3"></span>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Cart Panel -->
        <div class="lg:w-72 xl:w-80 bg-base-100 border-t lg:border-t-0 lg:border-l border-base-300 flex-col h-full">
            <div class="flex items-center justify-between px-2 py-1.5 border-b border-base-300 shrink-0">
                <span class="font-bold text-xs">Order <span class="text-base-content/60">({{ count($cart) }})</span></span>
                <button class="btn btn-xs btn-soft btn-error" wire:click="clearCart">Clear</button>
            </div>
            <div class="flex-1 overflow-y-scroll p-2 space-y-1" style="-webkit-overflow-scrolling:touch">
                @if(empty($cart))
                <p class="text-center text-base-content/40 text-xs py-4">Keranjang kosong</p>
                @else
                    @foreach($cart as $item)
                    <div class="py-1 border-b border-base-200 last:border-0">
                        <div class="flex items-center gap-1">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium truncate">{{ $item['product_nama'] }}</p>
                                @if($item['variant'] !== 'Regular' || $item['note'])
                                <p class="text-[9px] text-base-content/50">
                                    @if($item['variant'] !== 'Regular')
                                    <span class="badge badge-xs">{{ $item['variant'] }}</span>
                                    @endif
                                    @if($item['note'])📝 {{ $item['note'] }}@endif
                                </p>
                                @endif
                                <p class="text-[10px] text-base-content/60">
                                    Rp {{ number_format($item['product_harga']) }}
                                    @if($item['extra']) <span class="text-primary">(+Rp {{ number_format($item['extra']) }})</span>@endif
                                </p>
                            </div>
                            <div class="join shrink-0">
                                <button class="btn btn-xs join-item" wire:click="updateQty('{{ $item['key'] }}', -1)">−</button>
                                <span class="btn btn-xs join-item no-animation font-bold">{{ $item['qty'] }}</span>
                                <button class="btn btn-xs join-item" wire:click="updateQty('{{ $item['key'] }}', 1)">+</button>
                            </div>
                            <span class="text-xs font-bold w-14 text-right shrink-0">
                                Rp {{ number_format(($item['product_harga'] + $item['extra']) * $item['qty']) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <div class="border-t border-base-300 p-2 space-y-1.5 shrink-0">
                <!-- Shipping -->
                <div class="text-xs mb-2">
                    <span class="text-base-content/60">Pengiriman</span>
                    <div class="flex flex-col gap-1 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ship" class="radio radio-xs radio-primary" value="0" checked
                                wire:click="$set('shipCost', 0)">
                            <span class="text-xs">COD Berbah <span class="text-base-content/40">(Gratis)</span></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ship" class="radio radio-xs radio-primary" value="0"
                                wire:click="$set('shipCost', 0)">
                            <span class="text-xs">COD Piyungan <span class="text-base-content/40">(Gratis)</span></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ship" class="radio radio-xs radio-primary" value="delivery">
                            <span class="text-xs">Dikirim @if($shipCost) <span class="text-primary font-medium">Rp {{ number_format($shipCost) }}</span>@endif</span>
                        </label>
                        @if($shipCost > 0)
                        <div class="pl-5 mt-1">
                            <div class="join w-full">
                                <input type="text" class="input input-xs join-item flex-1" placeholder="Catatan alamat..."
                                    wire:model="shipAddress">
                                <button class="btn btn-xs btn-soft join-item gap-0.5" wire:click="openMap">
                                    <span class="icon-[tabler--map-pin] size-3"></span>Ubah
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="border-b border-base-300 -mx-2 my-1"></div>

                <!-- Voucher -->
                <div class="flex justify-between items-center text-xs">
                    <span class="text-base-content/60">Voucher</span>
                    <div class="join">
                        <input type="text" class="input input-xs join-item w-20" placeholder="Kode..."
                            wire:model="voucherCode" wire:keydown.enter="applyVoucher">
                        <button class="btn btn-xs btn-primary join-item" wire:click="applyVoucher">Apply</button>
                    </div>
                </div>
                @if($voucherType)
                <div class="flex justify-between text-xs text-success">
                    <span>Voucher {{ strtoupper($voucherCode) }}</span>
                    <span>{{ $voucherType === 'pct' ? '-' . $voucherVal . '%' : '-Rp ' . number_format($voucherVal) }}</span>
                </div>
                @endif

                <!-- Discount -->
                <div class="flex justify-between items-center text-xs">
                    <span class="text-base-content/60">Disc</span>
                    <div class="join">
                        <input type="number" class="input input-xs join-item w-12 text-right" value="{{ $discAmt }}"
                            wire:model="discAmt" wire:input="$refresh" min="0">
                        <button class="btn btn-xs join-item" wire:click="toggleDiscType">
                            {{ $discIsPct ? '%' : 'Rp' }}
                        </button>
                    </div>
                </div>

                <!-- Tax -->
                <div class="flex justify-between items-center text-xs">
                    <span class="text-base-content/60">Tax</span>
                    <div class="join">
                        <input type="number" class="input input-xs join-item w-12 text-right" value="11" disabled>
                        <span class="btn btn-xs join-item no-animation">%</span>
                    </div>
                </div>

                <div class="divider my-0"></div>

                <div class="flex justify-between font-bold text-base">
                    <span>Total</span>
                    <span>Rp {{ number_format($total) }}</span>
                </div>

                <div class="flex gap-1">
                    <button class="pay btn btn-xs {{ $payMethod === 'cash' ? 'btn-primary' : 'btn-outline' }} flex-1"
                        wire:click="$set('payMethod', 'cash')">Cash</button>
                    <button class="pay btn btn-xs {{ $payMethod === 'qris' ? 'btn-primary' : 'btn-outline' }} flex-1"
                        wire:click="$set('payMethod', 'qris')">QRIS</button>
                    <button class="pay btn btn-xs {{ $payMethod === 'card' ? 'btn-primary' : 'btn-outline' }} flex-1"
                        wire:click="$set('payMethod', 'card')">Card</button>
                </div>

                <button class="btn btn-sm btn-primary btn-block" wire:click="checkout">Bayar</button>
            </div>
        </div>
    </div>

    @if($noteProd)
    @php $noteProduct = \App\Models\Product::find($noteProd); @endphp
    <div class="modal modal-open" role="dialog" aria-modal="true" aria-labelledby="noteModalTitle">
        <div class="modal-box" id="noteModalTitle" tabindex="-1">
            <h3 class="font-bold text-lg mb-4">Catatan Produk</h3>
            <p class="text-xs text-base-content/60 mb-2">{{ $noteProduct?->product_nama }}</p>
            <div class="space-y-3">
                <div>
                    <label class="label-text text-xs">Varian</label>
                    <select class="select select-bordered w-full" wire:model="noteVariant">
                        @foreach($variants as $v => $extra)
                        <option value="{{ $v }}">{{ $v }} (+Rp {{ number_format($extra) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-text text-xs">Catatan</label>
                    <input type="text" class="input input-bordered w-full" placeholder="Tanpa es, pedas, dll..."
                        wire:model="noteInput">
                </div>
                @php $extra = $variants[$noteVariant ?? 'Regular'] ?? 0; @endphp
                <div class="flex justify-between text-xs">
                    <span class="text-base-content/60">Harga + varian</span>
                    <span class="font-bold text-primary">Rp {{ number_format(($noteProduct?->product_harga ?? 0) + $extra) }}</span>
                </div>
            </div>
            <div class="modal-action">
                <button class="btn btn-ghost" wire:click="$set('noteProd', null)">Batal</button>
                <button class="btn btn-primary" wire:click="submitNote">Tambahkan</button>
            </div>
        </div>
    </div>
    @endif

    @if($showMap)
    <div class="modal modal-open">
        <div class="modal-box max-w-2xl w-full">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-lg">Pilih Lokasi Pengiriman</h3>
                <button class="btn btn-xs btn-soft btn-circle" wire:click="$set('showMap', false)">
                    <span class="icon-[tabler--x] size-4"></span>
                </button>
            </div>
            <div id="map" class="w-full h-64 rounded border border-base-300 mb-2"></div>
            <div class="flex justify-between items-center text-xs">
                <div class="text-base-content/60">
                    Jarak: <span id="mapDist">0</span> km × Rp 3.000/km
                </div>
                <div class="text-right">
                    <span class="text-primary font-bold">Ongkir: <span id="mapCost">Rp 0</span></span>
                </div>
            </div>
            <div class="modal-action">
                <button class="btn btn-sm btn-primary" onclick="confirmShipping()">Konfirmasi Lokasi</button>
            </div>
        </div>
    </div>
    @endif

</div>

    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
    <script>
        setInterval(function() {
            var clkEl = document.getElementById('clk');
            if (clkEl) clkEl.textContent = new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
        }, 1000);

        (function() {
            var map, destMarker;
            var STORE_LAT = -7.8;
            var STORE_LNG = 110.4;
            var PRICE_PER_KM = 3000;

            function initMap() {
                map = L.map('map').setView([STORE_LAT, STORE_LNG], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '© OSM'}).addTo(map);
                L.marker([STORE_LAT, STORE_LNG], {draggable: false}).addTo(map).bindPopup('Toko').openPopup();
                map.on('click', function(e) {
                    setDest(e.latlng.lat, e.latlng.lng);
                });
            }

            function setDest(lat, lng) {
                if (destMarker) map.removeLayer(destMarker);
                destMarker = L.marker([lat, lng], {draggable: true}).addTo(map).bindPopup('Tujuan').openPopup();
                destMarker.on('dragend', function(e) {
                    var p = e.target.getLatLng();
                    calcDist(p.lat, p.lng);
                });
                calcDist(lat, lng);
            }

            function calcDist(lat, lng) {
                var R = 6371;
                var dLat = (lat - STORE_LAT) * Math.PI / 180;
                var dLng = (lng - STORE_LNG) * Math.PI / 180;
                var a = Math.sin(dLat/2)**2 + Math.cos(STORE_LAT * Math.PI/180) * Math.cos(lat * Math.PI/180) * Math.sin(dLng/2)**2;
                var dist = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                var km = Math.round(dist * 10) / 10;
                var cost = Math.round(km * PRICE_PER_KM);
                var mapDist = document.getElementById('mapDist');
                var mapCost = document.getElementById('mapCost');
                if (mapDist) mapDist.textContent = km;
                if (mapCost) mapCost.textContent = 'Rp ' + cost.toLocaleString('id-ID');
                window.shipCost = cost;
            }

            function getMyLocation() {
                if (!navigator.geolocation) return alert('Geolocation not supported');
                navigator.geolocation.getCurrentPosition(function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    map.setView([lat, lng], 15);
                    setDest(lat, lng);
                }, function() { alert('Tidak bisa mendapatkan lokasi'); });
            }

            window.initPosMap = function() {
                initMap();
                getMyLocation();
            };

            window.confirmShipping = function() {
                var el = document.querySelector('[wire\\:id]');
                if (!el) return;
                var id = el.getAttribute('wire:id');
                var cost = window.shipCost || 0;
                Livewire.find(id).set('shipCostFromMap', cost);
                Livewire.find(id).call('confirmMap');
            };
        })();

        document.addEventListener('livewire:load', function() {
            Livewire.on('mapOpened', function() {
                setTimeout(function() { window.initPosMap(); }, 300);
            });
        });
    </script>
</div>
