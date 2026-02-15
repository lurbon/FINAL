<?php
require_once 'includes/config.php';

// Creer la table si elle n'existe pas
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

$today = date('Y-m-d');

// Films a venir
$stmt = $pdo->prepare("SELECT * FROM EPI_cinema WHERE published = 1 AND session_date >= ? ORDER BY session_date ASC, session_time ASC");
$stmt->execute([$today]);
$upcoming = $stmt->fetchAll();

// Films passes (les 3 derniers mois)
$three_months_ago = date('Y-m-d', strtotime('-3 months'));
$stmt = $pdo->prepare("SELECT * FROM EPI_cinema WHERE published = 1 AND session_date < ? AND session_date >= ? ORDER BY session_date DESC");
$stmt->execute([$today, $three_months_ago]);
$past = $stmt->fetchAll();

// Grouper a venir par mois
function month_label_fr($date) {
    $mois = ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'];
    $m = (int)date('m', strtotime($date)) - 1;
    $y = date('Y', strtotime($date));
    return ucfirst($mois[$m]) . ' ' . $y;
}

function day_label_fr($date) {
    $jours = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
    $mois = ['janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'];
    $ts = strtotime($date);
    $j = $jours[(int)date('w', $ts)];
    $d = date('j', $ts);
    $m = $mois[(int)date('m', $ts) - 1];
    return ucfirst($j) . ' ' . $d . ' ' . $m;
}

$upcoming_by_month = [];
foreach ($upcoming as $film) {
    $mk = date('Y-m', strtotime($film['session_date']));
    $ml = month_label_fr($film['session_date']);
    if (!isset($upcoming_by_month[$mk])) {
        $upcoming_by_month[$mk] = ['label' => $ml, 'films' => []];
    }
    $upcoming_by_month[$mk]['films'][] = $film;
}

$past_by_month = [];
foreach ($past as $film) {
    $mk = date('Y-m', strtotime($film['session_date']));
    $ml = month_label_fr($film['session_date']);
    if (!isset($past_by_month[$mk])) {
        $past_by_month[$mk] = ['label' => $ml, 'films' => []];
    }
    $past_by_month[$mk]['films'][] = $film;
}

$page_title = "Sorties Cinema";
include 'includes/header.php';
?>

