<?php
require_once 'config.php';
include 'header.php';

// Vérifier si un ID d'équipe est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Team.php');
    exit;
}

$equipe_id = $_GET['id'];

// Récupération des pays pour le formulaire
$sql_pays = "SELECT codePays, nomPays FROM Pays ORDER BY nomPays";
$result_pays = $conn->query($sql_pays);

// Récupération des informations de l'équipe
$sql_equipe = "SELECT numEquipe, nomEquipe, codePays FROM Equipe WHERE numEquipe = ?";
$stmt = $conn->prepare($sql_equipe);
$stmt->bind_param("s", $equipe_id);
$stmt->execute();
$result_equipe = $stmt->get_result();

if ($result_equipe->num_rows == 0) {
    header('Location: Team.php');
    exit;
}

$equipe = $result_equipe->fetch_assoc();

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nomEquipe = isset($_POST['nomEquipe']) ? trim($_POST['nomEquipe']) : '';
    $pays = isset($_POST['pays']) ? trim($_POST['pays']) : '';
    
    // Validation des données
    if (empty($nomEquipe)) {
        $errors[] = "Le nom de l'équipe est obligatoire.";
    } elseif (strlen($nomEquipe) > 50) {
        $errors[] = "Le nom de l'équipe ne doit pas dépasser 50 caractères.";
    }
    
    if (empty($pays)) {
        $errors[] = "Le pays est obligatoire.";
    }
    
    // Si pas d'erreurs, on met à jour l'équipe
    if (empty($errors)) {
        $sql_update = "UPDATE Equipe SET nomEquipe = ?, codePays = ? WHERE numEquipe = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sss", $nomEquipe, $pays, $equipe_id);
        
        if ($stmt_update->execute()) {
            $success = true;
            
            // Mettre à jour les données de l'équipe pour l'affichage
            $equipe['nomEquipe'] = $nomEquipe;
            $equipe['codePays'] = $pays;
        } else {
            $errors[] = "Erreur lors de la mise à jour de l'équipe: " . $conn->error;
        }
    }
}

// Vérifier si l'équipe a des coureurs
$sql_count_coureurs = "SELECT COUNT(*) AS nb_coureurs FROM Coureur WHERE numEquipe = ?";
$stmt_count = $conn->prepare($sql_count_coureurs);
$stmt_count->bind_param("s", $equipe_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$has_coureurs = ($row_count['nb_coureurs'] > 0);
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modifier une équipe</h1>
        <div>
            <a href="Team_detail.php?id=<?php echo $equipe_id; ?>" class="btn btn-outline-info me-2">Détails de l'équipe</a>
            <a href="Team.php" class="btn btn-outline-primary">Retour aux équipes</a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        L'équipe a été modifiée avec succès !
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
                    <div class="col-md-6">
                        <label for="numEquipe" class="form-label">Code de l'équipe</label>
                        <input type="text" class="form-control" id="numEquipe" value="<?php echo htmlspecialchars($equipe['numEquipe']); ?>" readonly>
                        <small class="text-muted">Le code de l'équipe ne peut pas être modifié.</small>
                    </div>
                    <div class="col-md-6">
                        <label for="nomEquipe" class="form-label">Nom de l'équipe *</label>
                        <input type="text" class="form-control" id="nomEquipe" name="nomEquipe" value="<?php echo htmlspecialchars($equipe['nomEquipe']); ?>" maxlength="50" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="pays" class="form-label">Pays *</label>
                        <select class="form-select" id="pays" name="pays" required>
                            <?php 
                            $result_pays->data_seek(0); // Réinitialiser le pointeur de résultat
                            while($pays = $result_pays->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $pays['codePays']; ?>" <?php echo ($pays['codePays'] == $equipe['codePays']) ? 'selected' : ''; ?>>
                                <?php echo $pays['nomPays']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="Team_detail.php?id=<?php echo $equipe_id; ?>" class="btn btn-outline-secondary">Annuler</a>
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
            <h5 class="card-title">Supprimer cette équipe</h5>
            <p class="card-text">
                Cette action est irréversible. 
                <?php if ($has_coureurs): ?>
                <strong>Attention :</strong> Cette équipe a des coureurs. Vous devez d'abord supprimer ou réaffecter ces coureurs avant de pouvoir supprimer l'équipe.
                <?php else: ?>
                Vous pouvez supprimer cette équipe car elle n'a pas de coureurs associés.
                <?php endif; ?>
            </p>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" <?php echo $has_coureurs ? 'disabled' : ''; ?>>
                Supprimer l'équipe
            </button>
            <?php if ($has_coureurs): ?>
            <a href="Team_detail.php?id=<?php echo $equipe_id; ?>" class="btn btn-warning">
                Voir les coureurs de l'équipe
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
                    Êtes-vous sûr de vouloir supprimer l'équipe <strong><?php echo htmlspecialchars($equipe['nomEquipe']); ?></strong> (<?php echo $equipe['numEquipe']; ?>) ?
                    <br><br>
                    Cette action est irréversible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="delete_equipe.php?id=<?php echo urlencode($equipe_id); ?>" class="btn btn-danger">Supprimer définitivement</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>