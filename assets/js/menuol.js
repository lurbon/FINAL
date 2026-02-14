/**
 * FICHIER DESACTIVE - Ce script n'est inclus dans aucune page.
 * Le menu mobile et les dropdowns sont geres par main.js (lignes 8-36)
 * et par le header.php inline.
 *
 * Conserve au cas ou, mais peut etre supprime en toute securite.
 */

/*
document.addEventListener('DOMContentLoaded', function() {

    // 1. GESTION DU MENU MOBILE
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
            if (navMenu.classList.contains('active')) {
                this.innerHTML = '\u2715';
            } else {
                this.innerHTML = '\u2630';
            }
        });
    }

    // 2. GESTION DES DROPDOWNS
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const dropdownLink = dropdown.querySelector('a');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');
        let closeTimeout;

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

        dropdownLink.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('open');
                    }
                });
                dropdown.classList.toggle('open');
            }
        });
    });

    // 3. FERMER LE MENU MOBILE EN CLIQUANT A L'EXTERIEUR
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-container') && navMenu.classList.contains('active')) {
            navMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            mobileMenuToggle.innerHTML = '\u2630';
        }
    });

    // 4. GESTION DU REDIMENSIONNEMENT
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
            });
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
                mobileMenuToggle.innerHTML = '\u2630';
            }
        }, 250);
    });

    // 5. EMPECHER LE LIEN PRINCIPAL DES DROPDOWNS DE REDIRIGER
    dropdowns.forEach(dropdown => {
        const mainLink = dropdown.querySelector('a');
        mainLink.addEventListener('click', function(e) {
            if (dropdown.querySelector('.dropdown-menu') && window.innerWidth <= 768) {
                e.preventDefault();
            }
        });
    });

});
*/
