<section id="services" class="section services">
    <div class="o-container">

        <h2 class="section__title" data-animate="split-reveal">Nos coups.</h2>
        <div class="section__marquee" aria-hidden="true">
            <div class="section__marquee-track">
                <span>Web · Print · Identité visuelle</span>
                <span>·</span>
                <span>30 services. 3 catégories. 1 échange.</span>
                <span>·</span>
                <span>Coup droit. Revers. Smash.</span>
                <span>·</span>
                <span>Web · Print · Identité visuelle</span>
                <span>·</span>
                <span>30 services. 3 catégories. 1 échange.</span>
                <span>·</span>
                <span>Coup droit. Revers. Smash.</span>
                <span>·</span>
                <span>Web · Print · Identité visuelle</span>
                <span>·</span>
                <span>30 services. 3 catégories. 1 échange.</span>
                <span>·</span>
                <span>Coup droit. Revers. Smash.</span>
                <span>·</span>
            </div>
        </div>

        <!-- Mac OS 8 Chooser Window -->
        <div class="mac-win chooser">
            <div class="mac-win__titlebar">
                <span class="mac-win__btn mac-win__btn--close"></span>
                <span class="mac-win__btn mac-win__btn--minimize"></span>
                <span class="mac-win__btn mac-win__btn--zoom"></span>
                <span class="mac-win__title">Nos Coups</span>
                <div class="mac-win__stripes"></div>
            </div>

            <div class="chooser__body">

                <!-- Col 1 : Category Icons -->
                <div class="chooser__icons">
                    <button class="chooser__icon-btn is-active" data-category="web" aria-label="Web">
                        <div class="chooser__icon-img" aria-hidden="true">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="22" cy="22" r="16" fill="var(--color-turquoise)" stroke="var(--color-navy)" stroke-width="2"/>
                                <ellipse cx="22" cy="22" rx="7" ry="16" fill="none" stroke="var(--color-navy)" stroke-width="1.5"/>
                                <line x1="6" y1="22" x2="38" y2="22" stroke="var(--color-navy)" stroke-width="1.5"/>
                                <line x1="6" y1="14" x2="38" y2="14" stroke="var(--color-navy)" stroke-width="0.8" opacity="0.4"/>
                                <line x1="6" y1="30" x2="38" y2="30" stroke="var(--color-navy)" stroke-width="0.8" opacity="0.4"/>
                                <g transform="translate(28,28)">
                                    <rect x="2" y="0" width="4" height="10" fill="#fff" stroke="var(--color-navy)" stroke-width="1"/>
                                    <rect x="0" y="6" width="3" height="6" fill="#fff" stroke="var(--color-navy)" stroke-width="1"/>
                                    <rect x="6" y="4" width="3" height="6" fill="#fff" stroke="var(--color-navy)" stroke-width="1"/>
                                    <rect x="9" y="6" width="3" height="5" fill="#fff" stroke="var(--color-navy)" stroke-width="1"/>
                                </g>
                            </svg>
                        </div>
                        <span class="chooser__icon-label">Web</span>
                    </button>

                    <button class="chooser__icon-btn" data-category="print" aria-label="Print">
                        <div class="chooser__icon-img" aria-hidden="true">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="6" y="18" width="36" height="18" fill="#d4d4d4" stroke="var(--color-navy)" stroke-width="2"/>
                                <rect x="12" y="6" width="24" height="14" fill="#fff" stroke="var(--color-navy)" stroke-width="2"/>
                                <rect x="10" y="34" width="28" height="10" fill="#fff" stroke="var(--color-navy)" stroke-width="2"/>
                                <line x1="14" y1="38" x2="34" y2="38" stroke="#bbb" stroke-width="1.5"/>
                                <line x1="14" y1="41" x2="28" y2="41" stroke="#bbb" stroke-width="1.5"/>
                                <rect x="34" y="24" width="4" height="4" fill="var(--color-turquoise)" stroke="var(--color-navy)" stroke-width="1"/>
                                <rect x="8" y="28" width="6" height="3" fill="#999" stroke="var(--color-navy)" stroke-width="0.5"/>
                            </svg>
                        </div>
                        <span class="chooser__icon-label">Print</span>
                    </button>

                    <button class="chooser__icon-btn" data-category="identity" aria-label="Identité visuelle">
                        <div class="chooser__icon-img" aria-hidden="true">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g transform="translate(4,4)">
                                    <path d="M10,6 L16,6 L19,9 L19,14 L16,17 L14,17 L14,34 L12,34 L12,17 L10,17 L7,14 L7,9 Z" fill="#c0c0c0" stroke="var(--color-navy)" stroke-width="1.5"/>
                                    <rect x="26" y="4" width="6" height="20" rx="1" fill="var(--color-turquoise)" stroke="var(--color-navy)" stroke-width="1.5"/>
                                    <rect x="25" y="24" width="8" height="6" fill="#c0c0c0" stroke="var(--color-navy)" stroke-width="1.5"/>
                                    <rect x="26" y="30" width="6" height="12" fill="var(--color-coral)" stroke="var(--color-navy)" stroke-width="1.5"/>
                                    <rect x="28" y="42" width="2" height="3" fill="var(--color-coral)"/>
                                </g>
                            </svg>
                        </div>
                        <span class="chooser__icon-label">Identité</span>
                    </button>
                </div>

                <!-- Col 2 : Service List -->
                <div class="chooser__list">
                    <div class="chooser__list-header">Sélectionner un service :</div>
                    <div class="chooser__list-scroll" id="chooser-service-list"></div>
                </div>

                <!-- Col 3 : QuickTime Preview -->
                <div class="chooser__preview">
                    <div class="qt-player">
                        <div class="qt-player__titlebar">
                            <span class="mac-win__btn mac-win__btn--close"></span>
                            <span class="qt-player__title" id="qt-title">Site vitrine</span>
                        </div>
                        <div class="qt-player__screen" id="qt-screen">
                            <!-- Preview SVG goes here -->
                        </div>
                        <div class="qt-player__controls">
                            <div class="qt-player__progress">
                                <div class="qt-player__progress-fill"></div>
                            </div>
                            <div class="qt-player__buttons">
                                <button class="qt-player__btn qt-player__btn--prev" aria-label="Précédent">◀◀</button>
                                <button class="qt-player__btn qt-player__btn--play" aria-label="Lecture">▶</button>
                                <button class="qt-player__btn qt-player__btn--next" aria-label="Suivant">▶▶</button>
                                <div class="qt-player__volume">
                                    <span>🔈</span>
                                    <div class="qt-player__volume-bar">
                                        <div class="qt-player__volume-fill"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="chooser__preview-desc" id="qt-desc">
                        Ton coup droit de base. Un site clair, rapide, qui tape où il faut.
                    </p>
                </div>

            </div>

            <!-- Footer -->
            <div class="chooser__footer">
                <div class="chooser__footer-info">
                    <span class="chooser__footer-dot chooser__footer-dot--green"></span>
                    <span class="chooser__footer-dot chooser__footer-dot--yellow"></span>
                    Agence Ping Pong v1.0
                </div>
                <div>3 coups · 30 services</div>
            </div>
        </div>

    </div>
