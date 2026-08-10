import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/utilisateur_service.dart';
import '../theme.dart';

class AjouterUtilisateurScreen extends StatefulWidget {
  const AjouterUtilisateurScreen({super.key});

  @override
  State<AjouterUtilisateurScreen> createState() => _AjouterUtilisateurScreenState();
}

class _AjouterUtilisateurScreenState extends State<AjouterUtilisateurScreen> {
  final _cleFormulaire = GlobalKey<FormState>();
  final _controleurNom = TextEditingController();
  final _controleurPrenom = TextEditingController();
  final _controleurEmail = TextEditingController();
  final _controleurIdentifiant = TextEditingController();
  final _controleurMotDePasse = TextEditingController();
  final _controleurConfirmation = TextEditingController();
  final _utilisateurService = UtilisateurService();

  String _grade = 'Client';
  bool _enCours = false;
  String? _erreur;

  @override
  void dispose() {
    _controleurNom.dispose();
    _controleurPrenom.dispose();
    _controleurEmail.dispose();
    _controleurIdentifiant.dispose();
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
      await _utilisateurService.creer(
        nom: _controleurNom.text.trim(),
        prenom: _controleurPrenom.text.trim(),
        email: _controleurEmail.text.trim(),
        identifiant: _controleurIdentifiant.text.trim(),
        motDePasse: _controleurMotDePasse.text,
        motDePasseConfirmation: _controleurConfirmation.text,
        grade: _grade,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Utilisateur créé.')));
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
      appBar: AppBar(title: const Text('Ajouter un utilisateur')),
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
                const SizedBox(height: 16),
                TextFormField(
                  controller: _controleurPrenom,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Prénom'),
                  validator: (v) => (v == null || v.isEmpty) ? 'Le prénom est obligatoire.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _controleurEmail,
                  keyboardType: TextInputType.emailAddress,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Email'),
                  validator: (v) => (v == null || !v.contains('@')) ? 'Adresse email invalide.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _controleurIdentifiant,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                  decoration: const InputDecoration(labelText: 'Identifiant'),
                  validator: (v) => (v == null || v.isEmpty) ? "L'identifiant est obligatoire." : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _grade,
                  items: const [
                    DropdownMenuItem(value: 'Client', child: Text('Client')),
                    DropdownMenuItem(value: 'Membre_Entreprise', child: Text("Membre de l'entreprise")),
                    DropdownMenuItem(value: 'Admin', child: Text('Admin')),
                  ],
                  onChanged: (v) => setState(() => _grade = v!),
                  decoration: const InputDecoration(labelText: 'Statut'),
                ),
                const SizedBox(height: 16),
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
                const SizedBox(height: 16),
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
                      : const Text('Créer'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
