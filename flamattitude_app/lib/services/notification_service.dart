import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_client.dart';
import 'auth_service.dart';

/// Gère les notifications push (Firebase Cloud Messaging) : demande de
/// permission, récupération/enregistrement du jeton FCM auprès du serveur,
/// et affichage d'une vraie notification système même quand l'app est ouverte
/// (par défaut, FCM ne le fait qu'en arrière-plan/app fermée).
class NotificationService {
  final _messagerie = FirebaseMessaging.instance;
  final _notificationsLocales = FlutterLocalNotificationsPlugin();
  final _api = ApiClient();
  final _authService = AuthService();

  static const _canal = AndroidNotificationChannel(
    'flamattitude_notifications',
    'Flam\'Attitude',
    description: 'Notifications de rendez-vous et mises à jour Flam\'Attitude',
    importance: Importance.high,
  );

  Future<void> initialiser(GlobalKey<NavigatorState> cleNavigateur) async {
    await _messagerie.requestPermission();

    await _notificationsLocales
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_canal);

    await _notificationsLocales.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
        iOS: DarwinInitializationSettings(),
      ),
    );

    FirebaseMessaging.onMessage.listen(_afficherNotificationSysteme);

    await enregistrerJetonAupresDuServeur();
    _messagerie.onTokenRefresh.listen((_) => enregistrerJetonAupresDuServeur());
  }

  void _afficherNotificationSysteme(RemoteMessage message) {
    final titre = message.notification?.title;
    final corps = message.notification?.body;

    if (titre == null && corps == null) return;

    _notificationsLocales.show(
      id: message.hashCode,
      title: titre,
      body: corps,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          _canal.id,
          _canal.name,
          channelDescription: _canal.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
        ),
        iOS: const DarwinNotificationDetails(),
      ),
    );
  }

  /// Envoie le jeton FCM courant au serveur, pour que le compte connecté
  /// puisse recevoir des notifications ciblées. Sans effet si pas connecté.
  Future<void> enregistrerJetonAupresDuServeur() async {
    final jetonSanctum = await _authService.jetonActuel();
    if (jetonSanctum == null) return;

    final jetonFcm = await _messagerie.getToken();
    if (jetonFcm == null) return;

    try {
      await _api.post('/compte/jeton-fcm', {'jeton_fcm': jetonFcm}, jeton: jetonSanctum);
    } catch (_) {
      // Pas grave si ça échoue une fois : sera retenté à la prochaine ouverture
      // de l'app ou au prochain rafraîchissement de jeton.
    }
  }
}