</section>

<!-- 30 Preview SVG templates — one per service -->

<!-- ===== WEB ===== -->
<template id="tpl-web-0"><!-- Site vitrine -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="15" y="10" width="250" height="190" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="15" y="10" width="250" height="22" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="20" y="15" width="8" height="8" fill="#E63946"/><rect x="31" y="15" width="8" height="8" fill="#F5C542"/><rect x="42" y="15" width="8" height="8" fill="#2ABFBF"/>
        <rect x="56" y="16" width="200" height="8" fill="#fff" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="30" y="44" width="120" height="10" fill="#1b2a4a"/>
        <rect x="30" y="62" width="220" height="3" fill="#ddd"/><rect x="30" y="69" width="180" height="3" fill="#ddd"/><rect x="30" y="76" width="200" height="3" fill="#ddd"/>
        <rect x="30" y="92" width="100" height="70" fill="#2ABFBF" opacity="0.15" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="142" y="92" width="100" height="70" fill="#E63946" opacity="0.15" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="30" y="174" width="60" height="14" fill="#1b2a4a" rx="2"/>
        <text x="60" y="184" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">Contact</text>
    </svg>
</template>

<template id="tpl-web-1"><!-- Site e-commerce -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="15" y="10" width="250" height="190" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="15" y="10" width="250" height="22" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="20" y="15" width="8" height="8" fill="#E63946"/><rect x="31" y="15" width="8" height="8" fill="#F5C542"/><rect x="42" y="15" width="8" height="8" fill="#2ABFBF"/>
        <!-- Products grid -->
        <rect x="25" y="42" width="70" height="70" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="105" y="42" width="70" height="70" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="185" y="42" width="70" height="70" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Price tags -->
        <rect x="25" y="116" width="70" height="8" fill="#1b2a4a"/>
        <rect x="105" y="116" width="70" height="8" fill="#1b2a4a"/>
        <rect x="185" y="116" width="70" height="8" fill="#1b2a4a"/>
        <text x="60" y="123" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff">29,90€</text>
        <text x="140" y="123" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff">45,00€</text>
        <text x="220" y="123" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff">18,50€</text>
        <!-- Cart icon -->
        <rect x="200" y="140" width="55" height="20" fill="#E63946" rx="2"/>
        <text x="227" y="153" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">Panier</text>
        <!-- Product placeholders -->
        <circle cx="60" cy="72" r="18" fill="#2ABFBF" opacity="0.3"/>
        <rect cx="140" y="55" x="120" width="40" height="40" fill="#F5C542" opacity="0.3"/>
        <polygon points="220,55 240,90 200,90" fill="#E63946" opacity="0.3"/>
    </svg>
</template>

<template id="tpl-web-2"><!-- Landing page -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="15" y="10" width="250" height="190" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Big hero block -->
        <rect x="15" y="10" width="250" height="90" fill="#1b2a4a"/>
        <text x="140" y="50" text-anchor="middle" font-family="monospace" font-size="14" fill="#fff" font-weight="bold">HEADLINE</text>
        <rect x="100" y="65" width="80" height="16" fill="#E63946" rx="2"/>
        <text x="140" y="76" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">CTA</text>
        <!-- Content -->
        <rect x="30" y="112" width="220" height="3" fill="#ddd"/><rect x="50" y="119" width="180" height="3" fill="#ddd"/>
        <!-- 3 features -->
        <circle cx="60" cy="150" r="14" fill="#2ABFBF" opacity="0.2" stroke="#1b2a4a" stroke-width="1"/>
        <circle cx="140" cy="150" r="14" fill="#F5C542" opacity="0.2" stroke="#1b2a4a" stroke-width="1"/>
        <circle cx="220" cy="150" r="14" fill="#E63946" opacity="0.2" stroke="#1b2a4a" stroke-width="1"/>
        <text x="60" y="154" text-anchor="middle" font-family="monospace" font-size="10" fill="#1b2a4a">✓</text>
        <text x="140" y="154" text-anchor="middle" font-family="monospace" font-size="10" fill="#1b2a4a">✓</text>
        <text x="220" y="154" text-anchor="middle" font-family="monospace" font-size="10" fill="#1b2a4a">✓</text>
        <rect x="30" y="176" width="60" height="4" fill="#ddd"/><rect x="110" y="176" width="60" height="4" fill="#ddd"/><rect x="190" y="176" width="60" height="4" fill="#ddd"/>
    </svg>
</template>

<template id="tpl-web-3"><!-- Refonte de site -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Old site -->
        <rect x="15" y="20" width="110" height="80" fill="#ddd" stroke="#999" stroke-width="2" opacity="0.6"/>
        <rect x="15" y="20" width="110" height="14" fill="#bbb"/>
        <rect x="25" y="42" width="90" height="3" fill="#999"/><rect x="25" y="49" width="70" height="3" fill="#999"/>
        <text x="70" y="80" text-anchor="middle" font-family="monospace" font-size="8" fill="#999">OLD</text>
        <!-- Arrow -->
        <text x="140" y="70" text-anchor="middle" font-family="monospace" font-size="24" fill="#E63946">→</text>
        <!-- New site -->
        <rect x="155" y="15" width="115" height="90" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="155" y="15" width="115" height="16" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="160" y="19" width="6" height="6" fill="#E63946"/><rect x="168" y="19" width="6" height="6" fill="#F5C542"/><rect x="176" y="19" width="6" height="6" fill="#2ABFBF"/>
        <rect x="165" y="40" width="95" height="6" fill="#1b2a4a"/>
        <rect x="165" y="52" width="95" height="3" fill="#ddd"/>
        <rect x="165" y="58" width="80" height="3" fill="#ddd"/>
        <rect x="165" y="72" width="40" height="25" fill="#2ABFBF" opacity="0.2" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="212" y="72" width="48" height="25" fill="#E63946" opacity="0.2" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Sparkles -->
        <text x="270" y="20" font-family="monospace" font-size="12" fill="#F5C542">✦</text>
        <!-- Before/After labels -->
        <rect x="40" y="120" width="60" height="14" fill="#999" rx="2"/>
        <text x="70" y="130" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">Avant</text>
        <rect x="180" y="120" width="60" height="14" fill="#1b2a4a" rx="2"/>
        <text x="210" y="130" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">Après</text>
    </svg>
</template>

