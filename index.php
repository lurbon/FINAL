<?php
require_once 'includes/config.php';

// Récupérer les 3 dernières actualités
$stmt = $pdo->query("SELECT * FROM EPI_news WHERE published = 1 ORDER BY created_at DESC LIMIT 3");
$latest_news = $stmt->fetchAll();

// Recuperer les 3 prochaines sorties cinema
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM EPI_cinema WHERE published = 1 AND session_date >= ? ORDER BY session_date ASC LIMIT 3");
$stmt->execute([$today]);
$next_cinema = $stmt->fetchAll();

$page_title = "Accueil";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Bienvenue à Entraide Plus Iroise</h1>
        <p>Association d'entraide et de solidarité au service des personnes isolées depuis 2010</p>
		<p>sur les communes de</p>
		<p>Landunvez, Ploudalmézeau-Portsall, Lampaul-Ploudalmézeau, Saint-Pabu, Porspoder, Lanildut, Brélès, Plourin, Plouguin et Tréouergat</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
            <a href="nous-rejoindre.php" class="btn btn-primary">Devenir bénévole</a>
            <a href="contact.php" class="btn btn-outline">Nous contacter</a>
        </div>
    </div>
</section>

<!-- Présentation -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Qui sommes-nous ?</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <p style="font-size: 1.125rem; color: var(--text-secondary); line-height: 1.8;">
                Entraide Plus Iroise est une association créée en 2010 par un groupe de retraités dynamiques 
                souhaitant mettre en commun leur désir d'aider les personnes de Landunvez et des communes 
                avoisinantes qui se sentaient isolées.
            </p>
            <p style="font-size: 1.125rem; color: var(--text-secondary); line-height: 1.8; margin-top: 1rem;">
                Notre force ? L'entraide ! L'aidé d'aujourd'hui peut devenir le bénévole de demain.
            </p>
            <div style="margin-top: 2rem;">
                <a href="notre-histoire.php" class="btn btn-primary">En savoir plus</a>
            </div>
        </div>
    </div>
</section>

<!-- Nos missions -->
<section class="section section-light">
    <div class="container">
        <div class="section-title">
            <h2>Nos missions</h2>
        </div>
        <div class="cards-grid">
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; text-align: center; margin-bottom: 1rem;">🚗</div>
                    <h3 class="card-title" style="text-align: center;">Transport</h3>
                    <p class="card-text">
                        Accompagnement pour les rendez-vous médicaux, courses, démarches administratives...
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; text-align: center; margin-bottom: 1rem;">🤝</div>
                    <h3 class="card-title" style="text-align: center;">Aide à la personne</h3>
                    <p class="card-text">
                        Petits travaux, aide administrative, visites de courtoisie et soutien moral.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; text-align: center; margin-bottom: 1rem;">❤️</div>
                    <h3 class="card-title" style="text-align: center;">Lien social</h3>
                    <p class="card-text">
                        Création de liens entre les habitants, lutte contre l'isolement et la solitude.
                    </p>
                </div>
            </div>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="nos-missions.php" class="btn btn-primary">Découvrir toutes nos missions</a>
        </div>
    </div>
</section>

<!-- Actualités -->
<?php if (!empty($latest_news)): ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Dernières actualités</h2>
        </div>
        <div class="cards-grid">
            <?php foreach ($latest_news as $news): ?>
            <div class="card">
                <?php if ($news['image']): ?>
                    <img src="uploads/news/<?php echo htmlspecialchars($news['image']); ?>" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                         class="card-image">
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-date">
                        <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                    </div>
                    <h3 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                    <p class="card-text">
                        <?php 
                        $content = strip_tags($news['content']);
                        echo htmlspecialchars(substr($content, 0, 150)) . '...'; 
                        ?>
                    </p>
                    <a href="actualites.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">Lire la suite</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="actualites.php" class="btn btn-secondary">Voir toutes les actualités</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Prochaines sorties cinema -->
<?php if (!empty($next_cinema)): ?>
<section class="section section-light">
    <div class="container">
        <div class="section-title">
            <h2>Minibus - Cinéma de Saint-Renan</h2>
        </div>
        <div class="cards-grid">
            <?php
            $mois_court = ['jan','fev','mar','avr','mai','jun','jul','aou','sep','oct','nov','dec'];
            foreach ($next_cinema as $film): ?>
            <div class="card" style="display: flex; flex-direction: row; overflow: hidden;">
                <div style="width: 120px; flex-shrink: 0; position: relative; background: var(--background-dark);">
                    <?php if ($film['image']): ?>
                        <img src="uploads/cinema/<?php echo htmlspecialchars($film['image']); ?>"
                             alt="<?php echo htmlspecialchars($film['title']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                    <div style="position: absolute; top: 8px; left: 8px; background: var(--primary-color); color: white; border-radius: 6px; padding: 4px 8px; text-align: center; line-height: 1.1; box-shadow: var(--shadow-md);">
                        <span style="display: block; font-size: 1.5rem; font-weight: 700;"><?php echo date('d', strtotime($film['session_date'])); ?></span>
                        <span style="display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em;"><?php echo $mois_court[(int)date('m', strtotime($film['session_date'])) - 1]; ?></span>
                    </div>
                </div>
                <div class="card-content" style="flex: 1;">
                    <h3 class="card-title" style="font-size: 1.15rem;"><?php echo htmlspecialchars($film['title']); ?></h3>
                    <?php if ($film['director']): ?>
                        <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.25rem;">De <?php echo htmlspecialchars($film['director']); ?></div>
                    <?php endif; ?>
                    <?php if ($film['genre']): ?>
                        <span style="font-size: 0.7rem; background: var(--background-light); color: var(--primary-color); padding: 0.15rem 0.5rem; border-radius: 999px; font-weight: 600; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($film['genre']); ?></span>
                    <?php endif; ?>
                    <div style="margin-top: 0.75rem; font-size: 0.85rem;">
                        <?php if ($film['session_time']): ?>
                            <span style="font-weight: 600; color: var(--secondary-color);"><?php echo htmlspecialchars($film['session_time']); ?></span>
                        <?php endif; ?>
                        <?php if ($film['location']): ?>
                            <span style="color: var(--text-secondary);"> - <?php echo htmlspecialchars($film['location']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="cinema.php" class="btn btn-primary">Voir tout le programme</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Appel à l'action -->
<section class="section section-light">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto; text-align: center; padding: 3rem 1rem;">
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">Envie de donner quelques heures de votre temps ?</h2>
            <p style="font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 2rem;">
                Rejoignez notre équipe de bénévoles et participez à une aventure humaine enrichissante !
            </p>
            <a href="nous-rejoindre.php" class="btn btn-primary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                Devenir bénévole
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>