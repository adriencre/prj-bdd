<?php
require_once 'config.php';
include 'header.php';

// Statistiques générales
$stats = [];

// Nombre total de kilomètres
$sql_distance = "SELECT SUM(distance) as total_distance FROM Etape";
$result_distance = $conn->query($sql_distance);
$row_distance = $result_distance->fetch_assoc();
$stats['total_distance'] = $row_distance['total_distance'];

// Nombre d'équipes
$sql_equipes = "SELECT COUNT(*) as total_equipes FROM Equipe";
$result_equipes = $conn->query($sql_equipes);
$row_equipes = $result_equipes->fetch_assoc();
$stats['total_equipes'] = $row_equipes['total_equipes'];

// Nombre de coureurs
$sql_coureurs = "SELECT COUNT(*) as total_coureurs FROM Coureur";
$result_coureurs = $conn->query($sql_coureurs);
$row_coureurs = $result_coureurs->fetch_assoc();
$stats['total_coureurs'] = $row_coureurs['total_coureurs'];

// Nombre d'étapes
$sql_etapes = "SELECT COUNT(*) as total_etapes FROM Etape";
$result_etapes = $conn->query($sql_etapes);
$row_etapes = $result_etapes->fetch_assoc();
$stats['total_etapes'] = $row_etapes['total_etapes'];

// Nombre de performances enregistrées
$sql_performances = "SELECT COUNT(*) as total_performances FROM Performance";
$result_performances = $conn->query($sql_performances);
$row_performances = $result_performances->fetch_assoc();
$stats['total_performances'] = $row_performances['total_performances'];

// Nombre de bonifications accordées
$sql_bonifications = "SELECT COUNT(*) as total_bonifications FROM Bonification";
$result_bonifications = $conn->query($sql_bonifications);
$row_bonifications = $result_bonifications->fetch_assoc();
$stats['total_bonifications'] = $row_bonifications['total_bonifications'];

// Statistiques par type d'étape
$sql_type_etapes = "SELECT t.nomTypeEtape, COUNT(e.numEtape) as nombre, SUM(e.distance) as distance_totale
                   FROM TypeEtape t
                   LEFT JOIN Etape e ON t.idTypeEtape = e.idTypeEtape
                   GROUP BY t.idTypeEtape
                   ORDER BY t.nomTypeEtape";
$result_type_etapes = $conn->query($sql_type_etapes);

// Statistiques par pays (coureurs)
$sql_pays_coureurs = "SELECT p.nomPays, COUNT(c.numDossard) as nombre
                    FROM Pays p
                    LEFT JOIN Coureur c ON p.codePays = c.codePays
                    WHERE c.numDossard IS NOT NULL
                    GROUP BY p.codePays
                    ORDER BY nombre DESC
                    LIMIT 10";
$result_pays_coureurs = $conn->query($sql_pays_coureurs);

// Coureurs ayant participé à toutes les étapes
$sql_coureurs_toutes_etapes = "
    SELECT c.numDossard, c.nom, c.prenom, COUNT(DISTINCT p.numEtape) as nb_etapes
    FROM Coureur c
    JOIN Performance p ON c.numDossard = p.numDossard
    GROUP BY c.numDossard
    HAVING nb_etapes = (SELECT COUNT(*) FROM Etape)
    ORDER BY c.nom";
$result_coureurs_toutes_etapes = $conn->query($sql_coureurs_toutes_etapes);

// Étape la plus longue
$sql_etape_plus_longue = "
    SELECT e.numEtape, e.distance, v1.nomVille as villeDepart, v2.nomVille as villeArrivee, t.nomTypeEtape
    FROM Etape e
    JOIN Ville v1 ON e.numVille = v1.numVille
    JOIN Ville v2 ON e.numVille_1 = v2.numVille
    JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
    ORDER BY e.distance DESC
    LIMIT 1";
$result_etape_plus_longue = $conn->query($sql_etape_plus_longue);
$etape_plus_longue = $result_etape_plus_longue->fetch_assoc();

// Étape la plus courte
$sql_etape_plus_courte = "
    SELECT e.numEtape, e.distance, v1.nomVille as villeDepart, v2.nomVille as villeArrivee, t.nomTypeEtape
    FROM Etape e
    JOIN Ville v1 ON e.numVille = v1.numVille
    JOIN Ville v2 ON e.numVille_1 = v2.numVille
    JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
    ORDER BY e.distance ASC
    LIMIT 1";
$result_etape_plus_courte = $conn->query($sql_etape_plus_courte);
$etape_plus_courte = $result_etape_plus_courte->fetch_assoc();

// Meilleure performance (temps le plus rapide)
$sql_meilleure_perf = "
    SELECT p.numDossard, p.numEtape, p.temps, c.nom, c.prenom, e.distance,
           v1.nomVille as villeDepart, v2.nomVille as villeArrivee
    FROM Performance p
    JOIN Coureur c ON p.numDossard = c.numDossard
    JOIN Etape e ON p.numEtape = e.numEtape
    JOIN Ville v1 ON e.numVille = v1.numVille
    JOIN Ville v2 ON e.numVille_1 = v2.numVille
    WHERE p.temps IS NOT NULL
    ORDER BY TIME_TO_SEC(STR_TO_DATE(p.temps, '%h:%i:%s %p')) / e.distance ASC
    LIMIT 1";