<template id="tpl-web-4"><!-- Référencement SEO -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Search bar -->
        <rect x="40" y="15" width="200" height="24" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <text x="60" y="31" font-family="monospace" font-size="9" fill="#999">Rechercher...</text>
        <rect x="210" y="17" width="28" height="20" fill="#1b2a4a" rx="2"/>
        <text x="224" y="31" text-anchor="middle" font-family="monospace" font-size="10" fill="#fff">🔍</text>
        <!-- Results with rankings -->
        <rect x="40" y="50" width="200" height="20" fill="#2ABFBF" opacity="0.15" stroke="#1b2a4a" stroke-width="1"/>
        <text x="50" y="64" font-family="monospace" font-size="8" fill="#1b2a4a" font-weight="bold">#1 tonsite.fr</text>
        <text x="220" y="64" font-family="monospace" font-size="8" fill="#2ABFBF">★★★</text>
        <rect x="40" y="74" width="200" height="16" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <text x="50" y="85" font-family="monospace" font-size="7" fill="#999">#2 concurrent1.fr</text>
        <rect x="40" y="94" width="200" height="16" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <text x="50" y="105" font-family="monospace" font-size="7" fill="#999">#3 concurrent2.fr</text>
        <!-- Graph going up -->
        <polyline points="50,190 80,180 110,170 140,155 170,130 200,115 230,100" fill="none" stroke="#2ABFBF" stroke-width="3"/>
        <text x="240" y="100" font-family="monospace" font-size="12" fill="#2ABFBF">↑</text>
        <line x1="50" y1="190" x2="240" y2="190" stroke="#1b2a4a" stroke-width="1"/>
        <line x1="50" y1="100" x2="50" y2="190" stroke="#1b2a4a" stroke-width="1"/>
    </svg>
</template>

<template id="tpl-web-5"><!-- Maintenance web -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Wrench and gear -->
        <circle cx="140" cy="85" r="40" fill="none" stroke="#E0DBD5" stroke-width="8"/>
        <circle cx="140" cy="85" r="40" fill="none" stroke="#1b2a4a" stroke-width="2" stroke-dasharray="12 8"/>
        <circle cx="140" cy="85" r="15" fill="#1b2a4a"/>
        <circle cx="140" cy="85" r="8" fill="#E0DBD5"/>
        <!-- Gear teeth -->
        <rect x="132" y="38" width="16" height="14" fill="#1b2a4a" rx="2"/>
        <rect x="132" y="118" width="16" height="14" fill="#1b2a4a" rx="2"/>
        <rect x="93" y="77" width="14" height="16" fill="#1b2a4a" rx="2"/>
        <rect x="173" y="77" width="14" height="16" fill="#1b2a4a" rx="2"/>
        <!-- Checkmarks -->
        <text x="60" y="170" font-family="monospace" font-size="8" fill="#2ABFBF">✓ Sécurité</text>
        <text x="130" y="170" font-family="monospace" font-size="8" fill="#2ABFBF">✓ Backup</text>
        <text x="195" y="170" font-family="monospace" font-size="8" fill="#2ABFBF">✓ MAJ</text>
        <!-- Shield -->
        <path d="M140,145 L125,155 L125,170 L140,180 L155,170 L155,155 Z" fill="#2ABFBF" opacity="0.2" stroke="#1b2a4a" stroke-width="1.5"/>
        <text x="140" y="167" text-anchor="middle" font-family="monospace" font-size="10" fill="#1b2a4a">✓</text>
    </svg>
</template>

<template id="tpl-web-6"><!-- Hébergement -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Server rack -->
        <rect x="80" y="20" width="120" height="40" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <rect x="80" y="65" width="120" height="40" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <rect x="80" y="110" width="120" height="40" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <!-- LEDs -->
        <circle cx="95" cy="40" r="4" fill="#2ABFBF"/><circle cx="108" cy="40" r="4" fill="#2ABFBF"/>
        <circle cx="95" cy="85" r="4" fill="#2ABFBF"/><circle cx="108" cy="85" r="4" fill="#F5C542"/>
        <circle cx="95" cy="130" r="4" fill="#2ABFBF"/><circle cx="108" cy="130" r="4" fill="#2ABFBF"/>
        <!-- Drive bays -->
        <rect x="130" y="32" width="60" height="16" fill="#999" stroke="#1b2a4a" stroke-width="1" rx="1"/>
        <rect x="130" y="77" width="60" height="16" fill="#999" stroke="#1b2a4a" stroke-width="1" rx="1"/>
        <rect x="130" y="122" width="60" height="16" fill="#999" stroke="#1b2a4a" stroke-width="1" rx="1"/>
        <!-- Labels -->
        <text x="140" y="168" text-anchor="middle" font-family="monospace" font-size="8" fill="#1b2a4a">SSL · DNS · Mail</text>
        <!-- Connection lines -->
        <line x1="140" y1="155" x2="140" y2="162" stroke="#1b2a4a" stroke-width="1.5"/>
    </svg>
</template>

<template id="tpl-web-7"><!-- Blog / CMS -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="20" y="10" width="240" height="185" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="20" y="10" width="240" height="20" fill="#1b2a4a"/>
        <text x="140" y="24" text-anchor="middle" font-family="monospace" font-size="8" fill="#fff">MON BLOG</text>
        <!-- Article cards -->
        <rect x="30" y="40" width="105" height="65" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="30" y="40" width="105" height="30" fill="#2ABFBF" opacity="0.2"/>
        <rect x="35" y="76" width="80" height="4" fill="#1b2a4a"/>
        <rect x="35" y="84" width="90" height="3" fill="#ddd"/><rect x="35" y="90" width="70" height="3" fill="#ddd"/>
        <rect x="145" y="40" width="105" height="65" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="145" y="40" width="105" height="30" fill="#E63946" opacity="0.2"/>
        <rect x="150" y="76" width="80" height="4" fill="#1b2a4a"/>
        <rect x="150" y="84" width="90" height="3" fill="#ddd"/><rect x="150" y="90" width="70" height="3" fill="#ddd"/>
        <!-- Sidebar -->
        <rect x="30" y="115" width="220" height="70" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <rect x="40" y="122" width="60" height="4" fill="#1b2a4a"/>
        <rect x="40" y="132" width="200" height="3" fill="#ddd"/><rect x="40" y="139" width="180" height="3" fill="#ddd"/>
        <rect x="40" y="150" width="200" height="3" fill="#ddd"/><rect x="40" y="157" width="160" height="3" fill="#ddd"/>
        <rect x="40" y="168" width="200" height="3" fill="#ddd"/>
    </svg>
</template>

