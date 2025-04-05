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