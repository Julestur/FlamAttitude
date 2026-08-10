import 'dart:io';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_client.dart';
import '../services/disponibilite_service.dart';
import '../theme.dart';

class MonEdtScreen extends StatefulWidget {
  const MonEdtScreen({super.key});

  @override
  State<MonEdtScreen> createState() => _MonEdtScreenState();
}

class _MonEdtScreenState extends State<MonEdtScreen> {
  final _disponibiliteService = DisponibiliteService();

  bool _enCours = true;
  String? _erreur;
  List<dynamic> _mesRdv = [];

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
      final mesRdv = await _disponibiliteService.monEdt();
      if (mounted) setState(() => _mesRdv = mesRdv);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _ouvrirGoogleMaps(String adresse) async {
    final url = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(adresse)}');
    await launchUrl(url, mode: LaunchMode.externalApplication);
  }

  Future<void> _ouvrirWaze(String adresse) async {
    final url = Uri.parse('https://waze.com/ul?q=${Uri.encodeComponent(adresse)}&navigate=yes');
    await launchUrl(url, mode: LaunchMode.externalApplication);
  }

  Future<void> _afficherDetail(Map<String, dynamic> rdv) async {
    final adresse = rdv['clientAdresse'] as String?;

    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: CouleursFlamattitude.fond,
      isScrollControlled: true,
      builder: (contexteFeuille) => Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              rdv['typeNom'] as String,
              style: const TextStyle(color: CouleursFlamattitude.texte, fontSize: 20, fontWeight: FontWeight.bold),
            ),
            Text(
              '${rdv['date']} · ${(rdv['heure_debut'] as String).substring(0, 5)}-${(rdv['heure_fin'] as String).substring(0, 5)}',
              style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
            ),
            const SizedBox(height: 16),
            _ligneInfo(Icons.person_outline, '${rdv['clientPrenom']} ${rdv['clientNom']}'),
            if (rdv['clientTelephone'] != null) _ligneInfo(Icons.phone_outlined, rdv['clientTelephone'] as String),
            if (adresse != null) _ligneInfo(Icons.location_on_outlined, adresse),
            if (rdv['motif'] != null) ...[
              const SizedBox(height: 8),
              const Text('Motif', style: TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold)),
              Text(rdv['motif'] as String, style: const TextStyle(color: CouleursFlamattitude.texte)),
            ],
            if (adresse != null) ...[
              const SizedBox(height: 20),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _ouvrirGoogleMaps(adresse),
                      icon: const Icon(Icons.map_outlined),
                      label: const Text('Google Maps'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _ouvrirWaze(adresse),
                      icon: const Icon(Icons.navigation_outlined),
                      label: const Text('Waze'),
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: TextButton(
                onPressed: () {
                  Navigator.pop(contexteFeuille);
                  _annuler(rdv);
                },
                child: const Text('Annuler ce rendez-vous', style: TextStyle(color: CouleursFlamattitude.accent)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _ligneInfo(IconData icone, String texte) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        children: [
          Icon(icone, color: CouleursFlamattitude.texte.withValues(alpha: 0.7), size: 18),
          const SizedBox(width: 8),
          Expanded(child: Text(texte, style: const TextStyle(color: CouleursFlamattitude.texte))),
        ],
      ),
    );
  }

  Future<void> _annuler(Map<String, dynamic> rdv) async {
    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => AlertDialog(
        title: const Text('Annuler ce rendez-vous ?'),
        content: Text('${rdv['typeNom']} avec ${rdv['clientPrenom']} ${rdv['clientNom']} le ${rdv['date']}.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Retour')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: CouleursFlamattitude.accent),
            onPressed: () => Navigator.pop(contexteDialogue, true),
            child: const Text('Annuler le RDV'),
          ),
        ],
      ),
    );

    if (confirme != true || !mounted) return;

    try {
      await _disponibiliteService.annulerRdv(rdv['idRdv'] as int);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rendez-vous annulé.')));
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mon EDT')),
      body: SafeArea(
        child: RefreshIndicator(onRefresh: _charger, child: _corps()),
      ),
    );
  }

  Widget _corps() {
    if (_enCours) return const Center(child: CircularProgressIndicator());

    if (_erreur != null) {
      return ListView(
        children: [
          const SizedBox(height: 100),
          Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent))),
        ],
      );
    }

    if (_mesRdv.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 100),
          Center(child: Text('Aucun rendez-vous à venir.', style: TextStyle(color: CouleursFlamattitude.texte))),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _mesRdv.length,
      itemBuilder: (context, index) {
        final rdv = _mesRdv[index] as Map<String, dynamic>;
        final couleur = Color(int.parse((rdv['typeCouleur'] as String).substring(1), radix: 16) + 0xFF000000);

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          decoration: BoxDecoration(color: CouleursFlamattitude.champ, borderRadius: BorderRadius.circular(10)),
          child: ListTile(
            onTap: () => _afficherDetail(rdv),
            leading: Container(width: 12, height: 12, decoration: BoxDecoration(color: couleur, shape: BoxShape.circle)),
            title: Text(
              '${rdv['typeNom']} — ${(rdv['heure_debut'] as String).substring(0, 5)}-${(rdv['heure_fin'] as String).substring(0, 5)}',
              style: const TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold),
            ),
            subtitle: Text(
              '${rdv['date']} · ${rdv['clientPrenom']} ${rdv['clientNom']}',
              style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
            ),
            trailing: const Icon(Icons.chevron_right, color: CouleursFlamattitude.texte),
          ),
        );
      },
    );
  }
}
