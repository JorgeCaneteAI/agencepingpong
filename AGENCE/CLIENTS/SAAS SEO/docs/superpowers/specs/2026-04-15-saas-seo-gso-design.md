# SaaS SEO/GSO — Spec de design

> **Nom de travail :** "Mon Site Sur Google" (domaine final à définir — piste : jeveuxmonsitesurgoogle.fr ou sous agencepingpong.fr)  
> **Date :** 2026-04-15  
> **Auteur :** Jorge Canete + Claude  
> **Statut :** V1 MVP local

---

## 1. Vision produit

### Le problème

Les outils SEO existants (BotSEO, SEMrush, Ahrefs) s'adressent à des professionnels qui maîtrisent déjà le vocabulaire et les concepts. Un propriétaire de site sur Wix ou WordPress sans compétences techniques est perdu dès la première page : "analyse de niche", "structure sémantique", "local tracker" sont des termes qui ne lui parlent pas.

### La solution

Un **coach SEO & GSO pas-à-pas** qui prend l'utilisateur par la main. Il entre son URL, l'outil analyse tout, et lui dit exactement quoi faire — en français simple, avec des explications, dans un ordre logique de progression.

### Positionnement

"Le SEO pour les nuls" — mais avec des outils d'une efficacité redoutable. L'utilisateur apprend en faisant, pas en lisant de la documentation.

### Stratégie de déploiement

1. **Phase 1 — Outil personnel local** : développement et tests sur 4 sites propres
2. **Phase 2 — Ouverture** : déploiement en ligne, accès pour amis/contacts
3. **Phase 3 — SaaS** : abonnements payants, multi-utilisateurs

---

## 2. Sites de test (Phase 1)

| Site | Secteur | Type |
|------|---------|------|
| `villaplaisance.fr` | Hébergement / tourisme | B2C |
| `yelloevent.fr` | Traiteur / mariage | B2C |
| `canete.fr` | Conciergerie Airbnb | B2C |
| `agencepingpong.fr` | Agence web | B2B |

4 secteurs différents pour valider que l'outil fonctionne universellement.

---

## 3. Expérience utilisateur

### Système de progression à 3 dimensions

**Dimension 1 — Les niveaux (structure)**

5 niveaux, chacun débloqué quand le précédent atteint un score suffisant :

| Niveau | Titre utilisateur | Contenu | Chapitres formation |
|--------|-------------------|---------|---------------------|
| 1 | Les fondations | Crawl, index, HTTPS, vitesse, mobile, sitemap, robots.txt, balises title/meta | Ch.1 + Ch.2 + Ch.3 |
| 2 | Les mots-clés | Découverte de niche, suggestions mots-clés, volumes, concurrents | Ch.4 (recherche) + Ch.6 |
| 3 | Le contenu | Analyse sémantique, rédaction optimisée, structure H1-H6, E-E-A-T, rich results | Ch.4 (rédaction) + Ch.5 |
| 4 | L'autorité | Backlinks, SEO local, Google Business Profile, citations, NAP | Ch.7 + Ch.8 |
| 5 | Présence IA (GSO) | Visibilité ChatGPT/Gemini/Perplexity, données structurées avancées, contenu conversationnel | Ch.9 |

Transversal : le suivi (Ch.10) est intégré au dashboard.

**Dimension 2 — Le score (motivation)**

- Score global 0-100 visible en permanence
- Chaque niveau contribue à hauteur de 20 points max
- Indicateurs visuels : rouge (0-39) / orange (40-69) / vert (70-100)
- Le score monte quand l'utilisateur complète des actions

**Dimension 3 — Le plan d'action (concret)**

- Généré automatiquement après l'analyse initiale
- Tâches triées par **impact** (haut/moyen/bas) × **difficulté** (facile/moyen/dur)
- Quick wins en premier (impact fort + facile)
- Chaque tâche contient :
  - Ce qu'il faut faire (instruction pas-à-pas)
  - Pourquoi c'est important (explication pédagogique)
  - Comment vérifier que c'est fait

