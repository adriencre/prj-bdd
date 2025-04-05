<?php
include 'header.php';
include 'config.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Équipes et Coureurs par Pays</h1>

    <?php
    $sql = "SELECT 
                p.nomPays,
                e.nomEquipe,
                c.nom,
                c.prenom
            FROM Pays p
            LEFT JOIN Equipe e ON p.codePays = e.codePays
            LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
            WHERE e.nomEquipe IS NOT NULL
            ORDER BY 
                p.nomPays ASC,
                e.nomEquipe ASC,
                c.nom ASC,
                c.prenom ASC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $currentPays = "";
        $currentEquipe = "";
        
        while($row = $result->fetch_assoc()) {
            // Nouveau pays
            if ($currentPays != $row['nomPays']) {
                if ($currentPays != "") {
                    echo "</div></div>"; // Ferme les divs précédents
                }
                echo "<div class='card mb-4'>";
                echo "<div class='card-header bg-primary text-white'>";
                echo "<h2 class='h5 mb-0'>" . $row['nomPays'] . "</h2>";
                echo "</div>";
                echo "<div class='card-body'>";
                $currentPays = $row['nomPays'];
                $currentEquipe = ""; // Réinitialise l'équipe courante
            }
            
            // Nouvelle équipe
            if ($currentEquipe != $row['nomEquipe']) {
                if ($currentEquipe != "") {
                    echo "</ul></div>"; // Ferme les divs précédents
                }
                echo "<div class='mb-3'>";
                echo "<h3 class='h6 text-secondary'>" . $row['nomEquipe'] . "</h3>";
                echo "<ul class='list-group list-group-flush'>";
                $currentEquipe = $row['nomEquipe'];
            }
            
            // Coureur
            echo "<li class='list-group-item'>" . $row['nom'] . " " . $row['prenom'] . "</li>";
        }
        
        // Ferme les derniers divs
        echo "</ul></div></div></div>";
    } else {
        echo "<div class='alert alert-info'>Aucune donnée trouvée.</div>";
    }
    ?>
</div>

<?php
include 'footer.php';
$conn->close();
?> 