import 'package:flutter/material.dart';

/// Palette reprise de public/css/styleconnexion.css (site web), pour que l'app
/// Flutter garde la même identité visuelle que la connexion sur le site.
class CouleursFlamattitude {
  static const fond = Color(0xFF141210); // --bleuCY
  static const champ = Color(0xFF4A443D); // --grisfr
  static const texte = Color(0xFFF2D9B8); // --uranian
  static const accent = Color(0xFFD9531E); // --flamme
}

ThemeData construireTheme() {
  return ThemeData(
    brightness: Brightness.dark,
    scaffoldBackgroundColor: CouleursFlamattitude.fond,
    colorScheme: ColorScheme.fromSeed(
      seedColor: CouleursFlamattitude.accent,
      brightness: Brightness.dark,
      surface: CouleursFlamattitude.fond,
      primary: CouleursFlamattitude.accent,
    ),
    textTheme: ThemeData.dark().textTheme.apply(
          bodyColor: CouleursFlamattitude.texte,
          displayColor: CouleursFlamattitude.texte,
        ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: CouleursFlamattitude.champ,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: CouleursFlamattitude.accent, width: 2),
      ),
      labelStyle: const TextStyle(color: CouleursFlamattitude.texte),
      hintStyle: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6)),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: CouleursFlamattitude.accent,
        foregroundColor: Colors.white,
        padding: const EdgeInsets.symmetric(vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(foregroundColor: CouleursFlamattitude.accent),
    ),
  );
}
