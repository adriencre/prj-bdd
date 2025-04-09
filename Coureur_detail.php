<?php
require_once 'config.php';
include 'header.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si un ID de coureur est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Coureur.php');
    exit;
}

$coureur_id = $_GET['id'];

// Récupération des informations du coureur
$sql_coureur = "SELECT c.numDossard, c.nom, c.prenom, c.DN, c.numEquipe, e.nomEquipe, p.codePays, p.nomPays 
               FROM Coureur c 
               LEFT JOIN Equipe e ON c.numEquipe = e.numEquipe 
               LEFT JOIN Pays p ON c.codePays = p.codePays 
               WHERE c.numDossard = ?";
$stmt = $conn->prepare($sql_coureur);
$stmt->bind_param("i", $coureur_id);
$stmt->execute();
$result_coureur = $stmt->get_result();

if ($result_coureur->num_rows == 0) {
    echo '<div class="container my-5"><div class="alert alert-danger">Coureur non trouvé.</div></div>';
    include 'footer.php';
    $conn->close();
    exit;
}

$coureur = $result_coureur->fetch_assoc();

// Calcul de l'âge
$dob = new DateTime($coureur['DN']);
$now = new DateTime();
$age = $now->diff($dob)->y;

// Récupération des performances du coureur
$sql_performances = "SELECT p.numEtape, p.temps, e.dateEtape, e.distance, v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, 
                   t.nomTypeEtape, b.reductionTemps
                   FROM Performance p
                   LEFT JOIN Etape e ON p.numEtape = e.numEtape
                   LEFT JOIN Ville v1 ON e.numVille = v1.numVille
                   LEFT JOIN Ville v2 ON e.numVille_1 = v2.numVille
                   LEFT JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
                   LEFT JOIN Bonification b ON p.numDossard = b.numDossard AND p.numEtape = b.numEtape
                   WHERE p.numDossard = ?
                   ORDER BY e.dateEtape";
$stmt = $conn->prepare($sql_performances);
$stmt->bind_param("i", $coureur_id);
$stmt->execute();
$result_performances = $stmt->get_result();

