<?php
// Generate simple math captcha
$captchaA = rand(1, 9);
$captchaB = rand(1, 9);
$_SESSION['captcha_answer'] = $captchaA + $captchaB;
?>
<section id="contact" class="section contact">
    <div class="o-container">

        <h2 class="section__title" data-animate="split-reveal">Contact.</h2>
        <div class="section__marquee" aria-hidden="true">
            <div class="section__marquee-track">
                <span>Un message, un échange, une partie</span>
                <span>·</span>
                <span>bonjour@agencepingpong.fr</span>
                <span>·</span>
                <span>07 67 78 37 73</span>
                <span>·</span>
                <span>Un message, un échange, une partie</span>
                <span>·</span>
                <span>bonjour@agencepingpong.fr</span>
                <span>·</span>
                <span>07 67 78 37 73</span>
                <span>·</span>
                <span>Un message, un échange, une partie</span>
                <span>·</span>
                <span>bonjour@agencepingpong.fr</span>
                <span>·</span>
                <span>07 67 78 37 73</span>
                <span>·</span>
            </div>
        </div>

        <!-- Mac OS 9 Mail Window -->
        <div class="mac-win contact-win">
            <div class="mac-win__titlebar">
                <span class="mac-win__btn mac-win__btn--close"></span>
                <span class="mac-win__btn mac-win__btn--minimize"></span>
                <span class="mac-win__btn mac-win__btn--zoom"></span>
                <span class="mac-win__title">✉ Nouveau message</span>
                <div class="mac-win__stripes"></div>
            </div>

            <!-- Mail toolbar -->
            <div class="contact-win__toolbar">
                <button type="submit" form="contact-form" class="contact-win__toolbar-btn contact-win__toolbar-btn--send"><svg class="contact-win__toolbar-icon" viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/><path d="M2 10l15 2-15 2V10z" opacity="0.3"/></svg> Envoyer</button>
                <label class="contact-win__toolbar-btn contact-win__toolbar-btn--attach" id="toolbar-attach">
                    Joindre
                    <input type="file" name="attachment" form="contact-form" accept=".jpg,.jpeg,.png" class="contact-win__file-input" id="contact-file" aria-label="Joindre une image">
                </label>
                <div class="contact-win__toolbar-dropdown" id="toolbar-fonts">
                    <span class="contact-win__toolbar-btn">Polices</span>
                    <div class="contact-win__dropdown-menu" id="font-menu">
                        <button type="button" class="contact-win__dropdown-item" data-font="chicago">ChicagoFLF</button>
                        <button type="button" class="contact-win__dropdown-item" data-font="grotesk">Space Grotesk</button>
                        <button type="button" class="contact-win__dropdown-item" data-font="clash">Clash Display</button>
                        <button type="button" class="contact-win__dropdown-item" data-font="mono">Monaco / Mono</button>
                    </div>
                </div>
                <div class="contact-win__toolbar-dropdown" id="toolbar-colors">
                    <span class="contact-win__toolbar-btn">Couleurs</span>
                    <div class="contact-win__dropdown-menu" id="color-menu">
                        <button type="button" class="contact-win__dropdown-item contact-win__color-swatch" data-color="cream" title="Crème (défaut)"><span style="background:#F5F0EB"></span> Crème</button>
                        <button type="button" class="contact-win__dropdown-item contact-win__color-swatch" data-color="navy" title="Navy"><span style="background:#1B2A4A"></span> Navy</button>
                        <button type="button" class="contact-win__dropdown-item contact-win__color-swatch" data-color="coral" title="Corail"><span style="background:#E63946"></span> Corail</button>
                        <button type="button" class="contact-win__dropdown-item contact-win__color-swatch" data-color="turquoise" title="Turquoise"><span style="background:#2ABFBF"></span> Turquoise</button>
                    </div>
                </div>
                <span class="contact-win__toolbar-btn" id="toolbar-brouillon">Brouillon</span>
            </div>

            <!-- Attachment preview -->
            <div class="contact-win__attachment" id="attachment-preview" hidden>
                <span class="contact-win__attachment-icon">📎</span>
                <span class="contact-win__attachment-name" id="attachment-name"></span>
                <button type="button" class="contact-win__attachment-remove" id="attachment-remove" aria-label="Supprimer la pièce jointe">✕</button>
            </div>

            <!-- Form -->
            <div class="contact-win__body" id="contact-form-wrapper">
                <form class="contact-win__form" id="contact-form" method="POST" action="api/contact.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Mail-style headers as form fields -->
                    <div class="contact-win__headers">
                        <div class="contact-win__header-row">
                            <label for="contact-email" class="contact-win__header-label">De :</label>
                            <input type="email" id="contact-email" name="email" class="contact-win__header-input" placeholder="ton@email.fr" required autocomplete="email">
                        </div>
                        <div class="contact-win__header-row">
                            <span class="contact-win__header-label">À :</span>
                            <span class="contact-win__header-value contact-win__header-value--fixed">bonjour@agencepingpong.fr</span>
                        </div>
                        <div class="contact-win__header-row">
                            <label for="contact-subject" class="contact-win__header-label">Objet :</label>
                            <input type="text" id="contact-subject" name="subject" class="contact-win__header-input" placeholder="On échange ?" autocomplete="off">
                        </div>
                    </div>

                    <div class="contact-win__field-row contact-win__field-row--double">
                        <div class="contact-win__field-group">
                            <label for="contact-name" class="contact-win__field-label">Nom</label>
                            <input type="text" id="contact-name" name="name" class="contact-win__input" placeholder="Ton nom" required autocomplete="name">
                        </div>
                        <div class="contact-win__field-group">
                            <label for="contact-phone" class="contact-win__field-label">Tél.</label>
                            <input type="tel" id="contact-phone" name="phone" class="contact-win__input" placeholder="Ton numéro" autocomplete="tel">
                        </div>
                    </div>

                    <div class="contact-win__field-message">
                        <textarea id="contact-message" name="message" class="contact-win__textarea" placeholder="Qu'est-ce qu'on échange ?" required rows="6"></textarea>
                    </div>

                    <!-- Captcha -->
                    <div class="contact-win__captcha">
                        <div class="contact-win__captcha-box">
                            <span class="contact-win__captcha-icon" aria-hidden="true">🏓</span>
                            <span class="contact-win__captcha-question">
                                Combien font <?= $captchaA ?> + <?= $captchaB ?> ?
                            </span>
                            <input type="number" name="captcha" class="contact-win__captcha-input" required placeholder="?" autocomplete="off">
                        </div>
                        <span class="contact-win__captcha-hint">Anti-robot — prouve que tu sais compter</span>
                    </div>

                    <!-- Send button -->
                    <div class="contact-win__actions">
                        <button type="submit" class="contact-win__send-btn" id="contact-submit">
                            <svg class="contact-win__send-icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/><path d="M2 10l15 2-15 2V10z" opacity="0.3"/></svg>
                            Envoyer la balle
                        </button>
                    </div>
                </form>

                <!-- Success message -->
                <div class="contact-win__success" id="contact-success" aria-live="polite" hidden>
                    <div class="contact-win__success-icon">🏓</div>
                    <p class="contact-win__success-text">Bien joué. Je te renvoie la balle très vite.</p>
                </div>
            </div>

            <!-- Status bar -->
            <div class="contact-win__statusbar">
                <span>✦ Ping Pong Mail v1.0</span>
                <span>Connexion sécurisée</span>
            </div>
        </div>

    </div>
</section>
