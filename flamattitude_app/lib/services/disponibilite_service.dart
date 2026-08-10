import 'api_client.dart';
import 'auth_service.dart';

class DisponibiliteService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<Map<String, dynamic>> grille({String? semaine}) async {
    final jeton = await _authService.jetonActuel();
    final chemin = semaine != null ? '/mon-edt/grille?semaine=$semaine' : '/mon-edt/grille';
    return _api.get(chemin, jeton: jeton);
  }

  Future<Map<String, dynamic>> toggle(String date, String heure, {String? semaine}) async {
    final jeton = await _authService.jetonActuel();
    return _api.post('/mon-edt/toggle', {
      'date': date,
      'heure': heure,
      if (semaine != null) 'semaine': semaine,
    }, jeton: jeton);
  }

  Future<List<dynamic>> monEdt() async {
    final jeton = await _authService.jetonActuel();
    final reponse = await _api.get('/mon-edt/rdv', jeton: jeton);
    return reponse['mes_rdv'] as List<dynamic>;
  }

  Future<void> annulerRdv(int idRdv) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/mon-edt/rdv/$idRdv/annuler', {}, jeton: jeton);
  }
}
