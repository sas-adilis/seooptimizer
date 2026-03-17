# SEO Optimizer - Module PrestaShop

**Module complet d'optimisation SEO pour PrestaShop.**

| Info | Valeur |
|------|--------|
| Version | 1.0.0 |
| Auteur | Adilis |
| Compatibilite PS | 1.7.0.0 - 9.x |
| PHP | 7.1 - 8.3+ |

---

## Fonctionnalites

### Sitemap XML
- Generation automatique du sitemap XML avec pagination configurable
- Images produits, categories, fabricants et fournisseurs dans le sitemap
- Priorites et frequences configurables par type d'entite
- Routes : `/sitemap.xml` (index) et `/sitemap/{type}/{lang}/{page}.xml`

### Redirections 301/302
- Gestion manuelle des redirections (ajout, edition, suppression)
- Import en masse via fichier CSV (separateur `,` ou `;`)
- Redirections automatiques a la suppression/desactivation de produits et categories

### Indexation des pages
- Controle de l'indexation par type de page (fournisseurs, fabricants, magasins, sitemap)
- Actions possibles : ne rien faire, noindex, erreur 404, redirection 301/302
- Regles d'indexation par pattern d'URL (exact, contient, commence par)

### URLs canoniques
- Generation automatique des balises canonical
- Support hreflang pour les sites multilingues
- Header HTTP `Link: rel="canonical"` optionnel
- Liste configurable de parametres a ignorer

### Rich Snippets (JSON-LD)
- Schema.org : BreadcrumbList, WebPage, WebSite, Product, Store
- Politique de retour marchand (MerchantReturnPolicy)
- Proprietes additionnelles produit (caracteristiques selectionnables)
- Liste de produits structuree (ProductItemList)
- Scanner de rich snippets existants dans le theme

### Metadonnees sociales
- Balises Open Graph (og:title, og:image, og:type...)
- Twitter Cards
- Gestion des profils reseaux sociaux (Instagram, Facebook, X, Pinterest, YouTube, LinkedIn, TikTok)

### Codes de verification
- Google Search Console
- Bing Webmaster Tools
- Pinterest

### Robots.txt
- Edition directe du fichier robots.txt
- Sauvegarde automatique des versions precedentes
- Reinitialisation aux valeurs par defaut

### Correction des legendes d'images
- Methode texte : regle avec variables (`{product_title}`, `{product_meta_title}`, `{counter}`)
- Methode IA : generation automatique via prompt configurable
- Support multilingue

### Obfuscation de liens
- Masquage des URLs aux robots via attribut `data-obfuscate`
- Gestion du "link juice" interne

---

## Audits SEO (crawler)

Le module embarque 5 audits qui crawlent l'ensemble des pages du site :

| Audit | Cle | Description |
|-------|-----|-------------|
| Alt manquants | `missing_alt` | Detecte les images sans attribut alt ou avec alt vide |
| Liens casses | `broken_links` | Verifie tous les liens internes (pages, images, CSS, JS) pour les erreurs 404 |
| Temps de chargement | `page_load_time` | Mesure le temps de reponse de chaque page (seuils : Good/Medium/Slow) |
| Poids des pages | `page_weight` | Mesure le poids total (HTML, images, CSS, JS) avec detail par type |
| Hierarchie des titres | `heading_hierarchy` | Analyse la structure H1-H6 (H1 manquant, doublons, niveaux sautes) |

Chaque audit :
- Traite les URLs par batch de 10
- Affiche la progression en temps reel (KPIs, barres de progression par entite)
- Stocke les resultats en cache JSON
- Permet l'export CSV des resultats

### Seuils configurables

| Parametre | Defaut |
|-----------|--------|
| Temps de reponse "Good" | 750 ms |
| Temps de reponse "Slow" | 1000 ms |
| Poids "Light" | 1024 KB |
| Poids "Heavy" | 3072 KB |
| Longueur titre min/max | 50 / 70 caracteres |
| Longueur meta description min/max | 140 / 170 caracteres |

