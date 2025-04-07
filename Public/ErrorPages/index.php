<?php
// Fehlerseiten-Liste
$errorPages = [
    '400' => 'Ungültige Anfrage',
    '401' => 'Nicht autorisiert',
    '403' => 'Zugriff verweigert',
    '404' => 'Seite nicht gefunden',
    '408' => 'Anfragezeitüberschreitung',
    '429' => 'Zu viele Anfragen',
    '500' => 'Interner Serverfehler',
    '502' => 'Ungültiges Gateway',
    '503' => 'Dienst nicht verfügbar',
    '504' => 'Gateway-Zeitüberschreitung'
];

// Generate a unique nonce for CSP
$errorPageNonce = base64_encode(random_bytes(16));
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'nonce-<?php echo $errorPageNonce; ?>'">
    <title>Fehlerseiten-Übersicht - Feuerwehr Waldems Reichenbach</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap">
    <link rel="stylesheet" href="error-styles.css">
    <style>
        .error-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .error-btn {
            padding: 15px;
            text-align: center;
            border-radius: var(--border-radius);
            background-color: var(--primary);
            color: var(--text-light);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .error-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .error-code-big {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 5px;
        }
        
        .secret-badge {
            display: inline-block;
            background-color: var(--secondary);
            color: var(--text-dark);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">112</div>
        <h1 class="error-title">Geheime Kommandozentrale</h1>
        <span class="secret-badge">Streng geheim!</span>
        <p class="error-message">Glückwunsch! Du hast die geheime Fehlerseiten-Zentrale der Feuerwehr Reichenbach entdeckt! Hier kannst du alle möglichen digitalen Alarme auslösen und unsere Fehlerseiten testen. Aber pssst... erzähl's nicht weiter! Wähle einen Fehler und sieh dir an, wie er aussieht:</p>
        
        <div class="error-buttons">
            <?php foreach($errorPages as $code => $title): ?>
            <a href="<?php echo $code; ?>.html" class="error-btn">
                <div class="error-code-big"><?php echo $code; ?></div>
                <div><?php echo $title; ?></div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="buttons-container" style="margin-top: 30px;">
            <a href="/" class="btn btn-home">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Zurück zur Startseite
            </a>
        </div>
    </div>
    
    <script src="error-animations.js" nonce="<?php echo $errorPageNonce; ?>"></script>
</body>
</html>
