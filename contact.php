<?php

require_once 'includes/config.php';
require_once 'includes/csrf.php';
require_once 'includes/sanitize.php';

// Rate limiting : max 3 messages par IP toutes les 15 minutes
define('CONTACT_MAX_ATTEMPTS', 3);
define('CONTACT_LOCKOUT_DURATION', 900); // 15 minutes

function contactRateLimit(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'contact_attempts_' . md5($ip);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }

    $data = &$_SESSION[$key];

    // Réinitialiser après la période de lockout
    if ((time() - $data['first_attempt']) > CONTACT_LOCKOUT_DURATION) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    if ($data['count'] >= CONTACT_MAX_ATTEMPTS) {
        return false;
    }

    $data['count']++;
    return true;
}

// Générer une question mathématique simple pour le CAPTCHA
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

// PROTECTION ANTI-BOT : Timestamp pour vérifier le temps de soumission
if (!isset($_SESSION['form_timestamp'])) {
    $_SESSION['form_timestamp'] = time();
}

// PROTECTION ANTI-BOT : Token à usage unique (générer seulement s'il n'existe pas)
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    // PROTECTION ANTI-BOT 1 : Honeypot (champ invisible que seuls les bots remplissent)
    if (!empty($_POST['website'])) {
        error_log("Bot détecté via honeypot - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die('Erreur de validation du formulaire.');
    }

    // PROTECTION ANTI-BOT 2 : Timing check (minimum 3 secondes)
    $form_start_time = $_SESSION['form_timestamp'] ?? time();
    $elapsed_time = time() - $form_start_time;
    if ($elapsed_time < 3) {
        error_log("Soumission trop rapide détectée ({$elapsed_time}s) - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $message = "Veuillez prendre le temps de remplir le formulaire correctement.";
        $message_type = 'error';
        // Réinitialiser le timestamp
        $_SESSION['form_timestamp'] = time();
    } 
    // PROTECTION ANTI-BOT 3 : Token à usage unique
    elseif (empty($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['form_token']) {
        error_log("Token invalide ou réutilisé - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $message = "Erreur de validation du formulaire. Veuillez réessayer.";
        $message_type = 'error';
        // Régénérer pour permettre une nouvelle tentative
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
        $_SESSION['form_timestamp'] = time();
    }
    // Vérifier le rate limiting avant tout traitement
    elseif (!contactRateLimit()) {
        $message = "Trop de messages envoyés. Veuillez réessayer dans 15 minutes.";
        $message_type = 'error';
    } else {

    $name = sanitize_text($_POST['name'] ?? '', 200);
    $email = sanitize_text($_POST['email'] ?? '', 254);
    $subject = sanitize_text($_POST['subject'] ?? '', 200);
    $user_message = sanitize_text($_POST['message'] ?? '', 5000);
    $captcha_answer = $_POST['captcha'] ?? '';

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Le nom est requis";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }

    if (empty($user_message)) {
        $errors[] = "Le message est requis";
    }

    // Validation du CAPTCHA
    $expected_answer = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
    if (empty($captcha_answer)) {
        $errors[] = "Veuillez répondre à la question de sécurité";
    } elseif ((int)$captcha_answer !== $expected_answer) {
        $errors[] = "La réponse à la question de sécurité est incorrecte";
        $_SESSION['captcha_num1'] = rand(1, 10);
        $_SESSION['captcha_num2'] = rand(1, 10);
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO EPI_contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $user_message]);

            // Envoyer l'email - From fixe pour éviter l'injection d'en-têtes
            $to = 'entraideplusiroise@gmail.com';
            $email_subject = "Nouveau message de contact - " . ($subject ?: 'Sans objet');
            $email_body = "Nouveau message de contact\n\n";
            $email_body .= "Nom: $name\n";
            $email_body .= "Email: $email\n";
            $email_body .= "Sujet: $subject\n\n";
            $email_body .= "Message:\n$user_message\n";

            // SECURITE : Ne pas utiliser l'email de l'utilisateur dans From
            // (prévient l'injection d'en-têtes email et le spoofing)
            $fromEmail = defined('NOREPLY_EMAIL') ? NOREPLY_EMAIL : 'noreply@entraide-plus-iroise.fr';
            $headers = "From: " . $fromEmail . "\r\n";
            $headers .= "Reply-To: " . filter_var($email, FILTER_SANITIZE_EMAIL) . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($to, $email_subject, $email_body, $headers)) {
                $message = "Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.";
                $message_type = 'success';

                $name = $email = $subject = $user_message = '';

                $_SESSION['captcha_num1'] = rand(1, 10);
                $_SESSION['captcha_num2'] = rand(1, 10);
                
                // Réinitialiser les protections anti-bot
                $_SESSION['form_timestamp'] = time();
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
            } else {
                $message = "Votre message a été enregistré mais l'email n'a pas pu être envoyé. Nous vous contacterons rapidement.";
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            error_log("Erreur contact: " . $e->getMessage());
            $message = "Une erreur s'est produite lors de l'envoi du message. Veuillez réessayer.";
            $message_type = 'error';
        }
    } else {
        // Échapper chaque erreur individuellement pour prévenir le XSS
        $message = implode('<br>', array_map(function($err) { return htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); }, $errors));
        $message_type = 'error';
    }
    } // fin du else rate limiting
}

