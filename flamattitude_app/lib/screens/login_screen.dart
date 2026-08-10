import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme.dart';
import 'code_screen.dart';
import 'inscription_screen.dart';
import 'mot_de_passe_oublie_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _cleFormulaire = GlobalKey<FormState>();
  final _controleurEmail = TextEditingController();
  final _controleurMotDePasse = TextEditingController();
  final _authService = AuthService();

  bool _enCours = false;
  String? _erreur;

  @override
  void dispose() {
    _controleurEmail.dispose();
    _controleurMotDePasse.dispose();
    super.dispose();
  }

  Future<void> _seConnecter() async {
    if (!_cleFormulaire.currentState!.validate()) return;

    setState(() {
      _enCours = true;
      _erreur = null;
    });

    final email = _controleurEmail.text.trim();

    try {
      await _authService.login(email, _controleurMotDePasse.text);

      if (!mounted) return;
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => CodeScreen(email: email)),
      );
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur. Vérifiez votre connexion.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Form(
              key: _cleFormulaire,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Image.asset(
                    'assets/icone/logo_login.png',
                    height: 150,
                  ),
                  const SizedBox(height: 40),
                  TextFormField(
                    controller: _controleurEmail,
                    keyboardType: TextInputType.emailAddress,
                    style: const TextStyle(color: CouleursFlamattitude.texte),
                    decoration: const InputDecoration(labelText: 'Email'),
                    validator: (valeur) {
                      if (valeur == null || !valeur.contains('@')) {
                        return 'Adresse email invalide';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _controleurMotDePasse,
                    obscureText: true,
                    style: const TextStyle(color: CouleursFlamattitude.texte),
                    decoration: const InputDecoration(labelText: 'Mot de passe'),
                    validator: (valeur) {
                      if (valeur == null || valeur.isEmpty) {
                        return 'Mot de passe requis';
                      }
                      return null;
                    },
                  ),
                  if (_erreur != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      _erreur!,
                      style: const TextStyle(color: CouleursFlamattitude.accent),
                      textAlign: TextAlign.center,
                    ),
                  ],
                  const SizedBox(height: 28),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _enCours ? null : _seConnecter,
                      child: _enCours
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Text('Se connecter'),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const MotDePasseOublieScreen()),
                    ),
                    child: const Text('Mot de passe oublié ?'),
                  ),
                  TextButton(
                    onPressed: () => Navigator.push(
                      context,
                      MaterialPageRoute(builder: (_) => const InscriptionScreen()),
                    ),
                    child: const Text("Je n'ai pas encore de compte"),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
