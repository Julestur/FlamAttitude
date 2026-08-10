import 'package:flutter/material.dart';

import '../services/auth_service.dart';
import '../theme.dart';
import 'login_screen.dart';
import 'principal_screen.dart';

/// Écran affiché au lancement de l'app : tente une reconnexion silencieuse par
/// biométrie si un jeton d'appareil de confiance est stocké (staff/admin ayant
/// déjà fait une connexion complète), sinon bascule directement sur le login.
class DemarrageScreen extends StatefulWidget {
  const DemarrageScreen({super.key});

  @override
  State<DemarrageScreen> createState() => _DemarrageScreenState();
}

class _DemarrageScreenState extends State<DemarrageScreen> {
  final _authService = AuthService();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _verifier());
  }

  Future<void> _verifier() async {
    Map<String, dynamic>? utilisateur;

    try {
      if (await _authService.aUnJetonAppareil()) {
        utilisateur = await _authService.tenterConnexionBiometrique();
      }
    } catch (_) {
      utilisateur = null;
    }

    if (!mounted) return;

    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => utilisateur != null ? PrincipalScreen(utilisateur: utilisateur) : const LoginScreen(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(color: CouleursFlamattitude.accent),
      ),
    );
  }
}
