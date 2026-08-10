plugins {
    id("com.android.application")
}

android {
    namespace = "com.itoktoni.coldstorage"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.itoktoni.coldstorage"
        minSdk = 24
        targetSdk = 36
        versionCode = 1
        versionName = "1.0.0"
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
        debug {
            isMinifyEnabled = false
            applicationIdSuffix = ".debug"
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlin {
        jvmToolchain(17)
    }

    buildFeatures {
        viewBinding = true
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("androidx.activity:activity-ktx:1.9.3")
    implementation("androidx.constraintlayout:constraintlayout:2.2.0")
    implementation("androidx.swiperefreshlayout:swiperefreshlayout:1.1.0")
    implementation("androidx.webkit:webkit:1.12.1")

    implementation("com.google.android.material:material:1.12.0")
    implementation("androidx.core:core-splashscreen:1.0.1")
    implementation("com.google.android.gms:play-services-location:21.3.0")

    // Firebase
    implementation(platform("com.google.firebase:firebase-bom:33.7.0"))
    implementation("com.google.firebase:firebase-messaging-ktx")

    // WebSocket (OkHttp)
    implementation("com.squareup.okhttp3:okhttp:4.12.0")

    // WorkManager (Polling)
    implementation("androidx.work:work-runtime-ktx:2.10.0")

    // MQTT (Eclipse Paho)
    implementation("org.eclipse.paho:org.eclipse.paho.client.mqttv3:1.2.5")
    implementation("org.eclipse.paho:org.eclipse.paho.android.service:1.1.1")

    // ESC/POS Bluetooth Printer (KhairoHumsi/Printer-ktx) — library-based backend
    implementation("com.github.KhairoHumsi:Printer-ktx:1.0.1")
}
