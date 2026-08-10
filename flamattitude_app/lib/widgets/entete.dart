import 'package:flutter/material.dart';

import '../screens/ajouter_utilisateur_screen.dart';
import '../screens/changer_mot_de_passe_screen.dart';
import '../screens/changer_photo_profil_screen.dart';
import '../screens/login_screen.dart';
import '../screens/supprimer_utilisateur_screen.dart';
import '../screens/types_rdv_screen.dart';
import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme.dart';

// Doit rester synchronisé avec App\Support\Roles côté Laravel.
class _Roles {
  static const admin = 1;
}

/// Reprend le menu déroulant du site (photo de profil -> options), dans sa
/// variante "app mobile" : les liens déjà couverts par la navigation de l'app
/// (accueil, recherche client, EDT...) ne sont pas dupliqués ici.
class EnteteFlamattitude extends StatefulWidget implements PreferredSizeWidget {
  final Map<String, dynamic> utilisateur;
  final int idStatut;

  const EnteteFlamattitude({super.key, required this.utilisateur, required this.idStatut});

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  State<EnteteFlamattitude> createState() => _EnteteFlamattitudeState();
}

class _EnteteFlamattitudeState extends State<EnteteFlamattitude> {
  late String? _pdp = widget.utilisateur['photo_profil'] as String?;

  void _bientotDisponible() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Bientôt disponible.')),
    );
  }

  Future<void> _deconnecter() async {
    await AuthService().logout();

    if (!mounted) return;
    Navigator.pushAndRemoveUntil(
      context,
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  String? get _urlPhoto =>
      (_pdp != null && _pdp!.isNotEmpty) ? '${ApiClient.racineServeur}/images_profil/$_pdp' : null;

  Future<void> _changerPhoto() async {
    final nouveauNomFichier = await Navigator.push<String>(
      context,
      MaterialPageRoute(builder: (_) => ChangerPhotoProfilScreen(urlPhotoActuelle: _urlPhoto)),
    );

    if (nouveauNomFichier != null) setState(() => _pdp = nouveauNomFichier);
  }

  @override
  Widget build(BuildContext context) {
    final urlPhoto = _urlPhoto;

    return AppBar(
      title: const Text("Flam'Attitude"),
      actions: [
        PopupMenuButton<String>(
          onSelected: (valeur) {
            switch (valeur) {
              case 'deconnexion':
                _deconnecter();
              case 'mdp':
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const ChangerMotDePasseScreen()),
                );
              case 'pdp':
                _changerPhoto();
              case 'ajouter-utilisateur':
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const AjouterUtilisateurScreen()),
                );
              case 'supprimer-utilisateur':
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const SupprimerUtilisateurScreen()),
                );
              case 'types-rdv':
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const TypesRdvScreen()),
                );
              default:
                _bientotDisponible();
            }
          },
          itemBuilder: (context) => [
            PopupMenuItem(
              value: 'photo',
              enabled: false,
              child: Center(
                child: CircleAvatar(
                  radius: 60,
                  backgroundColor: CouleursFlamattitude.champ,
                  backgroundImage: urlPhoto != null ? NetworkImage(urlPhoto) : null,
                  child: urlPhoto == null
                      ? const Icon(Icons.person, color: CouleursFlamattitude.texte, size: 60)
                      : null,
                ),
              ),
            ),
            const PopupMenuDivider(),
            const PopupMenuItem(value: 'mdp', child: Text('Changer mot de passe')),
            const PopupMenuItem(value: 'pdp', child: Text('Changer photo de profil')),
            if (widget.idStatut == _Roles.admin) ...[
              const PopupMenuDivider(),
              const PopupMenuItem(value: 'ajouter-utilisateur', child: Text('Ajouter un utilisateur')),
              const PopupMenuItem(value: 'supprimer-utilisateur', child: Text('Supprimer un utilisateur')),
              const PopupMenuItem(value: 'types-rdv', child: Text('Types de RDV')),
            ],
            const PopupMenuDivider(),
            const PopupMenuItem(
              value: 'deconnexion',
              child: Text('Déconnexion', style: TextStyle(color: CouleursFlamattitude.accent)),
            ),
          ],
          child: Padding(
            padding: const EdgeInsets.only(right: 16),
            child: CircleAvatar(
              backgroundColor: CouleursFlamattitude.champ,
              backgroundImage: urlPhoto != null ? NetworkImage(urlPhoto) : null,
              child: urlPhoto == null
                  ? const Icon(Icons.person, color: CouleursFlamattitude.texte)
                  : null,
            ),
          ),
        ),
      ],
    );
  }
}