<template id="tpl-web-8"><!-- Design responsive -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Desktop -->
        <rect x="15" y="20" width="120" height="85" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <rect x="15" y="20" width="120" height="16" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="20" y="24" width="5" height="5" fill="#E63946"/><rect x="27" y="24" width="5" height="5" fill="#F5C542"/><rect x="34" y="24" width="5" height="5" fill="#2ABFBF"/>
        <rect x="25" y="44" width="100" height="4" fill="#1b2a4a"/><rect x="25" y="52" width="100" height="2" fill="#ddd"/>
        <rect x="25" y="60" width="45" height="30" fill="#2ABFBF" opacity="0.15"/><rect x="75" y="60" width="50" height="30" fill="#E63946" opacity="0.15"/>
        <rect x="55" y="105" width="40" height="6" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Tablet -->
        <rect x="150" y="30" width="55" height="75" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <rect x="155" y="38" width="45" height="3" fill="#1b2a4a"/>
        <rect x="155" y="44" width="45" height="2" fill="#ddd"/>
        <rect x="155" y="50" width="20" height="20" fill="#2ABFBF" opacity="0.15"/><rect x="178" y="50" width="22" height="20" fill="#E63946" opacity="0.15"/>
        <rect x="155" y="74" width="45" height="2" fill="#ddd"/><rect x="155" y="78" width="35" height="2" fill="#ddd"/>
        <!-- Phone -->
        <rect x="225" y="40" width="35" height="65" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="4"/>
        <rect x="230" y="48" width="25" height="3" fill="#1b2a4a"/>
        <rect x="230" y="54" width="25" height="2" fill="#ddd"/>
        <rect x="230" y="60" width="25" height="15" fill="#2ABFBF" opacity="0.15"/>
        <rect x="230" y="78" width="25" height="2" fill="#ddd"/>
        <!-- Arrows connecting -->
        <text x="140" y="72" font-family="monospace" font-size="10" fill="#F5C542">↔</text>
        <text x="212" y="72" font-family="monospace" font-size="10" fill="#F5C542">↔</text>
        <!-- Label -->
        <text x="140" y="150" text-anchor="middle" font-family="monospace" font-size="8" fill="#1b2a4a">Desktop · Tablet · Mobile</text>
    </svg>
</template>

<template id="tpl-web-9"><!-- Analytics & suivi -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Bar chart -->
        <line x1="40" y1="170" x2="250" y2="170" stroke="#1b2a4a" stroke-width="1.5"/>
        <line x1="40" y1="30" x2="40" y2="170" stroke="#1b2a4a" stroke-width="1.5"/>
        <rect x="55" y="120" width="24" height="50" fill="#2ABFBF" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="90" y="90" width="24" height="80" fill="#2ABFBF" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="125" y="100" width="24" height="70" fill="#F5C542" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="160" y="60" width="24" height="110" fill="#E63946" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="195" y="40" width="24" height="130" fill="#E63946" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Trend line -->
        <polyline points="67,115 102,85 137,95 172,55 207,35" fill="none" stroke="#1b2a4a" stroke-width="2" stroke-dasharray="4 2"/>
        <!-- Labels -->
        <text x="67" y="185" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Jan</text>
        <text x="102" y="185" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Fév</text>
        <text x="137" y="185" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Mar</text>
        <text x="172" y="185" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Avr</text>
        <text x="207" y="185" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Mai</text>
        <!-- Numbers -->
        <text x="207" y="35" text-anchor="middle" font-family="monospace" font-size="9" fill="#E63946" font-weight="bold">+42%</text>
    </svg>
</template>

<!-- ===== PRINT ===== -->
<template id="tpl-print-0"><!-- Carte de visite -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="40" y="30" width="200" height="110" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <rect x="50" y="45" width="60" height="8" fill="#1b2a4a"/>
        <text x="50" y="70" font-family="monospace" font-size="7" fill="#999">Directeur créatif</text>
        <line x1="50" y1="80" x2="230" y2="80" stroke="#E0DBD5" stroke-width="1"/>
        <text x="50" y="95" font-family="monospace" font-size="6" fill="#1b2a4a">✉ bonjour@agencepingpong.fr</text>
        <text x="50" y="108" font-family="monospace" font-size="6" fill="#1b2a4a">☎ 07 67 78 37 73</text>
        <text x="50" y="121" font-family="monospace" font-size="6" fill="#1b2a4a">🌐 agencepingpong.fr</text>
        <!-- Logo placeholder -->
        <circle cx="210" cy="60" r="18" fill="#E63946" opacity="0.15" stroke="#E63946" stroke-width="1.5"/>
        <text x="210" y="64" text-anchor="middle" font-family="monospace" font-size="8" fill="#E63946">PP</text>
        <!-- Shadow card behind -->
        <rect x="45" y="155" width="200" height="20" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1" rx="2"/>
        <text x="145" y="168" text-anchor="middle" font-family="monospace" font-size="7" fill="#999">verso</text>
    </svg>
</template>

<template id="tpl-print-1"><!-- Flyer / Tract -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="70" y="10" width="140" height="190" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="70" y="10" width="140" height="60" fill="#E63946"/>
        <text x="140" y="40" text-anchor="middle" font-family="monospace" font-size="12" fill="#fff" font-weight="bold">PROMO</text>
        <text x="140" y="56" text-anchor="middle" font-family="monospace" font-size="8" fill="#fff">-50%</text>
        <rect x="80" y="80" width="120" height="3" fill="#ddd"/><rect x="80" y="87" width="100" height="3" fill="#ddd"/><rect x="80" y="94" width="110" height="3" fill="#ddd"/>
        <rect x="80" y="108" width="120" height="40" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <rect x="95" y="158" width="90" height="16" fill="#1b2a4a" rx="2"/>
        <text x="140" y="169" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">En savoir +</text>
        <!-- Scattered flyers -->
        <rect x="20" y="60" width="30" height="40" fill="#F5F0EB" stroke="#ddd" stroke-width="1" transform="rotate(-15 35 80)" opacity="0.5"/>
        <rect x="230" y="50" width="30" height="40" fill="#F5F0EB" stroke="#ddd" stroke-width="1" transform="rotate(10 245 70)" opacity="0.5"/>
    </svg>
</template>

<template id="tpl-print-2"><!-- Affiche -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="50" y="5" width="180" height="200" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="2"/>
        <text x="140" y="50" text-anchor="middle" font-family="monospace" font-size="18" fill="#fff" font-weight="bold">EVENT</text>
        <text x="140" y="70" text-anchor="middle" font-family="monospace" font-size="8" fill="#F5C542">15 JUIN</text>
        <rect x="65" y="82" width="150" height="80" fill="#E63946" opacity="0.3"/>
        <text x="140" y="126" text-anchor="middle" font-family="monospace" font-size="10" fill="#fff">VISUEL</text>
        <rect x="80" y="172" width="120" height="3" fill="#fff" opacity="0.5"/>
        <rect x="90" y="180" width="100" height="3" fill="#fff" opacity="0.3"/>
        <rect x="100" y="188" width="80" height="3" fill="#fff" opacity="0.2"/>
        <!-- Pins -->
        <circle cx="80" cy="8" r="5" fill="#E63946" stroke="#fff" stroke-width="1"/>
        <circle cx="200" cy="8" r="5" fill="#E63946" stroke="#fff" stroke-width="1"/>
    </svg>
</template>

