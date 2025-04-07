<?php
require_once 'config.php';
include 'header.php';

// Vérifier si un ID de coureur est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Coureur.php');
    exit;
}

$coureur_id = $_GET['id'];

// Récupération des équipes pour le formulaire
$sql_equipes = "SELECT numEquipe, nomEquipe FROM Equipe ORDER BY nomEquipe";
$result_equipes = $conn->query($sql_equipes);

// Récupération des pays pour le formulaire
$sql_pays = "SELECT codePays, nomPays FROM Pays ORDER BY nomPays";
$result_pays = $conn->query($sql_pays);

// Récupération des informations du coureur
$sql_coureur = "SELECT numDossard, nom, prenom, DN, numEquipe, codePays FROM Coureur WHERE numDossard = ?";
$stmt = $conn->prepare($sql_coureur);
$stmt->bind_param("i", $coureur_id);
$stmt->execute();
$result_coureur = $stmt->get_result();

if ($result_coureur->num_rows == 0) {
    header('Location: Coureur.php');
    exit;
}

$coureur = $result_coureur->fetch_assoc();

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $dn = isset($_POST['dn']) ? trim($_POST['dn']) : '';
    $equipe = isset($_POST['equipe']) ? trim($_POST['equipe']) : '';
    $pays = isset($_POST['pays']) ? trim($_POST['pays']) : '';
    
    // Validation des données
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }
    
    if (empty($prenom)) {
        $errors[] = "Le prénom est obligatoire.";
    }
    
    if (empty($dn)) {
        $errors[] = "La date de naissance est obligatoire.";
    }
    
    if (empty($equipe)) {
        $errors[] = "L'équipe est obligatoire.";
    }
    
    if (empty($pays)) {
        $errors[] = "Le pays est obligatoire.";
    }
    
    // Si pas d'erreurs, on met à jour le coureur
    if (empty($errors)) {
        $sql_update = "UPDATE Coureur SET nom = ?, prenom = ?, DN = ?, numEquipe = ?, codePays = ? WHERE numDossard = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssssi", $nom, $prenom, $dn, $equipe, $pays, $coureur_id);
        
        if ($stmt_update->execute()) {
            $success = true;
            
            // Mettre à jour les données du coureur pour l'affichage
            $coureur['nom'] = $nom;
            $coureur['prenom'] = $prenom;
            $coureur['DN'] = $dn;
            $coureur['numEquipe'] = $equipe;
            $coureur['codePays'] = $pays;
        } else {
            $errors[] = "Erreur lors de la mise à jour du coureur: " . $conn->error;
        }
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Modifier un coureur</h1>
        <div>
            <a href="Coureur_detail.php?id=<?php echo $coureur_id; ?>" class="btn btn-outline-info me-2">Détails du coureur</a>
            <a href="Coureur.php" class="btn btn-outline-primary">Retour aux coureurs</a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        Le coureur a été modifié avec succès !
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
                        <label for="numDossard" class="form-label">Numéro de dossard</label>
                        <input type="number" class="form-control" id="numDossard" value="<?php echo $coureur['numDossard']; ?>" readonly>
                        <small class="text-muted">Le numéro de dossard ne peut pas être modifié.</small>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($coureur['nom']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($coureur['prenom']); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="dn" class="form-label">Date de naissance *</label>
                        <input type="date" class="form-control" id="dn" name="dn" value="<?php echo $coureur['DN']; ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="equipe" class="form-label">Équipe *</label>
                        <select class="form-select" id="equipe" name="equipe" required>
                            <?php 
                            $result_equipes->data_seek(0); // Réinitialiser le pointeur de résultat
                            while($equipe = $result_equipes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $equipe['numEquipe']; ?>" <?php echo ($equipe['numEquipe'] == $coureur['numEquipe']) ? 'selected' : ''; ?>>
                                <?php echo $equipe['nomEquipe']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pays" class="form-label">Pays *</label>
                        <select class="form-select" id="pays" name="pays" required>
                            <?php 
                            $result_pays->data_seek(0); // Réinitialiser le pointeur de résultat
                            while($pays = $result_pays->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $pays['codePays']; ?>" <?php echo ($pays['codePays'] == $coureur['codePays']) ? 'selected' : ''; ?>>
                                <?php echo $pays['nomPays']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="Coureur_detail.php?id=<?php echo $coureur_id; ?>" class="btn btn-outline-secondary">Annuler</a>
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
            <h5 class="card-title">Supprimer ce coureur</h5>
            <p class="card-text">Cette action est irréversible. Si vous supprimez ce coureur, toutes ses performances et bonifications seront également supprimées.</p>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                Supprimer le coureur
            </button>
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
                    Êtes-vous sûr de vouloir supprimer le coureur <strong><?php echo htmlspecialchars($coureur['prenom'] . ' ' . $coureur['nom']); ?></strong> (dossard n°<?php echo $coureur['numDossard']; ?>) ?
                    <br><br>
                    Cette action est irréversible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="delete_coureur.php?id=<?php echo $coureur_id; ?>" class="btn btn-danger">Supprimer définitivement</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>