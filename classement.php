<?php

require_once 'config.php';
include 'header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Récupération des étapes avec vérification
$query_etapes = "SELECT numEtape, dateEtape FROM Etape ORDER BY numEtape ASC";
$result_etapes = $conn->query($query_etapes);

if (!$result_etapes) {
    die("Erreur lors de la récupération des étapes : " . $conn->error);
}

$etapes = [];
while ($row = $result_etapes->fetch_assoc()) {
    $etapes[] = $row;
}

$etape1 = $etape2 = null;
$classement = [];
$classementEquipe = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etape1 = (int)$_POST['etape1'];
    $etape2 = (int)$_POST['etape2'];

    // Vérification des paramètres
    if (!isset($etape1) || !isset($etape2)) {
        die("Les paramètres etape1 et etape2 sont requis");
    }

    // Vérification que etape1 est inférieur à etape2
    if ($etape1 > $etape2) {
        die("L'étape de début doit être inférieure à l'étape de fin");
    }

    // Requête simplifiée pour le classement individuel
    $query = "
        SELECT 
            c.nom, 
            c.prenom, 
            c.numEquipe, 
            SEC_TO_TIME(SUM(TIME_TO_SEC(p.temps))) AS temps_combine
        FROM Performance p
        JOIN Coureur c ON c.numDossard = p.numDossard
        WHERE p.numEtape BETWEEN ? AND ?
        GROUP BY c.numDossard, c.nom, c.prenom, c.numEquipe
        HAVING COUNT(p.numEtape) = (? - ? + 1)
        ORDER BY SUM(TIME_TO_SEC(p.temps)) ASC
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }
    
    $stmt->bind_param("iiii", $etape1, $etape2, $etape2, $etape1);
    $stmt->execute();
    $result = $stmt->get_result();
    $classement = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calcul de l'écart après avoir récupéré les résultats
    if (count($classement) > 0) {
        $tempsLeader = TIME_TO_SEC($classement[0]['temps_combine']);
        foreach ($classement as &$coureur) {
            $tempsCoureur = TIME_TO_SEC($coureur['temps_combine']);
            $ecart = $tempsCoureur - $tempsLeader;
            $coureur['ecart'] = SEC_TO_TIME($ecart);
        }
    }

    // Requête simplifiée pour le classement des équipes
    $queryEquipe = "
        WITH BestTimes AS (
            SELECT 
                c.numEquipe,
                p.numEtape,
                TIME_TO_SEC(p.temps) AS temps_sec,
                ROW_NUMBER() OVER (PARTITION BY p.numEtape, c.numEquipe ORDER BY TIME_TO_SEC(p.temps) ASC) AS rang
            FROM Performance p
            JOIN Coureur c ON c.numDossard = p.numDossard
            WHERE p.numEtape BETWEEN ? AND ?
        ),
        TeamTimes AS (
            SELECT 
                bt.numEquipe,
                SUM(bt.temps_sec) AS total_sec
            FROM BestTimes bt
            WHERE bt.rang <= 3
            GROUP BY bt.numEquipe
        ),
        MinTeam AS (
            SELECT MIN(total_sec) AS best_sec FROM TeamTimes
        )
        SELECT 
            t.numEquipe,
            SEC_TO_TIME(t.total_sec) AS temps_total,
            SEC_TO_TIME(t.total_sec - m.best_sec) AS ecart
        FROM TeamTimes t, MinTeam m
        ORDER BY t.total_sec ASC;
    ";
    
    $stmtEquipe = $conn->prepare($queryEquipe);
    if ($stmtEquipe === false) {
        die("Erreur de préparation de la requête équipe : " . $conn->error);
    }
    
    $stmtEquipe->bind_param("ii", $etape1, $etape2);
    $stmtEquipe->execute();
    $resultEquipe = $stmtEquipe->get_result();
    $classementEquipe = $resultEquipe->fetch_all(MYSQLI_ASSOC);
    
    // Calcul de l'écart pour les équipes
    if (count($classementEquipe) > 0) {
        $tempsLeaderEquipe = TIME_TO_SEC($classementEquipe[0]['temps_total']);
        foreach ($classementEquipe as &$equipe) {
            $tempsEquipe = TIME_TO_SEC($equipe['temps_total']);
            $ecart = $tempsEquipe - $tempsLeaderEquipe;
            $equipe['ecart'] = SEC_TO_TIME($ecart);
        }
    }
}

// Fonction pour convertir le format de temps en secondes
function TIME_TO_SEC($time) {
    $parts = explode(':', $time);
    if (count($parts) === 3) {
        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
    }
    return 0;
}

// Fonction pour convertir les secondes en format de temps
function SEC_TO_TIME($seconds) {
    return sprintf('%02d:%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60), $seconds % 60);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement des Coureurs et Équipes</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.075);
        }

        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }

        .btn-primary {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-primary:hover {
            color: #fff;
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .form-select {
            display: block;
            width: 100%;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            appearance: none;
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-right: 15px;
            padding-left: 15px;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .position-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            background-color: #ffd800;
            color: white;
            border-radius: 50%;
        }
    </style>
</head>
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Classement Général</h1>
        <div>
            <a href="index.php" class="btn btn-outline-primary">Retour à l'accueil</a>
        </div>
    </div>
            <div class="card-body">
                <form method="POST" class="row mb-4">
                    <div class="col-md-6">
                        <label for="etape1" class="form-label">Choisir la première étape :</label>
                        <select name="etape1" id="etape1" class="form-select" required>
                            <?php foreach ($etapes as $etape): ?>
                                <option value="<?= $etape['numEtape'] ?>" <?= (isset($etape1) && $etape1 == $etape['numEtape']) ? 'selected' : '' ?>>
                                    Étape <?= $etape['numEtape'] ?> - <?= $etape['dateEtape'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="etape2" class="form-label">Choisir la deuxième étape :</label>
                        <select name="etape2" id="etape2" class="form-select" required>
                            <?php foreach ($etapes as $etape): ?>
                                <option value="<?= $etape['numEtape'] ?>" <?= (isset($etape2) && $etape2 == $etape['numEtape']) ? 'selected' : '' ?>>
                                    Étape <?= $etape['numEtape'] ?> - <?= $etape['dateEtape'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-primary">Afficher le classement</button>
                    </div>
                </form>

                <?php if (!empty($classement)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Équipe</th>
                                    <th>Temps Combiné</th>
                                    <th>Écart</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $position = 1; ?>
                                <?php foreach ($classement as $coureur): ?>
                                    <tr>
                                        <td>
                                            <div class="position-badge"><?= $position++ ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($coureur['nom']) ?></td>
                                        <td><?= htmlspecialchars($coureur['prenom']) ?></td>
                                        <td><?= htmlspecialchars($coureur['numEquipe']) ?></td>
                                        <td><?= htmlspecialchars($coureur['temps_combine']) ?></td>
                                        <td><?= htmlspecialchars($coureur['ecart']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($classementEquipe)): ?>
                
                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Équipe</th>
                                    <th>Temps Combiné</th>
                                    <th>Écart</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $position = 1; ?>
                                <?php foreach ($classementEquipe as $equipe): ?>
                                    <tr>
                                        <td>
                                            <div class="position-badge"><?= $position++ ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($equipe['numEquipe']) ?></td>
                                        <td><?= htmlspecialchars($equipe['temps_total']) ?></td>
                                        <td><?= htmlspecialchars($equipe['ecart']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</body>
</html>
