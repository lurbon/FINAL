<?php
require_once('config.php');
require_once('auth.php');
require_once(__DIR__ . '/../includes/sanitize.php');
require_once(__DIR__ . '/../includes/database.php');
verifierRole(['admin', 'gestionnaire']);

header('Content-Type: application/json');

// Fonction pour nettoyer les backslashes multiples
function cleanData($value) {
    if (is_null($value) || $value === '') {
        return $value;
    }
    // Retirer tous les backslashes d'échappement accumulés
    while (strpos($value, '\\\\') !== false) {
        $value = str_replace('\\\\', '\\', $value);
    }
    // Puis retirer les backslashes simples d'échappement
    return stripslashes($value);
}

$conn = getDBConnection();

try {
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT nom, adresse, code_postal, commune, secteur, tel_fixe, tel_portable FROM EPI_aide WHERE id_aide = :id");
        $stmt->execute([':id' => $_GET['id']]);
        $aide = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aide) {
            echo json_encode([
                'success' => true,
                'nom' => cleanData($aide['nom'] ?? ''),
                'adresse' => cleanData($aide['adresse'] ?? ''),
                'code_postal' => cleanData($aide['code_postal'] ?? ''),
                'commune' => cleanData($aide['commune'] ?? ''),
                'secteur' => cleanData($aide['secteur'] ?? ''),
                'tel_fixe' => cleanData($aide['tel_fixe'] ?? ''),
                'tel_portable' => cleanData($aide['tel_portable'] ?? '')
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Aidé introuvable']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
    }
} catch(PDOException $e) {
    error_log("get_aide: Erreur requête: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur interne du serveur']);
}
?>