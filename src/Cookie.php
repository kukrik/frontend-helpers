<?php

    namespace QCubed\Plugin;

    use QCubed\ApplicationBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Project\Application;
    use QCubed\Type;


    /**
     * Class Cookie represents a UI control for displaying a cookie consent banner.
     * The class facilitates the rendering of cookie-related information and user interaction
     * regarding consent preferences. It also allows configuration of various properties
     * such as button texts, cookie lifetime, and consent storage parameters.
     *
     * @property string $WrapperClass The CSS class to apply to the wrapper div element.
     * @property string $GoogleId The Google Analytics ID used for tracking purposes.
     * @property int $LifeTime The lifetime of the consent cookie in days.
     * @property string $ConsentCookieName The name of the consent cookie.
     * @property string $CookieInfoText The text to display in the cookie information section.
     * @property string $AcceptButtonText The text to display on the "Accept" button.
     * @property string $OnlyAecessaryButtonText The text to display on the "Only necessary" button.
     * @package QCubed\Plugin
     */
    class Cookie  extends Panel
    {
        /** @var string */
        protected string $strWrapperClass = 'cookie-banner';
        /** @var string|null */
        protected ?string $strGoogleId = null;
        /** @var int|null */
        protected ?int $intLifeTime = null;
        /** @var string|null */
        protected ?string $strConsentCookieName = null;
        /** @var string|null */
        protected ?string $strCookieInfoText = null;
        /** @var string|null */
        protected ?string $strAcceptButtonText = null;
        /** @var string|null */
        protected ?string $strOnlyAecessaryButtonText = null;

        /**
         * Constructor method for initializing the object.
         *
         * @param ControlBase|FormBase $objParentObject The parent object to which this control belongs.
         * @param string|null $strControlId The optional control ID.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);
            $this->registerFiles();
            //$this->UseWrapper = false;
        }

        /**
         * Registers the CSS and JavaScript files required for the functionality.
         * This method adds the specified files to the appropriate inclusion lists.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/qc.cookie.css");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . '/js/qc.cookie.min.js');
        }

        /**
         * Generates and returns the HTML for the control, including cookie consent information
         * and action buttons for user interaction.
         *
         * The method dynamically builds a wrapper `<div>` element, optionally assigning
         * an ID if the `strWrapperClass` property is set. It includes a description of cookies'
         * usage and provides buttons for users to indicate their consent.
         *
         * @return string The complete HTML string for the cookie consent control, wrapped inside a `<div>` tag.
         */
        protected function getControlHtml(): string
        {
            if ($this->strWrapperClass) {
                $attributes['class'] = $this->strWrapperClass;
            } else {
                $attributes = '';
            }

            $strOut = _nl(_indent('<span>', 1));
            $strOut .= _nl(_indent($this->strCookieInfoText, 2));
            $strOut .= _nl(_indent('</span>', 1));

            $strOut .= _nl(_indent('<div class="cookie-actions">', 1));

            $strOut .= _nl(_indent('<button class="cookie-button" data-cookie-consent="all">' . $this->strAcceptButtonText . '</button>', 2));
            $strOut .= _nl(_indent('<button class="cookie-button" data-cookie-consent="only-necessary">' . $this->strOnlyAecessaryButtonText . '</button>', 2));
            $strOut .= _nl(_indent('</div>', 1));

            return $this->renderTag('div', $attributes, null, $strOut);
        }

        /**
         * Builds an array of options specific to the jQuery configuration.
         * This method extends the parent implementation and adds additional key-value pairs
         * based on the object's properties such as GoogleId, LifeTime, and ConsentCookieName.
         *
         * @return array An array of options for jQuery, optionally including googleId, lifeTime, and consentCookieName
         *               if their respective properties are not null.
         */
        protected function makeJqOptions(): array
        {
            $jqOptions = parent::makeJqOptions();
            if (!is_null($val = $this->GoogleId)) {$jqOptions['googleId'] = $val;}
            if (!is_null($val = $this->LifeTime)) {$jqOptions['lifeTime'] = $val;}
            if (!is_null($val = $this->ConsentCookieName)) {$jqOptions['consentCookieName'] = $val;}
            return $jqOptions;
        }

        /**
         * Retrieves the jQuery setup function name.
         *
         * @return string The name of the jQuery setup function.
         */
        protected function getJqSetupFunction(): string
        {
            return 'qcCookie';
        }

        /**
         * Magic method to retrieve the value of a property.
         *
         * @param string $strName The name of the property being accessed.
         *
         * @return mixed The value associated with the requested property, or the value handled by the parent class if the property is not explicitly defined.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'WrapperClass' => $this->strWrapperClass,
                'GoogleId' => $this->strGoogleId,
                'LifeTime' => $this->intLifeTime,
                'ConsentCookieName' => $this->strConsentCookieName,
                'CookieInfoText' => $this->strCookieInfoText,
                'AcceptButtonText' => $this->strAcceptButtonText,
                'OnlyAecessaryButtonText' => $this->strOnlyAecessaryButtonText,

                default => parent::__get($strName),
            };
        }

        /**
         * Magic method to set the value of a property.
         *
         * @param string $strName The name of the property being modified.
         * @param mixed $mixValue The new value to assign to the property.
         *
         * @return void
         * @throws InvalidCast If the provided value cannot be cast to the required type.
         * @throws Caller If the property is not explicitly defined and cannot be handled by the parent class.
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'WrapperClass':
                    try {
                        $this->blnModified = true;
                        $this->strWrapperClass = Type::Cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case 'CookieInfoText':
                    try {
                        $this->blnModified = true;
                        $this->strCookieInfoText = Type::Cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                    case 'AcceptButtonText':
                        try {
                            $this->blnModified = true;
                            $this->strAcceptButtonText = Type::Cast($mixValue, Type::STRING);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case 'OnlyAecessaryButtonText':
                        try {
                            $this->blnModified = true;
                            $this->strOnlyAecessaryButtonText = Type::Cast($mixValue, Type::STRING);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                        }
                        break;

                case 'GoogleId':
                    $this->strGoogleId = $mixValue;
                    $this->addAttributeScript($this->getJqSetupFunction(), 'option', 'googleId', $mixValue);
                    break;

                case 'LifeTime':
                    $this->intLifeTime = $mixValue;
                    $this->addAttributeScript($this->getJqSetupFunction(), 'option', 'lifeTime', $mixValue);
                    break;

                case 'ConsentCookieName':
                    $this->strConsentCookieName = $mixValue;
                    $this->addAttributeScript($this->getJqSetupFunction(), 'option', 'consentCookieName', $mixValue);
                    break;

                default:
                    parent::__set($strName, $mixValue);
            }
        }
    }