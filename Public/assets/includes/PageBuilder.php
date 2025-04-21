<?php
class PageBuilder
{
    // ────────── Eigenschaften ──────────
    private string $title;
    private string $description;
    private array $styles = [];
    private array $scripts = [];
    private array $contentBlocks = [];
    private string $favicon = 'assets/images/gravatar-logo-dunkel.jpg';

    // ────────── Konstruktor ──────────
    public function __construct(
        string $title = 'Meine Seite',
        string $description = ''
    ) {
        $this->title = $title;
        $this->description = $description;
        // Standard‑Styles gleich hinzufügen
        $this->styles = [
            'assets/web/assets/mobirise-icons2/mobirise2.css',
            'assets/bootstrap/css/bootstrap.min.css',
            'assets/bootstrap/css/bootstrap-grid.min.css',
            'assets/bootstrap/css/bootstrap-reboot.min.css',
            'assets/parallax/jarallax.css',
            'assets/dropdown/css/style.css',
            'assets/socicon/css/styles.css',
            'assets/theme/css/style.css',
            'assets/css/custom-parallax.css',
            'assets/mobirise/css/mbr-additional.css?v=M1cYSM',
        ];
    }

    // ────────── Öffentliche Helfer ──────────
    public function addStyle(string $href): void
    {
        $this->styles[] = $href;
    }

    public function addScript(string $src): void
    {
        $this->scripts[] = $src;
    }

    public function addContent(string $html): void
    {
        $this->contentBlocks[] = $html;
    }

    public function setFavicon(string $href): void
    {
        $this->favicon = $href;
    }

    // ────────── Head generieren ──────────
    public function renderHead(): string
    {
        // Styles zusammenbauen
        $styleTags = '';
        foreach ($this->styles as $css) {
            $styleTags .= "<link rel=\"stylesheet\" href=\"{$css}\">\n        ";
        }

        // Scripts schon im Head? meistens nicht nötig, aber Option da:
        $scriptTags = '';
        foreach ($this->scripts as $js) {
            $scriptTags .= "<script src=\"{$js}\" defer></script>\n        ";
        }

        return <<<HTML
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
                <link rel="shortcut icon" href="{$this->favicon}" type="image/x-icon">
                <meta name="description" content="{$this->description}">
                <title>{$this->title}</title>

                {$styleTags}
                {$scriptTags}

                <!-- Google‑Fonts Lazy‑Load -->
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
    ): string
    {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';
    
        return <<<HTML
    <section data-bs-version="{$bsVersion}" class="header16 {$cidClass} mbr-fullscreen jarallax" id="{$id}" data-jarallax-speed="{$jarallaxSpeed}">
        <div class="mbr-overlay" style="opacity: {$overlayOpacity}; background-color: {$overlayColor};"></div>
        <div class="container-fluid">
            <div class="row">
                <div class="content-wrap col-12 col-md-12">
                    <h1 class="mbr-section-title mbr-fonts-style mbr-white mb-4 display-1">
                        <strong>{$title}</strong>
                    </h1>
                    <p class="mbr-fonts-style mbr-text mbr-white mb-4 display-7">
                        {$subtitle}
                    </p>
                    <div class="mbr-section-btn">
                        <a class="btn {$btnClass} display-7" href="{$buttonHref}">{$buttonText}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    HTML;
    }
    
    

