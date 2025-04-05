<?php
require_once 'config.php';
include 'header.php';

// Récupération des types d'étapes pour le filtre
$sql_types = "SELECT idTypeEtape, nomTypeEtape FROM TypeEtape ORDER BY nomTypeEtape";
$result_types = $conn->query($sql_types);

// Filtre
$filtre_type = isset($_GET['type']) ? intval($_GET['type']) : 0;

// Construction de la requête avec filtre
$sql_etapes = "SELECT e.numEtape, e.dateEtape, e.distance, 
              v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, 
              t.nomTypeEtape, t.idTypeEtape,
              COUNT(p.numDossard) AS participants
              FROM Etape e
              LEFT JOIN Ville v1 ON e.numVille = v1.numVille
              LEFT JOIN Ville v2 ON e.numVille_1 = v2.numVille
              LEFT JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
              LEFT JOIN Performance p ON e.numEtape = p.numEtape
              WHERE 1=1";

if ($filtre_type > 0) {
    $sql_etapes .= " AND t.idTypeEtape = " . $filtre_type;
}

$sql_etapes .= " GROUP BY e.numEtape ORDER BY e.dateEtape";
$result_etapes = $conn->query($sql_etapes);

// Calcul de la distance totale
$sql_distance_totale = "SELECT SUM(distance) AS total FROM Etape";
if ($filtre_type > 0) {
    $sql_distance_totale .= " WHERE idTypeEtape = " . $filtre_type;
}
$result_distance = $conn->query($sql_distance_totale);
$row_distance = $result_distance->fetch_assoc();
$distance_totale = $row_distance['total'];

