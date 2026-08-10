# Requirements Document

## Introduction

Aplikasi Android hybrid (Web2APK) yang menggabungkan native Android dan WebView untuk membungkus website https://coldstorage.nexeratech.co.id dengan fitur lengkap termasuk JavaScript, akses kamera untuk scanner barcode/QR code, file upload/download, integrasi native modules, dan tampilan modern Material 3 yang mendukung Android 7 (SDK 24) hingga Android 16 (SDK 36).

URL default dikonfigurasi di satu file konfigurasi (`AppConfig.kt`) sehingga mudah diganti tanpa mengubah logika aplikasi.

## Requirements

### Requirement 1 — Hybrid Architecture

**User Story:** As a developer, I want the app to use a hybrid architecture with native Android components and WebView, so that I can leverage both native performance and web flexibility.

#### Acceptance Criteria

1. WHEN the app launches THEN it SHALL initialize both native and web components
2. WHEN the WebView loads a page THEN JavaScript SHALL be enabled with bridge to native modules
3. WHEN native features are needed THEN the WebView SHALL communicate with native code via JavaScript Interface
4. WHEN the app starts THEN it SHALL load the default URL https://coldstorage.nexeratech.co.id
5. WHEN running on any device THEN DOM Storage, LocalStorage, SessionStorage, and Cookies SHALL be enabled and persisted

### Requirement 2 — Camera & Barcode/QR Scanner

**User Story:** As a user, I want the app to access my device camera so that I can scan barcodes and QR codes directly from the website or native interface.

#### Acceptance Criteria

1. WHEN the website requests camera access via getUserMedia() THEN the app SHALL request CAMERA permission from the system
2. WHEN the user grants camera permission THEN the WebView SHALL provide the camera stream to the website
3. WHEN the user denies camera permission THEN the app SHALL show an explanation dialog with option to open app settings
4. WHEN the app is installed on Android 6+ THEN camera permission SHALL be requested at runtime
5. WHEN the website uses an `<input type="file" accept="image/*" capture>` element THEN the app SHALL open the camera or file chooser
6. WHEN the native scanner module is invoked THEN it SHALL provide barcode/QR scanning functionality

### Requirement 3 — File Upload & Download

**User Story:** As a user, I want to upload files from my device and download files from the website, so that I can exchange documents and data.

#### Acceptance Criteria

1. WHEN the website renders an `<input type="file">` element AND the user taps it THEN the app SHALL open a file chooser
2. WHEN the file chooser is opened THEN it SHALL support multiple file types (images, documents, etc.)
3. WHEN the website triggers a file download THEN the app SHALL use the Android Download Manager to save the file
4. WHEN a download completes THEN the app SHALL show a system notification with the downloaded file name
5. WHEN the user taps the download notification THEN the downloaded file SHALL be opened

### Requirement 4 — Navigation & UX

**User Story:** As a user, I want smooth navigation with back button support, pull-to-refresh, and a progress bar, so that the browsing experience feels native.

#### Acceptance Criteria

1. WHEN the user presses the Android back button AND the WebView can go back THEN the WebView SHALL navigate to the previous page
2. WHEN the user presses the Android back button AND the WebView cannot go back THEN the app SHALL show an exit confirmation dialog
3. WHEN the user pulls down on the page THEN the page SHALL refresh (Pull to Refresh)
4. WHEN a page is loading THEN a horizontal progress bar SHALL be displayed at the top of the screen
5. WHEN the page finishes loading THEN the progress bar SHALL be hidden

### Requirement 5 — Splash Screen

**User Story:** As a user, I want to see a branded splash screen when the app launches, so that the app feels professional while content is loading.

#### Acceptance Criteria

1. WHEN the app is launched THEN a splash screen SHALL be displayed
2. WHEN the splash screen is shown THEN it SHALL display the app logo/branding
3. WHEN the initial URL finishes loading THEN the splash screen SHALL transition to the main screen
4. WHEN running on Android 12+ THEN the app SHALL use the native SplashScreen API

### Requirement 6 — SSL & Error Handling

**User Story:** As a user, I want the app to handle SSL errors and network issues gracefully, so that I am informed when something goes wrong.

#### Acceptance Criteria

1. WHEN a network error occurs THEN the app SHALL display an offline/error page with a retry button
2. WHEN an SSL certificate error occurs THEN the app SHALL optionally allow the user to proceed (configurable)
3. WHEN the device has no internet connection THEN the app SHALL show a user-friendly error message
4. WHEN the user taps retry THEN the app SHALL attempt to reload the last URL

### Requirement 7 — Android Platform & UI

**User Story:** As a developer, I want the app to target modern Android standards with Material 3 and edge-to-edge support, so that it looks and works correctly on all supported devices.

#### Acceptance Criteria

1. WHEN running on any supported device THEN the app SHALL target SDK 36 with minimum SDK 24 (Android 7)
2. WHEN running on Android 15/16 THEN the app SHALL support edge-to-edge display
3. WHEN rendered THEN the UI SHALL use Material 3 design components
4. WHEN the status bar and navigation bar are visible THEN the content SHALL render behind them (edge-to-edge)
5. WHEN the app is built THEN it SHALL comply with Android 15/16 edge-to-edge enforcement requirements

### Requirement 8 — Native Bridge Integration

**User Story:** As a developer, I want the WebView to communicate with native Android modules via JavaScript Interface, so that I can extend functionality beyond what web APIs provide.

#### Acceptance Criteria

1. WHEN JavaScript calls a registered native method THEN the native code SHALL execute and return results
2. WHEN native code needs to update the WebView THEN it SHALL be able to call JavaScript functions
3. WHEN the bridge is initialized THEN it SHALL expose a secure API surface (no direct access to sensitive system functions)
4. WHEN errors occur in native bridge calls THEN they SHALL be caught and reported to JavaScript
5. WHEN the app is built THEN the bridge interface SHALL be type-safe and well-documented
