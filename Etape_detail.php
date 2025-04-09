<?php
require_once 'config.php';
include 'header.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier si un ID d'étape est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: Etape.php');
    exit;
}

$etape_id = $_GET['id'];

// Récupération des informations de l'étape
$sql_etape = "SELECT e.numEtape, e.dateEtape, e.distance, 
              v1.nomVille AS villeDepart, v1.codePays AS paysDepart, 
              v2.nomVille AS villeArrivee, v2.codePays AS paysArrivee,
              t.nomTypeEtape, t.idTypeEtape
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
    echo '<div class="container my-5"><div class="alert alert-danger">Étape non trouvée.</div></div>';
    include 'footer.php';
    $conn->close();
    exit;
}

$etape = $result_etape->fetch_assoc();

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

// Récupération des performances des coureurs pour cette étape
$sql_performances = "SELECT p.numDossard, p.temps, c.nom, c.prenom, e.nomEquipe, pa.nomPays, b.reductionTemps
                   FROM Performance p
                   JOIN Coureur c ON p.numDossard = c.numDossard
                   JOIN Equipe e ON c.numEquipe = e.numEquipe
                   JOIN Pays pa ON c.codePays = pa.codePays
                   LEFT JOIN Bonification b ON p.numDossard = b.numDossard AND p.numEtape = b.numEtape
                   WHERE p.numEtape = ?
                   ORDER BY p.temps IS NULL, p.temps ASC";
                   
$stmt = $conn->prepare($sql_performances);
$stmt->bind_param("i", $etape_id);
$stmt->execute();
$result_performances = $stmt->get_result();

