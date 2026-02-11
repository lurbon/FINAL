<?php
$page_title = "Mentions légales";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Mentions légales</h1>
        <p>Informations légales de l'association</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            
            <!-- Note importante -->
         
            
            <!-- Informations légales -->
            <div style="background: white; padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                <h2 style="color: var(--primary-color); margin-bottom: 2rem;">Informations sur l'association</h2>
                
                <div style="line-height: 1.8; color: var(--text-primary);">
                    <p style="margin-bottom: 1.5rem;">
                        <strong>Entraide Plus Iroise</strong> est une association loi 1901
                    </p>
                    
                    <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                        <p style="margin-bottom: 0.75rem;">
                            <strong>Numéro d'enregistrement :</strong> W291004431
                        </p>
                        <p style="margin-bottom: 0;">
                            <strong>SIRET :</strong> 798 157 194 00019
                        </p>
                    </div>
                    
                    <h3 style="color: var(--secondary-color); margin: 2rem 0 1rem 0;">Adresse de l'association</h3>
                    <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md);">
                        <p style="margin: 0; line-height: 1.8;">
                            Mairie de Landunvez<br>
                            1 Place de l'Église<br>
                            29840 Landunvez
                        </p>
                    </div>
                    
                    <h3 style="color: var(--secondary-color); margin: 2rem 0 1rem 0;">Contact</h3>
                    <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md);">
                        <p style="margin-bottom: 0.75rem;">
                            <strong>Email :</strong> 
                            <a href="mailto:entraideplusiroise@gmail.com" style="color: var(--primary-color);">
                                entraideplusiroise@gmail.com
                            </a>
                        </p>
                        <p style="margin: 0;">
                            <strong>Téléphone :</strong> 
                            <a href="tel:0662487642" style="color: var(--primary-color);">
                                07.66.63.44.58
                            </a>
                        </p>
                    </div>
                    
                    <h3 style="color: var(--secondary-color); margin: 2rem 0 1rem 0;">Hébergement du site</h3>
                    <div style="background: var(--background-light); padding: 1.5rem; border-radius: var(--radius-md);">
                        <p style="margin: 0;">
                            Ce site est hébergé par OVH
							<br>2 RUE KELLERMANN 59100 ROUBAIX
                        </p>
                    </div>
                    
                    <h3 style="color: var(--secondary-color); margin: 2rem 0 1rem 0;">Propriété intellectuelle</h3>
                    <p>
                        L'ensemble du contenu de ce site (textes, images, vidéos) est la propriété de l'association 
                        Entraide Plus Iroise, sauf mention contraire. Toute reproduction, même partielle, est interdite 
                        sans l'autorisation préalable de l'association.
                    </p>
                    
                    <h3 style="color: var(--secondary-color); margin: 2rem 0 1rem 0;">Données personnelles</h3>
                    <p>
                        Les informations recueillies via le formulaire de contact sont destinées uniquement à l'usage 
                        interne de l'association. Conformément à la loi « Informatique et Libertés » et au RGPD, 
                        vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles. 
                        Pour exercer ce droit, vous pouvez nous contacter à l'adresse : 
                        <a href="mailto:entraideplusiroise@gmail.com" style="color: var(--primary-color);">
                            entraideplusiroise@gmail.com
                        </a>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    .hero {
        padding: 2rem 1rem !important;
    }
    
    section .container > div > div {
        padding: 1.5rem !important;
    }
    
    h2 {
        font-size: 1.5rem !important;
    }
    
    h3 {
        font-size: 1.2rem !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
