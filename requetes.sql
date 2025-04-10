-- 1) Donner le numéro et le nom des coureurs Français
SELECT c.numDossard, c.nom, c.prenom
FROM Coureur c
JOIN Pays p ON c.codePays = p.codePays
WHERE p.codePays = 'FRA'
ORDER BY c.nom, c.prenom;

-- 2) Quelle est la composition de l'équipe TOTALENERGIES (Numéro, nom et pays des coureurs) 
SELECT c.numDossard, c.nom, c.prenom, p.nomPays
FROM Coureur c
JOIN Pays p ON c.codePays = p.codePays
JOIN Equipe e ON c.numEquipe = e.numEquipe
WHERE e.nomEquipe = 'TOTALENERGIES'
ORDER BY c.nom;

-- 3) Quels sont les noms des coureurs qui n'ont pas obtenu de bonifications
SELECT DISTINCT c.nom, c.prenom
FROM Coureur c
LEFT JOIN Bonification b ON c.numDossard = b.numDossard
WHERE b.numDossard IS NULL
ORDER BY c.nom, c.prenom;

-- 4) Quels sont les coureurs qui sont enregistrés dans la base mais qui n’ont pas participé au tour 
SELECT c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays
FROM Coureur c
LEFT JOIN Performance perf ON c.numDossard = perf.numDossard
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
WHERE perf.numDossard IS NULL
ORDER BY c.nom, c.prenom;

-- 5) Quelles sont les villes qui ont été le lieu d’un départ d’étape ou d’une arrivée d’étape ? 
SELECT DISTINCT v.nomVille, p.nomPays
FROM Ville v
JOIN Pays p ON v.codePays = p.codePays
JOIN Etape e ON v.numVille = e.numVille OR v.numVille = e.numVille_1
ORDER BY v.nomVille;

-- 6) Quels sont les villes de France qui n’ont été le lieu ni d’un départ, ni d’une arrivée d’étape (vous pouvez télécharger une table contenant toutes les villes de France) 
SELECT v.nomVille
FROM Ville v
JOIN Pays p ON v.codePays = p.codePays
LEFT JOIN (
    SELECT numVille FROM Etape
    UNION
    SELECT numVille_1 FROM Etape
) AS e ON v.numVille = e.numVille
WHERE p.codePays = 'FRA'
AND e.numVille IS NULL
ORDER BY v.nomVille;

-- 7)Donner pour chaque coureur, son nom, sa date de naissance, son pays et les numéros des étapes auxquelles il a participé dans les deux cas suivants : 

-- 7a) Faire figurer dans le résultat de la requête que les coureurs qui ont participé à au moins une étape 
SELECT c.nom, c.prenom, c.DN, p.nomPays, GROUP_CONCAT(perf.numEtape ORDER BY perf.numEtape) as etapes
FROM Coureur c
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard
ORDER BY c.nom, c.prenom;

-- 7b) Faire figurer dans le résultat de la requête tous les coureurs (ceux qui ont participé à au moins une étape et ceux qui n’ont participé au tour) 
SELECT c.nom, c.prenom, c.DN, p.nomPays, 
       COALESCE(GROUP_CONCAT(perf.numEtape ORDER BY perf.numEtape), 'Aucune participation') as etapes
FROM Coureur c
JOIN Pays p ON c.codePays = p.codePays
LEFT JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard
ORDER BY c.nom, c.prenom;

-- 8) Donner pour chaque pays, le nom des équipes représentées et le nom des coureurs de ce pays en ordonnant le résultat par ordre alphabétique des pays puis des équipes et enfin des noms des coureurs
SELECT p.nomPays, e.nomEquipe, c.nom, c.prenom
FROM Pays p
JOIN Coureur c ON p.codePays = c.codePays
JOIN Equipe e ON c.numEquipe = e.numEquipe
ORDER BY p.nomPays, e.nomEquipe, c.nom, c.prenom;

-- 9) Quelle est l’étape ayant la plus petite distance ? 
SELECT numEtape, distance, v1.nomVille as villeDepart, v2.nomVille as villeArrivee
FROM Etape e
JOIN Ville v1 ON e.numVille = v1.numVille
JOIN Ville v2 ON e.numVille_1 = v2.numVille
WHERE distance = (SELECT MIN(distance) FROM Etape);

-- 10) Donner l’âge de chaque joueur 
SELECT c.nom, c.prenom, c.DN,
       TIMESTAMPDIFF(YEAR, c.DN, CURDATE()) as age
FROM Coureur c
ORDER BY age DESC;

-- 11) Donner pour chaque équipe son nom et le nombre de joueurs qu’elle comporte 
SELECT e.nomEquipe, COUNT(c.numDossard) as nombre_coureurs
FROM Equipe e
LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
GROUP BY e.numEquipe
ORDER BY nombre_coureurs DESC;

-- 12) Donner le nom des équipes qui ont au moins X joueurs :

