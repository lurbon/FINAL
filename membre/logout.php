<?php
/**
 * GESTIONNAIRE DE DÉCONNEXION - VERSION 2.0
 * Utilise SessionManager pour la déconnexion sécurisée
 * 
 * @version 2.0
 * @author Entraide Plus Iroise
 */

require_once __DIR__ . '/../includes/auth/SessionManager.php';

// Initialiser la session
SessionManager::init();

// Récupérer le nom de l'utilisateur avant déconnexion
$userData = SessionManager::getUserData();
$userName = $userData['name'] ?? 'utilisateur';

// Déconnecter l'utilisateur (cela détruit déjà la session)
SessionManager::logout();

// Redémarrer une session temporaire juste pour le message
session_start();
$_SESSION['success_message'] = "Déconnexion réussie. À bientôt " . htmlspecialchars($userName) . " !";

// Rediriger vers login.php dans le même dossier (membre)
header('Location: ../index.php');
exit();
