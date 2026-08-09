import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';

import 'api_client.dart';

/// Logique métier de connexion : reproduit le flux du site web (email + mot de
/// passe -> code par email -> jeton) mais avec un jeton Sanctum à la place d'une
/// session, et une reconnexion silencieuse par biométrie via un jeton d'appareil.
class AuthService {
  static const _cleJeton = 'jeton_sanctum';
  static const _cleJetonAppareil = 'jeton_appareil';

  final ApiClient _api = ApiClient();
  final FlutterSecureStorage _stockage = const FlutterSecureStorage();
  final LocalAuthentication _biometrie = LocalAuthentication();

  /// Étape 1 : envoie email + mot de passe, déclenche l'envoi du code par email.
  Future<void> login(String email, String motDePasse) async {
    await _api.post('/auth/login', {
      'email': email,
      'mot-de-passe': motDePasse,
    });
  }

  /// Étape 2 : vérifie le code reçu par email, stocke le jeton Sanctum (et le
  /// jeton d'appareil biométrique s'il est fourni, pour le staff/admin).
  Future<Map<String, dynamic>> verifierCode(String email, String code) async {
    final reponse = await _api.post('/auth/verifier-code', {
      'email': email,
      'code': code,
    });

    await _stockage.write(key: _cleJeton, value: reponse['jeton'] as String);

    final jetonAppareil = reponse['jeton_appareil'] as String?;
    if (jetonAppareil != null) {
      await _stockage.write(key: _cleJetonAppareil, value: jetonAppareil);
    }

    return reponse['utilisateur'] as Map<String, dynamic>;
  }

  /// True si un jeton d'appareil (biométrie) est stocké sur ce téléphone.
  Future<bool> aUnJetonAppareil() async {
    return (await _stockage.read(key: _cleJetonAppareil)) != null;
  }

  /// Déverrouille l'app par Face ID / empreinte, puis échange le jeton d'appareil
  /// contre un nouveau jeton Sanctum. Retourne null si annulé/échoué : dans ce
  /// cas l'appelant doit basculer sur l'écran de connexion classique.
  Future<Map<String, dynamic>?> tenterConnexionBiometrique() async {
    final jetonAppareil = await _stockage.read(key: _cleJetonAppareil);
    if (jetonAppareil == null) return null;

    final autorise = await _biometrie.authenticate(
      localizedReason: 'Déverrouillez Flam\'Attitude',
    );
    if (!autorise) return null;

    final reponse = await _api.post('/auth/appareil/connecter', {
      'jeton': jetonAppareil,
    });

    await _stockage.write(key: _cleJeton, value: reponse['jeton'] as String);

    return reponse['utilisateur'] as Map<String, dynamic>;
  }

  /// Profil de l'utilisateur actuellement connecté (jeton Sanctum stocké).
  Future<Map<String, dynamic>> moi() async {
    final jeton = await _stockage.read(key: _cleJeton);
    return _api.get('/auth/moi', jeton: jeton);
  }

  Future<void> logout() async {
    final jeton = await _stockage.read(key: _cleJeton);

    try {
      await _api.post('/auth/logout', {}, jeton: jeton);
    } catch (_) {
      // Le jeton était peut-être déjà expiré côté serveur : on efface quand
      // même le stockage local, l'important est que l'app oublie la session.
    }

    await _stockage.delete(key: _cleJeton);
    await _stockage.delete(key: _cleJetonAppareil);
  }
}
