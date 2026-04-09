<?php require_once 'includes/header.php'; ?>

<?php require_once 'sections/hero.php'; ?>

<!-- Net decoration (full width horizontal) -->
<div class="net-separator" aria-hidden="true">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 60" preserveAspectRatio="none" class="net-separator__svg">
        <rect x="0" y="0" width="1600" height="6" fill="currentColor"/>
        <line x1="0" y1="15" x2="1600" y2="15" stroke="currentColor" stroke-width="1.5"/>
        <line x1="0" y1="25" x2="1600" y2="25" stroke="currentColor" stroke-width="1.5"/>
        <line x1="0" y1="35" x2="1600" y2="35" stroke="currentColor" stroke-width="1.5"/>
        <line x1="0" y1="45" x2="1600" y2="45" stroke="currentColor" stroke-width="1.5"/>
        <line x1="0" y1="55" x2="1600" y2="55" stroke="currentColor" stroke-width="1.5"/>
        <?php for ($i = 0; $i <= 1600; $i += 40): ?>
        <line x1="<?= $i ?>" y1="0" x2="<?= $i ?>" y2="60" stroke="currentColor" stroke-width="1" stroke-opacity="0.4"/>
        <?php endfor; ?>
    </svg>
</div>

<?php require_once 'sections/concept.php'; ?>

<?php require_once 'sections/services.php'; ?>

<?php require_once 'sections/realisations.php'; ?>

<?php require_once 'sections/contact.php'; ?>

<?php require_once 'sections/pong.php'; ?>

<?php require_once 'includes/footer.php'; ?>
