import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/inscription_service.dart';
import '../theme.dart';
import 'inscription_code_screen.dart';

class InscriptionScreen extends StatefulWidget {
  const InscriptionScreen({super.key});

  @override
  State<InscriptionScreen> createState() => _InscriptionScreenState();
}

class _InscriptionScreenState extends State<InscriptionScreen> {
  final _cleFormulaire = GlobalKey<FormState>();
  final _controleurNom = TextEditingController();
  final _controleurPrenom = TextEditingController();
  final _controleurTelephone = TextEditingController();
  final _controleurAdresse = TextEditingController();
  final _controleurPseudo = TextEditingController();
  final _controleurEmail = TextEditingController();
  final _controleurMotDePasse = TextEditingController();
  final _controleurConfirmation = TextEditingController();
  final _inscriptionService = InscriptionService();

  bool _enCours = false;
  String? _erreur;

  @override
  void dispose() {
    _controleurNom.dispose();
    _controleurPrenom.dispose();
    _controleurTelephone.dispose();
    _controleurAdresse.dispose();
    _controleurPseudo.dispose();
    _controleurEmail.dispose();
    _controleurMotDePasse.dispose();
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
      await _inscriptionService.creer(
        nom: _controleurNom.text.trim(),
        prenom: _controleurPrenom.text.trim(),
        telephone: _controleurTelephone.text.trim(),
        adresse: _controleurAdresse.text.trim(),
        pseudo: _controleurPseudo.text.trim(),
        email: _controleurEmail.text.trim(),
        motDePasse: _controleurMotDePasse.text,
        motDePasseConfirmation: _controleurConfirmation.text,
      );

      if (!mounted) return;
      Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => InscriptionCodeScreen(email: _controleurEmail.text.trim())),
      );
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
      appBar: AppBar(title: const Text('Créer un compte')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _cleFormulaire,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _controleurNom,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Nom'),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le nom est obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurPrenom,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Prénom'),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le prénom est obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurTelephone,
                  keyboardType: TextInputType.phone,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Téléphone'),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le téléphone est obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurAdresse,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Adresse'),
                  validator: (v) => (v == null || v.isEmpty) ? "L'adresse est obligatoire." : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurPseudo,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Identifiant'),
                  validator: (v) => (v == null || v.isEmpty) ? "L'identifiant est obligatoire." : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurEmail,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Email'),
                  validator: (v) => (v == null || !v.contains('@')) ? 'Adresse email invalide.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurMotDePasse,
                  obscureText: true,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(
                    labelText: 'Mot de passe',
                    helperText: 'Au moins 10 caractères, majuscule, minuscule, chiffre et symbole.',
                    helperMaxLines: 2,
                  ),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le mot de passe est obligatoire.' : null,
                ),
                const SizedBox(height: 14),
                TextFormField(
                  controller: _controleurConfirmation,
                  obscureText: true,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Confirmer le mot de passe'),
                  validator: (v) => v != _controleurMotDePasse.text ? 'La confirmation ne correspond pas.' : null,
                ),
                if (_erreur != null) ...[
                  const SizedBox(height: 16),
                  Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent), textAlign: TextAlign.center),
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
                      : const Text('Continuer'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
