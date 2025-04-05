<?php
// Démarrage de la session
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour de France 2022</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FFD800;
            --secondary-color: #333;
            --light-bg: #f8f9fa;
            --border-radius: 10px;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .card {
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            font-weight: bold;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .icon-card {
            background-color: white;
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .icon-card:hover {
            transform: translateY(-5px);
        }
        
        .icon-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .stats-icon {
            width: 40px;
            height: 40px;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .position-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .footer {
            background-color: white;
            padding: 20px 0;
            margin-top: 50px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .stats-card {
            padding: 20px;
            text-align: center;
            height: 100%;
        }
        
        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #FFD800;
        }
        
        .stats-card h3 {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .map-container {
            height: 300px;
            background-color: #eee;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .map-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .stage-chart {
            height: 200px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 10px;
        }
        
        .stage-chart .bar {
            width: 30px;
            background-color: #2ecc71;
            border-radius: 5px 5px 0 0;
        }
        
        .stage-chart .bar.mountains {
            background-color: #e74c3c;
        }
        
        .stage-chart .bar.flat {
            background-color: #3498db;
        }
        
        .stage-chart .bar.medium {
            background-color: #f39c12;
        }
        
        .standing-table img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Tour de France Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Index</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Team.php">Équipes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Coureur.php">Coureurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Etape.php">Étapes</a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>