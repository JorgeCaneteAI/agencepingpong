</main><!-- /#site-content -->

<footer class="site-footer">
    <div class="site-footer__inner o-container">
        <!-- Rolling ball decoration -->
        <span class="site-footer__ball" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16">
                <circle cx="10" cy="10" r="9" fill="var(--color-navy, #1a1a2e)"/>
            </svg>
        </span>
        <p class="site-footer__copy">
            &copy; <?= date('Y') ?> Agence Ping Pong. Tous droits réservés.
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
<script src="assets/js/app.js" defer></script>

</body>
</html>
