# ProGuard rules for ColdStorage Web2APK

# Keep JavaScript Interface
-keepclassmembers class com.itoktoni.coldstorage.NativeBridge {
    @android.webkit.JavascriptInterface <methods>;
}

# Keep AppConfig
-keep class com.itoktoni.coldstorage.AppConfig { *; }

# General Android
-keepattributes *Annotation*
-keepattributes SourceFile,LineNumberTable
-renamesourcefileattribute SourceFile
