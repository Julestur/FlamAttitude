import 'dart:io';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../services/client_service.dart';
import '../theme.dart';

class DossierClientScreen extends StatefulWidget {
  final int idClient;

  const DossierClientScreen({super.key, required this.idClient});

  @override
  State<DossierClientScreen> createState() => _DossierClientScreenState();
}

class _DossierClientScreenState extends State<DossierClientScreen> {
  final _clientService = ClientService();
  final _controleurTelephone = TextEditingController();
  final _controleurAdresse = TextEditingController();

  bool _enCours = true;
  String? _erreur;
  Map<String, dynamic>? _dossier;

  @override
  void initState() {
    super.initState();
    _charger();
  }

  @override
  void dispose() {
    _controleurTelephone.dispose();
    _controleurAdresse.dispose();
    super.dispose();
  }

  Future<void> _charger() async {
    setState(() {
      _enCours = true;
      _erreur = null;
    });

    try {
      final dossier = await _clientService.dossier(widget.idClient);
      final client = dossier['client'] as Map<String, dynamic>;
      _controleurTelephone.text = (client['telephone'] as String?) ?? '';
      _controleurAdresse.text = (client['adresse'] as String?) ?? '';
      if (mounted) setState(() => _dossier = dossier);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _enregistrerContact() async {
    try {
      await _clientService.modifierContact(
        widget.idClient,
        telephone: _controleurTelephone.text.trim(),
        adresse: _controleurAdresse.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Coordonnées mises à jour.')));
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _ajouterDocument() async {
    final controleurNom = TextEditingController();
    PlatformFile? fichier;

    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => StatefulBuilder(
        builder: (contexteDialogue, setStateDialogue) => AlertDialog(
          title: const Text('Ajouter un document'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: controleurNom, decoration: const InputDecoration(labelText: 'Nom du document')),
              const SizedBox(height: 12),
              OutlinedButton.icon(
                onPressed: () async {
                  final resultat = await FilePicker.pickFiles();
                  if (resultat != null) setStateDialogue(() => fichier = resultat.files.single);
                },
                icon: const Icon(Icons.attach_file),
                label: Text(fichier?.name ?? 'Choisir un fichier'),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Annuler')),
            ElevatedButton(onPressed: () => Navigator.pop(contexteDialogue, true), child: const Text('Ajouter')),
          ],
        ),
      ),
    );

    if (confirme != true || fichier == null || controleurNom.text.trim().isEmpty) return;

    try {
      await _clientService.ajouterDocument(widget.idClient, controleurNom.text.trim(), fichier!.path!);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Document ajouté.')));
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _ajouterFacture() async {
    final controleurMontant = TextEditingController();
    final controleurDescription = TextEditingController();
    final controleurDate = TextEditingController(text: DateTime.now().toIso8601String().split('T').first);
    String statut = 'en_attente';
    PlatformFile? fichier;

    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => StatefulBuilder(
        builder: (contexteDialogue, setStateDialogue) => AlertDialog(
          title: const Text('Ajouter une facture'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: controleurMontant,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: 'Montant (€)'),
                ),
                const SizedBox(height: 12),
                TextField(controller: controleurDescription, decoration: const InputDecoration(labelText: 'Description')),
                const SizedBox(height: 12),
                TextField(
                  controller: controleurDate,
                  decoration: const InputDecoration(labelText: 'Date d\'émission (AAAA-MM-JJ)'),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: statut,
                  items: const [
                    DropdownMenuItem(value: 'en_attente', child: Text('En attente')),
                    DropdownMenuItem(value: 'payee', child: Text('Payée')),
                  ],
                  onChanged: (v) => setStateDialogue(() => statut = v!),
                  decoration: const InputDecoration(labelText: 'Statut'),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: () async {
                    final resultat = await FilePicker.pickFiles(type: FileType.custom, allowedExtensions: ['pdf']);
                    if (resultat != null) setStateDialogue(() => fichier = resultat.files.single);
                  },
                  icon: const Icon(Icons.picture_as_pdf_outlined),
                  label: Text(fichier?.name ?? 'PDF (optionnel)'),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Annuler')),
            ElevatedButton(onPressed: () => Navigator.pop(contexteDialogue, true), child: const Text('Ajouter')),
          ],
        ),
      ),
    );

    if (confirme != true) return;

    try {
      await _clientService.ajouterFacture(
        widget.idClient,
        montant: controleurMontant.text.trim(),
        description: controleurDescription.text.trim(),
        statut: statut,
        dateEmission: controleurDate.text.trim(),
        cheminFichier: fichier?.path,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Facture ajoutée.')));
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final client = _dossier?['client'] as Map<String, dynamic>?;

    return Scaffold(
      appBar: AppBar(title: Text(client != null ? '${client['prenom']} ${client['nom']}' : 'Dossier client')),
      body: SafeArea(child: _corps()),
    );
  }

  Widget _corps() {
    if (_enCours) return const Center(child: CircularProgressIndicator());

    if (_erreur != null) {
      return Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)));
    }

    final dossier = _dossier!;
    final client = dossier['client'] as Map<String, dynamic>;
    final documents = dossier['documents'] as List<dynamic>;
    final factures = dossier['factures'] as List<dynamic>;
    final rdv = dossier['rdv'] as List<dynamic>;

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(client['email'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
        const SizedBox(height: 24),
        _titreSection('Coordonnées'),
        TextField(
          controller: _controleurTelephone,
          style: const TextStyle(color: CouleursFlamattitude.texte),
          decoration: const InputDecoration(labelText: 'Téléphone'),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _controleurAdresse,
          style: const TextStyle(color: CouleursFlamattitude.texte),
          decoration: const InputDecoration(labelText: 'Adresse'),
        ),
        const SizedBox(height: 12),
        OutlinedButton(onPressed: _enregistrerContact, child: const Text('Enregistrer')),
        const SizedBox(height: 28),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _titreSection('Documents'),
            IconButton(onPressed: _ajouterDocument, icon: const Icon(Icons.add_circle_outline, color: CouleursFlamattitude.accent)),
          ],
        ),
        if (documents.isEmpty) _texteVide('Aucun document.'),
        ...documents.map((d) => _ligneDocument(d as Map<String, dynamic>)),
        const SizedBox(height: 20),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            _titreSection('Factures'),
            IconButton(onPressed: _ajouterFacture, icon: const Icon(Icons.add_circle_outline, color: CouleursFlamattitude.accent)),
          ],
        ),
        if (factures.isEmpty) _texteVide('Aucune facture.'),
        ...factures.map((f) => _ligneFacture(f as Map<String, dynamic>)),
        const SizedBox(height: 20),
        _titreSection('Historique des rendez-vous'),
        if (rdv.isEmpty) _texteVide('Aucun rendez-vous.'),
        ...rdv.map((r) => _ligneRdv(r as Map<String, dynamic>)),
      ],
    );
  }

  Widget _titreSection(String texte) => Text(
        texte,
        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: CouleursFlamattitude.texte),
      );

  Widget _texteVide(String texte) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text(texte, style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6))),
      );

  Widget _ligneDocument(Map<String, dynamic> d) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.description_outlined, color: CouleursFlamattitude.accent),
      title: Text(d['nom'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
      subtitle: Text(
        'Ajouté par ${d['ajoutePrenom'] ?? ''} ${d['ajouteNom'] ?? ''}',
        style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6)),
      ),
      onTap: () => launchUrl(Uri.parse(d['url'] as String), mode: LaunchMode.externalApplication),
    );
  }

  Widget _ligneFacture(Map<String, dynamic> f) {
    final payee = f['statut'] == 'payee';
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(Icons.receipt_long, color: payee ? Colors.greenAccent : CouleursFlamattitude.accent),
      title: Text(f['description'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
      subtitle: Text(
        '${f['montant']} € · ${f['date_emission']} · ${payee ? 'Payée' : 'En attente'}',
        style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6)),
      ),
      onTap: f['url'] != null
          ? () => launchUrl(Uri.parse(f['url'] as String), mode: LaunchMode.externalApplication)
          : null,
    );
  }

  Widget _ligneRdv(Map<String, dynamic> r) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: const Icon(Icons.event, color: CouleursFlamattitude.accent),
      title: Text(
        '${r['typeNom']} — ${r['date']} ${r['heure_debut']}-${r['heure_fin']}',
        style: const TextStyle(color: CouleursFlamattitude.texte),
      ),
      subtitle: Text(
        'Avec ${r['membrePrenom']} ${r['membreNom']}${r['motif'] != null ? ' · ${r['motif']}' : ''}',
        style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6)),
      ),
    );
  }
}
