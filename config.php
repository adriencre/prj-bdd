<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root"; // à modifier selon votre configuration
$password = "root"; // à modifier selon votre configuration
$dbname = "bdd_tdf";

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("Échec de la connexion: " . $conn->connect_error);
}

// Configuration de l'encodage des caractères
$conn->set_charset("utf8mb4");
?>