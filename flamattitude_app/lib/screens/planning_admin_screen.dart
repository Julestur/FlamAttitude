import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/planning_admin_service.dart';
import '../theme.dart';

const _nomsJours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

class PlanningAdminScreen extends StatefulWidget {
  const PlanningAdminScreen({super.key});

  @override
  State<PlanningAdminScreen> createState() => _PlanningAdminScreenState();
}

class _PlanningAdminScreenState extends State<PlanningAdminScreen> {
  final _planningAdminService = PlanningAdminService();

  bool _enCoursMembres = true;
  bool _enCoursGrille = false;
  String? _erreur;
  List<dynamic> _membres = [];
  int? _idMembreSelectionne;
  Map<String, dynamic>? _grille;
  int _jourSelectionne = 0;
  String? _semaineDemandee;

  Map<String, dynamic>? _rdvSelectionne;

  @override
  void initState() {
    super.initState();
    _chargerMembres();
  }

  Future<void> _chargerMembres() async {
    setState(() {
      _enCoursMembres = true;
      _erreur = null;
    });

    try {
      final membres = await _planningAdminService.membres();
      if (!mounted) return;
      setState(() {
        _membres = membres;
        if (membres.isNotEmpty) _idMembreSelectionne = (membres.first as Map<String, dynamic>)['idUtilisateur'] as int;
      });
      if (_idMembreSelectionne != null) await _chargerGrille();
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCoursMembres = false);
    }
  }

  Future<void> _chargerGrille() async {
    if (_idMembreSelectionne == null) return;

    setState(() {
      _enCoursGrille = true;
      _erreur = null;
    });

    try {
      final grille = await _planningAdminService.grilleMembre(_idMembreSelectionne!, semaine: _semaineDemandee);
      if (mounted) setState(() => _grille = grille);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCoursGrille = false);
    }
  }

  void _changerMembre(int idMembre) {
    setState(() {
      _idMembreSelectionne = idMembre;
      _jourSelectionne = 0;
    });
    _chargerGrille();
  }

  void _changerSemaine(int decalageJours) {
    final lundiActuel = DateTime.parse(_grille!['lundi'] as String);
    final nouveauLundi = lundiActuel.add(Duration(days: decalageJours));
    setState(() {
      _semaineDemandee = nouveauLundi.toIso8601String().split('T').first;
      _jourSelectionne = 0;
    });
    _chargerGrille();
  }

  void _surTapCreneau(String date, String heure, Map<String, dynamic> info) {
    final etat = info['etat'] as String;

    if (_rdvSelectionne == null) {
      if (etat == 'reserve') {
        setState(() => _rdvSelectionne = info['rdv'] as Map<String, dynamic>);
      }
      return;
    }

    final rdvSelectionneId = _rdvSelectionne!['idRdv'] as int;

    if (etat == 'reserve' && (info['rdv'] as Map<String, dynamic>)['idRdv'] == rdvSelectionneId) {
      setState(() => _rdvSelectionne = null);
      return;
    }

    if (etat == 'reserve') {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cette case est déjà occupée par un autre RDV.')));
      return;
    }

    _deplacerVersIci(date, heure);
  }

  Future<void> _deplacerVersIci(String date, String heure) async {
    final rdv = _rdvSelectionne!;

    try {
      await _planningAdminService.deplacerRdv(
        rdv['idRdv'] as int,
        idMembre: _idMembreSelectionne!,
        date: date,
        heure: heure,
      );
      if (!mounted) return;
      setState(() => _rdvSelectionne = null);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rendez-vous déplacé.')));
      _chargerGrille();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _annulerSelection() async {
    final rdv = _rdvSelectionne!;

    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => AlertDialog(
        title: const Text('Annuler ce rendez-vous ?'),
        content: Text('${rdv['typeNom']} avec ${rdv['clientNom']} ${rdv['clientPrenom']}.'),
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

    if (confirme != true) return;

    try {
      await _planningAdminService.annulerRdv(rdv['idRdv'] as int);
      if (!mounted) return;
      setState(() => _rdvSelectionne = null);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rendez-vous annulé.')));
      _chargerGrille();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Planning global')),
      body: SafeArea(child: _corps()),
    );
  }

  Widget _corps() {
    if (_enCoursMembres) return const Center(child: CircularProgressIndicator());

    if (_erreur != null && _grille == null) {
      return Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)));
    }

    if (_membres.isEmpty) {
      return const Center(child: Text('Aucun employé.', style: TextStyle(color: CouleursFlamattitude.texte)));
    }

    return Column(
      children: [
        SizedBox(
          height: 48,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            itemCount: _membres.length,
            itemBuilder: (context, index) {
              final membre = _membres[index] as Map<String, dynamic>;
              final idMembre = membre['idUtilisateur'] as int;

              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: ChoiceChip(
                  label: Text('${membre['prenom']} ${membre['nom']}'),
                  selected: idMembre == _idMembreSelectionne,
                  onSelected: (_) => _changerMembre(idMembre),
                ),
              );
            },
          ),
        ),
        if (_rdvSelectionne != null) _bandeauSelection(),
        Expanded(
          child: _enCoursGrille || _grille == null
              ? const Center(child: CircularProgressIndicator())
              : _grilleWidget(),
        ),
      ],
    );
  }

  Widget _bandeauSelection() {
    final rdv = _rdvSelectionne!;
    return Container(
      width: double.infinity,
      color: CouleursFlamattitude.accent.withValues(alpha: 0.2),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              'Sélectionné : ${rdv['typeNom']} avec ${rdv['clientNom']} ${rdv['clientPrenom']}. Touchez une case libre pour le déplacer.',
              style: const TextStyle(color: CouleursFlamattitude.texte, fontSize: 13),
            ),
          ),
          TextButton(onPressed: _annulerSelection, child: const Text('Annuler le RDV')),
          IconButton(
            onPressed: () => setState(() => _rdvSelectionne = null),
            icon: const Icon(Icons.close, color: CouleursFlamattitude.texte),
          ),
        ],
      ),
    );
  }

  Widget _grilleWidget() {
    final grille = _grille!;
    final jours = grille['jours'] as List<dynamic>;
    final lundi = grille['lundi'] as String;
    final lundiMin = grille['lundi_min'] as String;
    final semainePassee = lundi == lundiMin;
    final jour = jours[_jourSelectionne] as Map<String, dynamic>;
    final creneaux = jour['creneaux'] as Map<String, dynamic>;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
          child: Row(
            children: [
              IconButton(
                onPressed: semainePassee ? null : () => _changerSemaine(-7),
                icon: const Icon(Icons.chevron_left),
                color: CouleursFlamattitude.texte,
              ),
              Expanded(
                child: Text(
                  grille['label_semaine'] as String,
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
          height: 56,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            itemCount: jours.length,
            itemBuilder: (context, index) {
              final j = jours[index] as Map<String, dynamic>;
              final date = DateTime.parse(j['date'] as String);

              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: ChoiceChip(
                  label: Text('${_nomsJours[index]} ${date.day.toString().padLeft(2, '0')}'),
                  selected: index == _jourSelectionne,
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
              return _ligneCreneau(jour['date'] as String, entree.key, entree.value as Map<String, dynamic>);
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _ligneCreneau(String date, String heure, Map<String, dynamic> info) {
    final etat = info['etat'] as String;
    final estSelectionnee = etat == 'reserve' &&
        _rdvSelectionne != null &&
        (info['rdv'] as Map<String, dynamic>)['idRdv'] == _rdvSelectionne!['idRdv'];

    Color couleurFond;
    String texteEtat;
    Widget? sousTitre;

    switch (etat) {
      case 'reserve':
        final rdv = info['rdv'] as Map<String, dynamic>;
        couleurFond = estSelectionnee
            ? CouleursFlamattitude.accent.withValues(alpha: 0.6)
            : CouleursFlamattitude.accent.withValues(alpha: 0.25);
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
      decoration: BoxDecoration(
        color: couleurFond,
        borderRadius: BorderRadius.circular(10),
        border: estSelectionnee ? Border.all(color: Colors.white, width: 2) : null,
      ),
      child: ListTile(
        leading: Text(heure, style: const TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold)),
        title: Text(texteEtat, style: const TextStyle(color: CouleursFlamattitude.texte)),
        subtitle: sousTitre,
        onTap: () => _surTapCreneau(date, heure, info),
      ),
    );
  }
}