    /**
     * Liefert einen „Bild + Text“-Teaser‑Abschnitt (Mobirise‑Layout image08)
     *
     * @param string $id          HTML‑ID des Abschnitts (z. B. "image08-h")
     * @param string $title       Hauptüberschrift (h5 → display‑2)
     * @param string $subtitle    Unterüberschrift (h6 → display‑7)
     * @param string $linkHref    URL des Buttons
     * @param string $linkText    Beschriftung des Buttons
     * @param string $imageSrc    Bildquelle
     * @param string $imageAlt    Alt‑Attribut des Bildes
     * @param string $btnClass    optionale zusätzliche Button‑Klasse (z. B. "btn-primary")
     * @return string             Fertiger HTML‑Code
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
        // Bootstrap‑Version fix (Mobirise nutzt i. d. R. 5.1)
        $bsVersion = '5.1';
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="image08 {$cidClass}" id="{$id}">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4">
                            <div class="col-12 col-md-12">
                                <h5 class="mbr-section-title mbr-fonts-style mt-0 mb-4 display-2">
                                    <strong>{$title}</strong>
                                </h5>
                                <h6 class="mbr-section-subtitle mbr-fonts-style mt-0 mb-4 display-7">
                                    {$subtitle}
                                </h6>
                                <div class="mbr-section-btn item-footer mt-3 main-button">
                                    <a href="{$linkHref}" class="btn item-btn {$btnClass} display-7">
                                        {$linkText}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 side-features">
                            <div class="image-wrapper mb-4">
                                <img class="w-100" src="{$imageSrc}" alt="{$imageAlt}">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            HTML;
    }

    /**
     * Erzeugt einen Parallax‑Header‑Banner („header14“)
     *
     * @param string $id           Wert des id‑Attributs z. B. "header14-o"
     * @param string $title        Überschrift (h1)
     * @param string $buttonHref   Ziel‑URL des Call‑to‑Action‑Buttons
     * @param string $buttonText   Button‑Beschriftung
     * @param string $cidSuffix    Teil hinter „cid‑“ für Mobirise‑Klasse (leer = kein cid‑Teil)
     * @param string $btnClass     Bootstrap‑Klasse des Buttons (Default: "btn-primary")
     * @param string $bsVersion    data‑bs‑version (Default: "5.1")
     * @return string              Fertiger HTML‑Code
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
        // „cid‑…“ nur anhängen, wenn gewünscht
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="header14 {$cidClass} mbr-parallax-background" id="{$id}">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="card col-12 col-md-12 col-lg-9">
                            <div class="card-wrapper">
                                <div class="card-box align-center">
                                    <h1 class="card-title mbr-fonts-style mb-4 display-2">
                                        <strong>{$title}</strong>
                                    </h1>
                                    <div class="mbr-section-btn mt-4">
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
     * Rendert einen horizontal scrollenden Galerie‑Streifen („gallery07“)
     *
     * @param string      $id            Abschnitt‑ID, z. B. "gallery07-k"
     * @param array       $images        Liste der Bilder:
     *                                   [
     *                                      ['src' => 'assets/img1.webp', 'alt' => 'Alt‑Text'],
     *                                      …
     *                                   ]
     * @param string      $cidSuffix     Mobirise‑CID‑Suffix (leer, wenn egal)
     * @param string      $gridClass     CSS‑Klasse der Grid‑Spalte (z. B. "grid-container-3")
     * @param string      $movementClass Animations‑Klasse (z. B. "moving-left")
     * @param int         $translateX    Startversatz in px (für Inline‑Style)
     * @param string      $bsVersion     data‑bs‑version (Default: "5.1")
     * @return string                    HTML‑Code der Galerie
     */
    public function renderGalleryGrid(
        string $id,
        array $images,
        string $cidSuffix = '',
        string $gridClass = 'grid-container-3',
        string $movementClass = 'moving-left',
        int $translateX = -200,
        string $bsVersion = '5.1'
    ): string {
        $cidClass = $cidSuffix !== '' ? "cid-{$cidSuffix}" : '';

        // Grid‑Items zusammensetzen
        $itemsHtml = '';
        foreach ($images as $img) {
            $src = $img['src'] ?? '';
            $alt = $img['alt'] ?? '';
            $itemsHtml .= <<<HTML
                            <div class="grid-item">
                                <img src="{$src}" alt="{$alt}">
                            </div>

                HTML;
        }

        return <<<HTML
            <section data-bs-version="{$bsVersion}" class="gallery07 {$cidClass}" id="{$id}">
                <div class="container-fluid gallery-wrapper">
                    <div class="row justify-content-center">
                        <div class="col-12 content-head"></div>
                    </div>
                    <div class="grid-container">
                        <div class="{$gridClass} {$movementClass}" style="transform: translate3d({$translateX}px, 0, 0);">
            {$itemsHtml}            </div>
                    </div>
                </div>
            </section>
            HTML;
    }

