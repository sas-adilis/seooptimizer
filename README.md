# SEO Optimizer - Module PrestaShop

**Module complet d'optimisation SEO pour PrestaShop : optimisations front, audits par crawl et notation SEO.**

| Info | Valeur |
|------|--------|
| Version | 1.6.1 |
| Auteur | Adilis |
| Compatibilité PS | 1.7.0.0 - 9.x |
| PHP | 7.1 - 8.3+ |

---

## Fonctionnalités

### Sitemap XML
- Génération du sitemap XML avec pagination configurable
- Produits (avec images), catégories, pages CMS, fabricants, fournisseurs, pages meta et pages de modules
- Priorités et fréquences configurables par type d'entité
- Routes : `/sitemap.xml` (index) et `/sitemap/{type}/{lang}/{page}.xml`

### Redirections
- Gestion manuelle (ajout, édition, suppression) : 301, 302, 404 et 410
- Import en masse via fichier CSV (séparateur `,` ou `;`), export CSV
- Redirections automatiques à la suppression de produits, catégories et fabricants

### Indexation des pages
- Contrôle de l'indexation par type de page (fournisseurs, fabricants, magasins, sitemap)
- Actions possibles : ne rien faire, noindex, erreur 404, redirection 301/302
- Règles d'indexation par pattern d'URL (exact, contient, commence par)

### URLs canoniques
- Génération automatique des balises canonical
- Support hreflang pour les sites multilingues
- Header HTTP `Link: rel="canonical"` optionnel
- Liste configurable de paramètres à ignorer

### Configuration SEO par entité
- Mots-clés cibles, URL canonique personnalisée, noindex et nofollow, par langue
- Onglet SEO intégré aux fiches catégorie (rendu serveur), et panneau JS sur produit, fabricant, fournisseur et page CMS
- Aperçu Google (SERP) et panneau d'audit de la page directement dans la fiche

### Templates de meta
- Meta title / description générés à partir de templates à variables, uniquement quand le champ est vide
- Variables disponibles par type d'entité

### Rich Snippets (JSON-LD)
- Schema.org : BreadcrumbList, WebPage, WebSite, Product, Store
- Politique de retour marchand (MerchantReturnPolicy)
- Propriétés additionnelles produit (caractéristiques sélectionnables)
- Liste de produits structurée (ProductItemList)
- Scanner de rich snippets existants dans le thème

### Métadonnées sociales
- Balises Open Graph et Twitter Cards
- Profils de réseaux sociaux (Instagram, Facebook, X, Pinterest, YouTube, LinkedIn, TikTok)

### Codes de vérification
- Google Search Console, Bing Webmaster Tools, Pinterest

### Robots.txt et llms.txt
- Édition directe de `robots.txt` avec validation en direct et testeur d'URL
- Édition de `llms.txt` avec validation de structure
- Presets, sauvegarde des versions précédentes, réinitialisation

### Obfuscation de liens
- Masquage des URLs aux robots via l'attribut `data-obfuscate`
- Gestion du « link juice » interne

### Suivi des erreurs 404
- Enregistrement de chaque requête 404 (URL, referrer, IP)
- Historique sur 6 mois, graphique de fréquence, pages les plus touchées

---

## Audits SEO

### Audits par crawl (12)

Les audits parcourent l'ensemble des pages du site. Chacun implémente `AuditInterface` et est déclaré dans `AuditRegistry`.

| Audit | Clé | Description |
|-------|-----|-------------|
| Hiérarchie des titres | `heading_hierarchy` | Structure H1-H6 (H1 manquant, doublons, niveaux sautés) |
| Alt manquants | `missing_alt` | Images sans attribut alt ou avec alt vide |
| Liens cassés | `broken_links` | Liens internes en erreur (pages, images, CSS, JS) |
| Liens redirigés | `redirected_links` | Liens internes pointant vers du contenu redirigé |
| Temps de chargement | `page_load_time` | Temps de réponse par page |
| Poids des pages | `page_weight` | Poids total (HTML, images, CSS, JS) avec détail par type |
| Liens non sécurisés | `unsecured_links` | Liens HTTP sur des pages HTTPS (contenu mixte) |
| Balises meta | `meta_tags` | Longueur des titres et meta descriptions |
| Maillage interne | `internal_links` | Liens internes entrants et sortants |
| Ratio texte | `text_ratio` | Part de texte par rapport au HTML |
| Mots-clés | `keyword_check` | Présence des mots-clés cibles dans la page |
| Données structurées | `structured_data` | Présence et validité du JSON-LD |

Chaque audit :
- Traite les URLs par lots de 10 avec progression en temps réel
- Stocke ses résultats en base (`audit_run` / `audit_result`)
- Permet l'export CSV
- Déclare ses KPI, ses pénalités de score et son poids dans la note globale

### Notation SEO
- Note de A+ à F par page et note globale du site (`Score/`)
- Colonne « SEO » optionnelle dans les listings BO produits, catégories, fabricants, fournisseurs et pages CMS, avec audit progressif des entités non notées
- Vue d'ensemble des pages avec leurs problèmes critiques et avertissements, et ré-audit d'une page à la demande

### Audit front
Panneau d'audit affiché sur le site aux employés connectés au back-office (option « Front audit »).

