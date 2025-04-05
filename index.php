<?php
require_once 'config.php';
include 'header.php';

// Récupération de la distance totale
$sql_distance = "SELECT SUM(distance) as total_distance FROM Etape";
$result_distance = $conn->query($sql_distance);
$row_distance = $result_distance->fetch_assoc();
$total_distance = $row_distance['total_distance'];

// Récupération du nombre d'équipes
$sql_equipes = "SELECT COUNT(DISTINCT numEquipe) as total_equipes FROM Equipe";
$result_equipes = $conn->query($sql_equipes);
$row_equipes = $result_equipes->fetch_assoc();
$total_equipes = $row_equipes['total_equipes'];

// Récupération du nombre de coureurs
$sql_coureurs = "SELECT COUNT(numDossard) as total_coureurs FROM Coureur";
$result_coureurs = $conn->query($sql_coureurs);
$row_coureurs = $result_coureurs->fetch_assoc();
$total_coureurs = $row_coureurs['total_coureurs'];

// Récupération du nombre d'étapes
$sql_etapes = "SELECT COUNT(numEtape) as total_etapes FROM Etape";
$result_etapes = $conn->query($sql_etapes);
$row_etapes = $result_etapes->fetch_assoc();
$total_etapes = $row_etapes['total_etapes'];

// Récupération du classement général
$sql_classement = "
    SELECT 
        c.numDossard,
        c.nom,
        c.prenom,
        e.numEquipe,
        e.nomEquipe,
        SEC_TO_TIME(SUM(TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')))) as temps_total
    FROM 
        Coureur c
        JOIN Performance p ON c.numDossard = p.numDossard
        JOIN Equipe e ON c.numEquipe = e.numEquipe
    WHERE 
        p.temps IS NOT NULL
    GROUP BY 
        c.numDossard
    ORDER BY 
        temps_total ASC
    LIMIT 3";

$result_classement = $conn->query($sql_classement);
?>

<div class="container my-5">
<!-- Statistiques générales -->
<div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="d-flex justify-content-center">
                    <i class="fas fa-road fa-3x text-primary my-3"></i>
                </div>
                <h3><?php echo number_format($total_distance, 0, ',', ' '); ?> km</h3>
                <p>Distance Totale</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="d-flex justify-content-center">
                    <i class="fas fa-users fa-3x text-success my-3"></i>
                </div>
                <h3><?php echo $total_equipes; ?></h3>
                <p>Équipes</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="d-flex justify-content-center">
                    <i class="fas fa-user-circle fa-3x text-danger my-3"></i>
                </div>
                <h3><?php echo $total_coureurs; ?></h3>
                <p>Coureurs</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="d-flex justify-content-center">
                    <i class="fas fa-flag-checkered fa-3x text-warning my-3"></i>
                </div>
                <h3><?php echo $total_etapes; ?></h3>
                <p>Étapes</p>
            </div>
        </div>
        
    </div>

    <!-- Kilomètres par Type d'Étape -->
    <div class="row mb-4">
        <?php
        // Récupération des kilomètres par type d'étape
        $sql_kilometres_type = "
            SELECT 
                te.nomTypeEtape,
                COUNT(e.numEtape) AS nombre_etapes,
                SUM(e.distance) AS total_kilometres
            FROM 
                TypeEtape te
            LEFT JOIN 
                Etape e ON te.idTypeEtape = e.idTypeEtape
            GROUP BY 
                te.nomTypeEtape
            ORDER BY 
                total_kilometres DESC";

        $result_kilometres_type = $conn->query($sql_kilometres_type);

        if ($result_kilometres_type->num_rows > 0) {
            $colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary'];
            $color_index = 0;
            
            while ($row = $result_kilometres_type->fetch_assoc()) {
                $distance_moyenne = $row['nombre_etapes'] > 0 ? round($row['total_kilometres'] / $row['nombre_etapes'], 1) : 0;
                $color = $colors[$color_index % count($colors)];
                $color_index++;
                
                // Déterminer l'icône en fonction du type d'étape
                $icon = 'fa-route'; // Icône par défaut
                
                echo '<div class="col-md-3 mb-3">';
                echo '<div class="card stats-card">';
                echo '<div class="d-flex justify-content-center">';
                echo '<i class="fas ' . $icon . ' fa-3x text-' . $color . ' my-3"></i>';
                echo '</div>';
                echo '<h3>' . number_format($row['total_kilometres'], 0, ',', ' ') . ' km</h3>';
                echo '<p>' . $row['nomTypeEtape'] . ' (' . $row['nombre_etapes'] . ' étapes)</p>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo "<div class='col-12'><p class='alert alert-info'>Aucune donnée trouvée.</p></div>";
        }
        ?>
    </div>

    <!-- Parcours et Profil des étapes -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Parcours 2022</div>
                <div class="card-body">
                    <div class="map-container">
                        <img src="assets/images/carte_tdf.jpg" alt="Parcours du Tour de France 2022" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Profil des Étapes</div>
                <div class="card-body">
                    <div class="stage-chart">
                        <div class="bar flat" style="height: 80px;"></div>
                        <div class="bar medium" style="height: 120px;"></div>
                        <div class="bar mountains" style="height: 180px;"></div>
                        <div class="bar flat" style="height: 70px;"></div>
                        <div class="bar mountains" style="height: 150px;"></div>
                    </div>
                    <p class="text-center mt-3">Profil d'élévation des étapes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Classement Général -->
    <div class="card">
        <div class="card-header">Classement Général</div>
        <div class="card-body">
            <table class="table standing-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Coureur</th>
                        <th>Équipe</th>
                        <th>Temps</th>
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
                            
                            // Formater le temps total pour l'affichage
                            $time_parts = explode(':', $row['temps_total']);
                            $formatted_time = $time_parts[0] . 'h ' . $time_parts[1] . 'm ' . $time_parts[2] . 's';
                            
                            // Déterminer l'image du coureur
                            $image_path = "";
                            if ($row['nom'] == 'VINGEGAARD' && $row['prenom'] == 'JONAS') {
                                $image_path = "J.VINGEGAARD.jpg";
                            } elseif ($row['nom'] == 'POGACAR' && $row['prenom'] == 'TADEJ') {
                                $image_path = "T.POGACAR.jpg";
                            } else {
                                $image_path = "image-" . $position . ".png";
                            }
                    ?>
                    <tr>
                        <td>
                            <div class="position-badge"><?php echo $position; ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="assets/images/<?php echo $image_path; ?>" alt="<?php echo $row['prenom'] . ' ' . $row['nom']; ?>" class="me-2">
                                <?php echo $row['prenom'] . ' ' . $row['nom']; ?>
                            </div>
                        </td>
                        <td><?php echo $row['nomEquipe']; ?></td>
                        <td><?php echo $formatted_time; ?></td>
                        <td><?php echo $ecart; ?></td>
                    </tr>
                    <?php
                            $position++;
                        }
                    } else {
                        echo "<tr><td colspan='5'>Aucun résultat trouvé</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>