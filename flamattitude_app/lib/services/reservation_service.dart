import 'api_client.dart';
import 'auth_service.dart';

class ReservationService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<Map<String, dynamic>> creneaux({int? idTypeRdv, String? semaine}) async {
    final jeton = await _authService.jetonActuel();
    final params = <String>[
      if (idTypeRdv != null) 'type=$idTypeRdv',
      if (semaine != null) 'semaine=$semaine',
    ];
    final chemin = '/mon-espace/creneaux${params.isNotEmpty ? '?${params.join('&')}' : ''}';
    return _api.get(chemin, jeton: jeton);
  }

  Future<Map<String, dynamic>> grille({required int idTypeRdv, String? semaine}) async {
    final jeton = await _authService.jetonActuel();
    final params = ['type=$idTypeRdv', if (semaine != null) 'semaine=$semaine'];
    return _api.get('/mon-espace/creneaux/grille?${params.join('&')}', jeton: jeton);
  }

  Future<void> reserver({
    required int idTypeRdv,
    required String date,
    required String heure,
    required String motif,
  }) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/mon-espace/creneaux/reserver', {
      'idTypeRdv': idTypeRdv,
      'date': date,
      'heure': heure,
      'motif': motif,
    }, jeton: jeton);
  }

  Future<void> annuler(int idRdv) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/mon-espace/creneaux/$idRdv/annuler', {}, jeton: jeton);
  }
}