### Langage de l'interface

Tout est traduit du jargon SEO vers le français courant :

| Terme technique | Ce que l'utilisateur voit |
|-----------------|--------------------------|
| Structure sémantique | "Est-ce que ton site est bien organisé ?" |
| Backlinks | "Les sites qui parlent de toi" |
| Core Web Vitals | "Ton site est-il rapide ?" |
| Meta description | "Le résumé qui apparaît dans Google" |
| Données structurées | "Aider Google à mieux comprendre ton site" |
| E-E-A-T | "Google te fait-il confiance ?" |
| Crawl | "Google visite ton site" |
| Canonical | "Quelle est la vraie adresse de ta page ?" |

### Contenu pédagogique

La base de connaissances de l'app est alimentée par le document `formation-seo-google-search-central.md`. Chaque fonctionnalité est accompagnée de :

1. **Mini-leçon contextuelle** — courte explication du "pourquoi" avant chaque action
2. **Instructions pas-à-pas** — adaptées au CMS de l'utilisateur quand possible
3. **Coach IA** — chat intégré pour poser des questions, obtenir des suggestions, faire rédiger du contenu

---

## 4. Architecture technique

### Stack

| Couche | Choix | Raison |
|--------|-------|--------|
| Framework | Next.js 14 (App Router) | Full-stack, SSR, API routes intégrées |
| UI | Tailwind CSS | Rapide, léger, utilitaire |
| Base de données | SQLite (via Prisma) | Suffisant en local, migratable vers PostgreSQL |
| ORM | Prisma | Typage, migrations, multi-DB |
| IA | API Claude (Anthropic) | Coach temps réel, rédaction, analyse GSO |
| Hébergement V1 | Local (`localhost:3000`) | Dev et tests uniquement |

### Les 3 moteurs

**Moteur 1 — Crawler maison (coût : zéro)**

Scanne le site cible et extrait :
- Balises title, meta description, canonical
- Structure des titres (H1-H6)
- Images (src, alt, taille)
- Liens internes et externes (+ détection liens cassés)
- Sitemap.xml et robots.txt
- Certificat SSL / HTTPS
- Données structurées (JSON-LD)
- Temps de réponse serveur

Technologies : Node.js natif (fetch) + cheerio (parsing HTML).

**Moteur 2 — APIs externes (budget : ~15€/mois)**

| API | Données | Coût |
|-----|---------|------|
| Google PageSpeed Insights | Core Web Vitals, score performance | Gratuit |
| Google Search Console | Positions, clics, impressions, pages indexées | Gratuit (connexion OAuth) |
| DataForSEO (V2+) | Volumes mots-clés, SERP, données concurrents | ~15€/mois (pay-per-use) |

Note : en V1, on utilise uniquement le crawler maison + PageSpeed API. DataForSEO et Google Search Console seront intégrés en V2 (nécessitent budget récurrent et OAuth).

**Moteur 3 — Coach IA (API Claude)**

- Contexte injecté : résultats d'audit du projet + doc de formation SEO/GSO
- Fonctions : explications pédagogiques, rédaction de contenu optimisé, analyse GSO, réponses aux questions
- Modèle : Claude Haiku pour les réponses rapides, Claude Sonnet pour la rédaction

### Modèle de données