<template id="tpl-print-3"><!-- Brochure -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Tri-fold -->
        <rect x="20" y="25" width="80" height="160" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="100" y="25" width="80" height="160" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="180" y="25" width="80" height="160" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Content -->
        <rect x="28" y="35" width="64" height="30" fill="#2ABFBF" opacity="0.2"/><rect x="28" y="72" width="64" height="3" fill="#ddd"/><rect x="28" y="79" width="55" height="3" fill="#ddd"/><rect x="28" y="86" width="60" height="3" fill="#ddd"/>
        <rect x="108" y="35" width="64" height="8" fill="#1b2a4a"/><rect x="108" y="50" width="64" height="3" fill="#ddd"/><rect x="108" y="57" width="55" height="3" fill="#ddd"/><rect x="108" y="70" width="64" height="40" fill="#E63946" opacity="0.15"/>
        <rect x="188" y="35" width="64" height="8" fill="#1b2a4a"/><rect x="188" y="50" width="64" height="3" fill="#ddd"/><rect x="188" y="57" width="55" height="3" fill="#ddd"/><rect x="188" y="70" width="64" height="40" fill="#F5C542" opacity="0.15"/>
        <!-- Fold lines -->
        <line x1="100" y1="25" x2="100" y2="185" stroke="#1b2a4a" stroke-width="1" stroke-dasharray="4 3"/>
        <line x1="180" y1="25" x2="180" y2="185" stroke="#1b2a4a" stroke-width="1" stroke-dasharray="4 3"/>
    </svg>
</template>

<template id="tpl-print-4"><!-- Plaquette commerciale -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="40" y="15" width="200" height="180" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="40" y="15" width="200" height="40" fill="#1b2a4a"/>
        <text x="140" y="40" text-anchor="middle" font-family="monospace" font-size="10" fill="#fff">NOM ENTREPRISE</text>
        <rect x="55" y="65" width="80" height="50" fill="#2ABFBF" opacity="0.15" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="145" y="65" width="80" height="50" fill="#E63946" opacity="0.15" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="55" y="125" width="170" height="3" fill="#ddd"/><rect x="55" y="132" width="150" height="3" fill="#ddd"/>
        <rect x="55" y="145" width="170" height="3" fill="#ddd"/><rect x="55" y="152" width="130" height="3" fill="#ddd"/>
        <rect x="55" y="168" width="80" height="14" fill="#E63946" rx="2"/>
        <text x="95" y="178" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">Contactez-nous</text>
    </svg>
</template>

<template id="tpl-print-5"><!-- Menu restaurant -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <rect x="60" y="8" width="160" height="195" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="2" rx="2"/>
        <text x="140" y="32" text-anchor="middle" font-family="monospace" font-size="11" fill="#1b2a4a" font-weight="bold">MENU</text>
        <line x1="80" y1="38" x2="200" y2="38" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Entrées -->
        <text x="80" y="54" font-family="monospace" font-size="7" fill="#E63946" font-weight="bold">Entrées</text>
        <text x="80" y="66" font-family="monospace" font-size="6" fill="#1b2a4a">Soupe du jour</text><text x="200" y="66" text-anchor="end" font-family="monospace" font-size="6" fill="#1b2a4a">8€</text>
        <text x="80" y="76" font-family="monospace" font-size="6" fill="#1b2a4a">Salade fraîcheur</text><text x="200" y="76" text-anchor="end" font-family="monospace" font-size="6" fill="#1b2a4a">12€</text>
        <!-- Plats -->
        <text x="80" y="96" font-family="monospace" font-size="7" fill="#E63946" font-weight="bold">Plats</text>
        <text x="80" y="108" font-family="monospace" font-size="6" fill="#1b2a4a">Filet de boeuf</text><text x="200" y="108" text-anchor="end" font-family="monospace" font-size="6" fill="#1b2a4a">24€</text>
        <text x="80" y="118" font-family="monospace" font-size="6" fill="#1b2a4a">Risotto truffe</text><text x="200" y="118" text-anchor="end" font-family="monospace" font-size="6" fill="#1b2a4a">22€</text>
        <!-- Desserts -->
        <text x="80" y="138" font-family="monospace" font-size="7" fill="#E63946" font-weight="bold">Desserts</text>
        <text x="80" y="150" font-family="monospace" font-size="6" fill="#1b2a4a">Tarte tatin</text><text x="200" y="150" text-anchor="end" font-family="monospace" font-size="6" fill="#1b2a4a">10€</text>
        <line x1="80" y1="160" x2="200" y2="160" stroke="#E0DBD5" stroke-width="1"/>
        <text x="140" y="178" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">Menu 3 plats — 38€</text>
    </svg>
</template>

<template id="tpl-print-6"><!-- Packaging -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Box 3D -->
        <polygon points="70,60 170,40 170,150 70,170" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <polygon points="170,40 240,60 240,170 170,150" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="2"/>
        <polygon points="70,60 140,40 240,60 170,40" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Label on box -->
        <rect x="85" y="80" width="70" height="50" fill="#E63946" opacity="0.2" stroke="#E63946" stroke-width="1"/>
        <text x="120" y="100" text-anchor="middle" font-family="monospace" font-size="8" fill="#E63946">LOGO</text>
        <rect x="90" y="110" width="60" height="3" fill="#E63946" opacity="0.4"/>
        <rect x="95" y="116" width="50" height="3" fill="#E63946" opacity="0.3"/>
    </svg>
</template>

<template id="tpl-print-7"><!-- Kakémono / Roll-up -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Roll-up stand -->
        <rect x="90" y="10" width="100" height="170" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Content -->
        <circle cx="140" cy="45" r="22" fill="#E63946" opacity="0.3"/>
        <text x="140" y="50" text-anchor="middle" font-family="monospace" font-size="8" fill="#fff">LOGO</text>
        <text x="140" y="85" text-anchor="middle" font-family="monospace" font-size="10" fill="#fff" font-weight="bold">MARQUE</text>
        <rect x="105" y="95" width="70" height="2" fill="#fff" opacity="0.5"/>
        <rect x="105" y="105" width="70" height="3" fill="#fff" opacity="0.3"/>
        <rect x="105" y="112" width="60" height="3" fill="#fff" opacity="0.3"/>
        <rect x="105" y="119" width="65" height="3" fill="#fff" opacity="0.3"/>
        <rect x="110" y="140" width="60" height="14" fill="#E63946" rx="2"/>
        <text x="140" y="150" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff">CONTACT</text>
        <text x="140" y="170" text-anchor="middle" font-family="monospace" font-size="5" fill="#fff" opacity="0.5">www.site.fr</text>
        <!-- Base -->
        <rect x="80" y="180" width="120" height="10" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="1.5" rx="2"/>
        <!-- Stand legs -->
        <line x1="110" y1="190" x2="100" y2="200" stroke="#999" stroke-width="2"/>
        <line x1="170" y1="190" x2="180" y2="200" stroke="#999" stroke-width="2"/>
    </svg>
</template>

