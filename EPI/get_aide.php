<?php
require_once('config.php');
require_once('auth.php');
require_once(__DIR__ . '/../includes/sanitize.php');
require_once(__DIR__ . '/../includes/database.php');
verifierfonction(['admin', 'responsable']);

header('Content-Type: application/json');

$conn = getDBConnection();

try {
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT nom, adresse, code_postal, commune, secteur, tel_fixe, tel_portable FROM EPI_aide WHERE id_aide = :id");
        $stmt->execute([':id' => $_GET['id']]);
        $aide = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($aide) {
            echo json_encode([
                'success' => true,
                'nom' => $aide['nom'] ?? '',
                'adresse' => $aide['adresse'] ?? '',
                'code_postal' => $aide['code_postal'] ?? '',
                'commune' => $aide['commune'] ?? '',
                'secteur' => $aide['secteur'] ?? '',
                'tel_fixe' => $aide['tel_fixe'] ?? '',
                'tel_portable' => $aide['tel_portable'] ?? ''
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