// Récupérer les contacts depuis la table EPI_user
$contacts = [];
try {
    $stmt = $pdo->query("
        SELECT user_nicename,  user_phone, user_secteur,user_role 
        FROM EPI_user 
        WHERE ( user_role LIKE 'Présidente%' or  user_role LIKE 'Responsable du secteur%') 
        AND (user_phone  LIKE '0%')
        ORDER BY  user_role , user_secteur ASC
    ");
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur, on continue avec un tableau vide
    error_log("Erreur récupération contacts: " . $e->getMessage());
}

// Fonction pour formater un numéro de téléphone au format XX.XX.XX.XX.XX
function formatPhone($phone) {
    // Enlever tous les caractères non numériques
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Formater en groupes de 2 chiffres
    if (strlen($clean) == 10) {
        return substr($clean, 0, 2) . '.' . 
               substr($clean, 2, 2) . '.' . 
               substr($clean, 4, 2) . '.' . 
               substr($clean, 6, 2) . '.' . 
               substr($clean, 8, 2);
    }
    
    // Si le format n'est pas 10 chiffres, retourner tel quel
    return $phone;
}

$page_title = "Contact";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" style="padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>Contactez-nous</h1>
        <p>Nous sommes à votre écoute</p>
    </div>
</section>

<!-- Contenu -->
<section class="section">
    <div class="container">
        <!-- Coordonnées en grille -->
        <h2 style="color: var(--primary-color); margin-bottom: 2rem; text-align: center;">Nos coordonnées</h2>
        
        <?php if (!empty($contacts)): ?>
            <?php 
            // Séparer président(e) et gestionnaires
            $president = null;
            $gestionnaires = [];
            
            foreach ($contacts as $contact) {
                if ($contact['user_role'] === 'Présidente') {
                    $president = $contact;
                } else {
                    $gestionnaires[] = $contact;
                }
            }
            ?>
            
            <!-- Président(e) -->
            <?php if ($president): ?>
            <div style="max-width: 600px; margin: 0 auto 3rem auto;">
                <div style="background: var(--background-light); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); text-align: center;">
                  
                    <!-- Nicename en VERT (gros) -->
                    <p style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; color: #28a745;">
                        <?php echo htmlspecialchars($president['user_nicename']); ?>
                    </p>
                    
                    <!-- Role en VERT (plus petit) -->
                    <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1.5rem; color: #28a745;">
                        <?php echo htmlspecialchars($president['user_role']); ?>
                    </p>
                    
                    <!-- Téléphone -->
                    <?php if ($president['user_phone']): ?>
                    <p style="color: var(--text-secondary); font-size: 1.25rem;">
                        📞 <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $president['user_phone']); ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                            <?php echo formatPhone($president['user_phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Gestionnaires par secteur -->
            <?php if (!empty($gestionnaires)): ?>
           <div style="max-width: 600px; margin: 0 auto 3rem auto;">
                <?php foreach ($gestionnaires as $gestionnaire): ?>
               
                <div style="background: var(--background-light); padding: 2rem; border-radius: var(--radius-lg); text-align: center;">
 
                    <!-- Nicename en VERT (gros) -->
                    <p style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; color: #28a745;">
                        <?php echo htmlspecialchars($gestionnaire['user_nicename']); ?>
                    </p>
                    
                    <!-- Role en VERT (plus petit) -->
                    <p style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1.5rem; color: #28a745;">
                        <?php echo htmlspecialchars($gestionnaire['user_role']); ?>
                    </p>
                    
                    <!-- Téléphone -->
                    <?php if ($gestionnaire['user_phone']): ?>
                    <p style="color: var(--text-secondary); font-size: 1.1rem;">
                        📞 <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $gestionnaire['user_phone']); ?>" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">
                            <?php echo formatPhone($gestionnaire['user_phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="text-align: center; padding: 2rem; background: var(--background-light); border-radius: var(--radius-lg);">
                <p style="color: var(--text-secondary);">Aucun contact disponible pour le moment.</p>
            </div>
        <?php endif; ?>
        
        

        
        <!-- Formulaire de contact -->
        <div style="max-width: 800px; margin: 0 auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 2rem; text-align: center;">Envoyez-nous un message</h2>
            
            <?php if ($message): ?>
                <div class="form-message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form id="contact-form" method="POST" action="" style="background: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md);">
                <?php echo csrf_field(); ?>
                
                <!-- PROTECTION ANTI-BOT : Honeypot (champ invisible) -->
                <input type="text" name="website" value="" style="display:none !important; position: absolute; left: -9999px;" tabindex="-1" autocomplete="off" aria-hidden="true">
                
                <!-- PROTECTION ANTI-BOT : Token à usage unique -->
                <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label">Nom complet <span style="color: var(--error);">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($name ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email <span style="color: var(--error);">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="subject" class="form-label">Sujet</label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           class="form-control"
                           value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">Message <span style="color: var(--error);">*</span></label>
                    <textarea id="message" 
                              name="message" 
                              class="form-control" 
                              rows="6" 
                              required><?php echo htmlspecialchars($user_message ?? ''); ?></textarea>
                </div>
                
                <!-- CAPTCHA -->
                <div class="form-group">
                    <label for="captcha" class="form-label">
                        Question de sécurité <span style="color: var(--error);">*</span>
                    </label>
                    <p style="background: var(--background-light); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 0.5rem; font-weight: 600;">
                        Combien font <?php echo $_SESSION['captcha_num1']; ?> + <?php echo $_SESSION['captcha_num2']; ?> . Attention, c'est pas facile facile :-)
                    </p>
                    <input type="number" 
                           id="captcha" 
                           name="captcha" 
                           class="form-control" 
                           placeholder="Votre réponse"
                           required
                           style="max-width: 150px;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem;">
                    Envoyer le message
                </button>
                
                <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--text-secondary); text-align: center;">
                    <span style="color: var(--error);">*</span> Champs obligatoires
                </p>
            </form>
        </div>
		        <!-- Lien Facebook -->
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); 
                    color: white; padding: 3rem 2rem; border-radius: var(--radius-lg); margin-bottom: 3rem; text-align: center;">
            <h3 style="margin-bottom: 1rem; font-size: 1.5rem;">Suivez-nous</h3>
            <p style="margin-bottom: 1.5rem; opacity: 0.9; font-size: 1.125rem;">Retrouvez nos actualités sur Facebook</p>
            <a href="https://www.facebook.com/groups/1378419443105675/" target="_blank" 
               class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Rejoignez notre groupe
            </a>
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    /* Réduire les paddings sur mobile */
    .hero {
        padding: 2rem 1rem !important;
    }
    
    /* Bloc présidente */
    section .container > div[style*="max-width: 600px"] > div {
        padding: 1.5rem !important;
    }
    
    /* Cartes de secteurs */
    section .container > div[style*="grid-template-columns"] > div {
        padding: 1.5rem !important;
    }
    
    /* Formulaire */
    #contact-form {
        padding: 1.5rem !important;
    }
    
    /* Titres */
    h2 {
        font-size: 1.5rem !important;
    }
    
    h3 {
        font-size: 1.1rem !important;
        white-space: normal !important;
    }
    
    /* Noms */
    p[style*="font-size: 1.25rem"] {
        font-size: 1.1rem !important;
    }
    
    /* Grille responsive */
    div[style*="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr))"] {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    
    /* Bloc Facebook */
    div[style*="linear-gradient"] {
        padding: 2rem 1.5rem !important;
    }
    
    div[style*="linear-gradient"] h3 {
        font-size: 1.3rem !important;
    }
    
    div[style*="linear-gradient"] p {
        font-size: 1rem !important;
    }
}

@media (max-width: 480px) {
    /* Encore plus petit sur très petit écran */
    .hero {
        padding: 1.5rem 0.5rem !important;
    }
    
    .hero h1 {
        font-size: 1.75rem !important;
    }
    
    section .container > div[style*="max-width: 600px"] > div,
    section .container > div[style*="grid-template-columns"] > div,
    #contact-form {
        padding: 1rem !important;
    }
    
    h2 {
        font-size: 1.3rem !important;
    }
    
    h3 {
        font-size: 1rem !important;
    }
    
    div[style*="linear-gradient"] {
        padding: 1.5rem 1rem !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
