# Formation SEO / GSO — Guide complet pour néophites

> Approche : partir des questions humaines pour aller vers la technique  
> Source officielle : [developers.google.com/search/docs](https://developers.google.com/search/docs)  
> Tout ce qui est gratuit est indiqué. Les outils payants sont signalés.  
> Claude peut remplacer une grande partie des outils payants — indiqué au fil du guide.

---

## Principe fondamental

Le SEO a deux faces indissociables :
- **Côté Google** — comment le moteur fonctionne et ce qu'il veut
- **Côté utilisateur** — pourquoi les gens cherchent ce qu'ils cherchent

Les outils (gratuits ou payants) ne font qu'automatiser ce qu'on pourrait faire à la main. **Les concepts, eux, ne changent pas.** Comprendre pourquoi une technique existe vaut mieux que de suivre une checklist aveuglément.

---

# CHAPITRE 1 — Comprendre Google

## "Comment Google décide-t-il qui apparaît en premier ?"

Avant d'optimiser quoi que ce soit, comprendre comment Google pense. Sans ce vocabulaire de base, tout le reste est flou.

### Les 3 étapes de Google

**1 — Crawl**
Googlebot (le robot de Google) parcourt le web en suivant les liens, page par page. Il visite votre site régulièrement pour détecter les nouveautés.

**2 — Index**
Google enregistre le contenu des pages dans sa base de données gigantesque. Une page non indexée n'existe pas pour Google.

**3 — Ranking**
Google classe les pages indexées selon des centaines de critères pour répondre au mieux à chaque requête.

### Les critères de classement principaux

- Pertinence du contenu par rapport à la requête
- Qualité et autorité de la page (E-E-A-T)
- Expérience utilisateur (vitesse, mobile, stabilité visuelle)
- Backlinks (d'autres sites qui font confiance au vôtre)
- Données structurées (aide Google à comprendre le contenu)

### Les Search Essentials — les règles minimales de Google

Google publie ses règles officiellement. Tout site qui les respecte a une chance d'apparaître. Tout site qui les enfreint risque une pénalité.

- **Spam policies** : pas de contenu dupliqué massif, pas de liens artificiels, pas de cloaking
- **Pénalités manuelles** : un humain chez Google a signalé un problème → visible dans Search Console → Manuel Actions
- **Pénalités algorithmiques** : une mise à jour de l'algorithme a fait baisser le site (Panda, Penguin, Helpful Content...)
- **Core updates** : mises à jour majeures plusieurs fois par an — peuvent faire monter ou descendre des sites entiers

### Docs Google à lire

- [How Google Search Works](https://developers.google.com/search/docs/fundamentals/how-search-works)
- [Search Essentials](https://developers.google.com/search/docs/essentials)
- [Spam policies](https://developers.google.com/search/docs/essentials/spam-policies)
- [SEO Starter Guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide)
- [A guide to Google Search ranking systems](https://developers.google.com/search/docs/appearance/ranking-systems-guide)
- [Core updates](https://developers.google.com/search/docs/appearance/core-updates)

### Action

Lire les 3 premières pages sans prendre de notes — juste s'imprégner du vocabulaire.

---

# CHAPITRE 2 — La visibilité de base

## "Est-ce qu'on me trouve sur Google ?"

La première question à se poser. Avant d'optimiser, savoir si Google connaît le site.

### Google Search Console — l'outil indispensable (gratuit)

Search Console est le canal de communication officiel entre Google et le propriétaire d'un site. Aucun autre outil ne donne ces données.

**Installation :**
1. Aller sur [search.google.com/search-console](https://search.google.com/search-console)
2. Ajouter le site et vérifier la propriété (balise HTML ou DNS)
3. Attendre 48h pour les premières données

**Rapports essentiels :**
- **Couverture** → quelles pages sont indexées, lesquelles posent problème et pourquoi
- **Performances** → clics, impressions, positions, CTR par requête et par page
- **Expérience** → Core Web Vitals, mobile
- **Manuel Actions** → pénalités manuelles éventuelles

### Sitemap.xml — aider Google à trouver toutes les pages

Un sitemap est une liste de toutes les URLs importantes du site, au format XML. Il dit à Googlebot où regarder.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://monsite.fr/</loc></url>
  <url><loc>https://monsite.fr/page-importante/</loc></url>
</urlset>
```

- Soumettre le sitemap dans Search Console → Sitemaps
- Le placer à `monsite.fr/sitemap.xml`
- Claude peut générer un sitemap.xml à partir d'une liste d'URLs

### Robots.txt — dire à Google ce qu'il ne doit PAS crawler

```
User-agent: *
Disallow: /admin/
Disallow: /panier/
Allow: /
Sitemap: https://monsite.fr/sitemap.xml
```

- Ne jamais bloquer les pages importantes par erreur
- Vérifier avec Search Console → Inspection d'URL

### Canonicalisation — éviter le contenu dupliqué

Quand plusieurs URLs affichent le même contenu, Google ne sait pas laquelle indexer. La balise canonical désigne la version officielle.

```html
<link rel="canonical" href="https://monsite.fr/page-officielle/" />
```

### Balises meta robots — contrôler l'indexation page par page

```html
<meta name="robots" content="noindex, nofollow"> <!-- page cachée de Google -->
<meta name="robots" content="index, follow">     <!-- page normale -->
```

### Redirections — ne jamais perdre le "jus SEO"

- **301** → redirection permanente (transmet l'autorité SEO) → à utiliser lors d'une migration
- **302** → redirection temporaire (ne transmet pas l'autorité) → rarement utilisé en SEO
- Éviter les **chaînes de redirections** (A → B → C → D) : Google abandonne après quelques sauts
- Éviter les **boucles de redirections** (A → B → A)

### Migration de site — checklist critique

Lors d'une refonte, d'un changement de CMS ou de domaine :
1. Cartographier toutes les anciennes URLs
2. Créer une table de correspondance ancienne URL → nouvelle URL
3. Mettre en place toutes les redirections 301 avant la mise en ligne
4. Soumettre le nouveau sitemap dans Search Console
5. Surveiller les erreurs d'indexation pendant 30 jours

### Docs Google à lire

- [Get started with Search Console](https://developers.google.com/search/docs/monitor-debug/search-console-start)
- [Learn about sitemaps](https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview)
- [Introduction to robots.txt](https://developers.google.com/search/docs/crawling-indexing/robots/intro)
- [What is URL canonicalization](https://developers.google.com/search/docs/crawling-indexing/canonicalization)
- [Redirects and Google Search](https://developers.google.com/search/docs/crawling-indexing/301-redirects)
- [Move a site with URL changes](https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes)

---

# CHAPITRE 3 — La santé technique

## "Mon site est-il techniquement sain ?"

Un site lent, non sécurisé ou mal structuré est pénalisé — même si le contenu est excellent.

### HTTPS / SSL — la sécurité de base

- Tout site doit être en `https://` — c'est un signal de classement Google depuis 2014
- Vérifier que le certificat SSL est valide et non expiré
- Vérifier que toutes les pages utilisent HTTPS (pas de contenu mixte HTTP/HTTPS)

### Core Web Vitals — les 3 métriques de performance

Google mesure l'expérience réelle des visiteurs via 3 métriques :

| Métrique | Ce qu'elle mesure | Seuil "bon" |
|----------|-------------------|-------------|
| **LCP** (Largest Contentful Paint) | Temps d'affichage du contenu principal | < 2,5 secondes |
| **INP** (Interaction to Next Paint) | Réactivité aux clics | < 200 ms |
| **CLS** (Cumulative Layout Shift) | Stabilité visuelle (éléments qui bougent) | < 0,1 |

**Outils :**
- [PageSpeed Insights](https://pagespeed.web.dev/) — gratuit, mobile ET desktop
- Search Console → Expérience → Core Web Vitals

### Mobile-first indexing

Google indexe la version mobile du site en priorité.
- Design responsive obligatoire
- Même contenu sur mobile et desktop
- Navigation intuitive sur petit écran
- Tester avec [Test d'optimisation mobile](https://search.google.com/test/mobile-friendly)

### Vitesse — les optimisations techniques

- **Images** : compresser (WebP plutôt que JPEG/PNG), dimensionner correctement, lazy loading
- **CSS/JS** : minifier les fichiers, réduire les scripts bloquants
- **CDN** (Content Delivery Network) : servir les fichiers depuis des serveurs proches du visiteur
- **Cache navigateur** : éviter de recharger les ressources identiques
- **HTTP/2 ou HTTP/3** : protocoles de transfert plus rapides

### Architecture du site — la règle des 3 clics

Aucune page importante ne devrait être à plus de 3 clics de la page d'accueil. Une architecture plate aide Google à crawler toutes les pages.

```
Accueil (1 clic)
├── Catégorie A (2 clics)
│   ├── Page produit 1 (3 clics) ✓
│   └── Page produit 2 (3 clics) ✓
└── Catégorie B (2 clics)
    └── Page produit 3 (3 clics) ✓
```

### Liens cassés et erreurs

- **404** → page introuvable → mauvaise expérience utilisateur + perte d'autorité SEO
- **5xx** → erreur serveur → Google ne peut pas accéder à la page
- Détecter avec Search Console → Couverture, ou Screaming Frog (gratuit jusqu'à 500 URLs)

### JavaScript SEO

Si le site utilise beaucoup de JavaScript (React, Vue, Angular...) :
- Google peut avoir du mal à indexer le contenu rendu en JS
- Préférer le rendu côté serveur (SSR) pour le contenu important
- Tester avec l'outil d'inspection d'URL dans Search Console

### Accessibilité (WCAG)

L'accessibilité numérique devient un signal SEO indirect.
- Textes alternatifs sur les images
- Contraste suffisant entre texte et fond
- Navigation possible au clavier
- Structure de titres logique (H1 → H2 → H3)

### Interstitiels et popups

Google pénalise les popups intrusives sur mobile qui bloquent le contenu principal.

### Outils audit technique

| Outil | Usage | Prix | Claude peut remplacer ? |
|-------|-------|------|------------------------|
| PageSpeed Insights | Core Web Vitals | Gratuit | ❌ Données réelles |
| Search Console | Erreurs d'indexation | Gratuit | ❌ Données officielles Google |
| Screaming Frog | Crawler complet (500 URLs gratuites) | Gratuit / £199/an | ✅ Via Claude Code |
| Sitebulb | Audit technique + visuels | ~$19/mois | ✅ Via Claude Code |

### Docs Google à lire

- [Core Web Vitals](https://developers.google.com/search/docs/appearance/core-web-vitals)
- [Understanding page experience](https://developers.google.com/search/docs/appearance/page-experience)
- [Mobile site and mobile-first indexing](https://developers.google.com/search/docs/crawling-indexing/mobile/mobile-sites-mobile-first-indexing)
- [JavaScript SEO basics](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics)
- [Avoid intrusive interstitials](https://developers.google.com/search/docs/appearance/avoid-intrusive-interstitials)
- [URL structure](https://developers.google.com/search/docs/crawling-indexing/url-structure)

---

# CHAPITRE 4 — Le contenu

## "Est-ce que ce que je dis intéresse des gens ?"
## "De quoi devrais-je parler sur mon site ?"

Ces deux questions sont liées. On commence par comprendre ce que les gens cherchent, puis on crée le contenu qui y répond.

### Les 4 intentions de recherche

Chaque requête cache une intention. Une page efficace répond à une intention précise.

| Intention | Ce que l'utilisateur veut | Exemples de requêtes | Contenu adapté |
|-----------|--------------------------|---------------------|----------------|
| **Informationnelle** | Apprendre, comprendre | "comment faire X", "qu'est-ce que Y" | Articles, guides, FAQ |
| **Navigationnelle** | Trouver un site précis | "Facebook login", "[marque] contact" | Page d'accueil, pages institutionnelles |
| **Commerciale** | Comparer avant de choisir | "meilleur X pour Y", "avis sur Z" | Comparatifs, témoignages |
| **Transactionnelle** | Agir, acheter, réserver | "acheter X", "réserver Y", "prix de Z" | Pages produit, formulaires, CTA |

**Comment identifier l'intention d'un mot-clé :**
Taper le mot-clé dans Google et observer la page 1. Des articles → informationnelle. Des pages produit → transactionnelle. Des comparatifs → commerciale.

### Lire sa position dans Google

Search Console → Performances → colonne "Position"

| Position | Ce que ça signifie | Taux de clic moyen |
|----------|--------------------|--------------------|
| 1–3 | Podium — très visible | 15 à 30% |
| 4–10 | Page 1 — encore visible | 2 à 10% |
| 11–20 | Page 2 — quasi invisible | < 1% |
| 20+ | Inexistant pour l'utilisateur | ~0% |

Les pages entre position 11 et 20 sont la première cible à travailler — elles sont proches de la page 1 et peuvent progresser rapidement.

### Recherche de mots-clés

**Outils gratuits :**
- [Google Keyword Planner](https://ads.google.com/intl/fr/home/tools/keyword-planner/) → volumes de recherche officiels
- [Google Trends](https://trends.google.com) → tendances et saisonnalité
- Google lui-même → autocomplétion, "Autres questions posées", recherches associées

**Concepts clés :**
- **Volume de recherche** : combien de fois ce mot est tapé par mois
- **Difficulté** : à quel point il est difficile de se positionner (Semrush / Ahrefs — estimations propriétaires)
- **Longue traîne** : expressions de 3+ mots, moins compétitives, plus précises, meilleures pour débuter
- **Cannibalisation** : deux pages du même site ciblant le même mot-clé → se font concurrence → à éviter

**Claude peut :** générer des listes de mots-clés, identifier l'intention derrière une requête, simuler AnswerThePublic et AlsoAsked.

### Topic clusters — l'architecture sémantique

Organiser le contenu en clusters thématiques plutôt qu'en pages isolées :

```
Page pilier (sujet large)
├── Article cluster 1 (sous-sujet 1) → lien vers pilier
├── Article cluster 2 (sous-sujet 2) → lien vers pilier
└── Article cluster 3 (sous-sujet 3) → lien vers pilier
```

Google valorise les sites qui couvrent un sujet en profondeur.

### E-E-A-T — le standard de qualité de Google

- **Experience** : l'auteur a-t-il une expérience directe du sujet ?
- **Expertise** : l'auteur est-il compétent dans ce domaine ?
- **Authoritativeness** : le site est-il reconnu comme référence ?
- **Trustworthiness** : le site est-il digne de confiance ?

**Actions concrètes :** biographies d'auteurs, sources citées, page "À propos" complète, mentions légales, avis vérifiables.

### Optimisation on-page — les éléments techniques

**Balise title**
- 50–60 caractères · mot-clé principal au début · unique pour chaque page

**Meta description**
- 150–160 caractères · résumé accrocheur · augmente le CTR (pas un facteur de classement direct)

**Structure des titres**
- Un seul H1 par page · H2 = sections · H3 = sous-sections · hiérarchie logique

**URLs**
- Courtes et descriptives : `/guide-seo-debutant/`
- En minuscules · tirets comme séparateurs · mot-clé inclus

**Images**
- Attribut `alt` descriptif · nom de fichier descriptif · format WebP · taille optimisée

**Maillage interne**
- 5 à 10 liens internes par article · textes d'ancrage descriptifs (pas "cliquez ici")

### Fraîcheur du contenu

- Mettre à jour les articles existants plutôt que d'en créer de nouveaux
- Indiquer la date de dernière mise à jour
- Vérifier les statistiques et liens obsolètes
- Fréquence recommandée : 2 à 4 publications ou mises à jour par mois

### Contenu à éviter

- **Thin content** : pages avec trop peu de contenu réel
- **Duplicate content** : copier-coller d'autres sources ou pages internes identiques
- **Keyword stuffing** : répétition artificielle et excessive du mot-clé
- **Contenu IA massif sans valeur ajoutée** : Google le détecte et le pénalise

### Calendrier éditorial SEO

1. Lister les intentions de recherche cibles
2. Identifier les mots-clés pour chaque intention
3. Créer une page par intention (pas deux pages pour le même sujet)
4. Prioriser les mots-clés à faible difficulté pour démarrer
5. Publier régulièrement et mettre à jour les pages existantes

### Outils contenu

| Outil | Usage | Prix | Claude peut remplacer ? |
|-------|-------|------|------------------------|
| Google Keyword Planner | Volumes de recherche | Gratuit | ⚠️ Sans volumes réels |
| Google Trends | Tendances, saisonnalité | Gratuit | ❌ Données temps réel |
| AnswerThePublic | Questions utilisateurs | Freemium | ✅ Claude nativement |
| AlsoAsked | "Autres questions posées" | Freemium | ✅ Claude nativement |
| SurferSEO | Brief éditorial SEO | dès 99$/mois | ✅ Claude nativement |
| Yoast / Rank Math | Plugin WordPress on-page | Gratuit / 99€/an | ✅ Claude nativement |
| Ubersuggest | Suggestions mots-clés | dès 12$/mois | ⚠️ Partiel |

### Docs Google à lire

- [Creating helpful, reliable, people-first content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
- [Title links](https://developers.google.com/search/docs/appearance/title-link)
- [Snippets](https://developers.google.com/search/docs/appearance/snippet)
- [Images best practices](https://developers.google.com/search/docs/appearance/google-images)
- [Guidance on using generative AI](https://developers.google.com/search/docs/fundamentals/using-gen-ai-content)

---

# CHAPITRE 5 — L'apparence dans les résultats

## "Mon site apparaît-il bien dans les résultats — et donne-t-il envie de cliquer ?"

Apparaître ne suffit pas. Il faut que le résultat affiché soit attractif et pertinent.

### Snippets — ce qui s'affiche dans Google

Un snippet standard comprend le title link, l'URL affichée et la description (meta description ou générée par Google).

### Featured Snippets — la position zéro

Google extrait parfois un encadré de réponse directe au-dessus des résultats. Pour y apparaître :
- Répondre directement à une question dans le contenu
- Utiliser des listes, tableaux, définitions claires
- Structurer le contenu avec des H2/H3 sous forme de questions

### Données structurées — les rich results

Les données structurées (JSON-LD) indiquent à Google le type de contenu d'une page.

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [{
    "@type": "Question",
    "name": "Qu'est-ce que le SEO ?",
    "acceptedAnswer": {
      "@type": "Answer",
      "text": "Le SEO est l'ensemble des techniques pour améliorer la visibilité d'un site."
    }
  }]
}
```

**Types de rich results utiles :**
FAQ · Article · LocalBusiness · Product · VideoObject · BreadcrumbList · HowTo

**Outils :**
- [Rich Results Test](https://search.google.com/test/rich-results) — gratuit
- [Schema.org](https://schema.org) — référence de tous les types
- Claude peut générer le code JSON-LD pour n'importe quel type

### Vidéo SEO

- Héberger les vidéos sur YouTube pour le SEO vidéo
- Utiliser le schema `VideoObject` pour les vidéos intégrées
- Titre, description et transcription optimisés sur YouTube

### Recherche vocale

Les requêtes vocales sont plus longues et conversationnelles.
- Cibler des expressions naturelles ("comment faire X en 5 minutes")
- Optimiser pour les featured snippets (souvent lus par les assistants vocaux)
- Répondre à des questions précises avec des réponses concises

### Favicons

Le favicon apparaît dans les résultats de recherche sur mobile. Un favicon absent nuit à la perception de la marque.

### Docs Google à lire

- [Understand how structured data works](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)
- [Search gallery](https://developers.google.com/search/docs/appearance/structured-data/search-gallery)
- [Featured snippets](https://developers.google.com/search/docs/appearance/featured-snippets)
- [Visual Elements gallery](https://developers.google.com/search/docs/appearance/visual-elements-gallery)
- [Videos](https://developers.google.com/search/docs/appearance/video)
- [Favicons](https://developers.google.com/search/docs/appearance/favicon-in-search)

---

# CHAPITRE 6 — La concurrence

## "Comment s'en sortent mes concurrents sur internet ?"

Comprendre pourquoi des sites concurrents sont mieux positionnés — et identifier les opportunités.

### Identifier ses vrais concurrents SEO

Les concurrents SEO ne sont pas forcément les mêmes que les concurrents commerciaux. Un concurrent SEO est un site qui se positionne sur les mêmes mots-clés.

**Méthode gratuite :** taper ses mots-clés principaux dans Google et noter les 10 premiers résultats.

### Ce qu'on peut analyser sans outil payant

- **Contenu** : longueur, structure, qualité, mots-clés utilisés
- **Balises** : title, H1, meta description (visibles dans le code source)
- **Vitesse** : tester avec PageSpeed Insights
- **Données structurées** : Rich Results Test

**Claude peut :** analyser le code source d'une page concurrente collé dans le chat et en extraire tous les éléments SEO.

### Ce que les outils payants ajoutent

- Mots-clés sur lesquels un concurrent se positionne (Semrush / Ahrefs)
- Trafic estimé d'un site concurrent (estimation, pas une donnée officielle)
- Backlinks d'un concurrent
- Gap analysis : mots-clés du concurrent absents de votre site

### Concepts clés

- **Authority Score / Domain Rating** : métrique inventée par Semrush/Ahrefs — ce n'est pas une donnée Google officielle
- **Keyword gap** : mots-clés que vos concurrents rankent et vous non
- **Backlink gap** : sites qui font des liens vers vos concurrents mais pas vers vous

### Outils analyse concurrentielle

| Outil | Usage | Prix | Remplaçable ? |
|-------|-------|------|---------------|
| Google (recherche manuelle) | Identifier les concurrents | Gratuit | — |
| PageSpeed Insights | Comparer les performances | Gratuit | — |
| Semrush | Mots-clés + trafic concurrents | dès ~120$/mois | ❌ Données propriétaires |
| Ahrefs | Backlinks + mots-clés concurrents | dès 99$/mois | ❌ Données propriétaires |
| Claude | Analyser le HTML d'une page concurrente | Gratuit | ✅ |

---

# CHAPITRE 7 — Les backlinks

## "Est-ce que d'autres sites parlent de moi ?"

Les backlinks sont des liens venant d'autres sites vers le vôtre. Google les interprète comme des votes de confiance.

### Concepts fondamentaux

- **Backlink** : lien entrant depuis un autre site
- **Domaine référent** : site qui fait le lien (un domaine = un vote)
- **Anchor text** : le texte cliquable du lien — doit être descriptif et naturel
- **Dofollow** : lien qui transmet l'autorité SEO (par défaut)
- **Nofollow** : lien qui ne transmet pas l'autorité (attribut rel="nofollow")
- **Lien toxique** : lien depuis un site spam ou pénalisé — peut nuire

### Qualité vs quantité

Un lien depuis un site réputé vaut infiniment plus que 1000 liens depuis des annuaires de mauvaise qualité.

### Stratégies d'acquisition de backlinks

**Naturelles (les meilleures) :**
- Créer du contenu si utile que d'autres sites le citent spontanément
- Infographies, études originales, outils gratuits, guides de référence

**Actives (légitimes) :**
- Digital PR : faire parler de soi dans la presse, blogs spécialisés
- Guest posting : écrire un article sur un autre site avec un lien retour
- Citations locales : annuaires professionnels, Yelp, PagesJaunes...
- Témoignages clients : souvent accompagnés d'un lien retour

**À éviter absolument :**
- Achat de liens en masse · fermes de liens · échanges de liens artificiels

### Liens toxiques et désaveu

Si le site a un profil de liens douteux :
1. Identifier les liens toxiques avec Semrush ou Ahrefs
2. Contacter les sites pour demander la suppression
3. En dernier recours : [outil de désaveu Google](https://search.google.com/search-console/disavow-links)

### Outils backlinks

| Outil | Usage | Prix | Remplaçable ? |
|-------|-------|------|---------------|
| Search Console | Backlinks réels de votre site | Gratuit | ❌ Données officielles |
| Ahrefs | Base backlinks la plus complète | dès 99$/mois | ❌ Données propriétaires |
| Majestic | Trust Flow / Citation Flow | dès 49$/mois | ❌ Données propriétaires |
| Semrush | Audit backlinks + toxicité | dès ~120$/mois | ❌ Données propriétaires |

---

# CHAPITRE 8 — Le SEO local

## "Mon activité est-elle visible localement ?"

Si le site est lié à une activité physique (commerce, artisan, restaurant...), le SEO local a ses propres règles.

### Google Business Profile — la fiche locale (gratuit)

C'est la fiche qui apparaît dans Google Maps et dans le "Local Pack" (les 3 résultats locaux avec carte).

**Installation :**
1. Créer ou revendiquer la fiche sur [business.google.com](https://business.google.com)
2. Vérifier la propriété (courrier postal ou appel)
3. Remplir toutes les informations

**Optimisation :**
- Nom exact de l'entreprise · adresse complète · numéro de téléphone local
- Catégories pertinentes · horaires à jour · photos de qualité
- Description complète avec mots-clés naturels · publications régulières

### Les avis Google

Les avis sont l'un des 3 facteurs principaux du classement local.
- Encourager les clients satisfaits à laisser un avis
- Répondre à tous les avis (positifs et négatifs)
- Ne jamais acheter de faux avis — risque de suspension de la fiche

### NAP — la cohérence des informations

NAP = Name, Address, Phone. Ces informations doivent être **strictement identiques** partout : site web, Google Business Profile, annuaires, réseaux sociaux.

### Citations locales — les annuaires

Chaque mention du business sur un site tiers (avec NAP) renforce la confiance de Google.

Annuaires importants en France : PagesJaunes, Yelp, Tripadvisor, Foursquare, Hotfrog, 118000, Kompass...

### Schema LocalBusiness

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Mon Commerce",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "12 rue de la Paix",
    "addressLocality": "Paris",
    "postalCode": "75001"
  },
  "telephone": "+33 1 23 45 67 89",
  "openingHours": "Mo-Fr 09:00-18:00"
}
```

### Hreflang — site multilingue ou multi-pays

```html
<link rel="alternate" hreflang="fr" href="https://monsite.fr/" />
<link rel="alternate" hreflang="en" href="https://monsite.fr/en/" />
```

### Docs Google à lire

- [Business details](https://developers.google.com/search/docs/appearance/establish-business-details)
- [Local features](https://developers.google.com/search/docs/appearance/local)

---

# CHAPITRE 9 — Le GSO

## "Mon site apparaît-il dans les IA comme ChatGPT ou Perplexity ?"

Le GSO (Generative Search Optimization) est l'optimisation pour apparaître dans les réponses des IA génératives.

### Pourquoi c'est important maintenant

- **Google AI Overview** : réponses générées par IA qui apparaissent avant les résultats classiques
- **ChatGPT, Perplexity, Gemini** : de plus en plus utilisés comme moteurs de recherche
- Les IA citent des sources — être cité = visibilité et trafic

### Les 5 piliers du GSO

**1 — E-E-A-T renforcé**
Les IA ne citent que des sources perçues comme fiables et expertes.

**2 — Contenu structuré en questions/réponses**
Format FAQ, titres sous forme de questions, réponses concises en début de section.

**3 — Données structurées bien en place**
Les IA lisent le JSON-LD. Un contenu balisé correctement est mieux compris.

**4 — Contenu factuel, sourcé, vérifiable**
Citer des sources, des études, des chiffres datés.

**5 — Présence multicanale**
Être mentionné sur Wikipedia, dans la presse, sur des forums spécialisés, sur LinkedIn...

### Outils GSO

| Outil | Usage | Prix | Claude peut remplacer ? |
|-------|-------|------|------------------------|
| Semrush AI Visibility | Tracking visibilité dans les IA | Inclus Semrush | ⚠️ Manuellement avec Claude |
| Profound | Monitoring mentions dans les LLMs | Sur devis | ⚠️ Partiel |
| Otterly.ai | Suivi visibilité IA | Freemium | ⚠️ Partiel |
| Claude + web search | Tester manuellement sa visibilité | Gratuit | ✅ |

### Ce que Claude peut faire pour le GSO

- Analyser une page et la reformater pour être mieux comprise par les IA
- Générer du contenu en format Q/R optimisé pour les AI Overviews
- Vérifier que les données structurées sont correctes et complètes
- Simuler ce qu'une IA répondrait à une question liée au site

---

# CHAPITRE 10 — Le suivi

## "Est-ce que ça marche — et ça s'améliore ?"

Le SEO prend du temps. Les premiers effets visibles arrivent en général entre 3 et 6 mois.

### Les KPIs SEO à surveiller

| KPI | Où le trouver | Ce qu'il indique |
|-----|--------------|-----------------|
| **Impressions** | Search Console → Performances | Combien de fois le site apparaît dans Google |
| **Clics** | Search Console → Performances | Combien de fois on clique sur le site |
| **CTR** | Search Console → Performances | Clics / Impressions → attractivité du snippet |
| **Position moyenne** | Search Console → Performances | Rang moyen dans les résultats |
| **Trafic organique** | GA4 | Visiteurs venant de Google |
| **Pages indexées** | Search Console → Couverture | Santé de l'indexation |
| **Core Web Vitals** | Search Console → Expérience | Santé technique |

### Routine mensuelle (1h/mois minimum)

1. Search Console → **Performances** : évolution sur 3 mois vs année précédente
2. Search Console → **Couverture** : nouvelles erreurs d'indexation ?
3. Search Console → **Core Web Vitals** : dégradations techniques ?
4. GA4 : quelles pages ont le plus de trafic organique ?
5. Identifier les pages entre position 5 et 15 → zone de progression rapide
6. Identifier la page la moins performante → l'améliorer

### Comprendre les baisses de trafic

- **Core update** → vérifier les dates sur [status.search.google.com](https://status.search.google.com)
- **Erreur technique** → pages désindexées, robots.txt, pénalité manuelle
- **Saisonnalité** → comparer à la même période l'année précédente
- **Concurrence** → un site concurrent a renforcé son SEO

### Délais réalistes

| Action | Délai pour voir un effet |
|--------|-------------------------|
| Corriger une erreur technique | Quelques jours à 2 semaines |
| Optimiser une page existante | 2 à 6 semaines |
| Créer une nouvelle page | 1 à 3 mois |
| Construire une autorité de domaine | 6 à 12 mois minimum |
| Passer de la page 2 à la page 1 | 1 à 4 mois (si position 11–20) |

### Outils de suivi (tous gratuits)

- [Google Search Console](https://search.google.com/search-console)
- [Google Analytics 4](https://analytics.google.com)
- [Google Trends](https://trends.google.com)
- [Looker Studio](https://lookerstudio.google.com) — tableaux de bord personnalisés

### Docs Google à lire

- [Debug traffic drops](https://developers.google.com/search/docs/monitor-debug/debugging-search-traffic-drops)
- [Maintaining your site's SEO](https://developers.google.com/search/docs/fundamentals/get-started)
- [Using Search Console and Google Analytics for SEO](https://developers.google.com/search/docs/monitor-debug/google-analytics-search-console)

---

# ANNEXE A — Récapitulatif des outils gratuits

| Outil | Usage | Lien |
|-------|-------|------|
| Google Search Console | Tableau de bord officiel Google | search.google.com/search-console |
| Google Analytics 4 | Analyse du trafic | analytics.google.com |
| PageSpeed Insights | Vitesse et Core Web Vitals | pagespeed.web.dev |
| Rich Results Test | Tester les données structurées | search.google.com/test/rich-results |
| Google Trends | Tendances des recherches | trends.google.com |
| Google Keyword Planner | Volumes de recherche | ads.google.com/keyword-planner |
| Looker Studio | Tableaux de bord personnalisés | lookerstudio.google.com |
| Google Business Profile | Fiche locale | business.google.com |
| Screaming Frog (500 URLs) | Audit technique basique | screamingfrog.co.uk |
| Schema.org | Référence données structurées | schema.org |

---

# ANNEXE B — Ce que Claude peut faire à la place des outils payants

### Claude remplace nativement (sans aucun outil)

- Analyser le HTML d'une page → détecter les problèmes SEO on-page
- Réécrire les balises title et meta descriptions
- Générer des listes de mots-clés et de questions utilisateurs
- Auditer le contenu d'une page par rapport à une intention de recherche
- Vérifier H1/H2/H3, maillage interne, attributs alt
- Analyser un export CSV de Search Console et identifier les opportunités
- Générer un sitemap.xml, robots.txt, données structurées JSON-LD
- Rédiger du contenu optimisé SEO respectant les guidelines E-E-A-T
- Identifier l'intention de recherche derrière une liste de mots-clés
- Reformater un contenu pour le rendre plus lisible par les IA (GSO)
- Simuler AnswerThePublic, AlsoAsked, SurferSEO

### Claude + Claude Code peut faire (avec un peu de technique)

- Crawler un site entier et produire un rapport d'audit → équivalent Screaming Frog
- Extraire et analyser toutes les balises d'un site automatiquement
- Générer des rapports SEO personnalisés à partir d'exports de données
- Script de suivi de position ou d'analyse de SERP

### Ce que Claude ne remplace pas

- Les bases de données de backlinks de Semrush / Ahrefs (infrastructure unique)
- Le suivi de position automatisé dans le temps (infrastructure persistante)
- Les données de trafic estimé des concurrents (panel propriétaire de 200M d'internautes)
- Les données temps réel de Google Trends et Google Analytics

---

# ANNEXE C — Cartographie des outils payants SEO / GSO

### Ce que ces outils vendent vraiment

- **Screaming Frog, Sitebulb** — robots crawleurs qui lisent le HTML public. Pas de licence Google. Ce qu'ils vendent : de l'automatisation et de la lisibilité.
- **Semrush, Ahrefs, Majestic** — bases de données propriétaires construites sur leurs propres infrastructures. Leurs métriques (Authority Score, difficulté de mot-clé, trafic estimé) sont des calculs propriétaires — pas des données Google. Ce sont des estimations.
- **SurferSEO, Yoast, AnswerThePublic** — analysent du contenu public et le reformatent en recommandations. Remplaçables par Claude.
- **HubSpot** — n'est pas un outil SEO. C'est une plateforme CRM + inbound marketing qui inclut des fonctions SEO basiques.

### Tableau complet par catégorie

**Audit technique**

| Outil | Ce qu'il fait | Prix | Claude ? |
|-------|--------------|------|---------|
| Screaming Frog | Crawler local · 300+ erreurs | £199/an | ✅ Claude Code |
| Sitebulb | Idem + visualisations | ~$19/mois | ✅ Claude Code |

**Intelligence concurrentielle**

| Outil | Ce qu'il fait | Prix | Claude ? |
|-------|--------------|------|---------|
| Semrush | Suite complète · 25Mds mots-clés | dès ~120$/mois | ❌ |
| Ahrefs | Référence backlinks · analyse concurrents | dès 99$/mois | ❌ |
| Majestic | Trust Flow / Citation Flow | dès 49$/mois | ❌ |
| Ubersuggest | Suggestions mots-clés | dès 12$/mois | ⚠️ Partiel |

**Optimisation de contenu**

| Outil | Ce qu'il fait | Prix | Claude ? |
|-------|--------------|------|---------|
| SurferSEO | Brief éditorial · recommandations | dès 99$/mois | ✅ |
| Yoast SEO | Plugin WordPress on-page | Gratuit/99€ | ✅ |
| AnswerThePublic | Questions utilisateurs | Freemium | ✅ |
| AlsoAsked | "Autres questions posées" Google | Freemium | ✅ |

**Suivi de position**

| Outil | Ce qu'il fait | Prix | Claude ? |
|-------|--------------|------|---------|
| Monitorank | Suivi positions · alertes | dès ~30€/mois | ❌ |
| SE Ranking | Positions + audit | dès 39$/mois | ❌ |
| ProRankTracker | Suivi multi-moteurs | dès 13$/mois | ❌ |

**GSO — Visibilité IA**

| Outil | Ce qu'il fait | Prix | Claude ? |
|-------|--------------|------|---------|
| Semrush AI Visibility | Visibilité ChatGPT, AI Overview | Inclus Semrush | ⚠️ |
| Profound | Tracking mentions LLMs | Sur devis | ⚠️ |
| Otterly.ai | Monitoring visibilité IA | Freemium | ⚠️ |

---

# ANNEXE D — La règle d'or

> **Les outils changent. Les concepts, eux, restent.**
>
> Comprendre *pourquoi* une technique existe vaut mieux que de suivre une checklist aveuglément. Screaming Frog ne fait rien que Google ne fasse déjà — il l'automatise. Semrush ne sait rien que vous ne pourriez déduire — il le calcule plus vite. Claude ne connaît pas de secrets SEO cachés — il applique les mêmes principes, mais à grande vitesse.
>
> La vraie compétence SEO, c'est comprendre ce que Google cherche à accomplir : répondre au mieux à l'intention d'un être humain. Tout le reste est de la mécanique.

---

*Document créé avec Claude — formation SEO/GSO pour néophites*
*Version 4 — restructuration complète par chapitres et questions humaines*
*Chapitres : 1-Comprendre Google · 2-Visibilité · 3-Technique · 4-Contenu · 5-Apparence · 6-Concurrence · 7-Backlinks · 8-Local · 9-GSO · 10-Suivi*
*Annexes : Outils gratuits · Rôle de Claude · Outils payants · Règle d'or*
