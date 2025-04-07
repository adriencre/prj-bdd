<?php
require_once 'config.php';
include 'header.php';

// Récupération des villes pour le formulaire
$sql_villes = "SELECT v.numVille, v.nomVille, p.nomPays 
              FROM Ville v 
              JOIN Pays p ON v.codePays = p.codePays 
              ORDER BY v.nomVille";
$result_villes = $conn->query($sql_villes);

// Récupération des types d'étapes pour le formulaire
$sql_types = "SELECT idTypeEtape, nomTypeEtape FROM TypeEtape ORDER BY nomTypeEtape";
$result_types = $conn->query($sql_types);

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $numEtape = isset($_POST['numEtape']) ? intval($_POST['numEtape']) : 0;
    $dateEtape = isset($_POST['dateEtape']) ? trim($_POST['dateEtape']) : '';
    $distance = isset($_POST['distance']) ? floatval($_POST['distance']) : 0;
    $villeDepart = isset($_POST['villeDepart']) ? intval($_POST['villeDepart']) : 0;
    $villeArrivee = isset($_POST['villeArrivee']) ? intval($_POST['villeArrivee']) : 0;
    $typeEtape = isset($_POST['typeEtape']) ? intval($_POST['typeEtape']) : 0;
    
    // Validation des données
    if (empty($numEtape)) {
        $errors[] = "Le numéro d'étape est obligatoire.";
    }
    
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
    
    // Vérifier si l'étape existe déjà
    $sql_check = "SELECT numEtape FROM Etape WHERE numEtape = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $numEtape);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $errors[] = "L'étape numéro $numEtape existe déjà.";
    }
    
    // Si pas d'erreurs, on insère l'étape
    if (empty($errors)) {
        $sql_insert = "INSERT INTO Etape (numEtape, dateEtape, distance, numVille, numVille_1, idTypeEtape) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("isdiii", $numEtape, $dateEtape, $distance, $villeDepart, $villeArrivee, $typeEtape);
        
        if ($stmt_insert->execute()) {
            $success = true;
        } else {
            $errors[] = "Erreur lors de l'ajout de l'étape: " . $conn->error;
        }
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Ajouter une étape</h1>
        <a href="Etape.php" class="btn btn-outline-primary">Retour aux étapes</a>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        L'étape a été ajoutée avec succès !
        <a href="Etape_detail.php?id=<?php echo $numEtape; ?>" class="alert-link">Voir les détails de l'étape</a>
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
                        <label for="numEtape" class="form-label">Numéro d'étape *</label>
                        <input type="number" class="form-control" id="numEtape" name="numEtape" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label for="dateEtape" class="form-label">Date de l'étape *</label>
                        <input type="date" class="form-control" id="dateEtape" name="dateEtape" required>
                    </div>
                    <div class="col-md-4">
                        <label for="distance" class="form-label">Distance (km) *</label>
                        <input type="number" class="form-control" id="distance" name="distance" required min="0.1" step="0.1">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="villeDepart" class="form-label">Ville de départ *</label>
                        <select class="form-select" id="villeDepart" name="villeDepart" required>
                            <option value="">Sélectionner une ville</option>
                            <?php 
                            $result_villes->data_seek(0); // Réinitialiser le pointeur
                            while($ville = $result_villes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $ville['numVille']; ?>">
                                <?php echo $ville['nomVille'] . ' (' . $ville['nomPays'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="villeArrivee" class="form-label">Ville d'arrivée *</label>
                        <select class="form-select" id="villeArrivee" name="villeArrivee" required>
                            <option value="">Sélectionner une ville</option>
                            <?php 
                            $result_villes->data_seek(0); // Réinitialiser le pointeur
                            while($ville = $result_villes->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $ville['numVille']; ?>">
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
                            <option value="">Sélectionner un type</option>
                            <?php while($type = $result_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['idTypeEtape']; ?>">
                                <?php echo $type['nomTypeEtape']; ?>
                            </option>
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