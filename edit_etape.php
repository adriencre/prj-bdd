<?php
require_once 'config.php';
include 'header.php';

// Vérifier si un ID d'étape est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Etape.php');
    exit;
}

$etape_id = $_GET['id'];

// Récupération des villes pour le formulaire
$sql_villes = "SELECT v.numVille, v.nomVille, p.nomPays 
              FROM Ville v 
              JOIN Pays p ON v.codePays = p.codePays 
              ORDER BY v.nomVille";
$result_villes = $conn->query($sql_villes);

// Récupération des types d'étapes pour le formulaire
$sql_types = "SELECT idTypeEtape, nomTypeEtape FROM TypeEtape ORDER BY nomTypeEtape";
$result_types = $conn->query($sql_types);

// Récupération des informations de l'étape
$sql_etape = "SELECT e.numEtape, e.dateEtape, e.distance, e.numVille, e.numVille_1, e.idTypeEtape,
              v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, t.nomTypeEtape
              FROM Etape e
              LEFT JOIN Ville v1 ON e.numVille = v1.numVille
              LEFT JOIN Ville v2 ON e.numVille_1 = v2.numVille
              LEFT JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
              WHERE e.numEtape = ?";
$stmt = $conn->prepare($sql_etape);
$stmt->bind_param("i", $etape_id);
$stmt->execute();
$result_etape = $stmt->get_result();

if ($result_etape->num_rows == 0) {
    header('Location: Etape.php');
    exit;
}

$etape = $result_etape->fetch_assoc();

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $dateEtape = isset($_POST['dateEtape']) ? trim($_POST['dateEtape']) : '';
    $distance = isset($_POST['distance']) ? floatval($_POST['distance']) : 0;
    $villeDepart = isset($_POST['villeDepart']) ? intval($_POST['villeDepart']) : 0;
    $villeArrivee = isset($_POST['villeArrivee']) ? intval($_POST['villeArrivee']) : 0;
    $typeEtape = isset($_POST['typeEtape']) ? intval($_POST['typeEtape']) : 0;
    
    // Validation des données
    if (empty($dateEtape)) {
        $errors[] = "La date de l'étape est obligatoire.";
    }
    
    if (empty($distance) || $distance <= 0) {
        $errors[] = "La distance doit être supérieure à 0.";
    }
    
    if (empty($villeDepart)) {
        $errors[] = "La ville de départ est obligatoire.";
    }
    
    if (empty($villeArrivee)) {
        $errors[] = "La ville d'arrivée est obligatoire.";
    }
    
    if (empty($typeEtape)) {
        $errors[] = "Le type d'étape est obligatoire.";
    }
    
    // Si pas d'erreurs, on met à jour l'étape
    if (empty($errors)) {
        $sql_update = "UPDATE Etape SET dateEtape = ?, distance = ?, numVille = ?, numVille_1 = ?, idTypeEtape = ? WHERE numEtape = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sdiiis", $dateEtape, $distance, $villeDepart, $villeArrivee, $typeEtape, $etape_id);
        
        if ($stmt_update->execute()) {
            $success = true;
            
            // Mettre à jour les données de l'étape pour l'affichage
            $etape['dateEtape'] = $dateEtape;
            $etape['distance'] = $distance;
            $etape['numVille'] = $villeDepart;
            $etape['numVille_1'] = $villeArrivee;
            $etape['idTypeEtape'] = $typeEtape;
            
            // Récupérer les noms mis à jour
            $sql_update_names = "SELECT v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, t.nomTypeEtape
                               FROM Ville v1, Ville v2, TypeEtape t
                               WHERE v1.numVille = ? AND v2.numVille = ? AND t.idTypeEtape = ?";
            $stmt_names = $conn->prepare($sql_update_names);
            $stmt_names->bind_param("iii", $villeDepart, $villeArrivee, $typeEtape);
            $stmt_names->execute();
            $result_names = $stmt_names->get_result();
            $row_names = $result_names->fetch_assoc();
            
            $etape['villeDepart'] = $row_names['villeDepart'];
            $etape['villeArrivee'] = $row_names['villeArrivee'];
            $etape['nomTypeEtape'] = $row_names['nomTypeEtape'];
        } else {
            $errors[] = "Erreur lors de la mise à jour de l'étape: " . $conn->error;
        }
    }
}

