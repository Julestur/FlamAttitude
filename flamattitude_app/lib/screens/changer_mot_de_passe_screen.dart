import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/compte_service.dart';
import '../theme.dart';

class ChangerMotDePasseScreen extends StatefulWidget {
  const ChangerMotDePasseScreen({super.key});

  @override
  State<ChangerMotDePasseScreen> createState() => _ChangerMotDePasseScreenState();
}

class _ChangerMotDePasseScreenState extends State<ChangerMotDePasseScreen> {
  final _cleFormulaire = GlobalKey<FormState>();
  final _controleurAncien = TextEditingController();
  final _controleurNouveau = TextEditingController();
  final _controleurConfirmation = TextEditingController();
  final _compteService = CompteService();

  bool _enCours = false;
  String? _erreur;

  @override
  void dispose() {
    _controleurAncien.dispose();
    _controleurNouveau.dispose();
    _controleurConfirmation.dispose();
    super.dispose();
  }

  Future<void> _valider() async {
    if (!_cleFormulaire.currentState!.validate()) return;

    setState(() {
      _enCours = true;
      _erreur = null;
    });

    try {
      await _compteService.changerMotDePasse(
        ancienMotDePasse: _controleurAncien.text,
        motDePasse: _controleurNouveau.text,
        motDePasseConfirmation: _controleurConfirmation.text,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Votre mot de passe a bien été mis à jour !')),
      );
      Navigator.pop(context);
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
      appBar: AppBar(title: const Text('Changer mot de passe')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _cleFormulaire,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _controleurAncien,
                  obscureText: true,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Ancien mot de passe'),
                  validator: (v) => (v == null || v.isEmpty) ? "L'ancien mot de passe est requis." : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _controleurNouveau,
                  obscureText: true,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(
                    labelText: 'Nouveau mot de passe',
                    helperText: 'Au moins 10 caractères, majuscule, minuscule, chiffre et symbole.',
                    helperMaxLines: 2,
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le nouveau mot de passe est requis.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _controleurConfirmation,
                  obscureText: true,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Confirmer le nouveau mot de passe'),
                  validator: (v) => v != _controleurNouveau.text ? 'La confirmation ne correspond pas.' : null,
                ),
                if (_erreur != null) ...[
                  const SizedBox(height: 16),
                  Text(
                    _erreur!,
                    style: const TextStyle(color: CouleursFlamattitude.accent),
                    textAlign: TextAlign.center,
                  ),
                ],
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _enCours ? null : _valider,
                  child: _enCours
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Valider'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
