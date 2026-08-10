import 'api_client.dart';
import 'auth_service.dart';

class PlanningAdminService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<List<dynamic>> membres() async {
    final jeton = await _authService.jetonActuel();
    final reponse = await _api.get('/admin/membres', jeton: jeton);
    return reponse['membres'] as List<dynamic>;
  }

  Future<Map<String, dynamic>> grilleMembre(int idMembre, {String? semaine}) async {
    final jeton = await _authService.jetonActuel();
    final chemin = semaine != null
        ? '/admin/membres/$idMembre/grille?semaine=$semaine'
        : '/admin/membres/$idMembre/grille';
    return _api.get(chemin, jeton: jeton);
  }

  Future<void> deplacerRdv(int idRdv, {required int idMembre, required String date, required String heure}) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/admin/rdv/$idRdv/deplacer', {
      'id_membre': idMembre,
      'date': date,
      'heure': heure,
    }, jeton: jeton);
  }

  Future<void> annulerRdv(int idRdv) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/admin/rdv/$idRdv/annuler', {}, jeton: jeton);
  }
}
