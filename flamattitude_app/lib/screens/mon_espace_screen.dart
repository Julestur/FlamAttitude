import 'dart:io';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../services/espace_client_service.dart';
import '../theme.dart';

class MonEspaceScreen extends StatefulWidget {
  const MonEspaceScreen({super.key});

  @override
  State<MonEspaceScreen> createState() => _MonEspaceScreenState();
}

class _MonEspaceScreenState extends State<MonEspaceScreen> {
  final _espaceClientService = EspaceClientService();

  bool _enCours = true;
  String? _erreur;
  Map<String, dynamic>? _donnees;

  @override
  void initState() {
    super.initState();
    _charger();
  }

  Future<void> _charger() async {
    setState(() {
      _enCours = true;
      _erreur = null;
    });

    try {
      final donnees = await _espaceClientService.accueil();
      if (mounted) setState(() => _donnees = donnees);
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
      appBar: AppBar(title: const Text('Mes documents et factures')),
      body: SafeArea(
        child: RefreshIndicator(onRefresh: _charger, child: _corps()),
      ),
    );
  }

  Widget _corps() {
    if (_enCours && _donnees == null) return const Center(child: CircularProgressIndicator());

    if (_erreur != null && _donnees == null) {
      return ListView(
        children: [
          const SizedBox(height: 100),
          Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent))),
        ],
      );
    }

    final documents = _donnees!['documents'] as List<dynamic>;
    final factures = _donnees!['factures'] as List<dynamic>;

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        const Text('Mes documents', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: CouleursFlamattitude.texte)),
        const SizedBox(height: 12),
        if (documents.isEmpty) _texteVide('Aucun document.'),
        ...documents.map((d) => _ligneDocument(d as Map<String, dynamic>)),
        const SizedBox(height: 28),
        const Text('Mes factures', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: CouleursFlamattitude.texte)),
        const SizedBox(height: 12),
        if (factures.isEmpty) _texteVide('Aucune facture.'),
        ...factures.map((f) => _ligneFacture(f as Map<String, dynamic>)),
      ],
    );
  }

  Widget _texteVide(String texte) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text(texte, style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6))),
      );

  Widget _ligneDocument(Map<String, dynamic> d) {
    return Card(
      color: CouleursFlamattitude.champ,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: const Icon(Icons.description_outlined, color: CouleursFlamattitude.accent),
        title: Text(d['nom'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
        onTap: () => launchUrl(Uri.parse(d['url'] as String), mode: LaunchMode.externalApplication),
      ),
    );
  }

  Widget _ligneFacture(Map<String, dynamic> f) {
    final payee = f['statut'] == 'payee';
    return Card(
      color: CouleursFlamattitude.champ,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(Icons.receipt_long, color: payee ? Colors.greenAccent : CouleursFlamattitude.accent),
        title: Text(f['description'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
        subtitle: Text(
          '${f['montant']} € · ${f['date_emission']} · ${payee ? 'Payée' : 'En attente'}',
          style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6)),
        ),
        onTap: f['url'] != null
            ? () => launchUrl(Uri.parse(f['url'] as String), mode: LaunchMode.externalApplication)
            : null,
      ),
    );
  }
}
