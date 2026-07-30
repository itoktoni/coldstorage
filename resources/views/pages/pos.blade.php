<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://unpkg.com/dexie@4/dist/dexie.min.js"></script>
    <style>.pc:active{transform:scale(.96);transition:transform .1s}</style>
</head>
<body class="bg-base-200 h-screen flex flex-col overflow-hidden text-sm">

<!-- Nav -->
<nav class="bg-base-100 border-b border-base-300 px-3 py-1.5 flex items-center justify-between shrink-0">
    <a href="{{ url('/') }}" class="btn btn-xs btn-soft">
        <span class="icon-[tabler--arrow-left] size-3.5"></span>
    </a>
    <strong class="text-sm">POS</strong>
    <div class="flex items-center gap-2">
        <span id="syncStatus" class="badge badge-xs badge-success">Online</span>
        <button id="pendingSync" class="badge badge-xs badge-warning hidden cursor-pointer" onclick="clearPending()">0 pending</button>
        <span class="text-xs text-base-content/60" id="clk"></span>
    </div>
</nav>

<!-- Mobile tabs -->
<div class="lg:hidden flex border-b border-base-300 bg-base-100 shrink-0">
    <button id="tabProd" class="flex-1 py-2 text-xs font-bold border-b-2 border-primary text-primary" onclick="switchTab('prod')">Products</button>
    <button id="tabOrder" class="flex-1 py-2 text-xs font-medium text-base-content/60" onclick="switchTab('order')">Order <span id="ccM" class="badge badge-xs badge-primary"></span></button>
</div>

