<section id="contact" class="section">
    <div class="section__inner contact">
        <div class="o-container contact__content">
            <h2 class="contact__title" data-animate="split-reveal">
                À vous de jouer
            </h2>
            <p class="contact__intro body1" data-animate="fade-up">
                Vous avez un produit, on a le savoir-faire.<br>
                Renvoyez-nous la balle.
            </p>
            <div class="o-dashline"></div>
            <form class="contact__form" action="api/contact.php" method="POST" data-animate="fade-up">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="contact__form-group">
                    <label for="contact-name" class="contact__label text-muted">Nom</label>
                    <input type="text" id="contact-name" name="name" required class="contact__input" autocomplete="name">
                </div>
                <div class="contact__form-group">
                    <label for="contact-email" class="contact__label text-muted">Email</label>
                    <input type="email" id="contact-email" name="email" required class="contact__input" autocomplete="email">
                </div>
                <div class="contact__form-group">
                    <label for="contact-message" class="contact__label text-muted">Votre proposition</label>
                    <textarea id="contact-message" name="message" required rows="4" class="contact__textarea"></textarea>
                </div>
                <button type="submit" class="btn btn--accent btn--large contact__submit">Envoyer</button>
            </form>
            <div class="contact__success" hidden>
                <p class="body1">Bien reçu. On se renvoie la balle très vite.</p>
            </div>
        </div>
    </div>
</section>
