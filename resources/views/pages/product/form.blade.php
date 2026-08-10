<?php /** @var App\Models\Product $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-select col="4" name="product_category" :options="$categoryOptions ?? []" placeholder="Pilih kategori..." />
                <x-input col="4" name="product_nama" />
                <x-input col="4" name="product_harga" type="number" />
                <x-select col="4" name="product_status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="Pilih status..." />

                <div class="col-span-12 md:col-span-4">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Foto Produk</label>

                    {{-- Camera button (mobile) --}}
                    <button type="button" id="btn-camera-capture"
                        class="md:hidden w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary/5 px-4 py-5 text-primary font-bold hover:bg-primary/10 transition-colors mb-2">
                        <span class="material-symbols-outlined text-2xl">photo_camera</span>
                        <span class="font-body-sm">Ambil Foto</span>
                    </button>

                    {{-- Gallery button (mobile) --}}
                    <button type="button" id="btn-gallery"
                        class="md:hidden w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary/5 px-4 py-5 text-primary font-bold hover:bg-primary/10 transition-colors mb-2">
                        <span class="material-symbols-outlined text-2xl">photo_library</span>
                        <span class="font-body-sm">Pilih dari Galeri</span>
                    </button>

                    {{-- File browse button (desktop) --}}
                    <button type="button" id="btn-browse-file"
                        class="hidden md:flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-outline-variant px-4 py-6 text-on-surface-variant hover:border-primary hover:bg-primary/5 transition-colors">
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                        <span class="font-body-sm">Pilih File</span>
                    </button>

                    {{-- Preview --}}
                    <div id="image-preview" class="relative hidden mt-2">
                        <img id="preview-img" src="" alt="Preview" class="w-full max-h-48 object-contain rounded-lg border border-outline-variant">
                        <button type="button" id="btn-remove-image"
                            class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm shadow-md hover:bg-red-600">
                            ✕
                        </button>
                    </div>

                    {{-- Existing image (edit mode) --}}
                    @if(isset($model) && $model->exists && $model->product_image)
                        <div id="existing-image" class="relative mt-2">
                            <img src="{{ Storage::disk('public')->url($model->product_image) }}" alt="{{ $model->product_nama }}" class="w-full max-h-48 object-contain rounded-lg border border-outline-variant">
                            <button type="button" id="btn-remove-existing"
                                class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm shadow-md hover:bg-red-600">
                                ✕
                            </button>
                        </div>
                    @endif

                    {{-- Hidden file input (no capture — showImageChooser dialog will appear) --}}
                    <input type="file" id="input-product-image" name="product_image"
                        accept="image/jpeg,image/png,image/webp" class="hidden">
                    <input type="hidden" id="input-remove" name="remove_image" value="0">
                </div>
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    {{-- Debug Console --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4 mt-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">terminal</span>
                Console Log
            </h3>
            <button onclick="document.getElementById('log-panel').innerHTML=''" class="text-xs px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest transition-colors">Clear</button>
        </div>
        <div id="log-panel" class="bg-black rounded-lg p-3 h-40 overflow-x-hidden overflow-y-auto font-mono text-xs text-green-400 break-all">
            <div class="text-gray-500">Ready. Tap a button to upload an image.</div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        // ── Console log helper ──
        function log(msg, type) {
            type = type || 'info';
            var panel = document.getElementById('log-panel');
            if (!panel) return;
            var time = new Date().toLocaleTimeString();
            var color = type === 'error' ? '#ef4444' : type === 'success' ? '#22c55e' : type === 'warn' ? '#eab308' : '#4ade80';
            panel.innerHTML += '<div><span style="color:#6b7280">[' + time + ']</span> <span style="color:' + color + '">' + msg + '</span></div>';
            panel.scrollTop = panel.scrollHeight;
        }

        var btnCamera    = document.getElementById('btn-camera-capture');
        var btnGallery   = document.getElementById('btn-gallery');
        var btnBrowse    = document.getElementById('btn-browse-file');
        var inputImage   = document.getElementById('input-product-image');
        var preview      = document.getElementById('image-preview');
        var previewImg   = document.getElementById('preview-img');
        var btnRemove    = document.getElementById('btn-remove-image');
        var btnRemoveExisting = document.getElementById('btn-remove-existing');
        var inputRemove  = document.getElementById('input-remove');
        var existingImage = document.getElementById('existing-image');

        var hasNativeBridge = typeof NativeBridge !== 'undefined'
            && typeof NativeBridge.captureCamera === 'function';

        log('hasNativeBridge = ' + hasNativeBridge, hasNativeBridge ? 'success' : 'warn');
        log('User-agent: ' + navigator.userAgent.substring(0, 80), 'info');

        function dataURLtoFile(dataurl, filename) {
            var arr  = dataurl.split(',');
            var mime = (arr[0].match(/:(.*?);/) || [])[1] || 'image/jpeg';
            var bstr = atob(arr[1]);
            var n    = bstr.length;
            var u8   = new Uint8Array(n);
            while (n--) u8[n] = bstr.charCodeAt(n);
            return new File([u8], filename, { type: mime });
        }

        function setFileOnInput(file) {
            var dt = new DataTransfer();
            dt.items.add(file);
            inputImage.files = dt.files;
            log('setFileOnInput → ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)', 'success');
        }

        function showPreview(src) {
            previewImg.src = src;
            preview.classList.remove('hidden');
            btnCamera.classList.add('hidden');
            btnGallery.classList.add('hidden');
            btnBrowse.classList.add('hidden');
            log('showPreview → image displayed (' + Math.round(src.length / 1024) + ' KB)', 'success');
        }

        function hidePreview() {
            inputImage.value = '';
            preview.classList.add('hidden');
            previewImg.src = '';
            btnCamera.classList.remove('hidden');
            btnGallery.classList.remove('hidden');
            btnBrowse.classList.remove('hidden');
            log('hidePreview → image removed', 'info');
        }

        // ── NativeBridge callbacks ──
        window.onImageCaptured = function(base64) {
            log('onImageCaptured → (' + Math.round((base64||'').length/1024) + ' KB)', 'info');
            if (!base64 || (base64.indexOf('error') !== -1 && base64.indexOf('data:') === -1)) {
                log('onImageCaptured → error: ' + base64, 'error');
                return;
            }
            try {
                var file = dataURLtoFile(base64, 'camera_' + Date.now() + '.jpg');
                setFileOnInput(file);
                showPreview(base64);
            } catch(e) { log('Camera error: ' + e.message, 'error'); }
        };
        window.onImagePicked = function(base64) {
            log('onImagePicked → (' + Math.round((base64||'').length/1024) + ' KB)', 'info');
            if (!base64 || (base64.indexOf('error') !== -1 && base64.indexOf('data:') === -1)) {
                log('onImagePicked → error: ' + base64, 'error');
                return;
            }
            try {
                var ext = (base64.match(/^data:image\/(\w+)/)||[])[1]||'jpeg';
                var file = dataURLtoFile(base64, 'gallery_' + Date.now() + '.' + ext);
                setFileOnInput(file);
                showPreview(base64);
            } catch(e) { log('Gallery error: ' + e.message, 'error'); }
        };

        btnCamera.addEventListener('click', function() {
            log('btn-camera clicked', 'info');
            if (hasNativeBridge) {
                log('→ NativeBridge.captureCamera()', 'info');
                NativeBridge.captureCamera();
            } else {
                log('→ input.click() + capture [fallback]', 'warn');
                inputImage.setAttribute('capture','environment');
                inputImage.click();
                setTimeout(function(){ inputImage.removeAttribute('capture'); }, 2000);
            }
        });

        btnGallery.addEventListener('click', function() {
            log('btn-gallery clicked', 'info');
            if (hasNativeBridge) {
                log('→ NativeBridge.pickFromGallery()', 'info');
                NativeBridge.pickFromGallery();
            } else {
                log('→ input.click() no capture [fallback]', 'warn');
                inputImage.removeAttribute('capture');
                inputImage.click();
            }
        });

        btnBrowse.addEventListener('click', function() {
            log('btn-browse clicked', 'info');
            log('→ input.click() [approach 1]', 'info');
            inputImage.removeAttribute('capture');
            inputImage.click();
        });

        inputImage.addEventListener('change', function() {
            log('input change → files=' + this.files.length, 'info');
            if (this.files[0]) {
                log('File: ' + this.files[0].name + ' (' + Math.round(this.files[0].size/1024) + ' KB)', 'success');
                var reader = new FileReader();
                reader.onload = function(e) { showPreview(e.target.result); };
                reader.onerror = function() { log('FileReader error', 'error'); };
                reader.readAsDataURL(this.files[0]);
            }
        });

        btnRemove.addEventListener('click', function() {
            log('btn-remove clicked', 'info');
            hidePreview();
        });
        if (btnRemoveExisting) {
            btnRemoveExisting.addEventListener('click', function() {
                log('btn-remove-existing clicked', 'info');
                existingImage.classList.add('hidden');
                inputRemove.value = '1';
            });
        }
    })();
    </script>
    @endpush
</x-layouts::app>
