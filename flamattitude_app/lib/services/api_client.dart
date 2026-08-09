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
  // IP locale du PC qui fait tourner "php artisan serve" sur le même réseau Wi-Fi
  // que le téléphone. À changer si le PC change de réseau ou d'adresse.
  static const String baseUrl = 'http://192.168.1.25:8000/api';

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

    throw ApiException(
      corps['message'] as String? ?? 'Une erreur est survenue.',
      reponse.statusCode,
    );
  }
}
