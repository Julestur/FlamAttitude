import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../services/api_client.dart';
import '../services/compte_service.dart';
import '../theme.dart';

class ChangerPhotoProfilScreen extends StatefulWidget {
  final String? urlPhotoActuelle;

  const ChangerPhotoProfilScreen({super.key, this.urlPhotoActuelle});

  @override
  State<ChangerPhotoProfilScreen> createState() => _ChangerPhotoProfilScreenState();
}

class _ChangerPhotoProfilScreenState extends State<ChangerPhotoProfilScreen> {
  final _compteService = CompteService();
  final _picker = ImagePicker();

  XFile? _fichierChoisi;
  bool _enCours = false;
  String? _erreur;

  ImageProvider? _imageAffichee() {
    if (_fichierChoisi != null) return FileImage(File(_fichierChoisi!.path));
    if (widget.urlPhotoActuelle != null) return NetworkImage(widget.urlPhotoActuelle!);
    return null;
  }

  Future<void> _choisir(ImageSource source) async {
    final fichier = await _picker.pickImage(source: source, imageQuality: 85);
    if (fichier != null) setState(() => _fichierChoisi = fichier);
  }

  Future<void> _valider() async {
    if (_fichierChoisi == null) return;

    setState(() {
      _enCours = true;
      _erreur = null;
    });

    try {
      final nouveauNomFichier = await _compteService.changerPhotoProfil(_fichierChoisi!.path);

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Votre photo de profil a été mise à jour !')),
      );
      Navigator.pop(context, nouveauNomFichier);
    } on ApiException catch (e) {
      setState(() => _erreur = e.message);
    } on SocketException {
      setState(() => _erreur = 'Impossible de contacter le serveur.');
    } finally {
      if (mounted) setState(() => _enCours = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Changer photo de profil')),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            children: [
              CircleAvatar(
                radius: 70,
                backgroundColor: CouleursFlamattitude.champ,
                backgroundImage: _imageAffichee(),
                child: (_fichierChoisi == null && widget.urlPhotoActuelle == null)
                    ? const Icon(Icons.person, color: CouleursFlamattitude.texte, size: 70)
                    : null,
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _choisir(ImageSource.gallery),
                      icon: const Icon(Icons.photo_library_outlined),
                      label: const Text('Galerie'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _choisir(ImageSource.camera),
                      icon: const Icon(Icons.camera_alt_outlined),
                      label: const Text('Appareil photo'),
                    ),
                  ),
                ],
              ),
              if (_erreur != null) ...[
                const SizedBox(height: 16),
                Text(
                  _erreur!,
                  style: const TextStyle(color: CouleursFlamattitude.accent),
                  textAlign: TextAlign.center,
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: (_fichierChoisi == null || _enCours) ? null : _valider,
                  child: _enCours
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Valider'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
