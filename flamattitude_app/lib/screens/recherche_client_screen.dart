import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/client_service.dart';
import '../theme.dart';
import 'dossier_client_screen.dart';

class RechercheClientScreen extends StatefulWidget {
  const RechercheClientScreen({super.key});

  @override
  State<RechercheClientScreen> createState() => _RechercheClientScreenState();
}

class _RechercheClientScreenState extends State<RechercheClientScreen> {
  final _clientService = ClientService();
  final _controleurRecherche = TextEditingController();
  Timer? _debounce;

  bool _enCours = true;
  String? _erreur;
  List<dynamic> _clients = [];

  @override
  void initState() {
    super.initState();
    _rechercher();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _controleurRecherche.dispose();
    super.dispose();
  }

  void _surChangement(String valeur) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), _rechercher);
  }

  Future<void> _rechercher() async {
    setState(() {
      _enCours = true;
      _erreur = null;
    });

    try {
      final clients = await _clientService.rechercher(_controleurRecherche.text.trim());
      if (mounted) setState(() => _clients = clients);
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
      appBar: AppBar(title: const Text('Rechercher un client')),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: TextField(
                controller: _controleurRecherche,
                onChanged: _surChangement,
                style: const TextStyle(color: CouleursFlamattitude.texte),
                decoration: const InputDecoration(
                  labelText: 'Nom, prénom ou email',
                  prefixIcon: Icon(Icons.search),
                ),
              ),
            ),
            Expanded(child: _corps()),
          ],
        ),
      ),
    );
  }

  Widget _corps() {
    if (_enCours) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_erreur != null) {
      return Center(
        child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)),
      );
    }

    if (_clients.isEmpty) {
      return const Center(
        child: Text('Aucun client trouvé.', style: TextStyle(color: CouleursFlamattitude.texte)),
      );
    }

    return ListView.builder(
      itemCount: _clients.length,
      itemBuilder: (context, index) {
        final client = _clients[index] as Map<String, dynamic>;
        return ListTile(
          title: Text(
            '${client['prenom']} ${client['nom']}',
            style: const TextStyle(color: CouleursFlamattitude.texte),
          ),
          subtitle: Text(
            client['email'] as String,
            style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
          ),
          trailing: const Icon(Icons.chevron_right, color: CouleursFlamattitude.texte),
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (_) => DossierClientScreen(idClient: client['idUtilisateur'] as int),
              ),
            );
          },
        );
      },
    );
  }
}
