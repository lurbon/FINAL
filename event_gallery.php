<?php
require_once 'includes/config.php';

// Récupérer l'ID de l'événement
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$event_id) {
    header('Location: photos.php');
    exit;
}

// Récupérer les informations de l'événement et de l'année
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        y.year,
        y.description as year_description
    FROM EPI_gallery_event e
    JOIN EPI_year y ON e.year_id = y.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: photos.php');
    exit;
}

// Récupérer toutes les photos de cet événement
$stmt = $pdo->prepare("
    SELECT * FROM EPI_gallery 
    WHERE event_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$event_id]);
$photos = $stmt->fetchAll();

$page_title = $event['name'] . " - Galerie";
include 'includes/header.php';
?>

<section class="hero" style="padding: 3rem 1rem;">
    <div class="hero-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="photos.php">Galerie</a>
            <span>›</span>
            <a href="photos.php#year-<?php echo $event['year']; ?>"><?php echo $event['year']; ?></a>
            <span>›</span>
            <span><?php echo htmlspecialchars($event['name']); ?></span>
        </div>
        
        <h1><?php echo htmlspecialchars($event['name']); ?></h1>
        
        <div class="event-meta">
            <?php if ($event['event_date']): ?>
                <span class="event-date">
                    📅 <?php echo date('d F Y', strtotime($event['event_date'])); ?>
                </span>
            <?php endif; ?>
            <span class="event-photo-count">
                📷 <?php echo count($photos); ?> photo<?php echo count($photos) > 1 ? 's' : ''; ?>
            </span>
        </div>
        
        <?php if ($event['description']): ?>
            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($photos)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📷</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Aucune photo pour cet événement</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">
                    <a href="photos.php" class="btn btn-primary">← Retour à la galerie</a>
                </p>
            </div>
        <?php else: ?>
            
            <!-- Galerie de photos avec Masonry -->
            <div class="photo-gallery">
                <?php foreach ($photos as $index => $photo): ?>
                    <div class="photo-item" 
                         onclick="openLightbox(<?php echo $index; ?>)"
                         data-index="<?php echo $index; ?>">
                        <img src="uploads/gallery/<?php echo htmlspecialchars($photo['image']); ?>" 
                             alt="<?php echo htmlspecialchars($photo['title'] ?: $event['name']); ?>"
                             loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'photo-error\'>Image indisponible</div>'">
                        <div class="photo-overlay">
                            <?php if ($photo['title']): ?>
                                <div class="photo-title"><?php echo htmlspecialchars($photo['title']); ?></div>
                            <?php endif; ?>
                            <div class="photo-action">👁️ Agrandir</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Bouton retour -->
            <div style="text-align: center; margin-top: 3rem;">
                <a href="photos.php#year-<?php echo $event['year']; ?>" class="btn btn-secondary">
                    ← Retour à <?php echo $event['year']; ?>
                </a>
            </div>
            
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div id="lightbox" class="lightbox" style="display: none;">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <button class="lightbox-prev" onclick="navigateLightbox(-1)">‹</button>
    <button class="lightbox-next" onclick="navigateLightbox(1)">›</button>
    
    <div class="lightbox-content">
        <img id="lightbox-image" src="" alt="">
        <div id="lightbox-caption" class="lightbox-caption"></div>
        <div id="lightbox-counter" class="lightbox-counter"></div>
    </div>
</div>

<style>
/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--primary-color);
    text-decoration: none;
    transition: opacity 0.2s;
}

.breadcrumb a:hover {
    opacity: 0.7;
}

.breadcrumb span {
    color: var(--text-secondary);
}

/* Meta événement */
.event-meta {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.event-date,
.event-photo-count {
    color: var(--text-secondary);
    font-size: 0.95rem;
}

.event-description {
    margin-top: 1rem;
    color: var(--text-secondary);
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

/* Galerie de photos - Style Masonry */
.photo-gallery {
    column-count: 4;
    column-gap: 1rem;
}

.photo-item {
    break-inside: avoid;
    margin-bottom: 1rem;
    position: relative;
    cursor: pointer;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: var(--background-light);
    transition: transform 0.3s, box-shadow 0.3s;
}

.photo-item:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.photo-item img {
    width: 100%;
    display: block;
    transition: transform 0.3s;
}

.photo-item:hover img {
    transform: scale(1.05);
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.3s;
}

.photo-item:hover .photo-overlay {
    opacity: 1;
}

.photo-title {
    color: white;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.photo-action {
    color: rgba(255,255,255,0.9);
    font-size: 0.85rem;
}

.photo-error {
    width: 100%;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    background: var(--background-light);
}

/* Lightbox */
.lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.95);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.lightbox-content {
    max-width: 90%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: var(--radius-lg);
    object-fit: contain;
}

.lightbox-caption {
    color: white;
    margin-top: 1rem;
    font-size: 1.1rem;
    text-align: center;
}

.lightbox-counter {
    color: rgba(255,255,255,0.6);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.lightbox-close {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    color: white;
    font-size: 2.5rem;
    cursor: pointer;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
    background: rgba(255,255,255,0.1);
    border: none;
    line-height: 1;
}

.lightbox-close:hover {
    background: rgba(255,255,255,0.25);
}

.lightbox-prev,
.lightbox-next {
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    color: white;
    font-size: 3rem;
    cursor: pointer;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
    background: rgba(255,255,255,0.1);
    border: none;
    line-height: 1;
    user-select: none;
}

.lightbox-prev:hover,
.lightbox-next:hover {
    background: rgba(255,255,255,0.25);
}

.lightbox-prev {
    left: 1.5rem;
}

.lightbox-next {
    right: 1.5rem;
}

/* Responsive */
@media (max-width: 992px) {
    .photo-gallery {
        column-count: 3;
    }
}

@media (max-width: 768px) {
    .photo-gallery {
        column-count: 1;
        column-gap: 0.75rem;
    }
    
    .photo-item {
        margin-bottom: 0.75rem;
    }
    
    .event-meta {
        gap: 1rem;
    }
    
    .lightbox-prev,
    .lightbox-next {
        width: 44px;
        height: 44px;
        font-size: 2rem;
    }
    
    .lightbox-prev {
        left: 0.5rem;
    }
    
    .lightbox-next {
        right: 0.5rem;
    }
    
    .lightbox-close {
        width: 44px;
        height: 44px;
        font-size: 2rem;
        top: 1rem;
        right: 1rem;
    }
}
</style>

<script>
// Données des photos pour la lightbox
const photos = <?php echo json_encode(array_map(function($p) {
    return [
        'src' => 'uploads/gallery/' . $p['image'],
        'title' => $p['title'] ?: ''
    ];
}, $photos)); ?>;

let currentIndex = 0;

function openLightbox(index) {
    currentIndex = index;
    updateLightbox();
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    currentIndex = (currentIndex + direction + photos.length) % photos.length;
    updateLightbox();
}

function updateLightbox() {
    const photo = photos[currentIndex];
    document.getElementById('lightbox-image').src = photo.src;
    document.getElementById('lightbox-caption').textContent = photo.title;
    document.getElementById('lightbox-counter').textContent = 
        (currentIndex + 1) + ' / ' + photos.length;
}

// Navigation au clavier
document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('lightbox');
    if (lightbox.style.display === 'flex') {
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            navigateLightbox(-1);
        } else if (e.key === 'ArrowRight') {
            navigateLightbox(1);
        }
    }
});

// Fermer en cliquant sur le fond
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
