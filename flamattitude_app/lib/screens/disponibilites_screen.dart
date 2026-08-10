import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/disponibilite_service.dart';
import '../theme.dart';

const _nomsJours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

class DisponibilitesScreen extends StatefulWidget {
  const DisponibilitesScreen({super.key});

  @override
  State<DisponibilitesScreen> createState() => _DisponibilitesScreenState();
}

class _DisponibilitesScreenState extends State<DisponibilitesScreen> {
  final _disponibiliteService = DisponibiliteService();

  bool _enCours = true;
  String? _erreur;
  Map<String, dynamic>? _donnees;
  int _jourSelectionne = 0;
  String? _semaineDemandee;

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
      final donnees = await _disponibiliteService.grille(semaine: _semaineDemandee);
      if (mounted) setState(() => _donnees = donnees);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  void _changerSemaine(int decalageJours) {
    final lundiActuel = DateTime.parse(_donnees!['lundi'] as String);
    final nouveauLundi = lundiActuel.add(Duration(days: decalageJours));
    setState(() {
      _semaineDemandee = nouveauLundi.toIso8601String().split('T').first;
      _jourSelectionne = 0;
    });
    _charger();
  }

  Future<void> _toggle(String date, String heure) async {
    try {
      final donnees = await _disponibiliteService.toggle(date, heure, semaine: _donnees!['lundi'] as String);
      if (mounted) setState(() => _donnees = donnees);
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Mes disponibilités')),
      body: SafeArea(child: _corps()),
    );
  }

  Widget _corps() {
    if (_enCours && _donnees == null) return const Center(child: CircularProgressIndicator());

    if (_erreur != null && _donnees == null) {
      return Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)));
    }

    final donnees = _donnees!;
    final jours = donnees['jours'] as List<dynamic>;
    final lundi = donnees['lundi'] as String;
    final lundiMin = donnees['lundi_min'] as String;
    final semainePassee = lundi == lundiMin;
    final jour = jours[_jourSelectionne] as Map<String, dynamic>;
    final creneaux = jour['creneaux'] as Map<String, dynamic>;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          child: Row(
            children: [
              IconButton(
                onPressed: semainePassee ? null : () => _changerSemaine(-7),
                icon: const Icon(Icons.chevron_left),
                color: CouleursFlamattitude.texte,
              ),
              Expanded(
                child: Text(
                  donnees['label_semaine'] as String,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: CouleursFlamattitude.texte),
                ),
              ),
              IconButton(
                onPressed: () => _changerSemaine(7),
                icon: const Icon(Icons.chevron_right),
                color: CouleursFlamattitude.texte,
              ),
            ],
          ),
        ),
        SizedBox(
          height: 64,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: jours.length,
            itemBuilder: (context, index) {
              final j = jours[index] as Map<String, dynamic>;
              final date = DateTime.parse(j['date'] as String);
              final selectionne = index == _jourSelectionne;

              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: ChoiceChip(
                  label: Text('${_nomsJours[index]} ${date.day.toString().padLeft(2, '0')}'),
                  selected: selectionne,
                  onSelected: (_) => setState(() => _jourSelectionne = index),
                ),
              );
            },
          ),
        ),
        const Divider(color: CouleursFlamattitude.champ),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: creneaux.entries.map((entree) {
              final heure = entree.key;
              final info = entree.value as Map<String, dynamic>;
              return _ligneCreneau(jour['date'] as String, heure, info);
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _ligneCreneau(String date, String heure, Map<String, dynamic> info) {
    final etat = info['etat'] as String;

    Color couleurFond;
    String texteEtat;
    Widget? sousTitre;

    switch (etat) {
      case 'reserve':
        final rdv = info['rdv'] as Map<String, dynamic>;
        couleurFond = CouleursFlamattitude.accent.withValues(alpha: 0.25);
        texteEtat = rdv['typeNom'] as String;
        sousTitre = Text(
          'Avec ${rdv['clientNom']} ${rdv['clientPrenom']}',
          style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7)),
        );
      case 'disponible':
        couleurFond = Colors.greenAccent.withValues(alpha: 0.15);
        texteEtat = 'Disponible';
      default:
        couleurFond = CouleursFlamattitude.champ;
        texteEtat = 'Indisponible';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(color: couleurFond, borderRadius: BorderRadius.circular(10)),
      child: ListTile(
        leading: Text(heure, style: const TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold)),
        title: Text(texteEtat, style: const TextStyle(color: CouleursFlamattitude.texte)),
        subtitle: sousTitre,
        onTap: etat == 'reserve' ? null : () => _toggle(date, heure),
      ),
    );
  }
}
