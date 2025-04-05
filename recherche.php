<?php
require_once 'config.php';
include 'header.php';

// Récupération du terme de recherche
$terme = isset($_GET['terme']) ? trim($_GET['terme']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

// Résultats
$resultats_coureurs = [];
$resultats_equipes = [];
$resultats_etapes = [];

// Effectuer la recherche si un terme est fourni
if (!empty($terme)) {
    // Recherche des coureurs
    if ($type == 'all' || $type == 'coureurs') {
        $sql_coureurs = "SELECT c.numDossard, c.nom, c.prenom, c.DN, e.nomEquipe, p.nomPays 
                         FROM Coureur c 
                         LEFT JOIN Equipe e ON c.numEquipe = e.numEquipe 
                         LEFT JOIN Pays p ON c.codePays = p.codePays 
                         WHERE c.nom LIKE ? OR c.prenom LIKE ? 
                         ORDER BY c.nom, c.prenom 
                         LIMIT 20";
        $stmt_coureurs = $conn->prepare($sql_coureurs);
        $search_term = "%$terme%";
        $stmt_coureurs->bind_param("ss", $search_term, $search_term);
        $stmt_coureurs->execute();
        $result_coureurs = $stmt_coureurs->get_result();
        
        while($row = $result_coureurs->fetch_assoc()) {
            $resultats_coureurs[] = $row;
        }
    }
    
    // Recherche des équipes
    if ($type == 'all' || $type == 'equipes') {
        $sql_equipes = "SELECT e.numEquipe, e.nomEquipe, p.nomPays, COUNT(c.numDossard) AS nb_coureurs 
                       FROM Equipe e 
                       LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe 
                       LEFT JOIN Pays p ON e.codePays = p.codePays 
                       WHERE e.nomEquipe LIKE ? OR e.numEquipe LIKE ? 
                       GROUP BY e.numEquipe 
                       ORDER BY e.nomEquipe 
                       LIMIT 20";
        $stmt_equipes = $conn->prepare($sql_equipes);
        $search_term = "%$terme%";
        $stmt_equipes->bind_param("ss", $search_term, $search_term);
        $stmt_equipes->execute();
        $result_equipes = $stmt_equipes->get_result();
        
        while($row = $result_equipes->fetch_assoc()) {
            $resultats_equipes[] = $row;
        }
    }
    
    // Recherche des étapes
    if ($type == 'all' || $type == 'etapes') {
        $sql_etapes = "SELECT e.numEtape, e.dateEtape, e.distance, v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee, t.nomTypeEtape 
                      FROM Etape e 
                      LEFT JOIN Ville v1 ON e.numVille = v1.numVille 
                      LEFT JOIN Ville v2 ON e.numVille_1 = v2.numVille 
                      LEFT JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape 
                      WHERE v1.nomVille LIKE ? OR v2.nomVille LIKE ? 
                      ORDER BY e.numEtape 
                      LIMIT 20";
        $stmt_etapes = $conn->prepare($sql_etapes);
        $search_term = "%$terme%";
        $stmt_etapes->bind_param("ss", $search_term, $search_term);
        $stmt_etapes->execute();
        $result_etapes = $stmt_etapes->get_result();
        
        while($row = $result_etapes->fetch_assoc()) {
            $resultats_etapes[] = $row;
        }
    }
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Recherche avancée</h1>
        <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
    </div>
    
    <!-- Formulaire de recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="terme" class="form-label">Terme de recherche</label>
                    <input type="text" class="form-control" id="terme" name="terme" value="<?php echo htmlspecialchars($terme); ?>" required placeholder="Nom, prénom, équipe, ville...">
                </div>
                <div class="col-md-4">
                    <label for="type" class="form-label">Type de recherche</label>
                    <select class="form-select" id="type" name="type">
                        <option value="all" <?php echo ($type == 'all') ? 'selected' : ''; ?>>Tout</option>
                        <option value="coureurs" <?php echo ($type == 'coureurs') ? 'selected' : ''; ?>>Coureurs</option>
                        <option value="equipes" <?php echo ($type == 'equipes') ? 'selected' : ''; ?>>Équipes</option>
                        <option value="etapes" <?php echo ($type == 'etapes') ? 'selected' : ''; ?>>Étapes</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Rechercher</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (empty($terme)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Veuillez saisir un terme de recherche.
    </div>
    <?php elseif (empty($resultats_coureurs) && empty($resultats_equipes) && empty($resultats_etapes)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i> Aucun résultat trouvé pour "<strong><?php echo htmlspecialchars($terme); ?></strong>".
    </div>
    <?php else: ?>
    
    <!-- Résultats pour les coureurs -->
    <?php if ($type == 'all' || $type == 'coureurs'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Coureurs (<?php echo count($resultats_coureurs); ?> résultats)</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($resultats_coureurs)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Dossard</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Date de naissance</th>
                            <th>Équipe</th>
                            <th>Pays</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($resultats_coureurs as $coureur): ?>
                        <tr>
                            <td><?php echo $coureur['numDossard']; ?></td>
                            <td><?php echo $coureur['nom']; ?></td>
                            <td><?php echo $coureur['prenom']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($coureur['DN'])); ?></td>
                            <td><?php echo $coureur['nomEquipe']; ?></td>
                            <td><?php echo $coureur['nomPays']; ?></td>
                            <td>
                                <a href="Coureur_detail.php?id=<?php echo $coureur['numDossard']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye me-1"></i> Détails
                                </a>
                                <a href="edit_coureur.php?id=<?php echo $coureur['numDossard']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">Aucun coureur ne correspond à votre recherche.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Résultats pour les équipes -->
    <?php if ($type == 'all' || $type == 'equipes'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Équipes (<?php echo count($resultats_equipes); ?> résultats)</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($resultats_equipes)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th>Pays</th>
                            <th>Nombre de coureurs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($resultats_equipes as $equipe): ?>
                        <tr>
                            <td><?php echo $equipe['numEquipe']; ?></td>
                            <td><?php echo $equipe['nomEquipe']; ?></td>
                            <td><?php echo $equipe['nomPays']; ?></td>
                            <td><?php echo $equipe['nb_coureurs']; ?></td>
                            <td>
                                <a href="Team_detail.php?id=<?php echo $equipe['numEquipe']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye me-1"></i> Détails
                                </a>
                                <a href="edit_equipe.php?id=<?php echo $equipe['numEquipe']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">Aucune équipe ne correspond à votre recherche.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Résultats pour les étapes -->
    <?php if ($type == 'all' || $type == 'etapes'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Étapes (<?php echo count($resultats_etapes); ?> résultats)</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($resultats_etapes)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Date</th>
                            <th>Parcours</th>
                            <th>Type</th>
                            <th>Distance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($resultats_etapes as $etape): ?>
                        <tr>
                            <td><?php echo $etape['numEtape']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($etape['dateEtape'])); ?></td>
                            <td><?php echo $etape['villeDepart'] . ' - ' . $etape['villeArrivee']; ?></td>
                            <td><?php echo $etape['nomTypeEtape']; ?></td>
                            <td><?php echo $etape['distance']; ?> km</td>
                            <td>
                                <a href="Etape_detail.php?id=<?php echo $etape['numEtape']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye me-1"></i> Détails
                                </a>
                                <a href="edit_etape.php?id=<?php echo $etape['numEtape']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">Aucune étape ne correspond à votre recherche.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
    
    <!-- Suggestions de recherche -->
    <div class="card mt-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Suggestions de recherche</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h5>Recherche de coureurs</h5>
                    <ul>
                        <li><a href="recherche.php?terme=POGACAR&type=coureurs">POGACAR</a></li>
                        <li><a href="recherche.php?terme=VINGEGAARD&type=coureurs">VINGEGAARD</a></li>
                        <li><a href="recherche.php?terme=FRA&type=coureurs">Coureurs français</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Recherche d'équipes</h5>
                    <ul>
                        <li><a href="recherche.php?terme=UAE&type=equipes">UAE TEAM EMIRATES</a></li>
                        <li><a href="recherche.php?terme=INEOS&type=equipes">INEOS GRENADIERS</a></li>
                        <li><a href="recherche.php?terme=JUMBO&type=equipes">JUMBO-VISMA</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Recherche d'étapes</h5>
                    <ul>
                        <li><a href="recherche.php?terme=Paris&type=etapes">Paris</a></li>
                        <li><a href="recherche.php?terme=Copenhague&type=etapes">Copenhague</a></li>
                        <li><a href="recherche.php?terme=Alpes&type=etapes">Alpes</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>