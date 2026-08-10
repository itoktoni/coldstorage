package com.itoktoni.coldstorage

import android.app.Activity
import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Picture
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import android.net.NetworkRequest
import android.net.Uri
import android.os.BatteryManager
import android.os.Build
import android.os.Environment
import android.os.Handler
import android.os.LocaleList
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import android.provider.MediaStore
import android.provider.Settings
import android.print.PrintAttributes
import android.print.PrintManager
import androidx.core.content.FileProvider
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.widget.Toast
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import com.google.android.gms.tasks.CancellationTokenSource
import org.json.JSONObject
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class NativeBridge(private val context: Context) {

    private var webView: WebView? = null
    private var activity: Activity? = null
    private var pendingCameraAction: String? = null // "capture" or "form"

    fun setWebView(webView: WebView) {
        this.webView = webView
    }

    fun setActivity(activity: Activity) {
        this.activity = activity
    }

    fun onCameraPermissionResult(granted: Boolean) {
        if (granted) {
            val currentActivity = activity ?: return
            if (currentActivity is MainActivity) {
                when (pendingCameraAction) {
                    "capture" -> currentActivity.openCamera()
                    "form" -> currentActivity.openCameraForForm()
                }
            }
        } else {
            callJsCallback("onImageCaptured", "{\"error\": \"Camera permission denied\"}")
        }
        pendingCameraAction = null
    }

    @JavascriptInterface
    fun hasLocationPermission(): Boolean {
        return context.checkSelfPermission(android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED ||
               context.checkSelfPermission(android.Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
    }

    @JavascriptInterface
    fun requestLocationPermission() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: return@post
            if (currentActivity is MainActivity) {
                currentActivity.requestLocationPermission()
            }
        }
    }

    @JavascriptInterface
    fun showToast(message: String) {
        Handler(context.mainLooper).post {
            Toast.makeText(context, message, Toast.LENGTH_SHORT).show()
        }
    }

    @JavascriptInterface
    fun getDeviceModel(): String = Build.MODEL

    @JavascriptInterface
    fun getDeviceBrand(): String = Build.BRAND

    @JavascriptInterface
    fun getDeviceManufacturer(): String = Build.MANUFACTURER

    @JavascriptInterface
    fun getSdkVersion(): Int = Build.VERSION.SDK_INT

    @JavascriptInterface
    fun getAppVersion(): String {
        return try {
            context.packageManager.getPackageInfo(context.packageName, 0).versionName ?: "unknown"
        } catch (_: Exception) {
            "unknown"
        }
    }

    @JavascriptInterface
    fun getPackageName(): String = context.packageName

    @JavascriptInterface
    fun vibrate(durationMs: Int) {
        Handler(context.mainLooper).post {
            val vibrator = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                val manager = context.getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as VibratorManager
                manager.defaultVibrator
            } else {
                @Suppress("DEPRECATION")
                context.getSystemService(Context.VIBRATOR_SERVICE) as Vibrator
            }
            vibrator.vibrate(VibrationEffect.createOneShot(
                durationMs.toLong(),
                VibrationEffect.DEFAULT_AMPLITUDE
            ))
        }
    }

    @JavascriptInterface
    fun isConnected(): Boolean {
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val network = cm.activeNetwork ?: return false
        val capabilities = cm.getNetworkCapabilities(network) ?: return false
        return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    @JavascriptInterface
    fun startNetworkCallback() {
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val request = NetworkRequest.Builder()
            .addCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
            .build()

        cm.registerNetworkCallback(request, object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                Handler(context.mainLooper).post {
                    callJsCallback("onNetworkChanged", "online")
                }
            }

            override fun onLost(network: Network) {
                Handler(context.mainLooper).post {
                    callJsCallback("onNetworkChanged", "offline")
                }
            }

            override fun onCapabilitiesChanged(network: Network, caps: NetworkCapabilities) {
                val hasInternet = caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
                    && caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
                Handler(context.mainLooper).post {
                    callJsCallback("onNetworkChanged", if (hasInternet) "online" else "offline")
                }
            }
        })
    }

    @JavascriptInterface
    fun stopNetworkCallback() {
        val cm = context.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        try {
            cm.unregisterNetworkCallback(object : ConnectivityManager.NetworkCallback() {})
        } catch (_: Exception) {}
    }

    @JavascriptInterface
    fun captureCamera() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: return@post
            if (context.checkSelfPermission(android.Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                pendingCameraAction = "capture"
                if (currentActivity is MainActivity) {
                    currentActivity.requestCameraPermission()
                }
                return@post
            }
            if (currentActivity is MainActivity) {
                currentActivity.openCamera()
            }
        }
    }

    @JavascriptInterface
    fun pickFromGallery() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: return@post
            if (currentActivity is MainActivity) {
                currentActivity.openGallery()
            }
        }
    }

    @JavascriptInterface
    fun captureCameraForForm() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: return@post
            if (context.checkSelfPermission(android.Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
                pendingCameraAction = "form"
                if (currentActivity is MainActivity) {
                    currentActivity.requestCameraPermission()
                }
                return@post
            }
            if (currentActivity is MainActivity) {
                currentActivity.openCameraForForm()
            }
        }
    }

    @JavascriptInterface
    fun pickFromGalleryForForm() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: return@post
            if (currentActivity is MainActivity) {
                currentActivity.openGalleryForForm()
            }
        }
    }

    @JavascriptInterface
    fun getCurrentLocation() {
        Handler(context.mainLooper).post {
            val currentActivity = activity ?: run {
                callJsCallback("onLocationError", "Activity not available")
                return@post
            }

            val hasFineLocation = context.checkSelfPermission(android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
            val hasCoarseLocation = context.checkSelfPermission(android.Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED

            if (!hasFineLocation && !hasCoarseLocation) {
                callJsCallback("onLocationError", "Location permission not granted. Please grant location permission.")
                return@post
            }

            val locationManager = currentActivity.getSystemService(Context.LOCATION_SERVICE) as LocationManager
            val isGpsEnabled = locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)
            val isNetworkEnabled = locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)

            if (!isGpsEnabled && !isNetworkEnabled) {
                callJsCallback("onLocationError", "Location services are disabled. Please enable GPS.")
                return@post
            }

            val fusedLocationClient = LocationServices.getFusedLocationProviderClient(currentActivity)

            try {
                val cancellationToken = CancellationTokenSource()
                fusedLocationClient.getCurrentLocation(
                    Priority.PRIORITY_HIGH_ACCURACY,
                    cancellationToken.token
                ).addOnSuccessListener { location ->
                    if (location != null) {
                        val json = JSONObject().apply {
                            put("latitude", location.latitude)
                            put("longitude", location.longitude)
                            put("altitude", location.altitude)
                            put("accuracy", location.accuracy.toDouble())
                            put("speed", location.speed.toDouble())
                            put("bearing", location.bearing.toDouble())
                            put("timestamp", location.time)
                        }
                        callJsCallback("onLocationSuccess", json.toString())
                    } else {
                        callJsCallback("onLocationError", "Location is null. Make sure GPS has a fix.")
                    }
                }.addOnFailureListener { e ->
                    callJsCallback("onLocationError", e.message ?: "Unknown location error")
                }
            } catch (e: SecurityException) {
                callJsCallback("onLocationError", "Location permission not granted")
            } catch (e: Exception) {
                callJsCallback("onLocationError", e.message ?: "Unknown error")
            }
        }
    }

    @JavascriptInterface
    fun getDeviceInfo(): String {
        return try {
            val pm = context.packageManager
            val displayMetrics = context.resources.displayMetrics
            val locationManager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
            val batteryManager = context.getSystemService(Context.BATTERY_SERVICE) as BatteryManager

            val batteryLevel = batteryManager.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)

            val screenDensity = displayMetrics.densityDpi
            val screenDensityDp = displayMetrics.density

            val primaryLocale = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.N) {
                LocaleList.getDefault().get(0).toString()
            } else {
                Locale.getDefault().toString()
            }

            val json = JSONObject().apply {
                put("model", Build.MODEL)
                put("brand", Build.BRAND)
                put("manufacturer", Build.MANUFACTURER)
                put("device", Build.DEVICE)
                put("product", Build.PRODUCT)
                put("hardware", Build.HARDWARE)
                put("androidVersion", Build.VERSION.RELEASE)
                put("sdkVersion", Build.VERSION.SDK_INT)
                put("buildId", Build.ID)
                put("buildDisplay", Build.DISPLAY)
                put("appVersion", getAppVersion())
                put("packageName", context.packageName)
                put("androidId", getAndroidId())
                put("serialNumber", getSerialNumber())
                put("uniqueId", getUniqueId())
                put("screenWidth", displayMetrics.widthPixels)
                put("screenHeight", displayMetrics.heightPixels)
                put("screenDensity", screenDensity)
                put("screenDensityDp", screenDensityDp)
                put("locale", primaryLocale)
                put("batteryLevel", batteryLevel)
                put("isGpsEnabled", locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER))
                put("isNetworkEnabled", locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER))
                put("totalMemory", Runtime.getRuntime().maxMemory())
                put("availableMemory", Runtime.getRuntime().freeMemory())
                put("availableProcessors", Runtime.getRuntime().availableProcessors())
            }
            json.toString()
        } catch (e: Exception) {
            "{\"error\": \"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun getAndroidId(): String {
        return try {
            Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID) ?: "unknown"
        } catch (e: Exception) {
            "unknown"
        }
    }

    @JavascriptInterface
    fun getSerialNumber(): String {
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                if (context.checkSelfPermission(android.Manifest.permission.READ_PHONE_STATE) == PackageManager.PERMISSION_GRANTED) {
                    Build.getSerial() ?: "unknown"
                } else {
                    Handler(context.mainLooper).post {
                        val currentActivity = activity ?: return@post
                        if (currentActivity is MainActivity) {
                            currentActivity.requestPhoneStatePermission()
                        }
                    }
                    "permission_requested"
                }
            } else {
                @Suppress("DEPRECATION")
                Build.SERIAL ?: "unknown"
            }
        } catch (e: Exception) {
            "unknown"
        }
    }

    fun getSerialNumberDirect(): String {
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                Build.getSerial() ?: "unknown"
            } else {
                @Suppress("DEPRECATION")
                Build.SERIAL ?: "unknown"
            }
        } catch (e: Exception) {
            "unknown"
        }
    }

    @JavascriptInterface
    fun getUniqueId(): String {
        val androidId = getAndroidId()
        val serial = getSerialNumber()
        return "$androidId|$serial"
    }

    fun callJsCallback(functionName: String, data: String) {
        Handler(context.mainLooper).post {
            val currentWebView = webView ?: return@post
            val escapedData = data.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n").replace("\r", "\\r")
            val js = "javascript:if(typeof window.$functionName === 'function') { window.$functionName('$escapedData'); }"
            currentWebView.evaluateJavascript(js, null)
        }
    }

    @Suppress("DEPRECATION")
    @JavascriptInterface
    fun printPage() {
        Handler(context.mainLooper).post {
            try {
                val currentWebView = webView ?: return@post
                val printManager = context.getSystemService(Context.PRINT_SERVICE) as PrintManager
                val jobName = "${context.getString(R.string.app_name)} Print"
                val printAdapter = currentWebView.createPrintDocumentAdapter(jobName)
                printManager.print(jobName, printAdapter, PrintAttributes.Builder().build())
            } catch (e: Exception) {
                Toast.makeText(context, "Print failed: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    @JavascriptInterface
    fun saveAsPdf() {
        Handler(context.mainLooper).post {
            try {
                val currentWebView = webView ?: return@post
                val printManager = context.getSystemService(Context.PRINT_SERVICE) as PrintManager
                val jobName = "${context.getString(R.string.app_name)} ${System.currentTimeMillis()}"
                val printAdapter = currentWebView.createPrintDocumentAdapter(jobName)
                printManager.print(jobName, printAdapter, PrintAttributes.Builder().build())
                Toast.makeText(context, "Print dialog opened. Select 'Save as PDF' to save.", Toast.LENGTH_LONG).show()
            } catch (e: Exception) {
                Toast.makeText(context, "Save PDF failed: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    @JavascriptInterface
    fun shareAsImage() {
        Handler(context.mainLooper).post {
            try {
                val currentWebView = webView ?: return@post
                val bitmap = captureWebView(currentWebView) ?: run {
                    Toast.makeText(context, "Failed to capture screen", Toast.LENGTH_SHORT).show()
                    return@post
                }

                val file = saveBitmapToCache(bitmap)
                if (file != null) {
                    val uri = FileProvider.getUriForFile(
                        context,
                        "${context.packageName}.fileprovider",
                        file
                    )
                    val shareIntent = Intent(Intent.ACTION_SEND).apply {
                        type = "image/png"
                        putExtra(Intent.EXTRA_STREAM, uri)
                        addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                    }
                    activity?.startActivity(Intent.createChooser(shareIntent, "Share via"))
                } else {
                    Toast.makeText(context, "Failed to save image", Toast.LENGTH_SHORT).show()
                }

                bitmap.recycle()
            } catch (e: Exception) {
                Toast.makeText(context, "Share failed: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    @JavascriptInterface
    fun saveAsImage() {
        Handler(context.mainLooper).post {
            try {
                val currentWebView = webView ?: return@post
                val bitmap = captureWebView(currentWebView) ?: run {
                    Toast.makeText(context, "Failed to capture screen", Toast.LENGTH_SHORT).show()
                    return@post
                }

                val filename = "ColdStorage_${System.currentTimeMillis()}.png"
                val saved = saveBitmapToGallery(bitmap, filename)

                if (saved) {
                    Toast.makeText(context, "Image saved to Gallery", Toast.LENGTH_SHORT).show()
                } else {
                    Toast.makeText(context, "Failed to save image", Toast.LENGTH_SHORT).show()
                }

                bitmap.recycle()
            } catch (e: Exception) {
                Toast.makeText(context, "Save failed: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun captureWebView(webView: WebView): Bitmap? {
        return try {
            val picture: Picture? = webView.capturePicture()
            val width = picture?.width ?: return null
            val height = picture?.height ?: return null

            if (width <= 0 || height <= 0) return null

            val bitmap = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(bitmap)
            picture.draw(canvas)
            bitmap
        } catch (e: Exception) {
            null
        }
    }

    private fun saveBitmapToCache(bitmap: Bitmap): File? {
        return try {
            val cacheDir = File(context.cacheDir, "shared")
            cacheDir.mkdirs()
            val file = File(cacheDir, "share_${System.currentTimeMillis()}.png")
            FileOutputStream(file).use { out ->
                bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
            }
            file
        } catch (e: Exception) {
            null
        }
    }

    private fun saveBitmapToGallery(bitmap: Bitmap, filename: String): Boolean {
        return try {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val values = ContentValues().apply {
                    put(MediaStore.Images.Media.DISPLAY_NAME, filename)
                    put(MediaStore.Images.Media.MIME_TYPE, "image/png")
                    put(MediaStore.Images.Media.RELATIVE_PATH, Environment.DIRECTORY_PICTURES + "/ColdStorage")
                    put(MediaStore.Images.Media.IS_PENDING, 1)
                }

                val resolver = context.contentResolver
                val uri = resolver.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values)

                uri?.let {
                    resolver.openOutputStream(it)?.use { out ->
                        bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
                    }
                    values.clear()
                    values.put(MediaStore.Images.Media.IS_PENDING, 0)
                    resolver.update(it, values, null, null)
                    true
                } ?: false
            } else {
                @Suppress("DEPRECATION")
                val imagesDir = Environment.getExternalStoragePublicDirectory(Environment.DIRECTORY_PICTURES)
                val appDir = File(imagesDir, "ColdStorage")
                appDir.mkdirs()
                val file = File(appDir, filename)
                FileOutputStream(file).use { out ->
                    bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
                }
                true
            }
        } catch (e: Exception) {
            false
        }
    }

    @JavascriptInterface
    fun getFcmToken() {
        com.google.firebase.messaging.FirebaseMessaging.getInstance().token
            .addOnCompleteListener { task ->
                if (task.isSuccessful) {
                    val token = task.result
                    callJsCallback("onFcmToken", token ?: "")
                }
            }
    }

    @JavascriptInterface
    fun connectWebSocket(url: String) {
        WebSocketNotification.connect(url)
    }

    @JavascriptInterface
    fun disconnectWebSocket() {
        WebSocketNotification.disconnect()
    }

    @JavascriptInterface
    fun sendWebSocketMessage(message: String) {
        WebSocketNotification.sendMessage(message)
    }

    @JavascriptInterface
    fun startPolling(url: String, intervalSeconds: Int) {
        PollingNotification.startPolling(context, url, intervalSeconds.coerceAtLeast(30))
        callJsCallback("onPollingStarted", "{\"url\":\"$url\",\"interval\":$intervalSeconds}")
    }

    @JavascriptInterface
    fun stopPolling() {
        PollingNotification.stopPolling(context)
        callJsCallback("onPollingStopped", "{\"success\":true}")
    }

    @JavascriptInterface
    fun isPolling(): Boolean {
        return PollingNotification.isPolling(context)
    }

    @JavascriptInterface
    fun getPollingStatus(): String {
        return PollingNotification.getStatus(context)
    }

    @JavascriptInterface
    fun connectMqtt(brokerUrl: String, clientId: String, topic: String) {
        MqttNotification.connect(brokerUrl, clientId, topic)
    }

    @JavascriptInterface
    fun disconnectMqtt() {
        MqttNotification.disconnect()
    }

    @JavascriptInterface
    fun publishMqtt(topic: String, message: String) {
        MqttNotification.publish(topic, message)
    }

    @JavascriptInterface
    fun scheduleLocalNotification(title: String, body: String, hour: Int, minute: Int, repeatDaily: Boolean) {
        LocalNotification.schedule(context, title, body, hour, minute, repeatDaily)
    }

    @JavascriptInterface
    fun cancelLocalNotification(requestCode: Int) {
        LocalNotification.cancel(context, requestCode)
    }

    @JavascriptInterface
    fun cancelAllLocalNotifications() {
        LocalNotification.cancelAll(context)
    }

    // ─── FILE OPERATIONS ───

    private fun getFilesDir(): File {
        val dir = File(context.filesDir, "bridge_files")
        if (!dir.exists()) dir.mkdirs()
        return dir
    }

    @JavascriptInterface
    fun createFile(filename: String, content: String): String {
        return try {
            val file = File(getFilesDir(), filename)
            if (file.exists()) {
                "{\"success\":false,\"error\":\"File already exists\",\"filename\":\"$filename\"}"
            } else {
                file.writeText(content)
                "{\"success\":true,\"filename\":\"$filename\",\"size\":${file.length()}}"
            }
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun readFile(filename: String): String {
        return try {
            val file = File(getFilesDir(), filename)
            if (!file.exists()) {
                "{\"success\":false,\"error\":\"File not found\",\"filename\":\"$filename\"}"
            } else {
                val content = file.readText()
                val escaped = content.replace("\\", "\\\\").replace("\"", "\\\"").replace("\n", "\\n").replace("\r", "\\r")
                "{\"success\":true,\"filename\":\"$filename\",\"content\":\"$escaped\",\"size\":${file.length()}}"
            }
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun updateFile(filename: String, content: String): String {
        return try {
            val file = File(getFilesDir(), filename)
            file.writeText(content)
            "{\"success\":true,\"filename\":\"$filename\",\"size\":${file.length()},\"mode\":\"overwrite\"}"
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun appendFile(filename: String, content: String): String {
        return try {
            val file = File(getFilesDir(), filename)
            file.appendText(content)
            "{\"success\":true,\"filename\":\"$filename\",\"size\":${file.length()},\"mode\":\"append\"}"
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun deleteFile(filename: String): String {
        return try {
            val file = File(getFilesDir(), filename)
            if (!file.exists()) {
                "{\"success\":false,\"error\":\"File not found\",\"filename\":\"$filename\"}"
            } else {
                file.delete()
                "{\"success\":true,\"filename\":\"$filename\",\"deleted\":true}"
            }
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun renameFile(oldName: String, newName: String): String {
        return try {
            val oldFile = File(getFilesDir(), oldName)
            if (!oldFile.exists()) {
                "{\"success\":false,\"error\":\"File not found\",\"filename\":\"$oldName\"}"
            } else {
                val newFile = File(getFilesDir(), newName)
                if (newFile.exists()) {
                    "{\"success\":false,\"error\":\"Target file already exists\",\"filename\":\"$newName\"}"
                } else {
                    oldFile.renameTo(newFile)
                    "{\"success\":true,\"oldName\":\"$oldName\",\"newName\":\"$newName\"}"
                }
            }
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun fileExists(filename: String): Boolean {
        return File(getFilesDir(), filename).exists()
    }

    @JavascriptInterface
    fun listFiles(): String {
        return try {
            val dir = getFilesDir()
            val files = dir.listFiles() ?: emptyArray()
            val arr = org.json.JSONArray()
            files.sortedBy { it.name }.forEach { file ->
                val obj = org.json.JSONObject().apply {
                    put("name", file.name)
                    put("size", file.length())
                    put("isDirectory", file.isDirectory)
                    put("lastModified", file.lastModified())
                }
                arr.put(obj)
            }
            "{\"success\":true,\"count\":${files.size},\"files\":${arr.toString()}}"
        } catch (e: Exception) {
            "{\"success\":false,\"error\":\"${e.message}\"}"
        }
    }

    @JavascriptInterface
    fun getFilesDirectory(): String {
        return getFilesDir().absolutePath
    }

    // ─── BLUETOOTH PRINTER ───

    private var printer: BluetoothPrinter? = null

    private fun getPrinter(): BluetoothPrinter {
        if (printer == null) {
            printer = BluetoothPrinter(context)
        }
        return printer!!
    }

    @JavascriptInterface
    fun getPairedPrinters(): String {
        return getPrinter().getPairedDevices()
    }

    @JavascriptInterface
    fun scanPrinters() {
        getPrinter().startDiscovery { result ->
            callJsCallback("onPrintersFound", result)
        }
    }

    @JavascriptInterface
    fun cancelPrinterScan() {
        getPrinter().cancelDiscovery()
    }

    @JavascriptInterface
    fun connectPrinter(address: String) {
        getPrinter().connect(address) { result ->
            callJsCallback("onPrinterConnected", result)
        }
    }

    @JavascriptInterface
    fun disconnectPrinter() {
        getPrinter().disconnect()
        callJsCallback("onPrinterDisconnected", "{\"success\":true}")
    }

    @JavascriptInterface
    fun isPrinterConnected(): Boolean {
        return getPrinter().isConnected()
    }

    @JavascriptInterface
    fun getConnectedPrinter(): String {
        return getPrinter().getConnectedDevice()
    }

    @JavascriptInterface
    fun getSavedPrinter(): String {
        return getPrinter().getSavedPrinter()
    }

    @JavascriptInterface
    fun removeSavedPrinter() {
        getPrinter().removeSavedPrinter()
        callJsCallback("onPrinterRemoved", "{\"success\":true}")
    }

    @JavascriptInterface
    fun autoConnectPrinter() {
        getPrinter().autoConnectAsync { result ->
            callJsCallback("onPrinterConnected", result)
        }
    }

    @JavascriptInterface
    fun testPrint() {
        getPrinter().testPrint { result ->
            callJsCallback("onPrintResult", result)
        }
    }

    @JavascriptInterface
    fun printReceipt(data: String) {
        getPrinter().printReceipt(data) { result ->
            callJsCallback("onPrintResult", result)
        }
    }

    @JavascriptInterface
    fun printLabel(data: String) {
        getPrinter().printLabel(data) { result ->
            callJsCallback("onPrintResult", result)
        }
    }

    @JavascriptInterface
    fun printRaw(base64Data: String) {
        getPrinter().printRaw(base64Data) { result ->
            callJsCallback("onPrintResult", result)
        }
    }

    @JavascriptInterface
    fun printBitmap(base64Data: String) {
        val bytes = android.util.Base64.decode(base64Data, android.util.Base64.DEFAULT)
        val bitmap = android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size)
        if (bitmap != null) {
            getPrinter().printBitmap(bitmap) { result ->
                callJsCallback("onPrintResult", result)
            }
        } else {
            callJsCallback("onPrintResult", "{\"success\":false,\"error\":\"Invalid image data\"}")
        }
    }

    companion object {
        private var webViewStatic: WebView? = null
        private val handlers = mutableListOf<Handler>()

        fun setStaticWebView(webView: WebView) {
            webViewStatic = webView
        }

        fun notifyFcmToken(token: String) {
            callStaticCallback("onFcmToken", token)
        }

        fun notifyPushNotification(title: String, body: String, data: Map<String, String>) {
            val json = JSONObject().apply {
                put("title", title)
                put("body", body)
                data.forEach { (key, value) -> put(key, value) }
            }
            callStaticCallback("onPushNotification", json.toString())
        }

        fun notifyNetworkStatus(status: String) {
            callStaticCallback("onNetworkStatus", status)
        }

        private fun callStaticCallback(functionName: String, data: String) {
            val webView = webViewStatic ?: return
            Handler(android.os.Looper.getMainLooper()).post {
                val escapedData = data.replace("\\", "\\\\").replace("'", "\\'").replace("\n", "\\n").replace("\r", "\\r")
                val js = "javascript:if(typeof window.$functionName === 'function') { window.$functionName('$escapedData'); }"
                webView.evaluateJavascript(js, null)
            }
        }

        fun getContext(): Context = webViewStatic?.context ?: throw IllegalStateException("WebView not initialized")
    }
}
