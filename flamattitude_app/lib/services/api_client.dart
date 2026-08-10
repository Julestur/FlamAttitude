import 'dart:convert';

import 'package:http/http.dart' as http;

/// Erreur renvoyée par le serveur (message métier en français, prêt à afficher).
class ApiException implements Exception {
  final String message;
  final int codeHttp;

  ApiException(this.message, this.codeHttp);

  @override
  String toString() => message;
}

/// Couche réseau brute vers l'API Laravel : encode/décode le JSON et transforme
/// une réponse d'erreur en [ApiException] avec le message déjà en français.
class ApiClient {
  // 127.0.0.1 fonctionne ici car le téléphone est relié en USB avec
  // "adb reverse tcp:8000 tcp:8000" : le port 8000 du téléphone est redirigé
  // vers le port 8000 du PC via le câble, indépendamment du réseau Wi-Fi.
  // À changer pour l'IP réelle du serveur une fois hors développement local.
  static const String baseUrl = 'http://127.0.0.1:8000/api';

  // Racine du serveur, sans le "/api" : utile pour construire l'URL d'un
  // fichier public (ex: photo de profil dans public/images_profil/).
  static const String racineServeur = 'http://127.0.0.1:8000';

  Future<Map<String, dynamic>> post(
    String chemin,
    Map<String, dynamic> corps, {
    String? jeton,
  }) async {
    final reponse = await http.post(
      Uri.parse('$baseUrl$chemin'),
      headers: _entetes(jeton),
      body: jsonEncode(corps),
    );

    return _traiter(reponse);
  }

  Future<Map<String, dynamic>> get(String chemin, {String? jeton}) async {
    final reponse = await http.get(
      Uri.parse('$baseUrl$chemin'),
      headers: _entetes(jeton),
    );

    return _traiter(reponse);
  }

  Future<Map<String, dynamic>> delete(String chemin, {String? jeton}) async {
    final reponse = await http.delete(
      Uri.parse('$baseUrl$chemin'),
      headers: _entetes(jeton),
    );

    return _traiter(reponse);
  }

  /// POST multipart (upload de fichier), avec des champs texte additionnels.
  /// [cheminsFichiers] associe un nom de champ (ex: "photo_profil") au chemin
  /// local du fichier sur le téléphone.
  Future<Map<String, dynamic>> postMultipart(
    String chemin,
    Map<String, String> champs,
    Map<String, String> cheminsFichiers, {
    String? jeton,
  }) async {
    final requete = http.MultipartRequest('POST', Uri.parse('$baseUrl$chemin'))
      ..headers['Accept'] = 'application/json'
      ..fields.addAll(champs);

    if (jeton != null) requete.headers['Authorization'] = 'Bearer $jeton';

    for (final entree in cheminsFichiers.entries) {
      requete.files.add(await http.MultipartFile.fromPath(entree.key, entree.value));
    }

    final reponseFlux = await requete.send();
    final reponse = await http.Response.fromStream(reponseFlux);

    return _traiter(reponse);
  }

  Map<String, String> _entetes(String? jeton) => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (jeton != null) 'Authorization': 'Bearer $jeton',
      };

  Map<String, dynamic> _traiter(http.Response reponse) {
    final corps = reponse.body.isNotEmpty
        ? jsonDecode(reponse.body) as Map<String, dynamic>
        : <String, dynamic>{};

    if (reponse.statusCode >= 200 && reponse.statusCode < 300) {
      return corps;
    }

    throw ApiException(_messageErreur(corps), reponse.statusCode);
  }

  /// Laravel renvoie les erreurs de validation automatique (Request::validate())
  /// sous la forme {"message": "générique", "errors": {"champ": ["message précis"]}}.
  /// On préfère le premier message précis quand il existe.
  String _messageErreur(Map<String, dynamic> corps) {
    final erreurs = corps['errors'] as Map<String, dynamic>?;
    if (erreurs != null && erreurs.isNotEmpty) {
      final premiereListe = erreurs.values.first as List<dynamic>;
      if (premiereListe.isNotEmpty) return premiereListe.first as String;
    }

    return corps['message'] as String? ?? 'Une erreur est survenue.';
  }
}
