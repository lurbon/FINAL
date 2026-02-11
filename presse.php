<?php
require_once 'includes/config.php';
$press = $pdo->query("SELECT * FROM EPI_press ORDER BY article_date DESC, created_at DESC")->fetchAll();
$page_title = "Presse";
include 'includes/header.php';
?>

<style>
    .cards-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .card {
        max-width: 350px;
    }
    
    .card-image {
        height: 180px;
        object-fit: cover;
    }
    
    .card-content {
        padding: 1rem;
    }
    
    .card-title {
        font-size: 1rem;
        line-height: 1.4;
    }
</style>

<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Revue de presse</h1>
        <p>L'association dans les médias</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($press)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📰</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Aucun article pour le moment</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientôt !</p>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($press as $article): ?>
                    <div class="card">
                        <?php if ($article['image']): ?>
                            <img src="uploads/press/<?php echo htmlspecialchars($article['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                 class="card-image">
                        <?php else: ?>
                            <div class="card-image" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                                                        display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem;">
                                📰
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <?php if ($article['media']): ?>
                                <div style="color: var(--primary-color); font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($article['media']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($article['article_date']): ?>
                                <div class="card-date">
                                    <?php echo date('d/m/Y', strtotime($article['article_date'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                            
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
                                <?php if ($article['pdf_file']): ?>
                                    <a href="uploads/press/<?php echo htmlspecialchars($article['pdf_file']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="btn btn-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                        📄 Lire le PDF
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($article['link']): ?>
                                    <a href="<?php echo htmlspecialchars($article['link']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                        🔗 Article en ligne
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>