# NativeBridge API Reference

NativeBridge adalah JavaScript Interface yang menghubungkan WebView dengan native Android. Akses via `window.NativeBridge` atau `NativeBridge`.

---

## Device Info

### `NativeBridge.getDeviceModel(): String`
Mengembalikan model device (contoh: "Pixel 7", "Samsung S23").
```javascript
const model = NativeBridge.getDeviceModel();
console.log("Device:", model);
```

### `NativeBridge.getDeviceBrand(): String`
Mengembalikan brand device (contoh: "Google", "Samsung").
```javascript
const brand = NativeBridge.getDeviceBrand();
```

### `NativeBridge.getDeviceManufacturer(): String`
Mengembalikan manufacturer device (contoh: "Google", "Samsung Electronics").
```javascript
const manufacturer = NativeBridge.getDeviceManufacturer();
```

### `NativeBridge.getSdkVersion(): Number`
Mengembalikan Android SDK version (contoh: 34 untuk Android 14).
```javascript
const sdk = NativeBridge.getSdkVersion();
if (sdk >= 33) {
    console.log("Android 13+");
}
```

### `NativeBridge.getAppVersion(): String`
Mengembalikan versi aplikasi (contoh: "1.0.0").
```javascript
const version = NativeBridge.getAppVersion();
```

### `NativeBridge.getPackageName(): String`
Mengembalikan package name aplikasi (contoh: "com.itoktoni.coldstorage").
```javascript
const pkg = NativeBridge.getPackageName();
```

---

## UI Feedback

### `NativeBridge.showToast(message: String)`
Menampilkan Toast message.
```javascript
NativeBridge.showToast("File saved successfully!");
NativeBridge.showToast("Error: " + error.message);
```

### `NativeBridge.vibrate(durationMs: Number)`
Getar device selama `durationMs` milidetik.
```javascript
NativeBridge.vibrate(100);   // Getar 100ms (ringan)
NativeBridge.vibrate(500);   // Getar 500ms (sedang)
NativeBridge.vibrate(1000);  // Getar 1 detik (keras)
```

---

## Network

### `NativeBridge.isConnected(): Boolean`
Cek apakah device terhubung ke internet.
```javascript
if (NativeBridge.isConnected()) {
    console.log("Online");
    load_data();
} else {
    NativeBridge.showToast("No internet connection");
}
```

### `NativeBridge.startNetworkCallback()`
Mulai monitoring perubahan koneksi secara real-time. Status akan dikirim ke callback `onNetworkChanged`.
```javascript
// Define callback
window.onNetworkChanged = function(status) {
    if (status === 'online') {
        console.log('Back online!');
        // Reload page atau fetch data
        location.reload();
    } else {
        console.log('You are offline');
        // Tampilkan offline message
    }
};

// Start monitoring
NativeBridge.startNetworkCallback();
```

**Status values:**
- `"online"` — Device terhubung ke internet
- `"offline"` — Device tidak terhubung

### `NativeBridge.stopNetworkCallback()`
Hentikan monitoring koneksi.
```javascript
NativeBridge.stopNetworkCallback();
```

**Contoh lengkap:**
```html
<script>
    // Start monitoring saat page load
    window.addEventListener('load', function() {
        NativeBridge.startNetworkCallback();
    });

    // Handle perubahan status
    window.onNetworkChanged = function(status) {
        const indicator = document.getElementById('network-status');
        if (status === 'online') {
            indicator.textContent = 'Online';
            indicator.style.color = 'green';
        } else {
            indicator.textContent = 'Offline';
            indicator.style.color = 'red';
        }
    };
</script>

<div id="network-status">Checking...</div>
```

---

## Print & Save

### `NativeBridge.printPage()`
Buka print dialog untuk mencetak halaman WebView.
```javascript
NativeBridge.printPage();
```
**Behavior:** Membuka Android print dialog. User bisa pilih printer atau "Save as PDF".

