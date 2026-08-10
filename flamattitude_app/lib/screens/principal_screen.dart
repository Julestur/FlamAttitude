import 'package:flutter/material.dart';

import '../theme.dart';
import 'accueil_screen.dart';
import 'disponibilites_screen.dart';
import 'mon_edt_screen.dart';
import 'mon_espace_screen.dart';
import 'prendre_rdv_screen.dart';
import 'recherche_client_screen.dart';

// Doit rester synchronisé avec App\Support\Roles côté Laravel.
class _Roles {
  static const client = 3;
}

/// Conteneur avec barre d'onglets en bas, reprenant le découpage par rôle déjà
/// utilisé sur le site en contexte "app mobile" (voir partials/bottom-nav.blade.php).
/// Chaque onglet est un écran complet (avec son propre AppBar) : seule la barre
/// du bas est mutualisée ici.
class PrincipalScreen extends StatefulWidget {
  final Map<String, dynamic> utilisateur;

  const PrincipalScreen({super.key, required this.utilisateur});

  @override
  State<PrincipalScreen> createState() => _PrincipalScreenState();
}

class _PrincipalScreenState extends State<PrincipalScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final idStatut = (widget.utilisateur['id_statut'] as int?) ?? 0;
    final onglets = _ongletsPourRole(idStatut);

    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: onglets.map((o) => o.ecran).toList(),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        backgroundColor: CouleursFlamattitude.champ,
        indicatorColor: CouleursFlamattitude.accent.withValues(alpha: 0.3),
        height: 60,
        labelTextStyle: WidgetStateProperty.all(const TextStyle(fontSize: 11)),
        destinations: onglets
            .map((o) => NavigationDestination(icon: Icon(o.icone), label: o.label))
            .toList(),
      ),
    );
  }

  List<_Onglet> _ongletsPourRole(int idStatut) {
    if (idStatut == _Roles.client) {
      return [
        _Onglet('Accueil', Icons.home_outlined, AccueilScreen(utilisateur: widget.utilisateur)),
        _Onglet('RDV', Icons.event_outlined, const PrendreRdvScreen()),
        _Onglet('Documents', Icons.folder_outlined, const MonEspaceScreen()),
      ];
    }

    // Admin et membre de l'entreprise partagent la même barre.
    return [
      _Onglet('Accueil', Icons.home_outlined, AccueilScreen(utilisateur: widget.utilisateur)),
      _Onglet('Clients', Icons.people_outline, const RechercheClientScreen()),
      _Onglet('Mon EDT', Icons.event_outlined, const MonEdtScreen()),
      _Onglet('Dispos', Icons.event_available_outlined, const DisponibilitesScreen()),
    ];
  }
}

class _Onglet {
  final String label;
  final IconData icone;
  final Widget ecran;

  _Onglet(this.label, this.icone, this.ecran);
}
