<!--
  footer.php
  OggiInLab
  Copyright (c) 2026 Sergio Ferraro
  Licensed under the MIT License
-->

<footer class="footer-section mt-5">
    <div class="container text-center small text-muted">
        &copy; <?php echo date("Y"); ?> OggiInLab | Sergio Ferraro <br>
        Rilasciato sotto licenza <a href="LICENSE" target="_blank">MIT</a> |
        <a href="https://github.com/sergioferraro/OggiInLab" target="_blank">GitHub</a>
        <br>
        <a href="privacy.php">Informativa privacy</a>
    </div>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Page-specific external JS files (opzionale: definire $pageScriptFiles = ['file1.js', ...]) -->
<?php if (!empty($pageScriptFiles ?? [])): ?>
    <?php foreach ($pageScriptFiles as $jsFile): ?>
    <script src="<?= htmlspecialchars($jsFile) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<!-- Page-specific inline scripts (opzionale: definire $pageScripts = '...') -->
<?php if (!empty($pageScripts ?? '')): ?>
<script>
    <?= $pageScripts ?>
</script>
<?php endif; ?>



</body>
</html>
