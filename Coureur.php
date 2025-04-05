<?php
require_once 'config.php';
include 'header.php';

// Pagination
$coureurs_par_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $coureurs_par_page;

// Filtres
$filtre_pays = isset($_GET['pays']) ? $_GET['pays'] : '';
$filtre_equipe = isset($_GET['equipe']) ? $_GET['equipe'] : '';
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';

// Construction de la requête avec filtres
$sql_conditions = [];
$sql_params = [];
$param_types = "";

if (!empty($filtre_pays)) {
    $sql_conditions[] = "c.codePays = ?";
    $sql_params[] = $filtre_pays;
    $param_types .= "s";
}

if (!empty($filtre_equipe)) {
    $sql_conditions[] = "c.numEquipe = ?";
    $sql_params[] = $filtre_equipe;
    $param_types .= "s";
}

if (!empty($recherche)) {
    $sql_conditions[] = "(c.nom LIKE ? OR c.prenom LIKE ?)";
    $sql_params[] = "%$recherche%";
    $sql_params[] = "%$recherche%";
    $param_types .= "ss";
}

// Requête pour compter le nombre total de coureurs (pour la pagination)
$sql_count = "SELECT COUNT(*) AS total FROM Coureur c";
if (!empty($sql_conditions)) {
    $sql_count .= " WHERE " . implode(" AND ", $sql_conditions);
}

// Préparer et exécuter la requête de comptage
$stmt_count = $conn->prepare($sql_count);
if (!empty($sql_params)) {
    $stmt_count->bind_param($param_types, ...$sql_params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_coureurs = $row_count['total'];
$total_pages = ceil($total_coureurs / $coureurs_par_page);

// Requête pour récupérer les coureurs avec pagination
$sql_coureurs = "SELECT c.numDossard, c.nom, c.prenom, c.DN, e.nomEquipe, p.nomPays 
                FROM Coureur c 
                LEFT JOIN Equipe e ON c.numEquipe = e.numEquipe 
                LEFT JOIN Pays p ON c.codePays = p.codePays";

if (!empty($sql_conditions)) {
    $sql_coureurs .= " WHERE " . implode(" AND ", $sql_conditions);
}

$sql_coureurs .= " ORDER BY c.nom, c.prenom LIMIT ?, ?";

// Ajouter les paramètres de pagination
$sql_params[] = $offset;
$sql_params[] = $coureurs_par_page;
$param_types .= "ii";

// Préparer et exécuter la requête principale
$stmt = $conn->prepare($sql_coureurs);
$stmt->bind_param($param_types, ...$sql_params);
$stmt->execute();
$result_coureurs = $stmt->get_result();

// Requêtes pour les filtres
$sql_pays = "SELECT DISTINCT p.codePays, p.nomPays FROM Pays p 
            JOIN Coureur c ON p.codePays = c.codePays 
            ORDER BY p.nomPays";
$result_pays = $conn->query($sql_pays);

$sql_equipes = "SELECT DISTINCT e.numEquipe, e.nomEquipe FROM Equipe e 
               JOIN Coureur c ON e.numEquipe = c.numEquipe 
               ORDER BY e.nomEquipe";
$result_equipes = $conn->query($sql_equipes);
?>

<div class="container my-5">
    <h1 class="mb-4">Coureurs du Tour de France 2022</h1>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="pays" class="form-label">Filtre par pays</label>
                    <select name="pays" id="pays" class="form-select">
                        <option value="">Tous les pays</option>
                        <?php while($pays = $result_pays->fetch_assoc()): ?>
                        <option value="<?php echo $pays['codePays']; ?>" <?php echo ($filtre_pays == $pays['codePays']) ? 'selected' : ''; ?>>
                            <?php echo $pays['nomPays']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="equipe" class="form-label">Filtre par équipe</label>
                    <select name="equipe" id="equipe" class="form-select">
                        <option value="">Toutes les équipes</option>
                        <?php while($equipe = $result_equipes->fetch_assoc()): ?>
                        <option value="<?php echo $equipe['numEquipe']; ?>" <?php echo ($filtre_equipe == $equipe['numEquipe']) ? 'selected' : ''; ?>>
                            <?php echo $equipe['nomEquipe']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="recherche" class="form-label">Recherche par nom</label>
                    <input type="text" name="recherche" id="recherche" class="form-control" value="<?php echo htmlspecialchars($recherche); ?>" placeholder="Nom ou prénom">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau des coureurs -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Dossard</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date de naissance</th>
                    <th>Âge</th>
                    <th>Équipe</th>
                    <th>Pays</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_coureurs->num_rows > 0) {
                    while($coureur = $result_coureurs->fetch_assoc()) {
                        // Calcul de l'âge
                        $dob = new DateTime($coureur['DN']);
                        $now = new DateTime();
                        $age = $now->diff($dob)->y;
                ?>
                <tr>
                    <td><?php echo $coureur['numDossard']; ?></td>
                    <td><?php echo $coureur['nom']; ?></td>
                    <td><?php echo $coureur['prenom']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($coureur['DN'])); ?></td>
                    <td><?php echo $age; ?> ans</td>
                    <td><?php echo $coureur['nomEquipe']; ?></td>
                    <td><?php echo $coureur['nomPays']; ?></td>
                    <td>
                        <a href="Coureur_detail.php?id=<?php echo $coureur['numDossard']; ?>" class="btn btn-sm btn-info">Détails</a>
                    </td>
                </tr>
                <?php
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>Aucun coureur trouvé.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=1<?php echo (!empty($filtre_pays)) ? '&pays='.$filtre_pays : ''; ?><?php echo (!empty($filtre_equipe)) ? '&equipe='.$filtre_equipe : ''; ?><?php echo (!empty($recherche)) ? '&recherche='.$recherche : ''; ?>">Premier</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo (!empty($filtre_pays)) ? '&pays='.$filtre_pays : ''; ?><?php echo (!empty($filtre_equipe)) ? '&equipe='.$filtre_equipe : ''; ?><?php echo (!empty($recherche)) ? '&recherche='.$recherche : ''; ?>">Précédent</a>
            </li>
            <?php endif; ?>
            
            <?php
            // Afficher 5 pages autour de la page actuelle
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($filtre_pays)) ? '&pays='.$filtre_pays : ''; ?><?php echo (!empty($filtre_equipe)) ? '&equipe='.$filtre_equipe : ''; ?><?php echo (!empty($recherche)) ? '&recherche='.$recherche : ''; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo (!empty($filtre_pays)) ? '&pays='.$filtre_pays : ''; ?><?php echo (!empty($filtre_equipe)) ? '&equipe='.$filtre_equipe : ''; ?><?php echo (!empty($recherche)) ? '&recherche='.$recherche : ''; ?>">Suivant</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo (!empty($filtre_pays)) ? '&pays='.$filtre_pays : ''; ?><?php echo (!empty($filtre_equipe)) ? '&equipe='.$filtre_equipe : ''; ?><?php echo (!empty($recherche)) ? '&recherche='.$recherche : ''; ?>">Dernier</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <div class="mt-3 text-center">
        <p>Affichage de <?php echo min(($offset + 1), $total_coureurs); ?> à <?php echo min(($offset + $coureurs_par_page), $total_coureurs); ?> sur <?php echo $total_coureurs; ?> coureurs</p>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>