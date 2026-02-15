<?php
require_once 'includes/config.php';
$page_title = "Quelques chiffres";

// Récupérer les données depuis la table EPI_chiffre (Type='Global' pour les indicateurs principaux)
try {
    $stmt = $pdo->prepare("SELECT KPI, Valeur FROM EPI_chiffre WHERE Type = 'Global' ");
    $stmt->execute();
    $chiffres_globaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des chiffres globaux: " . $e->getMessage());
    $chiffres_globaux = [];
}

// Créer un tableau associatif pour faciliter l'accès aux valeurs
$kpi = [];
foreach($chiffres_globaux as $chiffre) {
    $kpi[$chiffre['KPI']] = $chiffre['Valeur'];
}

// Récupérer les détails des interventions depuis la table EPI_chiffre (Type='Détail')
try {
    $stmt = $pdo->prepare("SELECT KPI, Valeur, Icone FROM EPI_chiffre WHERE Type = 'Détail' ");
    $stmt->execute();
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Debug: afficher le nombre de résultats
    error_log("Nombre de détails récupérés: " . count($details));
    if(count($details) > 0) {
        error_log("Premier détail: " . print_r($details[0], true));
    }
} catch(PDOException $e) {
    error_log("Erreur lors de la récupération des détails: " . $e->getMessage());
    $details = [];
}

include 'includes/header.php';
?>

<main>
<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Quelques chiffres</h1>
        <p>L'impact de notre action en chiffres (Au 31/12/2025)</p>
    </div>
</section>

<!-- Statistiques principales -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 4rem; max-width: 1200px; margin-left: auto; margin-right: auto;">
            <?php
            // Palette de couleurs pour les cadres globaux
            $couleurs_globales = [
                ['primary' => 'var(--primary-color)', 'light' => 'var(--primary-light)'],
                ['primary' => 'var(--secondary-color)', 'light' => 'var(--secondary-light)'],
                ['primary' => 'var(--secondary-color)', 'light' => 'var(--secondary-light)'],
                ['primary' => '#f59e0b', 'light' => '#fbbf24'],
                ['primary' => '#8b5cf6', 'light' => '#a78bfa'],
                ['primary' => '#8b5cf6', 'light' => '#a78bfa'],
                ['primary' => '#10b981', 'light' => '#34d399'],
                ['primary' => '#ef4444', 'light' => '#f87171']
            ];
            
            $index = 0;
            foreach($chiffres_globaux as $chiffre):
                $kpi_name = $chiffre['KPI'];
                $valeur = intval($chiffre['Valeur']);
                $couleur = $couleurs_globales[$index % count($couleurs_globales)];
                $index++;
            ?>
            <div style="background: linear-gradient(135deg, <?php echo $couleur['primary']; ?>, <?php echo $couleur['light']; ?>); 
                        color: white; padding: 1.25rem 1rem; border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-lg);">
                <div class="counter" data-target="<?php echo $valeur; ?>" style="font-size: 2rem; font-weight: 800; margin-bottom: 0.25rem;">0</div>
                <h3 style="color: white; font-size: 0.875rem; margin: 0; line-height: 1.3;"><?php echo htmlspecialchars($kpi_name); ?></h3>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Détails des interventions -->
<section class="section section-light">
    <div class="container">
        <div class="section-title">
            <h2>Nos interventions en détail</h2>
        </div>
        
        <div style="max-width: 900px; margin: 0 auto;">
            <!-- Debug: afficher le nombre de résultats -->
            
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                <?php
                // Palette de couleurs pour assigner dynamiquement
                $couleurs = [
                    'var(--primary-color)',
                    'var(--secondary-color)',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6',
                    '#10b981',
                    '#06b6d4',
                    '#f97316'
                ];
                
                // Icône par défaut si non définie dans la base
                $icone_defaut = '📊';
                
                // Afficher les cartes dynamiquement
                $index = 0;
                foreach($details as $detail):
                    $kpi_name = $detail['KPI'];
                    $valeur = intval($detail['Valeur']);
                    
                    // Récupérer l'icône depuis la base de données ou utiliser l'icône par défaut
                    $icone = isset($detail['Icone']) && !empty($detail['Icone']) ? $detail['Icone'] : $icone_defaut;
                    
                    // Assigner automatiquement une couleur basée sur l'index
                    $couleur = $couleurs[$index % count($couleurs)];
                    $index++;
                ?>
                <div style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: <?php echo $couleur; ?>; color: white; width: 60px; height: 60px; 
                                    border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                            <?php echo $icone; ?>
                        </div>
                        <h3 style="color: <?php echo $couleur; ?>; margin: 0;"><?php echo htmlspecialchars($kpi_name); ?></h3>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: 700; color: <?php echo $couleur; ?>; margin-bottom: 0.5rem;">
                        <span class="counter" data-target="<?php echo $valeur; ?>">0</span>+
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>


<!-- Citation -->
<section class="section section-light">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto; text-align: center;">
            <div style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;">"</div>
            <p style="font-size: 1.5rem; line-height: 1.6; color: var(--text-primary); font-style: italic; margin-bottom: 2rem;">
                Ces chiffres représentent avant tout des histoires humaines, des sourires retrouvés 
                et des liens tissés au sein de notre communauté.
            </p>
            <div style="height: 3px; width: 60px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); 
                        margin: 0 auto; border-radius: 2px;"></div>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="section">
    <div class="container">
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                    color: white; padding: 3rem; border-radius: var(--radius-xl); text-align: center;">
            <h2 style="font-size: 2rem; margin-bottom: 1rem; color: white;">Envie de faire partie de ces chiffres ?</h2>
            <p style="font-size: 1.125rem; margin-bottom: 2rem; opacity: 0.95;">
                Rejoignez-nous et contribuez à faire grandir notre impact positif
            </p>
            <a href="nous-rejoindre.php" class="btn btn-outline" style="font-size: 1.125rem;">
                Devenir bénévole
            </a>
        </div>
    </div>
</section>

</main>

<style>
/* Grille responsive pour les cartes de statistiques */
@media (min-width: 1024px) {
    section div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (min-width: 640px) and (max-width: 1023px) {
    section div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 639px) {
    section div[style*="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    section div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script nonce="<?php echo csp_nonce(); ?>">
(function() {
    'use strict';
    
    console.log('Script compteurs chargé');
    
    // Formater les nombres avec séparateur de milliers
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
    
    // Fonction pour animer un compteur
    function animateCounter(counter) {
        const target = parseInt(counter.getAttribute('data-target'));
        console.log('Animation compteur, target:', target);
        
        const duration = 2000;
        const startTime = Date.now();
        
        function animate() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(progress * target);
            
            counter.textContent = formatNumber(current);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                counter.textContent = formatNumber(target);
                console.log('Animation terminée:', formatNumber(target));
            }
        }
        
        animate();
    }
    
    // Démarrer quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCounters);
    } else {
        initCounters();
    }
    
    function initCounters() {
        console.log('Initialisation des compteurs');
        const counters = document.querySelectorAll('.counter');
        console.log('Nombre de compteurs trouvés:', counters.length);
        
        counters.forEach((counter, index) => {
            console.log('Compteur', index, 'data-target:', counter.getAttribute('data-target'));
            // Petit délai pour chaque compteur
            setTimeout(() => animateCounter(counter), index * 100);
        });
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