// Vérifier s'il y a un message de suppression
$deleted = isset($_GET['deleted']) && $_GET['deleted'] == 1;
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Étapes du Tour de France 2022</h1>
        <a href="add_etape.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Ajouter une étape
        </a>
    </div>
    
    <?php if ($deleted): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        L'étape a été supprimée avec succès !
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">Filtre par type d'étape</label>
                    <select name="type" id="type" class="form-select">
                        <option value="0">Tous les types</option>
                        <?php 
                        $result_types->data_seek(0);
                        while($type = $result_types->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $type['idTypeEtape']; ?>" <?php echo ($filtre_type == $type['idTypeEtape']) ? 'selected' : ''; ?>>
                            <?php echo $type['nomTypeEtape']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <p class="mb-0"><strong>Distance totale:</strong> <?php echo number_format($distance_totale, 0, ',', ' '); ?> km</p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des étapes -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Départ</th>
                    <th>Arrivée</th>
                    <th>Type</th>
                    <th>Distance</th>
                    <th>Participants</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_etapes->num_rows > 0) {
                    while($etape = $result_etapes->fetch_assoc()) {
                ?>
                <tr>
                    <td><?php echo $etape['numEtape']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($etape['dateEtape'])); ?></td>
                    <td><?php echo $etape['villeDepart']; ?></td>
                    <td><?php echo $etape['villeArrivee']; ?></td>
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
                    <td><?php echo $etape['distance']; ?> km</td>
                    <td><?php echo $etape['participants']; ?></td>
                    <td>
                        <a href="Etape_detail.php?id=<?php echo $etape['numEtape']; ?>" class="btn btn-sm btn-info" title="Voir les détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_etape.php?id=<?php echo $etape['numEtape']; ?>" class="btn btn-sm btn-warning" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>Aucune étape trouvée.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Statistiques des étapes -->
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Statistiques des étapes</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <?php
                    // Étape la plus longue
                    $sql_longest = "SELECT e.numEtape, e.distance, v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee 
                                  FROM Etape e 
                                  JOIN Ville v1 ON e.numVille = v1.numVille 
                                  JOIN Ville v2 ON e.numVille_1 = v2.numVille 
                                  ORDER BY e.distance DESC LIMIT 1";
                    $result_longest = $conn->query($sql_longest);
                    $longest = $result_longest->fetch_assoc();
                    ?>
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Étape la plus longue</h5>
                            <p class="card-text">
                                <strong>Étape <?php echo $longest['numEtape']; ?></strong><br>
                                <?php echo $longest['villeDepart'] . ' - ' . $longest['villeArrivee']; ?><br>
                                <span class="text-primary h4"><?php echo $longest['distance']; ?> km</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <?php
                    // Étape la plus courte
                    $sql_shortest = "SELECT e.numEtape, e.distance, v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee 
                                   FROM Etape e 
                                   JOIN Ville v1 ON e.numVille = v1.numVille 
                                   JOIN Ville v2 ON e.numVille_1 = v2.numVille 
                                   ORDER BY e.distance ASC LIMIT 1";
                    $result_shortest = $conn->query($sql_shortest);
                    $shortest = $result_shortest->fetch_assoc();
                    ?>
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Étape la plus courte</h5>
                            <p class="card-text">
                                <strong>Étape <?php echo $shortest['numEtape']; ?></strong><br>
                                <?php echo $shortest['villeDepart'] . ' - ' . $shortest['villeArrivee']; ?><br>
                                <span class="text-primary h4"><?php echo $shortest['distance']; ?> km</span>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <?php
                    // Distance moyenne par étape
                    $sql_avg = "SELECT AVG(distance) AS avg_distance FROM Etape";
                    $result_avg = $conn->query($sql_avg);
                    $avg = $result_avg->fetch_assoc();
                    
                    // Nombre d'étapes par type
                    $sql_type_count = "SELECT t.nomTypeEtape, COUNT(e.numEtape) AS count 
                                      FROM TypeEtape t 
                                      LEFT JOIN Etape e ON t.idTypeEtape = e.idTypeEtape 
                                      GROUP BY t.idTypeEtape";
                    $result_type_count = $conn->query($sql_type_count);
                    $type_counts = [];
                    while($row = $result_type_count->fetch_assoc()) {
                        $type_counts[$row['nomTypeEtape']] = $row['count'];
                    }
                    ?>
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Distance moyenne</h5>
                            <p class="card-text">
                                <span class="text-primary h4"><?php echo number_format($avg['avg_distance'], 1, ',', ' '); ?> km</span>
                            </p>
                            <h6>Répartition des étapes</h6>
                            <ul class="list-unstyled">
                                <?php foreach($type_counts as $type => $count): ?>
                                <li><?php echo $type; ?>: <?php echo $count; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Villes ayant accueilli des étapes -->
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Villes ayant accueilli des étapes</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ville</th>
                            <th>Pays</th>
                            <th class="text-center">Nombre de départs</th>
                            <th class="text-center">Nombre d'arrivées</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_villes = "SELECT v.nomVille, p.nomPays,
                                     COUNT(DISTINCT CASE WHEN v.numVille = e.numVille THEN e.numEtape END) as nb_departs,
                                     COUNT(DISTINCT CASE WHEN v.numVille = e.numVille_1 THEN e.numEtape END) as nb_arrivees
                                     FROM Ville v
                                     JOIN Pays p ON v.codePays = p.codePays
                                     LEFT JOIN Etape e ON v.numVille = e.numVille OR v.numVille = e.numVille_1
                                     GROUP BY v.numVille, v.nomVille, p.nomPays
                                     HAVING nb_departs > 0 OR nb_arrivees > 0
                                     ORDER BY v.nomVille";
                        
                        $result_villes = $conn->query($sql_villes);
                        if ($result_villes->num_rows > 0) {
                            while($ville = $result_villes->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td>' . $ville['nomVille'] . '</td>';
                                echo '<td>' . $ville['nomPays'] . '</td>';
                                echo '<td class="text-center">';
                                if ($ville['nb_departs'] > 0) {
                                    echo '<span class="badge bg-primary">' . $ville['nb_departs'] . '</span>';
                                } else {
                                    echo '<i class="bi bi-x-circle-fill text-muted"></i>';
                                }
                                echo '</td>';
                                echo '<td class="text-center">';
                                if ($ville['nb_arrivees'] > 0) {
                                    echo '<span class="badge bg-success">' . $ville['nb_arrivees'] . '</span>';
                                } else {
                                    echo '<i class="bi bi-x-circle-fill text-muted"></i>';
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>Aucune ville trouvée.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>