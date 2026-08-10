import 'dart:io';

import 'package:flutter/material.dart';

import '../services/accueil_service.dart';
import '../services/api_client.dart';
import '../theme.dart';
import '../widgets/entete.dart';
import 'ajouter_utilisateur_screen.dart';
import 'disponibilites_screen.dart';
import 'mon_edt_screen.dart';
import 'mon_espace_screen.dart';
import 'planning_admin_screen.dart';
import 'prendre_rdv_screen.dart';
import 'recherche_client_screen.dart';
import 'supprimer_utilisateur_screen.dart';
import 'types_rdv_screen.dart';

// Doit rester synchronisé avec App\Support\Roles côté Laravel.
class _Roles {
  static const admin = 1;
  static const membreEntreprise = 2;
  static const client = 3;
}

class AccueilScreen extends StatefulWidget {
  final Map<String, dynamic> utilisateur;

  const AccueilScreen({super.key, required this.utilisateur});

  @override
  State<AccueilScreen> createState() => _AccueilScreenState();
}

class _AccueilScreenState extends State<AccueilScreen> {
  final _accueilService = AccueilService();

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
      final donnees = await _accueilService.stats();
      if (mounted) setState(() => _donnees = donnees);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  void _bientotDisponible() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Bientôt disponible.')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: EnteteFlamattitude(
        utilisateur: widget.utilisateur,
        idStatut: (widget.utilisateur['id_statut'] as int?) ?? 0,
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _charger,
          child: _corps(),
        ),
      ),
    );
  }

  Widget _corps() {
    if (_enCours && _donnees == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_erreur != null && _donnees == null) {
      return ListView(
        children: [
          const SizedBox(height: 120),
          Center(
            child: Text(_erreur!, style: const TextStyle(color: CouleursFlamattitude.accent)),
          ),
          const SizedBox(height: 16),
          Center(child: TextButton(onPressed: _charger, child: const Text('Réessayer'))),
        ],
      );
    }

    final donnees = _donnees!;
    final idStatut = donnees['id_statut'] as int;
    final stats = donnees['stats'] as Map<String, dynamic>;

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          '${donnees['salutation']}, ${widget.utilisateur['prenom']} !',
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: CouleursFlamattitude.texte,
          ),
        ),
        const SizedBox(height: 24),
        ..._cartesPourRole(idStatut, stats),
        ..._sectionActionsRapides(idStatut),
      ],
    );
  }

  List<Widget> _cartesPourRole(int idStatut, Map<String, dynamic> stats) {
    final cartes = switch (idStatut) {
      _Roles.admin => [
          _Carte('Clients', stats['clients'], Icons.people),
          _Carte("Membres de l'entreprise", stats['membres'], Icons.badge),
          _Carte('Factures en attente', stats['facturesEnAttente'], Icons.receipt_long),
          _Carte('RDV à venir', stats['rdvAVenir'], Icons.event),
        ],
      _Roles.membreEntreprise => [
          _Carte('Clients', stats['clients'], Icons.people),
          _Carte('Mes disponibilités à venir', stats['mesDisponibilites'], Icons.event_available),
          _Carte('Mes RDV à venir', stats['mesRdvAVenir'], Icons.event),
        ],
      _Roles.client => [
          _Carte('Mes prochains RDV', stats['prochainsRdv'], Icons.event),
          _Carte('Factures en attente', stats['facturesEnAttente'], Icons.receipt_long),
          _Carte('Mes documents', stats['documents'], Icons.folder),
        ],
      _ => <_Carte>[],
    };

    return cartes.map((c) => _construireCarte(c)).toList();
  }

  List<Widget> _sectionActionsRapides(int idStatut) {
    final actions = _actionsPourRole(idStatut);
    if (actions.isEmpty) return [];

    return [
      const SizedBox(height: 24),
      const Text(
        'Actions rapides',
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: CouleursFlamattitude.texte,
        ),
      ),
      const SizedBox(height: 12),
      ...actions,
    ];
  }

  List<Widget> _actionsPourRole(int idStatut) {
    final actions = switch (idStatut) {
      // "Rechercher un client", "Mon EDT" et "Mes disponibilités" sont désormais
      // dans la barre d'onglets du bas : pas besoin de les dupliquer ici.
      _Roles.admin => const [
          'Planning global',
          'Types de RDV',
          'Ajouter un utilisateur',
          'Supprimer un utilisateur',
        ],
      _Roles.client => const [],
      _ => const <String>[],
    };

    return actions
        .map(
          (libelle) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => _surAction(libelle),
                child: Text(libelle),
              ),
            ),
          ),
        )
        .toList();
  }

  void _surAction(String libelle) {
    switch (libelle) {
      case 'Rechercher un client':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const RechercheClientScreen()));
      case 'Ajouter un utilisateur':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const AjouterUtilisateurScreen()));
      case 'Supprimer un utilisateur':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const SupprimerUtilisateurScreen()));
      case 'Types de RDV':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const TypesRdvScreen()));
      case 'Mon EDT':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const MonEdtScreen()));
      case 'Planning global':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const PlanningAdminScreen()));
      case 'Mes disponibilités':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const DisponibilitesScreen()));
      case 'Mes documents et factures':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const MonEspaceScreen()));
      case 'Prendre ou gérer un RDV':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const PrendreRdvScreen()));
      default:
        _bientotDisponible();
    }
  }

  Widget _construireCarte(_Carte carte) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: CouleursFlamattitude.champ,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(carte.icone, color: CouleursFlamattitude.accent, size: 28),
          const SizedBox(width: 16),
          Expanded(
            child: Text(carte.titre, style: const TextStyle(color: CouleursFlamattitude.texte)),
          ),
          Text(
            '${carte.valeur}',
            style: const TextStyle(
              color: CouleursFlamattitude.texte,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}

class _Carte {
  final String titre;
  final dynamic valeur;
  final IconData icone;

  _Carte(this.titre, this.valeur, this.icone);
}