<template id="tpl-print-8"><!-- Papeterie -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Envelope -->
        <rect x="20" y="120" width="120" height="75" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <line x1="20" y1="120" x2="80" y2="155" stroke="#1b2a4a" stroke-width="1.5"/><line x1="140" y1="120" x2="80" y2="155" stroke="#1b2a4a" stroke-width="1.5"/>
        <!-- Letterhead -->
        <rect x="150" y="15" width="110" height="150" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="158" y="22" width="30" height="6" fill="#E63946"/>
        <rect x="158" y="35" width="94" height="2" fill="#ddd"/><rect x="158" y="41" width="80" height="2" fill="#ddd"/><rect x="158" y="47" width="85" height="2" fill="#ddd"/>
        <rect x="158" y="58" width="94" height="2" fill="#ddd"/><rect x="158" y="64" width="70" height="2" fill="#ddd"/>
        <!-- Stamp -->
        <rect x="85" y="130" width="30" height="20" fill="#2ABFBF" opacity="0.3" stroke="#2ABFBF" stroke-width="1"/>
        <!-- Business card small -->
        <rect x="25" y="25" width="100" height="55" fill="#F5F0EB" stroke="#1b2a4a" stroke-width="1.5"/>
        <rect x="32" y="32" width="25" height="5" fill="#E63946"/>
        <rect x="32" y="42" width="80" height="2" fill="#ddd"/><rect x="32" y="48" width="60" height="2" fill="#ddd"/>
        <text x="100" y="70" text-anchor="end" font-family="monospace" font-size="5" fill="#999">06 00 00 00</text>
    </svg>
</template>

<template id="tpl-print-9"><!-- Signalétique -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Sign post -->
        <rect x="135" y="30" width="10" height="170" fill="#999" stroke="#1b2a4a" stroke-width="1.5"/>
        <!-- Arrow signs -->
        <polygon points="60,45 200,45 210,65 200,85 60,85" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="1"/>
        <text x="130" y="70" text-anchor="middle" font-family="monospace" font-size="9" fill="#fff">ACCUEIL →</text>
        <polygon points="80,95 220,95 210,115 220,135 80,135" fill="#E63946" stroke="#1b2a4a" stroke-width="1"/>
        <text x="150" y="120" text-anchor="middle" font-family="monospace" font-size="9" fill="#fff">← SORTIE</text>
        <polygon points="70,145 210,145 220,165 210,185 70,185" fill="#2ABFBF" stroke="#1b2a4a" stroke-width="1"/>
        <text x="140" y="170" text-anchor="middle" font-family="monospace" font-size="9" fill="#fff">PARKING →</text>
    </svg>
</template>

<!-- ===== IDENTITY ===== -->
<template id="tpl-identity-0"><!-- Logo -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <circle cx="140" cy="90" r="55" fill="none" stroke="#E63946" stroke-width="3"/>
        <circle cx="140" cy="90" r="38" fill="none" stroke="#F5C542" stroke-width="2"/>
        <circle cx="140" cy="90" r="22" fill="#1b2a4a"/>
        <text x="140" y="96" text-anchor="middle" font-family="monospace" font-size="14" fill="#fff" font-weight="bold">PP</text>
        <!-- Sketchy lines around -->
        <line x1="70" y1="30" x2="85" y2="42" stroke="#ddd" stroke-width="1.5" stroke-dasharray="3 2"/>
        <line x1="195" y1="32" x2="210" y2="25" stroke="#ddd" stroke-width="1.5" stroke-dasharray="3 2"/>
        <line x1="60" y1="140" x2="75" y2="148" stroke="#ddd" stroke-width="1.5" stroke-dasharray="3 2"/>
        <line x1="205" y1="138" x2="220" y2="145" stroke="#ddd" stroke-width="1.5" stroke-dasharray="3 2"/>
        <!-- Grid dots -->
        <circle cx="40" cy="90" r="2" fill="#ddd"/><circle cx="50" cy="90" r="2" fill="#ddd"/><circle cx="60" cy="90" r="2" fill="#ddd"/>
        <circle cx="220" cy="90" r="2" fill="#ddd"/><circle cx="230" cy="90" r="2" fill="#ddd"/><circle cx="240" cy="90" r="2" fill="#ddd"/>
        <text x="140" y="175" text-anchor="middle" font-family="monospace" font-size="7" fill="#999">Construction logotype</text>
    </svg>
</template>

<template id="tpl-identity-1"><!-- Charte graphique -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Color swatches -->
        <rect x="20" y="20" width="50" height="50" fill="#E63946" stroke="#fff" stroke-width="2"/>
        <rect x="75" y="20" width="50" height="50" fill="#1b2a4a" stroke="#fff" stroke-width="2"/>
        <rect x="130" y="20" width="50" height="50" fill="#2ABFBF" stroke="#fff" stroke-width="2"/>
        <rect x="185" y="20" width="50" height="50" fill="#F5C542" stroke="#fff" stroke-width="2"/>
        <text x="45" y="80" text-anchor="middle" font-family="monospace" font-size="5" fill="#999">#E63946</text>
        <text x="100" y="80" text-anchor="middle" font-family="monospace" font-size="5" fill="#999">#1B2A4A</text>
        <text x="155" y="80" text-anchor="middle" font-family="monospace" font-size="5" fill="#999">#2ABFBF</text>
        <text x="210" y="80" text-anchor="middle" font-family="monospace" font-size="5" fill="#999">#F5C542</text>
        <!-- Typography -->
        <text x="30" y="105" font-family="monospace" font-size="16" fill="#1b2a4a" font-weight="bold">Aa</text>
        <text x="80" y="105" font-family="monospace" font-size="12" fill="#1b2a4a">Bb Cc</text>
        <text x="155" y="105" font-family="monospace" font-size="9" fill="#999">0123456789</text>
        <!-- Rules -->
        <line x1="20" y1="115" x2="260" y2="115" stroke="#E0DBD5" stroke-width="1"/>
        <rect x="20" y="125" width="80" height="8" fill="#ddd"/><text x="25" y="132" font-family="monospace" font-size="5" fill="#999">Heading</text>
        <rect x="20" y="140" width="240" height="3" fill="#ddd"/>
        <rect x="20" y="148" width="200" height="3" fill="#ddd"/>
        <!-- Logo usage -->
        <rect x="20" y="162" width="50" height="30" fill="#1b2a4a"/>
        <text x="45" y="181" text-anchor="middle" font-family="monospace" font-size="7" fill="#fff">PP</text>
        <rect x="80" y="162" width="50" height="30" fill="#fff" stroke="#1b2a4a" stroke-width="1"/>
        <text x="105" y="181" text-anchor="middle" font-family="monospace" font-size="7" fill="#1b2a4a">PP</text>
        <text x="145" y="181" font-family="monospace" font-size="5" fill="#2ABFBF">✓</text>
        <text x="155" y="181" font-family="monospace" font-size="5" fill="#E63946">✗</text>
    </svg>
</template>

