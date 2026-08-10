import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/inscription_service.dart';
import '../theme.dart';
import 'login_screen.dart';

class InscriptionCodeScreen extends StatefulWidget {
  final String email;

  const InscriptionCodeScreen({super.key, required this.email});

  @override
  State<InscriptionCodeScreen> createState() => _InscriptionCodeScreenState();
}

class _InscriptionCodeScreenState extends State<InscriptionCodeScreen> {
  final _controleurCode = TextEditingController();
  final _inscriptionService = InscriptionService();

  bool _enCours = false;
  String? _erreur;
  String? _info;

  @override
  void dispose() {
    _controleurCode.dispose();
    super.dispose();
  }

  Future<void> _valider() async {
    if (_controleurCode.text.length != 6) {
      setState(() => _erreur = 'Le code doit contenir 6 chiffres.');
      return;
    }

    setState(() {
      _enCours = true;
      _erreur = null;
      _info = null;
    });

    try {
      await _inscriptionService.verifierCode(widget.email, _controleurCode.text);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Compte créé avec succès ! Vous pouvez maintenant vous connecter.')),
      );
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _renvoyer() async {
    setState(() {
      _erreur = null;
      _info = null;
    });

    try {
      await _inscriptionService.renvoyerCode(widget.email);
      setState(() => _info = 'Un nouveau code vous a été envoyé.');
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Vérification')),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'Un code a été envoyé à ${widget.email}',
                  style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32),
                TextField(
                  controller: _controleurCode,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: CouleursFlamattitude.texte, fontSize: 24, letterSpacing: 8),
                  decoration: const InputDecoration(counterText: ''),
                ),
                if (_erreur != null) ...[
                  const SizedBox(height: 8),
                  Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent), textAlign: TextAlign.center),
                ],
                if (_info != null) ...[
                  const SizedBox(height: 8),
                  Text(_info!, style: const TextStyle(color: CouleursFlamattitude.texte), textAlign: TextAlign.center),
                ],
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _enCours ? null : _valider,
                    child: _enCours
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Valider'),
                  ),
                ),
                const SizedBox(height: 12),
                TextButton(onPressed: _enCours ? null : _renvoyer, child: const Text('Renvoyer le code')),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
