<?php
// Ce fichier contient les liens du menu principal pour faciliter la navigation.
// Il est à inclure dans des sections de menu, par exemple dans un aside ou dans l'en-tête.

// Définir le fichier actif pour la mise en surbrillance des liens
$currentFile = basename($_SERVER['PHP_SELF']);
?>

<div class="list-group mb-4">
    <h5 class="list-group-item list-group-item-primary">Navigation</h5>
    <a href="index.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'index.php') ? 'active' : ''; ?>">
        <i class="fas fa-home me-2"></i> Accueil
    </a>
    <a href="Coureur.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'Coureur.php') ? 'active' : ''; ?>">
        <i class="fas fa-user-circle me-2"></i> Coureurs
    </a>
    <a href="Team.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'Team.php') ? 'active' : ''; ?>">
        <i class="fas fa-users me-2"></i> Équipes
    </a>
    <a href="Etape.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'Etape.php') ? 'active' : ''; ?>">
        <i class="fas fa-map-signs me-2"></i> Étapes
    </a>
</div>

<div class="list-group mb-4">
    <h5 class="list-group-item list-group-item-primary">Classements</h5>
    <a href="classement_general.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'classement_general.php') ? 'active' : ''; ?>">
        <i class="fas fa-trophy me-2"></i> Classement général
    </a>
    <a href="classement_equipe.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'classement_equipe.php') ? 'active' : ''; ?>">
        <i class="fas fa-flag-checkered me-2"></i> Classement par équipe
    </a>
</div>

<div class="list-group mb-4">
    <h5 class="list-group-item list-group-item-primary">Outils</h5>
    <a href="statistiques.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'statistiques.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar me-2"></i> Statistiques
    </a>
    <a href="recherche.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'recherche.php') ? 'active' : ''; ?>">
        <i class="fas fa-search me-2"></i> Recherche
    </a>
</div>

<div class="list-group mb-4">
    <h5 class="list-group-item list-group-item-primary">Administration</h5>
    <a href="add_coureur.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'add_coureur.php') ? 'active' : ''; ?>">
        <i class="fas fa-user-plus me-2"></i> Ajouter un coureur
    </a>
    <a href="add_equipe.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'add_equipe.php') ? 'active' : ''; ?>">
        <i class="fas fa-plus-circle me-2"></i> Ajouter une équipe
    </a>
    <a href="add_etape.php" class="list-group-item list-group-item-action <?php echo ($currentFile == 'add_etape.php') ? 'active' : ''; ?>">
        <i class="fas fa-route me-2"></i> Ajouter une étape
    </a>
</div>