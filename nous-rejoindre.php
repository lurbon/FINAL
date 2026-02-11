<?php
require_once 'includes/config.php';
$page_title = "Nous rejoindre";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Devenez bénévole</h1>
        <p>Rejoignez notre équipe et faites la différence</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            
 <div style="margin-bottom: 3rem;">
    <img src="assets/images/rejoigneznous.jpeg"
         alt="Rejoignez-nous"
         style="
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
         ">
</div>
            <div style="background: var(--background-light); padding: 2.5rem; border-radius: var(--radius-lg); margin-bottom: 3rem;">
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem; text-align: center;">
                    Pourquoi devenir bénévole ?
                </h2>
                <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); text-align: center;">
                    Les bénévoles qui s'engagent à rendre service au sein de l'association Entraide Plus Iroise, 
                    acceptent d'intervenir de manière totalement bénévole, autant pour le temps qu'ils passent 
                    que pour les frais de voiture pour les déplacements.
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎯</div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Une mission utile</h3>
                    <p style="color: var(--text-secondary);">
                        Contribuez au bien-être des personnes isolées de votre commune
                    </p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Rencontres enrichissantes</h3>
                    <p style="color: var(--text-secondary);">
                        Créez des liens avec d'autres bénévoles et les personnes aidées
                    </p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⏰</div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Flexibilité</h3>
                    <p style="color: var(--text-secondary);">
                        Donnez selon vos disponibilités, quelques heures par semaine ou par mois
                    </p>
                </div>
            </div>
            
            <div style="background: white; padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); margin-bottom: 3rem;">
                <h2 style="color: var(--secondary-color); margin-bottom: 2rem;">Avantages fiscaux</h2>
                <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1rem;">
                    Une attestation vous sera fournie en fin d'année indiquant la dépense, au tarif fiscal de référence, 
                    correspondant au nombre de km parcourus pour les prestations fournies.
                </p>
                <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md); border-left: 4px solid var(--secondary-color);">
                    <p style="font-weight: 600; color: var(--text-primary);">
                        💰 Cette attestation vous permettra de solliciter une réduction d'impôt
                    </p>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                        color: white; padding: 2.5rem; border-radius: var(--radius-lg); margin-bottom: 3rem;">
                <h2 style="margin-bottom: 2rem; text-align: center;">Les conditions pour devenir bénévole</h2>
                
                <div style="display: grid; gap: 1.5rem;">
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; 
                                    display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 1.5rem;">✓</span>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.5rem;">Adhésion obligatoire</h4>
                            <p style="opacity: 0.9;">L'adhésion à l'association est obligatoire pour tous les bénévoles</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; 
                                    display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 1.5rem;">✓</span>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.5rem;">Convention de bénévolat</h4>
                            <p style="opacity: 0.9;">À l'inscription, vous recevrez une convention de bénévolat à signer</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: start;">
                        <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; 
                                    display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="font-size: 1.5rem;">✓</span>
                        </div>
                        <div>
                            <h4 style="margin-bottom: 0.5rem;">Documents requis</h4>
                            <p style="opacity: 0.9;">
                                Copie de votre carte d'identité, permis de conduire, carte grise du véhicule 
                                utilisé et attestation d'assurance
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="background: var(--background-light); padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 3rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.5rem;">🛡️</span>
                    Vous êtes couvert !
                </h3>
                <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary);">
                    Ces pièces sont nécessaires car toutes les missions confiées aux bénévoles sont couvertes 
                    par une assurance souscrite par l'association, aussi bien pour la protection des personnes 
                    que du véhicule.
                </p>
            </div>
            
            <div style="text-align: center; padding: 3rem 1rem; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl);">
                <h2 style="color: var(--primary-color); margin-bottom: 1rem; font-size: 2rem;">
                    Prêt à nous rejoindre ?
                </h2>
                <p style="font-size: 1.125rem; color: var(--text-secondary); margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Contactez-nous dès maintenant pour devenir bénévole et faire partie de cette belle aventure humaine !
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="contact.php" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                        Nous contacter
                    </a>
                    <a href="tel:0662487642" class="btn btn-secondary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                        📞 07.66.63.44.58
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    section div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
