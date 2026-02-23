<?php

    declare(strict_types = 1);

    namespace QCubed\Plugin;

    use QCubed\ApplicationBase;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Project\Application;

    /**
     * Represents a modal dialog control that extends the Panel control functionality.
     * Provides methods for displaying, hiding, and dynamically setting modal properties.
     *
     * @property string $Title The title of the dialog box.
     * @property bool $HasCloseButton Whether to show the close button in the header. Default is true.
     * @property bool $CloseOnEscape Whether to close the dialog box when the user presses the Escape key. Default is true.
     * @property mixed $Backdrop Whether to show a backdrop behind the dialog box. Default is true.
     * @property string $HeaderClass CSS class for a header (eg "is-primary"). Default null = white header.
     * @property string $HeaderClasses CSS class for a header (eg "is-primary"). Default null = white header.
     *
     * @package QCubed\Plugin
     */
    class Modal extends Panel
    {
        protected bool $blnAutoRender = true;

        public const string SIZE_SM = 'sm';
        public const string SIZE_MD = 'md';
        public const string SIZE_LG = 'lg';

        public const string HEADER_THEME_NONE = 'none';
        public const string HEADER_THEME_PRIMARY = 'primary';
        public const string HEADER_THEME_SUCCESS = 'success';
        public const string HEADER_THEME_WARNING = 'warning';
        public const string HEADER_THEME_DANGER = 'danger';
        public const string HEADER_THEME_INFO = 'info';
        public const string HEADER_THEME_DARK = 'dark';

        protected ?string $strTitle = null;
        protected bool $blnHasCloseButton = true;
        protected bool $blnCloseOnEscape = true;
        protected mixed $mixBackdrop = true;

        protected string $strSize = self::SIZE_MD;

        /** CSS class for a header (eg "is-primary"). Default null = white header. */
        protected ?string $strHeaderClass = null;

        /** Footer align: left|right (default right). */
        protected string $strFooterAlignment = 'right';

        /** Footer container to host QCubed controls (buttons, etc.). */
        protected Panel $pnlFooter;

        /** Optional raw HTML inside footer (before controls). */
        protected ?string $strFooterHtml = null;

        /** Load CSS/JS only once (multiple modals on the page). */
        protected static bool $blnAssetsRegistered = false;

        /**
         * Initializes a new instance of the control with the specified parent object and optional control ID.
         *
         * @param ControlBase|FormBase $objParentObject The parent control or form that owns this control.
         * @param string|null $strControlId An optional ID to assign to the control. If null, an ID will be generated
         *     automatically.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);

            $this->pnlFooter = new Panel($this, $this->ControlId . '_footer');

            $this->applyFooterAlignmentCss();

            $this->registerFiles();
        }

        /**
         * Registers the required CSS and JavaScript files for the modal functionality.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            if (self::$blnAssetsRegistered) {
                return;
            }
            self::$blnAssetsRegistered = true;

            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/qc.modal.css");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/js/qc.modal.js");
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
         * Applies CSS classes to align the footer based on the specified alignment setting.
         *
         * @return void
         */
        protected function applyFooterAlignmentCss(): void
        {
            $this->pnlFooter->removeCssClass('modal__footer--left');
            $this->pnlFooter->removeCssClass('modal__footer--right');

            $this->pnlFooter->addCssClass('modal__footer');
            $this->pnlFooter->addCssClass(
                $this->strFooterAlignment === 'left'
                    ? 'modal__footer--left'
                    : 'modal__footer--right'
            );
        }

        /**
         * Create a footer button and return it so the developer can attach QCubed actions.
         *
         * @param string|null $cssClass Optional extra CSS class(es) for the button (project-specific).
         *
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

            // “Uniform” button styling for our modal (you control the CSS)
            $btn->addCssClass('modal__btn');
            $btn->addCssClass($isPrimary ? 'modal__btn--primary' : 'modal__btn--secondary');

            // Developer project-specific class (could be btn btn-orange etc.)
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
            ?string $cssClass = null
        ): Button {
            return $this->addFooterButton(
                $text,
                $controlId,
                false,
                false,
                null,
                [
                    'type' => 'button',
                    'data-modal-close' => '1',
                ],
                $cssClass
            );
        }

        /**
         * Displays the dialog box by making it visible and executing the associated control command to open it.
         *
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
         * Closes the dialog box associated with the current control.
         *
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
         * Retrieves the jQuery setup function name associated with the modal functionality.
         *
         * @return string The name of the jQuery setup function.
         */
        protected function getJqSetupFunction(): string
        {
            return 'qcModal';
        }

        /**
         * Initializes a jQuery widget for the current control with the specified options.
         *
         * @return void
         */
        protected function makeJqWidget(): void
        {
            $opts = [
                'title' => $this->strTitle,
                'hasCloseButton' => $this->blnHasCloseButton,
                'closeOnEscape' => $this->blnCloseOnEscape,
                'backdrop' => $this->mixBackdrop,
            ];

            Application::executeControlCommand(
                $this->ControlId,
                $this->getJqSetupFunction(),
                $opts,
                ApplicationBase::PRIORITY_HIGH
            );
        }

        /**
         * Generates and returns the HTML markup for the control, including the dialog structure,
         * title, body content, and optional close button.
         *
         * @return string The generated HTML markup for the control.
         * @throws Caller
         */
        public function getControlHtml(): string
        {
            $title = trim((string)$this->strTitle);
            $hasTitle = $title !== '';

            $bodyHtml = $this->Text;

            $closeBtn = '';
            if ($this->blnHasCloseButton) {
                $closeBtn = '<button type="button" class="modal__close" data-modal-close="1" aria-label="Close">×</button>';
            }

            $titleHtml = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hasTitleClass = $hasTitle ? ' has-title' : '';

            $sizeClass = ' modal--' . htmlspecialchars($this->strSize, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $headerClass = $this->strHeaderClass ? ' ' . htmlspecialchars($this->strHeaderClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';

            $footerControlsHtml = $this->pnlFooter->getChildControls()
                ? $this->pnlFooter->render(false)
                : '';

            $footerRawHtml = $this->strFooterHtml ?? '';

            $footerHtml = '';
            if ($footerRawHtml !== '' || $footerControlsHtml !== '') {
                $footerHtml = ($footerRawHtml !== '' ? '<div class="modal__footer modal__footer--raw">' . $footerRawHtml . '</div>' : '') .
                    $footerControlsHtml;
            }

            return sprintf(
                '<dialog id="%s" class="modal%s%s" aria-labelledby="%s-title">' .
                '<div class="modal__surface">' .
                '<header class="modal__header%s">' .
                '<h2 id="%s-title" class="modal__title">%s</h2>' .
                '%s' .
                '</header>' .
                '<div class="modal__body">%s</div>' .
                '%s' .
                '</div>' .
                '</dialog>',
                $this->ControlId,
                $hasTitleClass,
                $sizeClass,
                $this->ControlId,
                $headerClass,
                $this->ControlId,
                $titleHtml,
                $closeBtn,
                $bodyHtml,
                $footerHtml
            );
        }

        /**
         * Sets the CSS class for the header of the current control.
         *
         * @param string|null $class The CSS class (eg "is-success") to be applied to the header. Pass null to remove the class.
         *
         * @return static Returns the current instance for method chaining.
         */
        public function setHeaderClass(?string $class): static
        {
            $this->HeaderClass = $class;
            return $this;
        }

        /**
         * Sets the theme for the header of the modal.
         *
         * @param string $theme The header theme. Must be one of the Modal::HEADER_THEME_* constants.
         *
         * @return static Returns the current instance for method chaining.
         * @throws InvalidCast If an invalid theme is provided.
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
         * Sets the value of a given property dynamically.
         *
         * This method allows setting specific properties of the object such as
         * 'Title', 'HasCloseButton', 'CloseOnEscape', and 'Backdrop'. For unsupported
         * properties, it delegates the handling to the parent class implementation.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
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

                case 'Size':
                    $size = (string)$mixValue;
                    if (!in_array($size, [self::SIZE_SM, self::SIZE_MD, self::SIZE_LG], true)) {
                        throw new InvalidCast('Invalid Size. Use Modal::SIZE_SM, SIZE_MD or SIZE_LG.');
                    }
                    $this->strSize = $size;
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

                default:
                    parent::__set($strName, $mixValue);
            }
        }

        /**
         * Retrieves the value of a property based on the given property name.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property, or the result from the parent::__get() method if the
         *     property is not found.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'Title' => $this->strTitle,
                'HasCloseButton' => $this->blnHasCloseButton,
                'CloseOnEscape' => $this->blnCloseOnEscape,
                'Backdrop' => $this->mixBackdrop,
                'Size' => $this->strSize,
                'HeaderClass', 'HeaderClasses' => $this->strHeaderClass,
                'FooterHtml' => $this->strFooterHtml,
                default => parent::__get($strName),
            };
        }
    }