<?php

require_once 'config.php';
include 'header.php';

// Récupération des équipes pour le formulaire
$sql_equipes = "SELECT numEquipe, nomEquipe FROM Equipe ORDER BY nomEquipe";
$result_equipes = $conn->query($sql_equipes);

// Récupération des pays pour le formulaire
$sql_pays = "SELECT codePays, nomPays FROM Pays ORDER BY nomPays";
$result_pays = $conn->query($sql_pays);

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $numDossard = isset($_POST['numDossard']) ? intval($_POST['numDossard']) : 0;
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $dn = isset($_POST['dn']) ? trim($_POST['dn']) : '';
    $equipe = isset($_POST['equipe']) ? trim($_POST['equipe']) : '';
    $pays = isset($_POST['pays']) ? trim($_POST['pays']) : '';
    
    // Validation des données
    if (empty($numDossard)) {
        $errors[] = "Le numéro de dossard est obligatoire.";
    }
    
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
    
    // Vérifier si le dossard existe déjà
    $sql_check = "SELECT numDossard FROM Coureur WHERE numDossard = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $numDossard);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $errors[] = "Le numéro de dossard $numDossard existe déjà.";
    }
    
    // Si pas d'erreurs, on insère le coureur
    if (empty($errors)) {
        $sql_insert = "INSERT INTO Coureur (numDossard, nom, prenom, DN, numEquipe, codePays) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("isssss", $numDossard, $nom, $prenom, $dn, $equipe, $pays);
        
        if ($stmt_insert->execute()) {
            $success = true;
        } else {
            $errors[] = "Erreur lors de l'ajout du coureur: " . $conn->error;
        }
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Ajouter un coureur</h1>
        <a href="Coureur.php" class="btn btn-outline-primary">Retour aux coureurs</a>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        Le coureur a été ajouté avec succès !
        <a href="Coureur_detail.php?id=<?php echo $numDossard; ?>" class="alert-link">Voir la fiche du coureur</a>
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
                        <label for="numDossard" class="form-label">Numéro de dossard *</label>
                        <input type="number" class="form-control" id="numDossard" name="numDossard" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="dn" class="form-label">Date de naissance *</label>
                        <input type="date" class="form-control" id="dn" name="dn" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="equipe" class="form-label">Équipe *</label>
                        <select class="form-select" id="equipe" name="equipe" required>
                            <option value="">Sélectionner une équipe</option>
                            <?php while($equipe = $result_equipes->fetch_assoc()): ?>
                            <option value="<?php echo $equipe['numEquipe']; ?>"><?php echo $equipe['nomEquipe']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="pays" class="form-label">Pays *</label>
                        <select class="form-select" id="pays" name="pays" required>
                            <option value="">Sélectionner un pays</option>
                            <?php while($pays = $result_pays->fetch_assoc()): ?>
                            <option value="<?php echo $pays['codePays']; ?>"><?php echo $pays['nomPays']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>