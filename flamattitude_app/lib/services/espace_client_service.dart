import 'api_client.dart';
import 'auth_service.dart';

class EspaceClientService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<Map<String, dynamic>> accueil() async {
    final jeton = await _authService.jetonActuel();
    return _api.get('/mon-espace', jeton: jeton);
  }
}
