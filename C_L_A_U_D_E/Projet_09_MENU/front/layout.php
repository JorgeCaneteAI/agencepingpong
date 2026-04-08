<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <meta name="api-base-url" content="<?= BASE_URL ?>/api">
    <title><?= htmlspecialchars($pageTitle) ?> — MealCoach</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css?v=<?= filemtime(BASE_PATH . '/assets/css/app.css') ?>">
</head>
<body class="front">

    <main class="page-content">
        <?= $content ?>
    </main>

    <!-- SOS FAB -->
    <button type="button" class="sos-fab" onclick="openSOS()" aria-label="SOS Craquage">
        <span class="sos-icon">🆘</span>
        SOS
    </button>

    <!-- Bottom Nav -->
    <nav class="bottom-nav" role="navigation" aria-label="Navigation principale">
        <a href="<?= BASE_URL ?>/"
           class="nav-item<?= $activeNav === 'dashboard' ? ' active' : '' ?>"
           aria-label="Accueil">
            <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
            <span class="nav-label">Accueil</span>
        </a>
        <a href="<?= BASE_URL ?>/semaine"
           class="nav-item<?= $activeNav === 'semaine' ? ' active' : '' ?>"
           aria-label="Semaine">
            <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/><line x1="8" y1="18" x2="8" y2="18.01"/><line x1="12" y1="18" x2="12" y2="18.01"/></svg></span>
            <span class="nav-label">Semaine</span>
        </a>
        <a href="<?= BASE_URL ?>/compositeur"
           class="nav-item<?= $activeNav === 'compositeur' ? ' active' : '' ?>"
           aria-label="Composer">
            <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v4m0 12v4m-7.07-15.07l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg></span>
            <span class="nav-label">Composer</span>
        </a>
        <a href="<?= BASE_URL ?>/courses"
           class="nav-item<?= $activeNav === 'courses' ? ' active' : '' ?>"
           aria-label="Courses">
            <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
            <span class="nav-label">Courses</span>
        </a>
        <button type="button"
                class="nav-item<?= in_array($activeNav, ['suivi', 'tableau', 'stock', 'batch']) ? ' active' : '' ?>"
                onclick="togglePlusMenu(event)"
                aria-label="Plus"
                aria-expanded="false">
            <span class="nav-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/><circle cx="5" cy="12" r="1.5"/></svg></span>
            <span class="nav-label">Plus</span>
        </button>
    </nav>

    <!-- Plus Menu (bottom sheet) -->
    <div id="plusMenuOverlay" class="plus-menu-overlay" onclick="togglePlusMenu(event)"></div>
    <div id="plusMenu" class="plus-menu" role="dialog" aria-label="Menu supplementaire" style="display:none;">
        <div class="plus-menu-label">Navigation</div>
        <nav class="plus-menu-content">
            <a href="<?= BASE_URL ?>/suivi"
               class="plus-menu-item<?= $activeNav === 'suivi' ? ' active' : '' ?>">
                <span style="font-size:1.3rem;">📊</span> Suivi detaille
            </a>
            <a href="<?= BASE_URL ?>/tableau"
               class="plus-menu-item<?= $activeNav === 'tableau' ? ' active' : '' ?>">
                <span style="font-size:1.3rem;">📋</span> Tableau de reference
            </a>
            <a href="<?= BASE_URL ?>/stock"
               class="plus-menu-item<?= $activeNav === 'stock' ? ' active' : '' ?>">
                <span style="font-size:1.3rem;">🥫</span> Stock / Garde-manger
            </a>
            <hr class="plus-menu-separator">
            <a href="<?= BASE_URL ?>/admin" class="plus-menu-item plus-menu-item--secondary">
                <span style="font-size:1.3rem;">⚙️</span> Back office
            </a>
            <a href="<?= BASE_URL ?>/logout" class="plus-menu-item plus-menu-item--danger">
                <span style="font-size:1.3rem;">🚪</span> Deconnexion
            </a>
        </nav>
    </div>

    <!-- SOS Overlay -->
    <div id="sosOverlay" class="sos-overlay">
        <div class="sos-sheet" style="position:relative;">
            <button type="button" class="sos-close" onclick="closeSOS()">&times;</button>

            <!-- Step: Start -->
            <div class="sos-step active" data-step="start">
                <div class="sos-big-emoji">🫂</div>
                <h2>Stop. Respire.</h2>
                <p>C'est normal. L'envie va passer. Tu as le choix.</p>
                <div class="sos-actions">
                    <button class="btn btn-accent btn-full" onclick="startGrounding()">
                        🧠 Gerer l'emotion
                    </button>
                    <button class="btn btn-ghost btn-full" onclick="showSOSStep('fringale')">
                        🍫 Alternative anti-fringale
                    </button>
                </div>
            </div>

            <!-- Step: Grounding 5 -->
            <div class="sos-step" data-step="g5">
                <div class="sos-big-emoji">👀</div>
                <h3>5 choses que tu vois</h3>
                <p>Regarde autour de toi. Nomme mentalement 5 choses que tu vois en ce moment.</p>
                <button class="btn btn-accent btn-full" onclick="nextGroundingStep('g5')">Suivant →</button>
            </div>

            <!-- Step: Grounding 4 -->
            <div class="sos-step" data-step="g4">
                <div class="sos-big-emoji">✋</div>
                <h3>4 choses que tu touches</h3>
                <p>Touche 4 objets pres de toi. Sens leur texture sous tes doigts.</p>
                <button class="btn btn-accent btn-full" onclick="nextGroundingStep('g4')">Suivant →</button>
            </div>

            <!-- Step: Grounding 3 -->
            <div class="sos-step" data-step="g3">
                <div class="sos-big-emoji">👂</div>
                <h3>3 sons que tu entends</h3>
                <p>Ferme les yeux un instant. Ecoute 3 sons differents autour de toi.</p>
                <button class="btn btn-accent btn-full" onclick="nextGroundingStep('g3')">Suivant →</button>
            </div>

            <!-- Step: Grounding 2 -->
            <div class="sos-step" data-step="g2">
                <div class="sos-big-emoji">👃</div>
                <h3>2 odeurs</h3>
                <p>Sens 2 odeurs. Meme subtiles : l'air, ta peau, un vetement.</p>
                <button class="btn btn-accent btn-full" onclick="nextGroundingStep('g2')">Suivant →</button>
            </div>

            <!-- Step: Grounding 1 -->
            <div class="sos-step" data-step="g1">
                <div class="sos-big-emoji">👅</div>
                <h3>1 gout</h3>
                <p>Goute quelque chose. Un verre d'eau, ta salive. Concentre-toi sur la sensation.</p>
                <button class="btn btn-accent btn-full" onclick="showSOSStep('bilan')">Suivant →</button>
            </div>

            <!-- Step: Anti-fringale alternatives -->
            <div class="sos-step" data-step="fringale">
                <div class="sos-big-emoji">🍫</div>
                <h3>Alternatives anti-fringale</h3>
                <p>Choisis une option qui va calmer l'envie :</p>
                <div class="fringale-card"><span class="fringale-emoji">🍫</span><span class="fringale-text">1 carre de chocolat noir 85%</span></div>
                <div class="fringale-card"><span class="fringale-emoji">🍌</span><span class="fringale-text">1 banane (tryptophane)</span></div>
                <div class="fringale-card"><span class="fringale-emoji">🥜</span><span class="fringale-text">20g d'amandes</span></div>
                <div class="fringale-card"><span class="fringale-emoji">🥛</span><span class="fringale-text">1 verre de lait tiede</span></div>
                <div class="fringale-card"><span class="fringale-emoji">🫖</span><span class="fringale-text">Tisane + 1 datte</span></div>
                <div class="spacer"></div>
                <button class="btn btn-accent btn-full" onclick="showSOSStep('bilan')">Comment tu te sens ?</button>
            </div>

            <!-- Step: Bilan -->
            <div class="sos-step" data-step="bilan">
                <div class="sos-big-emoji">💭</div>
                <h3>Comment tu te sens ?</h3>
                <p>Pas de jugement. Juste un constat.</p>
                <div class="sos-actions">
                    <button class="btn btn-success btn-full" onclick="sosResult('resiste')">
                        💪 J'ai resiste
                    </button>
                    <button class="btn btn-ghost btn-full" onclick="sosResult('craque')">
                        J'ai craque
                    </button>
                </div>
            </div>

            <!-- Step: Bravo -->
            <div class="sos-step" data-step="bravo">
                <div class="sos-big-emoji">🎉</div>
                <h3>Bravo !</h3>
                <p>Tu as resiste. Chaque victoire compte. Tu es plus fort que tu ne le penses.</p>
                <button class="btn btn-accent btn-full" onclick="closeSOS()">Fermer</button>
            </div>

            <!-- Step: Craque OK -->
            <div class="sos-step" data-step="craque-ok">
                <div class="sos-big-emoji">🤗</div>
                <h3>C'est pas grave.</h3>
                <p>Un craquage ne definit pas ta journee. Demain est un nouveau jour. Tu fais de ton mieux.</p>
                <button class="btn btn-accent btn-full" onclick="closeSOS()">Fermer</button>
            </div>

        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= filemtime(BASE_PATH . '/assets/js/app.js') ?>"></script>
</body>
</html>