### `NativeBridge.saveAsPdf()`
Buka print dialog dengan instruksi save as PDF.
```javascript
NativeBridge.saveAsPdf();
```
**Behavior:** Membuka print dialog + Toast "Select 'Save as PDF' to save".

---

## Share & Image

### `NativeBridge.shareAsImage()`
Capture WebView sebagai gambar dan share ke aplikasi lain.
```javascript
NativeBridge.shareAsImage();
```
**Behavior:**
1. Capture halaman WebView ke Bitmap
2. Simpan ke cache sebagai PNG
3. Buka share dialog (WhatsApp, Email, dll)

**Contoh penggunaan:**
```javascript
// Share bukti transaksi
function shareReceipt() {
    NativeBridge.shareAsImage();
}

// Share dengan caption
<button onclick="NativeBridge.shareAsImage()">Share Gambar</button>
```

### `NativeBridge.saveAsImage()`
Capture WebView sebagai gambar dan simpan ke Gallery.
```javascript
NativeBridge.saveAsImage();
```
**Behavior:**
1. Capture halaman WebView ke Bitmap
2. Simpan ke Pictures/ColdStorage/ (Gallery)
3. Toast "Image saved to Gallery"

**Lokasi file:**
- Android 10+: `Pictures/ColdStorage/ColdStorage_{timestamp}.png`
- Android 9-: `Pictures/ColdStorage/ColdStorage_{timestamp}.png`

---

## Camera & Gallery

### `NativeBridge.captureCamera()`
Buka kamera untuk ambil foto. Hasil berupa base64 data URL dikirim ke callback `onImageCaptured`.
```javascript
window.onImageCaptured = function(dataUrl) {
    // dataUrl format: "data:image/jpeg;base64,/9j/4AAQ..."
    console.log(dataUrl);
    
    // Kirim sebagai POST file input
    sendToServer(dataUrl);
};

NativeBridge.captureCamera();
```

### `NativeBridge.pickFromGallery()`
Buka gallery untuk pilih gambar. Hasil berupa base64 data URL dikirim ke callback `onImagePicked`.
```javascript
window.onImagePicked = function(dataUrl) {
    // dataUrl format: "data:image/png;base64,iVBOR..."
    console.log(dataUrl);
    
    // Kirim sebagai POST file input
    sendToServer(dataUrl);
};

NativeBridge.pickFromGallery();
```

### `NativeBridge.captureCameraForForm()`
Buka kamera untuk form `<input type="file">`. Hasil langsung di-post ke form sebagai file input.
```javascript
// Panggil sebelum form submit, atau sebagai override input file
NativeBridge.captureCameraForForm();
```
**Behavior:** Membuka kamera → Foto diambil → Otomatis mengisi input file di form → Form bisa di-submit dengan gambar.

### `NativeBridge.pickFromGalleryForForm()`
Buka gallery untuk form `<input type="file">`. Hasil langsung di-post ke form sebagai file input.
```javascript
// Panggil sebelum form submit, atau sebagai override input file
NativeBridge.pickFromGalleryForForm();
```
**Behavior:** Membuka gallery → Gambar dipilih → Otomatis mengisi input file di form → Form bisa di-submit dengan gambar.

### Cara Kirim sebagai POST File Input
```javascript
function dataUrlToFile(dataUrl, filename) {
    const arr = dataUrl.split(',');
    const mime = arr[0].match(/:(.*?);/)[1];
    const bstr = atob(arr[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], filename, { type: mime });
}

function sendToServer(dataUrl) {
    const file = dataUrlToFile(dataUrl, 'photo.jpg');
    const formData = new FormData();
    formData.append('image', file);
    
    fetch('/upload', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
      .then(data => console.log('Uploaded:', data));
}

//Contoh: Pilih dari gallery lalu upload
NativeBridge.pickFromGallery();
window.onImagePicked = function(dataUrl) {
    sendToServer(dataUrl);
};
```

