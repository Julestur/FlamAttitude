import 'api_client.dart';

class InscriptionService {
  final ApiClient _api = ApiClient();

  Future<void> creer({
    required String nom,
    required String prenom,
    required String telephone,
    required String adresse,
    required String pseudo,
    required String email,
    required String motDePasse,
    required String motDePasseConfirmation,
  }) async {
    await _api.post('/auth/inscription', {
      'nom': nom,
      'prenom': prenom,
      'telephone': telephone,
      'adresse': adresse,
      'pseudo': pseudo,
      'email': email,
      'mot_de_passe': motDePasse,
      'mot_de_passe_confirmation': motDePasseConfirmation,
    });
  }

  Future<void> verifierCode(String email, String code) async {
    await _api.post('/auth/inscription/verifier-code', {'email': email, 'code': code});
  }

  Future<void> renvoyerCode(String email) async {
    await _api.post('/auth/inscription/renvoyer-code', {'email': email});
  }
}
