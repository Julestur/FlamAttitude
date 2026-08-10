import 'api_client.dart';
import 'auth_service.dart';

class ClientService {
  final ApiClient _api = ApiClient();
  final AuthService _authService = AuthService();

  Future<List<dynamic>> rechercher(String recherche) async {
    final jeton = await _authService.jetonActuel();
    final chemin = recherche.isEmpty ? '/clients' : '/clients?search=$recherche';
    final reponse = await _api.get(chemin, jeton: jeton);
    return reponse['clients'] as List<dynamic>;
  }

  Future<Map<String, dynamic>> dossier(int idClient) async {
    final jeton = await _authService.jetonActuel();
    return _api.get('/clients/$idClient', jeton: jeton);
  }

  Future<void> modifierContact(int idClient, {String? telephone, String? adresse}) async {
    final jeton = await _authService.jetonActuel();
    await _api.post('/clients/$idClient/contact', {
      'telephone': telephone,
      'adresse': adresse,
    }, jeton: jeton);
  }

  Future<void> ajouterDocument(int idClient, String nom, String cheminFichier) async {
    final jeton = await _authService.jetonActuel();
    await _api.postMultipart(
      '/clients/$idClient/documents',
      {'nom': nom},
      {'fichier': cheminFichier},
      jeton: jeton,
    );
  }

  Future<void> ajouterFacture(
    int idClient, {
    required String montant,
    required String description,
    required String statut,
    required String dateEmission,
    String? cheminFichier,
  }) async {
    final jeton = await _authService.jetonActuel();
    await _api.postMultipart(
      '/clients/$idClient/factures',
      {
        'montant': montant,
        'description': description,
        'statut': statut,
        'date_emission': dateEmission,
      },
      cheminFichier != null ? {'fichier': cheminFichier} : {},
      jeton: jeton,
    );
  }
}
