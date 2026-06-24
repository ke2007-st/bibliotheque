# Mise en ligne — front et back separes

Architecture recommandee (gratuit, URLs permanentes HTTPS) :

| Composant | Hebergeur | URL permanente |
|-----------|-----------|----------------|
| **Frontend** (HTML/CSS/JS) | [Cloudflare Pages](https://pages.cloudflare.com) | `https://votre-projet.pages.dev` |
| **Backend** (API PHP) | [Railway](https://railway.app) | `https://votre-api.up.railway.app` |
| **Base de donnees** | Railway MariaDB (plugin) | interne |

Alternatives frontend : Netlify, GitHub Pages.  
Alternatives backend : Render, Fly.io, VPS (OVH, Hetzner).

---

## Etape 1 — Pousser le code sur GitHub

```powershell
cd c:\Users\PROBOOK\docker_docker_web
git add .
git commit -m "Prepare deploiement front/back separe"
git push origin main
```

---

## Etape 2 — Backend sur Railway

1. Creer un compte sur [railway.app](https://railway.app)
2. **New Project** → **Deploy from GitHub repo** → selectionner ce depot
3. Railway detecte `railway.toml` et le Dockerfile `deploy/backend/Dockerfile`
4. Ajouter une base **MariaDB** : **+ New** → **Database** → **MariaDB**
5. Variables d environnement sur le service **backend** :

| Variable | Valeur |
|----------|--------|
| `DB_HOST` | host interne Railway (ex. `containers-us-west-xxx.railway.app`) |
| `DB_NAME` | `railway` (ou valeur du plugin) |
| `DB_USER` | fourni par Railway |
| `DB_PASS` | fourni par Railway |
| `CORS_ORIGINS` | URL du front (etape 3), ex. `https://biblio-front.pages.dev` |

6. **Settings** → **Networking** → **Generate Domain**  
   → URL permanente du type `https://biblio-api-production.up.railway.app`

7. Initialiser la base (une seule fois) :

```powershell
Get-Content sql/init.sql | railway run mariadb ...
```

Ou depuis votre PC si la DB est exposee :

```powershell
Get-Content sql/init.sql | docker compose exec -T db mariadb ...
# puis sur Railway, importer via client SQL Railway
Get-Content sql/seed_catalogue_demo.sql | ...
```

8. Verifier : ouvrir `https://VOTRE-API.up.railway.app/api/health.php`  
   → doit afficher `{"success":true,"status":"ok"}`

---

## Etape 3 — Frontend sur Cloudflare Pages

1. Compte sur [dash.cloudflare.com](https://dash.cloudflare.com)
2. **Workers & Pages** → **Create** → **Pages** → **Connect to Git**
3. Configuration build :

| Champ | Valeur |
|-------|--------|
| Build command | `powershell deploy/prepare-frontend.ps1` ou `bash deploy/prepare-frontend.sh` |
| Build output directory | `deploy/frontend-dist` |

4. Variables d environnement (Build) :

| Variable | Valeur |
|----------|--------|
| `BIBLIO_API_URL` | `https://VOTRE-API.up.railway.app/api` |
| `BIBLIO_ADMIN_API_URL` | `https://VOTRE-API.up.railway.app/api/admin` |

5. Deploy → URL permanente : `https://votre-projet.pages.dev`

6. **Retour sur Railway** : mettre a jour `CORS_ORIGINS` avec l URL Pages exacte, puis redeployer le backend.

---

## Etape 4 — Tester

| Page | URL |
|------|-----|
| Connexion membre | `https://votre-projet.pages.dev/login.html` |
| Catalogue | `https://votre-projet.pages.dev/index.html` |
| Admin | `https://votre-projet.pages.dev/admin/login.html` |

Comptes demo : voir README (`demo@biblio.local` / `Demo1234`, admin `admin@biblio.local` / `admin123`).

---

## Alternative — Netlify (frontend)

1. [netlify.com](https://netlify.com) → **Add new site** → Git
2. Build : `deploy/prepare-frontend.ps1`, publish : `deploy/frontend-dist`
3. Variables : `BIBLIO_API_URL`, `BIBLIO_ADMIN_API_URL`
4. URL permanente : `https://votre-site.netlify.app`

---

## Domaine personnalise (optionnel)

- **Cloudflare Pages** : Custom domains → `biblio.mondomaine.fr`
- **Railway** : Custom domain → `api.mondomaine.fr`
- Mettre a jour `CORS_ORIGINS` et `BIBLIO_API_URL` avec les nouvelles URLs.

---

## Developpement local (inchangé)

```powershell
docker compose up -d
# Front + API sur http://localhost:8080 — pas de CORS_ORIGINS necessaire
```

Le fichier `src/js/env.js` utilise `/api` en local (meme domaine).

---

## Depannage

| Probleme | Solution |
|----------|----------|
| Erreur CORS | Verifier `CORS_ORIGINS` = URL exacte du front (avec `https://`, sans slash final) |
| Connexion echoue (401 apres login) | HTTPS obligatoire en prod ; cookies `SameSite=None` actives si `CORS_ORIGINS` est defini |
| API 502 | Verifier variables DB sur Railway |
| Front appelle `/api` au lieu du backend | Rebuild front avec `BIBLIO_API_URL` correct |
