<?php
require_once 'includes/config.php';
$page_title = "Notre histoire";
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
        <h1>Notre histoire</h1>
        <p>Une idée simple dans le sens de l'entraide</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; margin-bottom: 3rem;">
                <div>
                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Les débuts en 2010</h2>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                        Une idée simple dans le sens de l'entraide est à l'origine de la fondation de l'association : 
                        un groupe de "jeunes retraités dynamiques" se sont regroupés afin de mettre en commun leur 
                        désir d'aider les personnes de Landunvez qui se sentaient isolées au sein de leur belle commune.
                    </p>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary);">
                        Ils pensaient qu'une forme de vie sociale pouvait se mettre en place sur fond d'entraide et 
                        de solidarité, sans toujours attendre l'aide des pouvoirs publics.
                    </p>
                </div>
                <div>
                    <img src="assets/images/bebe.jpg" alt="Début de l'association" 
                         style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
                </div>
            </div>
            
            <div style="background: var(--background-light); padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 3rem;">
                <h3 style="color: var(--secondary-color); margin-bottom: 1rem; text-align: center; font-size: 1.75rem;">
                    Naissance de "Landunvez Entraide Plus"
                </h3>
                <p style="font-size: 1.125rem; line-height: 1.8; text-align: center; color: var(--text-secondary);">
                    L'association a vu le jour au travers d'une association Loi 1901 au début du mois de mars 2010.
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; margin-bottom: 3rem;">
                <div style="order: 2;">
                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">Notre philosophie</h2>
                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                        Notre association est composée uniquement de bénévoles et des personnes soutenues par ces bénévoles.
                    </p>
                    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                                color: white; padding: 1.5rem; border-radius: var(--radius-lg); margin-top: 1.5rem;">
                        <p style="font-size: 1.25rem; font-weight: 600; text-align: center; margin: 0;">
                            "L'Aidé" de la veille peut devenir le "Bénévole" de demain…<br>
                            C'est cela "L'Entraide" ! C'est cela notre force…
                        </p>
                    </div>
                </div>
                <div style="order: 1;">
                    <img src="assets/images/aide.jpg" alt="Bénévolat" 
                         style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
                </div>
            </div>
            
            <div style="margin-top: 3rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem; text-align: center;">L'effet "boule de neige"</h2>
                <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); text-align: center; max-width: 700px; margin: 0 auto 2rem;">
                    Démarrée sur Landunvez, notre action s'est étendue rapidement aux communes avoisinantes qui en ont 
                    fait la demande et a eu un effet "boule de neige" qui font que cette idée a été reprise par d'autres communes.
                </p>
                
                
            </div>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="section section-light">
    <div class="container">
        <div style="text-align: center; max-width: 600px; margin: 0 auto;">
            <h2 style="margin-bottom: 1rem;">Envie de nous rejoindre ?</h2>
            <p style="font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem;">
                Devenez acteur du changement dans votre commune
            </p>
            <a href="nous-rejoindre.php" class="btn btn-primary">Devenir bénévole</a>
        </div>
    </div>
</section>

</main>

<?php include 'includes/footer.php'; ?>
