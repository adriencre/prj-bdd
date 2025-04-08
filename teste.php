<?php

require_once 'config.php';
include 'header.php';


//$host = "localhost"; 
//$username = "root";
//$password = "root"; 
//$dbname = "bdd_tdf"; 

//$servername = "localhost";
//$username = "login8082";
//$password = "quGvDsmvUTeaEOP";
//$dbname = "bdd_tdf";

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
        SELECT 
            e.nomEquipe,
            SEC_TO_TIME(SUM(TIME_TO_SEC(p.temps))) as temps_total
        FROM Performance p
        JOIN Coureur c ON c.numDossard = p.numDossard
        JOIN Equipe e ON c.numEquipe = e.numEquipe
        WHERE p.numEtape BETWEEN ? AND ?
        GROUP BY e.numEquipe
        ORDER BY SUM(TIME_TO_SEC(p.temps)) ASC
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
        hr {
            margin: 20px 0;
            border: none;
            border-top: 2px solid #ccc;
        }
        table {
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px 12px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Classement des Coureurs</h1>
    <form method="POST">
        <label for="etape1">Choisir la première étape :</label>
        <select name="etape1" id="etape1" required>
            <?php foreach ($etapes as $etape): ?>
                <option value="<?= $etape['numEtape'] ?>" <?= (isset($etape1) && $etape1 == $etape['numEtape']) ? 'selected' : '' ?>>
                    Étape <?= $etape['numEtape'] ?> - <?= $etape['dateEtape'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="etape2">Choisir la deuxième étape :</label>
        <select name="etape2" id="etape2" required>
            <?php foreach ($etapes as $etape): ?>
                <option value="<?= $etape['numEtape'] ?>" <?= (isset($etape2) && $etape2 == $etape['numEtape']) ? 'selected' : '' ?>>
                    Étape <?= $etape['numEtape'] ?> - <?= $etape['dateEtape'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Afficher le classement</button>
    </form>

    <?php if (!empty($classement)): ?>
        <h2>Classement individuel des coureurs</h2>
        <table>
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
                        <td><?= $position++ ?></td>
                        <td><?= htmlspecialchars($coureur['nom']) ?></td>
                        <td><?= htmlspecialchars($coureur['prenom']) ?></td>
                        <td><?= htmlspecialchars($coureur['numEquipe']) ?></td>
                        <td><?= htmlspecialchars($coureur['temps_combine']) ?></td>
                        <td><?= htmlspecialchars($coureur['ecart']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>
    
    <?php if (!empty($classementEquipe)): ?>
        <h2>Classement des Équipes</h2>
        <table>
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
                        <td><?= $position++ ?></td>
                        <td><?= htmlspecialchars($equipe['nomEquipe']) ?></td>
                        <td><?= htmlspecialchars($equipe['temps_total']) ?></td>
                        <td><?= htmlspecialchars($equipe['ecart']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
