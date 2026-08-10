import 'api_client.dart';
import 'auth_service.dart';

class TypeRdvService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<Map<String, dynamic>> index() async {
    final jeton = await _authService.jetonActuel();
    return _api.get('/types-rdv', jeton: jeton);
  }

  Future<void> creer({required String nom, required int dureeMinutes, required String couleur}) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/types-rdv', {
      'nom': nom,
      'duree_minutes': dureeMinutes,
      'couleur': couleur,
    }, jeton: jeton);
  }

  Future<void> modifier(int id, {required String nom, required int dureeMinutes, required String couleur}) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/types-rdv/$id', {
      'nom': nom,
      'duree_minutes': dureeMinutes,
      'couleur': couleur,
    }, jeton: jeton);
  }

  Future<bool> basculerActif(int id) async {
    final jeton = await _authService.jetonActuel();
    final reponse = await _api.post('/types-rdv/$id/basculer', {}, jeton: jeton);
    return reponse['actif'] as bool;
  }

  /// [affectations] : idMembre -> liste des idTypeRdv qu'il propose.
  Future<void> enregistrerAffectations(Map<int, List<int>> affectations) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/types-rdv-affectations', {
      'types': affectations.map((idMembre, idsTypes) => MapEntry(idMembre.toString(), idsTypes)),
    }, jeton: jeton);
  }
}
