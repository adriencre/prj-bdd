<?php
include 'header.php';
include 'config.php';
?>

<div class="container my-5">
    <h1 class="mb-4">Équipes du Tour de France 2022</h1>
    
    <?php
    // Récupération des pays avec leurs équipes
    $sql_pays = "SELECT DISTINCT p.nomPays, p.codePays 
                 FROM Pays p 
                 JOIN Equipe e ON p.codePays = e.codePays 
                 ORDER BY p.nomPays";
    $result_pays = $conn->query($sql_pays);

    if ($result_pays->num_rows > 0) {
        while($pays = $result_pays->fetch_assoc()) {
            echo "<div class='card mb-4'>";
            echo "<div class='card-header bg-primary text-white'>";
            echo "<h2 class='h5 mb-0'>" . $pays['nomPays'] . "</h2>";
            echo "</div>";
            echo "<div class='card-body'>";
            echo "<div class='row'>";

            // Récupération des équipes pour ce pays
            $sql_equipes = "SELECT e.numEquipe, e.nomEquipe, COUNT(c.numDossard) AS nb_coureurs 
                           FROM Equipe e 
                           LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe 
                           WHERE e.codePays = ? 
                           GROUP BY e.numEquipe 
                           ORDER BY e.nomEquipe";
            $stmt = $conn->prepare($sql_equipes);
            $stmt->bind_param("s", $pays['codePays']);
            $stmt->execute();
            $result_equipes = $stmt->get_result();

            if ($result_equipes->num_rows > 0) {
                while($row = $result_equipes->fetch_assoc()) {
                    // Générer une couleur aléatoire pour le badge d'équipe
                    $colors = ['primary', 'success', 'danger', 'warning', 'info'];
                    $color = $colors[array_rand($colors)];
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['nomEquipe']; ?></h5>
                                <p class="card-text">
                                    <span class="badge bg-<?php echo $color; ?> me-2"><?php echo $pays['nomPays']; ?></span>
                                    <span class="badge bg-secondary"><?php echo $row['nb_coureurs']; ?> coureurs</span>
                                </p>
                                
                                <?php
                                // Récupération des coureurs de l'équipe
                                $sql_coureurs = "SELECT numDossard, nom, prenom FROM Coureur WHERE numEquipe = ? ORDER BY nom LIMIT 4";
                                $stmt_coureurs = $conn->prepare($sql_coureurs);
                                $stmt_coureurs->bind_param("s", $row['numEquipe']);
                                $stmt_coureurs->execute();
                                $result_coureurs = $stmt_coureurs->get_result();
                                
                                if ($result_coureurs->num_rows > 0) {
                                    echo '<h6 class="mt-3">Coureurs principaux:</h6>';
                                    echo '<ul class="list-group list-group-flush">';
                                    while($coureur = $result_coureurs->fetch_assoc()) {
                                        echo '<li class="list-group-item">' . $coureur['nom'] . ' ' . $coureur['prenom'] . '</li>';
                                    }
                                    echo '</ul>';
                                }
                                ?>
                                
                                <a href="Team_detail.php?id=<?php echo $row['numEquipe']; ?>" class="btn btn-primary mt-3">Voir les détails</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            echo "</div></div></div>";
        }
    } else {
        echo "<div class='alert alert-info'>Aucune équipe trouvée.</div>";
    }
    ?>
</div>

<?php
include 'footer.php';
$conn->close();
?>