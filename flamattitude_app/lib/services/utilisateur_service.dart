import 'api_client.dart';
import 'auth_service.dart';

class UtilisateurService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<void> creer({
    required String nom,
    required String prenom,
    required String email,
    required String identifiant,
    required String motDePasse,
    required String motDePasseConfirmation,
    required String grade,
  }) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/utilisateurs', {
      'nom': nom,
      'prenom': prenom,
      'email': email,
      'identifiant': identifiant,
      'mot_de_passe': motDePasse,
      'mot_de_passe_confirmation': motDePasseConfirmation,
      'grade': grade,
    }, jeton: jeton);
  }

  Future<List<dynamic>> listerPourSuppression(String recherche) async {
    final jeton = await _authService.jetonActuel();
    final chemin = recherche.isEmpty ? '/utilisateurs/supprimables' : '/utilisateurs/supprimables?search=$recherche';
    final reponse = await _api.get(chemin, jeton: jeton);
    return reponse['utilisateurs'] as List<dynamic>;
  }

  Future<void> supprimer(int id) async {
    final jeton = await _authService.jetonActuel();
    await _api.delete('/utilisateurs/$id', jeton: jeton);
  }
}
