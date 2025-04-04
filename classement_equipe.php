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

// Requête pour le classement par équipe
// Le classement par équipe est calculé en prenant les 3 meilleurs coureurs de chaque équipe
$sql_classement = "
    WITH CoureurTemps AS (
        SELECT 
            c.numDossard,
            c.numEquipe,
            e.nomEquipe,
            e.codePays,
            p.nomPays,
            SEC_TO_TIME(SUM(TIME_TO_SEC(STR_TO_DATE(perf.temps, '%h:%i:%s %p')))) as temps_total
        FROM 
            Coureur c
            JOIN Performance perf ON c.numDossard = perf.numDossard
            JOIN Equipe e ON c.numEquipe = e.numEquipe
            JOIN Pays p ON e.codePays = p.codePays
        WHERE 
            perf.temps IS NOT NULL
            AND perf.numEtape <= ?
        GROUP BY 
            c.numDossard
    ),
    EquipeMeilleursCoureurs AS (
        SELECT 
            numEquipe,
            nomEquipe,
            codePays,
            nomPays,
            numDossard,
            temps_total,
            ROW_NUMBER() OVER (PARTITION BY numEquipe ORDER BY temps_total) as rang_equipe
        FROM 
            CoureurTemps
    )
    SELECT 
        e.numEquipe,
        e.nomEquipe,
        e.codePays,
        e.nomPays,
        COUNT(e.numDossard) as nb_coureurs,
        SEC_TO_TIME(SUM(TIME_TO_SEC(e.temps_total))) as temps_total
    FROM 
        EquipeMeilleursCoureurs e
    WHERE 
        e.rang_equipe <= 3
    GROUP BY 
        e.numEquipe
    ORDER BY 
        temps_total ASC";

$stmt_classement = $conn->prepare($sql_classement);
$stmt_classement->bind_param("i", $etape_limite);
$stmt_classement->execute();
$result_classement = $stmt_classement->get_result();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Classement par Équipe</h1>
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
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Le classement par équipe est calculé sur la base des temps cumulés des trois meilleurs coureurs de chaque équipe.
    </div>
    
    <!-- Tableau du classement -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Équipe</th>
                    <th>Pays</th>
                    <th>Coureurs classés</th>
                    <th>Temps total</th>
                    <th>Écart</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                $first_time = null;
                
                if ($result_classement->num_rows > 0) {
                    while($row = $result_classement->fetch_assoc()) {
                        // Pour la première équipe, on sauvegarde son temps
                        if ($position == 1) {
                            $first_time = $row['temps_total'];
                            $ecart = "-";
                        } else {
                            // Calculer l'écart avec la première
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
                ?>
                <tr>
                    <td>
                        <div class="position-badge"><?php echo $position; ?></div>
                    </td>
                    <td>
                        <a href="Team_detail.php?id=<?php echo $row['numEquipe']; ?>">
                            <?php echo $row['nomEquipe']; ?>
                        </a>
                    </td>
                    <td><?php echo $row['nomPays']; ?></td>
                    <td><?php echo $row['nb_coureurs']; ?> / 3</td>
                    <td><?php echo $row['temps_total']; ?></td>
                    <td><?php echo $ecart; ?></td>
                </tr>
                <?php
                        $position++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Aucun résultat trouvé</td></tr>";
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