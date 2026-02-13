<?php
require_once 'includes/config.php';

// Récupérer les membres avec un rôle de 1 à 4 depuis la table EPI_user
$stmt = $pdo->query("
    SELECT 
        ID,
        user_nicename as name,
        user_role as role,
        user_photo as photo,
        user_email as email,
        user_phone as phone,
        user_bio as bio
    FROM EPI_user 
    WHERE user_rang IN (1,2,3,4)
    ORDER BY CAST(user_rang AS UNSIGNED) ASC, user_nicename ASC
");
$members = $stmt->fetchAll();

$page_title = "Les membres du bureau";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Les membres du bureau</h1>
        <p>L'équipe qui fait vivre l'association</p>
    </div>
</section>

<!-- Membres -->
<section class="section">
    <div class="container">
        <?php if (empty($members)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Les membres seront bientôt présentés</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientôt pour découvrir notre équipe !</p>
            </div>
        <?php else: ?>
            <div style="max-width: 1000px; margin: 0 auto;">
                <?php foreach ($members as $index => $member): ?>
                    <?php 
                    $isEven = $index % 2 === 0;
                    $colors = [
                        'var(--primary-color)',
                        'var(--secondary-color)',
                        '#f59e0b',
                        '#8b5cf6'
                    ];
                    $color = $colors[$index % count($colors)];
                    ?>
                    
                    <div style="margin-bottom: 3rem;">
                        <div style="display: grid; grid-template-columns: <?php echo $isEven ? '1fr 2fr' : '2fr 1fr'; ?>; 
                                    gap: 3rem; align-items: center; background: white; padding: 2rem; 
                                    border-radius: var(--radius-xl); box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                                    border: 2px solid #e0e0e0;">
                            
                            <div style="order: <?php echo $isEven ? '1' : '2'; ?>;">
                                <?php if ($member['photo'] && file_exists('uploads/members/' . $member['photo'])): ?>
                                    <img src="uploads/members/<?php echo htmlspecialchars($member['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($member['name']); ?>"
                                         style="width: 100%; aspect-ratio: 1; object-fit: cover; 
                                                border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                                <?php else: ?>
                                    <div style="width: 100%; aspect-ratio: 1; background: linear-gradient(135deg, <?php echo $color; ?>, <?php echo $color; ?>dd); 
                                                border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; 
                                                box-shadow: var(--shadow-md);">
                                        <span style="font-size: 5rem; color: white; opacity: 0.9;">👤</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="order: <?php echo $isEven ? '2' : '1'; ?>;">
                                <div style="color: #8BC34A; font-weight: 700; 
                                            font-size: 1.5rem; margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars($member['role']); ?>
                                </div>
                                
                                <h2 style="color: var(--text-primary); margin-bottom: 1rem; font-size: 2rem;">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </h2>
                                
                                <?php if ($member['bio']): ?>
                                    <p style="font-size: 1.125rem; line-height: 1.8; color: var(--text-secondary); margin-bottom: 1.5rem;">
                                        <?php echo nl2br(htmlspecialchars($member['bio'])); ?>
                                    </p>
                                <?php endif; ?>
                                
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Section rejoindre -->
<section class="section section-light">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto; text-align: center; padding: 3rem 1rem;">
            <h2 style="font-size: 2.5rem; margin-bottom: 1.5rem;">Envie de vous engager ?</h2>
            <p style="font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 2rem; line-height: 1.8;">
                Notre association est toujours à la recherche de bénévoles motivés pour renforcer notre équipe 
                et étendre nos actions d'entraide.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="nous-rejoindre.php" class="btn btn-primary" style="font-size: 1.125rem;">
                    Devenir bénévole
                </a>
                <a href="contact.php" class="btn btn-secondary" style="font-size: 1.125rem;">
                    Nous contacter
                </a>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    section div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    section div[style*="order:"] {
        order: 0 !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
