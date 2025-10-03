/**
 * 2-Klick-Lösung für Google Maps
 * Lädt Google Maps erst nach Zustimmung des Nutzers
 */

class TwoClickMap {
    constructor(containerId, mapUrl, options = {}) {
        this.container = document.getElementById(containerId);
        this.mapUrl = mapUrl;
        this.options = {
            theme: options.theme || 'feuerwehr', // 'feuerwehr' oder 'grillhuette'
            title: options.title || 'Karte anzeigen',
            description: options.description || 'Klicken Sie auf "Karte laden", um die Google Maps Karte anzuzeigen.',
            icon: options.icon || 'bi-geo-alt-fill',
            storageKey: options.storageKey || 'map-consent-' + containerId,
            autoLoad: options.autoLoad || false
        };
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Map container not found:', this.container);
            return;
        }
        
        // Prüfe, ob Zustimmung bereits gegeben wurde
        if (this.options.autoLoad || this.hasConsent()) {
            this.loadMap();
        } else {
            this.showConsentOverlay();
        }
    }
    
    hasConsent() {
        try {
            return localStorage.getItem(this.options.storageKey) === 'true';
        } catch (e) {
            return false;
        }
    }
    
    saveConsent() {
        try {
            localStorage.setItem(this.options.storageKey, 'true');
        } catch (e) {
            console.warn('LocalStorage nicht verfügbar');
        }
    }
    
    showConsentOverlay() {
        const themeClass = this.options.theme === 'grillhuette' ? 'grillhuette' : '';
        
        this.container.innerHTML = `
            <div class="map-consent-overlay ${themeClass}">
                <div class="map-consent-content">
                    <div class="map-consent-icon">
                        <i class="bi ${this.options.icon}"></i>
                    </div>
                    <h3 class="map-consent-title">${this.options.title}</h3>
                    <p class="map-consent-text">
                        ${this.options.description}
                    </p>
                    <button class="btn btn-activate-map" data-action="activate-map">
                        <i class="bi bi-check-circle-fill"></i>
                        Karte laden
                    </button>
                    <div class="map-consent-privacy">
                        <i class="bi bi-shield-lock-fill"></i>
                        <strong>Datenschutzhinweis:</strong> Durch das Laden der Karte werden Daten an Google übertragen. 
                        Ihre IP-Adresse wird dabei an Google-Server gesendet. 
                        <a href="/Datenschutz" target="_blank" style="color: #ffc107; text-decoration: underline;">Mehr erfahren</a>
                    </div>
                </div>
            </div>
        `;
        
        // Event Listener für Aktivierung (CSP-konform ohne onclick)
        const button = this.container.querySelector('[data-action="activate-map"]');
        if (button) {
            button.addEventListener('click', () => {
                this.saveConsent();
                this.loadMap();
            });
        }
    }
    
    loadMap() {
        // Zeige Loading-Spinner
        this.container.innerHTML = `
            <div class="map-loading">
                <div class="map-loading-spinner"></div>
            </div>
        `;
        
        // Lade die Karte nach kurzer Verzögerung (für bessere UX)
        setTimeout(() => {
            this.container.innerHTML = `
                <div class="map-iframe-container">
                    <iframe
                        src="${this.mapUrl}"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="${this.options.title}">
                    </iframe>
                </div>
            `;
        }, 300);
    }
    
    // Methode zum Zurücksetzen der Zustimmung (für Entwicklung/Testing)
    static resetConsent(storageKey) {
        try {
            localStorage.removeItem(storageKey);
            console.log('Zustimmung zurückgesetzt für:', storageKey);
        } catch (e) {
            console.warn('Konnte Zustimmung nicht zurücksetzen');
        }
    }
    
    // Methode zum Zurücksetzen aller Map-Zustimmungen
    static resetAllConsents() {
        try {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('map-consent-')) {
                    localStorage.removeItem(key);
                }
            });
            console.log('Alle Map-Zustimmungen zurückgesetzt');
        } catch (e) {
            console.warn('Konnte Zustimmungen nicht zurücksetzen');
        }
    }
}

// Globale Funktion für einfache Initialisierung
function initTwoClickMap(containerId, mapUrl, options) {
    return new TwoClickMap(containerId, mapUrl, options);
}

// Export für Module (falls verwendet)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TwoClickMap;
}

