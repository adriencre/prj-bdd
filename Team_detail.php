<?php
require_once 'config.php';
include 'header.php';

// Vérifier si un ID d'équipe est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Team.php');
    exit;
}

$team_id = $_GET['id'];

// Récupération des informations de l'équipe
$sql_equipe = "SELECT e.numEquipe, e.nomEquipe, p.nomPays 
              FROM Equipe e 
              LEFT JOIN Pays p ON e.codePays = p.codePays 
              WHERE e.numEquipe = ?";
$stmt = $conn->prepare($sql_equipe);
$stmt->bind_param("s", $team_id);
$stmt->execute();
$result_equipe = $stmt->get_result();

if ($result_equipe->num_rows == 0) {
    header('Location: Team.php');
    exit;
}

$equipe = $result_equipe->fetch_assoc();

// Récupération des coureurs de l'équipe
$sql_coureurs = "SELECT c.numDossard, c.nom, c.prenom, c.DN, p.nomPays 
                FROM Coureur c 
                LEFT JOIN Pays p ON c.codePays = p.codePays 
                WHERE c.numEquipe = ? 
                ORDER BY c.nom";
$stmt = $conn->prepare($sql_coureurs);
$stmt->bind_param("s", $team_id);
$stmt->execute();
$result_coureurs = $stmt->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $equipe['nomEquipe']; ?></h1>
        <a href="Team.php" class="btn btn-outline-primary">Retour aux équipes</a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Pays:</strong> <?php echo $equipe['nomPays']; ?></p>
                    <p><strong>Identifiant:</strong> <?php echo $equipe['numEquipe']; ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Nombre de coureurs:</strong> <?php echo $result_coureurs->num_rows; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <h2 class="mb-3">Coureurs de l'équipe</h2>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Dossard</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date de naissance</th>
                    <th>Âge</th>
                    <th>Pays</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_coureurs->num_rows > 0) {
                    while($coureur = $result_coureurs->fetch_assoc()) {
                        // Calcul de l'âge
                        $dob = new DateTime($coureur['DN']);
                        $now = new DateTime();
                        $age = $now->diff($dob)->y;
                ?>
                <tr>
                    <td><?php echo $coureur['numDossard']; ?></td>
                    <td><?php echo $coureur['nom']; ?></td>
                    <td><?php echo $coureur['prenom']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($coureur['DN'])); ?></td>
                    <td><?php echo $age; ?> ans</td>
                    <td><?php echo $coureur['nomPays']; ?></td>
                    <td>
                        <a href="Coureur_detail.php?id=<?php echo $coureur['numDossard']; ?>" class="btn btn-sm btn-info">Détails</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>Aucun coureur trouvé pour cette équipe.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <h2 class="mt-5 mb-3">Performances de l'équipe</h2>
    
    <?php
    // Récupération des performances des coureurs de l'équipe
    $sql_performances = "SELECT e.numEtape, e.dateEtape, v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, 
                       COUNT(DISTINCT p.numDossard) AS nb_coureurs_finis, MIN(p.temps) AS meilleur_temps
                       FROM Etape e
                       LEFT JOIN Ville v1 ON e.numVille = v1.numVille
                       LEFT JOIN Ville v2 ON e.numVille_1 = v2.numVille
                       LEFT JOIN Performance p ON e.numEtape = p.numEtape
                       LEFT JOIN Coureur c ON p.numDossard = c.numDossard
                       WHERE c.numEquipe = ?
                       GROUP BY e.numEtape
                       ORDER BY e.dateEtape";
    $stmt = $conn->prepare($sql_performances);
    $stmt->bind_param("s", $team_id);
    $stmt->execute();
    $result_performances = $stmt->get_result();
    
    if ($result_performances->num_rows > 0) {
    ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Étape</th>
                    <th>Date</th>
                    <th>Parcours</th>
                    <th>Coureurs terminés</th>
                    <th>Meilleur temps</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while($perf = $result_performances->fetch_assoc()) {
                    // Formater le temps
                    $temps = !empty($perf['meilleur_temps']) ? 
                            date('H:i:s', strtotime($perf['meilleur_temps'])) : 'N/A';
                ?>
                <tr>
                    <td><?php echo $perf['numEtape']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($perf['dateEtape'])); ?></td>
                    <td><?php echo $perf['villeDepart'] . ' - ' . $perf['villeArrivee']; ?></td>
                    <td><?php echo $perf['nb_coureurs_finis']; ?></td>
                    <td><?php echo $temps; ?></td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
    } else {
        echo "<p class='alert alert-info'>Aucune performance enregistrée pour cette équipe.</p>";
    }
    ?>
</div>

<?php
include 'footer.php';
$conn->close();
?>