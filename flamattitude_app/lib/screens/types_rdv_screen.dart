import 'dart:io';

import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../services/type_rdv_service.dart';
import '../theme.dart';

class TypesRdvScreen extends StatefulWidget {
  const TypesRdvScreen({super.key});

  @override
  State<TypesRdvScreen> createState() => _TypesRdvScreenState();
}

class _TypesRdvScreenState extends State<TypesRdvScreen> {
  final _typeRdvService = TypeRdvService();

  bool _enCours = true;
  String? _erreur;
  List<dynamic> _types = [];
  List<dynamic> _membres = [];
  Map<int, Set<int>> _affectations = {};

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
      final donnees = await _typeRdvService.index();
      final affectationsBrutes = donnees['affectations'] as Map<String, dynamic>;

      setState(() {
        _types = donnees['types'] as List<dynamic>;
        _membres = donnees['membres'] as List<dynamic>;
        _affectations = affectationsBrutes.map(
          (idMembre, idsTypes) => MapEntry(int.parse(idMembre), (idsTypes as List<dynamic>).cast<int>().toSet()),
        );
      });
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  Future<void> _basculerActif(Map<String, dynamic> type) async {
    try {
      await _typeRdvService.basculerActif(type['idTypeRdv'] as int);
      _charger();
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  Future<void> _ouvrirFormulaire({Map<String, dynamic>? type}) async {
    final controleurNom = TextEditingController(text: type?['nom'] as String? ?? '');
    final controleurDuree = TextEditingController(text: type != null ? '${type['duree_minutes']}' : '');
    String couleurChoisie = (type?['couleur'] as String?) ?? _palette.first;
    String? erreurLocale;

    final confirme = await showDialog<bool>(
      context: context,
      builder: (contexteDialogue) => StatefulBuilder(
        builder: (contexteDialogue, setStateDialogue) => AlertDialog(
          title: Text(type == null ? 'Ajouter un type de RDV' : 'Modifier le type'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: controleurNom, decoration: const InputDecoration(labelText: 'Nom')),
                const SizedBox(height: 12),
                TextField(
                  controller: controleurDuree,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Durée (minutes)'),
                ),
                const SizedBox(height: 16),
                const Align(
                  alignment: Alignment.centerLeft,
                  child: Text('Couleur', style: TextStyle(color: CouleursFlamattitude.texte)),
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: _palette.map((hex) {
                    final selectionnee = hex == couleurChoisie;
                    final couleur = Color(int.parse(hex.substring(1), radix: 16) + 0xFF000000);

                    return GestureDetector(
                      onTap: () => setStateDialogue(() => couleurChoisie = hex),
                      child: Container(
                        width: 36,
                        height: 36,
                        decoration: BoxDecoration(
                          color: couleur,
                          shape: BoxShape.circle,
                          border: selectionnee ? Border.all(color: Colors.white, width: 3) : null,
                        ),
                        child: selectionnee ? const Icon(Icons.check, color: Colors.white, size: 18) : null,
                      ),
                    );
                  }).toList(),
                ),
                if (erreurLocale != null) ...[
                  const SizedBox(height: 12),
                  Text(erreurLocale!, style: const TextStyle(color: CouleursFlamattitude.accent)),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(contexteDialogue, false), child: const Text('Annuler')),
            ElevatedButton(
              onPressed: () async {
                final duree = int.tryParse(controleurDuree.text.trim());
                if (controleurNom.text.trim().isEmpty || duree == null) {
                  setStateDialogue(() => erreurLocale = 'Nom et durée valides requis.');
                  return;
                }

                try {
                  if (type == null) {
                    await _typeRdvService.creer(
                      nom: controleurNom.text.trim(),
                      dureeMinutes: duree,
                      couleur: couleurChoisie,
                    );
                  } else {
                    await _typeRdvService.modifier(
                      type['idTypeRdv'] as int,
                      nom: controleurNom.text.trim(),
                      dureeMinutes: duree,
                      couleur: couleurChoisie,
                    );
                  }
                  if (contexteDialogue.mounted) Navigator.pop(contexteDialogue, true);
                } on ApiException catch (e) {
                  setStateDialogue(() => erreurLocale = e.message);
                }
              },
              child: const Text('Enregistrer'),
            ),
          ],
        ),
      ),
    );

    if (confirme == true) _charger();
  }

  static const _palette = [
    '#D9531E',
    '#5988DA',
    '#e67e22',
    '#e74c3c',
    '#2ecc71',
    '#9b59b6',
    '#1abc9c',
    '#f1c40f',
    '#34495e',
    '#7f8c8d',
  ];

  Future<void> _enregistrerAffectations() async {
    try {
      await _typeRdvService.enregistrerAffectations(
        _affectations.map((idMembre, idsTypes) => MapEntry(idMembre, idsTypes.toList())),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Affectations mises à jour.')));
    } on ApiException catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Types de RDV'),
        actions: [
          IconButton(onPressed: () => _ouvrirFormulaire(), icon: const Icon(Icons.add)),
        ],
      ),
      body: SafeArea(child: _corps()),
    );
  }