---

## Rapports d'analyse

| Rapport | Description |
|---------|-------------|
| Longueur des titres | Pages dont le titre est trop court ou trop long |
| Longueur meta description | Meta descriptions hors limites recommandees |
| Liens non securises | Liens HTTP sur des pages HTTPS (contenu mixte) |
| Liens rediriges | Liens internes pointant vers du contenu redirige |
| Liens 404 | Liens internes casses detectes |
| Legendes images | Images sans legende / alt vide |

---

## Suivi des erreurs 404

- Enregistrement automatique de chaque requete 404 (URL, referrer, IP)
- Historique sur 6 mois
- Graphique de frequence
- Tableau de bord avec les pages les plus touchees

---

## Structure du module

```
seooptimizer/
|-- seooptimizer.php              # Classe principale
|-- composer.json                 # Autoloader PSR-4
|-- controllers/
|   +-- front/
|       +-- sitemap.php           # Controleur front sitemap XML
|-- sql/
|   |-- install.sql
|   +-- uninstall.sql
|-- src/
|   |-- Actions/                  # Hooks frontend (canonical, rich snippets, social, 404)
|   |-- Audit/                    # 5 audits + runner + interface
|   |-- CrawlerObserver/          # Observeurs de crawl (alt, liens, temps, poids, headings)
|   |-- Content/
|   |   |-- DataList/             # Listes admin (redirections, regles, 404)
|   |   +-- Report/               # Rapports d'analyse (titres, meta, liens)
|   |-- EntityDefinition/         # Definitions d'entites (produit, categorie, CMS...)
|   |-- Events/                   # Evenements suppression (redirections auto)
|   |-- Form/                     # 17 formulaires de configuration
|   |-- FormBuilderModifier/      # Extension formulaire fournisseur
|   |-- HtmlOutputBefore/         # Obfuscation de liens
|   +-- SitemapIndexer/           # Indexeurs par type d'entite pour le sitemap
|-- views/
|   |-- css/                      # SCSS organise en partiels
|   |-- js/
|   |-- img/                      # Illustrations panda
|   +-- templates/
|       |-- admin/                # Templates back-office (configure, audit)
|       +-- hook/                 # Templates hooks (header, rich snippets, social)
+-- vendor/                       # Dependances Composer
```

---

## Tables en base de donnees

| Table | Description |
|-------|-------------|
| `ps_seooptimizer_redirect` | Redirections manuelles (from, to, type 301/302) |
| `ps_seooptimizer_log_404` | Journal des erreurs 404 (URL, referrer, IP, date) |
| `ps_seooptimizer_indexation_rule` | Regles d'indexation par pattern d'URL |

---

## Hooks utilises

| Hook | Utilisation |
|------|-------------|
| `backOfficeHeader` | Chargement CSS/JS en back-office |
| `displayBeforeBodyClosingTag` | Rich snippets, surveillance 404 |
| `displayHeader` | Canonical, social meta, verification codes, indexation |
| `actionFrontControllerInitBefore` | Execution des redirections |
| `actionObjectDeleteBefore` | Redirections automatiques a la suppression |
| `actionSupplierFormBuilderModifier` | Champs SEO sur le formulaire fournisseur |
| `actionOutputHTMLBefore` | Obfuscation des liens |
| `moduleRoutes` | Routes sitemap XML |

---

## Architecture technique

- **Namespace** : `Adilis\SeoOptimizer` (PSR-4 via Composer)
- **Controleurs** : Legacy uniquement (pas de Symfony)
- **Templates** : Smarty (`.tpl`)
- **Cache** : Fichiers JSON dans `var/cache/seooptimizer/`
- **Securite** : Tokens CSRF, pSQL(), cast (int), validation MIME uploads
- **Multi-shop** : Gestion du contexte shop sur les configurations et les tables
