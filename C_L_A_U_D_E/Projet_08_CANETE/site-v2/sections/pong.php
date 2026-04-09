<section id="pong-game" class="section pong-section">
    <div class="pong-section__inner">

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
                <h2 class="pong-section__title">Une petite partie ?</h2>
                <p class="pong-section__subtitle">Le classique de 1972. Fleches haut/bas pour jouer.</p>

                <div class="pong-section__wrapper">
                    <canvas id="pong-canvas" class="pong-canvas" width="800" height="500"></canvas>

                    <div id="pong-overlay" class="pong-overlay">
                        <button id="pong-start" class="pong-start-btn">&#9654; JOUER</button>
                    </div>
                </div>

                <p class="pong-section__controls">
                    <kbd>&uarr;</kbd> <kbd>&darr;</kbd> pour deplacer ta raquette — Premier a 5 !
                </p>
            </div>
        </div>

    </div>
</section>
