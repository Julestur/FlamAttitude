<div align="center">

<img src="public/Images/LogoCyBlanc.png" alt="CYStage Logo" width="180" />

# CYSTAGE

**Plateforme de gestion des stages pour les étudiants, professeurs et entreprises de CY Tech.**

---

> 🎨 **Envie d'un meilleur rendu visuel ?**
> Consultez le **[README interactif](https://htmlpreview.github.io/?https://github.com/Julestur/CYStage/blob/main/README.html)** avec la charte graphique complète de CYStage.

</div>

---

## ✨ Fonctionnalités

| | Fonctionnalité | Description |
|---|---|---|
| 🔐 | **Authentification** | Connexion, déconnexion et inscription en 3 étapes avec vérification par e-mail |
| 🔑 | **Gestion du mot de passe** | Réinitialisation par lien e-mail et changement depuis le profil |
| 📋 | **Offres de stages** | Les entreprises et admins publient des offres consultables par tous |
| 📨 | **Candidatures** | Les étudiants postulent aux offres directement depuis la plateforme |
| 📊 | **Tableau de bord** | Statistiques en temps réel : stages, candidatures, utilisateurs |
| 👤 | **Gestion du profil** | Modification du mot de passe et de la photo de profil |

---

## 🚀 Installation en local

### Prérequis

Avant de commencer, assurez-vous d'avoir installé sur votre machine :

- **PHP** — [php.net/downloads](https://www.php.net/downloads)
- **Composer** — [getcomposer.org](https://getcomposer.org)
- **Git** — [git-scm.com](https://git-scm.com)

---

### Étape 1 — Cloner le dépôt

```bash
git clone https://github.com/Julestur/CYStage.git
```

---

### Étape 2 — Installer les dépendances PHP

```bash
composer install
```

---

### Étape 3 — Configurer l'environnement

Copiez le fichier d'exemple et générez la clé d'application :

```bash
cp .env.example .env

#Initialiser le fichier .env avec vos identifiants MySQL en enlevant les "#"

    env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME="Votre identifiant MySQL"
DB_PASSWORD="Votre mot de passe MySQL"

php artisan key:generate
```

---

### Étape 4 — Lancer les migrations

Le projet utilise **MySQL** 

```bash
# Lancer les migrations
php artisan migrate
```

---

### Étape 5 — Initialiser les bases de données

```bash
php artisan db:seed
```

---

### Étape 6 — Créer le lien symbolique pour les fichiers de stockage

```bash
php artisan storage:link
```

---

### Étape 7 — Lancer le serveur de développement

```bash
php artisan serve
```

> ✅ Le site est accessible sur **[http://localhost:8000](http://localhost:8000)**

---

## 📊 Identifiants de connexion

Utilisez les identifiants suivants pour tester l'application :

| Profil | Identifiant | Mot de passe |
|--------|-------------|--------------|
| Admin | Admin | Admin |
| Étudiant 1 | Esteban | Esteban |
| Étudiant 2 | Tilio | Tilio |
| Étudiant 3 | Jules | Jules |
| Étudiant 4 | Etudiant | Etudiant |
| Professeur | Prof | Prof |
| Entreprise | Entreprise | Entreprise |

---

## 📧 Configuration de l'envoi d'e-mails

L'inscription et la réinitialisation de mot de passe nécessitent un service SMTP.
Modifiez ces variables dans votre fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="ne.pas.repondreCYStage@gmail.com"
MAIL_PASSWORD="javmrpqjlmmgghea"
MAIL_FROM_ADDRESS="ne_pas_repondre@CYStage.com"
MAIL_FROM_NAME="CYStage"
```

---

## 📁 Structure du projet

```
cystage/
├── app/
│   ├── Http/Controllers/    # Auth, inscription, accueil, profil…
│   ├── Models/              # Modèles Eloquent (User…)
│   └── Mail/                # Envoi d'e-mails (vérification, réinit. MDP)
├── database/
│   ├── migrations/          # Structure BDD (utilisateur, stage, candidature…)
│   └── seeders/             # Données de test (lieu où sont géré les identifiants et mot de passe) 
├── public/
│   ├── css/                 # Feuilles de style
│   ├── js/                  # Scripts 
│   └── Images/              # Images
├── resources/views/         # Vues Blade (pages de chaque profil, message, layouts, partials)
│   ├── acceuil/             # de chaque profil
│   ├── connexion/           
│   ├── gestionMDP/
│   ├── Mail/           
│   ├──  messages/
│   └── partials/
├── storage/                 # zone de stockage
│   ├── app/                 # de l'application
│   └──framework/           # de Laravel
└── routes/
    └── web.php              # Toutes les routes de l'application
```

---

<div align="center">

**CYStage** - Projet scolaire - Equipe 4 - EHRMANN_PRETO_TURCHI - CY Tech

</div>
