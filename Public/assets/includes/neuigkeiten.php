<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/Private/Database/Database.php';

class Neuigkeiten {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Holt alle aktiven Neuigkeiten aus der Datenbank
     * 
     * @param int $limit Optional. Begrenzt die Anzahl der zurückgegebenen Einträge
     * @return array Array mit Neuigkeiten
     */
    public function getAktiveNeuigkeiten($limit = null) {
        $sql = "SELECT * FROM neuigkeiten WHERE aktiv = 1 ORDER BY Datum DESC";
        
        if ($limit !== null && is_numeric($limit)) {
            $sql .= " LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Gibt eine einzelne Neuigkeit anhand der ID zurück
     * 
     * @param int $id ID der Neuigkeit
     * @return array|bool Neuigkeit als Array oder false wenn nicht gefunden
     */
    public function getNeuigkeitById($id) {
        $sql = "SELECT * FROM neuigkeiten WHERE ID = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Zeigt Neuigkeiten in einer Kartendarstellung an mit Modal für Details
     * 
     * @param int $limit Optional. Begrenzt die Anzahl der angezeigten Einträge
     * @return string HTML-Output
     */
    public function zeigeNeuigkeitenKarten($limit = null) {
        $neuigkeiten = $this->getAktiveNeuigkeiten($limit);
        
        $output = '<div class="row neuigkeiten-container">';
        $modals = '';
        
        foreach ($neuigkeiten as $neuigkeit) {
            $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
            $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
            $id = $neuigkeit['ID'];
            
            $output .= '
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card h-100 neuigkeit-card">
                    <div class="card-img-wrapper">
                        <img src="' . htmlspecialchars($bildPfad) . '" class="card-img-top" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                        <h6 class="card-subtitle mb-2 text-muted">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</h6>
                        <p class="card-text">' . htmlspecialchars($neuigkeit['kurzinfo']) . '</p>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#neuigkeitModal' . $id . '">
                            Mehr erfahren
                        </button>
                    </div>
                </div>
            </div>';
            
            // Modal für jede Neuigkeit
            $modals .= $this->erstelleNeuigkeitModal($neuigkeit);
        }
        
        $output .= '</div>';
        
        // Modals am Ende anhängen
        $output .= $modals;
        
        if (empty($neuigkeiten)) {
            $output = '<div class="alert alert-info">Aktuell sind keine Neuigkeiten vorhanden.</div>';
        }
        
        return $output;
    }
    
    /**
     * Erstellt ein Modal für eine einzelne Neuigkeit
     * 
     * @param array $neuigkeit Die Neuigkeit als Array
     * @return string HTML-Output für das Modal
     */
    private function erstelleNeuigkeitModal($neuigkeit) {
        $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
        $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
        $id = $neuigkeit['ID'];
        
        return '
        <div class="modal fade" id="neuigkeitModal' . $id . '" tabindex="-1" aria-labelledby="neuigkeitModalLabel' . $id . '" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="neuigkeitModalLabel' . $id . '">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <div class="neuigkeit-inhalt">
                                    <p class="text-muted">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</p>
                                    <div class="mt-3">
                                        ' . nl2br(htmlspecialchars($neuigkeit['Information'])) . '
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="flyer-image-container">
                                    <img src="' . htmlspecialchars($bildPfad) . '" class="img-fluid rounded flyer-image" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Zeigt Neuigkeiten in einer Listenansicht an
     * 
     * @param int $limit Optional. Begrenzt die Anzahl der angezeigten Einträge
     * @return string HTML-Output
     */
    public function zeigeNeuigkeitenListe($limit = null) {
        $neuigkeiten = $this->getAktiveNeuigkeiten($limit);
        
        $output = '<div class="list-group neuigkeiten-liste">';
        $modals = '';
        
        foreach ($neuigkeiten as $neuigkeit) {
            $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
            $id = $neuigkeit['ID'];
            
            $output .= '
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h5>
                    <small>' . $datum . '</small>
                </div>
                <p class="mb-1">' . htmlspecialchars($neuigkeit['kurzinfo']) . '</p>
                <small>' . htmlspecialchars($neuigkeit['Ort']) . '</small>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#neuigkeitModal' . $id . '">
                        Mehr erfahren
                    </button>
                </div>
            </div>';
            
            // Modal für jede Neuigkeit
            $modals .= $this->erstelleNeuigkeitModal($neuigkeit);
        }
        
        $output .= '</div>';
        
        // Modals am Ende anhängen
        $output .= $modals;
        
        if (empty($neuigkeiten)) {
            $output = '<div class="alert alert-info">Aktuell sind keine Neuigkeiten vorhanden.</div>';
        }
        
        return $output;
    }
    
    /**
     * Zeigt eine einzelne Neuigkeit detailliert an
     * (Legacy-Funktion für die Detailseite, falls noch benötigt)
     * 
     * @param int $id ID der Neuigkeit
     * @return string HTML-Output
     */
    public function zeigeNeuigkeitDetail($id) {
        $neuigkeit = $this->getNeuigkeitById($id);
        
        if (!$neuigkeit) {
            return '<div class="alert alert-danger">Die angeforderte Neuigkeit wurde nicht gefunden.</div>';
        }
        
        $bildPfad = $neuigkeit['path_to_image'] ?? '/Veranstaltungen/Flyer/default.jpg';
        $datum = date('d.m.Y', strtotime($neuigkeit['Datum']));
        
        $output = '
        <div class="neuigkeit-detail">
            <div class="row">
                <div class="col-md-8">
                    <h1>' . htmlspecialchars($neuigkeit['Ueberschrift']) . '</h1>
                    <p class="text-muted">' . $datum . ' | ' . htmlspecialchars($neuigkeit['Ort']) . '</p>
                    <div class="neuigkeit-inhalt mt-4">
                        ' . nl2br(htmlspecialchars($neuigkeit['Information'])) . '
                    </div>
                </div>
                <div class="col-md-4">
                    <img src="' . htmlspecialchars($bildPfad) . '" class="img-fluid rounded" alt="' . htmlspecialchars($neuigkeit['Ueberschrift']) . '">
                </div>
            </div>
        </div>';
        
        return $output;
    }
}

/**
 * Beispiel für die Verwendung in anderen Dateien:
 * 
 * <?php
 * require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/neuigkeiten.php';
 * $neuigkeiten = new Neuigkeiten();
 * 
 * // Neuigkeiten als Karten anzeigen (z.B. auf der Startseite)
 * echo $neuigkeiten->zeigeNeuigkeitenKarten(3); // Zeigt die neuesten 3 Einträge
 * 
 * // Oder als Liste
 * // echo $neuigkeiten->zeigeNeuigkeitenListe(5); // Zeigt die neuesten 5 Einträge
 * 
 * // Einzelne Neuigkeit anzeigen (alte Methode, jetzt über Modal)
 * // echo $neuigkeiten->zeigeNeuigkeitDetail($_GET['id']);
 * ?>
 */
?>
