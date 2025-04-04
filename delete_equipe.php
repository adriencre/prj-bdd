<?php
require_once 'config.php';

// Vérifier si un ID d'équipe est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Team.php');
    exit;
}

$equipe_id = $_GET['id'];

// Vérifier si l'équipe existe
$sql_check = "SELECT numEquipe FROM Equipe WHERE numEquipe = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $equipe_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // L'équipe n'existe pas, rediriger
    header('Location: Team.php');
    exit;
}

// Vérifier si l'équipe a des coureurs
$sql_count_coureurs = "SELECT COUNT(*) AS nb_coureurs FROM Coureur WHERE numEquipe = ?";
$stmt_count = $conn->prepare($sql_count_coureurs);
$stmt_count->bind_param("s", $equipe_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();

if ($row_count['nb_coureurs'] > 0) {
    // L'équipe a des coureurs, impossible de la supprimer
    header('Location: edit_equipe.php?id=' . urlencode($equipe_id) . '&error=1');
    exit;
}

// Supprimer l'équipe
$sql_delete = "DELETE FROM Equipe WHERE numEquipe = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("s", $equipe_id);

if ($stmt_delete->execute()) {
    // Rediriger vers la page des équipes avec un message de succès
    header('Location: Team.php?deleted=1');
    exit;
} else {
    // Rediriger vers la page d'édition avec un message d'erreur
    header('Location: edit_equipe.php?id=' . urlencode($equipe_id) . '&error=2');
    exit;
}

$conn->close();
?>