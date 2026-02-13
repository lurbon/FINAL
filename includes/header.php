<?php require_once __DIR__ . '/security-headers.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Entraide Plus Iroise - Association d'entraide et de solidarité">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Entraide Plus Iroise</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpg" href="assets/images/Logo-Entraide-Plus-Iroise.jpg">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS principal -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* ==================== HEADER MODERNE AVEC BORDURES ANIMÉES ==================== */
        .header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }

        .header .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #1a1a1a;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 25px 0;
            font-family: 'Poppins', sans-serif;
            z-index: 1002;
        }

        .header .logo img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .header .logo span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Navigation principale */
        .header nav {
            display: flex;
        }

        .header .nav-menu {
            display: flex;
            gap: 0;
            list-style: none;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        .header .nav-menu > li {
            position: relative;
        }

        .header .nav-menu > li > a {
            text-decoration: none;
            color: #4b5563 !important;
            padding: 30px 24px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
            position: relative;
            display: block;
            font-family: 'Inter', sans-serif;
            background: transparent !important;
            background-color: transparent !important;
        }

        /* Bordure animée au survol */
        .header .nav-menu > li > a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .header .nav-menu > li > a:hover {
            color: #1a1a1a !important;
            background: transparent !important;
            background-color: transparent !important;
        }

        .header .nav-menu > li > a:hover::after,
        .header .nav-menu > li > a.active::after {
            width: 100%;
        }

        /* Style pour la page active */
        .header .nav-menu > li > a.active {
            color: #1a1a1a !important;
            background: transparent !important;
            background-color: transparent !important;
        }

        /* Activer la bordure du parent si un enfant est actif */
        .header .dropdown:has(.dropdown-menu a.active) > a::after {
            width: 100%;
        }

        /* Bouton Membres */
        .header .membre-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            padding: 10px 24px !important;
            border-radius: 8px;
            margin-left: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .header .membre-link::after {
            display: none;
        }

        .header .membre-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Menu déroulant */
        .header .dropdown {
            position: relative;
        }

        .header .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            margin-top: 0;
            padding: 8px;
            list-style: none;
        }

        .header .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .header .dropdown-menu li {
            display: block;
        }

        .header .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            border-radius: 8px;
            color: #333 !important;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            background: transparent !important;
            background-color: transparent !important;
        }

        .header .dropdown-menu a:hover {
            background: linear-gradient(135deg, #f0f3ff 0%, #e8eeff 100%) !important;
            color: #667eea !important;
            padding-left: 20px;
        }

        .header .dropdown-menu a.active {
            background: linear-gradient(135deg, #f0f3ff 0%, #e8eeff 100%) !important;
            color: #667eea !important;
            border-left: 3px solid #667eea;
            padding-left: 20px;
        }

        /* Menu mobile toggle */
        .mobile-menu-toggle {
            display: none;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            position: relative;
            z-index: 1002;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .mobile-menu-toggle span {
            display: block;
            width: 28px;
            height: 3px;
            background: white;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .mobile-menu-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .mobile-menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }

        /* Overlay */
        .menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header-container {
                padding: 0 20px;
            }

            .header .nav-menu > li > a {
                padding: 30px 16px;
                font-size: 0.9rem;
            }
        }

        /* ==================== MOBILE UNIQUEMENT - ÉCRASE LE STYLE.CSS ==================== */
        @media (max-width: 768px) {
            /* Afficher le bouton hamburger */
            .mobile-menu-toggle {
                display: flex !important;
            }

            /* Menu mobile - Position fixe qui glisse */
            .header nav {
                position: fixed !important;
                top: 0 !important;
                right: -100% !important;
                left: auto !important;
                width: 85% !important;
                max-width: 350px !important;
                height: 100vh !important;
                background: white !important;
                box-shadow: -5px 0 20px rgba(0,0,0,0.1) !important;
                transition: right 0.3s ease !important;
                z-index: 1001 !important;
                overflow-y: auto !important;
                padding: 80px 20px 20px !important;
                display: block !important;
            }

            .header nav.active {
                right: 0 !important;
            }

            /* Menu en colonne sur mobile */
            .header .nav-menu {
                display: flex !important;
                flex-direction: column !important;
                gap: 0 !important;
                align-items: stretch !important;
                padding: 0 !important;
                position: static !important;
                background: transparent !important;
                box-shadow: none !important;
            }

            .header .nav-menu > li {
                width: 100%;
            }

            .header .nav-menu > li > a {
                padding: 15px 20px !important;
                border-bottom: 1px solid #e5e7eb;
            }

            .header .nav-menu > li > a::after {
                display: none;
            }

            /* Dropdowns mobiles */
            .header .dropdown-menu {
                position: static !important;
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
                box-shadow: none !important;
                padding: 0 0 0 20px !important;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease !important;
                background: transparent !important;
            }

            .header .dropdown.open .dropdown-menu {
                max-height: 500px;
            }

            .header .dropdown > a::before {
                content: '+';
                float: right;
                font-size: 1.5rem;
                font-weight: 300;
                transition: transform 0.3s ease;
            }

            .header .dropdown.open > a::before {
                content: '−';
            }

            /* Bouton Membres mobile */
            .header .membre-link {
                margin: 20px 0 0 0 !important;
                text-align: center;
            }

            .header .logo {
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay pour fermer le menu -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="assets/images/Logo-Entraide-Plus-Iroise.jpg" alt="Logo Entraide Plus Iroise">
                <span>Entraide Plus Iroise</span>
            </a>
            
            <!-- Bouton hamburger avec 3 barres -->
            <button class="mobile-menu-toggle" id="menuToggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav id="mainNav">
                <ul class="nav-menu">
                    <li class="dropdown">
                        <a href="index.php">L'association</a>
                        <ul class="dropdown-menu">
                            <li><a href="notre-histoire.php">Notre histoire</a></li>
                            <li><a href="nos-missions.php">Nos missions</a></li>
                            <li><a href="quelques-chiffres.php">Quelques chiffres</a></li>
                            <li><a href="les-membres.php">Les membres</a></li>
                        </ul>
                    </li>
                    <li><a href="actualites.php">News</a></li>
                    <li class="dropdown">
                        <a href="#">Médias</a>
                        <ul class="dropdown-menu">
                            <li><a href="photos.php">Photos</a></li>
                            <li><a href="presse.php">Presse</a></li>
                            <li><a href="videos.php">Vidéos</a></li>
                        </ul>
                    </li>
                    <li><a href="nous-rejoindre.php">Nous rejoindre</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="membre/login.php" class="membre-link">Membres</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        // Menu mobile
        (function() {
            'use strict';
            
            var menuToggle = document.getElementById('menuToggle');
            var mainNav = document.getElementById('mainNav');
            var overlay = document.getElementById('menuOverlay');

            // Toggle menu mobile
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    menuToggle.classList.toggle('active');
                    mainNav.classList.toggle('active');
                    overlay.classList.toggle('active');
                    
                    if (mainNav.classList.contains('active')) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            }

            // Fermer avec overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    menuToggle.classList.remove('active');
                    mainNav.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Dropdowns mobiles
            var dropdowns = document.querySelectorAll('.header .dropdown > a');
            dropdowns.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        var parent = this.parentElement;
                        
                        document.querySelectorAll('.header .dropdown').forEach(function(item) {
                            if (item !== parent) {
                                item.classList.remove('open');
                            }
                        });
                        
                        parent.classList.toggle('open');
                    }
                });
            });

            // Fermer menu sur clic lien
            var navLinks = document.querySelectorAll('.header .nav-menu a:not(.dropdown > a)');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        menuToggle.classList.remove('active');
                        mainNav.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });

            // Page active
            try {
                var currentPage = window.location.pathname.split('/').pop();
                if (currentPage) {
                    document.querySelectorAll('.header .nav-menu a').forEach(function(link) {
                        if (link.getAttribute('href') === currentPage) {
                            link.classList.add('active');
                        }
                    });
                }
            } catch (e) {
                console.error('Erreur détection page active:', e);
            }

            // Fermer au resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    menuToggle.classList.remove('active');
                    mainNav.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        })();
    </script>
</body>
</html>
