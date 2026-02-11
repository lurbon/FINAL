<?php
$page_title = "Politique de confidentialité";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Politique de confidentialité</h1>
        <p>Protection de vos données personnelles</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            
            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">1. Collecte des données</h2>
                <p style="line-height: 1.8; color: var(--text-secondary);">
                    Entraide Plus Iroise collecte uniquement les données nécessaires au bon fonctionnement de ses services et à la gestion de l'association. 
                    Ces données sont collectées de manière transparente et avec votre consentement.
                </p>
            </div>

            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">2. Utilisation des cookies</h2>
                <p style="line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                    Notre site utilise des cookies pour améliorer votre expérience de navigation. Les cookies sont de petits fichiers texte 
                    stockés sur votre appareil.
                </p>
                <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Types de cookies utilisés :</h3>
                <ul style="line-height: 1.8; color: var(--text-secondary);">
                    <li><strong>Cookies essentiels :</strong> Nécessaires au fonctionnement du site (connexion, panier, etc.)</li>
                    <li><strong>Cookies de performance :</strong> Nous permettent d'analyser l'utilisation du site pour l'améliorer</li>
                    <li><strong>Cookies de préférence :</strong> Mémorisent vos choix et préférences</li>
                </ul>
                <p style="line-height: 1.8; color: var(--text-secondary); margin-top: 1rem;">
                    Vous pouvez à tout moment modifier vos préférences en matière de cookies via les paramètres de votre navigateur.
                </p>
            </div>

            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">3. Protection des données</h2>
                <p style="line-height: 1.8; color: var(--text-secondary);">
                    Nous mettons en œuvre toutes les mesures techniques et organisationnelles appropriées pour protéger vos données 
                    personnelles contre tout accès non autorisé, modification, divulgation ou destruction.
                </p>
            </div>

            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">4. Vos droits</h2>
                <p style="line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                    Conformément au RGPD, vous disposez des droits suivants :
                </p>
                <ul style="line-height: 1.8; color: var(--text-secondary);">
                    <li>Droit d'accès à vos données personnelles</li>
                    <li>Droit de rectification de vos données</li>
                    <li>Droit à l'effacement de vos données</li>
                    <li>Droit à la limitation du traitement</li>
                    <li>Droit à la portabilité de vos données</li>
                    <li>Droit d'opposition au traitement</li>
                </ul>
            </div>

            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">5. Conservation des données</h2>
                <p style="line-height: 1.8; color: var(--text-secondary);">
                    Vos données personnelles sont conservées uniquement le temps nécessaire aux finalités pour lesquelles elles ont été collectées, 
                    ou conformément aux obligations légales de conservation.
                </p>
            </div>

            <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: 2rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem;">6. Contact</h2>
                <p style="line-height: 1.8; color: var(--text-secondary);">
                    Pour toute question concernant cette politique de confidentialité ou pour exercer vos droits, 
                    vous pouvez nous contacter :
                </p>
                <ul style="line-height: 1.8; color: var(--text-secondary); list-style: none; padding: 0; margin-top: 1rem;">
                    <li>📧 Email : <a href="mailto:contact@entraide-plus-iroise.fr" style="color: var(--primary-color);">contact@entraide-plus-iroise.fr</a></li>
                    <li>📞 Téléphone : [Votre numéro]</li>
                    <li>📍 Adresse : [Votre adresse]</li>
                </ul>
            </div>

            <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary-color);">
                <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem;">
                    <strong>Dernière mise à jour :</strong> <?php echo date('d/m/Y'); ?>
                </p>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