// Récupération du classement général
$sql_classement = "
    SELECT 
        c.numDossard,
        c.nom,
        c.prenom,
        SEC_TO_TIME(SUM(TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')))) as temps_total,
        RANK() OVER (ORDER BY SUM(TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')))) as classement
    FROM 
        Coureur c
        JOIN Performance p ON c.numDossard = p.numDossard
    WHERE 
        p.temps IS NOT NULL
    GROUP BY 
        c.numDossard
    ORDER BY 
        temps_total ASC";

// Cette requête pourrait échouer si vous n'avez pas une version de MySQL qui prend en charge RANK()
// Dans ce cas, essayons une alternative
try {
    $result_classement = $conn->query($sql_classement);
    
    if ($result_classement === false) {
        throw new Exception("Erreur dans la requête: " . $conn->error);
    }
    
    $classement_position = 0;
    $temps_total = null;
    
    while($row = $result_classement->fetch_assoc()) {
        if($row['numDossard'] == $coureur_id) {
            $classement_position = $row['classement'];
            $temps_total = $row['temps_total'];
            break;
        }
    }
} catch (Exception $e) {
    // Si la requête avec RANK() échoue, on utilise une approche alternative
    $sql_alt = "
        SELECT 
            c.numDossard,
            c.nom,
            c.prenom,
            SEC_TO_TIME(SUM(TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')))) as temps_total
        FROM 
            Coureur c
            JOIN Performance p ON c.numDossard = p.numDossard
        WHERE 
            p.temps IS NOT NULL
        GROUP BY 
            c.numDossard
        ORDER BY 
            temps_total ASC";
            
    $result_alt = $conn->query($sql_alt);
    
    if ($result_alt === false) {
        $classement_position = "N/A";
        $temps_total = "N/A";
    } else {
        $position = 1;
        $classement_position = "N/A";
        $temps_total = "N/A";
        
        while($row = $result_alt->fetch_assoc()) {
            if($row['numDossard'] == $coureur_id) {
                $classement_position = $position;
                $temps_total = $row['temps_total'];
                break;
            }
            $position++;
        }
    }
}

// Récupération des bonifications
$sql_bonifications = "SELECT b.numEtape, b.reductionTemps 
                    FROM Bonification b 
                    WHERE b.numDossard = ?
                    ORDER BY b.numEtape";
$stmt = $conn->prepare($sql_bonifications);
$stmt->bind_param("i", $coureur_id);
$stmt->execute();
$result_bonifications = $stmt->get_result();

// Fonction pour convertir le format de temps en secondes
function TIME_TO_SEC($time) {
    $parts = explode(':', $time);
    if (count($parts) === 3) {
        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }
    return 0;
}

// Fonction pour convertir les secondes en format de temps
function SEC_TO_TIME($seconds) {
    // Arrondir les secondes à l'entier le plus proche pour éviter les avertissements de conversion
    $seconds = round($seconds);
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $coureur['prenom'] . ' ' . $coureur['nom']; ?></h1>
        <a href="Coureur.php" class="btn btn-outline-primary">Retour aux coureurs</a>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    Informations personnelles
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php
                        // Définir l'image du coureur
                        if ($coureur['nom'] == 'VINGEGAARD' && $coureur['prenom'] == 'JONAS') {
                            echo '<img src="J.VINGEGAARD.jpg" alt="Jonas Vingegaard" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">';
                        } elseif ($coureur['nom'] == 'POGACAR' && $coureur['prenom'] == 'TADEJ') {
                            echo '<img src="T.POGACAR.jpg" alt="Tadej Pogacar" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">';
                        } else {
                            echo '<i class="fas fa-user-circle fa-7x text-primary"></i>';
                        }
                        ?>
                    </div>
                    <table class="table">
                        <tr>
                            <th>Dossard</th>
                            <td><?php echo $coureur['numDossard']; ?></td>
                        </tr>
                        <tr>
                            <th>Date de naissance</th>
                            <td><?php echo date('d/m/Y', strtotime($coureur['DN'])); ?></td>
                        </tr>
                        <tr>
                            <th>Âge</th>
                            <td><?php echo $age; ?> ans</td>
                        </tr>
                        <tr>
                            <th>Pays</th>
                            <td><?php echo $coureur['nomPays']; ?></td>
                        </tr>
                        <tr>
                            <th>Équipe</th>
                            <td>
                                <a href="Team_detail.php?id=<?php echo $coureur['numEquipe']; ?>">
                                    <?php echo $coureur['nomEquipe']; ?>
                                </a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    Statistiques
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Classement général</th>
                            <td><?php echo $classement_position !== 0 && $classement_position !== 'N/A' ? $classement_position : 'Non classé'; ?></td>
                        </tr>
                        <tr>
                            <th>Temps total</th>
                            <td><?php echo isset($temps_total) && $temps_total !== 'N/A' ? $temps_total : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Étapes participées</th>
                            <td><?php echo $result_performances->num_rows; ?></td>
                        </tr>
                        <tr>
                            <th>Bonifications</th>
                            <td><?php echo $result_bonifications->num_rows > 0 ? $result_bonifications->num_rows : 'Aucune'; ?></td>
                        </tr>
                        <?php
                        // Calcul des temps minimum, maximum et moyen
                        if ($result_performances->num_rows > 0) {
                            // Réinitialiser le pointeur du résultat
                            $result_performances->data_seek(0);
                            
                            $temps_array = [];
                            $total_seconds = 0;
                            
                            while ($perf = $result_performances->fetch_assoc()) {
                                // Convertir le temps en secondes
                                $temps_sec = TIME_TO_SEC($perf['temps']);
                                $temps_array[] = $temps_sec;
                                $total_seconds += $temps_sec;
                            }
                            
                            // Calculer les statistiques
                            $temps_min = min($temps_array);
                            $temps_max = max($temps_array);
                            $temps_moyen = $total_seconds / count($temps_array);
                            
                            // Réinitialiser le pointeur pour les autres utilisations
                            $result_performances->data_seek(0);
                        ?>
                        <tr>
                            <th>Temps minimum</th>
                            <td><?php echo SEC_TO_TIME($temps_min); ?></td>
                        </tr>
                        <tr>
                            <th>Temps maximum</th>
                            <td><?php echo SEC_TO_TIME($temps_max); ?></td>
                        </tr>
                        <tr>
                            <th>Temps moyen</th>
                            <td><?php echo SEC_TO_TIME($temps_moyen); ?></td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
            </div>
            
            <?php if ($result_bonifications->num_rows > 0): ?>
            <div class="card">
                <div class="card-header">
                    Bonifications obtenues
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Étape</th>
                                <th>Réduction (secondes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($bonif = $result_bonifications->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $bonif['numEtape']; ?></td>
                                <td><?php echo $bonif['reductionTemps']; ?> sec</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Performances aux étapes
                </div>
                <div class="card-body">
                    <?php if ($result_performances->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Étape</th>
                                    <th>Date</th>
                                    <th>Parcours</th>
                                    <th>Type</th>
                                    <th>Distance</th>
                                    <th>Temps</th>
                                    <th>Bonification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_distance = 0;
                                while($perf = $result_performances->fetch_assoc()) {
                                    $total_distance += $perf['distance'];
                                ?>
                                <tr>
                                    <td><?php echo $perf['numEtape']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($perf['dateEtape'])); ?></td>
                                    <td><?php echo $perf['villeDepart'] . ' - ' . $perf['villeArrivee']; ?></td>
                                    <td><?php echo $perf['nomTypeEtape']; ?></td>
                                    <td><?php echo $perf['distance']; ?> km</td>
                                    <td><?php echo $perf['temps']; ?></td>
                                    <td>
                                        <?php
                                        if (!empty($perf['reductionTemps'])) {
                                            echo '-' . $perf['reductionTemps'] . ' sec';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <td colspan="4"><strong>Total</strong></td>
                                    <td><strong><?php echo $total_distance; ?> km</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="alert alert-info">Aucune performance enregistrée pour ce coureur.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    Actions
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="edit_coureur.php?id=<?php echo $coureur_id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-1"></i> Modifier le coureur
                        </a>
                        <a href="#" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-1"></i> Supprimer le coureur
                        </a>
                    </div>
                </div>
            </div>
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
                    Êtes-vous sûr de vouloir supprimer le coureur <strong><?php echo $coureur['prenom'] . ' ' . $coureur['nom']; ?></strong> (dossard n°<?php echo $coureur['numDossard']; ?>) ?
                    <br><br>
                    Cette action est irréversible. Toutes les performances et bonifications associées seront également supprimées.
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