<template id="tpl-identity-2"><!-- Direction artistique -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Mood board -->
        <rect x="15" y="10" width="120" height="90" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="145" y="10" width="120" height="55" fill="#E63946" opacity="0.3" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="145" y="70" width="55" height="30" fill="#2ABFBF" opacity="0.3" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="205" y="70" width="60" height="30" fill="#F5C542" opacity="0.3" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Photo indicators -->
        <text x="75" y="60" text-anchor="middle" font-family="monospace" font-size="20" fill="#fff" opacity="0.3">📷</text>
        <text x="205" y="45" text-anchor="middle" font-family="monospace" font-size="14" fill="#E63946" opacity="0.5">📷</text>
        <!-- Text elements -->
        <rect x="15" y="110" width="250" height="8" fill="#1b2a4a"/>
        <text x="140" y="117" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff">DIRECTION ARTISTIQUE</text>
        <rect x="15" y="125" width="120" height="70" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <rect x="25" y="132" width="50" height="4" fill="#1b2a4a"/>
        <rect x="25" y="140" width="100" height="2" fill="#ddd"/><rect x="25" y="146" width="90" height="2" fill="#ddd"/>
        <rect x="145" y="125" width="120" height="70" fill="#F5F0EB" stroke="#ddd" stroke-width="1"/>
        <rect x="155" y="132" width="50" height="4" fill="#E63946"/>
        <rect x="155" y="140" width="100" height="2" fill="#ddd"/><rect x="155" y="146" width="90" height="2" fill="#ddd"/>
    </svg>
</template>

<template id="tpl-identity-3"><!-- Naming -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Speech bubble -->
        <rect x="40" y="25" width="200" height="80" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="8"/>
        <polygon points="100,105 120,105 105,130" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <rect x="100" y="103" width="22" height="4" fill="#fff"/>
        <!-- Name options -->
        <text x="140" y="55" text-anchor="middle" font-family="monospace" font-size="14" fill="#1b2a4a" font-weight="bold">PING PONG</text>
        <text x="140" y="75" text-anchor="middle" font-family="monospace" font-size="8" fill="#E63946">Le nom qui claque.</text>
        <text x="140" y="90" text-anchor="middle" font-family="monospace" font-size="6" fill="#999">mémorable · unique · sonore</text>
        <!-- Rejected names -->
        <text x="60" y="155" font-family="monospace" font-size="7" fill="#ddd"><tspan text-decoration="line-through">Agence Créa+</tspan></text>
        <text x="60" y="170" font-family="monospace" font-size="7" fill="#ddd"><tspan text-decoration="line-through">Studio Vision</tspan></text>
        <text x="160" y="155" font-family="monospace" font-size="7" fill="#ddd"><tspan text-decoration="line-through">Com Factory</tspan></text>
        <text x="160" y="170" font-family="monospace" font-size="9" fill="#1b2a4a" font-weight="bold">PING PONG ✓</text>
    </svg>
</template>

<template id="tpl-identity-4"><!-- Univers de marque -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Central logo -->
        <circle cx="140" cy="85" r="25" fill="#1b2a4a"/>
        <text x="140" y="91" text-anchor="middle" font-family="monospace" font-size="12" fill="#fff">PP</text>
        <!-- Orbiting elements -->
        <circle cx="140" cy="85" r="55" fill="none" stroke="#E0DBD5" stroke-width="1" stroke-dasharray="4 3"/>
        <circle cx="140" cy="85" r="80" fill="none" stroke="#E0DBD5" stroke-width="1" stroke-dasharray="4 3"/>
        <!-- Satellites -->
        <circle cx="80" cy="45" r="12" fill="#E63946" opacity="0.3" stroke="#E63946" stroke-width="1"/>
        <text x="80" y="49" text-anchor="middle" font-family="monospace" font-size="5" fill="#E63946">Voix</text>
        <circle cx="200" cy="50" r="12" fill="#2ABFBF" opacity="0.3" stroke="#2ABFBF" stroke-width="1"/>
        <text x="200" y="54" text-anchor="middle" font-family="monospace" font-size="5" fill="#2ABFBF">Style</text>
        <circle cx="65" cy="120" r="12" fill="#F5C542" opacity="0.3" stroke="#F5C542" stroke-width="1"/>
        <text x="65" y="124" text-anchor="middle" font-family="monospace" font-size="5" fill="#F5C542">Valeurs</text>
        <circle cx="215" cy="115" r="12" fill="#1b2a4a" opacity="0.3" stroke="#1b2a4a" stroke-width="1"/>
        <text x="215" y="119" text-anchor="middle" font-family="monospace" font-size="5" fill="#1b2a4a">Vision</text>
        <!-- Bottom label -->
        <text x="140" y="185" text-anchor="middle" font-family="monospace" font-size="7" fill="#999">Écosystème de marque</text>
    </svg>
</template>

<template id="tpl-identity-5"><!-- Réseaux sociaux -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Phone frame -->
        <rect x="85" y="10" width="110" height="190" fill="#fff" stroke="#1b2a4a" stroke-width="2" rx="10"/>
        <rect x="120" y="15" width="40" height="6" fill="#ddd" rx="3"/>
        <!-- Feed -->
        <rect x="92" y="30" width="96" height="12" fill="#F5F0EB"/>
        <circle cx="102" cy="36" r="5" fill="#E63946"/><rect x="112" y="33" width="40" height="3" fill="#1b2a4a"/><rect x="112" y="38" width="25" height="2" fill="#ddd"/>
        <rect x="92" y="46" width="96" height="80" fill="#1b2a4a"/>
        <text x="140" y="90" text-anchor="middle" font-family="monospace" font-size="8" fill="#fff">POST</text>
        <rect x="92" y="130" width="96" height="3" fill="#ddd"/>
        <rect x="92" y="136" width="70" height="3" fill="#ddd"/>
        <!-- Story circles -->
        <circle cx="45" cy="50" r="18" fill="none" stroke="#E63946" stroke-width="2"/><circle cx="45" cy="50" r="14" fill="#F5F0EB"/>
        <circle cx="45" cy="100" r="18" fill="none" stroke="#F5C542" stroke-width="2"/><circle cx="45" cy="100" r="14" fill="#F5F0EB"/>
        <circle cx="235" cy="50" r="18" fill="none" stroke="#2ABFBF" stroke-width="2"/><circle cx="235" cy="50" r="14" fill="#F5F0EB"/>
        <circle cx="235" cy="100" r="18" fill="none" stroke="#E63946" stroke-width="2"/><circle cx="235" cy="100" r="14" fill="#F5F0EB"/>
        <!-- Like icons -->
        <text x="100" y="155" font-family="monospace" font-size="8" fill="#E63946">♥ 247</text>
        <text x="150" y="155" font-family="monospace" font-size="8" fill="#999">💬 18</text>
    </svg>
</template>

