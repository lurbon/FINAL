<?php
require_once 'includes/config.php';

$videos = $pdo->query("SELECT * FROM EPI_videos ORDER BY id desc")->fetchAll();

$page_title = "Videos";
include 'includes/header.php';
?>

<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Nos videos</h1>
        <p>Retrouvez nos moments en video</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($videos)): ?>
            <div style="text-align: center; padding: 4rem 1rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">&#9654;</div>
                <h2 style="color: var(--text-secondary); font-weight: 400;">Aucune video pour le moment</h2>
                <p style="color: var(--text-secondary); margin-top: 1rem;">Revenez bientot pour decouvrir nos videos !</p>
            </div>
        <?php else: ?>
            <div class="videos-list">
                <?php foreach ($videos as $video):
                    $is_youtube = !empty($video['youtube_id']);
                    $is_file = !empty($video['video_url']) && strpos($video['video_url'], 'uploads/') === 0;
                    $is_url = !empty($video['video_url']) && !$is_file;
                ?>
                    <div class="video-card">
                        <div class="video-player">
                            <?php if ($is_youtube): ?>
                                <iframe
                                    src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video['youtube_id']); ?>"
                                    title="<?php echo htmlspecialchars($video['title']); ?>"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy">
                                </iframe>
                            <?php elseif ($is_file): ?>
                                <video controls preload="metadata">
                                    <source src="<?php echo htmlspecialchars($video['video_url']); ?>">
                                    Votre navigateur ne supporte pas la lecture video.
                                </video>
                            <?php elseif ($is_url): ?>
                                <?php
                                    // Detecter les plateformes connues pour embed
                                    $url = $video['video_url'];
                                    $embed_url = null;

                                    // Dailymotion
                                    if (preg_match('/dailymotion\.com\/video\/([a-zA-Z0-9]+)/', $url, $m)) {
                                        $embed_url = 'https://www.dailymotion.com/embed/video/' . $m[1];
                                    } elseif (preg_match('/dai\.ly\/([a-zA-Z0-9]+)/', $url, $m)) {
                                        $embed_url = 'https://www.dailymotion.com/embed/video/' . $m[1];
                                    }
                                    // Vimeo
                                    elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
                                        $embed_url = 'https://player.vimeo.com/video/' . $m[1];
                                    }
                                    // URL directe video (mp4, webm, etc.)
                                    elseif (preg_match('/\.(mp4|webm|ogg|mov)(\?|$)/i', $url)) {
                                        $embed_url = null; // sera traite comme video native
                                    }
                                ?>
                                <?php if ($embed_url): ?>
                                    <iframe
                                        src="<?php echo htmlspecialchars($embed_url); ?>"
                                        title="<?php echo htmlspecialchars($video['title']); ?>"
                                        frameborder="0"
                                        allow="autoplay; fullscreen; picture-in-picture"
                                        allowfullscreen
                                        loading="lazy">
                                    </iframe>
                                <?php elseif (preg_match('/\.(mp4|webm|ogg|mov)(\?|$)/i', $url)): ?>
                                    <video controls preload="metadata">
                                        <source src="<?php echo htmlspecialchars($url); ?>">
                                        Votre navigateur ne supporte pas la lecture video.
                                    </video>
                                <?php else: ?>
                                    <div class="video-external-link">
                                        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener">
                                            <span class="video-play-icon">&#9654;</span>
                                            <span>Voir la video</span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <h2 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h2>
                            <?php if ($video['description']): ?>
                                <p class="video-desc"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                            <?php endif; ?>
                            <div class="video-date">
                                Ajoutee le <?php echo date('d/m/Y', strtotime($video['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.videos-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.video-card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
}
.video-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-xl);
}

.video-player {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 */
    background: #000;
}
.video-player iframe,
.video-player video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-external-link {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
}
.video-external-link a {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    color: white;
    text-decoration: none;
    font-size: 1.1rem;
    font-weight: 600;
    transition: transform 0.3s;
}
.video-external-link a:hover {
    transform: scale(1.05);
    color: white;
}
.video-play-icon {
    font-size: 3rem;
    width: 80px;
    height: 80px;
    border: 3px solid white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 6px;
}

.video-info {
    padding: 1.25rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.video-title {
    font-size: 1.1rem;
    margin: 0 0 0.5rem;
    color: var(--text-primary);
    line-height: 1.3;
}
.video-desc {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0 0 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.video-date {
    font-size: 0.75rem;
    color: var(--text-secondary);
    opacity: 0.7;
    margin-top: auto;
}

@media (max-width: 992px) {
    .videos-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .videos-list {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    .video-info {
        padding: 1rem;
    }
    .video-title {
        font-size: 1rem;
    }
    .video-desc {
        font-size: 0.85rem;
        -webkit-line-clamp: 2;
    }
}
</style>

<?php include 'includes/footer.php'; ?>