-- 3) Quels sont les noms des coureurs qui n'ont pas obtenu de bonifications
SELECT DISTINCT c.nom, c.prenom
FROM coureur c
LEFT JOIN bonification b ON c.idcoureur = b.idcoureur
WHERE b.idbonification IS NULL
ORDER BY c.nom, c.prenom;

-- Requête pour trouver les coureurs qui n'ont reçu aucune bonification
SELECT c.idcoureur, c.nom, c.prenom, c.idequipe, c.codepays
FROM coureur c
LEFT JOIN bonification b ON c.idcoureur = b.idcoureur
WHERE b.idbonification IS NULL
ORDER BY c.idcoureur;

-- 4) Quels sont les coureurs qui sont enregistrés dans la base mais qui n'ont pas participé au tour
SELECT c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays
FROM Coureur c
LEFT JOIN Equipe e ON c.numEquipe = e.numEquipe
LEFT JOIN Pays p ON c.codePays = p.codePays
LEFT JOIN Performance perf ON c.numDossard = perf.numDossard
WHERE perf.numEtape IS NULL
ORDER BY c.nom, c.prenom;

-- 5) Quelles sont les villes qui ont été le lieu d'un départ d'étape ou d'une arrivée d'étape ?
SELECT DISTINCT v.nomVille, v.codePays, p.nomPays
FROM Ville v
JOIN Pays p ON v.codePays = p.codePays
JOIN Etape e ON v.idVille = e.idVilleDepart OR v.idVille = e.idVilleArrivee
ORDER BY v.nomVille;

-- 6) Quelles sont les villes de France qui n'ont été le lieu ni d'un départ, ni d'une arrivée d'étape ?
SELECT v.nomVille, v.codePays, p.nomPays
FROM Ville v
JOIN Pays p ON v.codePays = p.codePays
LEFT JOIN Etape e ON v.numVille = e.numVille OR v.numVille = e.numVille_1
WHERE p.codePays = 'FRA'
AND e.numEtape IS NULL
ORDER BY v.nomVille;

-- 7) Insertion des villes de France manquantes
INSERT INTO Ville (numVille, nomVille, codePays)
SELECT DISTINCT 
    (SELECT COALESCE(MAX(numVille), 0) + 1 FROM Ville v2) + ROW_NUMBER() OVER (ORDER BY ville),
    ville, 
    'FRA'
FROM (
    SELECT 'Paris' as ville UNION ALL
    SELECT 'Marseille' UNION ALL
    SELECT 'Lyon' UNION ALL
    SELECT 'Toulouse' UNION ALL
    SELECT 'Nice' UNION ALL
    SELECT 'Nantes' UNION ALL
    SELECT 'Strasbourg' UNION ALL
    SELECT 'Montpellier' UNION ALL
    SELECT 'Bordeaux' UNION ALL
    SELECT 'Lille' UNION ALL
    SELECT 'Rennes' UNION ALL
    SELECT 'Reims' UNION ALL
    SELECT 'Le Havre' UNION ALL
    SELECT 'Saint-Étienne' UNION ALL
    SELECT 'Toulon' UNION ALL
    SELECT 'Grenoble' UNION ALL
    SELECT 'Dijon' UNION ALL
    SELECT 'Angers' UNION ALL
    SELECT 'Nîmes' UNION ALL
    SELECT 'Villeurbanne' UNION ALL
    SELECT 'Le Mans' UNION ALL
    SELECT 'Aix-en-Provence' UNION ALL
    SELECT 'Brest' UNION ALL
    SELECT 'Tours' UNION ALL
    SELECT 'Amiens' UNION ALL
    SELECT 'Metz' UNION ALL
    SELECT 'Orléans' UNION ALL
    SELECT 'Rouen' UNION ALL
    SELECT 'Nancy' UNION ALL
    SELECT 'Argenteuil' UNION ALL
    SELECT 'Montreuil' UNION ALL
    SELECT 'Saint-Denis' UNION ALL
    SELECT 'Roubaix' UNION ALL
    SELECT 'Dunkerque' UNION ALL
    SELECT 'Avignon' UNION ALL
    SELECT 'Saint-Paul' UNION ALL
    SELECT 'Fort-de-France' UNION ALL
    SELECT 'Cayenne' UNION ALL
    SELECT 'Saint-Denis' UNION ALL
    SELECT 'Saint-Pierre'
) AS nouvelles_villes
WHERE NOT EXISTS (
    SELECT 1 
    FROM Ville v 
    WHERE v.nomVille = nouvelles_villes.ville 
    AND v.codePays = 'FRA'
);

