<?php
require_once 'config.php';
include 'header.php';

// Filtre pour le nombre d'étapes (optionnel)
$etape_limite = isset($_GET['etape']) ? intval($_GET['etape']) : 0;

// Récupération du nombre total d'étapes
$sql_nb_etapes = "SELECT MAX(numEtape) AS max_etape FROM Etape";
$result_nb_etapes = $conn->query($sql_nb_etapes);
$row_nb_etapes = $result_nb_etapes->fetch_assoc();
$nb_total_etapes = $row_nb_etapes['max_etape'];

// Si aucune limite spécifiée ou limite supérieure au nombre total, prendre toutes les étapes
if ($etape_limite <= 0 || $etape_limite > $nb_total_etapes) {
    $etape_limite = $nb_total_etapes;
}

// Requête pour obtenir la liste des étapes disponibles
$sql_etapes = "SELECT numEtape, dateEtape FROM Etape ORDER BY numEtape";
$result_etapes = $conn->query($sql_etapes);

// Requête pour le classement général (temps total par coureur jusqu'à l'étape limite)
$sql_classement = "
    SELECT 
        c.numDossard,
        c.nom,
        c.prenom,
        c.codePays,
        p.nomPays,
        e.numEquipe,
        e.nomEquipe,
        COUNT(DISTINCT perf.numEtape) AS nb_etapes,
        SEC_TO_TIME(SUM(TIME_TO_SEC(STR_TO_DATE(perf.temps, '%h:%i:%s %p')))) as temps_total,
        IFNULL(SUM(b.reductionTemps), 0) AS total_bonifications
    FROM 
        Coureur c
        JOIN Performance perf ON c.numDossard = perf.numDossard
        JOIN Equipe e ON c.numEquipe = e.numEquipe
        JOIN Pays p ON c.codePays = p.codePays
        LEFT JOIN Bonification b ON c.numDossard = b.numDossard AND perf.numEtape = b.numEtape
    WHERE 
        perf.temps IS NOT NULL
        AND perf.numEtape <= ?
    GROUP BY 
        c.numDossard
    HAVING 
        nb_etapes > 0
    ORDER BY 
        temps_total ASC";

$stmt_classement = $conn->prepare($sql_classement);
$stmt_classement->bind_param("i", $etape_limite);
$stmt_classement->execute();
$result_classement = $stmt_classement->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Classement Général</h1>
        <div>
            <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
        </div>
    </div>
    
    <!-- Filtre par étape -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="etape" class="form-label">Voir le classement jusqu'à l'étape :</label>
                    <select name="etape" id="etape" class="form-select">
                        <?php while($etape = $result_etapes->fetch_assoc()): ?>
                        <option value="<?php echo $etape['numEtape']; ?>" <?php echo ($etape['numEtape'] == $etape_limite) ? 'selected' : ''; ?>>
                            Étape <?php echo $etape['numEtape']; ?> (<?php echo date('d/m/Y', strtotime($etape['dateEtape'])); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Afficher</button>
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <p class="mb-0"><strong>Étapes comptabilisées:</strong> 1 à <?php echo $etape_limite; ?> sur <?php echo $nb_total_etapes; ?></p>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau du classement -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Coureur</th>
                    <th>Équipe</th>
                    <th>Pays</th>
                    <th>Étapes courues</th>
                    <th>Temps total</th>
                    <th>Bonification</th>
                    <th>Écart</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                $first_time = null;
                
                if ($result_classement->num_rows > 0) {
                    while($row = $result_classement->fetch_assoc()) {
                        // Pour le premier coureur, on sauvegarde son temps
                        if ($position == 1) {
                            $first_time = $row['temps_total'];
                            $ecart = "-";
                        } else {
                            // Calculer l'écart avec le premier
                            $time1 = strtotime($first_time);
                            $time2 = strtotime($row['temps_total']);
                            $diff_seconds = $time2 - $time1;
                            
                            $hours = floor($diff_seconds / 3600);
                            $mins = floor(($diff_seconds % 3600) / 60);
                            $secs = $diff_seconds % 60;
                            
                            if ($hours > 0) {
                                $ecart = "+{$hours}h {$mins}m {$secs}s";
                            } else {
                                $ecart = "+{$mins}m {$secs}s";
                            }
                        }
                        
                        // Déterminer l'image du coureur
                        $image_path = "";
                        if ($row['nom'] == 'VINGEGAARD' && $row['prenom'] == 'JONAS') {
                            $image_path = "J.VINGEGAARD.jpg";
                        } elseif ($row['nom'] == 'POGACAR' && $row['prenom'] == 'TADEJ') {
                            $image_path = "T.POGACAR.jpg";
                        } else {
                            $image_path = "image-" . ($position % 20 + 1) . ".png";
                        }
                ?>
                <tr>
                    <td>
                        <div class="position-badge"><?php echo $position; ?></div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $row['prenom'] . ' ' . $row['nom']; ?>" class="me-2 rounded-circle" style="width: 30px; height: 30px; object-fit: cover;">
                            <a href="Coureur_detail.php?id=<?php echo $row['numDossard']; ?>">
                                <?php echo $row['prenom'] . ' ' . $row['nom']; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <a href="Team_detail.php?id=<?php echo $row['numEquipe']; ?>">
                            <?php echo $row['nomEquipe']; ?>
                        </a>
                    </td>
                    <td><?php echo $row['nomPays']; ?></td>
                    <td><?php echo $row['nb_etapes']; ?> / <?php echo $etape_limite; ?></td>
                    <td><?php echo $row['temps_total']; ?></td>
                    <td><?php echo $row['total_bonifications'] > 0 ? "-" . $row['total_bonifications'] . " sec" : "-"; ?></td>
                    <td><?php echo $ecart; ?></td>
                </tr>
                <?php
                        $position++;
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>Aucun résultat trouvé</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>