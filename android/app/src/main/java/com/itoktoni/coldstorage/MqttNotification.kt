package com.itoktoni.coldstorage

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import androidx.core.app.NotificationCompat
import org.eclipse.paho.client.mqttv3.MqttCallback
import org.eclipse.paho.client.mqttv3.MqttClient
import org.eclipse.paho.client.mqttv3.MqttConnectOptions
import org.eclipse.paho.client.mqttv3.MqttMessage
import org.eclipse.paho.client.mqttv3.persist.MemoryPersistence
import org.json.JSONObject

object MqttNotification {

    private var mqttClient: MqttClient? = null

    fun connect(brokerUrl: String, clientId: String, topic: String) {
        try {
            val persistence = MemoryPersistence()
            mqttClient = MqttClient(brokerUrl, clientId, persistence)

            val options = MqttConnectOptions().apply {
                isCleanSession = true
                connectionTimeout = 10
                keepAliveInterval = 20
            }

            mqttClient?.setCallback(object : MqttCallback {
                override fun connectionLost(cause: Throwable?) {
                    NativeBridge.notifyNetworkStatus("mqtt_disconnected")
                }

                override fun messageArrived(topic: String?, message: MqttMessage?) {
                    message?.let {
                        try {
                            val json = JSONObject(String(it.payload))
                            val title = json.optString("title", "Notification")
                            val body = json.optString("body", "")

                            NativeBridge.notifyPushNotification(title, body, mutableMapOf())
                            showNotification(title, body)
                        } catch (e: Exception) {
                            e.printStackTrace()
                        }
                    }
                }

                override fun deliveryComplete(token: org.eclipse.paho.client.mqttv3.IMqttDeliveryToken?) {}
            })

            mqttClient?.connect(options)
            mqttClient?.subscribe(topic)
            NativeBridge.notifyNetworkStatus("mqtt_connected")
        } catch (e: Exception) {
            e.printStackTrace()
            NativeBridge.notifyNetworkStatus("mqtt_error")
        }
    }

    fun disconnect() {
        try {
            mqttClient?.disconnect()
            mqttClient = null
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun publish(topic: String, message: String) {
        try {
            mqttClient?.publish(topic, MqttMessage(message.toByteArray()))
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    private fun showNotification(title: String, body: String) {
        val intent = Intent(NativeBridge.getContext(), MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }

        val pendingIntent = PendingIntent.getActivity(
            NativeBridge.getContext(), System.currentTimeMillis().toInt(), intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(NativeBridge.getContext(), AppConfig.CHANNEL_ID_DOWNLOADS)
            .setSmallIcon(R.drawable.ic_splash_logo)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .build()

        val manager = NativeBridge.getContext().getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(System.currentTimeMillis().toInt(), notification)
    }
}
