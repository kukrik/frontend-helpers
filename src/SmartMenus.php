<?php

    /** This file contains the SmartMenus Class */

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use Exception;
    use QCubed\Exception\DataBind;
    use QCubed\Type;

    /**
     * Class SmartMenus
     *
     * This class represents a component for creating and managing hierarchical menu structures.
     * It extends the ControlBase class and includes functionalities for rendering menu trees,
     * handling node parameters, validating state, and processing POST data.
     *
     * The SmartMenus class allows developers to customize menu properties and render
     * menus dynamically based on input configurations. It supports features such as
     * nested menu structures, dynamic parameter assignment, and asset management for
     * integrating JavaScript and CSS files.
     *
     * @property integer $Id
     * @property integer $ParentId
     * @property integer $Depth
     * @property integer $Left
     * @property integer $Right
     * @property string $MenuText
     * @property integer $Status
     * @property string $RedirectUrl
     * @property integer $HomelyUrl
     * @property string $ExternalUrl
     * @property string $TargetType
     * @property string $NavWrapperClass
     * @property string $WrapperClass
     * @property string $NavLabel
     * @property string $Url The URL to navigate to when the logo is clicked.
     * @property string $LogoPath The path to the logo image file.
     * @property string $AlternateText The alternate text for the logo image.
     * @property string $CloseMenuText
     * @property string $TagName
     * @property string $TagClass
     * @property boolean $MobileView
     *
     * @property string $DataSource
     *
     * @package QCubed\Plugin
     */
    class SmartMenus extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        /** @var string NavWrapperClass */
        protected string $strNavWrapperClass = 'main-nav';
        /** @var string WrapperClass */
        protected string $strWrapperClass = 'nav-inner';
        /** @var string NavLabel */
        protected string $strNavLabel = 'Main menu';
        /** @var null|string Url */
        protected ?string $strUrl = '';
        /** @var null|string */
        protected ?string $strLogoPath = '';
        /** @var null|string */
        protected ?string $strAlternateText = '';
        /** @var string CloseMenuText */
        protected string $strCloseMenuText = 'Close menu';
        /** @var string TagName */
        protected string $strTagName = 'ul';
        /** @var string TagClass */
        protected string $strTagClass = 'main-menu menu sm sm-core';
        /** @var bool MobileView */
        protected bool $blnMobileView = false;

        /** @var  callable */
        protected mixed $nodeParamsCallback = null;
        /** @var array DataSource, from which the items are picked and rendered */
        protected array $objDataSource;

        protected int $intCurrentDepth = 0;
        protected int $intCounter = 0;

        /** @var  null|integer Id */
        protected ?int $intId = null;
        /** @var  null|integer ParentId */
        protected ?int $intParentId = null;
        /** @var  null|integer Depth */
        protected ?int $intDepth = null;
        /** @var  null|integer Left */
        protected ?int $intLeft = null;
        /** @var  null|integer Right */
        protected ?int $intRight = null;
        /** @var  string MenuText */
        protected string $strMenuText;
        /** @var  integer Status */
        protected int $intStatus;
        /** @var string RedirectUrl */
        protected string $strRedirectUrl;
        /** @var int HomelyUrl */
        protected int $intHomelyUrl;
        /** @var string InternalUrl */
        protected string $strExternalUrl;
        /** @var string|null TargetType (e.g. "_blank", "_self") */
        protected ?string $strTargetType = null;

        /**
         * Constructor for initializing the object with a parent object and an optional control ID.
         * Invokes the parent constructor and handles exceptions by incrementing the offset
         * before re-throwing. Also, registers the necessary files during initialization.
         *
         * @param ControlBase|FormBase $objParentObject The parent object that this object is associated with.
         * @param string|null $strControlId An optional control ID for identifying this object.
         *
         * @return void
         * @throws \Exception
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            try {
                parent::__construct($objParentObject, $strControlId);
            } catch (Caller  $objExc) {
                $objExc->incrementOffset();
                throw $objExc;
            }
            $this->registerFiles();
        }

        /**
         * Register required CSS and JavaScript files for the module.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/smartmenus/css/sm-core-css");
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/smartmenus/css/nav.css");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/smartmenus/js/jquery.smartmenus.js");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/smartmenus/js/nav.js");
        }

        /**
         * Validates the current state.
         *
         * @return bool Always returns true.
         */
        public function validate(): bool
        {
            return true;
        }

        /**
         * Parses the incoming POST data for processing.
         *
         * @return void
         */
        public function parsePostData(): void
        {}

        /**
         * Sets the node parameters callback.
         *
         * @param callable $callback The callback to set for node parameters.
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Retrieves raw item parameters using a callback function.
         *
         * @param mixed $objItem The item required to fetch its parameters.
         * @return array The raw parameters of the item including 'id', 'parent_id', 'depth',
         *               'left', 'right', 'menu_text', 'status', 'redirect_url', 'homely_url', 'target_type'.
         * @throws Exception If the nodeParamsCallback is not set.
         */
        public function getItemRaw(mixed $objItem): array
        {
            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }
            $params = call_user_func($this->nodeParamsCallback, $objItem);

            $intId = '';
            if (isset($params['id'])) {
                $intId = $params['id'];
            }
            $intParentId = '';
            if (isset($params['parent_id'])) {
                $intParentId = $params['parent_id'];
            }
            $intDepth = '';
            if (isset($params['depth'])) {
                $intDepth = $params['depth'];
            }
            $intLeft = '';
            if (isset($params['left'])) {
                $intLeft = $params['left'];
            }
            $intRight = '';
            if (isset($params['right'])) {
                $intRight = $params['right'];
            }
            $strMenuText = '';
            if (isset($params['menu_text'])) {
                $strMenuText = $params['menu_text'];
            }
            $intStatus = '';
            if (isset($params['status'])) {
                $intStatus = $params['status'];
            }
            $strRedirectUrl = '';
            if (isset($params['redirect_url'])) {
                $strRedirectUrl = $params['redirect_url'];
            }
            $intHomelyUrl = '';
            if (isset($params['homely_url'])) {
                $intHomelyUrl = $params['homely_url'];
            }
            $strExternalUrl = '';
            if (isset($params['external_url'])) {
                $strExternalUrl = $params['external_url'];
            }
            $strTargetType = '';
            if (isset($params['target_type']) && $params['target_type'] !== '') {
                $strTargetType = $params['target_type'];
            }

            return [
                'id' => $intId,
                'parent_id' => $intParentId,
                'depth' => $intDepth,
                'left' => $intLeft,
                'right' => $intRight,
                'menu_text' => $strMenuText,
                'status' => $intStatus,
                'redirect_url' => $strRedirectUrl,
                'homely_url' => $intHomelyUrl,
                'external_url' => $strExternalUrl,
                'target_type' => $strTargetType
            ];
        }


        /**
         * Generates the HTML for the control by first binding data to the source,
         * processing each data item, rendering a menu tree, and finally wrapping
         * the rendered content in a specified HTML tag.
         *
         * @return string The generated HTML for the control.
         * @throws Caller
         * @throws Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();

            if (empty($this->objDataSource)) {
                $this->objDataSource = [];
            }

            $strParams = [];

            if ($this->objDataSource) {
                foreach ($this->objDataSource as $objObject) {
                    $strParams[] = $this->getItemRaw($objObject);
                }
            }

            $attributes = [];

            if ($this->strNavWrapperClass) {
                $attributes['class'] = $this->strNavWrapperClass;
            }

            if ($this->strNavLabel) {
                $attributes['aria-label'] = $this->strNavLabel;
            }

            $attributes['aria-hidden'] = 'true';

            $strOut = $this->renderMenuTree($strParams);
            $strHtml = $this->renderTag('nav', $attributes, null, $strOut);

            $this->objDataSource = [];
            return $strHtml;
        }

        /**
         * Binds the data to the object by running the DataBinder if the data source is null,
         * the object has a DataBinder and has not been rendered. If the DataBinder call fails,
         * it catches the exception, increments its offset, and rethrows it.
         *
         * @return void
         * @throws Caller
         * @throws DataBind
         */
        public function dataBind(): void
        {
            // Run the DataBinder (if applicable)
            if ($this->hasDataBinder() && !$this->blnRendered) {
                try {
                    $this->callDataBinder();
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
            }
        }

        /**
         * Puts the current object into a sleep state by handling the node parameters callback
         * through the ControlBase's sleepHelper method and then invoking the parent's sleep
         * method.
         *
         * @return array
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            return parent::sleep();
        }

        /**
         * Restores the state of the current object by invoking the parent's wakeup method
         * with the given FormBase object and then calling ControlBase's wakeupHelper method
         * to manage the node parameters callback.
         *
         * @param FormBase $objForm The form object used to restore the state of the current object.
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
        }

        /**
         * Generates an HTML representation of a menu tree based on an array of menu parameters.
         *
         * @param array $arrParams An array containing menu parameters. Each element in the array should be an
         *                         an associative array with the following keys: 'id', 'parent_id', 'depth', 'left',
         *                         'right', 'menu_text', 'status', 'redirect_url', 'homely_url', 'external_url', 'target_type'.
         *
         * @return string HTML string representing the menu tree.
         */
        protected function renderMenuTree(array $arrParams): string
        {
            $strHtml = '';

            ///////////////////////////////////
            // MOBILE NAV drawer (separately, not the “between” button)

            if ($this->blnMobileView === true) {

                $strHtml .= _nl('<div class="mobile-nav-header">');

                if ($this->strLogoPath) {
                    $strHtml .= _nl(_indent('<a class="mobile-logo" href="' . $this->strUrl . '">', 1));
                    $strHtml .= _nl(_indent('<img src="' . $this->strLogoPath . '" alt="' . $this->strAlternateText . '">', 2));
                    $strHtml .= _nl(_indent('</a>', 1));
                }

                $strHtml .= '<button class="mobile-nav-close mobile-nav-toggle" aria-label="' . $this->strCloseMenuText . '">×</button>';
                $strHtml .= _nl('</div>');

            }

            ///////////////////////////////////

            $strHtml .= '<div class="' . $this->strWrapperClass . '">';

            $strHtml .= '<' . $this->strTagName . ' class="' . $this->strTagClass . '">';
            $this->intCurrentDepth = 0;

            for ($i = 0; $i < count($arrParams); $i++) {
                $node = $arrParams[$i];
                $this->intId = $node['id'];
                $this->intParentId = (int)$node['parent_id'];
                $this->intDepth = $node['depth'];
                $this->intLeft = $node['left'];
                $this->intRight = $node['right'];
                $this->strMenuText = $node['menu_text'];
                $this->intStatus = $node['status'];
                $this->strRedirectUrl = $node['redirect_url'];
                $this->intHomelyUrl = (int)$node['homely_url'];
                $this->strExternalUrl = $node['external_url'];
                $this->strTargetType = $node['target_type'] !== null ? (string)$node['target_type'] : null;

                if ($this->intStatus == 2 || $this->intStatus == 3) {
                    continue;
                }

                while ($this->intDepth < $this->intCurrentDepth) {
                    $strHtml .= '</li>' . '</' . $this->strTagName . '>';
                    $this->intCurrentDepth--;
                }

                if ($this->intDepth > $this->intCurrentDepth) {
                    $strHtml .= '<' . $this->strTagName . ' class="submenu">';
                    $this->intCurrentDepth++;
                } else if ($this->intCounter > 0) {
                    $strHtml .= '</li>';
                }

                if ($this->intRight == $this->intLeft + 1) {
                    $strHtml .= '<li id="' . $this->strControlId . '_' . $this->intId . '" class="menu-item">';
                } else {
                    $strHtml .= '<li id="' . $this->strControlId . '_' . $this->intId . '" class="menu-item has-children">';
                }

                $strHtml .= $this->generateMenuItem();
                ++$this->intCounter;
            }

            while ($this->intCurrentDepth > 0) {
                $strHtml .= '</li>' . '</' . $this->strTagName . '>';
                $this->intCurrentDepth--;
            }

            $strHtml .= '</li></' . $this->strTagName . '>';
            $strHtml .= '</div>';

            return $strHtml;
        }

        /**
         * Generates an HTML menu item based on the provided attributes such as target type, URLs, and menu text.
         * It includes an optional dropdown indicator if the menu item has children.
         *
         * @return string The generated HTML string for the menu item.
         */
        private function generateMenuItem(): string
        {
            $target = '';
            if (!empty($this->strTargetType)) {
                $target = ' target="' . $this->strTargetType . '"';
            }

            $link = ($this->intHomelyUrl === 1) ? $this->strRedirectUrl : $this->strExternalUrl;

            $menuItem = '<a href="' . $link . '"' . $target . '>';
            $menuItem .= $this->strMenuText;
            $menuItem .= '</a>';

            return $menuItem;
        }


        /**
         * Magic method to retrieve the value of a requested property.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property, or the result of the parent's __get method if the property
         *               is not found in this class. Throws an exception if the property is invalid or inaccessible.
         * @throws Caller
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "Id": return $this->intId;
                case "ParentId": return $this->intParentId;
                case "Depth": return $this->intDepth;
                case "Left": return $this->intLeft;
                case "Right": return $this->intRight;
                case "MenuText": return $this->strMenuText;
                case "Status": return $this->intStatus;
                case "RedirectUrl": return $this->strRedirectUrl;
                case "HomelyUrl": return $this->intHomelyUrl;
                case "ExternalUrl": return $this->strExternalUrl;
                case "TargetType": return $this->strTargetType;

                case "WrapperClass": return $this->strWrapperClass;
                case "NavLabel": return $this->strNavLabel;
                case "Url": return $this->strUrl;
                case "LogoPath": return $this->strLogoPath;
                case "AlternateText": return $this->strAlternateText;
                case "CloseMenuText": return $this->strCloseMenuText;
                case "TagName": return $this->strTagName;
                case "TagClass": return $this->strTagClass;
                case "MobileView ": return $this->blnMobileView;
                case "DataSource": return $this->objDataSource;

                default:
                    try {
                        return parent::__get($strName);
                    } catch (Caller $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
            }
        }

        /**
         * Sets the value of a property dynamically by the provided property name.
         * This method handles a variety of predefined properties and performs type casting
         * and validation where necessary. If the property is not recognized, it delegates
         * the request to the parent implementation.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to be assigned to the property. The type of the value depends on the
         *     property.
         *
         * @return void
         *
         * @throws InvalidCast If the provided value cannot be cast to the expected type for the property.
         * @throws Caller If the property name is unknown, and the parent handler does not recognize it.
         * @throws \Exception
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case "Id":
                    try {
                        //$this->blnModified = true;
                        $this->intId = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "ParentId":
                    try {
                        $this->blnModified = true;
                        $this->intParentId = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "Depth":
                    try {
                        $this->blnModified = true;
                        $this->intDepth = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "Left":
                    try {
                        $this->blnModified = true;
                        $this->intLeft = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "Right":
                    try {
                        $this->blnModified = true;
                        $this->intRight = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "MenuText":
                    try {
                        $this->blnModified = true;
                        $this->strMenuText = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "Status":
                    try {
                        $this->blnModified = true;
                        $this->intStatus = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "RedirectUrl":
                    try {
                        $this->blnModified = true;
                        $this->strRedirectUrl = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "HomelyUrlUrl":
                    try {
                        $this->blnModified = true;
                        $this->intHomelyUrl = Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "ExternalUrl":
                    try {
                        $this->blnModified = true;
                        $this->strExternalUrl = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "TargetType":
                    try {
                        $this->blnModified = true;
                        $this->strTargetType = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "WrapperClass":
                    try {
                        $this->blnModified = true;
                        $this->strWrapperClass = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "NavLabel":
                    try {
                        $this->blnModified = true;
                        $this->strNavLabel = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
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
                case "CloseMenuText":
                    try {
                        $this->blnModified = true;
                        $this->strCloseMenuText = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "TagName":
                    try {
                        $this->blnModified = true;
                        $this->strTagName = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "TagClass":
                    try {
                        $this->blnModified = true;
                        $this->strTagClass = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "MobileView":
                    try {
                        $this->blnModified = true;
                        $this->blnMobileView = Type::cast($mixValue, Type::BOOLEAN);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;
                case "DataSource":
                    $this->objDataSource = $mixValue;
                    $this->blnModified = true;
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
            }
        }

    }