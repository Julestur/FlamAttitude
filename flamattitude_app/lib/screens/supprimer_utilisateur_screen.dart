import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/utilisateur_service.dart';
import '../theme.dart';

class SupprimerUtilisateurScreen extends StatefulWidget {
  const SupprimerUtilisateurScreen({super.key});

  @override
  State<SupprimerUtilisateurScreen> createState() => _SupprimerUtilisateurScreenState();
}

class _SupprimerUtilisateurScreenState extends State<SupprimerUtilisateurScreen> {
  final _utilisateurService = UtilisateurService();
  final _controleurRecherche = TextEditingController();
  Timer? _debounce;

  bool _enCours = true;
  String? _erreur;
  List<dynamic> _utilisateurs = [];

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
      final utilisateurs = await _utilisateurService.listerPourSuppression(_controleurRecherche.text.trim());
      if (mounted) setState(() => _utilisateurs = utilisateurs);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _confirmerSuppression(Map<String, dynamic> utilisateur) async {
    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => AlertDialog(
        title: const Text('Confirmer la suppression'),
        content: Text(
          'Supprimer définitivement ${utilisateur['prenom']} ${utilisateur['nom']} '
          '(${utilisateur['grade']}) ? Cette action est irréversible.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: CouleursFlamattitude.accent),
            onPressed: () => Navigator.pop(contexteDialogue, true),
            child: const Text('Supprimer'),
          ),
        ],
      ),
    );

    if (confirme != true) return;

    try {
      await _utilisateurService.supprimer(utilisateur['idUtilisateur'] as int);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Suppression effectuée avec succès.')));
      _rechercher();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Supprimer un utilisateur')),
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
                  labelText: 'Nom, prénom ou identifiant',
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
    if (_enCours) return const Center(child: CircularProgressIndicator());

    if (_erreur != null) {
      return Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)));
    }

    if (_utilisateurs.isEmpty) {
      return const Center(child: Text('Aucun utilisateur trouvé.', style: TextStyle(color: CouleursFlamattitude.texte)));
    }

    return ListView.builder(
      itemCount: _utilisateurs.length,
      itemBuilder: (context, index) {
        final utilisateur = _utilisateurs[index] as Map<String, dynamic>;
        return ListTile(
          title: Text(
            '${utilisateur['prenom']} ${utilisateur['nom']}',
            style: const TextStyle(color: CouleursFlamattitude.texte),
          ),
          subtitle: Text(
            '${utilisateur['identifiant']} · ${utilisateur['grade']}',
            style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
          ),
          trailing: IconButton(
            icon: const Icon(Icons.delete_outline, color: CouleursFlamattitude.accent),
            onPressed: () => _confirmerSuppression(utilisateur),
          ),
        );
      },
    );
  }
}
