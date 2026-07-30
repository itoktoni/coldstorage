<?php

namespace App\Livewire;

use Livewire\Component;
use Native\Mobile\Camera;
use Native\Mobile\Scanner;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Events\Camera\PhotoCancelled;
use Native\Mobile\Events\Scanner\CodeScanned;
use Native\Mobile\Events\Scanner\ScannerCancelled;
use Native\Mobile\Attributes\OnNative;

class ImageScanner extends Component
{
    public ?string $capturedImagePath = null;
    public ?string $scannedCode = null;
    public ?string $scanFormat = null;
    public ?string $statusMessage = null;
    public string $statusType = 'info';

    public function capturePhoto(): void
    {
        $this->reset(['capturedImagePath', 'scannedCode', 'scanFormat', 'statusMessage', 'statusType']);
        Camera::getPhoto()
            ->id('capture-' . uniqid())
            ->remember()
            ->start();
    }

    public function scanQRCode(): void
    {
        $this->reset(['capturedImagePath', 'scannedCode', 'scanFormat', 'statusMessage', 'statusType']);
        Scanner::scan()
            ->id('scan-' . uniqid())
            ->prompt('Align QR code within the frame')
            ->start();
    }

    #[OnNative(PhotoTaken::class)]
    public function onPhotoTaken(PhotoTaken $event): void
    {
        $this->capturedImagePath = $event->path;
        $this->scannedCode = null;
        $this->scanFormat = null;
        $this->statusMessage = 'Photo captured successfully.';
        $this->statusType = 'success';
    }

    #[OnNative(PhotoCancelled::class)]
    public function onPhotoCancelled(PhotoCancelled $event): void
    {
        $this->statusMessage = 'Photo capture was cancelled.';
        $this->statusType = 'warning';
    }

    #[OnNative(CodeScanned::class)]
    public function onCodeScanned(CodeScanned $event): void
    {
        $this->scannedCode = $event->data;
        $this->scanFormat = $event->format;
        $this->capturedImagePath = null;
        $this->statusMessage = 'QR code scanned successfully.';
        $this->statusType = 'success';
    }

    #[OnNative(ScannerCancelled::class)]
    public function onScannerCancelled(ScannerCancelled $event): void
    {
        $this->statusMessage = 'QR scan was cancelled.';
        $this->statusType = 'warning';
    }

    public function clearResults(): void
    {
        $this->reset(['capturedImagePath', 'scannedCode', 'scanFormat', 'statusMessage', 'statusType']);
    }

    public function render()
    {
        return view('livewire.image-scanner');
    }
}