**Error handling:**
```javascript
window.onImageCaptured = function(data) {
    if (data.startsWith('{') && data.includes('"error"')) {
        const error = JSON.parse(data);
        console.error('Camera error:', error.error);
    } else {
        // Success - data is base64 data URL
        displayImage(data);
    }
};
```

---

## Location

### `NativeBridge.hasLocationPermission(): Boolean`
Cek apakah izin lokasi sudah di-grant.
```javascript
if (NativeBridge.hasLocationPermission()) {
    NativeBridge.getCurrentLocation();
} else {
    NativeBridge.requestLocationPermission();
}
```

### `NativeBridge.requestLocationPermission()`
Request izin lokasi ke user. Hasil dikirim ke callback `onLocationSuccess` atau `onLocationError`.
```javascript
NativeBridge.requestLocationPermission();
```

### `NativeBridge.getCurrentLocation()`
Mendapatkan lokasi device saat ini (latitude, longitude, dll).
```javascript
// Define callback functions
window.onLocationSuccess = function(data) {
    const location = JSON.parse(data);
    console.log("Lat:", location.latitude);
    console.log("Lng:", location.longitude);
    console.log("Accuracy:", location.accuracy);
    console.log("Speed:", location.speed);
};

window.onLocationError = function(error) {
    console.error("Location error:", error);
    NativeBridge.showToast("Location error: " + error);
};

// Request location
if (NativeBridge.hasLocationPermission()) {
    NativeBridge.getCurrentLocation();
} else {
    NativeBridge.requestLocationPermission();
}
```

**Response JSON (`onLocationSuccess`):**
```json
{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "altitude": 10.5,
    "accuracy": 3.0,
    "speed": 0.0,
    "bearing": 0.0,
    "timestamp": 1691234567890
}
```

**Error (`onLocationError`):**
- `"Location permission not granted"`
- `"Location services are disabled. Please enable GPS."`
- `"Location is null. Make sure GPS has a fix."`

---

## Full Device Info

### `NativeBridge.getDeviceInfo(): String`
Mendapatkan semua info device dalam format JSON.
```javascript
const deviceInfo = JSON.parse(NativeBridge.getDeviceInfo());
console.log("Model:", deviceInfo.model);
console.log("Android:", deviceInfo.androidVersion);
console.log("Screen:", deviceInfo.screenWidth + "x" + deviceInfo.screenHeight);
console.log("Battery:", deviceInfo.batteryLevel + "%");
```

**Response JSON:**
```json
{
    "model": "Pixel 7",
    "brand": "Google",
    "manufacturer": "Google",
    "device": "panther",
    "product": "panther",
    "hardware": "raven",
    "androidVersion": "14",
    "sdkVersion": 34,
    "buildId": "AP2A.240805.005",
    "buildDisplay": "AP2A.240805.005",
    "appVersion": "1.0.0",
    "packageName": "com.itoktoni.coldstorage",
    "screenWidth": 1080,
    "screenHeight": 2400,
    "screenDensity": 420,
    "screenDensityDp": 2.625,
    "locale": "id_ID",
    "batteryLevel": 85,
    "isGpsEnabled": true,
    "isNetworkEnabled": true,
    "totalMemory": 536870912,
    "availableMemory": 268435456,
    "availableProcessors": 8
}
```

---

## Complete Usage Example

