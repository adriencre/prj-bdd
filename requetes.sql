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

-- 8) Liste des équipes et coureurs par pays
SELECT 
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
    c.prenom ASC; 