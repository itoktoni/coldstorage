package com.itoktoni.coldstorage

import android.app.AlarmManager
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.SharedPreferences
import androidx.core.app.NotificationCompat
import java.net.HttpURLConnection
import java.net.URL
import java.util.concurrent.Executors

object PollingNotification {

    private const val ACTION_POLL = "com.itoktoni.coldstorage.POLL_ALARM"
    private const val EXTRA_URL = "url"
    private const val EXTRA_INTERVAL = "interval_seconds"
    private const val PREFS_NAME = "polling_notification"
    private const val KEY_LAST_ID = "last_notification_id"
    private const val KEY_IS_POLLING = "is_polling"
    private const val KEY_POLL_URL = "poll_url"
    private const val KEY_POLL_INTERVAL = "poll_interval"

    private val executor = Executors.newSingleThreadExecutor()

    fun startPolling(context: Context, url: String, intervalSeconds: Int = 60) {
        val prefs = getPrefs(context)
        prefs.edit().putBoolean(KEY_IS_POLLING, true)
            .putString(KEY_POLL_URL, url)
            .putInt(KEY_POLL_INTERVAL, intervalSeconds)
            .apply()

        scheduleAlarm(context, url, intervalSeconds)

        // Do an immediate first poll
        executor.execute { doPoll(context, url) }
    }

    fun stopPolling(context: Context) {
        getPrefs(context).edit().putBoolean(KEY_IS_POLLING, false).apply()

        val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        val intent = Intent(context, PollReceiver::class.java).apply {
            action = ACTION_POLL
        }
        val pendingIntent = PendingIntent.getBroadcast(
            context, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        alarmManager.cancel(pendingIntent)
    }

    fun isPolling(context: Context): Boolean {
        return getPrefs(context).getBoolean(KEY_IS_POLLING, false)
    }

    fun getStatus(context: Context): String {
        val prefs = getPrefs(context)
        val isPolling = prefs.getBoolean(KEY_IS_POLLING, false)
        val url = prefs.getString(KEY_POLL_URL, "") ?: ""
        val interval = prefs.getInt(KEY_POLL_INTERVAL, 60)
        val lastId = prefs.getLong(KEY_LAST_ID, 0)

        return org.json.JSONObject().apply {
            put("polling", isPolling)
            put("url", url)
            put("interval", interval)
            put("lastNotificationId", lastId)
        }.toString()
    }

    private fun scheduleAlarm(context: Context, url: String, intervalSeconds: Int) {
        val alarmManager = context.getSystemService(Context.ALARM_SERVICE) as AlarmManager
        val intent = Intent(context, PollReceiver::class.java).apply {
            action = ACTION_POLL
            putExtra(EXTRA_URL, url)
            putExtra(EXTRA_INTERVAL, intervalSeconds)
        }

        val pendingIntent = PendingIntent.getBroadcast(
            context, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val intervalMs = intervalSeconds * 1000L
        alarmManager.setRepeating(
            AlarmManager.RTC_WAKEUP,
            System.currentTimeMillis() + intervalMs,
            intervalMs,
            pendingIntent
        )
    }

    private fun getPrefs(context: Context): SharedPreferences {
        return context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    }

    internal fun doPoll(context: Context, url: String) {
        try {
            val connection = URL(url).openConnection() as HttpURLConnection
            connection.requestMethod = "GET"
            connection.connectTimeout = 10000
            connection.readTimeout = 10000
            connection.setRequestProperty("Accept", "application/json")

            if (connection.responseCode == 200) {
                val response = connection.inputStream.bufferedReader().readText()
                val json = org.json.JSONObject(response)

                val notifications = json.optJSONArray("notifications")

                if (notifications != null) {
                    // Array of notifications
                    for (i in 0 until notifications.length()) {
                        val notif = notifications.getJSONObject(i)
                        val id = notif.optLong("id", 0)
                        val title = notif.optString("title", "Notification")
                        val body = notif.optString("body", "")

                        if (id > getLastNotificationId(context) && body.isNotEmpty()) {
                            showNotification(context, id.toInt(), title, body)
                            setLastNotificationId(context, id)
                        }
                    }
                } else {
                    // Single notification response
                    val id = json.optLong("id", System.currentTimeMillis())
                    val title = json.optString("title", "Notification")
                    val body = json.optString("body", "")

                    if (body.isNotEmpty() && id > getLastNotificationId(context)) {
                        showNotification(context, id.toInt(), title, body)
                        setLastNotificationId(context, id)
                    }
                }

                // Notify JavaScript
                NativeBridge.notifyPushNotification(
                    json.optString("title", "Poll"),
                    json.optString("body", ""),
                    mapOf("type" to "poll")
                )
            }

            connection.disconnect()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun getLastNotificationId(context: Context): Long {
        return getPrefs(context).getLong(KEY_LAST_ID, 0)
    }

    private fun setLastNotificationId(context: Context, id: Long) {
        getPrefs(context).edit().putLong(KEY_LAST_ID, id).apply()
    }

    private fun showNotification(context: Context, id: Int, title: String, body: String) {
        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }

        val pendingIntent = PendingIntent.getActivity(
            context, id, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(context, AppConfig.CHANNEL_ID_PUSH)
            .setSmallIcon(R.drawable.ic_splash_logo)
            .setContentTitle(title)
            .setContentText(body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(body))
            .setAutoCancel(true)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setContentIntent(pendingIntent)
            .build()

        val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(id, notification)
    }

    class PollReceiver : BroadcastReceiver() {
        override fun onReceive(context: Context, intent: Intent) {
            if (intent.action == ACTION_POLL) {
                val url = intent.getStringExtra(EXTRA_URL) ?: return

                // Run on background thread
                executor.execute { doPoll(context, url) }
            }
        }
    }
}
