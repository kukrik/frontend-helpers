<?php

    declare(strict_types=1);

    namespace QCubed\Plugin;

    use QCubed\ApplicationBase;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Project\Application;
    use QCubed\Project\Control\Button;

    /**
     * Represents a modal dialog control that extends the Panel control functionality.
     * Provides methods for displaying, hiding, and dynamically setting modal properties.
     *
     * @property string|null $Title The title of the dialog box.
     * @property bool $HasCloseButton Whether to show the close button in the header. Default is true.
     * @property bool $CloseOnEscape Whether to close the dialog box when the user presses the Escape key. Default is true.
     * @property mixed $Backdrop Whether to show a backdrop behind the dialog box. Default is true. (true|false|"static")
     *
     * @property string $Placement Placement: top|center|bottom. Default top.
     * @property string $Offset Vertical offset for top/bottom placement (e.g. "2rem", "24px", "10vh").
     *
     * @property string $Size Modal size: sm|md|lg|auto. Default md.
     * @property bool $FullscreenOnMobile Whether the modal becomes fullscreen on small screens.
     * @property string $FullscreenMaxWidth Breakpoint for fullscreen behavior (e.g. "576px").
     *
     * @property string|null $HeaderClass CSS class for a header (alias: HeaderClasses).
     * @property string|null $HeaderClasses CSS class for a header (alias of HeaderClass).
     *
     * @property string|null $FooterHtml Optional HTML for a footer.
     * @property bool $FooterHtmlIsRaw If false, FooterHtml will be escaped (rendered as text). Default true.
     *
     * @package QCubed\Plugin
     */
    class Modal extends Panel
    {
        protected bool $blnAutoRender = true;

        public const SIZE_SM = 'sm';
        public const SIZE_MD = 'md';
        public const SIZE_LG = 'lg';
        public const SIZE_AUTO = 'auto';

        /** Placement: top|center|bottom. Default: top. */
        public const PLACEMENT_TOP = 'top';
        public const PLACEMENT_CENTER = 'center';
        public const PLACEMENT_BOTTOM = 'bottom';

        public const HEADER_THEME_NONE = 'none';
        public const HEADER_THEME_PRIMARY = 'primary';
        public const HEADER_THEME_SUCCESS = 'success';
        public const HEADER_THEME_WARNING = 'warning';
        public const HEADER_THEME_DANGER = 'danger';
        public const HEADER_THEME_INFO = 'info';
        public const HEADER_THEME_DARK = 'dark';

        protected ?string $strTitle = null;
        protected bool $blnHasCloseButton = true;
        protected bool $blnCloseOnEscape = true;
        protected mixed $mixBackdrop = true;

        /** Where to show modal vertically. */
        protected string $strPlacement = self::PLACEMENT_BOTTOM;

        /**
         * Vertical offset for top/bottom placement.
         * Examples: "2rem", "24px", "10vh".
         */
        protected string $strOffset = '2rem';

        /** Modal size: sm|md|lg|auto. Default md. */
        protected string $strSize = self::SIZE_MD;

        /** If true, render a class that turns modal fullscreen on small screens (CSS breakpoint). */
        protected bool $blnFullscreenOnMobile = true;

        /**
         * Breakpoint for fullscreen CSS. Example: "576px".
         * NOTE: we output this as CSS var so a project can tweak per modal.
         */
        protected string $strFullscreenMaxWidth = '576px';

        /** CSS class for a header (eg "is-primary"). Default null = white header. */
        protected ?string $strHeaderClass = null;

        /** Footer align: left|right (default right). */
        protected string $strFooterAlignment = 'right';

        /** Footer container to host QCubed controls (buttons, etc.). */
        protected Panel $pnlFooter;

        /** Optional HTML inside footer (before controls). */
        protected ?string $strFooterHtml = null;

        /** If true, FooterHtml is treated as raw HTML. If false, it will be escaped. */
        protected bool $blnFooterHtmlIsRaw = true;

        /** Load CSS/JS only once (multiple modals on the page). */
        protected static bool $blnAssetsRegistered = false;

        /**
         * @param ControlBase|FormBase $objParentObject
         * @param string|null $strControlId
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);

            $this->UseWrapper = false;

            $this->pnlFooter = new Panel($this);
            $this->pnlFooter->UseWrapper = false;

            $this->applyFooterAlignmentCss();
            $this->registerFiles();
        }

        /**
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            if (self::$blnAssetsRegistered) {
                return;
            }
            self::$blnAssetsRegistered = true;

            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . '/css/qc.modal.css');
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . '/js/qc.modal.min.js');
        }

        /**
         * Set footer alignment. Allowed: 'left' or 'right'. Default is 'right'.
         * @throws InvalidCast
         */
        public function setFooterAlignment(string $alignment): static
        {
            $alignment = strtolower(trim($alignment));
            if (!in_array($alignment, ['left', 'right'], true)) {
                throw new InvalidCast("Invalid footer alignment. Use 'left' or 'right'.");
            }

            $this->strFooterAlignment = $alignment;
            $this->applyFooterAlignmentCss();
            $this->blnModified = true;

            return $this;
        }

        /**
         * @return void
         */
        protected function applyFooterAlignmentCss(): void
        {
            // NOTE:
            // pnlFooter is a technical (non-rendered) container to host QCubed controls.
            // Footer wrapper (with alignment classes) is rendered directly in getControlHtml().
            // This method is intentionally left as a no-op for backward compatibility.
        }

        /**
         * Create a footer button and return it so the developer can attach QCubed actions.
         *
         * @param array $htmlAttributes Optional extra HTML attributes
         * @throws Caller
         */
        public function addFooterButton(
            string $text,
            ?string $controlId = null,
            bool $isPrimary = false,
            bool $causesValidation = true,
            ?string $confirmation = null,
            array $htmlAttributes = [],
            ?string $cssClass = null
        ): Button {
            $btn = new Button($this->pnlFooter, $controlId);
            $btn->Text = $text;
            $btn->CausesValidation = $causesValidation;

            foreach ($htmlAttributes as $k => $v) {
                $btn->setHtmlAttribute((string)$k, (string)$v);
            }

            if ($confirmation) {
                $btn->setHtmlAttribute(
                    'onclick',
                    sprintf('return confirm(%s);', json_encode($confirmation, JSON_UNESCAPED_UNICODE))
                );
            }

            $btn->addCssClass('modal-btn');
            $btn->addCssClass($isPrimary ? 'modal-btn-primary' : 'modal-btn-secondary');

            if ($cssClass) {
                $btn->addCssClass($cssClass);
            }

            return $btn;
        }

        /**
         * Add a standard close button to the footer (does NOT validate and does NOT submit).
         * @throws Caller
         */
        public function addCloseFooterButton(
            string $text = 'Cancel',
            ?string $controlId = null,
            ?string $cssClass = null,
            bool $autofocus = false,
            array $htmlAttributes = []
        ): Button {
            $attrs = array_merge(
                [
                    'type' => 'button',
                    'data-modal-close' => '1',
                ],
                $htmlAttributes
            );

            if ($autofocus) {
                $attrs['autofocus'] = 'autofocus';
            }
            return $this->addFooterButton(
                $text,
                $controlId,
                false,
                false,
                null,
                $attrs,
                $cssClass
            );
        }

        /**
         * Set footer HTML content.
         * By default, $isRaw = true keeps backwards compatibility (treats provided string as raw HTML).
         * Set $isRaw = false to escape the string and render it as text.
         */
        public function setFooterHtml(?string $html, bool $isRaw = true): static
        {
            $this->strFooterHtml = $html;
            $this->blnFooterHtmlIsRaw = $isRaw;
            $this->blnModified = true;
            return $this;
        }

        /**
         * @return void
         */
        public function showDialogBox(): void
        {
            $this->Visible = true;
            $this->Display = true;

            Application::executeControlCommand(
                $this->ControlId,
                $this->getJqSetupFunction(),
                'open',
                ApplicationBase::PRIORITY_LOW
            );
        }

        /**
         * @return void
         */
        public function hideDialogBox(): void
        {
            Application::executeControlCommand(
                $this->ControlId,
                $this->getJqSetupFunction(),
                'close',
                ApplicationBase::PRIORITY_LOW
            );
        }

        /**
         * Return the name of the JavaScript setup function that should get called on this control's HTML object. Returning
         * a value triggers the other jquery widget support.
         *
         * @return string
         */
        protected function getJqSetupFunction(): string
        {
            return 'qcModal';
        }

        /**
         * Attaches the JQueryUI widget to the HTML object if a widget is specified.
         */
        protected function makeJqWidget(): void
        {
            $opts = [
                'title' => $this->strTitle,
                'hasCloseButton' => $this->blnHasCloseButton,
                'closeOnEscape' => $this->blnCloseOnEscape,
                'backdrop' => $this->mixBackdrop,
                'size' => $this->strSize,
                'headerClass' => $this->strHeaderClass,
            ];

            Application::executeControlCommand(
                $this->ControlId,
                $this->getJqSetupFunction(),
                $opts,
                ApplicationBase::PRIORITY_HIGH
            );
        }

        /**
         * @throws Caller
         */
        public function getControlHtml(): string
        {
            $title = trim((string)$this->strTitle);
            $hasTitle = $title !== '';

            $bodyHtml = $this->Text;

            // Auto-render direct child controls (except the technical footer panel) inside the modal body
            $bodyChildrenHtml = '';
            if ($this->AutoRenderChildren) {
                foreach ($this->getChildControls() as $child) {
                    if ($child === $this->pnlFooter) {
                        continue;
                    }
                    $bodyChildrenHtml .= $child->render(false);
                }
            }

            $closeBtn = '';
            if ($this->blnHasCloseButton) {
                $closeBtn = '<button type="button" class="modal-close" data-modal-close="1" aria-label="Close">×</button>';
            }

            $titleHtml = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hasTitleClass = $hasTitle ? ' has-title' : '';
            $hasCloseClass = $this->blnHasCloseButton ? ' has-close-button' : '';

            // Accessibility: if there is no visible title, use aria-label instead of aria-labelledby.
            $ariaAttr = $hasTitle
                ? ' aria-labelledby="' . $this->ControlId . '-title"'
                : ' aria-label="Dialog"';

            $sizeClass = ' modal-' . htmlspecialchars($this->strSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $headerClass = $this->strHeaderClass
                ? ' ' . htmlspecialchars($this->strHeaderClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : '';

            $placementClass = match ($this->strPlacement) {
                self::PLACEMENT_CENTER => ' modal-pos-center',
                self::PLACEMENT_BOTTOM => ' modal-pos-bottom',
                default => ' modal-pos-top',
            };

            $fullscreenClass = $this->blnFullscreenOnMobile ? ' modal-fullscreen' : '';

            $styleParts = [];

            $offset = trim($this->strOffset);
            if ($offset !== '') {
                $safeOffset = htmlspecialchars($offset, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if ($this->strPlacement === self::PLACEMENT_BOTTOM) {
                    $styleParts[] = '--qc-modal-bottom: ' . $safeOffset;
                } elseif ($this->strPlacement === self::PLACEMENT_TOP) {
                    $styleParts[] = '--qc-modal-top: ' . $safeOffset;
                }
            }

            if ($this->blnFullscreenOnMobile && trim($this->strFullscreenMaxWidth) !== '') {
                $styleParts[] = '--qc-modal-fs-max: ' .
                    htmlspecialchars($this->strFullscreenMaxWidth, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            $styleAttr = $styleParts ? ' style="' . implode('; ', $styleParts) . ';"' : '';

            // Header (render only when needed)
            $headerHtml = '';
            if ($hasTitle || $this->blnHasCloseButton) {
                $headerHtml = '<header class="modal-header' . $headerClass . '">' .
                    ($hasTitle ? '<h2 id="' . $this->ControlId . '-title" class="modal-title">' . $titleHtml . '</h2>' : '') .
                    $closeBtn .
                    '</header>';
            }

            // Footer HTML (raw/escaped) + footer controls (children only)
            $footerContent = $this->strFooterHtml ?? '';
            if ($footerContent !== '' && !$this->blnFooterHtmlIsRaw) {
                $footerContent = htmlspecialchars($footerContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            $footerControlsHtml = $this->pnlFooter->getChildControls()
                ? $this->pnlFooter->renderChildren(false)
                : '';

            $footerHtml = '';
            if ($footerContent !== '' || $footerControlsHtml !== '') {
                $alignClass = ($this->strFooterAlignment === 'left') ? 'modal-footer-left' : 'modal-footer-right';
                $footerHtml = '<footer class="modal-footer ' . $alignClass . '">' . $footerContent . $footerControlsHtml . '</footer>';
            }

            return sprintf(
                '<dialog id="%s" class="modal%s%s%s%s%s"%s%s>' .
                '<div class="modal-surface">' .
                '%s' .
                '<div class="modal-body">%s</div>' .
                '%s' .
                '</div>' .
                '</dialog>',
                $this->ControlId,
                $hasTitleClass,
                $hasCloseClass,
                $sizeClass,
                $placementClass,
                $fullscreenClass,
                $ariaAttr,
                $styleAttr,
                $headerHtml,
                $bodyHtml . $bodyChildrenHtml,
                $footerHtml
            );
        }

        /**
         * @param null|string $class
         *
         * @return $this
         */
        public function setHeaderClass(?string $class): static
        {
            $this->HeaderClass = $class; // magic __set handles it
            return $this;
        }

        /**
         * @throws InvalidCast
         */
        public function setHeaderTheme(string $theme): static
        {
            $theme = strtolower(trim($theme));

            $map = [
                self::HEADER_THEME_NONE => null,
                self::HEADER_THEME_PRIMARY => 'is-primary',
                self::HEADER_THEME_SUCCESS => 'is-success',
                self::HEADER_THEME_WARNING => 'is-warning',
                self::HEADER_THEME_DANGER => 'is-danger',
                self::HEADER_THEME_INFO => 'is-info',
                self::HEADER_THEME_DARK => 'is-dark',
            ];

            if (!array_key_exists($theme, $map)) {
                throw new InvalidCast('Invalid header theme. Use Modal::HEADER_THEME_* constants.');
            }

            $this->HeaderClass = $map[$theme];
            return $this;
        }

        /**
         * @throws Caller
         * @throws InvalidCast
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'Title':
                    $this->strTitle = $mixValue !== null ? (string)$mixValue : null;
                    $this->blnModified = true;
                    break;

                case 'HasCloseButton':
                    $this->blnHasCloseButton = (bool)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'CloseOnEscape':
                    $this->blnCloseOnEscape = (bool)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'Backdrop':
                    // true|false|"static"
                    $this->mixBackdrop = $mixValue;
                    $this->blnModified = true;
                    break;

                case 'Placement':
                    $placement = strtolower(trim((string)$mixValue));
                    if (!in_array($placement, [self::PLACEMENT_TOP, self::PLACEMENT_CENTER, self::PLACEMENT_BOTTOM], true)) {
                        throw new InvalidCast("Invalid Placement. Use 'top', 'center' or 'bottom'.");
                    }
                    $this->strPlacement = $placement;
                    $this->blnModified = true;
                    break;

                case 'Offset':
                    $this->strOffset = trim((string)$mixValue);
                    $this->blnModified = true;
                    break;

                case 'Size':
                    $size = strtolower(trim((string)$mixValue));
                    if (!in_array($size, [self::SIZE_SM, self::SIZE_MD, self::SIZE_LG, self::SIZE_AUTO], true)) {
                        throw new InvalidCast('Invalid Size. Use Modal::SIZE_SM, SIZE_MD, SIZE_LG or SIZE_AUTO.');
                    }
                    $this->strSize = $size;
                    $this->blnModified = true;
                    break;

                case 'FullscreenOnMobile':
                    $this->blnFullscreenOnMobile = (bool)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'FullscreenMaxWidth':
                    $this->strFullscreenMaxWidth = trim((string)$mixValue);
                    $this->blnModified = true;
                    break;

                case 'HeaderClass':
                case 'HeaderClasses':
                    $this->strHeaderClass = $mixValue !== null ? (string)$mixValue : null;
                    $this->blnModified = true;
                    break;

                case 'FooterHtml':
                    $this->strFooterHtml = $mixValue !== null ? (string)$mixValue : null;
                    $this->blnModified = true;
                    break;

                case 'FooterHtmlIsRaw':
                    $this->blnFooterHtmlIsRaw = (bool)$mixValue;
                    $this->blnModified = true;
                    break;

                default:
                    parent::__set($strName, $mixValue);
            }
        }

        /**
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'Title' => $this->strTitle,
                'HasCloseButton' => $this->blnHasCloseButton,
                'CloseOnEscape' => $this->blnCloseOnEscape,
                'Backdrop' => $this->mixBackdrop,

                'Placement' => $this->strPlacement,
                'Offset' => $this->strOffset,

                'Size' => $this->strSize,
                'FullscreenOnMobile' => $this->blnFullscreenOnMobile,
                'FullscreenMaxWidth' => $this->strFullscreenMaxWidth,

                'HeaderClass', 'HeaderClasses' => $this->strHeaderClass,
                'FooterHtml' => $this->strFooterHtml,
                'FooterHtmlIsRaw' => $this->blnFooterHtmlIsRaw,

                default => parent::__get($strName),
            };
        }
    }