    /**
     * Rendert eine Accordion‑Liste (Mobirise list1)
     *
     * @param string $id           Abschnitt‑ID, z. B. "list01-q"
     * @param string $title        Überschrift über dem Accordion
     * @param array  $items        FAQ‑Einträge:
     *                             [
     *                               ['q' => 'Frage 1', 'a' => 'Antwort 1'],
     *                               …
     *                             ]
     * @param string $cidSuffix    Mobirise‑CID‑Suffix (leer lassen, wenn egal)
     * @param string $accordionId  HTML‑ID des Accordion‑Wrappers
     *                             (leer = automatisch "accordion‑{$id}")
     * @param string $bsVersion    Bootstrap‑Version im data‑Attribut
     * @return string              Fertiger HTML‑Block
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
        $panelGroupId = "bootstrap-{$accordionId}";

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
                                        <h6 class="panel-title-edit mbr-semibold mbr-fonts-style mb-0 display-5">
                                            {$question}
                                        </h6>
                                        <span class="sign mbr-iconfont mobi-mbri-arrow-down"></span>
                                    </a>
                                </div>
                                <div id="{$collapseId}" class="panel-collapse noScroll collapse" role="tabpanel"
                                     aria-labelledby="{$headingId}" data-bs-parent="#{$panelGroupId}">
                                    <div class="panel-body">
                                        <p class="mbr-fonts-style panel-text display-7 text-white">
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
                                        <div class="mbr-section-head">
                                            <h4 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
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
     * Liefert alle Standard‑Scripts (Bootstrap‑Bundle, SmoothScroll, Jarallax …)
     * plus – optional – das Inline‑Snippet zur Jarallax‑Initialisierung.
     *
     * @param array $extraScripts    Zusätzliche Skript‑URLs, die ebenfalls eingebunden werden sollen
     * @param bool  $initJarallax    true = Inline‑Init‑Snippet anhängen (Default)
     * @param bool  $defer           true = "defer"‑Attribut setzen, false = weglassen
     * @return string                Zusammenhängender <script>‑Block
     */
    public function renderScriptBundle(
        array $extraScripts = [],
        bool $initJarallax = true,
        bool $defer = true
    ): string {
        // Standard‑Bundle
        $scripts = [
            'assets/bootstrap/js/bootstrap.bundle.min.js',
            'assets/smoothscroll/smooth-scroll.js',
            'assets/ytplayer/index.js',
            'assets/dropdown/js/navbar-dropdown.js',
            'assets/theme/js/script.js',
            'assets/parallax/jarallax.js',
            'assets/scrollgallery/scroll-gallery.js',
        ];

        // Zusätzliche Pfade anfügen (falls übergeben)
        $scripts = array_merge($scripts, $extraScripts);

        // Script‑Tags bauen
        $tags = '';
        foreach ($scripts as $src) {
            $tags .= '<script src="' . $src . '"' . ($defer ? ' defer' : '') . "></script>\n";
        }

        // Jarallax‑Initialisierung anhängen?
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

    public function renderFullPage(): string
    {
        $head = $this->renderHead();
        $navbar = $this->renderInclude('assets/includes/navbar.php');
        $social = $this->renderInclude('assets/includes/socialFooter.php');
        $footer = $this->renderInclude('assets/includes/footer.php');
        $scripts = $this->renderScriptBundle();

        $content = implode("\n", $this->contentBlocks);

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
?>
