<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Type;

    /**
     * MobileBar is a specialized panel control designed for mobile devices.
     * It includes functionality for displaying a logo, managing navigation links,
     * and providing a toggle button for interacting with a mobile menu.
     *
     * Features:
     * - Customizable CSS class.
     * - Configurable logo, URL, alternate text, and toggle button text.
     *
     * @property string $Url The URL to navigate to when the logo is clicked.
     * @property string $LogoPath The path to the logo image file.
     * @property string $AlternateText The alternate text for the logo image.
     * @property string $OpenMenuText The text to display on the toggle button.
     *
     */
    class MobileBar extends Panel
    {
        /** @var null|string */
        protected ?string $strUrl = '';
        /** @var null|string */
        protected ?string $strLogoPath = '';
        /** @var null|string */
        protected ?string $strAlternateText = '';
        /** @var null|string */
        protected ?string $strOpenMenuText = 'Open menu';

        /**
         * Class constructor.
         *
         * @param mixed $objParentObject The parent object with which this control is associated.
         * @param string|null $strControlId The optional control ID, if not passed, an ID will be auto-generated.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(mixed $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);

            $this->registerFiles();
        }

        /**
         * Registers the necessary files for the functionality.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(QCUBED_NESTEDSORTABLE_ASSETS_URL . "/smartmenus/css/nav.css");
        }

        /**
         * Generates the HTML for a control element, including a logo and a toggle button for navigation.
         *
         * @return string The generated HTML string.
         */
        protected function getControlHtml(): string
        {
            $strHtml = '';

            $strHtml .= _nl('<div class="mobile-bar mobile-only">');

            if ($this->strLogoPath) {
                $strHtml .= _nl(_indent('<a class="mobile-logo" href="' . $this->strUrl . '">', 1));
                $strHtml .= _nl(_indent('<img src="' . $this->strLogoPath . '" alt="' . $this->strAlternateText . '">', 2));
                $strHtml .= _nl(_indent('</a>', 1));
            }

            $strHtml .= _nl(_indent('<button class="mobile-nav-toggle"', 1));
            $strHtml .= _nl(_indent('aria-controls="mobile-nav"', 3));
            $strHtml .= _nl(_indent('aria-expanded="false"', 3));
            $strHtml .= _nl(_indent('aria-label="' . $this->strOpenMenuText . '">', 3));
            $strHtml .= _nl(_indent('<span></span><span></span><span></span>', 2));
            $strHtml .= _nl(_indent('</button>', 1));

            $strHtml .= _nl('</div>');

            return $strHtml;
        }

        /**
         * Magic method to retrieve the value of a property dynamically.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property.
         * @throws Caller If the property does not exist or cannot be accessed.
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "Url": return $this->strUrl;
                case "LogoPath": return $this->strLogoPath;
                case "AlternateText": return $this->strAlternateText;
                case "OpenMenuText": return $this->strOpenMenuText;

                default:
                    try {
                        return parent::__get($strName);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }

        /**
         * Magic method for setting property values dynamically.
         *
         * @param string $strName The name of the property being set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         * @throws InvalidCast If the provided value cannot be cast to the appropriate type.
         * @throws Caller If the property name is invalid or cannot be handled by the parent class.
         * @throws \Exception
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case "Url":
                    try {
                        $this->blnModified = true;
                        $this->strUrl = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "LogoPath":
                    try {
                        $this->blnModified = true;
                        $this->strLogoPath = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "AlternateText":
                    try {
                        $this->blnModified = true;
                        $this->strAlternateText = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "OpenMenuText":
                    try {
                        $this->blnModified = true;
                        $this->strOpenMenuText = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;
            }
        }

    }