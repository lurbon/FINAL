<?php
require_once 'includes/config.php';
$page_title = "Nos missions";
include 'includes/header.php';
?>

<style>
@media (max-width: 768px) {
    section div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    section div[style*="order: 2"],
    section div[style*="order: 1"] {
        order: 0 !important;
    }
}
</style>

<main>
<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Nos missions</h1>
        <p>Au service des personnes isolées</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto; text-align: center; margin-bottom: 3rem;">
            <p style="font-size: 1.25rem; line-height: 1.8; color: var(--text-secondary);">
                Entraide Plus Iroise intervient auprès des personnes isolées, âgées ou en difficulté 
                pour leur apporter soutien et aide dans leur quotidien.
            </p>
        </div>
        
        <!-- Mission 1 -->
        <div style="margin-bottom: 4rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div>
                    <div style="display: inline-block; padding: 1rem; background: var(--primary-color); 
                                border-radius: var(--radius-lg); margin-bottom: 1rem;">
                        <span style="font-size: 3rem;">🚗</span>
                    </div>
                    <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Transport et accompagnement</h2>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                        Nos bénévoles accompagnent les personnes pour :
                    </p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Rendez-vous médicaux (médecin, hôpital, spécialistes)</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Courses alimentaires</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Démarches administratives</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Sorties culturelles et de loisirs</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <img src="assets/images/transport.jpg" alt="Transport" 
                         style="width: 100%; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl);">
                </div>
            </div>
        </div>
        
        <!-- Mission 2 -->
        <div style="margin-bottom: 4rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div style="order: 2;">
                    <div style="display: inline-block; padding: 1rem; background: var(--secondary-color); 
                                border-radius: var(--radius-lg); margin-bottom: 1rem;">
                        <span style="font-size: 3rem;">🤝</span>
                    </div>
                    <h2 style="color: var(--secondary-color); margin-bottom: 1rem;">Aide à la personne</h2>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                        Nous apportons une aide concrète dans diverses situations :
                    </p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--primary-color); font-weight: bold;">✓</span>
                            <span>Petits travaux de bricolage</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--primary-color); font-weight: bold;">✓</span>
                            <span>Aide administrative et rédaction de courriers</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--primary-color); font-weight: bold;">✓</span>
                            <span>Utilisation des outils numériques</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--primary-color); font-weight: bold;">✓</span>
                            <span>Aide ponctuelle selon les besoins</span>
                        </li>
                    </ul>
                </div>
                <div style="order: 1;">
                    <img src="assets/images/aide.jpg" alt="Aide à la personne" 
                         style="width: 100%; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl);">
                </div>
            </div>
        </div>
        
        <!-- Mission 3 -->
        <div style="margin-bottom: 4rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center;">
                <div>
                    <div style="display: inline-block; padding: 1rem; background: #f59e0b; 
                                border-radius: var(--radius-lg); margin-bottom: 1rem;">
                        <span style="font-size: 3rem;">❤️</span>
                    </div>
                    <h2 style="color: #f59e0b; margin-bottom: 1rem;">Lien social et soutien moral</h2>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                        Lutter contre l'isolement est au cœur de nos préoccupations :
                    </p>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Visites de courtoisie régulières</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Écoute et soutien moral</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Création de liens entre habitants</span>
                        </li>
                        <li style="padding: 0.5rem 0; display: flex; align-items: start; gap: 0.5rem;">
                            <span style="color: var(--secondary-color); font-weight: bold;">✓</span>
                            <span>Organisation d'événements conviviaux</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <img src="assets/images/discute.jpg" alt="Lien social" 
                         style="width: 100%; border-radius: var(--radius-xl); box-shadow: var(--shadow-xl);">
                </div>
            </div>
        </div>
        
        <!-- Encadré important -->
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                    color: white; padding: 3rem; border-radius: var(--radius-xl); text-align: center; margin-top: 3rem;">
            <h2 style="font-size: 2rem; margin-bottom: 1rem;">Gratuité et bénévolat</h2>
            <p style="font-size: 1.25rem; line-height: 1.8; max-width: 700px; margin: 0 auto; opacity: 0.95;">
                Toutes nos interventions sont totalement gratuites et réalisées par des bénévoles 
                couverts par une assurance de l'association.
            </p>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="section section-light">
    <div class="container">
        <div style="text-align: center; max-width: 600px; margin: 0 auto;">
            <h2 style="margin-bottom: 1rem;">Besoin d'aide ou envie de nous rejoindre ?</h2>
            <p style="font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem;">
                N'hésitez pas à nous contacter
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-primary">Nous contacter</a>
                <a href="nous-rejoindre.php" class="btn btn-secondary">Devenir bénévole</a>
            </div>
        </div>
    </div>
</section>

</main>

<?php include 'includes/footer.php'; ?>
