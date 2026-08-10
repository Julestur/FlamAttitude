import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme.dart';

class MotDePasseOublieScreen extends StatefulWidget {
  const MotDePasseOublieScreen({super.key});

  @override
  State<MotDePasseOublieScreen> createState() => _MotDePasseOublieScreenState();
}

class _MotDePasseOublieScreenState extends State<MotDePasseOublieScreen> {
  final _cleFormulaire = GlobalKey<FormState>();
  final _controleurEmail = TextEditingController();
  final _authService = AuthService();

  bool _enCours = false;
  String? _erreur;
  String? _confirmation;

  @override
  void dispose() {
    _controleurEmail.dispose();
    super.dispose();
  }

  Future<void> _envoyer() async {
    if (!_cleFormulaire.currentState!.validate()) return;

    setState(() {
      _enCours = true;
      _erreur = null;
      _confirmation = null;
    });

    try {
      await _authService.demanderReinitialisationMotDePasse(_controleurEmail.text.trim());
      setState(() => _confirmation =
          'Si un compte existe pour cette adresse, un email vient de vous être envoyé. Ouvrez le lien qu\'il contient dans votre navigateur pour choisir un nouveau mot de passe.');
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mot de passe oublié')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _cleFormulaire,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text(
                  'Entrez votre adresse email, nous vous enverrons un lien pour réinitialiser votre mot de passe.',
                  style: TextStyle(color: CouleursFlamattitude.texte),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: _controleurEmail,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Email'),
                  validator: (v) => (v == null || !v.contains('@')) ? 'Adresse email invalide.' : null,
                ),
                if (_erreur != null) ...[
                  const SizedBox(height: 16),
                  Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent), textAlign: TextAlign.center),
                ],
                if (_confirmation != null) ...[
                  const SizedBox(height: 16),
                  Text(_confirmation!, style: const TextStyle(color: CouleursFlamattitude.texte), textAlign: TextAlign.center),
                ],
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _enCours ? null : _envoyer,
                  child: _enCours
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Envoyer le lien'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
