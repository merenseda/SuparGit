import 'package:flutter/material.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';
import 'services/api_service.dart';

// Arka plan bildirim yakalayıcı
@pragma('vm:entry-point')
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  print('Arka planda bildirim geldi: ${message.messageId}');
}

final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
    FlutterLocalNotificationsPlugin();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Firebase Başlatma
  try {
    await Firebase.initializeApp();
    FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);

    // Bildirim İzni İsteme (Android 13+ ve iOS)
    final messaging = FirebaseMessaging.instance;
    await messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Cihaz FCM Token'ı al ve sunucuya ilet
    final fcmToken = await messaging.getToken();
    if (fcmToken != null) {
      print('Cihaz FCM Token: $fcmToken');
      await ApiService.registerFcmToken(fcmToken);
    }

    // Uygulama açıkken (Foreground) bildirim gösterme ayarı
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    const InitializationSettings initializationSettings =
        InitializationSettings(android: initializationSettingsAndroid);
    await flutterLocalNotificationsPlugin.initialize(initializationSettings);

    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      final notification = message.notification;
      if (notification != null) {
        flutterLocalNotificationsPlugin.show(
          notification.hashCode,
          notification.title,
          notification.body,
          const NotificationDetails(
            android: AndroidNotificationDetails(
              'gitpush_channel',
              'GitSüpar Bildirimleri',
              importance: Importance.max,
              priority: Priority.high,
            ),
          ),
        );
      }
    });
  } catch (e) {
    print('Firebase başlatılamadı: $e');
  }

  final token = await ApiService.getToken();
  runApp(GitSuparMobileApp(initialRoute: token != null ? '/home' : '/login'));
}

class GitSuparMobileApp extends StatelessWidget {
  final String initialRoute;
  const GitSuparMobileApp({super.key, required this.initialRoute});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'GitSüpar',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primaryColor: const Color(0xFF6366F1),
        scaffoldBackgroundColor: const Color(0xFF0F172A),
        fontFamily: 'Roboto',
      ),
      initialRoute: initialRoute,
      routes: {
        '/login': (context) => const LoginScreen(),
        '/home': (context) => const HomeScreen(),
      },
    );
  }
}