```html
<!DOCTYPE html>
<html>
<head>
    <title>ColdStorage</title>
</head>
<body>
    <h1>My App</h1>
    
    <!-- Device Info -->
    <button onclick="showDeviceInfo()">Device Info</button>
    <button onclick="showFullDeviceInfo()">Full Device Info</button>
    
    <!-- Location -->
    <button onclick="getLocation()">Get Location</button>
    
    <!-- Actions -->
    <button onclick="NativeBridge.printPage()">Print</button>
    <button onclick="NativeBridge.saveAsPdf()">Save PDF</button>
    <button onclick="NativeBridge.shareAsImage()">Share</button>
    <button onclick="NativeBridge.saveAsImage()">Save Image</button>
    
    <script>
        // Callback functions for location
        window.onLocationSuccess = function(data) {
            const location = JSON.parse(data);
            NativeBridge.showToast(
                "Lat: " + location.latitude + "\n" +
                "Lng: " + location.longitude
            );
        };
        
        window.onLocationError = function(error) {
            NativeBridge.showToast("Error: " + error);
        };
        
        function showDeviceInfo() {
            const info = `
                Model: ${NativeBridge.getDeviceModel()}
                Brand: ${NativeBridge.getDeviceBrand()}
                Android: ${NativeBridge.getSdkVersion()}
                App Version: ${NativeBridge.getAppVersion()}
                Online: ${NativeBridge.isConnected()}
            `;
            NativeBridge.showToast(info);
        }
        
        function showFullDeviceInfo() {
            const info = JSON.parse(NativeBridge.getDeviceInfo());
            NativeBridge.showToast(
                "Model: " + info.model + "\n" +
                "Android: " + info.androidVersion + "\n" +
                "Battery: " + info.batteryLevel + "%"
            );
        }
        
        function getLocation() {
            if (NativeBridge.hasLocationPermission()) {
                NativeBridge.getCurrentLocation();
            } else {
                NativeBridge.requestLocationPermission();
            }
        }
        
        // Check network before action
        function safeAction(action) {
            if (!NativeBridge.isConnected()) {
                NativeBridge.showToast("No internet connection");
                return;
            }
            action();
        }
        
        // Usage with network check
        function shareSafe() {
            safeAction(() => NativeBridge.shareAsImage());
        }
    </script>
</body>
</html>
```

---

## Notes

1. **Thread:** Semua fungsi NativeBridge berjalan di background thread. UI updates (Toast) otomatis di-post ke main thread.

2. **Permissions:**
   - `CAMERA` - Diperlukan untuk akses kamera (runtime permission)
   - `ACCESS_FINE_LOCATION` / `ACCESS_COARSE_LOCATION` - Untuk getCurrentLocation (runtime permission)
   - `WRITE_EXTERNAL_STORAGE` - Hanya untuk Android 9 ke bawah (maxSdkVersion=28)
   - `INTERNET` - Untuk load website

3. **Share Image:** File disimpan di cache directory dan akan di-cleanup otomatis oleh system.

4. **Save Image:** File disimpan permanent di Gallery (Pictures/ColdStorage/).

5. **Print/Save PDF:** Menggunakan Android Print Framework. User harus pilih printer atau "Save as PDF" di dialog.

6. **Location:** Menggunakan FusedLocationProviderClient untuk akurasi tinggi. GPS harus aktif.

---

## Error Handling

Semua fungsi sudah memiliki error handling internal dan akan menampilkan Toast jika terjadi error. Contoh:

```javascript
// Tidak perlu try-catch manual
NativeBridge.shareAsImage();  // Error ditangani otomatis, Toast muncul

// Tapi bisa cek connectivity dulu
if (NativeBridge.isConnected()) {
    NativeBridge.shareAsImage();
} else {
    NativeBridge.showToast("Offline - cannot share");
}
```

---

## Push Notifications

App mendukung 5 metode push notification:

### 1. Firebase Cloud Messaging (FCM)

**Setup:** Buat Firebase project di console.firebase.google.com, download `google-services.json` ke folder `app/`.

```javascript
// Dapatkan FCM token
window.onFcmToken = function(token) {
    console.log('FCM Token:', token);
    // Kirim token ke server untuk registrasi
    fetch('/register-device', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: token })
    });
};
NativeBridge.getFcmToken();

// Handle notifikasi saat app terbuka
window.onPushNotification = function(data) {
    const payload = JSON.parse(data);
    console.log(payload.title, payload.body);
};
```

**Flow:**
```
Server → FCM → FcmService → onPushNotification callback
```

### 2. WebSocket (Real-time)

**Setup:** Jalankan WebSocket server (Node.js, Go, dll).