// Vérifier si l'étape a des performances liées
$sql_performances = "SELECT COUNT(*) AS nb_performances FROM Performance WHERE numEtape = ?";
$stmt_perf = $conn->prepare($sql_performances);
$stmt_perf->bind_param("i", $etape_id);
$stmt_perf->execute();
$result_perf = $stmt_perf->get_result();
$row_perf = $result_perf->fetch_assoc();
$has_performances = ($row_perf['nb_performances'] > 0);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modifier l'étape <?php echo $etape['numEtape']; ?></h1>
        <div>
            <a href="Etape_detail.php?id=<?php echo $etape_id; ?>" class="btn btn-outline-info me-2">Détails de l'étape</a>
            <a href="Etape.php" class="btn btn-outline-primary">Retour aux étapes</a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        L'étape a été modifiée avec succès !
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Erreurs :</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form action="" method="post">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="numEtape" class="form-label">Numéro d'étape</label>
                        <input type="number" class="form-control" id="numEtape" value="<?php echo $etape['numEtape']; ?>" readonly>
                        <small class="text-muted">Le numéro d'étape ne peut pas être modifié.</small>
                    </div>
                    <div class="col-md-4">
                        <label for="dateEtape" class="form-label">Date de l'étape *</label>
                        <input type="date" class="form-control" id="dateEtape" name="dateEtape" value="<?php echo $etape['dateEtape']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="distance" class="form-label">Distance (km) *</label>
                        <input type="number" class="form-control" id="distance" name="distance" value="<?php echo $etape['distance']; ?>" required min="0.1" step="0.1">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="villeDepart" class="form-label">Ville de départ *</label>
                        <select class="form-select" id="villeDepart" name="villeDepart" required>
                            <?php 
                            $result_villes->data_seek(0); // Réinitialiser le pointeur
                            while($ville = $result_villes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $ville['numVille']; ?>" <?php echo ($ville['numVille'] == $etape['numVille']) ? 'selected' : ''; ?>>
                                <?php echo $ville['nomVille'] . ' (' . $ville['nomPays'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="villeArrivee" class="form-label">Ville d'arrivée *</label>
                        <select class="form-select" id="villeArrivee" name="villeArrivee" required>
                            <?php 
                            $result_villes->data_seek(0); // Réinitialiser le pointeur
                            while($ville = $result_villes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $ville['numVille']; ?>" <?php echo ($ville['numVille'] == $etape['numVille_1']) ? 'selected' : ''; ?>>
                                <?php echo $ville['nomVille'] . ' (' . $ville['nomPays'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="typeEtape" class="form-label">Type d'étape *</label>
                        <select class="form-select" id="typeEtape" name="typeEtape" required>
                            <?php 
                            $result_types->data_seek(0); // Réinitialiser le pointeur
                            while($type = $result_types->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $type['idTypeEtape']; ?>" <?php echo ($type['idTypeEtape'] == $etape['idTypeEtape']) ? 'selected' : ''; ?>>
                                <?php echo $type['nomTypeEtape']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="Etape_detail.php?id=<?php echo $etape_id; ?>" class="btn btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Section suppression -->
    <div class="card mt-4 border-danger">
        <div class="card-header bg-danger text-white">
            Zone dangereuse
        </div>
        <div class="card-body">
            <h5 class="card-title">Supprimer cette étape</h5>
            <p class="card-text">
                Cette action est irréversible. 
                <?php if ($has_performances): ?>
                <strong>Attention :</strong> Des performances sont enregistrées pour cette étape. Vous devez d'abord les supprimer avant de pouvoir supprimer l'étape.
                <?php else: ?>
                Vous pouvez supprimer cette étape car elle n'a pas de performances associées.
                <?php endif; ?>
            </p>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" <?php echo $has_performances ? 'disabled' : ''; ?>>
                Supprimer l'étape
            </button>
            <?php if ($has_performances): ?>
            <a href="Etape_detail.php?id=<?php echo $etape_id; ?>" class="btn btn-warning">
                Voir les performances de l'étape
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmation de suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer l'étape n°<?php echo $etape['numEtape']; ?> (<?php echo $etape['villeDepart'] . ' - ' . $etape['villeArrivee']; ?>) ?
                    <br><br>
                    Cette action est irréversible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="delete_etape.php?id=<?php echo $etape_id; ?>" class="btn btn-danger">Supprimer définitivement</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>