<template id="tpl-identity-6"><!-- Motion design -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Film strip -->
        <rect x="30" y="30" width="220" height="120" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Sprocket holes -->
        <rect x="35" y="35" width="8" height="10" fill="#fff" rx="1"/><rect x="35" y="55" width="8" height="10" fill="#fff" rx="1"/><rect x="35" y="75" width="8" height="10" fill="#fff" rx="1"/><rect x="35" y="95" width="8" height="10" fill="#fff" rx="1"/><rect x="35" y="115" width="8" height="10" fill="#fff" rx="1"/>
        <rect x="237" y="35" width="8" height="10" fill="#fff" rx="1"/><rect x="237" y="55" width="8" height="10" fill="#fff" rx="1"/><rect x="237" y="75" width="8" height="10" fill="#fff" rx="1"/><rect x="237" y="95" width="8" height="10" fill="#fff" rx="1"/><rect x="237" y="115" width="8" height="10" fill="#fff" rx="1"/>
        <!-- Frames showing animation -->
        <rect x="50" y="40" width="55" height="40" fill="#333"/>
        <circle cx="77" cy="60" r="10" fill="#E63946" opacity="0.5"/>
        <rect x="110" y="40" width="55" height="40" fill="#333"/>
        <circle cx="137" cy="60" r="12" fill="#E63946" opacity="0.7"/>
        <rect x="170" y="40" width="55" height="40" fill="#333"/>
        <circle cx="197" cy="60" r="15" fill="#E63946"/>
        <text x="197" y="64" text-anchor="middle" font-family="monospace" font-size="8" fill="#fff">PP</text>
        <!-- Play button -->
        <circle cx="140" cy="110" r="15" fill="none" stroke="#fff" stroke-width="2"/>
        <polygon points="135,102 135,118 150,110" fill="#fff"/>
        <!-- Timeline -->
        <rect x="50" y="165" width="180" height="4" fill="#E0DBD5" stroke="#1b2a4a" stroke-width="1"/>
        <rect x="50" y="165" width="100" height="4" fill="#E63946"/>
        <circle cx="150" cy="167" r="6" fill="#E63946" stroke="#1b2a4a" stroke-width="1"/>
        <text x="140" y="190" text-anchor="middle" font-family="monospace" font-size="7" fill="#999">00:03 / 00:08</text>
    </svg>
</template>

<template id="tpl-identity-7"><!-- Photographie -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Camera body -->
        <rect x="80" y="50" width="120" height="80" fill="#d4d4d4" stroke="#1b2a4a" stroke-width="2" rx="5"/>
        <rect x="110" y="38" width="40" height="16" fill="#999" stroke="#1b2a4a" stroke-width="1.5" rx="3"/>
        <!-- Lens -->
        <circle cx="140" cy="90" r="28" fill="#333" stroke="#1b2a4a" stroke-width="2"/>
        <circle cx="140" cy="90" r="20" fill="#1b2a4a"/>
        <circle cx="140" cy="90" r="12" fill="#2ABFBF" opacity="0.3"/>
        <circle cx="134" cy="82" r="4" fill="#fff" opacity="0.5"/>
        <!-- Flash -->
        <rect x="175" y="56" width="12" height="8" fill="#F5C542" stroke="#1b2a4a" stroke-width="1"/>
        <!-- Photos scattered -->
        <rect x="20" y="145" width="70" height="50" fill="#fff" stroke="#ddd" stroke-width="1.5" transform="rotate(-5 55 170)"/>
        <rect x="25" y="150" width="60" height="32" fill="#E63946" opacity="0.15"/>
        <rect x="110" y="142" width="70" height="50" fill="#fff" stroke="#ddd" stroke-width="1.5"/>
        <rect x="115" y="147" width="60" height="32" fill="#2ABFBF" opacity="0.15"/>
        <rect x="195" y="148" width="70" height="50" fill="#fff" stroke="#ddd" stroke-width="1.5" transform="rotate(5 230 173)"/>
        <rect x="200" y="153" width="60" height="32" fill="#F5C542" opacity="0.15"/>
    </svg>
</template>

<template id="tpl-identity-8"><!-- Illustration -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Canvas/artboard -->
        <rect x="40" y="15" width="200" height="170" fill="#fff" stroke="#1b2a4a" stroke-width="2"/>
        <!-- Simple illustration elements -->
        <circle cx="100" cy="70" r="25" fill="#E63946" opacity="0.3"/>
        <rect x="150" y="50" width="60" height="60" fill="#2ABFBF" opacity="0.3" transform="rotate(15 180 80)"/>
        <polygon points="140,110 170,160 110,160" fill="#F5C542" opacity="0.3"/>
        <!-- Pencil -->
        <line x1="200" y1="40" x2="240" y2="80" stroke="#1b2a4a" stroke-width="3"/>
        <polygon points="240,80 245,75 250,85" fill="#F5C542"/>
        <line x1="200" y1="40" x2="197" y2="37" stroke="#E63946" stroke-width="4"/>
        <!-- Character sketch -->
        <circle cx="90" cy="65" r="10" fill="none" stroke="#1b2a4a" stroke-width="1.5"/>
        <line x1="90" y1="75" x2="90" y2="100" stroke="#1b2a4a" stroke-width="1.5"/>
        <line x1="90" y1="82" x2="75" y2="92" stroke="#1b2a4a" stroke-width="1.5"/>
        <line x1="90" y1="82" x2="105" y2="92" stroke="#1b2a4a" stroke-width="1.5"/>
        <text x="140" y="195" text-anchor="middle" font-family="monospace" font-size="7" fill="#999">Mascotte sur mesure</text>
    </svg>
</template>

<template id="tpl-identity-9"><!-- Brand book -->
    <svg viewBox="0 0 280 210" xmlns="http://www.w3.org/2000/svg">
        <!-- Book cover -->
        <rect x="55" y="10" width="170" height="190" fill="#1b2a4a" stroke="#1b2a4a" stroke-width="2" rx="3"/>
        <!-- Spine -->
        <rect x="55" y="10" width="15" height="190" fill="#0f1c33" rx="3"/>
        <!-- Cover content -->
        <rect x="85" y="30" width="120" height="3" fill="#fff" opacity="0.3"/>
        <text x="145" y="75" text-anchor="middle" font-family="monospace" font-size="16" fill="#fff" font-weight="bold">BRAND</text>
        <text x="145" y="95" text-anchor="middle" font-family="monospace" font-size="16" fill="#fff" font-weight="bold">BOOK</text>
        <line x1="95" y1="105" x2="195" y2="105" stroke="#E63946" stroke-width="2"/>
        <text x="145" y="125" text-anchor="middle" font-family="monospace" font-size="8" fill="#F5C542">AGENCE PING PONG</text>
        <!-- Color dots on cover -->
        <circle cx="115" cy="150" r="8" fill="#E63946"/><circle cx="137" cy="150" r="8" fill="#2ABFBF"/><circle cx="159" cy="150" r="8" fill="#F5C542"/><circle cx="181" cy="150" r="8" fill="#F5F0EB"/>
        <text x="145" y="180" text-anchor="middle" font-family="monospace" font-size="6" fill="#fff" opacity="0.5">v1.0 — 2026</text>
        <!-- Pages peeking -->
        <line x1="225" y1="15" x2="225" y2="195" stroke="#ddd" stroke-width="1"/>
        <line x1="227" y1="18" x2="227" y2="192" stroke="#eee" stroke-width="1"/>
    </svg>
</template>
