<x-layouts::app title="Settings - NativeBridge Test">
    <x-breadcrumb :items="[['url' => route('dashboard'), 'label' => 'Dashboard'], ['url' => '', 'label' => 'NativeBridge Test']]" />

    {{-- Log Panel --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-headline-sm text-headline-sm text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">terminal</span>
                Console Log
            </h3>
            <button onclick="clearLog()" class="text-xs px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest transition-colors">Clear</button>
        </div>
        <div id="log-panel" class="bg-black rounded-lg p-3 h-40 overflow-y-auto font-mono text-xs text-green-400">
            <div class="text-gray-500">Ready. Click any button to test NativeBridge functions.</div>
        </div>
    </div>

    {{-- 1. Device Info --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">info</span>
            Device Info
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <button onclick="testGetDeviceModel()" class="btn-test">
                <span class="material-symbols-outlined text-sm">phone_iphone</span>
                getDeviceModel()
            </button>
            <button onclick="testGetDeviceBrand()" class="btn-test">
                <span class="material-symbols-outlined text-sm">branding_watermark</span>
                getDeviceBrand()
            </button>
            <button onclick="testGetDeviceManufacturer()" class="btn-test">
                <span class="material-symbols-outlined text-sm">factory</span>
                getManufacturer()
            </button>
            <button onclick="testGetSdkVersion()" class="btn-test">
                <span class="material-symbols-outlined text-sm">android</span>
                getSdkVersion()
            </button>
            <button onclick="testGetAppVersion()" class="btn-test">
                <span class="material-symbols-outlined text-sm">new_releases</span>
                getAppVersion()
            </button>
            <button onclick="testGetPackageName()" class="btn-test">
                <span class="material-symbols-outlined text-sm">package_2</span>
                getPackageName()
            </button>
            <button onclick="testGetDeviceInfo()" class="btn-test col-span-2 sm:col-span-3">
                <span class="material-symbols-outlined text-sm">devices</span>
                getDeviceInfo() (Full)
            </button>
        </div>
    </div>

    {{-- 2. UI Feedback --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">notifications</span>
            UI Feedback
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testShowToast()" class="btn-test">
                <span class="material-symbols-outlined text-sm">message</span>
                showToast()
            </button>
            <button onclick="testVibrate(100)" class="btn-test">
                <span class="material-symbols-outlined text-sm">vibration</span>
                vibrate(100ms)
            </button>
            <button onclick="testVibrate(500)" class="btn-test">
                <span class="material-symbols-outlined text-sm">vibration</span>
                vibrate(500ms)
            </button>
            <button onclick="testVibrate(1000)" class="btn-test">
                <span class="material-symbols-outlined text-sm">vibration</span>
                vibrate(1000ms)
            </button>
        </div>
    </div>

    {{-- 3. Network --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">wifi</span>
            Network
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testIsConnected()" class="btn-test">
                <span class="material-symbols-outlined text-sm">wifi_find</span>
                isConnected()
            </button>
            <button onclick="testStartNetworkCallback()" class="btn-test">
                <span class="material-symbols-outlined text-sm">wifi_find</span>
                startNetworkCallback()
            </button>
            <button onclick="testStopNetworkCallback()" class="btn-test">
                <span class="material-symbols-outlined text-sm">wifi_off</span>
                stopNetworkCallback()
            </button>
        </div>
        <div id="network-status" class="mt-3 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface-variant text-sm font-mono">
            Network status: Checking...
        </div>
    </div>

    {{-- 4. Print & Save --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">print</span>
            Print & Save
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testPrintPage()" class="btn-test">
                <span class="material-symbols-outlined text-sm">print</span>
                printPage()
            </button>
            <button onclick="testSaveAsPdf()" class="btn-test">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                saveAsPdf()
            </button>
        </div>
    </div>

    {{-- 5. Share & Image --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">share</span>
            Share & Image
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testShareAsImage()" class="btn-test">
                <span class="material-symbols-outlined text-sm">share</span>
                shareAsImage()
            </button>
            <button onclick="testSaveAsImage()" class="btn-test">
                <span class="material-symbols-outlined text-sm">save</span>
                saveAsImage()
            </button>
        </div>
    </div>

    {{-- 6. Camera & Gallery --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">photo_camera</span>
            Camera & Gallery
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testCaptureCamera()" class="btn-test">
                <span class="material-symbols-outlined text-sm">photo_camera</span>
                captureCamera()
            </button>
            <button onclick="testPickFromGallery()" class="btn-test">
                <span class="material-symbols-outlined text-sm">photo_library</span>
                pickFromGallery()
            </button>
            <button onclick="testCaptureCameraForForm()" class="btn-test">
                <span class="material-symbols-outlined text-sm">add_a_photo</span>
                captureCameraForForm()
            </button>
            <button onclick="testPickFromGalleryForForm()" class="btn-test">
                <span class="material-symbols-outlined text-sm">add_photo_alternate</span>
                pickFromGalleryForForm()
            </button>
        </div>
        <div id="camera-preview" class="mt-3 hidden">
            <p class="text-xs text-on-surface-variant mb-2">Captured image:</p>
            <img id="captured-image" class="w-full max-h-60 object-contain rounded-lg border border-outline-variant" />
        </div>
    </div>

    {{-- 7. Location --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">location_on</span>
            Location
        </h3>
        <div class="grid grid-cols-2 gap-2">
            <button onclick="testHasLocationPermission()" class="btn-test">
                <span class="material-symbols-outlined text-sm">admin_panel_settings</span>
                hasLocationPermission()
            </button>
            <button onclick="testRequestLocationPermission()" class="btn-test">
                <span class="material-symbols-outlined text-sm">lock_open</span>
                requestLocationPermission()
            </button>
            <button onclick="testGetCurrentLocation()" class="btn-test col-span-2">
                <span class="material-symbols-outlined text-sm">my_location</span>
                getCurrentLocation()
            </button>
        </div>
        <div id="location-result" class="mt-3 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface-variant text-sm font-mono hidden">
        </div>
    </div>

    {{-- 8. Push Notifications --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 mb-4">
        <h3 class="font-headline-sm text-headline-sm text-on-surface pb-3 mb-3 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">notifications_active</span>
            Push Notifications
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <button onclick="testGetFcmToken()" class="btn-test">
                <span class="material-symbols-outlined text-sm">vpn_key</span>
                getFcmToken()
            </button>
            <button onclick="testConnectWebSocket()" class="btn-test">
                <span class="material-symbols-outlined text-sm">cable</span>
                connectWebSocket()
            </button>
            <button onclick="testDisconnectWebSocket()" class="btn-test">
                <span class="material-symbols-outlined text-sm">link_off</span>
                disconnectWebSocket()
            </button>
            <button onclick="testStartPolling()" class="btn-test">
                <span class="material-symbols-outlined text-sm">sync</span>
                startPolling()
            </button>
            <button onclick="testStopPolling()" class="btn-test">
                <span class="material-symbols-outlined text-sm">sync_disabled</span>
                stopPolling()
            </button>
            <button onclick="testConnectMqtt()" class="btn-test">
                <span class="material-symbols-outlined text-sm">cell_tower</span>
                connectMqtt()
            </button>
            <button onclick="testDisconnectMqtt()" class="btn-test">
                <span class="material-symbols-outlined text-sm">signal_cellular_off</span>
                disconnectMqtt()
            </button>
            <button onclick="testScheduleLocalNotification()" class="btn-test">
                <span class="material-symbols-outlined text-sm">alarm_add</span>
                scheduleLocal()
            </button>
            <button onclick="testCancelAllLocalNotifications()" class="btn-test">
                <span class="material-symbols-outlined text-sm">alarm_off</span>
                cancelAllLocal()
            </button>
        </div>
    </div>

    <style>
        .btn-test {
            @apply flex items-center justify-center gap-2 px-3 py-2.5 bg-surface-container-high text-on-surface text-sm font-medium rounded-lg hover:bg-surface-container-highest transition-colors active:scale-95;
        }
        .btn-test:active {
            transform: scale(0.97);
        }
    </style>

    <script>
        function log(msg, type = 'info') {
            const panel = document.getElementById('log-panel');
            const time = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ef4444' : type === 'success' ? '#22c55e' : type === 'warn' ? '#eab308' : '#4ade80';
            panel.innerHTML += `<div><span style="color:#6b7280">[${time}]</span> <span style="color:${color}">${msg}</span></div>`;
            panel.scrollTop = panel.scrollHeight;
        }

        function clearLog() {
            document.getElementById('log-panel').innerHTML = '<div class="text-gray-500">Log cleared.</div>';
        }

        function hasNativeBridge() {
            if (typeof NativeBridge === 'undefined') {
                log('NativeBridge is not available (not running in Android WebView)', 'error');
                return false;
            }
            return true;
        }

        // === Device Info ===
        function testGetDeviceModel() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getDeviceModel();
            log('getDeviceModel() → ' + result, 'success');
        }

        function testGetDeviceBrand() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getDeviceBrand();
            log('getDeviceBrand() → ' + result, 'success');
        }

        function testGetDeviceManufacturer() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getDeviceManufacturer();
            log('getDeviceManufacturer() → ' + result, 'success');
        }

        function testGetSdkVersion() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getSdkVersion();
            log('getSdkVersion() → ' + result + (result >= 33 ? ' (Android 13+)' : ''), 'success');
        }

        function testGetAppVersion() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getAppVersion();
            log('getAppVersion() → ' + result, 'success');
        }

        function testGetPackageName() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.getPackageName();
            log('getPackageName() → ' + result, 'success');
        }

        function testGetDeviceInfo() {
            if (!hasNativeBridge()) return;
            const result = JSON.parse(NativeBridge.getDeviceInfo());
            log('getDeviceInfo() → Model: ' + result.model + ', Android: ' + result.androidVersion + ', SDK: ' + result.sdkVersion + ', Battery: ' + result.batteryLevel + '%, Screen: ' + result.screenWidth + 'x' + result.screenHeight, 'success');
        }

        // === UI Feedback ===
        function testShowToast() {
            if (!hasNativeBridge()) return;
            NativeBridge.showToast('Hello from NativeBridge Test! (' + new Date().toLocaleTimeString() + ')');
            log('showToast() → sent', 'success');
        }

        function testVibrate(ms) {
            if (!hasNativeBridge()) return;
            NativeBridge.vibrate(ms);
            log('vibrate(' + ms + 'ms) → sent', 'success');
        }

        // === Network ===
        function testIsConnected() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.isConnected();
            log('isConnected() → ' + result, result ? 'success' : 'warn');
            document.getElementById('network-status').textContent = 'Network status: ' + (result ? 'Online' : 'Offline');
            document.getElementById('network-status').className = 'mt-3 px-3 py-2 rounded-lg text-sm font-mono ' + (result ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
        }

        function testStartNetworkCallback() {
            if (!hasNativeBridge()) return;
            window.onNetworkChanged = function(status) {
                log('onNetworkChanged → ' + status, status === 'online' ? 'success' : 'warn');
                const el = document.getElementById('network-status');
                el.textContent = 'Network status: ' + (status === 'online' ? 'Online' : 'Offline');
                el.className = 'mt-3 px-3 py-2 rounded-lg text-sm font-mono ' + (status === 'online' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700');
            };
            NativeBridge.startNetworkCallback();
            log('startNetworkCallback() → listening...', 'success');
        }

        function testStopNetworkCallback() {
            if (!hasNativeBridge()) return;
            NativeBridge.stopNetworkCallback();
            log('stopNetworkCallback() → stopped', 'success');
            document.getElementById('network-status').textContent = 'Network status: Monitoring stopped';
            document.getElementById('network-status').className = 'mt-3 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface-variant text-sm font-mono';
        }

        // === Print & Save ===
        function testPrintPage() {
            if (!hasNativeBridge()) return;
            NativeBridge.printPage();
            log('printPage() → print dialog opened', 'success');
        }

        function testSaveAsPdf() {
            if (!hasNativeBridge()) return;
            NativeBridge.saveAsPdf();
            log('saveAsPdf() → save dialog opened', 'success');
        }

        // === Share & Image ===
        function testShareAsImage() {
            if (!hasNativeBridge()) return;
            NativeBridge.shareAsImage();
            log('shareAsImage() → share dialog opened', 'success');
        }

        function testSaveAsImage() {
            if (!hasNativeBridge()) return;
            NativeBridge.saveAsImage();
            log('saveAsImage() → saving to gallery', 'success');
        }

        // === Camera & Gallery ===
        function testCaptureCamera() {
            if (!hasNativeBridge()) return;
            window.onImageCaptured = function(data) {
                if (data.startsWith('{') && data.includes('"error"')) {
                    const error = JSON.parse(data);
                    log('captureCamera() → error: ' + error.error, 'error');
                } else {
                    log('captureCamera() → received image (' + Math.round(data.length / 1024) + ' KB)', 'success');
                    const preview = document.getElementById('camera-preview');
                    const img = document.getElementById('captured-image');
                    img.src = data;
                    preview.classList.remove('hidden');
                }
            };
            NativeBridge.captureCamera();
            log('captureCamera() → opening camera...', 'info');
        }

        function testPickFromGallery() {
            if (!hasNativeBridge()) return;
            window.onImagePicked = function(data) {
                if (data.startsWith('{') && data.includes('"error"')) {
                    const error = JSON.parse(data);
                    log('pickFromGallery() → error: ' + error.error, 'error');
                } else {
                    log('pickFromGallery() → received image (' + Math.round(data.length / 1024) + ' KB)', 'success');
                    const preview = document.getElementById('camera-preview');
                    const img = document.getElementById('captured-image');
                    img.src = data;
                    preview.classList.remove('hidden');
                }
            };
            NativeBridge.pickFromGallery();
            log('pickFromGallery() → opening gallery...', 'info');
        }

        function testCaptureCameraForForm() {
            if (!hasNativeBridge()) return;
            NativeBridge.captureCameraForForm();
            log('captureCameraForForm() → opening camera for form...', 'info');
        }

        function testPickFromGalleryForForm() {
            if (!hasNativeBridge()) return;
            NativeBridge.pickFromGalleryForForm();
            log('pickFromGalleryForForm() → opening gallery for form...', 'info');
        }

        // === Location ===
        function testHasLocationPermission() {
            if (!hasNativeBridge()) return;
            const result = NativeBridge.hasLocationPermission();
            log('hasLocationPermission() → ' + result, result ? 'success' : 'warn');
        }

        function testRequestLocationPermission() {
            if (!hasNativeBridge()) return;
            NativeBridge.requestLocationPermission();
            log('requestLocationPermission() → requesting permission...', 'info');
        }

        function testGetCurrentLocation() {
            if (!hasNativeBridge()) return;

            window.onLocationSuccess = function(data) {
                const loc = JSON.parse(data);
                log('onLocationSuccess → Lat: ' + loc.latitude + ', Lng: ' + loc.longitude + ', Acc: ' + loc.accuracy + 'm', 'success');
                const el = document.getElementById('location-result');
                el.classList.remove('hidden');
                el.innerHTML = '<strong>Latitude:</strong> ' + loc.latitude + '<br><strong>Longitude:</strong> ' + loc.longitude + '<br><strong>Accuracy:</strong> ' + loc.accuracy + 'm<br><strong>Speed:</strong> ' + loc.speed + ' m/s<br><strong>Bearing:</strong> ' + loc.bearing + '<br><strong>Timestamp:</strong> ' + new Date(loc.timestamp).toLocaleString();
            };

            window.onLocationError = function(error) {
                log('onLocationError → ' + error, 'error');
                const el = document.getElementById('location-result');
                el.classList.remove('hidden');
                el.innerHTML = '<span class="text-red-600">' + error + '</span>';
            };

            if (NativeBridge.hasLocationPermission()) {
                NativeBridge.getCurrentLocation();
                log('getCurrentLocation() → requesting location...', 'info');
            } else {
                NativeBridge.requestLocationPermission();
                log('requestLocationPermission() → requesting first, then get location', 'warn');
            }
        }

        // === Push Notifications ===
        function testGetFcmToken() {
            if (!hasNativeBridge()) return;
            window.onFcmToken = function(token) {
                log('onFcmToken → ' + token.substring(0, 30) + '...', 'success');
            };
            NativeBridge.getFcmToken();
            log('getFcmToken() → requesting token...', 'info');
        }

        function testConnectWebSocket() {
            if (!hasNativeBridge()) return;
            window.onPushNotification = function(data) {
                const payload = JSON.parse(data);
                log('WebSocket notification → ' + payload.title + ': ' + payload.body, 'success');
            };
            window.onNetworkStatus = function(status) {
                log('WebSocket status → ' + status, status === 'websocket_connected' ? 'success' : 'warn');
            };
            NativeBridge.connectWebSocket('wss://echo.websocket.org');
            log('connectWebSocket() → connecting...', 'info');
        }

        function testDisconnectWebSocket() {
            if (!hasNativeBridge()) return;
            NativeBridge.disconnectWebSocket();
            log('disconnectWebSocket() → disconnected', 'success');
        }

        function testStartPolling() {
            if (!hasNativeBridge()) return;
            window.onPushNotification = function(data) {
                const payload = JSON.parse(data);
                log('Polling notification → ' + payload.title + ': ' + payload.body, 'success');
            };
            NativeBridge.startPolling('https://jsonplaceholder.typicode.com/posts/1', 1);
            log('startPolling() → polling every 1 minute', 'success');
        }

        function testStopPolling() {
            if (!hasNativeBridge()) return;
            NativeBridge.stopPolling();
            log('stopPolling() → stopped', 'success');
        }

        function testConnectMqtt() {
            if (!hasNativeBridge()) return;
            window.onPushNotification = function(data) {
                const payload = JSON.parse(data);
                log('MQTT notification → ' + payload.title + ': ' + payload.body, 'success');
            };
            window.onNetworkStatus = function(status) {
                log('MQTT status → ' + status, status === 'mqtt_connected' ? 'success' : 'warn');
            };
            NativeBridge.connectMqtt('tcp://broker.example.com:1883', 'sidoraya-test', 'notifications/test');
            log('connectMqtt() → connecting...', 'info');
        }

        function testDisconnectMqtt() {
            if (!hasNativeBridge()) return;
            NativeBridge.disconnectMqtt();
            log('disconnectMqtt() → disconnected', 'success');
        }

        function testScheduleLocalNotification() {
            if (!hasNativeBridge()) return;
            const now = new Date();
            const h = now.getHours();
            const m = (now.getMinutes() + 1) % 60;
            NativeBridge.scheduleLocalNotification('Test Notification', 'This is a scheduled test from NativeBridge page', h, m, false);
            log('scheduleLocalNotification() → scheduled for ' + h + ':' + String(m).padStart(2, '0'), 'success');
        }

        function testCancelAllLocalNotifications() {
            if (!hasNativeBridge()) return;
            NativeBridge.cancelAllLocalNotifications();
            log('cancelAllLocalNotifications() → cancelled all', 'success');
        }
    </script>
</x-layouts::app>
