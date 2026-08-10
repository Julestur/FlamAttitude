import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';

import 'screens/demarrage_screen.dart';
import 'services/notification_service.dart';
import 'theme.dart';

final navigatorKey = GlobalKey<NavigatorState>();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  await NotificationService().initialiser(navigatorKey);

  runApp(const FlamattitudeApp());
}

class FlamattitudeApp extends StatelessWidget {
  const FlamattitudeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: navigatorKey,
      title: "Flam'Attitude",
      debugShowCheckedModeBanner: false,
      theme: construireTheme(),
      home: const DemarrageScreen(),
    );
  }
}