-- 8) Liste des coureurs français
SELECT c.numDossard, c.nom, c.prenom, e.nomEquipe, c.DN
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
WHERE p.codePays = 'FRA'
ORDER BY c.nom, c.prenom;

-- 9) Liste des étapes de haute montagne
SELECT e.numEtape, e.dateEtape, e.distance, 
       v1.nomVille AS villeDepart, v2.nomVille AS villeArrivee,
       t.nomTypeEtape
FROM Etape e
JOIN Ville v1 ON e.numVille = v1.numVille
JOIN Ville v2 ON e.numVille_1 = v2.numVille
JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
WHERE t.nomTypeEtape LIKE '%Montagne%'
ORDER BY e.numEtape;

-- 10) Liste des coureurs qui ont obtenu des bonifications
SELECT DISTINCT c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays,
       COUNT(b.numEtape) AS nombre_bonifications,
       SUM(b.reductionTemps) AS total_reduction_temps
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Bonification b ON c.numDossard = b.numDossard
GROUP BY c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays
ORDER BY total_reduction_temps DESC;

-- 11) Liste des coureurs avec leur équipe et pays
SELECT c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays, c.DN
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
ORDER BY p.nomPays, e.nomEquipe, c.nom, c.prenom;

-- 12) Villes étapes avec leur pays et le type d'étape (départ ou arrivée)
SELECT 
    v.nomVille, 
    p.nomPays,
    CASE 
        WHEN e.numVille = v.numVille THEN 'Départ'
        WHEN e.numVille_1 = v.numVille THEN 'Arrivée'
    END AS type_etape,
    e.numEtape,
    e.dateEtape,
    t.nomTypeEtape
FROM Ville v
JOIN Pays p ON v.codePays = p.codePays
JOIN Etape e ON v.numVille = e.numVille OR v.numVille = e.numVille_1
JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
ORDER BY e.numEtape, type_etape;

-- 13) Coureurs et étapes auxquelles ils ont participé
SELECT 
    c.numDossard, 
    c.nom, 
    c.prenom, 
    e.nomEquipe, 
    p.nomPays,
    et.numEtape,
    et.dateEtape,
    et.distance,
    v1.nomVille AS villeDepart,
    v2.nomVille AS villeArrivee,
    t.nomTypeEtape,
    perf.temps
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
JOIN Etape et ON perf.numEtape = et.numEtape
JOIN Ville v1 ON et.numVille = v1.numVille
JOIN Ville v2 ON et.numVille_1 = v2.numVille
JOIN TypeEtape t ON et.idTypeEtape = t.idTypeEtape
ORDER BY c.nom, c.prenom, et.numEtape;

-- 14) Nombre de coureurs par équipe
SELECT 
    e.numEquipe,
    e.nomEquipe,
    COUNT(c.numDossard) AS nombre_coureurs
FROM Equipe e
LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
GROUP BY e.numEquipe, e.nomEquipe
ORDER BY nombre_coureurs DESC, e.nomEquipe;

-- 15) Distance totale du Tour de France
SELECT 
    SUM(distance) AS distance_totale_km,
    ROUND(SUM(distance) / 1.60934, 2) AS distance_totale_miles
FROM Etape;

-- 16) Distance totale par type d'étape
SELECT 
    t.nomTypeEtape,
    COUNT(e.numEtape) AS nombre_etapes,
    SUM(e.distance) AS distance_totale_km,
    ROUND(SUM(e.distance) / 1.60934, 2) AS distance_totale_miles,
    ROUND(AVG(e.distance), 2) AS distance_moyenne_km
FROM Etape e
JOIN TypeEtape t ON e.idTypeEtape = t.idTypeEtape
GROUP BY t.idTypeEtape, t.nomTypeEtape
ORDER BY distance_totale_km DESC;

-- 17) Âge moyen des coureurs par équipe
SELECT 
    e.numEquipe,
    e.nomEquipe,
    COUNT(c.numDossard) AS nombre_coureurs,
    ROUND(AVG(TIMESTAMPDIFF(YEAR, c.DN, CURDATE())), 1) AS age_moyen
FROM Equipe e
LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
GROUP BY e.numEquipe, e.nomEquipe
ORDER BY age_moyen DESC, e.nomEquipe;