<div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
    <!-- Products -->
    <div id="panelProd" class="flex-1 flex flex-col p-2 pb-14 lg:pb-2 min-h-0">
        <!-- Categories row + view toggle fixed right -->
        <div class="flex items-center gap-1.5 mb-1.5 shrink-0">
            <div class="flex-1 flex gap-1 overflow-x-scroll" style="-webkit-overflow-scrolling:touch" id="categories">
                <button class="cat btn btn-xs btn-primary shrink-0" data-c="All">All</button>
                @foreach($categories as $cat)
                <button class="cat btn btn-xs btn-outline shrink-0" data-c="{{ $cat['category_nama'] }}">{{ $cat['category_nama'] }}</button>
                @endforeach
            </div>
            <button id="gB" class="btn btn-xs btn-primary shrink-0" onclick="view='grid';render();this.className='btn btn-xs btn-primary shrink-0';document.getElementById('lB').className='btn btn-xs btn-outline shrink-0'">
                <span class="icon-[tabler--layout-grid] size-3.5"></span>
            </button>
            <button id="lB" class="btn btn-xs btn-outline shrink-0" onclick="view='list';render();this.className='btn btn-xs btn-primary shrink-0';document.getElementById('gB').className='btn btn-xs btn-outline shrink-0'">
                <span class="icon-[tabler--list] size-3.5"></span>
            </button>
        </div>
        <!-- Search below -->
        <input type="text" id="src" class="input input-sm w-full mb-2 shrink-0" placeholder="Cari produk..." oninput="render()">
        <!-- Grid -->
        <div id="prods" class="flex-1 overflow-y-scroll min-h-0" style="-webkit-overflow-scrolling:touch"></div>
    </div>

    <!-- Note Modal -->
    <div id="noteModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40" onclick="closeNote()"></div>
        <div class="absolute bottom-0 left-0 right-0 lg:bottom-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-80 bg-base-100 rounded-t-2xl lg:rounded-xl p-4 space-y-3">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-bold" id="noteTitle">Catatan</h4>
                <button class="btn btn-xs btn-soft btn-circle" onclick="closeNote()">
                    <span class="icon-[tabler--x] size-4"></span>
                </button>
            </div>
            <div>
                <label class="label-text text-xs">Varian</label>
        <select id="noteVariant" class="select select-sm w-full" onchange="updateNotePrice()"></select>
            </div>
            <div>
                <label class="label-text text-xs">Catatan</label>
                <input type="text" id="noteInput" class="input input-sm w-full" placeholder="Tanpa es, pedas, dll...">
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-base-content/60">Harga + varian</span>
                <span id="noteTotal" class="font-bold text-primary"></span>
            </div>
            <button class="btn btn-sm btn-primary btn-block" onclick="submitNote()">Tambahkan</button>
        </div>
    </div>

    <!-- Order -->
    <div id="panelOrder" class="hidden lg:flex lg:w-72 xl:w-80 bg-base-100 border-t lg:border-t-0 lg:border-l border-base-300 flex-col h-full">
        <div class="flex items-center justify-between px-2 py-1.5 border-b border-base-300 shrink-0">
            <span class="font-bold text-xs">Order <span id="cc" class="text-base-content/60"></span></span>
            <button class="btn btn-xs btn-soft btn-error" onclick="clearCart()">Clear</button>
        </div>
        <div id="cart" class="flex-1 overflow-y-scroll p-2 space-y-1" style="-webkit-overflow-scrolling:touch">
            <p class="text-center text-base-content/40 text-xs py-4">Empty</p>
        </div>
        <div class="border-t border-base-300 p-2 space-y-1.5 shrink-0">
            <!-- Customer -->
            <div class="text-xs mb-2">
                <span class="text-base-content/60">Customer</span>
                <select id="custSelect" class="select select-sm w-full mt-1" onchange="onCustChange(this)">
                    <option value="">Walk-in Customer</option>
                    @foreach($customers as $c)
                    <option value="{{ $c['id'] }}">{{ $c['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="border-b border-base-300 -mx-2 my-1"></div>
            <!-- Shipping -->
            <div class="text-xs mb-2">
                <span class="text-base-content/60">Pengiriman</span>
                <div class="flex flex-col gap-1 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ship" class="radio radio-xs radio-primary" value="0" checked onchange="setShip(0)">
                        <span class="text-xs">COD Berbah <span class="text-base-content/40">(Gratis)</span></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ship" class="radio radio-xs radio-primary" value="0" onchange="setShip(0)">
                        <span class="text-xs">COD Piyungan <span class="text-base-content/40">(Gratis)</span></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="ship" class="radio radio-xs radio-primary" value="delivery" onchange="openMap()">
                        <span class="text-xs">Dikirim <span id="shipCost" class="text-primary font-medium"></span></span>
                    </label>
                    <div id="shipDetail" class="hidden pl-5 mt-1">
                        <div class="join w-full">
                            <input type="text" id="shipAddr" class="input input-xs join-item flex-1" placeholder="Catatan alamat...">
                            <button class="btn btn-xs btn-soft join-item gap-0.5" onclick="openMap()">
                                <span class="icon-[tabler--map-pin] size-3"></span>Ubah
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-b border-base-300 -mx-2 my-1"></div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-base-content/60">Voucher</span>
                <div class="join">
                    <input type="text" id="voucherCode" class="input input-xs join-item w-20" placeholder="Kode...">
                    <button class="btn btn-xs btn-primary join-item" onclick="applyVoucher()">Apply</button>
                </div>
            </div>
            <div id="voucherInfo" class="hidden flex justify-between text-xs text-success">
                <span id="voucherLabel"></span>
                <span id="voucherAmt"></span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-base-content/60">Subtotal</span>
                <span id="sub">Rp 0</span>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-base-content/60">Disc</span>
                <div class="join">
                    <input type="number" id="dsc" value="0" class="input input-xs join-item w-12 text-right" oninput="calcT()">
                    <button id="dTog" class="btn btn-xs join-item" onclick="dP=!dP;this.textContent=dP?'%':'Rp';calcT()">%</button>
                </div>
            </div>
            <div class="flex justify-between items-center text-xs">
                <span class="text-base-content/60">Tax</span>
                <div class="join">
                    <input type="number" id="tax" value="11" class="input input-xs join-item w-12 text-right" oninput="calcT()">
                    <span class="btn btn-xs join-item no-animation">%</span>
                </div>
            </div>
            <div class="divider my-0"></div>
            <div class="flex justify-between font-bold text-base">
                <span>Total</span>
                <span id="tot">Rp 0</span>
            </div>
            <div class="flex gap-1">
                <button class="pay btn btn-xs btn-outline flex-1" data-m="cash">Cash</button>
                <button class="pay btn btn-xs btn-outline flex-1" data-m="qris">QRIS</button>
                <button class="pay btn btn-xs btn-outline flex-1" data-m="card">COD</button>
            </div>
            <div id="qr" class="hidden py-1 text-center">
                <img src="https://placehold.co/100x100/f8fafc/1e293b?text=QRIS" class="rounded mx-auto">
            </div>
            <button class="btn btn-sm btn-primary btn-block" onclick="checkout()">Bayar</button>
            <div class="flex gap-1">
                <button class="btn btn-xs btn-outline flex-1" onclick="window.print()">
                    <span class="icon-[tabler--printer] size-3"></span>
                </button>
                <button class="btn btn-xs btn-outline flex-1" onclick="expPDF()">
                    <span class="icon-[tabler--pdf] size-3"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Map Modal -->
<div id="mapModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" onclick="closeMap()"></div>
    <div class="absolute inset-4 lg:inset-12 bg-base-100 rounded-xl flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2 border-b border-base-300 shrink-0">
            <h4 class="text-sm font-bold">Pilih Lokasi Pengiriman</h4>
            <button class="btn btn-xs btn-soft btn-circle" onclick="closeMap()">
                <span class="icon-[tabler--x] size-4"></span>
            </button>
        </div>
        <div id="map" class="flex-1"></div>
        <div class="px-3 py-2 border-t border-base-300 shrink-0">
            <div class="flex items-center justify-between gap-3">
                <button class="btn btn-xs btn-soft gap-1" onclick="getMyLocation()">
                    <span class="icon-[tabler--current-location] size-3.5"></span>Lokasi Saya
                </button>
                <div class="text-right">
                    <p class="text-[10px] text-base-content/50">Jarak: <span id="mapDist">0</span> km × Rp 3.000/km</p>
                    <p class="text-sm font-bold text-primary">Ongkir: <span id="mapCost">Rp 0</span></p>
                </div>
            </div>
            <button class="btn btn-sm btn-primary btn-block mt-2" onclick="confirmMap()">Konfirmasi Lokasi</button>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
    <script>
    const P=@json($products ?? []);
    const CATEGORIES=@json($categories ?? ['All']);
    const DISCOUNTS=@json($discounts ?? []);
    const CUSTOMERS=@json($customers ?? []);
    let cart=[],cat="All",view="grid",dP=true,pay="",noteProd=null,shipCost=0,voucherDisc=0,customerId=null;
    const fmt=n=>"Rp "+n.toLocaleString("id-ID");

    // Dexie DB for offline storage
    const db = new Dexie('POSOffline');
    db.version(1).stores({
        orders: '++id, order_code, status, created_at'
    });

    // Online/Offline Status
    let isOnline = navigator.onLine;
    let posData = null;
    let posDataLoaded = false;

    function updateOnlineStatus() {
        isOnline = navigator.onLine;
        const statusEl = document.getElementById('syncStatus');
        if (statusEl) {
            statusEl.textContent = isOnline ? 'Online' : 'Offline';
            statusEl.className = isOnline ? 'badge badge-xs badge-success' : 'badge badge-xs badge-error';
        }
    }
    window.addEventListener('online', () => { updateOnlineStatus(); syncPendingOrders(); loadPosData(); });
    window.addEventListener('offline', () => { updateOnlineStatus(); });

    async function loadPosData() {
        if (!isOnline) {
            const cached = localStorage.getItem('posData');
            if (cached) {
                posData = JSON.parse(cached);
                posDataLoaded = true;
                applyPosData();
            }
            return;
        }
        try {
            const res = await fetch('/pos/data');
            const json = await res.json();
            posData = json;
            posDataLoaded = true;
            localStorage.setItem('posData', JSON.stringify(json));
            applyPosData();
        } catch (e) {
            console.log('Failed to load POS data:', e);
        }
    }

    function applyPosData() {
        if (!posData) return;
        if (posData.products) P = posData.products;
        if (posData.categories) {
            const catNames = posData.categories.map(c => c.category_nama);
            CATEGORIES.length = 0; CATEGORIES.push(...catNames);
            const catContainer = document.getElementById('categories');
            if (catContainer) {
                catContainer.innerHTML = '<button class="cat btn btn-xs btn-primary shrink-0" data-c="All">All</button>' +
                    posData.categories.map(c => `<button class="cat btn btn-xs btn-outline shrink-0" data-c="${c.category_nama}">${c.category_nama}</button>`).join('');
                document.querySelectorAll('.cat').forEach(b => b.addEventListener('click', () => {
                    document.querySelectorAll('.cat').forEach(x => x.className = 'cat btn btn-xs btn-outline shrink-0');
                    b.className = 'cat btn btn-xs btn-primary shrink-0';
                    cat = b.dataset.c;
                    render();
                }));
            }
        }
        if (posData.discounts) DISCOUNTS.length = 0; DISCOUNTS.push(...posData.discounts);
        if (posData.customers) {
            const custSelect = document.getElementById('custSelect');
            if (custSelect) {
                custSelect.innerHTML = '<option value="">Walk-in Customer</option>' +
                    posData.customers.map(c => `<option value="${c.id}">${c.nama}</option>`).join('');
            }
            CUSTOMERS.length = 0; CUSTOMERS.push(...posData.customers);
        }
        if (posData.store_lat) STORE_LAT = posData.store_lat;
        if (posData.store_lng) STORE_LNG = posData.store_lng;
        if (posData.price_per_km) PRICE_PER_KM = posData.price_per_km;
        render();
    }

    // Update pending sync badge
    async function updatePendingBadge() {
        const count = await db.orders.where('status').equals('pending').count();
        const el = document.getElementById('pendingSync');
        if (el) {
            if (count > 0) {
                el.textContent = count + ' pending';
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }
    }

    // Save order locally
    async function saveOrderLocal(data) {
        const orderCode = 'POS' + Date.now();
        await db.orders.add({
            order_code: orderCode,
            data: JSON.stringify(data),
            status: 'pending',
            created_at: new Date().toISOString()
        });
        await updatePendingBadge();
        return orderCode;
    }

    // Clear all pending orders
    async function clearPending() {
        if (!confirm('Hapus semua order pending?')) return;
        await db.orders.where('status').equals('pending').delete();
        await updatePendingBadge();
    }

    // Sync pending orders to server
    async function syncPendingOrders() {
        if (!isOnline) return;
        const pending = await db.orders.where('status').equals('pending').toArray();
        for (const order of pending) {
            try {
                const res = await fetch('/pos-checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                    },
                    body: order.data
                });
                const result = await res.json();
                if (result.success) {
                    await db.orders.update(order.id, { status: 'synced', server_order_id: result.order_id });
                }
            } catch (e) {
                console.log('Sync failed:', e);
            }
        }
        await updatePendingBadge();
    }

function onCustChange(sel){customerId=sel.value||null;}
function applyVoucher(){
  const code=document.getElementById('voucherCode').value.trim().toUpperCase();
  const st=cart.reduce((s,i)=>s+(i.p+i.extra)*i.q,0);
  const v=DISCOUNTS.find(d=>d.code===code);
  if(!v){alert('Voucher tidak valid');voucherDisc=0;document.getElementById('voucherInfo').classList.add('hidden');calcT();return;}
  if(v.min && st<v.min){alert('Minimal transaksi Rp '+v.min.toLocaleString('id-ID')+' untuk menggunakan '+v.nama);return;}
  document.getElementById('voucherInfo').classList.remove('hidden');
  const labelText=v.type==='percentage'?' ('+v.val+'%)':' (Rp '+v.val.toLocaleString('id-ID')+')';
  const minText=v.min?'\nMin: Rp '+v.min.toLocaleString('id-ID'):'';
  document.getElementById('voucherLabel').textContent='✓ '+v.nama+labelText+minText;
  voucherDisc=v.val;
  document.getElementById('voucherInfo').dataset.type=v.type;
  calcT();
}
const STORE_LAT={{ $store_lat }},STORE_LNG={{ $store_lng }},PRICE_PER_KM={{ $price_per_km }};
let map,marker,destMarker;

// Shipping
function setShip(cost){shipCost=+cost;document.getElementById('shipCost').textContent='';calcT();}
function openMap(){document.getElementById('mapModal').classList.remove('hidden');if(!map){setTimeout(()=>{initMap();getMyLocation();},100);}else{getMyLocation();}}
function closeMap(){document.getElementById('mapModal').classList.add('hidden');}
function initMap(){
  map=L.map('map').setView([STORE_LAT,STORE_LNG],13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OSM'}).addTo(map);
  marker=L.marker([STORE_LAT,STORE_LNG],{draggable:false}).addTo(map).bindPopup('Toko').openPopup();
  map.on('click',function(e){setDest(e.latlng.lat,e.latlng.lng);});
}
function setDest(lat,lng){
  if(destMarker)map.removeLayer(destMarker);
  destMarker=L.marker([lat,lng],{draggable:true}).addTo(map).bindPopup('Tujuan').openPopup();
  destMarker.on('dragend',function(e){const p=e.target.getLatLng();calcDist(p.lat,p.lng);});
  calcDist(lat,lng);
}
function calcDist(lat,lng){
  const R=6371,dLat=(lat-STORE_LAT)*Math.PI/180,dLng=(lng-STORE_LNG)*Math.PI/180;
  const a=Math.sin(dLat/2)**2+Math.cos(STORE_LAT*Math.PI/180)*Math.cos(lat*Math.PI/180)*Math.sin(dLng/2)**2;
  const dist=R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
  const km=Math.round(dist*10)/10;
  const cost=Math.round(km*PRICE_PER_KM);
  document.getElementById('mapDist').textContent=km;
  document.getElementById('mapCost').textContent=fmt(cost);
  shipCost=cost;
}
function getMyLocation(){
  if(!navigator.geolocation)return alert('Geolocation not supported');
  navigator.geolocation.getCurrentPosition(pos=>{
    const{latitude:lat,longitude:lng}=pos.coords;
    map.setView([lat,lng],15);setDest(lat,lng);
  },()=>alert('Tidak bisa mendapatkan lokasi'));
}
function confirmMap(){document.getElementById('shipCost').textContent=fmt(shipCost);document.getElementById('shipDetail').classList.remove('hidden');closeMap();calcT();}


function openNote(name,e){e.stopPropagation();noteProd=name;const p=P.find(x=>x.product_nama===name);document.getElementById('noteTitle').textContent=name;document.getElementById('noteInput').value='';const sel=document.getElementById('noteVariant');sel.innerHTML='';const variants=p.variants||[];if(variants.length===0){sel.innerHTML='<option value="0" data-price="0">Regular (+Rp 0)</option>';}else{variants.forEach((v,i)=>{const diff=v.variant_harga-p.product_harga;const label=diff>=0?'+Rp '+diff.toLocaleString('id-ID'):'-Rp '+Math.abs(diff).toLocaleString('id-ID');sel.innerHTML+=`<option value="${v.variant_id}" data-price="${v.variant_harga}">${v.variant_nama} (${label})</option>`;});}sel.selectedIndex=0;document.getElementById('noteTotal').textContent=fmt(p.product_harga);document.getElementById('noteModal').classList.remove('hidden');}
function closeNote(){document.getElementById('noteModal').classList.add('hidden');}
function updateNotePrice(){const p=P.find(x=>x.product_nama===noteProd);const extra=+document.getElementById('noteVariant').value;document.getElementById('noteTotal').textContent=fmt(p.product_harga+extra);}
function submitNote(){if(!noteProd)return;const p=P.find(x=>x.product_nama===noteProd);const sel=document.getElementById('noteVariant');const extra=+sel.value;const vLabel=sel.options[sel.selectedIndex].text.split(' (')[0];const vVal=sel.value;const note=document.getElementById('noteInput').value;const key=p.product_id+'|'+vLabel+'|'+note;const e=cart.find(x=>x.key===key);if(e)e.q++;else cart.push({key,id:p.product_id,n:p.product_nama,p:p.product_harga,extra:extra,q:1,variant:vLabel,note,vid:vVal});rCart();calcT();closeNote();}


function switchTab(t){
  document.getElementById('panelProd').className=t==='prod'?'flex-1 flex flex-col p-2 min-h-0':'hidden';
  document.getElementById('panelOrder').className=t==='order'?'flex flex-col h-full':'hidden lg:flex lg:w-72 xl:w-80 bg-base-100 border-t lg:border-t-0 lg:border-l border-base-300 flex-col h-full';
  document.getElementById('tabProd').className=t==='prod'?'flex-1 py-2 text-xs font-bold border-b-2 border-primary text-primary':'flex-1 py-2 text-xs font-medium text-base-content/60';
  document.getElementById('tabOrder').className=t==='order'?'flex-1 py-2 text-xs font-bold border-b-2 border-primary text-primary':'flex-1 py-2 text-xs font-medium text-base-content/60';
}
window.addEventListener('resize',()=>{
  const d=innerWidth>=1024;
  document.getElementById('panelProd').className='flex-1 flex flex-col p-2 min-h-0';
  document.getElementById('panelOrder').className=d?'lg:flex lg:w-72 xl:w-80 bg-base-100 border-l border-base-300 flex-col h-full':'hidden lg:flex lg:w-72 xl:w-80 bg-base-100 border-t lg:border-t-0 lg:border-l border-base-300 flex-col h-full';
  if(d){document.getElementById('tabProd').className='flex-1 py-2 text-xs font-bold border-b-2 border-primary text-primary';document.getElementById('tabOrder').className='flex-1 py-2 text-xs font-medium text-base-content/60';}
});

function render(){const c=document.getElementById("prods"),q=document.getElementById("src").value.toLowerCase(),f=P.filter(p=>(cat==="All"||p.product_category===cat)&&p.product_nama.toLowerCase().includes(q));
if(view==="grid"){c.className="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-4 gap-1.5 content-start overflow-y-scroll min-h-0";c.innerHTML=f.map(p=>`<div class="pc card bg-base-100 border border-base-300 cursor-pointer" onclick="add('${p.product_nama}')"><figure class="px-1 pt-1"><img src="https://placehold.co/100x70/f1f5f9/475569?text=${encodeURIComponent(p.product_nama.split(' ')[0])}" class="rounded w-full h-14 object-cover"></figure><div class="p-1.5 pb-0"><p class="text-[11px] font-medium truncate">${p.product_nama}</p><p class="text-[11px] font-bold">${fmt(p.product_harga)}</p></div><div class="border-t border-base-200 mt-1.5 px-1.5 py-1"><button class="btn btn-xs btn-soft btn-block gap-0.5" onclick="openNote('${p.product_nama}',event)"><span class="icon-[tabler--note] size-3"></span><span class="text-[9px]">Catatan</span></button></div></div>`).join("");}
else{c.className="space-y-1 overflow-y-scroll min-h-0";c.innerHTML=f.map(p=>`<div class="pc flex items-center gap-2 bg-base-100 border border-base-300 rounded p-1.5 cursor-pointer" onclick="add('${p.product_nama}')"><div class="flex-1 min-w-0"><p class="text-xs font-medium truncate">${p.product_nama}</p><span class="text-[10px] text-base-content/60">${p.product_category}</span></div><span class="text-xs font-bold shrink-0 mr-1">${fmt(p.product_harga)}</span><button class="btn btn-xs btn-soft btn-circle shrink-0" onclick="openNote('${p.product_nama}',event)" title="Catatan"><span class="icon-[tabler--note] size-3"></span></button></div>`).join("");}}

function add(n){const p=P.find(x=>x.product_nama===n);const key=p.product_id+'|Regular|';const e=cart.find(x=>x.key===key);if(e)e.q++;else cart.push({key,id:p.product_id,n:p.product_nama,p:p.product_harga,q:1,variant:'Regular',note:'',extra:0,vid:null});rCart();calcT();}
function uQ(key,d){const i=cart.find(x=>x.key===key);i.q+=d;if(i.q<=0)cart=cart.filter(x=>x.key!==key);
  // Recheck voucher min amount
  const st=cart.reduce((s,item)=>s+(item.p+item.extra)*item.q,0);
  const code=document.getElementById('voucherCode').value.trim().toUpperCase();
  const v=DISCOUNTS.find(disc=>disc.code===code);
  if(v&&v.min&&st<v.min){document.getElementById('voucherCode').value='';voucherDisc=0;document.getElementById('voucherInfo').classList.add('hidden');}
  rCart();calcT();}
function clearCart(){cart=[];rCart();calcT();}
function rCart(){const c=document.getElementById("cart");const cnt=cart.reduce((s,i)=>s+i.q,0);document.getElementById("cc").textContent=cnt?"("+cnt+")":"";document.getElementById("ccM").textContent=cnt||"";if(!cart.length){c.innerHTML='<p class="text-center text-base-content/40 text-xs py-4">Empty</p>';return;}c.innerHTML=cart.map(i=>{const hasInfo=i.variant!=="Regular"||i.note;const unitPrice=i.p;const lineTotal=(unitPrice+i.extra)*i.q;return '<div class="py-1 border-b border-base-200 last:border-0"><div class="flex items-center gap-1"><div class="flex-1 min-w-0"><p class="text-xs font-medium truncate">'+i.n+'</p>'+(hasInfo?'<p class="text-[9px] text-base-content/50">'+(i.variant!=="Regular"?'<span class="badge badge-xs">'+i.variant+'</span> ':'')+(i.note?'📝 '+i.note:'')+'</p>':'')+'<p class="text-[10px] text-base-content/60">'+fmt(unitPrice)+(i.extra&&i.variant!=="Regular"?' <span class="text-primary">(+ '+fmt(i.extra)+')</span>':'')+'</p></div><div class="join shrink-0"><button class="btn btn-xs join-item" onclick="uQ(\''+i.key+'\',-1)">−</button><span class="btn btn-xs join-item no-animation font-bold">'+i.q+'</span><button class="btn btn-xs join-item" onclick="uQ(\''+i.key+'\',1)">+</button></div><span class="text-xs font-bold w-14 text-right shrink-0">'+fmt(lineTotal)+'</span></div></div>';}).join("");}
function calcT(){const st=cart.reduce((s,i)=>s+(i.p+i.extra)*i.q,0);const dv=+(document.getElementById("dsc").value)||0;const tv=+(document.getElementById("tax").value)||0;const disc=dP?st*dv/100:dv;let af=Math.max(0,st-disc);
  // Voucher with max amount check
  const vType=document.getElementById('voucherInfo').dataset.type;
  const code=document.getElementById('voucherCode').value.trim().toUpperCase();
  const v=DISCOUNTS.find(d=>d.code===code);
  let vAmt=0;
  if(voucherDisc&&v){vAmt=v.type==='pct'?af*voucherDisc/100:voucherDisc;if(v.max&&vAmt>v.max)vAmt=v.max;}
  if(voucherDisc)document.getElementById('voucherAmt').textContent='-'+fmt(Math.round(vAmt));
  af=Math.max(0,af-vAmt);
  const g=Math.round(af+af*tv/100+shipCost);document.getElementById("sub").textContent=fmt(st);document.getElementById("tot").textContent=fmt(g);}
async function checkout(){if(!cart.length)return alert("Empty!");if(!pay)return alert("Select payment!");const st=cart.reduce((s,i)=>s+(i.p+i.extra)*i.q,0);const dv=+(document.getElementById("dsc").value)||0;const tv=+(document.getElementById("tax").value)||0;const disc=dP?st*dv/100:dv;let af=Math.max(0,st-disc);const vType=document.getElementById('voucherInfo').dataset.type;const vAmt=vType==='pct'?af*voucherDisc/100:voucherDisc;if(voucherDisc)af=Math.max(0,af-vAmt);const g=Math.round(af+af*tv/100+shipCost);const shipType=document.querySelector('input[name="ship"]:checked')?.value||'cod_berbah';const shipAddr=document.getElementById('shipAddr')?.value||'';const data={items:cart.map(i=>({product_id:i.id,name:i.n,price:i.p,quantity:i.q,variant:i.variant,variant_id:i.vid,note:i.note,extra:i.extra})),payment_method:pay,subtotal:st,discount:disc,tax:tv,shipping_cost:shipCost,total:g,shipping_type:shipType,shipping_address:shipAddr,voucher_code:document.getElementById('voucherCode').value||null,voucher_discount:Math.round(vAmt),customer_id:customerId};try{if(isOnline){const res=await fetch('/pos-checkout',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''},body:JSON.stringify(data)});const result=await res.json();if(result.success){alert('Order '+result.order_id+' berhasil!');clearCart();}else{alert('Gagal: '+result.message);}}else{await saveOrderLocal(data);alert('Order disimpan offline! Akan sync saat online.');clearCart();}}catch(e){await saveOrderLocal(data);alert('Koneksi gagal - order disimpan offline! Akan sync saat online.');clearCart();}}
function expPDF(){if(!cart.length)return alert("Empty!");const d=document.createElement('div');d.style.cssText='padding:16px;font:12px sans-serif';d.innerHTML='<h3>Struk - '+new Date().toLocaleString('id-ID')+'</h3><hr><br>'+cart.map(i=>`<div style="display:flex;justify-content:space-between"><span>${i.n} x${i.q}</span><span>${fmt((i.p+i.extra)*i.q)}</span></div>`).join('')+'<hr><div style="display:flex;justify-content:space-between;font-weight:bold;margin-top:8px"><span>Total</span><span>'+document.getElementById("tot").textContent+'</span></div>';html2pdf().set({margin:5,filename:'struk.pdf',jsPDF:{format:[80,200],unit:'mm'}}).from(d).save();}
document.querySelectorAll(".cat").forEach(b=>b.addEventListener("click",()=>{document.querySelectorAll(".cat").forEach(x=>{x.className="cat btn btn-xs btn-outline shrink-0"});b.className="cat btn btn-xs btn-primary shrink-0";cat=b.dataset.c;render();}));
document.querySelectorAll(".pay").forEach(b=>b.addEventListener("click",()=>{document.querySelectorAll(".pay").forEach(x=>{x.className="pay btn btn-xs btn-outline flex-1"});b.className="pay btn btn-xs btn-primary flex-1";pay=b.dataset.m;document.getElementById("qr").classList.toggle("hidden",pay!=="qris");}));
setInterval(()=>{document.getElementById("clk").textContent=new Date().toLocaleTimeString("id-ID",{hour:"2-digit",minute:"2-digit"})},1000);
render();
// Init offline status
updateOnlineStatus();
updatePendingBadge();
</script>
</body>
</html>
