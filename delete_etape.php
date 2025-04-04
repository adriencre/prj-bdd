<?php
require_once 'config.php';

// Vérifier si un ID d'étape est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Etape.php');
    exit;
}

$etape_id = $_GET['id'];

// Vérifier si l'étape existe
$sql_check = "SELECT numEtape FROM Etape WHERE numEtape = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("i", $etape_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    // L'étape n'existe pas, rediriger
    header('Location: Etape.php');
    exit;
}

// Vérifier si l'étape a des performances liées
$sql_performances = "SELECT COUNT(*) AS nb_performances FROM Performance WHERE numEtape = ?";
$stmt_perf = $conn->prepare($sql_performances);
$stmt_perf->bind_param("i", $etape_id);
$stmt_perf->execute();
$result_perf = $stmt_perf->get_result();
$row_perf = $result_perf->fetch_assoc();

if ($row_perf['nb_performances'] > 0) {
    // L'étape a des performances, impossible de la supprimer
    header('Location: edit_etape.php?id=' . $etape_id . '&error=1');
    exit;
}

// Vérifier si l'étape a des bonifications liées
$sql_bonif = "SELECT COUNT(*) AS nb_bonifications FROM Bonification WHERE numEtape = ?";
$stmt_bonif = $conn->prepare($sql_bonif);
$stmt_bonif->bind_param("i", $etape_id);
$stmt_bonif->execute();
$result_bonif = $stmt_bonif->get_result();
$row_bonif = $result_bonif->fetch_assoc();

if ($row_bonif['nb_bonifications'] > 0) {
    // L'étape a des bonifications, on les supprime d'abord
    $sql_delete_bonif = "DELETE FROM Bonification WHERE numEtape = ?";
    $stmt_delete_bonif = $conn->prepare($sql_delete_bonif);
    $stmt_delete_bonif->bind_param("i", $etape_id);
    
    if (!$stmt_delete_bonif->execute()) {
        // Erreur lors de la suppression des bonifications
        header('Location: edit_etape.php?id=' . $etape_id . '&error=2');
        exit;
    }
}

// Supprimer l'étape
$sql_delete = "DELETE FROM Etape WHERE numEtape = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $etape_id);

if ($stmt_delete->execute()) {
    // Rediriger vers la page des étapes avec un message de succès
    header('Location: Etape.php?deleted=1');
    exit;
} else {
    // Rediriger vers la page d'édition avec un message d'erreur
    header('Location: edit_etape.php?id=' . $etape_id . '&error=3');
    exit;
}

$conn->close();
?>