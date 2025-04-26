<?php
class PageBuilder
{
    // ────────── Eigenschaften ──────────
    private string $title;
    private string $description;
    private array $styles = [];
    private array $scripts = [];
    private array $contentBlocks = [];
    private string $keywords;
    private string $author;
    private ?string $canonicalUrl;
    private string $favicon = '/assets/images/gravatar-logo-dunkel.webp';
    private string $ogImage = '/assets/images/gravatar-logo-dunkel.webp';
    private string $twitterCardType = 'summary_large_image';
    private array $schemaOrgData = [];
    private string $metaRobots = 'index, follow';
    private array $alternateLanguages = [];
    private string $siteLanguage = 'de-DE';
    private string $locale = 'de_DE';
    private string $organizationName = 'Freiwillige Feuerwehr Waldems Reichenbach';
    private string $organizationLogo = '/assets/images/gravatar-logo-dunkel.webp';
    private string $location = 'Waldems, Hessen, DE';
    private string $contactEmail = 'info@feuerwehr-waldems-reichenbach.de';
    private string $contactType = 'info';



    // ────────── Konstruktor ──────────
    public function __construct(
        string $title = 'Meine Seite',
        string $description = '',
        string $keywords = '',
        ?string $canonicalUrl = null,
        string $author = 'Freiwillige Feuerwehr Waldems Reichenbach',
        string $ogImage = '/assets/images/gravatar-logo-dunkel.webp',
        string $twitterCardType = 'summary_large_image',
        string $metaRobots = 'index, follow',
        string $siteLanguage = 'de-DE',
        string $locale = 'de_DE',
        string $organizationName = 'Freiwillige Feuerwehr Waldems Reichenbach',
        string $organizationLogo = '/assets/images/gravatar-logo-dunkel.webp',
        string $location = 'Am Dorfgemeinschaftshaus 1, 65529 Waldems, Hessen, DE',
        string $contactEmail = 'info@feuerwehr-waldems-reichenbach.de',
        string $contactType = 'info'
    ) {
        $this->title = $title;
        $this->description = $description;
        $this->keywords = $keywords;
        $this->author = $author;
        $this->canonicalUrl = $canonicalUrl ?? "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $this->ogImage = $ogImage;
        $this->twitterCardType = $twitterCardType;
        $this->metaRobots = $metaRobots;
        $this->siteLanguage = $siteLanguage;
        $this->locale = $locale;
        $this->organizationName = $organizationName;
        $this->organizationLogo = $organizationLogo ?: $this->favicon;
        $this->location = $location;
        $this->contactEmail = $contactEmail;
        $this->contactType = $contactType;
        
        // Schema.org-Daten automatisch generieren
        $this->buildDefaultSchemaOrgData();

        $this->styles = [
            '/assets/bootstrap/css/bootstrap.min.css',
            '/assets/bootstrap/css/bootstrap-grid.min.css',
            '/assets/bootstrap/css/bootstrap-reboot.min.css',
            '/assets/parallax/jarallax.css',
            '/assets/dropdown/css/style.css',
            '/assets/socicon/css/styles.css',
            '/assets/theme/css/style.css',
            '/assets/css/custom-parallax.css',
            '/assets/ffr/css/ffr-additional.css?v=M1cYSM',
        ];
    }

