package com.itoktoni.coldstorage

import android.Manifest
import android.annotation.SuppressLint
import android.app.DownloadManager
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.graphics.Bitmap
import android.graphics.Color
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import android.webkit.CookieManager
import android.webkit.GeolocationPermissions
import android.webkit.PermissionRequest
import android.webkit.ValueCallback
import android.webkit.WebChromeClient
import android.webkit.WebResourceError
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.ProgressBar
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.core.splashscreen.SplashScreen.Companion.installSplashScreen
import androidx.core.view.ViewCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.WindowInsetsControllerCompat
import androidx.core.view.updatePadding
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import java.io.File

class MainActivity : AppCompatActivity() {

    private companion object {
        const val RETURN_URL_PREFS = "native_bridge_return"
        const val RETURN_URL_KEY = "url"
    }

    private lateinit var webView: WebView
    private lateinit var swipeRefresh: SwipeRefreshLayout
    private lateinit var progressBar: ProgressBar
    private lateinit var errorLayout: View
    private lateinit var webContainer: ViewGroup
    private lateinit var statusBarBackground: View
    private lateinit var navBarBackground: View

    private var filePathCallback: ValueCallback<Array<Uri>>? = null
    private var currentUrl = AppConfig.DEFAULT_URL
    private var pendingPermissionRequest: PermissionRequest? = null
    private var nativeBridge: NativeBridge? = null

