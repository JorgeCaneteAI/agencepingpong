<section id="pong-game" class="section pong-section">
    <div class="pong-section__inner">

        <h2 class="section__title section__title--light" data-animate="split-reveal">Une partie ?</h2>
        <div class="section__marquee section__marquee--light" aria-hidden="true">
            <div class="section__marquee-track">
                <span>Pong · 1972 · Atari · Le classique</span>
                <span>·</span>
                <span>Flèches haut/bas ou glisse ton doigt · Premier à 5</span>
                <span>·</span>
                <span>Pong · 1972 · Atari · Le classique</span>
                <span>·</span>
                <span>Flèches haut/bas ou glisse ton doigt · Premier à 5</span>
                <span>·</span>
                <span>Pong · 1972 · Atari · Le classique</span>
                <span>·</span>
                <span>Flèches haut/bas ou glisse ton doigt · Premier à 5</span>
                <span>·</span>
            </div>
        </div>

        <!-- Mac OS 8 Window -->
        <div class="pong-window mac-win">
            <!-- Title bar -->
            <div class="mac-win__titlebar">
                <div class="mac-win__btn mac-win__btn--close"></div>
                <div class="mac-win__btn mac-win__btn--minimize"></div>
                <div class="mac-win__btn mac-win__btn--zoom"></div>
                <div class="mac-win__title">Pong.app</div>
                <div class="mac-win__stripes"></div>
            </div>
            <!-- Menu bar -->
            <div class="pong-menubar">
                <span class="pong-menubar__item">Fichier</span>
                <span class="pong-menubar__item">Edition</span>
                <span class="pong-menubar__item">Partie</span>
                <span class="pong-menubar__item">Special</span>
            </div>
            <!-- Content -->
            <div class="mac-win__body pong-body">
                <div class="pong-section__wrapper">
                    <div class="pong-rotate-hint" id="pong-rotate-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="36" height="36" fill="none" stroke="#1B2A4A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="10" y="4" width="28" height="40" rx="3"/>
                            <path d="M38 24a14 14 0 0 1-14 14"/>
                            <polyline points="20 34 24 38 28 34"/>
                        </svg>
                        <span>Tourne ton téléphone en paysage pour jouer</span>
                    </div>
                    <canvas id="pong-canvas" class="pong-canvas" width="800" height="500"></canvas>

                    <div id="pong-overlay" class="pong-overlay">
                        <button id="pong-start" class="pong-start-btn">&#9654; JOUER</button>
                    </div>
                </div>

                <p class="pong-section__controls">
                    <kbd>&uarr;</kbd> <kbd>&darr;</kbd> ou glisse ton doigt pour déplacer ta raquette — Premier à 5 !
                </p>
            </div>
        </div>

    </div>
</section>
