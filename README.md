# eco house. — Gestion des factures d'eau et d'électricité

Mini-projet PHP (ENSA Tétouan, Génie Informatique) — **version alpha**.
PHP 8 · MySQL (PDO) · architecture mini-MVC du TP visiteurs · XAMPP.
Référence : *Cahier des charges – Version alpha* (à respecter à la lettre).

## 1. Installation (chaque membre, une seule fois)

1. Installer **XAMPP** (PHP 8) et démarrer **Apache** et **MySQL** dans le panneau de contrôle.
2. Cloner le dépôt **dans `C:\xampp\htdocs\`** pour obtenir `C:\xampp\htdocs\gestion_factures` :
   ```
   cd C:\xampp\htdocs
   git clone <URL-DU-DEPOT> gestion_factures
   ```
3. Ouvrir http://localhost/phpmyadmin → onglet **Importer** → choisir `gestion_factures.sql` → **Importer**.
   Le script crée la base `gestion_factures` (11 tables) et les données de test. Il peut être ré-importé à tout moment pour tout remettre à zéro.
4. Ouvrir http://localhost/gestion_factures/

> Si les pages donnent une erreur 404 : vérifier que `mod_rewrite` est actif et que `AllowOverride All` est réglé pour `htdocs` (valeur par défaut de XAMPP).
> Si MySQL a un mot de passe, modifier uniquement `BD/Connexion.php` en local, sans le commiter.

## 2. Comptes de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Fournisseur | fournisseur@ecohouse.ma | `Four#2026` |
| Clients | salma@gmail.com · youssef@gmail.com · nadia@gmail.com · khadija@gmail.com · mouad@gmail.com | `Client#2026` |

Chaque client a une consommation et une facture de **septembre 2026** (la facture de Salma est payée, les autres impayées).
Index de septembre, pour préparer la saisie d'octobre :

| Client | Index eau | Index élec. | Octobre à saisir pour le test |
|---|---|---|---|
| Salma Bennani | 1245 | 8430 | eau **1260**, élec **8550** → 15 m³ / 120 kWh → HT 221,25 · TVA 39,83 · TTC **261,08** |
| Youssef ElAmrani | 1250 | 6200 | eau **1150** → *index inférieur* (anomalie) |
| Nadia Berrada | 640 | 3100 | saisie normale (ex. eau 650, élec 3200) |
| Khadija Tazi | 410 | 5100 | élec **5900** → 800 kWh > 500 → *dépasse le seuil* |
| Mouad Jemaoui | 850 | 2300 | eau **980** → 130 m³ > 50 → *dépasse le seuil* |

## 3. Architecture

```
index.php            routeur unique (lit Entité/action/id, appelle la fonction de Traitement/)
.htaccess            envoie toute URL vers index.php?url=...
gestion_factures.sql script final : tables + données de test
BD/                  requêtes PDO préparées, retournent des tableaux   (pas de HTML, $_POST, $_SESSION)
Traitement/          contrôles, calculs, sessions, upload, redirections (pas de SQL, pas de HTML)
IHM/                 HTML + echo de variables déjà préparées            (pas de SQL, pas de calcul)
assets/              css/style.css (feuille unique), img/logo.svg
uploads/             photos des compteurs (jamais commitées)
```

### Routes de l'alpha

| URL | Fonction | Rôle | Propriétaire |
|---|---|---|---|
| `Auth/login` · `Auth/logout` | `authLogin()` · `authLogout()` | tous | Souraya |
| `Auth/bientot/{page}` | `authBientot($page)` | connecté | Souraya |
| `Client/all` · `one/{id}` · `add` · `update/{id}` · `delete/{id}` | `clientAll()` … | fournisseur | Anwar |
| `Consommation/add` · `Consommation/all` | `consoAdd()` · `consoAll()` | client | Amal |
| `Facture/all` · `one/{id}` | `factureAll()` · `factureOne($id)` | client | Farah |
| `Facture/admin` · `Facture/pdf/{id}` | `factureAdmin()` · `facturePdf($id)` | fournisseur · tous | Chaimaa |
| `Paiement/add/{id}` | `paiementAdd($id)` | fournisseur | Chaimaa |

