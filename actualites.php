<?php
require_once 'includes/config.php';

// Creer la table cinema si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS EPI_cinema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    director VARCHAR(150),
    genre VARCHAR(100),
    duration INT,
    session_date DATE NOT NULL,
    session_time VARCHAR(10),
    location VARCHAR(255),
    published BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Récupérer une actualité spécifique si ID fourni
$single_news = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM EPI_news WHERE id = ? AND published = 1");
    $stmt->execute([$_GET['id']]);
    $single_news = $stmt->fetch();
}

// Si pas d'actualité spécifique, récupérer toutes les actualités
if (!$single_news) {
    $stmt = $pdo->query("SELECT * FROM EPI_news WHERE published = 1 ORDER BY created_at DESC");
    $all_news = $stmt->fetchAll();

    // Recuperer les prochaines sorties cinema
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM EPI_cinema WHERE published = 1 AND session_date >= ? ORDER BY session_date ASC, session_time ASC LIMIT 6");
    $stmt->execute([$today]);
    $upcoming_cinema = $stmt->fetchAll();
}

$page_title = $single_news ? $single_news['title'] : "Actualités";
include 'includes/header.php';
?>

<?php if ($single_news): ?>
    <!-- Vue d'une actualité -->
    <section class="hero" style="padding: 4rem 1rem;">
        <div class="hero-content">
            <p style="font-size: 0.875rem; opacity: 0.9; margin-bottom: 1rem;">
                <?php echo date('d/m/Y', strtotime($single_news['created_at'])); ?>
            </p>
            <h1><?php echo htmlspecialchars($single_news['title']); ?></h1>
        </div>
    </section>
    
    <section class="section">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto;">
                <?php if ($single_news['image']): ?>
                    <img src="uploads/news/<?php echo htmlspecialchars($single_news['image']); ?>" 
                         alt="<?php echo htmlspecialchars($single_news['title']); ?>"
                         style="width: 100%; border-radius: var(--radius-lg); margin-bottom: 2rem;">
                <?php endif; ?>
                
                <div style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary);">
                    <?php echo nl2br(htmlspecialchars($single_news['content'])); ?>
                </div>
                
                <div style="margin-top: 3rem; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                    <a href="actualites.php" class="btn btn-primary">Retour aux actualités</a>
                </div>
            </div>
        </div>
    </section>
    
<?php else: ?>
    <!-- Liste des actualités -->
    <section class="hero" style="padding: 4rem 1rem;">
        <div class="hero-content">
            <h1>Nos actualités</h1>
            <p>Suivez les dernières nouvelles de l'association</p>
        </div>
    </section>
    
    <section class="section">
        <div class="container">
            <!-- Prochaines sorties cinema -->
            <?php if (!empty($upcoming_cinema)): ?>           


		   <div style="margin-bottom: 3rem;">
           <h2 style="margin: 0 0 1rem 0; color: var(--primary-color);">Minibus - Cinema - Saint-Renan</h2>

           </div>				
						
						
				    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; align-items: start;">

                        <?php foreach ($upcoming_cinema as $film):
                            $mois_court = ['jan','fev','mar','avr','mai','jun','jul','aou','sep','oct','nov','dec'];
                        ?>
                            <div class="card" style="display: flex; flex-direction: row; overflow: hidden;">
                                <div style="width: 90px; flex-shrink: 0; position: relative; background: var(--background-dark);">
                                    <?php if ($film['image']): ?>
                                        <img src="uploads/cinema/<?php echo htmlspecialchars($film['image']); ?>"
                                             alt="<?php echo htmlspecialchars($film['title']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php endif; ?>
                                    <div style="position: absolute; top: 4px; left: 4px; background: var(--primary-color); color: white; border-radius: 4px; padding: 2px 6px; text-align: center; line-height: 1.1;">
                                        <span style="display: block; font-size: 1.1rem; font-weight: 700;"><?php echo date('d', strtotime($film['session_date'])); ?></span>
                                        <span style="display: block; font-size: 0.55rem; text-transform: uppercase;"><?php echo $mois_court[(int)date('m', strtotime($film['session_date'])) - 1]; ?></span>
                                </div>
                                </div>
                                <div style="padding: 0.75rem; flex: 1;">
                                    <h4 style="margin: 0 0 0.25rem; font-size: 0.95rem; line-height: 1.3;"><?php echo htmlspecialchars($film['title']); ?></h4>
                                    <?php if ($film['director']): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.25rem;">De <?php echo htmlspecialchars($film['director']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($film['genre']): ?>
                                        <span style="font-size: 0.65rem; background: var(--background-light); color: var(--primary-color); padding: 0.1rem 0.4rem; border-radius: 999px; font-weight: 600;"><?php echo htmlspecialchars($film['genre']); ?></span>
                                    <?php endif; ?>
                                    <div style="margin-top: 0.4rem; font-size: 0.75rem;">
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
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="cinema.php" class="btn btn-primary">🎬 Voir tout le programme</a>
                    </div>

                <h2 style="margin: 2rem 0 1rem 0; color: var(--primary-color);">Actualites</h2>
            <?php endif; ?>

            <?php if (empty($all_news)): ?>
                <div style="text-align: center; padding: 4rem 1rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📰</div>
                    <h2 style="color: var(--text-secondary); font-weight: 400;">Aucune actualité pour le moment</h2>
                    <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientôt pour découvrir nos dernières nouvelles !</p>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($all_news as $news): ?>
                        <div class="card">
                            <?php if ($news['image']): ?>
                                <img src="uploads/news/<?php echo htmlspecialchars($news['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($news['title']); ?>" 
                                     class="card-image">
                            <?php else: ?>
                                <div class="card-image" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                                                                display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem;">
                                    📰
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-content">
                                <div class="card-date">
                                    <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                                <p class="card-text">
                                    <?php 
                                    $content = strip_tags($news['content']);
                                    echo htmlspecialchars(substr($content, 0, 200)) . '...'; 
                                    ?>
                                </p>
                                <a href="actualites.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
                                    Lire la suite
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>