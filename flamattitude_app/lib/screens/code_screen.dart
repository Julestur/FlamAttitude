import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme.dart';
import 'accueil_screen.dart';

class CodeScreen extends StatefulWidget {
  final String email;

  const CodeScreen({super.key, required this.email});

  @override
  State<CodeScreen> createState() => _CodeScreenState();
}

class _CodeScreenState extends State<CodeScreen> {
  final _controleurCode = TextEditingController();
  final _authService = AuthService();

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
      final resultat = await _authService.verifierCode(widget.email, _controleurCode.text);

      if (resultat.jetonAppareilPropose != null) {
        if (!mounted) return;
        final activer = await _demanderActivationBiometrie();
        if (activer == true) {
          await _authService.activerBiometrie(resultat.jetonAppareilPropose!);
        }
      }

      if (!mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => AccueilScreen(utilisateur: resultat.utilisateur)),
        (route) => false,
      );
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur. Vérifiez votre connexion.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<bool?> _demanderActivationBiometrie() {
    return showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (contexteDialogue) => AlertDialog(
        title: const Text('Connexion rapide'),
        content: const Text(
          "Voulez-vous utiliser votre empreinte digitale ou la reconnaissance faciale pour vous reconnecter plus rapidement la prochaine fois ?",
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Non merci')),
          ElevatedButton(onPressed: () => Navigator.pop(contexteDialogue, true), child: const Text('Activer')),
        ],
      ),
    );
  }

  Future<void> _renvoyer() async {
    setState(() {
      _erreur = null;
      _info = null;
    });

    try {
      await _authService.renvoyerCode(widget.email);
      setState(() => _info = 'Un nouveau code vous a été envoyé.');
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur. Vérifiez votre connexion.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Code de vérification',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: CouleursFlamattitude.texte,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Envoyé à ${widget.email}',
                  style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32),
                TextField(
                  controller: _controleurCode,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: CouleursFlamattitude.texte,
                    fontSize: 24,
                    letterSpacing: 8,
                  ),
                  decoration: const InputDecoration(counterText: ''),
                ),
                if (_erreur != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    _erreur!,
                    style: const TextStyle(color: CouleursFlamattitude.accent),
                    textAlign: TextAlign.center,
                  ),
                ],
                if (_info != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    _info!,
                    style: const TextStyle(color: CouleursFlamattitude.texte),
                    textAlign: TextAlign.center,
                  ),
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
                TextButton(
                  onPressed: _enCours ? null : _renvoyer,
                  child: const Text('Renvoyer le code'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
