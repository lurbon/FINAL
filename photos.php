<?php
require_once 'includes/config.php';

// Récupérer toutes les années avec leurs événements
$sql = "
    SELECT 
        y.id as year_id,
        y.year,
        y.description as year_description,
        e.id as event_id,
        e.name as event_name,
        e.description as event_description,
        e.event_date,
        e.cover_image,
        COUNT(g.id) as photo_count
    FROM EPI_year y
    LEFT JOIN EPI_gallery_event e ON y.id = e.year_id
    LEFT JOIN EPI_gallery g ON e.id = g.event_id
    GROUP BY y.id, e.id
    ORDER BY y.year DESC, e.event_date DESC
";

$results = $pdo->query($sql)->fetchAll();

// Organiser les données : années > événements
$years = [];
foreach ($results as $row) {
    $year = $row['year'];
    
    if (!isset($years[$year])) {
        $years[$year] = [
            'id' => $row['year_id'],
            'year' => $year,
            'description' => $row['year_description'],
            'events' => [],
            'total_photos' => 0
        ];
    }
    
    if ($row['event_id']) {
        $years[$year]['events'][] = [
            'id' => $row['event_id'],
            'name' => $row['event_name'],
            'description' => $row['event_description'],
            'date' => $row['event_date'],
            'cover_image' => $row['cover_image'],
            'photo_count' => $row['photo_count']
        ];
        $years[$year]['total_photos'] += $row['photo_count'];
    }
}

$page_title = "Galerie photos";
include 'includes/header.php';
?>

<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>📸 Galerie photos</h1>
        <p>Revivez nos moments en images, année par année</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($years)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📷</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Galerie en cours de création</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientôt pour découvrir nos photos !</p>
            </div>
        <?php else: ?>
            
            <!-- Navigation par années -->
            <div class="years-timeline">
                <?php foreach ($years as $year_data): ?>
                    <div class="year-section" id="year-<?php echo $year_data['year']; ?>">
                        
                        <!-- En-tête de l'année -->
                        <div class="year-header">
                            <div class="year-badge"><?php echo $year_data['year']; ?></div>
                            <div class="year-info">
                             
                                <?php if ($year_data['description']): ?>
                                    <p><?php echo htmlspecialchars($year_data['description']); ?></p>
                                <?php endif; ?>
                                <span class="year-stats">
                                    <?php echo count($year_data['events']); ?> événement(s) • 
                                    <?php echo $year_data['total_photos']; ?> photo(s)
                                </span>
                            </div>
                        </div>

                        <!-- Grille des événements -->
                        <?php if (!empty($year_data['events'])): ?>
                            <div class="events-grid">
                                <?php foreach ($year_data['events'] as $event): ?>
                                    <a href="event_gallery.php?id=<?php echo $event['id']; ?>" class="event-card">
                                        <div class="event-card-image">
                                            <?php if ($event['cover_image']): ?>
                                                <img src="uploads/gallery/<?php echo htmlspecialchars($event['cover_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($event['name']); ?>"
                                                     loading="lazy"
                                                     onerror="this.parentElement.innerHTML='<div class=\'event-placeholder\'>📷</div>'">
                                            <?php else: ?>
                                                <div class="event-placeholder">📷</div>
                                            <?php endif; ?>
                                            <div class="event-card-overlay">
                                                <span class="event-card-action">Voir les photos</span>
                                                <span class="event-card-count"><?php echo $event['photo_count']; ?> photo<?php echo $event['photo_count'] > 1 ? 's' : ''; ?></span>
                                            </div>
                                        </div>
                                        <div class="event-card-content">
                                            <h3><?php echo htmlspecialchars($event['name']); ?></h3>
                                            <?php if ($event['date']): ?>
                                                <div class="event-card-date">
                                                    📅 <?php echo date('d/m/Y', strtotime($event['date'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($event['description']): ?>
                                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-events">Aucun événement pour cette année</p>
                        <?php endif; ?>
                        
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<style>
/* Timeline des années */
.years-timeline {
    position: relative;
    padding-left: 2rem;
}

.years-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 3rem;
    bottom: 3rem;
    width: 3px;
    background: linear-gradient(to bottom, var(--primary-color), var(--primary-color) 50%, transparent);
}

/* Section année */
.year-section {
    margin-bottom: 4rem;
    position: relative;
}

.year-section::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 2.5rem;
    width: 15px;
    height: 15px;
    background: var(--primary-color);
    border: 4px solid white;
    border-radius: 50%;
    box-shadow: 0 0 0 3px var(--primary-color);
    z-index: 1;
}

/* En-tête année */
.year-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
}

.year-badge {
    font-size: 3rem;
    font-weight: 800;
    color: var(--primary-color);
    line-height: 1;
    min-width: 120px;
    text-align: center;
}

.year-info {
    flex: 1;
}

.year-info h2 {
    font-size: 2rem;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
}

.year-info p {
    color: var(--text-secondary);
    margin: 0 0 0.75rem 0;
}

.year-stats {
    display: inline-block;
    color: var(--text-secondary);
    font-size: 0.9rem;
    padding: 0.4rem 1rem;
    background: var(--background-light);
    border-radius: 999px;
}

/* Grille des événements */
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.event-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all 0.3s;
    text-decoration: none;
    display: block;
}

.event-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-xl);
}

.event-card-image {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: var(--background-light);
}

.event-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s;
}

.event-card:hover .event-card-image img {
    transform: scale(1.08);
}

.event-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    color: var(--text-secondary);
    background: linear-gradient(135deg, var(--background-light) 0%, #e5e7eb 100%);
}

.event-card-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 60%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1.25rem;
    opacity: 0;
    transition: opacity 0.3s;
}

.event-card:hover .event-card-overlay {
    opacity: 1;
}

.event-card-action {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.event-card-count {
    color: rgba(255,255,255,0.8);
    font-size: 0.85rem;
}

.event-card-content {
    padding: 1.25rem;
}

.event-card-content h3 {
    font-size: 1.15rem;
    margin: 0 0 0.5rem 0;
    color: var(--text-primary);
}

.event-card-date {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
}

.event-card-content p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.no-events {
    text-align: center;
    color: var(--text-secondary);
    padding: 2rem;
    font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
    .years-timeline {
        padding-left: 1rem;
    }
    
    .years-timeline::before {
        left: 0;
    }
    
    .year-section::before {
        left: -1rem;
        width: 10px;
        height: 10px;
        border-width: 3px;
    }
    
    .year-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
    }
    
    .year-badge {
        font-size: 2rem;
        min-width: auto;
    }
    
    .year-info h2 {
        font-size: 1.5rem;
    }
    
    .events-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .event-card-image {
        height: 160px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
