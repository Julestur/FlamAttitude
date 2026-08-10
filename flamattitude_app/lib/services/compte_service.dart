import 'api_client.dart';
import 'auth_service.dart';

class CompteService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  /// Renvoie le nouveau nom de fichier de la photo de profil.
  Future<String> changerPhotoProfil(String cheminFichier) async {
    final jeton = await _authService.jetonActuel();

    final reponse = await _api.postMultipart(
      '/compte/changer-photo-profil',
      {},
      {'photo_profil': cheminFichier},
      jeton: jeton,
    );

    return reponse['photo_profil'] as String;
  }

  Future<void> changerMotDePasse({
    required String ancienMotDePasse,
    required String motDePasse,
    required String motDePasseConfirmation,
  }) async {
    final jeton = await _authService.jetonActuel();

    await _api.post('/compte/changer-mot-de-passe', {
      'ancien_mot_de_passe': ancienMotDePasse,
      'mot_de_passe': motDePasse,
      'mot_de_passe_confirmation': motDePasseConfirmation,
    }, jeton: jeton);
  }
}