  Widget _corps() {
    if (_enCours) return const Center(child: CircularProgressIndicator());

    if (_erreur != null) {
      return Center(child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)));
    }

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        ..._types.map((t) => _ligneType(t as Map<String, dynamic>)),
        const SizedBox(height: 28),
        const Text(
          'Quels employés font quels RDV ?',
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: CouleursFlamattitude.texte),
        ),
        const SizedBox(height: 12),
        ..._membres.map((m) => _ligneMembre(m as Map<String, dynamic>)),
        const SizedBox(height: 16),
        ElevatedButton(onPressed: _enregistrerAffectations, child: const Text('Enregistrer les affectations')),
      ],
    );
  }

  Widget _ligneType(Map<String, dynamic> type) {
    final actif = type['actif'] == true || type['actif'] == 1;
    final couleur = Color(int.parse((type['couleur'] as String).substring(1), radix: 16) + 0xFF000000);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: CouleursFlamattitude.champ, borderRadius: BorderRadius.circular(10)),
      child: Row(
        children: [
          Container(width: 16, height: 16, decoration: BoxDecoration(color: couleur, shape: BoxShape.circle)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(type['nom'] as String, style: const TextStyle(color: CouleursFlamattitude.texte, fontWeight: FontWeight.bold)),
                Text(
                  '${type['duree_minutes']} min · ${actif ? 'Actif' : 'Désactivé'}',
                  style: TextStyle(color: CouleursFlamattitude.texte.withValues(alpha: 0.6), fontSize: 12),
                ),
              ],
            ),
          ),
          IconButton(onPressed: () => _ouvrirFormulaire(type: type), icon: const Icon(Icons.edit_outlined, color: CouleursFlamattitude.texte)),
          Switch(value: actif, onChanged: (_) => _basculerActif(type), activeThumbColor: CouleursFlamattitude.accent),
        ],
      ),
    );
  }

  Widget _ligneMembre(Map<String, dynamic> membre) {
    final idMembre = membre['idUtilisateur'] as int;
    final typesActuels = _affectations[idMembre] ?? <int>{};

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${membre['prenom']} ${membre['nom']}', style: const TextStyle(color: CouleursFlamattitude.texte)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            children: _types.map((t) {
              final type = t as Map<String, dynamic>;
              final idType = type['idTypeRdv'] as int;
              final selectionne = typesActuels.contains(idType);

              return FilterChip(
                label: Text(type['nom'] as String),
                selected: selectionne,
                onSelected: (valeur) {
                  setState(() {
                    final ensemble = _affectations.putIfAbsent(idMembre, () => <int>{});
                    if (valeur) {
                      ensemble.add(idType);
                    } else {
                      ensemble.remove(idType);
                    }
                  });
                },
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}
