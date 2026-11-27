
</main>

<footer class="site-footer">
    <div>
        <strong><?= e($headerSettings['site_name'] ?? SITE_NAME) ?></strong>
        <p>Plateforme officielle EUROLINK V5.</p>
    </div>

    <div class="footer-links">
        <a href="index.php">Accueil</a>
        <a href="fonctionnalites.php">Fonctionnalités</a>
        <a href="nouveautes.php">Nouveautés</a>

        <?php if (!empty($discordUrl) && filter_var($discordUrl, FILTER_VALIDATE_URL)): ?>
            <a href="<?= e($discordUrl) ?>" target="_blank" rel="noopener noreferrer">
                Rejoindre Discord
            </a>
        <?php endif; ?>
    </div>

    <p class="copyright">
        © <?= date('Y') ?> EUROLINK V5 — Tous droits réservés.
    </p>
</footer>

</body>
</html>
