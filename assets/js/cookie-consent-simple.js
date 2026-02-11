// Gestion du bandeau de cookies - Version simplifiée
(function() {
    'use strict';
    
    // Vérifier si l'utilisateur a déjà fait un choix
    const cookieConsent = localStorage.getItem('cookieConsent');
    
    if (!cookieConsent) {
        // Afficher le bandeau après 1 seconde
        setTimeout(showCookieBanner, 1000);
    }
    
    function showCookieBanner() {
        // Créer le bandeau
        const banner = document.createElement('div');
        banner.id = 'cookie-banner';
        banner.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#fff;box-shadow:0 -4px 20px rgba(0,0,0,0.15);z-index:9999;padding:1.5rem;border-top:3px solid #2563eb;';
        
        banner.innerHTML = `
            <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:2rem;flex-wrap:wrap;">
                <div style="flex:1;min-width:300px;">
                    <p style="margin:0;font-size:0.95rem;line-height:1.6;color:#111827;">
                        🍪 Nous utilisons des cookies pour améliorer votre expérience sur notre site. 
                        En continuant à naviguer, vous acceptez notre utilisation des cookies.
                        <a href="politique-confidentialite.php" style="color:#2563eb;text-decoration:underline;">En savoir plus</a>
                    </p>
                </div>
                <div style="display:flex;gap:1rem;flex-shrink:0;">
                    <button id="cookie-accept" style="padding:0.75rem 1.5rem;border-radius:6px;border:none;background:#2563eb;color:#fff;cursor:pointer;font-size:0.95rem;white-space:nowrap;">Accepter</button>
                    <button id="cookie-refuse" style="padding:0.75rem 1.5rem;border-radius:6px;border:none;background:#6b7280;color:#fff;cursor:pointer;font-size:0.95rem;white-space:nowrap;">Refuser</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
        
        // Ajouter les événements
        document.getElementById('cookie-accept').addEventListener('click', function() {
            setCookieConsent('accepted');
            hideCookieBanner();
        });
        
        document.getElementById('cookie-refuse').addEventListener('click', function() {
            setCookieConsent('refused');
            hideCookieBanner();
        });
    }
    
    function setCookieConsent(value) {
        localStorage.setItem('cookieConsent', value);
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        
        if (value === 'accepted') {
            // Activer Google Analytics, Facebook Pixel, etc. ici
            console.log('Cookies acceptés');
        } else {
            console.log('Cookies refusés');
        }
    }
    
    function hideCookieBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.remove();
        }
    }
})();
