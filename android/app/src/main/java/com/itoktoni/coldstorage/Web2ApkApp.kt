package com.itoktoni.coldstorage

import android.app.Application
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build

class Web2ApkApp : Application() {

    override fun onCreate() {
        super.onCreate()
        createNotificationChannels()
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = getSystemService(NotificationManager::class.java)

            val downloadChannel = NotificationChannel(
                AppConfig.CHANNEL_ID_DOWNLOADS,
                AppConfig.CHANNEL_NAME_DOWNLOADS,
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "File download notifications"
            }

            val pushChannel = NotificationChannel(
                AppConfig.CHANNEL_ID_PUSH,
                AppConfig.CHANNEL_NAME_PUSH,
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Push notifications from server"
                enableVibration(true)
                vibrationPattern = longArrayOf(0, 300, 200, 300)
            }

            manager.createNotificationChannel(downloadChannel)
            manager.createNotificationChannel(pushChannel)
        }
    }
}