Le routeur déduit la fonction de l'URL : `Client/all` → `clientAll()`. Seule exception : `Consommation` → préfixe `conso`.
Une route absente de la constante `ROUTES` d'`index.php` donne la page 404 : **pour ajouter une route, demander à Souraya**.
Les entiers de l'URL (`{id}`) sont passés à la fonction si son premier paramètre est de type `int`.

## 4. Fonctions communes (Traitement/Auth.php)

```php
verifierRole('client');          // ou 'fournisseur' ; 'connecte' = n'importe quel rôle connecté (ex. facturePdf)
afficherVue('facture/one.php', ['facture' => $f, 'titre' => 'Facture']);  // header + menu + vue + footer
rediriger('Facture/all', 'Votre facture a été générée');                 // succès (bande verte)
rediriger('Consommation/add', 'Vous avez déjà saisi ce mois', 'erreur'); // erreur (bande rouge)
```
- Session : `$_SESSION['id']`, `$_SESSION['role']` (`client` | `fournisseur`), `$_SESSION['nom']`.
- Un client filtre **toujours** ses requêtes sur `$_SESSION['id']`, jamais sur un id venu de l'URL.
- Dans une vue : `<?= htmlspecialchars($x, ENT_QUOTES, 'UTF-8') ?>` pour tout texte ; liens avec `<?= BASE_URL ?>Facture/all`.
- Constante `SAISIE_TOUJOURS_OUVERTE` (index.php) : `true` en développement, `false` pour tester la règle R1.
- Un formulaire poste vers la même URL que l'affichage : `<form method="post" action="<?= BASE_URL ?>Client/add">`.

## 5. Feuille de style : classes à réutiliser (assets/css/style.css)

| Besoin | Classes |
|---|---|
| En-tête de page | `<header class="carte carte-titre"><div><h1>…</h1><p class="sous-titre">…</p></div><div class="cercle">…</div></header>` |
| Carte | `carte` · deux cartes côte à côte : `grille-2` |
| Formulaire | `<form class="formulaire">` avec `label` + `input`/`select`/`textarea` (style automatique) ; bouton `btn-principal` ; secondaire `btn-secondaire` |
| Tableau | `tableau` (dans `scroll-x` si large) ; actions : `<div class="actions">` |
| Badges | `badge badge-rouge` (Impayée, index inférieur) · `badge-jaune` (En vérification, dépasse le seuil) · `badge-vert` (Payée, Normale) |
| Icônes (Lucide) | `<i data-lucide="eye"></i>` · `pencil` · `trash-2` · `download` · `camera` · `upload` |
| Messages | automatiques via `rediriger(...)` |

Pas de style dans les vues et pas d'autre feuille CSS.

## 6. Git

- `main` est protégée : on ne pousse jamais dessus directement.
- Une branche par module : `feature/clients` · `feature/saisie` · `feature/factures-client` · `feature/factures-fournisseur`.
- Commits courts en français (« Ajout du calcul par tranches »), au moins un par jour de travail.

```
git checkout main
git pull origin main
git checkout -b feature/saisie          # une fois, à la création de la branche
... travail ...
git add <vos fichiers>                  # seulement VOS fichiers
git commit -m "Ajout de la détection des anomalies"
git push -u origin feature/saisie
```
Puis, sur GitHub : **Compare & pull request** → Souraya relit et fusionne.
Avant de reprendre : `git checkout main` puis `git pull origin main`.
Jamais de commit de `uploads/*` (déjà dans `.gitignore`) ni de mot de passe personnel.
**Seul l'auteur d'un fichier le modifie** ; les autres passent par lui.
