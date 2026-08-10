import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/reservation_service.dart';
import '../theme.dart';

const _nomsJours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

class PrendreRdvScreen extends StatefulWidget {
  const PrendreRdvScreen({super.key});

  @override
  State<PrendreRdvScreen> createState() => _PrendreRdvScreenState();
}

class _PrendreRdvScreenState extends State<PrendreRdvScreen> {
  final _reservationService = ReservationService();

  bool _enCours = true;
  String? _erreur;
  Map<String, dynamic>? _donnees;
  int _jourSelectionne = 0;
  int? _idTypeSelectionne;
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
      final donnees = await _reservationService.creneaux(
        idTypeRdv: _idTypeSelectionne,
        semaine: _semaineDemandee,
      );
      if (mounted) {
        setState(() {
          _donnees = donnees;
          _idTypeSelectionne = donnees['id_type_rdv_selectionne'] as int?;
        });
      }
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _rechargerGrille() async {
    if (_idTypeSelectionne == null) return;

    try {
      final grille = await _reservationService.grille(
        idTypeRdv: _idTypeSelectionne!,
        semaine: _semaineDemandee ?? _donnees!['lundi'] as String,
      );
      if (mounted) {
        setState(() {
          _donnees!['jours'] = grille['jours'];
          _donnees!['label_semaine'] = grille['label_semaine'];
          _donnees!['lundi'] = grille['lundi'];
        });
      }
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  void _changerType(int idType) {
    setState(() => _idTypeSelectionne = idType);
    _rechargerGrille();
  }

  void _changerSemaine(int decalageJours) {
    final lundiActuel = DateTime.parse(_donnees!['lundi'] as String);
    final nouveauLundi = lundiActuel.add(Duration(days: decalageJours));
    setState(() {
      _semaineDemandee = nouveauLundi.toIso8601String().split('T').first;
      _jourSelectionne = 0;
    });
    _rechargerGrille();
  }

  /// Cache les créneaux déjà passés (uniquement pertinent pour aujourd'hui,
  /// les autres jours de la semaine sont forcément dans le futur).
  Iterable<MapEntry<String, dynamic>> _creneauxAffiches(String date, Map<String, dynamic> creneaux) {
    final maintenant = DateTime.now();
    final jourDate = DateTime.parse(date);
    final estAujourdhui = jourDate.year == maintenant.year && jourDate.month == maintenant.month && jourDate.day == maintenant.day;

    if (!estAujourdhui) return creneaux.entries;

    return creneaux.entries.where((entree) {
      final parties = entree.key.split(':');
      final heureCreneau = DateTime(maintenant.year, maintenant.month, maintenant.day, int.parse(parties[0]), int.parse(parties[1]));
      return heureCreneau.isAfter(maintenant);
    });
  }

  Future<void> _reserver(String date, String heure) async {
    final controleurMotif = TextEditingController();

    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => AlertDialog(
        title: Text('Réserver le $date à $heure'),
        content: TextField(
          controller: controleurMotif,
          maxLines: 3,
          decoration: const InputDecoration(labelText: 'Motif de la visite'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Annuler')),
          ElevatedButton(onPressed: () => Navigator.pop(contexteDialogue, true), child: const Text('Confirmer')),
        ],
      ),
    );

    if (confirme != true || !mounted) return;

    if (controleurMotif.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Le motif est obligatoire.')));
      return;
    }

    try {
      await _reservationService.reserver(
        idTypeRdv: _idTypeSelectionne!,
        date: date,
        heure: heure,
        motif: controleurMotif.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Rendez-vous réservé avec succès. Un email de confirmation vous a été envoyé.')),
      );
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _annulerRdv(Map<String, dynamic> rdv) async {
    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => AlertDialog(
        title: const Text('Annuler ce rendez-vous ?'),
        content: Text('${rdv['typeNom']} le ${_formaterDate(rdv['date'] as String)} avec ${rdv['membrePrenom']} ${rdv['membreNom']}.'),
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
      await _reservationService.annuler(rdv['idRdv'] as int);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Rendez-vous annulé.')));
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Rendez-vous'),
          bottom: const TabBar(tabs: [Tab(text: 'Réserver'), Tab(text: 'Mes RDV')]),
        ),
        body: SafeArea(
          child: _enCours && _donnees == null
              ? const Center(child: CircularProgressIndicator())
              : _erreur != null && _donnees == null
                  ? Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)))
                  : TabBarView(children: [_ongletReserver(), _ongletMesRdv()]),
        ),
      ),
    );
  }

  Widget _ongletReserver() {
    final donnees = _donnees!;
    final types = donnees['types'] as List<dynamic>;
    final jours = donnees['jours'] as List<dynamic>;
    final lundi = donnees['lundi'] as String;
    final lundiMin = donnees['lundi_min'] as String;
    final semainePassee = lundi == lundiMin;

    if (types.isEmpty) {
      return const Center(child: Text('Aucun type de RDV disponible.', style: TextStyle(color: CouleursFlamattitude.texte)));
    }

    final jour = jours[_jourSelectionne] as Map<String, dynamic>;
    final creneaux = jour['creneaux'] as Map<String, dynamic>;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: DropdownButtonFormField<int>(
            initialValue: _idTypeSelectionne,
            items: types
                .map((t) => t as Map<String, dynamic>)
                .map((t) => DropdownMenuItem(value: t['idTypeRdv'] as int, child: Text('${t['nom']} (${t['duree_minutes']} min)')))
                .toList(),
            onChanged: (v) => _changerType(v!),
            decoration: const InputDecoration(labelText: 'Type de rendez-vous'),
          ),
        ),
        Row(
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
            children: _creneauxAffiches(jour['date'] as String, creneaux).map((entree) {
              final heure = entree.key;
              final libre = entree.value == true;

              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                decoration: BoxDecoration(
                  color: libre ? Colors.greenAccent.withValues(alpha: 0.15) : CouleursFlamattitude.champ.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: ListTile(
                  leading: Text(
                    heure,
                    style: TextStyle(
                      color: libre ? CouleursFlamattitude.texte : CouleursFlamattitude.texte.withValues(alpha: 0.35),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  title: libre
                      ? const Text('Disponible', style: TextStyle(color: CouleursFlamattitude.texte))
                      : null,
                  onTap: libre ? () => _reserver(jour['date'] as String, heure) : null,
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }

  Widget _ongletMesRdv() {
    final mesRdv = _donnees!['mes_rdv'] as List<dynamic>;

    if (mesRdv.isEmpty) {
      return const Center(child: Text('Aucun rendez-vous.', style: TextStyle(color: CouleursFlamattitude.texte)));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: mesRdv.length,
      itemBuilder: (context, index) {
        final rdv = mesRdv[index] as Map<String, dynamic>;
        final couleur = Color(int.parse((rdv['typeCouleur'] as String).substring(1), radix: 16) + 0xFF000000);
        final estPasse = DateTime.parse('${rdv['date']} ${rdv['heure_debut']}').isBefore(DateTime.now());

        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(color: CouleursFlamattitude.champ, borderRadius: BorderRadius.circular(10)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(width: 12, height: 12, decoration: BoxDecoration(color: couleur, shape: BoxShape.circle)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${rdv['typeNom']} — ${_formaterDate(rdv['date'] as String)} ${(rdv['heure_debut'] as String).substring(0, 5)}',
                      style: const TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text('Avec ${rdv['membrePrenom']} ${rdv['membreNom']}', style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7))),
              if (rdv['motif'] != null)
                Text('Motif : ${rdv['motif']}', style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.7))),
              if (!estPasse)
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(onPressed: () => _annulerRdv(rdv), child: const Text('Annuler')),
                ),
            ],
          ),
        );
      },
    );
  }
}

const _moisFr = [
  'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
  'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
];

String _formaterDate(String isoDate) {
  final date = DateTime.parse(isoDate);
  return '${date.day} ${_moisFr[date.month - 1]} ${date.year}';
}
