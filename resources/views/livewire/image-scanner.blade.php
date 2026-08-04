<div class="max-w-2xl mx-auto p-4 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Image Capture & QR Scanner</h2>
            <p class="text-sm text-base-content/60">Capture a photo or scan a QR code using your device camera.</p>
        </div>
        <button class="btn btn-xs btn-soft" wire:click="clearResults">
            <span class="icon-[tabler--trash] size-3.5"></span>
            Clear
        </button>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <button class="btn btn-primary gap-2" wire:click="capturePhoto">
            <span class="icon-[tabler--camera] size-4"></span>
            Capture Photo
        </button>
        <button class="btn btn-outline gap-2" wire:click="scanQRCode">
            <span class="icon-[tabler--qrcode] size-4"></span>
            Scan QR Code
        </button>
    </div>

    @if($statusMessage)
        <div class="alert alert-{{ $statusType }} py-2">
            <span class="icon-[tabler--{{ $statusType === 'success' ? 'check' : 'alert-triangle' }}] size-4"></span>
            <span class="text-sm">{{ $statusMessage }}</span>
        </div>
    @endif

    @if($capturedImagePath)
        <div class="bg-base-100 border border-base-300 rounded-box p-4 space-y-2">
            <h3 class="text-sm font-bold">Captured Photo</h3>
            <img src="{{ route('image-scanner.photo', ['path' => ltrim($capturedImagePath, '/')]) }}"
                 alt="Captured photo"
                 class="w-full max-h-80 object-contain rounded-lg bg-base-200"
                 onerror="this.src='https://placehold.co/400x300/f1f5f9/475569?text=Image+not+available'">
            <p class="text-xs text-base-content/60 break-all">{{ $capturedImagePath }}</p>
        </div>
    @endif

    @if($scannedCode)
        <div class="bg-base-100 border border-base-300 rounded-box p-4 space-y-2">
            <h3 class="text-sm font-bold">Scanned Result</h3>
            <div class="bg-base-200 rounded-lg p-3">
                <p class="text-sm font-mono break-all">{{ $scannedCode }}</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-base-content/60">
                <span class="badge badge-xs badge-primary">{{ strtoupper($scanFormat) }}</span>
                <span>Format detected</span>
            </div>
            <button class="btn btn-xs btn-soft gap-1" wire:click="navigator.clipboard.writeText('{{ $scannedCode }}')">
                <span class="icon-[tabler--copy] size-3"></span>
                Copy to clipboard
            </button>
        </div>
    @endif
</div>