// Si la requête échoue, afficher l'erreur
if ($result_performances === false) {
    echo '<div class="container my-5"><div class="alert alert-danger">Erreur SQL: ' . $conn->error . '</div></div>';
    include 'footer.php';
    $conn->close();
    exit;
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Étape <?php echo $etape['numEtape']; ?></h1>
        <a href="Etape.php" class="btn btn-outline-primary">Retour aux étapes</a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="card-title">Informations générales</h5>
                    <table class="table">
                        <tr>
                            <th>Date</th>
                            <td><?php echo date('d/m/Y', strtotime($etape['dateEtape'])); ?></td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>
                                <span class="badge bg-<?php 
                                    switch($etape['idTypeEtape']) {
                                        case 1: echo 'warning'; break; // Accidentée
                                        case 2: echo 'success'; break; // Plat
                                        case 3: echo 'danger'; break;  // Montagne
                                        case 4: echo 'primary'; break; // CLM Individuel
                                        default: echo 'secondary';
                                    }
                                ?>"><?php echo $etape['nomTypeEtape']; ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Distance</th>
                            <td><?php echo $etape['distance']; ?> km</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="card-title">Parcours</h5>
                    <table class="table">
                        <tr>
                            <th>Ville de départ</th>
                            <td><?php echo $etape['villeDepart']; ?></td>
                        </tr>
                        <tr>
                            <th>Ville d'arrivée</th>
                            <td><?php echo $etape['villeArrivee']; ?></td>
                        </tr>
                        <tr>
                            <th>Pays</th>
                            <td>
                                <?php
                                try {
                                    $pays_depart = $conn->query("SELECT nomPays FROM Pays WHERE codePays = '" . $etape['paysDepart'] . "'");
                                    $pays_arrivee = $conn->query("SELECT nomPays FROM Pays WHERE codePays = '" . $etape['paysArrivee'] . "'");
                                    
                                    if ($pays_depart !== false && $pays_arrivee !== false) {
                                        $pays_depart = $pays_depart->fetch_assoc();
                                        $pays_arrivee = $pays_arrivee->fetch_assoc();
                                        
                                        echo $pays_depart['nomPays'];
                                        if ($etape['paysDepart'] != $etape['paysArrivee']) {
                                            echo ' - ' . $pays_arrivee['nomPays'];
                                        }
                                    } else {
                                        echo "Information non disponible";
                                    }
                                } catch (Exception $e) {
                                    echo "Information non disponible";
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                        // Calcul des temps minimum, maximum et moyen
                        if ($result_performances->num_rows > 0) {
                            // Réinitialiser le pointeur du résultat
                            $result_performances->data_seek(0);
                            
                            $temps_array = [];
                            $total_seconds = 0;
                            $count = 0;
                            
                            while ($perf = $result_performances->fetch_assoc()) {
                                if (!empty($perf['temps'])) {
                                    // Convertir le temps en secondes
                                    $temps_sec = TIME_TO_SEC($perf['temps']);
                                    $temps_array[] = $temps_sec;
                                    $total_seconds += $temps_sec;
                                    $count++;
                                }
                            }
                            
                            // Calculer les statistiques
                            if ($count > 0) {
                                $temps_min = min($temps_array);
                                $temps_max = max($temps_array);
                                $temps_moyen = $total_seconds / $count;
                                
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
                        <?php 
                            }
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <h2 class="mb-3">Classement de l'étape</h2>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Coureur</th>
                    <th>Équipe</th>
                    <th>Pays</th>
                    <th>Temps</th>
                    <th>Bonification</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                if ($result_performances->num_rows > 0) {
                    while($perf = $result_performances->fetch_assoc()) {
                ?>
                <tr>
                    <td><?php echo $position; ?></td>
                    <td>
                        <a href="Coureur_detail.php?id=<?php echo $perf['numDossard']; ?>">
                            <?php echo $perf['prenom'] . ' ' . $perf['nom']; ?>
                        </a>
                    </td>
                    <td><?php echo $perf['nomEquipe']; ?></td>
                    <td><?php echo $perf['nomPays']; ?></td>
                    <td><?php echo !empty($perf['temps']) ? $perf['temps'] : 'Non terminé'; ?></td>
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
                <?php
                        $position++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Aucune performance enregistrée pour cette étape.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <h2 class="mt-5 mb-3">Statistiques de l'étape</h2>
    
    <?php
    // Requête pour obtenir les statistiques de l'étape
    $sql_stats = "SELECT 
                    COUNT(p.numDossard) AS nb_coureurs,
                    MIN(p.temps) AS meilleur_temps,
                    MAX(p.temps) AS pire_temps
                  FROM Performance p
                  WHERE p.numEtape = ? AND p.temps IS NOT NULL";
                  
    $stmt = $conn->prepare($sql_stats);
    $stmt->bind_param("i", $etape_id);
    $stmt->execute();
    $result_stats = $stmt->get_result();
    
    if ($result_stats === false) {
        $stats = [
            'nb_coureurs' => 0,
            'meilleur_temps' => 'N/A',
            'pire_temps' => 'N/A',
            'temps_moyen' => 'N/A'
        ];
    } else {
        $stats = $result_stats->fetch_assoc();
        
        // Calcul du temps moyen (uniquement si MySQL ne supporte pas AVG avec les TIME)
        try {
            $sql_avg = "SELECT SEC_TO_TIME(AVG(TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')))) AS temps_moyen
                       FROM Performance p
                       WHERE p.numEtape = ? AND p.temps IS NOT NULL";
            $stmt_avg = $conn->prepare($sql_avg);
            $stmt_avg->bind_param("i", $etape_id);
            $stmt_avg->execute();
            $result_avg = $stmt_avg->get_result();
            
            if ($result_avg !== false) {
                $avg = $result_avg->fetch_assoc();
                $stats['temps_moyen'] = $avg['temps_moyen'];
            } else {
                $stats['temps_moyen'] = 'N/A';
            }
        } catch (Exception $e) {
            $stats['temps_moyen'] = 'N/A';
        }
    }
    
    // Requête pour obtenir les bonifications de l'étape
    $sql_bonifications = "SELECT b.numDossard, b.reductionTemps, c.nom, c.prenom
                        FROM Bonification b
                        JOIN Coureur c ON b.numDossard = c.numDossard
                        WHERE b.numEtape = ?
                        ORDER BY b.reductionTemps DESC";
                        
    $stmt = $conn->prepare($sql_bonifications);
    $stmt->bind_param("i", $etape_id);
    $stmt->execute();
    $result_bonifications = $stmt->get_result();
    
    if ($result_bonifications === false) {
        echo '<div class="alert alert-warning">Impossible de récupérer les informations de bonification.</div>';
    }
    ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Performances
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Nombre de coureurs</th>
                            <td><?php echo $stats['nb_coureurs']; ?></td>
                        </tr>
                        <tr>
                            <th>Meilleur temps</th>
                            <td><?php echo $stats['meilleur_temps']; ?></td>
                        </tr>
                        <tr>
                            <th>Pire temps</th>
                            <td><?php echo $stats['pire_temps']; ?></td>
                        </tr>
                        <tr>
                            <th>Temps moyen</th>
                            <td><?php echo isset($stats['temps_moyen']) ? $stats['temps_moyen'] : 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    Bonifications accordées
                </div>
                <div class="card-body">
                    <?php if ($result_bonifications && $result_bonifications->num_rows > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Coureur</th>
                                <th>Réduction (secondes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($bonif = $result_bonifications->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="Coureur_detail.php?id=<?php echo $bonif['numDossard']; ?>">
                                        <?php echo $bonif['prenom'] . ' ' . $bonif['nom']; ?>
                                    </a>
                                </td>
                                <td><?php echo $bonif['reductionTemps']; ?> sec</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="alert alert-info">Aucune bonification accordée pour cette étape.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            Actions
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="edit_etape.php?id=<?php echo $etape_id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Modifier l'étape
                </a>
                <a href="#" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash me-1"></i> Supprimer l'étape
                </a>
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
                    <p>Êtes-vous sûr de vouloir supprimer l'étape n°<?php echo $etape['numEtape']; ?> (<?php echo $etape['villeDepart'] . ' - ' . $etape['villeArrivee']; ?>) ?</p>
                    <p class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Cette action est irréversible. Toutes les performances et bonifications associées à cette étape seront également supprimées.
                    </p>
                    <?php if ($result_performances->num_rows > 0): ?>
                    <p class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Cette étape a des performances enregistrées. La suppression n'est pas possible sans supprimer d'abord ces performances.
                    </p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="delete_etape.php?id=<?php echo $etape_id; ?>" class="btn btn-danger" <?php echo ($result_performances->num_rows > 0) ? 'disabled' : ''; ?>>
                        Supprimer définitivement
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>