$result_meilleure_perf = $conn->query($sql_meilleure_perf);
$meilleure_perf = $result_meilleure_perf->fetch_assoc();
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Statistiques du Tour de France 2022</h1>
        <div>
            <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
        </div>
    </div>
    
    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Statistiques générales</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo number_format($stats['total_distance'], 0, ',', ' '); ?> km</h3>
                                <p class="text-muted mb-0">Distance totale</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo $stats['total_etapes']; ?></h3>
                                <p class="text-muted mb-0">Nombre d'étapes</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo $stats['total_equipes']; ?></h3>
                                <p class="text-muted mb-0">Nombre d'équipes</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo $stats['total_coureurs']; ?></h3>
                                <p class="text-muted mb-0">Nombre de coureurs</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo $stats['total_performances']; ?></h3>
                                <p class="text-muted mb-0">Performances enregistrées</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="h2"><?php echo $stats['total_bonifications']; ?></h3>
                                <p class="text-muted mb-0">Bonifications accordées</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques par type d'étape -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 mb-0">Statistiques par type d'étape</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type d'étape</th>
                                    <th>Nombre</th>
                                    <th>Distance totale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_type_etapes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nomTypeEtape']; ?></td>
                                    <td><?php echo $row['nombre']; ?></td>
                                    <td><?php echo number_format($row['distance_totale'], 0, ',', ' '); ?> km</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 mb-0">Top 10 des pays participants</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Pays</th>
                                    <th>Nombre de coureurs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result_pays_coureurs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['nomPays']; ?></td>
                                    <td><?php echo $row['nombre']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Records et informations remarquables -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Records et informations remarquables</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h4>Étape la plus longue</h4>
                            <p>
                                <strong>Étape <?php echo $etape_plus_longue['numEtape']; ?>:</strong> 
                                <?php echo $etape_plus_longue['villeDepart'] . ' - ' . $etape_plus_longue['villeArrivee']; ?><br>
                                <strong>Distance:</strong> <?php echo $etape_plus_longue['distance']; ?> km<br>
                                <strong>Type:</strong> <?php echo $etape_plus_longue['nomTypeEtape']; ?>
                            </p>
                            <a href="Etape_detail.php?id=<?php echo $etape_plus_longue['numEtape']; ?>" class="btn btn-sm btn-info">Voir l'étape</a>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <h4>Étape la plus courte</h4>
                            <p>
                                <strong>Étape <?php echo $etape_plus_courte['numEtape']; ?>:</strong> 
                                <?php echo $etape_plus_courte['villeDepart'] . ' - ' . $etape_plus_courte['villeArrivee']; ?><br>
                                <strong>Distance:</strong> <?php echo $etape_plus_courte['distance']; ?> km<br>
                                <strong>Type:</strong> <?php echo $etape_plus_courte['nomTypeEtape']; ?>
                            </p>
                            <a href="Etape_detail.php?id=<?php echo $etape_plus_courte['numEtape']; ?>" class="btn btn-sm btn-info">Voir l'étape</a>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <h4>Performance la plus rapide</h4>
                            <p>
                                <strong>Coureur:</strong> <?php echo $meilleure_perf['prenom'] . ' ' . $meilleure_perf['nom']; ?><br>
                                <strong>Étape <?php echo $meilleure_perf['numEtape']; ?>:</strong> 
                                <?php echo $meilleure_perf['villeDepart'] . ' - ' . $meilleure_perf['villeArrivee']; ?><br>
                                <strong>Temps:</strong> <?php echo $meilleure_perf['temps']; ?> pour <?php echo $meilleure_perf['distance']; ?> km
                            </p>
                            <a href="Coureur_detail.php?id=<?php echo $meilleure_perf['numDossard']; ?>" class="btn btn-sm btn-info">Voir le coureur</a>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <h4>Coureurs ayant participé à toutes les étapes</h4>
                            <?php if ($result_coureurs_toutes_etapes->num_rows > 0): ?>
                            <ul class="list-group">
                                <?php while($row = $result_coureurs_toutes_etapes->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo $row['prenom'] . ' ' . $row['nom']; ?></span>
                                    <a href="Coureur_detail.php?id=<?php echo $row['numDossard']; ?>" class="btn btn-sm btn-info">Détails</a>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                            <?php else: ?>
                            <p>Aucun coureur n'a participé à toutes les étapes.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liens vers les autres statistiques -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Accès rapides</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="classement_general.php" class="btn btn-outline-primary w-100 p-3">
                                <i class="fas fa-list-ol me-2"></i> Classement général
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="classement_equipe.php" class="btn btn-outline-primary w-100 p-3">
                                <i class="fas fa-users me-2"></i> Classement par équipe
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="recherche.php" class="btn btn-outline-primary w-100 p-3">
                                <i class="fas fa-search me-2"></i> Recherche avancée
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>