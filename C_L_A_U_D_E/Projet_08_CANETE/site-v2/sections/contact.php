<section id="contact" class="section section--fullscreen contact">
    <div class="o-container">

        <div class="contact__game" id="contact-game">
            <h2 class="contact__title" data-animate="split-reveal">À toi de jouer.</h2>
            <p class="contact__subtitle">
                Tu as un projet ? Une compétence à échanger ?<br>
                Envoie la balle — on voit ce qu'on peut faire ensemble.
            </p>
            <button class="contact__tap-btn btn btn--primary" id="contact-tap-btn" aria-label="Révéler le formulaire de contact">
                Tape dans la balle
            </button>
        </div>

        <!-- Confetti container (filled by JS on tap) -->
        <div class="confetti-container" id="confetti-container" aria-hidden="true"></div>

        <!-- Contact form (hidden until ball tap) -->
        <div class="contact__form-wrapper" id="contact-form-wrapper" aria-hidden="true" hidden>
            <form class="contact__form" id="contact-form" method="POST" action="api/contact.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form__field">
                    <label for="contact-name" class="form__label">Nom</label>
                    <input
                        type="text"
                        id="contact-name"
                        name="name"
                        class="form__input"
                        placeholder="Ton nom"
                        required
                        autocomplete="name"
                    >
                </div>

                <div class="form__field">
                    <label for="contact-email" class="form__label">Email</label>
                    <input
                        type="email"
                        id="contact-email"
                        name="email"
                        class="form__input"
                        placeholder="Ton email"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form__field">
                    <label for="contact-message" class="form__label">Message</label>
                    <textarea
                        id="contact-message"
                        name="message"
                        class="form__textarea"
                        placeholder="Qu'est-ce qu'on échange ?"
                        required
                        rows="5"
                    ></textarea>
                </div>

                <button type="submit" class="form__submit btn btn--primary">
                    Envoyer la balle
                </button>
            </form>

            <!-- Success message (hidden until form sent) -->
            <div class="contact__success" id="contact-success" aria-live="polite" hidden>
                <p class="contact__success-text">
                    Bien joué. Je te renvoie la balle très vite.
                </p>
            </div>
        </div>

    </div>
</section>
