</main><!-- /#site-content -->

<!-- Phylactère portrait -->
<div id="speech-bubble" class="speech-bubble" aria-hidden="true">
    <button class="speech-bubble__close" id="speech-bubble-close" aria-label="Fermer">✕</button>
    <p class="speech-bubble__text">Alors ?<br>On se fait une partie ?</p>
</div>

<!-- Bouton remonter -->
<button id="back-to-top" class="back-to-top" aria-label="Remonter en haut de page">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15"/>
    </svg>
</button>

<footer class="site-footer">
    <div class="site-footer__inner o-container">
        <!-- Rolling ball decoration -->
        <span class="site-footer__ball" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16">
                <circle cx="10" cy="10" r="9" fill="var(--color-navy, #1a1a2e)"/>
            </svg>
        </span>
        <p class="site-footer__copy">
            &copy; <?= date('Y') ?> Agence Ping Pong : <a href="https://www.anthropic.com/claude-code" class="site-footer__link" target="_blank" rel="noopener">Claude Code</a> et Jorge
        </p>
    </div>
</footer>

<!-- JS vendors (defer) -->
<script src="assets/js/vendors/gsap.min.js" defer></script>
<script src="assets/js/vendors/ScrollTrigger.min.js" defer></script>
<script src="assets/js/vendors/SplitText.min.js" defer></script>
<script src="assets/js/vendors/MotionPathPlugin.min.js" defer></script>
<script src="assets/js/vendors/lenis.min.js" defer></script>

<!-- JS app scripts (defer) -->
<script src="assets/js/ball.js" defer></script>
<script src="assets/js/portrait.js" defer></script>
<script src="assets/js/animations.js" defer></script>
<script src="assets/js/contact-interaction.js" defer></script>
<script src="assets/js/pong.js" defer></script>
<script src="assets/js/app.js" defer></script>

</body>
</html>
