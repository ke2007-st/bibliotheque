# BiblioDash — Bibliotheque PHP Docker

Application de gestion de bibliotheque en **PHP pur**, **HTML/CSS/JS vanilla**, avec **Docker** (Nginx + PHP-FPM + MariaDB).

## Mise en ligne (front + back separes)

Guide complet : **[DEPLOY.md](DEPLOY.md)**

- **Frontend** → Cloudflare Pages ou Netlify (`*.pages.dev` permanent)
- **Backend API** → Railway (`*.up.railway.app` permanent)
- **Base** → MariaDB sur Railway

## Demarrage rapide

```powershell
cd c:\Users\PROBOOK\docker_docker_web
docker compose up -d --build
```

> **Port : 8080** (8000 evite les conflits avec d'autres projets locaux)

## Acces

| Zone | URL |
|------|-----|
| Connexion membre | http://localhost:8080/login.html |
| Inscription | http://localhost:8080/register.html |
| Espace membre | http://localhost:8080/index.html |
| Admin (separe) | http://localhost:8080/admin/login.html |

## Comptes demo

**Administrateur** (espace `/admin/` uniquement) :
- Email : `admin@biblio.local`
- Mot de passe : `admin123`

**Membre demo** (apres chargement du jeu de donnees) :
- Email : `demo@biblio.local`
- Mot de passe : `Demo1234`

**Charger le catalogue demo** (80 livres, 10 par scenario) :

```powershell
Get-Content sql/seed_catalogue_demo.sql | docker compose exec -T db mariadb -ukevine -pkevine1234 php_docker
```

Scenarios couverts : disponibles, empruntes, mes emprunts, ruptures, reserves bibliotheque, reservables, deja reserves, deja achetes.

**Membre** : creer un compte via `/register.html` ou utiliser le compte demo ci-dessus.

## Architecture

```
src/
├── index.html          # Dashboard membre (lecture seule)
├── login.html          # Connexion membre
├── register.html       # Inscription
├── api/                # API membres
│   ├── auth/           # login, register, profile...
│   ├── books.php       # GET uniquement
│   └── stats.php
└── admin/              # Interface admin separee
    ├── login.html
    ├── index.html
    └── js/             # API admin dediee

api/admin/              # CRUD livres, gestion membres
```

## Securite

- Sessions separees membre / admin
- Token CSRF sur les actions sensibles
- Rate limiting connexion (5 tentatives / 15 min)
- Mots de passe bcrypt (cost 12)
- Requetes PDO preparees
- Headers HTTP securises (Nginx + PHP)

## Commandes utiles

```powershell
# Arreter
docker compose down

# Reinitialiser la base de donnees
docker compose down -v
docker compose up -d --build

# Voir les logs
docker compose logs -f
```

## Base de donnees

- Host interne : `db`
- Port externe : `3306`
- Base : `php_docker`
- User : `kevine` / Password : `kevine1234`

## Migration base existante

Si la base existait avant l'auth :

```powershell
docker compose exec db mariadb -u kevine -pkevine1234 php_docker < sql/migrate_auth.sql
```
