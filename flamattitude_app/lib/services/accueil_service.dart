import 'api_client.dart';
import 'auth_service.dart';

class AccueilService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<Map<String, dynamic>> stats() async {
    final jeton = await _authService.jetonActuel();
    return _api.get('/accueil', jeton: jeton);
  }
}