    // Neue Methode zum Erstellen der Standarddaten für Schema.org
    private function buildDefaultSchemaOrgData(): void
    {
        // Aufteilen des Standorts in seine Komponenten
        $locationParts = explode(', ', $this->location);
        $addressLocality = $locationParts[0] ?? '';
        $addressRegion = $locationParts[1] ?? '';
        $addressCountry = $locationParts[2] ?? 'DE';
        
        // URL der aktuellen Seite
        $currentUrl = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        
        // Organization Schema
        $this->schemaOrgData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->organizationName,
            'url' => $currentUrl,
            'logo' => "https://{$_SERVER['HTTP_HOST']}/" . ltrim($this->organizationLogo, '/'),
            'description' => $this->description,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $addressLocality,
                'addressRegion' => $addressRegion,
                'addressCountry' => $addressCountry
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => $this->contactEmail,
                'contactType' => $this->contactType
            ]
        ];
        
        // WebPage Schema
        $this->schemaOrgData[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $this->title,
            'description' => $this->description,
            'url' => $currentUrl
        ];
    }


    // ────────── Öffentliche Helfer ──────────
    public function addStyle(string $href): void
    {
        // Ensure path starts with a slash for absolute paths from the site root
        $href = '/' . ltrim($href, '/');
        $this->styles[] = $href;
    }

    public function addScript(string $src): void
    {
        // Ensure path starts with a slash for absolute paths from the site root
        $src = '/' . ltrim($src, '/');
        $this->scripts[] = $src;
    }

    public function addContent(string $html): void
    {
        $this->contentBlocks[] = $html;
    }

    public function setFavicon(string $href): void
    {
        // Ensure path starts with a slash for absolute paths from the site root
        $this->favicon = '/' . ltrim($href, '/');
    }

    // Neue SEO-Methoden
    public function setOgImage(string $src): void
    {
        // Ensure path starts with a slash for absolute paths from the site root
        $this->ogImage = '/' . ltrim($src, '/');
    }

    public function setTwitterCardType(string $type): void
    {
        $this->twitterCardType = $type;
    }

    public function setMetaRobots(string $content): void
    {
        $this->metaRobots = $content;
    }

    public function setSiteLanguage(string $language): void
    {
        $this->siteLanguage = $language;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function addAlternateLanguage(string $hreflang, string $url): void
    {
        $this->alternateLanguages[$hreflang] = $url;
    }

    public function addSchemaOrgData(array $data): void
    {
        // Wenn es ein mehrdimensionales Array ist, füge jedes Element hinzu
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $schema) {
                $this->schemaOrgData[] = $schema;
            }
        } else {
            // Sonst füge das einzelne Schema-Objekt hinzu
            $this->schemaOrgData[] = $data;
        }
    }

    // ────────── Head generieren ──────────
    public function renderHead(): string
    {
        $styleTags = '';
        foreach ($this->styles as $css) {
            $styleTags .= "<link rel=\"stylesheet\" href=\"{$css}\">\n        ";
        }

        $scriptTags = '';
        foreach ($this->scripts as $js) {
            $scriptTags .= "<script src=\"{$js}\" defer></script>\n        ";
        }

        $ogTitle = htmlspecialchars($this->title);
        $ogDescription = htmlspecialchars($this->description);
        $canonicalUrl = htmlspecialchars($this->canonicalUrl);
        $ogImage = htmlspecialchars("https://{$_SERVER['HTTP_HOST']}/" . ltrim($this->ogImage, '/'));
        
        // Alternate Language Tags
        $alternateTags = '';
        foreach ($this->alternateLanguages as $hreflang => $url) {
            $alternateTags .= "<link rel=\"alternate\" hreflang=\"{$hreflang}\" href=\"{$url}\">\n        ";
        }
        
        // Schema.org JSON-LD
        $schemaOrgScript = '';
        if (!empty($this->schemaOrgData)) {
            $schemaOrgScript = "\n        <script type=\"application/ld+json\">\n        " . 
                              json_encode($this->schemaOrgData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
                              "\n        </script>";
        }

        return <<<HTML
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{$this->description}">
        <meta name="keywords" content="{$this->keywords}">
        <meta name="author" content="{$this->author}">
        <meta name="robots" content="{$this->metaRobots}">
        <meta name="language" content="{$this->siteLanguage}">

        <!-- Open Graph / Facebook -->
        <meta property="og:title" content="{$ogTitle}">
        <meta property="og:description" content="{$ogDescription}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{$canonicalUrl}">
        <meta property="og:image" content="{$ogImage}">
        <meta property="og:locale" content="{$this->locale}">
        <meta property="og:site_name" content="Freiwillige Feuerwehr Waldems Reichenbach">

        <!-- Twitter -->
        <meta name="twitter:card" content="{$this->twitterCardType}">
        <meta name="twitter:title" content="{$ogTitle}">
        <meta name="twitter:description" content="{$ogDescription}">
        <meta name="twitter:image" content="{$ogImage}">

        {$alternateTags}
        <link rel="canonical" href="{$canonicalUrl}">
        <link rel="shortcut icon" href="{$this->favicon}" type="image/x-icon">
        <title>{$this->title}</title>

        {$styleTags}
        {$scriptTags}
        {$schemaOrgScript}

        <link rel="preload"
              href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900&display=swap"
              as="style"
              onload="this.onload=null;this.rel='stylesheet'">
        <noscript>
            <link rel="stylesheet"
                  href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900&display=swap">
        </noscript>
    </head>
    HTML;
    }


    public function renderFullscreenHero(
        string $id,
        string $title,
        string $subtitle,
        string $buttonText,
        string $buttonHref,
        string $cidSuffix = '',
        float  $jarallaxSpeed = 0.8,
        float  $overlayOpacity = 0.2,
        string $overlayColor = 'rgb(0, 0, 0)',
        string $btnClass = 'btn-secondary',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="header16 {$cidClass} ffr-fullscreen jarallax" id="{$id}" data-jarallax-speed="{$jarallaxSpeed}">
        <div class="ffr-overlay" style="opacity: {$overlayOpacity}; background-color: {$overlayColor};"></div>
        <div class="container-fluid">
            <div class="row">
                <div class="content-wrap col-12 col-md-12">
                    <h1 class="ffr-section-title ffr-fonts-style ffr-white mb-4 display-1">
                        <strong>{$title}</strong>
                    </h1>
                    <p class="ffr-fonts-style ffr-text ffr-white mb-4 display-7">
                        {$subtitle}
                    </p>
                    <div class="ffr-section-btn">
                        <a class="btn {$btnClass} display-7" href="{$buttonHref}">{$buttonText}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }



    /**
     * Liefert einen "Bild + Text" Teaser-Abschnitt (Layout image08)
     *
     * @param string $id          HTML-ID des Abschnitts (z. B. "image08-h")
     * @param string $title       Hauptüberschrift (h5 → display-2)
     * @param string $subtitle    Unterüberschrift (h6 → display-7)
     * @param string $linkHref    URL des Buttons
     * @param string $linkText    Beschriftung des Buttons
     * @param string $imageSrc    Bildquelle
     * @param string $imageAlt    Alt-Attribut des Bildes
     * @param string $btnClass    optionale zusätzliche Button-Klasse (z. B. "btn-primary")
     * @return string             Fertiger HTML-Code
     */
    public function renderImageTeaser(
        string $id,
        string $cidSuffix = '',
        string $title,
        string $subtitle,
        string $linkHref,
        string $linkText,
        string $imageSrc,
        string $imageAlt = '',
        string $btnClass = 'btn-secondary'
    ): string {
        // Bootstrap-Version fix (nutzt i. d. R. 5.1)
        $bsVersion = '5.1';
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="image08 {$cidClass}" id="{$id}">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4">
                            <div class="col-12 col-md-12">
                                <h5 class="ffr-section-title ffr-fonts-style mt-0 mb-4 display-2">
                                    <strong>{$title}</strong>
                                </h5>
                                <h6 class="ffr-section-subtitle ffr-fonts-style mt-0 mb-4 display-7">
                                    {$subtitle}
                                </h6>
                                <div class="ffr-section-btn item-footer mt-3 main-button">
                                    <a href="{$linkHref}" class="btn item-btn {$btnClass} display-7">
                                        {$linkText}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 side-features">
                            <div class="image-wrapper mb-4">
                                <img class="w-100" src="{$imageSrc}" alt="{$imageAlt}" loading="lazy">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            HTML;
    }

    /**
     * Erzeugt einen Parallax-Header-Banner ("header14")
     *
     * @param string $id           Wert des id-Attributs z. B. "header14-o"
     * @param string $title        Überschrift (h1)
     * @param string $buttonHref   Ziel-URL des Call-to-Action-Buttons
     * @param string $buttonText   Button-Beschriftung
     * @param string $cidSuffix    Teil hinter "cid-" für Klasse (leer = kein cid-Teil)
     * @param string $btnClass     Bootstrap-Klasse des Buttons (Default: "btn-primary")
     * @param string $bsVersion    data-bs-version (Default: "5.1")
     * @return string              Fertiger HTML-Code
     */
    public function renderCallToActionBanner(
        string $id,
        string $title,
        string $buttonHref,
        string $buttonText,
        string $cidSuffix = '',
        string $btnClass = 'btn-primary',
        string $bsVersion = '5.1'
    ): string {
        // "cid-..." nur anhängen, wenn gewünscht
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="header14 {$cidClass} ffr-parallax-background" id="{$id}">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="card col-12 col-md-12 col-lg-9">
                            <div class="card-wrapper">
                                <div class="card-box align-center">
                                    <h1 class="card-title ffr-fonts-style mb-4 display-2">
                                        <strong>{$title}</strong>
                                    </h1>
                                    <div class="ffr-section-btn mt-4">
                                        <a href="{$buttonHref}" class="btn {$btnClass} display-7">
                                            {$buttonText}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            HTML;
    }

    /**
     * Rendert eine Accordion-Liste (list1)
     *
     * @param string $id           Abschnitt-ID, z. B. "list01-q"
     * @param string $title        Überschrift über dem Accordion
     * @param array  $items        FAQ-Einträge:
     *                             [
     *                               ['q' => 'Frage 1', 'a' => 'Antwort 1'],
     *                               …
     *                             ]
     * @param string $cidSuffix    CID-Suffix (leer lassen, wenn egal)
     * @param string $accordionId  HTML-ID des Accordion-Wrappers
     *                             (leer = automatisch "accordion-{$id}")
     * @param string $bsVersion    Bootstrap-Version im data-Attribut
     * @return string              Fertiger HTML-Block
     */
    public function renderAccordionList(
        string $id,
        string $title,
        array $items,
        string $cidSuffix = '',
        string $accordionId = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $accordionId = $accordionId !== '' ? $accordionId : "accordion-{$id}";
        $panelGroupId = "accordion-bootstrap-{$accordionId}";

        // ── Einzelne Cards zusammensetzen ────────────────────────────────────
        $cardsHtml = '';
        $i = 0;
        foreach ($items as $item) {
            $i++;
            $headingId = "heading{$i}";
            $collapseId = "collapse{$i}_{$accordionId}";
            $question = htmlspecialchars($item['q'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $answer = htmlspecialchars($item['a'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $cardsHtml .= <<<HTML
                            <div class="card">
                                <div class="card-header" role="tab" id="{$headingId}">
                                    <a role="button" class="panel-title collapsed"
                                       data-toggle="collapse" data-bs-toggle="collapse"
                                       href="#{$collapseId}" aria-expanded="false"
                                       aria-controls="{$collapseId}">
                                        <h6 class="panel-title-edit ffr-semibold ffr-fonts-style mb-0 display-5" style="margin-right: 10px;">
                                            {$question}
                                        </h6>
                                        <span class="sign ffr-iconfont ffr-arrow-down"></span>
                                    </a>
                                </div>
                                <div id="{$collapseId}" class="panel-collapse noScroll collapse" role="tabpanel"
                                     aria-labelledby="{$headingId}" data-bs-parent="#{$panelGroupId}">
                                    <div class="panel-body">
                                        <p class="ffr-fonts-style panel-text display-7 text-white">
                                            {$answer}
                                        </p>
                                    </div>
                                </div>
                            </div>

                HTML;
        }

        // ── Gesamtes Accordion zusammenbauen ────────────────────────────────
        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="list1 {$cidClass}" id="{$id}">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-12 col-md-12 col-lg-10 m-auto">
                            <div class="content">
                                <div class="row justify-content-center mb-5">
                                    <div class="col-12 content-head">
                                        <div class="ffr-section-head">
                                            <h4 class="ffr-section-title ffr-fonts-style align-center mb-0 display-2">
                                                <strong>{$title}</strong>
                                            </h4>
                                        </div>
                                    </div>
                                </div>

                                <div id="{$panelGroupId}" class="panel-group accordionStyles accordion"
                                     role="tablist" aria-multiselectable="true">
            {$cardsHtml}                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            HTML;
    }

    /**
     * Liefert alle Standard-Scripts (Bootstrap-Bundle, SmoothScroll, Jarallax …)
     * plus – optional – das Inline-Snippet zur Jarallax-Initialisierung.
     *
     * @param array $extraScripts    Zusätzliche Skript-URLs, die ebenfalls eingebunden werden sollen
     * @param bool  $initJarallax    true = Inline-Init-Snippet anhängen (Default)
     * @param bool  $defer           true = "defer"Attribut setzen, false = weglassen
     * @return string                Zusammenhängender <script>Block
     */
    public function renderScriptBundle(
        array $extraScripts = [],
        bool $initJarallax = true,
        bool $defer = false
    ): string {
        // Standard-Bundle
        $scripts = [
            '/assets/bootstrap/js/bootstrap.bundle.min.js',
            '/assets/smoothscroll/smooth-scroll.js',

            '/assets/theme/js/script.js',
            '/assets/parallax/jarallax.js',
            '/assets/scrollgallery/scroll-gallery.js',
        ];

        // Zusätzliche Pfade anfügen (falls übergeben)
        $scripts = array_merge($scripts, $extraScripts);

        // Script-Tags bauen
        $tags = '';
        foreach ($scripts as $src) {
            $tags .= '<script src="' . $src . '"' . ($defer ? ' defer' : '') . "></script>\n";
        }

        // Jarallax-Initialisierung anhängen?
        if ($initJarallax) {
            $tags .= <<<JS
                <script>
                document.addEventListener("DOMContentLoaded", function () {
                  jarallax(document.querySelectorAll('.jarallax'), {
                    speed: 0.6,
                    imgPosition: '50% 50%',
                    imgSize: 'cover'
                  });
                });
                </script>

                JS;
        }

        return $tags;
    }

    public function renderInclude(string $relativePath): string
    {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($relativePath, '/');

        if (!file_exists($fullPath)) {
            return "<!-- Include fehlgeschlagen: {$relativePath} -->";
        }

        ob_start();
        include $fullPath;
        return ob_get_clean();
    }

    public function renderPopup(): string
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/assets/includes/neuigkeiten.php';
        if (file_exists($path)) {
            require_once $path;

            if (function_exists('ShowPotentialPopup')) {
                ob_start();
                ShowPotentialPopup();
                return ob_get_clean();
            }
        }

        return '<!-- Popup konnte nicht geladen werden -->';
    }


    public function renderGalleryWithLightbox(
        string $id,
        string $title,
        array $images,
        string $lightboxId = 'gallery-modal',
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $lightboxId = htmlspecialchars($lightboxId);
        $titleEscaped = htmlspecialchars($title);

        // Galerie-Grid
        $gridHtml = '';
        $modalItems = '';
        $indicators = '';
        $i = 0;

        foreach ($images as $img) {
            $src = htmlspecialchars($img['src']);
            $alt = htmlspecialchars($img['alt'] ?? '');
            $slideTo = $i;

            // Galerie-Item
            $gridHtml .= <<<HTML
            <div class="col-12 col-md-6 col-lg-4 item gallery-image active">
                <div class="item-wrapper" data-bs-toggle="modal" data-bs-target="#{$lightboxId}-modal">
                    <img class="w-100" src="{$src}" alt="{$alt}"
                         data-bs-slide-to="{$slideTo}" data-bs-target="#lb-{$lightboxId}" loading="lazy">
                    <div class="icon-wrapper">
                        <span class="ffr-ffr ffr-ffr-search ffr-iconfont ffr-iconfont-btn"></span>
                    </div>
                </div>
            </div>
    HTML;

            // Modal-Item
            $activeClass = $i === 0 ? ' active' : '';
            $modalItems .= <<<HTML
                <div class="carousel-item{$activeClass}">
                    <img class="d-block w-100" src="{$src}" alt="{$alt}" loading="lazy">
                </div>
    HTML;

            // Indicator
            $indicatorActive = $i === 0 ? ' active' : '';
            $indicators .= <<<HTML
                <li data-bs-slide-to="{$slideTo}" data-bs-target="#lb-{$lightboxId}" class="{$indicatorActive}"></li>
    HTML;

            $i++;
        }

        // Full Gallery Section inkl. Modal
        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="gallery1 ffr-gallery {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 content-head">
                    <div class="ffr-section-head mb-5">
                        <h3 class="ffr-section-title ffr-fonts-style align-center m-0 display-2">
                            <strong>{$titleEscaped}</strong>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="row ffr-gallery ffr-masonry" data-masonry='{"percentPosition": true }'>
    {$gridHtml}
            </div>
    
            <!-- Lightbox Modal -->
            <div class="modal ffr-slider" tabindex="-1" role="dialog" id="{$lightboxId}-modal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="carousel slide" id="lb-{$lightboxId}" data-bs-interval="5000">
                                <div class="carousel-inner">
    {$modalItems}
                                </div>
                                <ol class="carousel-indicators">
    {$indicators}
                                </ol>
                                <a class="close" data-bs-dismiss="modal" aria-label="Close"></a>
                                <a class="carousel-control-prev carousel-control" role="button" data-bs-slide="prev" href="#lb-{$lightboxId}">
                                    <span class="ffr-ffr ffr-ffr-arrow-prev" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </a>
                                <a class="carousel-control-next carousel-control" role="button" data-bs-slide="next" href="#lb-{$lightboxId}">
                                    <span class="ffr-ffr ffr-ffr-arrow-next" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderImageInfoBlock(
        string $id,
        string $title,
        string $subtitle,
        string $imageSrc,
        string $imageAlt = '',
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $imageSrc = htmlspecialchars($imageSrc);
        $imageAlt = htmlspecialchars($imageAlt);

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="image08 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4">
                    <div class="col-12 col-md-12">
                        <h5 class="ffr-section-title ffr-fonts-style mt-0 mb-4 display-2">
                            <strong>{$title}</strong>
                        </h5>
                        <h6 class="ffr-section-subtitle ffr-fonts-style mt-0 mb-4 display-7">
                            {$subtitle}
                        </h6>
                    </div>
                </div>
                <div class="col-lg-8 side-features">
                    <div class="image-wrapper mb-4">
                        <img class="w-100" src="{$imageSrc}" alt="{$imageAlt}" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderFeatureCardsWithImages(
        string $id,
        string $title = '',
        array $features = [],
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $sectionTitle = $title !== ''
            ? "<div class=\"row justify-content-center\"><div class=\"col-12 content-head\"><h3 class=\"ffr-section-title ffr-fonts-style align-center mb-5 display-2\"><strong>" . htmlspecialchars($title) . "</strong></h3></div></div>"
            : '';

        $cardsHtml = '';
        foreach ($features as $feature) {
            $img = htmlspecialchars($feature['img'] ?? '');
            $alt = htmlspecialchars($feature['alt'] ?? '');
            $headline = $feature['title'] ?? '';
            $text = $feature['text'] ?? '';

            $cardsHtml .= <<<HTML
            <div class="item features-without-image col-12 col-lg-4 item-mb">
                <div class="item-wrapper">
                    <div class="card-box align-left">
                        <div class="img-wrapper mb-3">
                            <img src="{$img}" alt="{$alt}" loading="lazy">
                        </div>
                        <h5 class="card-title ffr-fonts-style display-5">
                            <strong>{$headline}</strong>
                        </h5>
                        <p class="card-text ffr-fonts-style display-7">
                            {$text}
                        </p>
                    </div>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="features19 {$cidClass}" id="{$id}">
        <div class="container">
            {$sectionTitle}
            <div class="row">
                {$cardsHtml}
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderTextArticle(
        string $id,
        string $title,
        string $text,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';


        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="article13 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-md-12 col-lg-12">
                    <div class="card-wrapper">
                        <div class="card-box align-left">
                            <h4 class="card-title ffr-fonts-style display-2">
                                <strong>{$title}</strong>
                            </h4>
                            <p class="ffr-text ffr-fonts-style mt-4 display-7">
                                {$text}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderImageSection(
        string $id,
        string $imageSrc,
        string $imageAlt = '',
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass  = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $imageSrc  = htmlspecialchars($imageSrc);
        $imageAlt  = htmlspecialchars($imageAlt);

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="image03 {$cidClass}" id="{$id}">
        <div class="image-block m-auto">
            <img src="{$imageSrc}" alt="{$imageAlt}" loading="lazy">
        </div>
    </section>
    HTML;
    }

    public function renderDownloadHeaderWithButtons(
        string $id,
        string $title,
        array $buttons, // Format: [['label' => 'Text', 'href' => 'link', 'class' => 'btn-primary'], ...]
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        $buttonHtml = '';
        foreach ($buttons as $btn) {
            $label = htmlspecialchars($btn['label'] ?? 'Button');
            $href  = htmlspecialchars($btn['href'] ?? '#');
            $class = htmlspecialchars($btn['class'] ?? 'btn-primary');
            $buttonHtml .= <<<HTML
            <a class="btn {$class} display-7" href="{$href}">{$label}</a>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="header14 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-12 col-md-12 col-lg-12">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <h1 class="card-title ffr-fonts-style mb-4 display-2">
                                <strong>{$title}</strong>
                            </h1>
                            <div class="ffr-section-btn mt-4">
                                {$buttonHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderCTAHeaderTextButtonBanner(
        string $id,
        string $title,
        string $text,
        string $buttonLabel,
        string $buttonHref,
        string $buttonClass = 'btn-primary',
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass   = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $buttonLabel = htmlspecialchars($buttonLabel);
        $buttonHref  = htmlspecialchars($buttonHref);
        $buttonClass = htmlspecialchars($buttonClass);

        return <<<HTML
<section data-bs-version="{$bsVersion}" class="header14 {$cidClass} ffr-parallax-background" id="{$id}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="card col-12 col-md-12 col-lg-12">
                <div class="card-wrapper">
                    <div class="card-box align-center">
                        <h1 class="card-title ffr-fonts-style mb-4 display-2">
                            <strong>{$title}</strong>
                        </h1>
                        <p class="ffr-text ffr-fonts-style mb-4 display-7 text-white">
                            {$text}
                        </p>
                        <div class="ffr-section-btn mt-4">
                            <a class="btn {$buttonClass} display-7" href="{$buttonHref}">
                                {$buttonLabel}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
    }


    public function renderFeatureCardsWithButtons(
        string $id,
        array $features,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $cardsHtml = '';

        foreach ($features as $feature) {
            $title = $feature['title'] ?? '';
            $text  = $feature['text'] ?? '';
            $btnLabel = htmlspecialchars($feature['button']['label'] ?? '');
            $btnHref  = htmlspecialchars($feature['button']['href'] ?? '#');
            $btnClass = htmlspecialchars($feature['button']['class'] ?? 'btn-secondary');

            $cardsHtml .= <<<HTML
            <div class="item features-without-image col-12 col-md-6 col-lg-4 item-mb">
                <div class="item-wrapper">
                    <div class="card-box align-left">
                        <h5 class="card-title ffr-fonts-style display-5">
                            <strong>{$title}</strong>
                        </h5>
                        <p class="card-text ffr-fonts-style display-7">
                            {$text}
                        </p>
                        <div class="ffr-section-btn item-footer">
                            <a href="{$btnHref}" class="btn item-btn {$btnClass} display-7">{$btnLabel}</a>
                        </div>
                    </div>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="features5 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row">
                {$cardsHtml}
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderFeatureSection(
        string $id,
        array $features,
        string $title = '',
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $sectionTitle = $title !== ''
            ? '<div class="row justify-content-center"><div class="col-12 mb-5 content-head"><h3 class="ffr-section-title ffr-fonts-style align-center mb-0 display-2"><strong>' . $title . '</strong></h3></div></div>'
            : '';

        $cardsHtml = '';
        foreach ($features as $feature) {
            $headline = $feature['title'] ?? '';
            $text     = $feature['text'] ?? '';

            $cardsHtml .= <<<HTML
            <div class="item features-without-image col-12 col-lg-4 item-mb">
                <div class="item-wrapper">
                    <div class="card-box align-left">
                        <h5 class="card-title ffr-fonts-style display-5">
                            <strong>{$headline}</strong>
                        </h5>
                        <p class="card-text ffr-fonts-style display-7">
                            {$text}
                        </p>
                    </div>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="features19 {$cidClass}" id="{$id}">
        <div class="container">
            {$sectionTitle}
            <div class="row">
                {$cardsHtml}
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderGoogleMap(
        string $id,
        string $iframeSrc,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass  = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $iframeSrc = htmlspecialchars($iframeSrc);

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="map1 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 content-head"></div>
            </div>
            <div class="google-map">
                <iframe frameborder="0" style="border:0" src="{$iframeSrc}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
    HTML;
    }


    public function renderTextSection(
        string $id,
        string $mainTitle,
        array $sections,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass   = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';


        $contentHtml = '';
        foreach ($sections as $section) {
            $subtitle = $section['subtitle'] ?? '';
            $text     = $section['text'] ?? ''; // HTML erlaubt, daher nicht escapen

            $contentHtml .= <<<HTML
            <div class="item features-without-image col-12">
                <div class="item-wrapper">
                    <h4 class="ffr-section-subtitle ffr-fonts-style mb-3 display-5">
                        <strong>{$subtitle}</strong>
                    </h4>
                    <p class="ffr-text ffr-fonts-style display-7">{$text}</p>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="article07 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-md-12 col-lg-10">
                    <div class="card-wrapper">
                        <h3 class="card-title ffr-fonts-style ffr-white mt-3 mb-4 display-2">
                            <strong>{$mainTitle}</strong>
                        </h3>
                        <div class="row card-box align-left">
                            {$contentHtml}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }


    public function renderDocumentDownloadCards(
        string $id,
        string $title,
        string $description,
        array $documents,
        string $cidSuffix = '',
        string $bsVersion = '5.1',
        string $textColorClass = 'text-white'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';


        $cardsHtml = '';
        foreach ($documents as $doc) {
            $docTitle = htmlspecialchars($doc['title'] ?? '');
            $docDesc  = htmlspecialchars($doc['description'] ?? '');
            $docLink  = htmlspecialchars($doc['href'] ?? '#');
            $btnLabel = htmlspecialchars($doc['button'] ?? 'Herunterladen');

            $cardsHtml .= <<<HTML
            <div class="col-md-6 mb-4">
                <div class="card-wrapper">
                    <div class="card-box align-left">
                        <h4 class="card-title ffr-fonts-style mb-3 display-5"><strong>{$docTitle}</strong></h4>
                        <p class="ffr-text ffr-fonts-style mb-3 display-7">{$docDesc}</p>
                        <div class="ffr-section-btn mt-3">
                            <a class="btn btn-primary display-4" href="{$docLink}" target="_blank">{$btnLabel}</a>
                        </div>
                    </div>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="ffr-section content5 {$cidClass}" id="{$id}">
        <div class="container" style="margin-top: 6rem;">
            <div class="row justify-content-center">
                <div class="col-md-12 text-center">
                    <h2 class="ffr-section-title ffr-fonts-style mb-4 display-2 {$textColorClass}">
                        <strong>{$title}</strong>
                    </h2>
                    <p class="ffr-text ffr-fonts-style display-7 text-center {$textColorClass}">
                        {$description}
                    </p>
                </div>
            </div>
            <div class="row mt-4">
                {$cardsHtml}
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderSectionHeader(
        string $id,
        string $title,
        string $subtitle = '',
        string $cidSuffix = '',
        string $bsVersion = '5.1',
        string $containerStyle = 'margin-top: 12rem;',
        string $titleTag = 'h3',
        string $subtitleTag = 'h4'
    ): string {
        $cidClass  = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        $subtitleHtml = $subtitle !== ''
            ? "<{$subtitleTag} class=\"ffr-section-subtitle align-center ffr-fonts-style mb-4 display-7\">{$subtitle}</{$subtitleTag}>"
            : '';

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="content4 {$cidClass}" id="{$id}">
        <div class="container" style="{$containerStyle}">
            <div class="row justify-content-center">
                <div class="title col-md-12 col-lg-10">
                    <{$titleTag} class="ffr-section-title ffr-fonts-style align-center mb-4 display-2">
                        <strong>{$title}</strong>
                    </{$titleTag}>
                    {$subtitleHtml}
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderDownloadList(
        string $id,
        string $title,
        array $downloads,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass  = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        $itemsHtml = '';
        foreach ($downloads as $download) {
            $docTitle = htmlspecialchars($download['title'] ?? '');
            $docDesc  = htmlspecialchars($download['description'] ?? '');
            $docLink  = htmlspecialchars($download['href'] ?? '#');

            $itemsHtml .= <<<HTML
            <div class="row mb-3">
                <div class="col-12 col-md-8">
                    <h5 class="ffr-fonts-style display-7"><strong>{$docTitle}</strong></h5>
                    <p class="ffr-text ffr-fonts-style display-7">{$docDesc}</p>
                </div>
                <div class="col-12 col-md-4 text-center text-md-end mt-3 mt-md-0">
                    <a href="{$docLink}" class="btn btn-primary display-7" target="_blank">
                        <span class="ffr-ffr ffr-ffr-download ffr-iconfont ffr-iconfont-btn"></span>Herunterladen
                    </a>
                </div>
            </div>
            <hr>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="content5 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <h4 class="ffr-section-title ffr-fonts-style mb-4 display-5"><strong>{$title}</strong></h4>
                    <div class="card p-3">
                        <div class="card-body">
                            {$itemsHtml}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderLinkCardGrid(
        string $id,
        string $title,
        array $pages,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        $cardsHtml = '';
        foreach ($pages as $page) {
            $pageTitle = htmlspecialchars($page['title'] ?? '');
            $desc      = htmlspecialchars($page['description'] ?? '');
            $href      = htmlspecialchars($page['href'] ?? '#');
            $button    = htmlspecialchars($page['button'] ?? 'Zur Seite');

            $cardsHtml .= <<<HTML
            <div class="col mb-4">
                <div class="p-3 border rounded h-100 d-flex flex-column">
                    <h5 class="ffr-fonts-style display-7"><strong>{$pageTitle}</strong></h5>
                    <p class="ffr-text ffr-fonts-style display-7 mb-3">{$desc}</p>
                    <div class="text-end mt-auto">
                        <a href="{$href}" class="btn btn-secondary display-7">
                            <span class="ffr-ffr ffr-ffr-right ffr-iconfont ffr-iconfont-btn"></span>{$button}
                        </a>
                    </div>
                </div>
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="content5 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <h4 class="ffr-section-title ffr-fonts-style mb-4 display-5">
                        <strong>{$title}</strong>
                    </h4>
                    <div class="card p-3">
                        <div class="card-body">
                            <div class="row row-cols-1 row-cols-md-2">
                                {$cardsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderAnimatedGallery(
        string $id,
        array $rows,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        $galleryHtml = '';
        foreach ($rows as $index => $images) {
            $rowClass = "grid-container-" . ($index + 1);
            $style = $index === 0 ? 'transform: translate3d(-200px, 0px, 0px);' : 'transform: translate3d(-70px, 0px, 0px);';

            $imgHtml = '';
            foreach ($images as $img) {
                $src = htmlspecialchars($img['src']);
                $alt = htmlspecialchars($img['alt'] ?? '');
                $imgHtml .= <<<HTML
                <div class="grid-item">
                    <img src="{$src}" alt="{$alt}" loading="lazy">
                </div>
    HTML;
            }

            $galleryHtml .= <<<HTML
            <div class="{$rowClass}" style="{$style}">
                {$imgHtml}
            </div>
    HTML;
        }

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="gallery4 {$cidClass}" id="{$id}">
        <div class="container-fluid gallery-wrapper">
            <div class="row justify-content-center">
                <div class="col-12 content-head"></div>
            </div>
            <div class="grid-container">
                {$galleryHtml}
            </div>
        </div>
    </section>
    HTML;
    }

    public function renderCenteredCTA(
        string $id,
        string $title,
        string $buttonLabel,
        string $buttonHref,
        string $cidSuffix = '',
        string $bsVersion = '5.1'
    ): string {
        $cidClass     = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
        $buttonLabel  = htmlspecialchars($buttonLabel);
        $buttonHref   = htmlspecialchars($buttonHref);

        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="header14 {$cidClass}" id="{$id}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="card col-12 col-md-12 col-lg-10">
                    <div class="card-wrapper">
                        <div class="card-box align-center">
                            <h1 class="card-title ffr-fonts-style mb-4 display-2"><strong>{$title}</strong></h1>
                            <div class="ffr-section-btn mt-4">
                                <a class="btn btn-primary display-7" href="{$buttonHref}">{$buttonLabel}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }


    public function renderFullPage(): string
    {
        $head = $this->renderHead();
        $navbar = $this->renderInclude('assets/includes/navbar.php');
        $social = $this->renderInclude('assets/includes/socialFooter.php');
        $footer = $this->renderInclude('assets/includes/footer.php');
        $scripts = $this->renderScriptBundle();

        $content = implode("\n", $this->contentBlocks);
        
        // Automatisches Schema.org Organization Markup, falls noch nicht gesetzt
        if (empty($this->schemaOrgData)) {
            $this->addSchemaOrgData([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => 'Freiwillige Feuerwehr Waldems Reichenbach',
                'url' => $this->canonicalUrl,
                'logo' => "https://{$_SERVER['HTTP_HOST']}/" . ltrim($this->favicon, '/'),
                'description' => $this->description
            ]);
            $head = $this->renderHead(); // Head neu rendern mit Schema.org Daten
        }

        return <<<HTML
            <!DOCTYPE html>
            <html lang="de">
            {$head}
            <body>

              <!-- ░░░ Navigation ░░░ -->
              {$navbar}

              <!-- ░░░ Hauptinhalt ░░░ -->
              {$content}


              <!-- ░░░ Social Footer ░░░ -->
              {$social}

              <!-- ░░░ Footer ░░░ -->
              {$footer}

              <!-- ░░░ JS-Bundle ░░░ -->
              {$scripts}

            </body>
            </html>
            HTML;
    }
}
