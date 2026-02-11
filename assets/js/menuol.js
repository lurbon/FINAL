/**
 * GESTION DU MENU ET DES DROPDOWNS
 * Fichier : assets/js/menu.js
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. GESTION DU MENU MOBILE
    // ============================================
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
            
            // Changer l'icône du burger
            if (navMenu.classList.contains('active')) {
                this.innerHTML = '✕'; // Croix
            } else {
                this.innerHTML = '☰'; // Burger
            }
        });
    }
    
    // ============================================
    // 2. GESTION DES DROPDOWNS
    // ============================================
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropdownLink = dropdown.querySelector('a');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        let closeTimeout;
        
        // DESKTOP : Hover pour ouvrir/fermer avec délai
        dropdown.addEventListener('mouseenter', function() {
            if (window.innerWidth > 768) {
                clearTimeout(closeTimeout);
                this.classList.add('open');
            }
        });
        
        dropdown.addEventListener('mouseleave', function() {
            if (window.innerWidth > 768) {
                const self = this;
                closeTimeout = setTimeout(function() {
                    self.classList.remove('open');
                }, 150);
            }
        });
        
        // MOBILE : Click pour ouvrir/fermer
        dropdownLink.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                
                // Fermer tous les autres dropdowns
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('open');
                    }
                });
                
                // Toggle le dropdown actuel
                dropdown.classList.toggle('open');
            }
        });
    });
    
    // ============================================
    // 3. FERMER LE MENU MOBILE EN CLIQUANT À L'EXTÉRIEUR
    // ============================================
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-container') && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            mobileMenuToggle.innerHTML = '☰';
        }
    });
    
    // ============================================
    // 4. GESTION DU REDIMENSIONNEMENT
    // ============================================
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Fermer tous les dropdowns lors du redimensionnement
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
            });
            
            // Fermer le menu mobile si on passe en desktop
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                mobileMenuToggle.innerHTML = '☰';
            }
        }, 250);
    });
    
    // ============================================
    // 5. EMPÊCHER LE LIEN PRINCIPAL DES DROPDOWNS DE REDIRIGER
    // ============================================
    dropdowns.forEach(dropdown => {
        const mainLink = dropdown.querySelector('a');
        mainLink.addEventListener('click', function(e) {
            // Si le dropdown a un sous-menu et qu'on est sur mobile
            if (dropdown.querySelector('.dropdown-menu') && window.innerWidth <= 768) {
                e.preventDefault();
            }
        });
    });
    
});