<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Sorties Cinéma</h1>
        <p>Notre programme de sorties cinéma pour les adhérents</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (empty($upcoming) && empty($past)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">F</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Programme en cours de préparation</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientot pour découvrir nos prochaines sorties cinéma !</p>
            </div>
        <?php else: ?>

            <!-- Prochaines seances -->
            <?php if (!empty($upcoming_by_month)): ?>
                <div class="cinema-section-title">
                    <h2>Prochaines séances</h2>
                    <span class="cinema-count"><?php echo count($upcoming); ?> film<?php echo count($upcoming) > 1 ? 's' : ''; ?> à venir</span>
                </div>

                <?php foreach ($upcoming_by_month as $month): ?>
                    <div class="cinema-month">
                        <div class="cinema-month-header"><?php echo htmlspecialchars($month['label']); ?></div>
                        <div class="cinema-films-grid">
                            <?php foreach ($month['films'] as $film): ?>
                                <div class="cinema-card">
                                    <div class="cinema-card-poster">
                                        <?php if ($film['image']): ?>
                                            <img src="uploads/cinema/<?php echo htmlspecialchars($film['image']); ?>"
                                                 alt="<?php echo htmlspecialchars($film['title']); ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="cinema-card-no-poster">
                                                <span>F</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="cinema-card-date">
                                            <span class="cinema-day"><?php echo date('d', strtotime($film['session_date'])); ?></span>
                                            <span class="cinema-month-short"><?php
                                                $mois_court = ['jan','fev','mar','avr','mai','jun','jul','aou','sep','oct','nov','dec'];
                                                echo $mois_court[(int)date('m', strtotime($film['session_date'])) - 1];
                                            ?></span>
                                        </div>
                                    </div>
                                    <div class="cinema-card-body">
                                        <h3 class="cinema-card-title"><?php echo htmlspecialchars($film['title']); ?></h3>
                                        <div class="cinema-card-meta">
                                            <?php if ($film['director']): ?>
                                                <span class="cinema-meta-item">De <?php echo htmlspecialchars($film['director']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($film['genre']): ?>
                                                <span class="cinema-genre-tag"><?php echo htmlspecialchars($film['genre']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($film['description']): ?>
                                            <p class="cinema-card-desc"><?php echo htmlspecialchars(mb_substr($film['description'], 0, 1500)); ?><?php echo mb_strlen($film['description']) > 1500 ? '...' : ''; ?></p>
                                        <?php endif; ?>
                                        <div class="cinema-card-footer">
                                            <div class="cinema-card-schedule">
                                                <span class="cinema-schedule-day"><?php echo day_label_fr($film['session_date']); ?></span>
                                                <?php if ($film['session_time']): ?>
                                                    <span class="cinema-schedule-time"><?php echo htmlspecialchars($film['session_time']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($film['location']): ?>
                                                <div class="cinema-card-location"><?php echo htmlspecialchars($film['location']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($film['duration']): ?>
                                                <div class="cinema-card-duration"><?php echo $film['duration']; ?> min</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Films passes -->
            <?php if (!empty($past_by_month)): ?>
                <div class="cinema-section-title" style="margin-top: 3rem;">
                    <h2>Séances passées</h2>
                </div>

                <?php foreach ($past_by_month as $month): ?>
                    <div class="cinema-month cinema-month-past">
                        <div class="cinema-month-header cinema-month-header-past"><?php echo htmlspecialchars($month['label']); ?></div>
                        <div class="cinema-films-grid">
                            <?php foreach ($month['films'] as $film): ?>
                                <div class="cinema-card cinema-card-past">
                                    <div class="cinema-card-poster">
                                        <?php if ($film['image']): ?>
                                            <img src="uploads/cinema/<?php echo htmlspecialchars($film['image']); ?>"
                                                 alt="<?php echo htmlspecialchars($film['title']); ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="cinema-card-no-poster">
                                                <span>F</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cinema-card-body">
                                        <h3 class="cinema-card-title"><?php echo htmlspecialchars($film['title']); ?></h3>
                                        <div class="cinema-card-meta">
                                            <?php if ($film['director']): ?>
                                                <span class="cinema-meta-item">De <?php echo htmlspecialchars($film['director']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($film['genre']): ?>
                                                <span class="cinema-genre-tag"><?php echo htmlspecialchars($film['genre']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cinema-card-footer">
                                            <span class="cinema-schedule-day" style="color: var(--text-secondary);"><?php echo day_label_fr($film['session_date']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="actualites.php" class="btn btn-primary">Retour aux actualités</a>
        </div>
    </div>
</section>

<style>
.cinema-section-title {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.cinema-section-title h2 {
    margin: 0;
    color: var(--text-primary);
}
.cinema-count {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.cinema-month {
    margin-bottom: 2.5rem;
}
.cinema-month-header {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 0.6rem 1.25rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 1.25rem;
    display: inline-block;
}
.cinema-month-header-past {
    background: linear-gradient(135deg, var(--text-secondary), #4b5563);
}

.cinema-films-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cinema-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    display: flex;
    width: 100%;
    transition: transform 0.3s, box-shadow 0.3s;
}
.cinema-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}
.cinema-card-past {
    opacity: 0.75;
}
.cinema-card-past:hover {
    opacity: 1;
}

.cinema-card-poster {
    width: 180px;
    min-height: 240px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    background: var(--background-dark);
}
.cinema-card-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cinema-card-no-poster {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--text-secondary);
    background: var(--background-light);
}
.cinema-card-date {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: var(--primary-color);
    color: white;
    border-radius: var(--radius-md);
    padding: 0.35rem 0.6rem;
    text-align: center;
    line-height: 1.1;
    box-shadow: var(--shadow-md);
}
.cinema-day {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
}
.cinema-month-short {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.cinema-card-body {
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.cinema-card-title {
    font-size: 1.35rem;
    margin: 0 0 0.5rem;
    color: var(--text-primary);
    line-height: 1.3;
}
.cinema-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    align-items: center;
}
.cinema-meta-item {
    font-size: 0.9rem;
    color: var(--text-secondary);
}
.cinema-genre-tag {
    font-size: 0.75rem;
    background: var(--background-light);
    color: var(--primary-color);
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-weight: 600;
    border: 1px solid var(--border-color);
}
.cinema-card-desc {
    font-size: 0.95rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0 0 1rem;
    flex: 1;
}
.cinema-card-footer {
    margin-top: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.5rem;
    align-items: center;
}
.cinema-card-schedule {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.cinema-schedule-day {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--primary-color);
}
.cinema-schedule-time {
    font-size: 0.85rem;
    font-weight: 700;
    background: var(--secondary-color);
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: var(--radius-sm);
}
.cinema-card-location {
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.cinema-card-duration {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .cinema-card {
        flex-direction: column;
    }
    .cinema-card-poster {
        width: 100%;
        min-height: 200px;
        max-height: 280px;
    }
    .cinema-card-body {
        padding: 1rem;
    }
    .cinema-card-title {
        font-size: 1.15rem;
    }
    .cinema-card-desc {
        font-size: 0.85rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>