-- 12a) 1 ère solution : en utilisant la requête 11
WITH NbCoureursParEquipe AS (
    SELECT e.nomEquipe, COUNT(c.numDossard) as nombre_coureurs
    FROM Equipe e
    LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
    GROUP BY e.numEquipe
)
SELECT * FROM NbCoureursParEquipe
WHERE nombre_coureurs >= 8  -- Remplacer 8 par X
ORDER BY nombre_coureurs DESC;

-- 12b)  2 ème solution : en utilisant que les tables de la base de données 
SELECT e.nomEquipe, COUNT(c.numDossard) as nombre_coureurs
FROM Equipe e
LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
GROUP BY e.numEquipe
HAVING COUNT(c.numDossard) >= 8  -- Remplacer 8 par X
ORDER BY nombre_coureurs DESC;

-- 13) Donner pour chaque joueur son nom, la durée totale au tour de France, son temps maximum, son temps minimum et le temps moyen réalisé sur l’ensemble des étapes 
SELECT 
    c.nom, c.prenom,
    SEC_TO_TIME(SUM(TIME_TO_SEC(perf.temps))) as temps_total,
    MAX(perf.temps) as temps_max,
    MIN(perf.temps) as temps_min,
    SEC_TO_TIME(AVG(TIME_TO_SEC(perf.temps))) as temps_moyen
FROM Coureur c
JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard
ORDER BY temps_total;

-- 14) Donner pour chaque étape la ville de départ, d’arrivée, le temps minimal, le temps maximal ainsi que le temps moyen. 
SELECT 
    e.numEtape,
    v1.nomVille as villeDepart,
    v2.nomVille as villeArrivee,
    MIN(perf.temps) as temps_min,
    MAX(perf.temps) as temps_max,
    SEC_TO_TIME(AVG(TIME_TO_SEC(perf.temps))) as temps_moyen
FROM Etape e
JOIN Ville v1 ON e.numVille = v1.numVille
JOIN Ville v2 ON e.numVille_1 = v2.numVille
JOIN Performance perf ON e.numEtape = perf.numEtape
GROUP BY e.numEtape
ORDER BY e.numEtape;

-- 15) Donner les 3 meilleurs temps réalisés sur l’étape Haute Montagne. 
SELECT c.nom, c.prenom, e.numEtape, perf.temps
FROM Performance perf
JOIN Coureur c ON perf.numDossard = c.numDossard
JOIN Etape e ON perf.numEtape = e.numEtape
JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
WHERE t.nomTypeEtape = 'Haute Montagne'
ORDER BY perf.temps ASC
LIMIT 3;

-- 16) Quel est le nombre de kilomètres total du Tour de France ? 
SELECT SUM(distance) as distance_totale
FROM Etape;

-- 17) Quel est le nombre de kilomètres total des étapes de type "Haute Montagne" du Tour de France ? 
SELECT SUM(e.distance) as distance_haute_montagne
FROM Etape e
JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
WHERE t.nomTypeEtape = 'Haute Montagne';

-- 18) Quels sont les noms des coureurs qui ont participé à au moins X étapes ? 
SELECT c.nom, c.prenom, COUNT(perf.numEtape) as nb_etapes
FROM Coureur c
JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard
HAVING COUNT(perf.numEtape) >= 8  -- Remplacer 8 par X
ORDER BY nb_etapes DESC;

-- 19) Quels sont les noms des coureurs qui ont participé à toutes les étapes ? 
SELECT c.nom, c.prenom, COUNT(perf.numEtape) as nb_etapes
FROM Coureur c
JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard
HAVING COUNT(perf.numEtape) = (SELECT COUNT(*) FROM Etape)
ORDER BY c.nom;

-- 20) Quel est le classement général des coureurs (nom, code équipe, code pays et temps des coureurs) à l'issue des 13 premières étapes sachant que les bonifications ont été intégrées dans les temps réalisés à chaque étape ? 
SELECT 
    c.nom, c.prenom,
    e.nomEquipe,
    p.nomPays,
    SEC_TO_TIME(SUM(TIME_TO_SEC(perf.temps)) - COALESCE(SUM(b.reductionTemps), 0)) as temps_total
FROM Coureur c
JOIN Performance perf ON c.numDossard = perf.numDossard
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
LEFT JOIN Bonification b ON c.numDossard = b.numDossard AND perf.numEtape = b.numEtape
WHERE perf.numEtape <= 13
GROUP BY c.numDossard
ORDER BY temps_total;

-- 21) Quel est le classement par équipe à l'issue des 13 premières étapes (nom et temps des équipes) ? 
WITH TempsCoureursParEquipe AS (
    SELECT 
        e.numEquipe,
        perf.numEtape,
        MIN(perf.temps) as meilleur_temps
    FROM Performance perf
    JOIN Coureur c ON perf.numDossard = c.numDossard
    JOIN Equipe e ON c.numEquipe = e.numEquipe
    WHERE perf.numEtape <= 13
    GROUP BY e.numEquipe, perf.numEtape
)
SELECT 
    e.nomEquipe,
    SEC_TO_TIME(SUM(TIME_TO_SEC(t.meilleur_temps))) as temps_total
FROM TempsCoureursParEquipe t
JOIN Equipe e ON t.numEquipe = e.numEquipe
GROUP BY e.numEquipe
ORDER BY temps_total;