-- 18) Équipes avec au moins 7 coureurs
SELECT 
    e.numEquipe,
    e.nomEquipe,
    COUNT(c.numDossard) AS nombre_coureurs
FROM Equipe e
LEFT JOIN Coureur c ON e.numEquipe = c.numEquipe
GROUP BY e.numEquipe, e.nomEquipe
HAVING COUNT(c.numDossard) >= 7
ORDER BY nombre_coureurs DESC, e.nomEquipe;

-- 19) Les 3 meilleurs temps réalisés sur une étape de haute montagne
SELECT 
    c.numDossard,
    c.nom,
    c.prenom,
    e.nomEquipe,
    p.nomPays,
    et.numEtape,
    et.dateEtape,
    et.distance,
    v1.nomVille AS villeDepart,
    v2.nomVille AS villeArrivee,
    t.nomTypeEtape,
    perf.temps
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
JOIN Etape et ON perf.numEtape = et.numEtape
JOIN Ville v1 ON et.numVille = v1.numVille
JOIN Ville v2 ON et.numVille_1 = v2.numVille
JOIN TypeEtape t ON et.idTypeEtape = t.idTypeEtape
WHERE t.nomTypeEtape LIKE '%Montagne%'
ORDER BY perf.temps ASC
LIMIT 3;

-- 20) Classement général du Tour de France (avec bonifications)
SELECT 
    c.numDossard,
    c.nom,
    c.prenom,
    e.nomEquipe,
    p.nomPays,
    COUNT(DISTINCT perf.numEtape) AS etapes_completees,
    SEC_TO_TIME(SUM(TIME_TO_SEC(perf.temps))) AS temps_total,
    SEC_TO_TIME(COALESCE(SUM(b.reductionTemps), 0)) AS total_bonifications,
    SEC_TO_TIME(SUM(TIME_TO_SEC(perf.temps)) - COALESCE(SUM(b.reductionTemps), 0)) AS temps_total_avec_bonifications
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
LEFT JOIN Bonification b ON c.numDossard = b.numDossard
GROUP BY c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays
HAVING COUNT(DISTINCT perf.numEtape) = (SELECT COUNT(*) FROM Etape)
ORDER BY temps_total_avec_bonifications ASC;

-- 21) Évolution du temps par étape pour un coureur donné (exemple avec le coureur numéro 1)
SELECT 
    c.numDossard,
    c.nom,
    c.prenom,
    e.nomEquipe,
    p.nomPays,
    et.numEtape,
    et.dateEtape,
    et.distance,
    v1.nomVille AS villeDepart,
    v2.nomVille AS villeArrivee,
    t.nomTypeEtape,
    perf.temps,
    COALESCE(b.reductionTemps, 0) AS bonification,
    SEC_TO_TIME(TIME_TO_SEC(perf.temps) - COALESCE(b.reductionTemps, 0)) AS temps_avec_bonification
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
JOIN Etape et ON perf.numEtape = et.numEtape
JOIN Ville v1 ON et.numVille = v1.numVille
JOIN Ville v2 ON et.numVille_1 = v2.numVille
JOIN TypeEtape t ON et.idTypeEtape = t.idTypeEtape
LEFT JOIN Bonification b ON c.numDossard = b.numDossard AND et.numEtape = b.numEtape
WHERE c.numDossard = 1  -- Remplacer par le numéro de dossard du coureur souhaité
ORDER BY et.numEtape;

-- 22) Constance des performances (écart-type des temps)
SELECT 
    c.numDossard,
    c.nom,
    c.prenom,
    e.nomEquipe,
    p.nomPays,
    COUNT(DISTINCT perf.numEtape) AS etapes_completees,
    SEC_TO_TIME(SUM(TIME_TO_SEC(perf.temps))) AS temps_total,
    SEC_TO_TIME(ROUND(STDDEV(TIME_TO_SEC(perf.temps)), 0)) AS ecart_type_secondes,
    SEC_TO_TIME(ROUND(AVG(TIME_TO_SEC(perf.temps)), 0)) AS temps_moyen,
    SEC_TO_TIME(ROUND(MIN(TIME_TO_SEC(perf.temps)), 0)) AS temps_minimum,
    SEC_TO_TIME(ROUND(MAX(TIME_TO_SEC(perf.temps)), 0)) AS temps_maximum
