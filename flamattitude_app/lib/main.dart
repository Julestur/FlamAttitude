import 'package:flutter/material.dart';

import 'screens/demarrage_screen.dart';
import 'theme.dart';

void main() {
  runApp(const FlamattitudeApp());
}

class FlamattitudeApp extends StatelessWidget {
  const FlamattitudeApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: "Flam'Attitude",
      debugShowCheckedModeBanner: false,
      theme: construireTheme(),
      home: const DemarrageScreen(),
    );
  }
}