    private val cameraPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val cameraGranted = permissions[Manifest.permission.CAMERA] == true
        if (cameraGranted) {
            pendingPermissionRequest?.grant(pendingPermissionRequest?.resources)
            nativeBridge?.onCameraPermissionResult(true)
        } else {
            showCameraPermissionRationale()
            nativeBridge?.onCameraPermissionResult(false)
        }
        pendingPermissionRequest = null
    }

    private val locationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val fineLocationGranted = permissions[Manifest.permission.ACCESS_FINE_LOCATION] == true
        val coarseLocationGranted = permissions[Manifest.permission.ACCESS_COARSE_LOCATION] == true
        if (fineLocationGranted || coarseLocationGranted) {
            nativeBridge?.getCurrentLocation()
        } else {
            Toast.makeText(this, "Location permission denied", Toast.LENGTH_SHORT).show()
        }
    }

    private val phoneStatePermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val granted = permissions[Manifest.permission.READ_PHONE_STATE] == true
        if (granted) {
            nativeBridge?.callJsCallback("onSerialNumber", nativeBridge?.getSerialNumberDirect() ?: "unknown")
        } else {
            nativeBridge?.callJsCallback("onSerialNumber", "permission_denied")
            Toast.makeText(this, "Phone state permission denied", Toast.LENGTH_SHORT).show()
        }
    }

    private val bluetoothPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { permissions ->
        val granted = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            permissions[Manifest.permission.BLUETOOTH_CONNECT] == true
        } else {
            true
        }
        nativeBridge?.callJsCallback("onBluetoothPermission", if (granted) "granted" else "denied")
        if (!granted) {
            Toast.makeText(this, "Bluetooth permission denied", Toast.LENGTH_SHORT).show()
        }
    }

    private val fileChooserLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) { result ->
        if (result.resultCode == RESULT_OK) {
            val data = result.data
            val resultUri = if (data?.data != null) {
                arrayOf(data.data!!)
            } else {
                data?.clipData?.let { clip ->
                    Array(clip.itemCount) { clip.getItemAt(it).uri }
                }
            }
            filePathCallback?.onReceiveValue(resultUri)
        } else {
            filePathCallback?.onReceiveValue(null)
        }
        filePathCallback = null
    }

    private val settingsLauncher = registerForActivityResult(
        ActivityResultContracts.StartActivityForResult()
    ) {
        if (hasCameraPermission()) {
            webView.reload()
        }
    }

    private var cameraImageUri: Uri? = null
    private var cameraFilePathCallback: ValueCallback<Array<Uri>>? = null

    private val cameraLauncher = registerForActivityResult(
        ActivityResultContracts.TakePicture()
    ) { success ->
        if (success && cameraImageUri != null) {
            val base64 = uriToBase64(cameraImageUri!!)
            if (base64 != null) {
                nativeBridge?.callJsCallback("onImageCaptured", base64)
            } else {
                nativeBridge?.callJsCallback("onImageCaptured", "{\"error\": \"Failed to encode image\"}")
            }
        } else {
            nativeBridge?.callJsCallback("onImageCaptured", "{\"error\": \"Camera capture cancelled\"}")
        }
    }

    private val cameraForFormLauncher = registerForActivityResult(
        ActivityResultContracts.TakePicture()
    ) { success ->
        if (success && cameraImageUri != null) {
            cameraFilePathCallback?.onReceiveValue(arrayOf(cameraImageUri!!))
        } else {
            cameraFilePathCallback?.onReceiveValue(null)
        }
        cameraFilePathCallback = null
    }

    private val galleryLauncher = registerForActivityResult(
        ActivityResultContracts.GetContent()
    ) { uri ->
        if (uri != null) {
            val base64 = uriToBase64(uri)
            if (base64 != null) {
                nativeBridge?.callJsCallback("onImagePicked", base64)
            } else {
                nativeBridge?.callJsCallback("onImagePicked", "{\"error\": \"Failed to encode image\"}")
            }
        } else {
            nativeBridge?.callJsCallback("onImagePicked", "{\"error\": \"Gallery pick cancelled\"}")
        }
    }

    private fun showImageChooser() {
        val items = arrayOf<CharSequence>("Camera", "Gallery")
        AlertDialog.Builder(this)
            .setTitle("Select Image")
            .setItems(items) { _, which ->
                when (which) {
                    0 -> openCameraForForm()
                    1 -> openGalleryForForm()
                }
            }
            .setNegativeButton("Cancel") { dialog, _ ->
                filePathCallback?.onReceiveValue(null)
                filePathCallback = null
                dialog.dismiss()
            }
            .show()
    }

    fun openCameraForForm() {
        if (!hasCameraPermission()) {
            cameraPermissionLauncher.launch(arrayOf(Manifest.permission.CAMERA))
            return
        }
        val imageFile = File(cacheDir, "images").apply { mkdirs() }
            .let { File(it, "camera_${System.currentTimeMillis()}.jpg") }
        cameraImageUri = FileProvider.getUriForFile(this, "${packageName}.fileprovider", imageFile)
        cameraForFormLauncher.launch(cameraImageUri!!)
    }

    fun openGalleryForForm() {
        val intent = Intent(Intent.ACTION_GET_CONTENT).apply {
            type = "image/*"
            addCategory(Intent.CATEGORY_OPENABLE)
            putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true)
        }
        fileChooserLauncher.launch(intent)
    }

    fun openCamera() {
        val imageFile = File(cacheDir, "images").apply { mkdirs() }
            .let { File(it, "camera_${System.currentTimeMillis()}.jpg") }
        cameraImageUri = FileProvider.getUriForFile(this, "${packageName}.fileprovider", imageFile)
        cameraLauncher.launch(cameraImageUri!!)
    }

    fun openGallery() {
        galleryLauncher.launch("image/*")
    }

    private fun uriToBase64(uri: Uri): String? {
        return try {
            contentResolver.openInputStream(uri)?.use { inputStream ->
                val bytes = inputStream.readBytes()
                val base64 = android.util.Base64.encodeToString(bytes, android.util.Base64.NO_WRAP)
                val mimeType = contentResolver.getType(uri) ?: "image/jpeg"
                "data:$mimeType;base64,$base64"
            }
        } catch (e: Exception) {
            null
        }
    }

    private val downloadReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            val downloadId = intent?.getLongExtra(DownloadManager.EXTRA_DOWNLOAD_ID, -1) ?: return
            if (downloadId != -1L) {
                Toast.makeText(this@MainActivity, "Download complete", Toast.LENGTH_SHORT).show()
            }
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        val splashScreen = installSplashScreen()
        super.onCreate(savedInstanceState)

        enableEdgeToEdge()
        setContentView(R.layout.activity_main)

        swipeRefresh = findViewById(R.id.swipeRefresh)
        webView = findViewById(R.id.webView)
        progressBar = findViewById(R.id.progressBar)
        errorLayout = findViewById(R.id.errorLayout)
        webContainer = findViewById(R.id.webContainer)
        statusBarBackground = findViewById(R.id.statusBarBackground)
        navBarBackground = findViewById(R.id.navBarBackground)

        applyInsets()

        setupWebView()
        setupSwipeRefresh()
        setupErrorLayout()

        if (savedInstanceState == null) {
            val returnUrl = getSharedPreferences(RETURN_URL_PREFS, MODE_PRIVATE)
                .getString(RETURN_URL_KEY, null)

            getSharedPreferences(RETURN_URL_PREFS, MODE_PRIVATE)
                .edit()
                .remove(RETURN_URL_KEY)
                .apply()

            webView.loadUrl(returnUrl ?: AppConfig.DEFAULT_URL)
        } else {
            webView.restoreState(savedInstanceState)
        }

        val filter = IntentFilter(DownloadManager.ACTION_DOWNLOAD_COMPLETE)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(downloadReceiver, filter, RECEIVER_NOT_EXPORTED)
        } else {
            registerReceiver(downloadReceiver, filter)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        webView.saveState(outState)
    }

    fun rememberExternalReturnUrl() {
        val url = webView.url ?: return

        getSharedPreferences(RETURN_URL_PREFS, MODE_PRIVATE)
            .edit()
            .putString(RETURN_URL_KEY, url)
            .apply()
    }

    override fun onDestroy() {
        super.onDestroy()
        try {
            unregisterReceiver(downloadReceiver)
        } catch (_: Exception) {}
    }

    @Suppress("DEPRECATION")
    private fun enableEdgeToEdge() {
        WindowCompat.setDecorFitsSystemWindows(window, false)

        val primaryColor = ContextCompat.getColor(this, R.color.primary)

        window.apply {
            addFlags(WindowManager.LayoutParams.FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS)
            statusBarColor = primaryColor
            navigationBarColor = primaryColor
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            window.attributes.layoutInDisplayCutoutMode =
                WindowManager.LayoutParams.LAYOUT_IN_DISPLAY_CUTOUT_MODE_SHORT_EDGES
        }

        val controller = WindowInsetsControllerCompat(window, window.decorView)
        controller.isAppearanceLightStatusBars = false
        controller.isAppearanceLightNavigationBars = false
    }

    private fun applyInsets() {
        ViewCompat.setOnApplyWindowInsetsListener(webContainer) { view, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            view.updatePadding(
                top = systemBars.top,
                bottom = systemBars.bottom
            )
            insets
        }

        ViewCompat.setOnApplyWindowInsetsListener(progressBar) { view, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            val params = view.layoutParams as ViewGroup.MarginLayoutParams
            params.topMargin = systemBars.top
            view.layoutParams = params
            insets
        }

        ViewCompat.setOnApplyWindowInsetsListener(statusBarBackground) { view, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            val params = view.layoutParams
            params.height = systemBars.top
            view.layoutParams = params
            insets
        }

        ViewCompat.setOnApplyWindowInsetsListener(navBarBackground) { view, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            val params = view.layoutParams
            params.height = systemBars.bottom
            view.layoutParams = params
            insets
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    @Suppress("DEPRECATION")
    private fun setupWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            databaseEnabled = true
            allowFileAccess = true
            allowContentAccess = true
            loadWithOverviewMode = true
            useWideViewPort = true
            builtInZoomControls = true
            displayZoomControls = false
            mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
            cacheMode = WebSettings.LOAD_DEFAULT
            setSupportMultipleWindows(false)
            mediaPlaybackRequiresUserGesture = false
        }

        CookieManager.getInstance().apply {
            setAcceptCookie(true)
            setAcceptThirdPartyCookies(webView, true)
        }

        webView.webViewClient = object : WebViewClient() {
            override fun onPageStarted(view: WebView?, url: String?, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                progressBar.visibility = View.VISIBLE
                errorLayout.visibility = View.GONE
                currentUrl = url ?: currentUrl
            }

            override fun onPageFinished(view: WebView?, url: String?) {
                super.onPageFinished(view, url)
                progressBar.visibility = View.GONE
                swipeRefresh.isRefreshing = false
            }

            override fun onReceivedError(
                view: WebView?,
                request: WebResourceRequest?,
                error: WebResourceError?
            ) {
                super.onReceivedError(view, request, error)
                if (request?.isForMainFrame == true) {
                    progressBar.visibility = View.GONE
                    swipeRefresh.isRefreshing = false
                    if (!isNetworkAvailable()) {
                        showError()
                    }
                }
            }

            override fun onReceivedSslError(
                view: WebView?,
                handler: android.webkit.SslErrorHandler?,
                error: android.net.http.SslError?
            ) {
                if (AppConfig.ALLOW_SSL_ERROR) {
                    AlertDialog.Builder(this@MainActivity)
                        .setTitle("SSL Certificate Error")
                        .setMessage("The certificate for this site is not trusted. Proceed anyway?")
                        .setPositiveButton("Proceed") { _, _ -> handler?.proceed() }
                        .setNegativeButton("Cancel") { _, _ -> handler?.cancel() }
                        .show()
                } else {
                    handler?.cancel()
                    Toast.makeText(this@MainActivity, "SSL certificate error", Toast.LENGTH_SHORT).show()
                }
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onProgressChanged(view: WebView?, newProgress: Int) {
                progressBar.progress = newProgress
                if (newProgress == 100) {
                    progressBar.visibility = View.GONE
                } else {
                    progressBar.visibility = View.VISIBLE
                }
            }

            override fun onShowFileChooser(
                webView: WebView?,
                filePathCallback: ValueCallback<Array<Uri>>?,
                fileChooserParams: FileChooserParams?
            ): Boolean {
                this@MainActivity.filePathCallback?.onReceiveValue(null)
                this@MainActivity.filePathCallback = filePathCallback

                val acceptTypes = fileChooserParams?.acceptTypes ?: arrayOf()
                val isImageOnly = acceptTypes.any { it.startsWith("image/") }

                if (isImageOnly) {
                    if (fileChooserParams?.isCaptureEnabled == true) {
                        openCameraForForm()
                    } else {
                        showImageChooser()
                    }
                } else {
                    val intent = fileChooserParams?.createIntent()
                        ?: Intent(Intent.ACTION_GET_CONTENT).apply {
                            type = "*/*"
                            addCategory(Intent.CATEGORY_OPENABLE)
                            putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true)
                        }
                    fileChooserLauncher.launch(intent)
                }
                return true
            }

            override fun onPermissionRequest(request: PermissionRequest) {
                val requestedPermissions = request.resources
                val needsCamera = requestedPermissions.any {
                    it == PermissionRequest.RESOURCE_VIDEO_CAPTURE ||
                    it == PermissionRequest.RESOURCE_AUDIO_CAPTURE
                }

                if (needsCamera && !hasCameraPermission()) {
                    pendingPermissionRequest = request
                    cameraPermissionLauncher.launch(arrayOf(Manifest.permission.CAMERA))
                } else {
                    request.grant(requestedPermissions)
                }
            }

            override fun onGeolocationPermissionsShowPrompt(
                origin: String?,
                callback: GeolocationPermissions.Callback?
            ) {
                callback?.invoke(origin, true, false)
            }

            override fun onJsAlert(
                view: WebView?,
                url: String?,
                message: String?,
                result: android.webkit.JsResult?
            ): Boolean {
                AlertDialog.Builder(this@MainActivity)
                    .setMessage(message)
                    .setPositiveButton(android.R.string.ok) { _, _ -> result?.confirm() }
                    .setOnCancelListener { result?.cancel() }
                    .show()
                return true
            }

            override fun onJsConfirm(
                view: WebView?,
                url: String?,
                message: String?,
                result: android.webkit.JsResult?
            ): Boolean {
                AlertDialog.Builder(this@MainActivity)
                    .setMessage(message)
                    .setPositiveButton(android.R.string.ok) { _, _ -> result?.confirm() }
                    .setNegativeButton(android.R.string.cancel) { _, _ -> result?.cancel() }
                    .setOnCancelListener { result?.cancel() }
                    .show()
                return true
            }

            override fun onJsPrompt(
                view: WebView?,
                url: String?,
                message: String?,
                defaultValue: String?,
                result: android.webkit.JsPromptResult?
            ): Boolean {
                val input = android.widget.EditText(this@MainActivity).apply {
                    setText(defaultValue)
                }
                AlertDialog.Builder(this@MainActivity)
                    .setMessage(message)
                    .setView(input)
                    .setPositiveButton(android.R.string.ok) { _, _ ->
                        result?.confirm(input.text.toString())
                    }
                    .setNegativeButton(android.R.string.cancel) { _, _ ->
                        result?.cancel()
                    }
                    .setOnCancelListener { result?.cancel() }
                    .show()
                return true
            }
        }

        if (AppConfig.ENABLE_NATIVE_BRIDGE) {
            nativeBridge = NativeBridge(this)
            nativeBridge?.setWebView(webView)
            nativeBridge?.setActivity(this)
            webView.addJavascriptInterface(nativeBridge!!, "NativeBridge")
            NativeBridge.setStaticWebView(webView)
        }
    }

    private fun setupSwipeRefresh() {
        swipeRefresh.setColorSchemeResources(R.color.primary)
        swipeRefresh.setOnRefreshListener {
            if (isNetworkAvailable()) {
                webView.reload()
            } else {
                swipeRefresh.isRefreshing = false
                showError()
            }
        }
    }

    private fun setupErrorLayout() {
        errorLayout.findViewById<com.google.android.material.button.MaterialButton>(
            R.id.btnRetry
        ).setOnClickListener {
            errorLayout.visibility = View.GONE
            if (isNetworkAvailable()) {
                webView.loadUrl(currentUrl)
            } else {
                Toast.makeText(this, "Still offline", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun showError() {
        errorLayout.visibility = View.VISIBLE
    }

    private fun isNetworkAvailable(): Boolean {
        val cm = getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
        val network = cm.activeNetwork ?: return false
        val capabilities = cm.getNetworkCapabilities(network) ?: return false
        return capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }

    private fun hasCameraPermission(): Boolean {
        return ContextCompat.checkSelfPermission(
            this, Manifest.permission.CAMERA
        ) == PackageManager.PERMISSION_GRANTED
    }

    private fun showCameraPermissionRationale() {
        AlertDialog.Builder(this)
            .setTitle(R.string.permission_camera_title)
            .setMessage(R.string.permission_camera_message)
            .setPositiveButton(R.string.open_settings) { _, _ ->
                val intent = Intent(android.provider.Settings.ACTION_APPLICATION_DETAILS_SETTINGS)
                intent.data = Uri.fromParts("package", packageName, null)
                settingsLauncher.launch(intent)
            }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }

    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            AlertDialog.Builder(this)
                .setTitle(R.string.exit_title)
                .setMessage(R.string.exit_message)
                .setPositiveButton(R.string.exit) { _, _ -> finish() }
                .setNegativeButton(R.string.cancel, null)
                .show()
        }
    }

    fun requestLocationPermission() {
        locationPermissionLauncher.launch(
            arrayOf(
                Manifest.permission.ACCESS_FINE_LOCATION,
                Manifest.permission.ACCESS_COARSE_LOCATION
            )
        )
    }

    fun requestCameraPermission() {
        cameraPermissionLauncher.launch(arrayOf(Manifest.permission.CAMERA))
    }

    fun requestPhoneStatePermission() {
        phoneStatePermissionLauncher.launch(arrayOf(Manifest.permission.READ_PHONE_STATE))
    }

    fun requestBluetoothPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
            bluetoothPermissionLauncher.launch(
                arrayOf(
                    Manifest.permission.BLUETOOTH_CONNECT,
                    Manifest.permission.BLUETOOTH_SCAN
                )
            )
        } else {
            nativeBridge?.callJsCallback("onBluetoothPermission", "granted")
        }
    }
}
