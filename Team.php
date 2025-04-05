<?php
require_once 'config.php';
include 'header.php';

// Récupération des équipes
$sql_equipes = "SELECT e.numEquipe, e.nomEquipe, p.nomPays, COUNT(c.numDossard) AS nb_coureurs 
               FROM Equipe e 
               LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe 
               LEFT JOIN Pays p ON e.codePays = p.codePays 
               GROUP BY e.numEquipe 
               ORDER BY e.nomEquipe";
$result_equipes = $conn->query($sql_equipes);
?>

<div class="container my-5">
    <h1 class="mb-4">Équipes du Tour de France 2022</h1>
    
    <div class="row">
        <?php
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
                        <span class="badge bg-<?php echo $color; ?> me-2"><?php echo $row['nomPays']; ?></span>
                        <span class="badge bg-secondary"><?php echo $row['nb_coureurs']; ?> coureurs</span>
                    </p>
                    
                    <?php
                    // Récupération des coureurs de l'équipe
                    $sql_coureurs = "SELECT numDossard, nom, prenom FROM Coureur WHERE numEquipe = ? ORDER BY nom LIMIT 4";
                    $stmt = $conn->prepare($sql_coureurs);
                    $stmt->bind_param("s", $row['numEquipe']);
                    $stmt->execute();
                    $result_coureurs = $stmt->get_result();
                    
                    if ($result_coureurs->num_rows > 0) {
                        echo '<h6 class="mt-3">Coureurs principaux:</h6>';
                        echo '<ul class="list-group list-group-flush">';
                        while($coureur = $result_coureurs->fetch_assoc()) {
                            echo '<li class="list-group-item">' . $coureur['prenom'] . ' ' . $coureur['nom'] . '</li>';
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
        } else {
            echo "<div class='col-12'><p class='alert alert-info'>Aucune équipe trouvée.</p></div>";
        }
        ?>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>