```javascript
// Koneksi ke WebSocket server
NativeBridge.connectWebSocket('wss://your-server.com/ws');

// Handle notifikasi
window.onPushNotification = function(data) {
    const payload = JSON.parse(data);
    NativeBridge.showToast(payload.title + ': ' + payload.body);
};

// Kirim pesan
NativeBridge.sendWebSocketMessage(JSON.stringify({
    title: 'Hello',
    body: 'Test message'
}));

// Handle status koneksi
window.onNetworkStatus = function(status) {
    console.log('WebSocket status:', status);
};

// Disconnect
NativeBridge.disconnectWebSocket();
```

**Status values:**
- `"websocket_connected"` — Terhubung ke server
- `"websocket_disconnected"` — Terputus dari server
- `"websocket_error"` — Error koneksi

### 3. WorkManager + Polling

**Setup:** Siapkan endpoint JSON yang mengembalikan notifikasi.

```javascript
// Mulai polling tiap 15 menit
NativeBridge.startPolling('https://your-server.com/notifications', 15);

// Mulai polling tiap 5 menit
NativeBridge.startPolling('https://your-server.com/notifications', 5);

// Hentikan polling
NativeBridge.stopPolling();

// Handle notifikasi
window.onPushNotification = function(data) {
    const payload = JSON.parse(data);
    console.log(payload.title, payload.body);
};
```

**Endpoint response format:**
```json
{
    "title": "New Update",
    "body": "You have a new message"
}
```

### 4. MQTT

**Setup:** Jalankan MQTT broker (Mosquitto, HiveMQ, dll).

```javascript
// Koneksi ke MQTT broker
NativeBridge.connectMqtt('tcp://broker.example.com:1883', 'client-id', 'notifications/topic');

// Subscribe ke topic (otomatis saat connect)
// Handle notifikasi
window.onPushNotification = function(data) {
    const payload = JSON.parse(data);
    console.log(payload.title, payload.body);
};

// Publish pesan
NativeBridge.publishMqtt('notifications/topic', JSON.stringify({
    title: 'Alert',
    body: 'Server maintenance at 10 PM'
}));

// Handle status koneksi
window.onNetworkStatus = function(status) {
    console.log('MQTT status:', status);
};

// Disconnect
NativeBridge.disconnectMqtt();
```

**Status values:**
- `"mqtt_connected"` — Terhubung ke broker
- `"mqtt_disconnected"` — Terputus dari broker
- `"mqtt_error"` — Error koneksi

### 5. Local Scheduled Notifications

**Setup:** Tidak perlu server. Notifikasi dijadwalkan dari device.

```javascript
// Jadwalkan notifikasi harian jam 08:00
NativeBridge.scheduleLocalNotification(
    'Daily Reminder',
    'Time to check your tasks!',
    8,  // jam
    0,  // menit
    true // repeat daily
);

// Jadwalkan notifikasi sekali jam 14:30
NativeBridge.scheduleLocalNotification(
    'Meeting',
    'Team meeting in 30 minutes',
    14,
    30,
    false // single execution
);

// Batalkan notifikasi tertentu (berdasarkan request code)
NativeBridge.cancelLocalNotification(12345);

// Batalkan semua notifikasi
NativeBridge.cancelAllLocalNotifications();

// Handle notifikasi
window.onPushNotification = function(data) {
    const payload = JSON.parse(data);
    console.log(payload.title, payload.body);
};
```

### Perbandingan Metode

| Metode | Real-time | Perlu Server | Setup | Best For |
|--------|-----------|--------------|-------|----------|
| **FCM** | ✅ Ya | ✅ Ya | Sedang | Production apps |
| **WebSocket** | ✅ Ya | ✅ Ya | Sedang | Real-time chat |
| **Polling** | ❌ Interval | ✅ Ya | Mudah | Simple notifications |
| **MQTT** | ✅ Ya | ✅ Ya | Sedang | IoT devices |
| **Local** | ❌ Lokal | ❌ Tidak | Mudah | Reminders, alarms |