FROM Coureur c
JOIN Equipe e ON c.numEquipe = e.numEquipe
JOIN Pays p ON c.codePays = p.codePays
JOIN Performance perf ON c.numDossard = perf.numDossard
GROUP BY c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays
HAVING COUNT(DISTINCT perf.numEtape) >= 10  -- Filtrer pour n'avoir que les coureurs ayant complété au moins 10 étapes
ORDER BY ecart_type_secondes ASC;  -- Les coureurs les plus constants (écart-type le plus faible) apparaissent en premier 

-- 23) Classement par équipe (somme des temps des 3 meilleurs coureurs par étape)
WITH MeilleursTempsParEtape AS (
    SELECT 
        e.numEquipe,
        e.nomEquipe,
        perf.numEtape,
        perf.temps,
        ROW_NUMBER() OVER (PARTITION BY e.numEquipe, perf.numEtape ORDER BY perf.temps ASC) AS rang
    FROM Equipe e
    JOIN Coureur c ON e.numEquipe = c.numEquipe
    JOIN Performance perf ON c.numDossard = perf.numDossard
)
SELECT 
    e.numEquipe,
    e.nomEquipe,
    COUNT(DISTINCT m.numEtape) AS etapes_completees,
    SEC_TO_TIME(SUM(TIME_TO_SEC(m.temps))) AS temps_total
FROM Equipe e
JOIN MeilleursTempsParEtape m ON e.numEquipe = m.numEquipe
WHERE m.rang <= 3  -- Ne prendre que les 3 meilleurs temps par équipe et par étape
GROUP BY e.numEquipe, e.nomEquipe
HAVING COUNT(DISTINCT m.numEtape) = (SELECT COUNT(*) FROM Etape)  -- Ne prendre que les équipes ayant participé à toutes les étapes
ORDER BY temps_total ASC; 

-- 24) Identification des spécialistes par type d'étape
WITH PerformancesParType AS (
    SELECT 
        c.numDossard,
        c.nom,
        c.prenom,
        e.nomEquipe,
        p.nomPays,
        t.nomTypeEtape,
        COUNT(DISTINCT perf.numEtape) AS nombre_etapes,
        AVG(TIME_TO_SEC(perf.temps)) AS temps_moyen_secondes,
        MIN(TIME_TO_SEC(perf.temps)) AS temps_min_secondes,
        MAX(TIME_TO_SEC(perf.temps)) AS temps_max_secondes,
        STDDEV(TIME_TO_SEC(perf.temps)) AS ecart_type_secondes
    FROM Coureur c
    JOIN Equipe e ON c.numEquipe = e.numEquipe
    JOIN Pays p ON c.codePays = p.codePays
    JOIN Performance perf ON c.numDossard = perf.numDossard
    JOIN Etape et ON perf.numEtape = et.numEtape
    JOIN TypeEtape t ON et.idTypeEtape = t.idTypeEtape
    GROUP BY c.numDossard, c.nom, c.prenom, e.nomEquipe, p.nomPays, t.nomTypeEtape
    HAVING COUNT(DISTINCT perf.numEtape) >= 3  -- Au moins 3 étapes du même type
),
RangParType AS (
    SELECT 
        numDossard,
        nom,
        prenom,
        nomEquipe,
        nomPays,
        nomTypeEtape,
        nombre_etapes,
        temps_moyen_secondes,
        temps_min_secondes,
        temps_max_secondes,
        ecart_type_secondes,
        ROW_NUMBER() OVER (PARTITION BY nomTypeEtape ORDER BY temps_moyen_secondes ASC) AS rang
    FROM PerformancesParType
)
SELECT 
    numDossard,
    nom,
    prenom,
    nomEquipe,
    nomPays,
    nomTypeEtape,
    nombre_etapes,
    SEC_TO_TIME(ROUND(temps_moyen_secondes, 0)) AS temps_moyen,
    SEC_TO_TIME(ROUND(temps_min_secondes, 0)) AS temps_minimum,
    SEC_TO_TIME(ROUND(temps_max_secondes, 0)) AS temps_maximum,
    SEC_TO_TIME(ROUND(ecart_type_secondes, 0)) AS ecart_type,
    rang
FROM RangParType
WHERE rang <= 5  -- Top 5 pour chaque type d'étape
ORDER BY 
    CASE 
        WHEN nomTypeEtape LIKE '%Montagne%' THEN 1
        WHEN nomTypeEtape LIKE '%Contre-la-montre%' THEN 2
        WHEN nomTypeEtape LIKE '%Plaine%' THEN 3
        ELSE 4
    END,
    rang; 