```
User (V2+, ignoré en V1)
│
Project
├── url: string (URL du site)
├── name: string
├── score: number (0-100)
├── currentLevel: number (1-5)
├── createdAt: datetime
├── updatedAt: datetime
│
├── Audit (snapshot d'analyse)
│   ├── date: datetime
│   ├── scoreBreakdown: json (score par niveau)
│   ├── technicalChecks: json (résultats crawl)
│   ├── contentAnalysis: json (analyse contenu)
│   └── gsoAnalysis: json (visibilité IA)
│
├── Task (plan d'action)
│   ├── title: string
│   ├── description: text
│   ├── level: number (1-5)
│   ├── impact: enum (high/medium/low)
│   ├── difficulty: enum (easy/medium/hard)
│   ├── status: enum (pending/done/skipped)
│   └── completedAt: datetime?
│
├── Keyword (mots-clés suivis)
│   ├── term: string
│   ├── volume: number?
│   ├── position: number?
│   └── lastChecked: datetime
│
└── Competitor (sites concurrents)
    ├── url: string
    ├── name: string
    └── lastAnalyzed: datetime
```

---

## 5. Écrans

### 5.1 Dashboard (accueil)

- Liste des projets (4 sites en V1)
- Pour chaque projet : score global, niveau actuel, date dernière analyse, nombre d'actions en attente
- Bouton "Analyser" pour relancer un scan
- Bouton "Ajouter un site"

### 5.2 Ajout de projet

- Champ URL + nom du site
- Lancement automatique du premier crawl
- Redirection vers la vue projet après analyse

### 5.3 Vue projet (écran principal)

Sidebar gauche avec navigation :
- Dashboard projet (score + résumé)
- Niveau 1 à 5 (avec indicateur de progression)
- Plan d'action
- Suivi & historique (V2)
- Coach IA

Zone principale : contenu de la section sélectionnée.

### 5.4 Vue niveau (détail)

Liste des vérifications du niveau avec statut visuel :
- ✅ Validé
- 🔶 À améliorer (avec détails)
- ❌ Problème critique

Clic sur un check → détail avec :
- Résultat de l'analyse
- Explication pédagogique (pourquoi c'est important)
- Instructions pour corriger
- Bouton "Marquer comme fait"

### 5.5 Plan d'action

Tâches groupées par priorité :
1. 🔥 Impact fort + Facile (quick wins)
2. 💪 Impact fort + Moyen
3. 📌 Impact moyen + Facile
4. Reste

Chaque tâche : titre, description, guide pas-à-pas, checkbox.

### 5.6 Coach IA

Chat en temps réel avec contexte du projet. L'utilisateur pose des questions, le coach répond en s'appuyant sur :
- Les résultats d'audit du projet
- Le document de formation SEO/GSO
- Les bonnes pratiques Google

Fonctions spéciales :
- "Rédige-moi une meta description pour [page]"
- "Est-ce que les IA recommandent mon site pour [requête] ?"
- "Explique-moi [concept SEO]"

---

## 6. Périmètre V1 (MVP local)

### Inclus

- 4 projets pré-configurés (les sites de test)
- Crawler maison complet (audit technique)
- Intégration PageSpeed API (Core Web Vitals)
- Score global + score par niveau
- 5 niveaux avec checks détaillés
- Plan d'action auto-généré avec tri impact/difficulté
- Coach IA (API Claude)
- Base de connaissances intégrée (doc formation)
- Interface responsive (Tailwind)
- Base SQLite locale

### Exclu (V2+)

- Authentification / multi-utilisateurs
- Système de paiement / abonnement
- Déploiement en ligne
- Intégration Google Search Console API (OAuth)
- Suivi de positions automatisé dans le temps
- Historique long terme / graphiques d'évolution
- Intégration DataForSEO (mots-clés, volumes)
- Multi-langue

---

## 7. Base de connaissances

Le fichier `formation-seo-google-search-central.md` sert de source unique pour :

- Le contenu pédagogique affiché dans l'interface (mini-leçons, explications)
- Le contexte injecté au coach IA pour des réponses pertinentes
- La logique de scoring (quels critères vérifier, quels seuils)
- Les instructions pas-à-pas des tâches du plan d'action

Ce fichier est structuré en 10 chapitres + 4 annexes qui couvrent l'intégralité du parcours SEO/GSO, des fondamentaux à la présence IA.