### Cron
`controllers/front/cron.php` lance les audits hors BO, protégé par la clé `secure_key`. L'URL est affichée dans la configuration du module.

| Paramètre | Valeur |
|-----------|--------|
| `token` | clé secrète du module |
| `audit` | `all` (défaut), `list`, ou la clé d'un audit |

### Seuils configurables

| Paramètre | Défaut |
|-----------|--------|
| Temps de réponse « Good » / « Slow » | 750 / 1000 ms |
| Poids « Light » / « Heavy » | 1024 / 3072 KB |
| Ratio texte « Low » / « Good » | 100 / 300 |
| Longueur titre min / max | 50 / 70 caractères |
| Longueur meta description min / max | 140 / 170 caractères |

---

## Structure du module

```
seooptimizer/
|-- seooptimizer.php              # Classe principale (install, hooks délégués, getContent)
|-- composer.json                 # Autoloader PSR-4
|-- classes/                      # ObjectModel (redirection, 404, page, règle d'indexation)
|-- controllers/front/            # sitemap, cron, pageaudit
|-- sql/                          # install.sql, uninstall.sql, install.php
|-- upgrade/                      # Scripts de mise à jour
|-- src/
|   |-- Actions/                  # Hooks front : canonical, rich snippets, social, 404,
|   |                             #   indexation, config SEO par entité, redirections, audit front
|   |-- Admin/                    # BO : assets, AJAX, page de configuration, panneaux, colonnes de grille
|   |-- Audit/                    # Audits, registre, runner, KPI
|   |-- Content/DataList/         # Listes admin (redirections, règles, 404)
|   |-- CrawlerObserver/          # Observeurs de crawl (un par audit)
|   |-- Entity/                   # Définitions d'entités (produit, catégorie, CMS...)
|   |-- Events/                   # Redirections automatiques à la suppression
|   |-- Form/                     # Formulaires de configuration
|   |-- FormBuilderModifier/      # Extension des formulaires Symfony (catégorie, fournisseur)
|   |-- FormHandler/              # Sauvegarde des champs SEO de la catégorie
|   |-- FrontAudit/               # Analyse d'une page unique (panneau d'audit)
|   |-- HtmlOutputBefore/         # Obfuscation de liens
|   |-- MetaTemplate/             # Moteur de templates de meta
|   |-- Pages/                    # Audit complet, audit d'une page, agrégation
|   |-- Score/                    # Calcul de la note et correspondance note / lettre
|   |-- SitemapIndexer/           # Indexeurs par type d'entité
|   |-- Storage/                  # Persistance des audits
|   |-- Utils/                    # Crawl cURL, extraction HTML, URLs
|   `-- Routes.php                # Routes du sitemap
`-- views/
    |-- css/                      # SCSS en partiels + CSS compilés
    |-- js/
    |-- icons/ img/
    `-- templates/                # admin/ et hook/ (Smarty)
```

---

## Tables en base de données

| Table | Description |
|-------|-------------|
| `seooptimizer_redirect` | Redirections manuelles |
| `seooptimizer_log_404` | Journal des erreurs 404 |
| `seooptimizer_page` | Note SEO, mots-clés, canonical et noindex/nofollow par entité, langue et boutique |
| `seooptimizer_audit_run` | Exécutions d'audit |
| `seooptimizer_audit_result` | Résultats d'audit |
| `seooptimizer_indexation_rule` | Règles d'indexation par pattern d'URL |

---

## Hooks utilisés

| Hook | Utilisation |
|------|-------------|
| `backOfficeHeader` | CSS/JS du back-office |
| `displayBackOfficeFooter` | Onglet SEO des fiches catégorie |
| `displayAdminProductsSeoStepBottom` | Panneau d'audit sur la fiche produit |
| `displayHeader` | Templates de meta, indexation, canonical, social, codes de vérification |
| `displayBeforeBodyClosingTag` | Rich snippets, suivi 404, panneau d'audit front |
| `actionFrontControllerInitBefore` | Exécution des redirections |
| `actionObjectDeleteBefore` | Redirections automatiques à la suppression |
| `actionOutputHTMLBefore` | Obfuscation des liens |
| `actionCategoryFormBuilderModifier`, `actionRootCategoryFormBuilderModifier` | Onglet SEO catégorie |
| `actionAfter{Create,Update}{,Root}CategoryFormHandler` | Sauvegarde des champs SEO catégorie |
| `actionSupplierFormBuilderModifier` | Champs SEO sur le formulaire fournisseur |
| `action{Product,Category,Manufacturer,Supplier,CmsPage}Grid{Definition,QueryBuilder}Modifier` | Colonne « SEO » des listings |
| `moduleRoutes` | Routes du sitemap XML |

---

## Architecture technique

- **Namespace** : `Adilis\SeoOptimizer` (PSR-4 via Composer, dossier `src/`)
- **Contrôleurs** : Legacy uniquement
- **Templates** : Smarty (`.tpl`), CSS et JS externalisés
- **Traductions** : système Legacy ; les chaînes passées au JS restent dans `seooptimizer.php` pour conserver leurs clés
- **Sécurité** : tokens CSRF, `pSQL()`, cast `(int)`, clé secrète pour le cron
- **Multi-boutique** : contexte shop sur les configurations et les tables
- **Mise en forme** : `make fix` lance php-cs-fixer
