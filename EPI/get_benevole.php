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
        $stmt = $conn->prepare("SELECT nom, adresse, code_postal, commune, secteur FROM EPI_benevole WHERE id_benevole = :id");
        $stmt->execute([':id' => $_GET['id']]);
        $benevole = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($benevole) {
            echo json_encode([
                'success' => true,
                'nom' => $benevole['nom'],
                'adresse' => $benevole['adresse'],
                'code_postal' => $benevole['code_postal'],
                'commune' => $benevole['commune'],
                'secteur' => $benevole['secteur']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Bénévole introuvable']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
    }
} catch(PDOException $e) {
    error_log("get_benevole: Erreur requête: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur interne du serveur']);
}
?>