<?php

    namespace QCubed\Plugin;

    use Exception;
    use QCubed\Control\Panel;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Exception\Caller;
    use QCubed\Type;

    /**
     * SharePage is a customizable control extending the Panel class, providing various options
     * for sharing content through multiple channels such as bookmarks, copy links, social media,
     * and more. It allows dynamic rendering of buttons and integration with JavaScript functionalities.
     *
     * @property bool $ShowBookmark Whether to show the bookmark button. Default is true.
     * @property bool $ShowCopy Whether to show the copy link button. Default is true.
     * @property bool $ShowFacebook Whether to show the Facebook button. Default is true.
     * @property bool $ShowX Whether to show the X button. Default is true.
     * @property bool $ShowEmail Whether to show the email button. Default is true.
     * @property bool $ShowPrint Whether to show the print button. Default is true.
     * @property string $ToastPosition Position of the toast message. Default is 'top-center'.
     * @property string $BookmarkText Text for the bookmark button. The default is 'Add to bookmarks'.
     * @property string $CopyText Text for the copy link button. The default is 'Copy link'.
     * @property string $FacebookText Text for the Facebook button. The default is 'Share on Facebook'.
     * @property string $TwitterText Text for the Twitter button. The default is 'Tweet'.
     * @property string $EmailText Text for the email button. The default is 'Send it by email'.
     * @property string $PrintText Text for the print button. The default is 'Print'.
     *
     * @package QCubed\Plugin
     */
    class SharePage extends Panel
    {
        protected bool $blnShowBookmark = false;
        protected bool $blnShowCopy = true;
        protected bool $blnShowFacebook = true;
        protected bool $blnShowX = true;
        protected bool $blnShowEmail = true;
        protected bool $blnShowPrint = true;
        protected string $strToastPosition = 'top-center';
        protected string $strBookmarkText = 'Add to bookmarks';
        protected string $strCopyText = 'Copy link';
        protected string $strFacebookText = 'Share on Facebook';
        protected string $strTwitterText = 'Tweet';
        protected string $strEmailText = 'Send it by email';
        protected string $strPrintText = 'Print';

        /**
         * Constructor for initializing the control.
         *
         * @param ControlBase|FormBase $objParentObject The parent object to which this control belongs.
         * @param string|null $strControlId Optional unique identifier for the control.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);
            $this->registerFiles();
        }

        /**
         * Registers the necessary JavaScript and CSS files for the control.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . '/js/share-page.js');
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . '/css/share-page.css');
        }

        /**
         * Generates the strHtml string for the control with configured options.
         *
         * The method assembles a customizable control containing various features such as
         * bookmark, copy, Facebook integration, and others, based on the specified properties.
         * It also includes JSON-encoded configuration options within the control.
         *
         * @return string The generated strHtml string for the control.
         */
        protected function getControlHtml(): string
        {
            $options = [
                'enableBookmark' => $this->blnShowBookmark,
                'enableCopy' => $this->blnShowCopy,
                'enableFacebook' => $this->blnShowFacebook,
                'enableX' => $this->blnShowX,
                'enableEmail' => $this->blnShowEmail,
                'enablePrint' => $this->blnShowPrint,
                'useNativeShareOnMobile' => true,
                'toastPosition' => $this->strToastPosition
            ];

            $json = htmlspecialchars(json_encode($options, JSON_UNESCAPED_SLASHES), ENT_QUOTES);

            $strHtml = sprintf(
                '<div id="%s" class="page-share" data-share-options="%s">',
                $this->ControlId,
                $json
            );

            if ($this->blnShowBookmark) {
                $strHtml .= _nl(_indent('<button class="share-btn js-add-bookmark" aria-label="' . $this->strBookmarkText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg viewBox="0 0 72 63" class="icon icon-heart" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path d="M64.353 4.265c-7.625-6.5-18.972-5.331-25.972 1.891l-2.744 2.828-2.744-2.828c-6.987-7.222-18.343-8.394-25.972-1.891-8.743 7.46-9.203 20.85-1.378 28.938l26.935 27.812c.822.861 1.962 1.348 3.153 1.348 1.19 0 2.33-.487 3.153-1.348l26.934-27.812c7.838-8.088 7.375-21.478-1.365-28.938Z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            if ($this->blnShowCopy) {
                $strHtml .= _nl(_indent('<button class="share-btn js-copy-link" aria-label="' . $this->strCopyText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg viewBox="0 0 84 84" class="icon icon-link" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path d="M77.761 6.644l-2.897-2.563c-6.708-5.946-17.121-5.319-23.069 1.388l-8.293 9.356c-1.875 2.112-1.678 5.356.434 7.228l.778.691c2.119 1.878 5.35 1.687 7.231-.435l8.294-9.356c1.843-2.072 5.062-2.266 7.141-.431l2.894 2.568c2.074 1.842 2.269 5.063.431 7.141L52.573 42.69c-1.53 1.73-4.06 2.201-6.11 1.138-2.243-1.163-5.012-.648-6.687 1.244l-.141.159c-1.134 1.273-1.615 3.003-1.3 4.678.313 1.688 1.359 3.113 2.881 3.906 6.631 3.474 14.842 1.965 19.803-3.64l18.135-20.46c5.935-6.714 5.308-17.119-1.391-23.071z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('<path d="M39.289 61.169l-.781-.691c-2.105-1.861-5.366-1.663-7.231.437l-8.291 9.354c-1.842 2.07-5.058 2.266-7.137.434l-2.897-2.572c-2.076-1.839-2.271-5.061-.432-7.137l18.135-20.46c1.508-1.694 3.979-2.179 6.015-1.181 2.344 1.15 5.188.594 6.925-1.366l.063-.075c1.116-1.256 1.584-2.928 1.291-4.581-.29-1.644-1.323-3.066-2.797-3.85-6.645-3.579-14.949-2.091-19.938 3.572L4.056 53.866c-5.936 6.713-5.31 17.116 1.387 23.069l2.897 2.569c2.984 2.65 6.84 4.112 10.831 4.106 4.678.008 9.14-1.996 12.241-5.497l8.29-9.356c1.875-2.116 1.688-5.35-.437-7.228z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            if ($this->blnShowFacebook) {
                $strHtml .= _nl(_indent('<button class="share-btn js-share-facebook" aria-label="' .$this->strFacebookText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg class="icon icon-facebook" viewBox="0 0 122 123" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path d="M68.05 74.359h16.241l2.431-18.859H67.05V43.459c0-5.459 1.516-9.178 9.344-9.178l9.984-.006V17.409c-1.725-.228-7.656-.743-14.55-.743-14.397 0-24.253 8.787-24.253 24.928V55.5H31.294v18.859h16.281v48.388h19.475V74.359z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            if ($this->blnShowX) {
                $strHtml .= _nl(_indent('<button class="share-btn js-share-twitter" aria-label="' . $this->strTwitterText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg class="icon icon-x" viewBox="0 0 122 122" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path fill="currentColor" d="M81.716 28.594h10.981L68.588 56.044l28.165 37.237H74.65L57.344 70.653 37.531 93.281H26.55l25.541-29.359-26.972-35.328h22.653l15.634 20.672 18.31-20.672Zm-3.844 58.243h6.087L44.575 34.799h-6.541l39.838 52.038Z"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            if ($this->blnShowEmail) {
                $strHtml .= _nl(_indent('<button class="share-btn js-share-via-email" aria-label="' . $this->strEmailText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg class="icon icon-envelope" viewBox="0 0 122 122" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path d="M21.875 49.841v35.243c0 1.954.697 3.625 2.087 5.016 1.391 1.391 3.063 2.088 5.016 2.088h65.341c1.953 0 3.625-.697 5.015-2.088 1.391-1.391 2.088-3.063 2.088-5.016V49.844c-1.326 1.471-2.833 2.769-4.485 3.862-10.712 7.281-18.065 12.385-22.062 15.313-1.688 1.243-3.056 2.212-4.106 2.906-1.05.697-2.447 1.406-4.194 2.131-1.747.725-3.375 1.088-4.884 1.088h-.088c-1.509 0-3.137-.363-4.881-1.088-1.75-.725-3.147-1.434-4.197-2.128-1.392-.936-2.761-1.905-4.106-2.906-5.032-3.644-12.397-8.75-22.107-15.316-1.629-1.104-3.118-2.401-4.437-3.862zm0-13.05c0 2.337.725 4.572 2.175 6.703 1.456 2.136 3.291 3.986 5.416 5.459l20.775 14.425c.293.21.925.66 1.884 1.353.962.697 1.762 1.26 2.397 1.688.637.431 1.406.909 2.312 1.444.9.531 1.75.931 2.55 1.196.8.269 1.538.4 2.219.4h.088c.681 0 1.421-.131 2.218-.4.8-.265 1.653-.665 2.553-1.197.907-.531 1.675-1.012 2.313-1.443.634-.428 1.431-.991 2.394-1.688l1.887-1.353c2.694-1.894 6.569-4.594 11.628-8.1 3.039-2.1 6.073-4.208 9.1-6.325 1.835-1.244 3.566-2.953 5.194-5.125 1.628-2.178 2.444-4.197 2.444-6.062 0-2.307-.616-4.232-1.844-5.769-1.228-1.541-2.981-2.309-5.259-2.309H28.978c-1.922 0-3.584.696-4.991 2.087-1.406 1.391-2.109 3.063-2.109 5.016z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            if ($this->blnShowPrint) {
                $strHtml .= _nl(_indent('<button class="share-btn js-print" aria-label="' . $this->strPrintText . '">', 1));
                $strHtml .= _nl(_indent('<span class="share-svg-icon">', 2));
                $strHtml .= _nl(_indent('<svg class="icon icon-print" viewBox="0 0 66 63" aria-hidden="true" focusable="false">', 3));
                $strHtml .= _nl(_indent('<path d="M11.547 62.397V46.125c-.31-.016-.572-.05-.822-.05-1.35 0-4.216.016-5.563 0-3.037-.034-5.162-2.525-5.162-6.081V14.056c0-3.556 2.113-6.081 5.147-6.081h54.75c3.081 0 5.178 2.525 5.178 6.131v25.853c0 3.557-2.113 6.063-5.147 6.097-1.584.019-4.7 0-6.372 0v16.319H11.547ZM15.056 29.719v27.99c0 .797.25.916.847.916h33.232c.675 0 .862-.206.862-.984V29.719H15.056ZM56.116 5.134H8.966c-.075-.799-.101-1.603-.079-2.406.016-1.719 1.094-2.728 2.888-2.728h41.406c1.844 0 2.919 1.012 2.935 2.772v2.362Z" fill="currentColor"/>', 4));
                $strHtml .= _nl(_indent('</svg>', 3));
                $strHtml .= _nl(_indent('</span>', 2));
                $strHtml .= _nl(_indent('</button>', 1));
            }

            $strHtml .= '</div>';

            return $strHtml;
        }

        /**
         * Generates and returns the JavaScript code to be executed at the end of the control's rendering process.
         *
         * @return string The JavaScript code appended with an initialization script for FrontendHelpersSharePage, if applicable.
         */
        public function getEndScript(): string
        {
            $str = parent::getEndScript();

            $str .= sprintf('
                if (window.FrontendHelpersSharePage) {
                    window.FrontendHelpersSharePage.init("%s");
                }',
                $this->ControlId
            );

            return $str;
        }

        /**
         * Magic getter method to retrieve the value of a property.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property.
         * @throws Caller If the property does not exist or is inaccessible.
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'ShowBookmark' => $this->blnShowBookmark,
                'ShowCopy' => $this->blnShowCopy,
                'ShowFacebook' => $this->blnShowFacebook,
                'ShowX' => $this->blnShowX,
                'ShowEmail' => $this->blnShowEmail,
                'ShowPrint' => $this->blnShowPrint,
                'ToastPosition' => $this->strToastPosition,
                'BookmarkText' => $this->strBookmarkText,
                'CopyText' => $this->strCopyText,
                'FacebookText' => $this->strFacebookText,
                'TwitterText' => $this->strTwitterText,
                'EmailText' => $this->strEmailText,
                'PrintText' => $this->strPrintText,
                default => parent::__get($strName),
            };
        }

        /**
         * Magic method to set the value of a property dynamically.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         * @throws Exception If the property name is invalid, or the value cannot be properly cast.
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'ShowBookmark':
                    $this->blnShowBookmark = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ShowCopy':
                    $this->blnShowCopy = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ShowFacebook':
                    $this->blnShowFacebook = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ShowX':
                    $this->blnShowX = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ShowEmail':
                    $this->blnShowEmail = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ShowPrint':
                    $this->blnShowPrint = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ToastPosition':
                    $this->strToastPosition = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'BookmarkText':
                    $this->strBookmarkText = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'CopyText':
                    $this->strCopyText = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'FacebookText':
                    $this->strFacebookText = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'TwitterText':
                    $this->strTwitterText = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'EmailText':
                    $this->strEmailText = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'PrintText':
                    $this->strPrintText = Type::Cast($mixValue, Type::STRING);
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }
