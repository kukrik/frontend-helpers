<?php

    /** This file contains the NestedSidebar Class */

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\Control\Panel;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Exception\DataBind;
    use Exception;
    use QCubed\Type;

    /**
     * Represents a Nested Sidebar control designed to dynamically create and render
     * hierarchical menu structures. The control supports data binding, customization
     * of node parameters through callbacks, and the generation of HTML trees based
     * on a provided data source.
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
     * @property string $WrapperClass
     * @property string $NavLabel
     * @property string $TagName
     * * @property mixed $DataSource
     *
     * @package QCubed\Plugin
     */
    class NestedSidebar extends Panel
    {
        use Q\Control\DataBinderTrait;

        /** @var string WrapperClass */
        protected string $strWrapperClass = 'nested-sidebar';
        /** @var string SubTagClass */
        protected string $strNavLabel = 'Side menu';
        /** @var string SubTagName */
        protected string $strTagName = 'ul';

        /** @var  callable */
        protected mixed $nodeParamsCallback = null;
        /** @var array DataSource, from which the items are picked and rendered */
        protected array $objDataSource;

        protected array $strParams = [];

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
         * Constructor for initializing the control. It attempts to set up the parent control,
         * register required files, and handle any exceptions that may occur during initialization.
         *
         * @param ControlBase|FormBase $objParentObject The parent object for this control, which can either be a
         *     ControlBase or FormBase instance.
         * @param string|null $strControlId An optional control ID for the current control. If not provided, a default
         *     ID may be assigned.
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
         * Registers necessary CSS and JavaScript files for the application.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/sidebar.css");
            $this->AddJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/js/sidebar.js");
        }

        /**
         * Validates the input or entity.
         *
         * @return bool Returns true if the validation is successful.
         */
        public function validate(): bool
        {
            return true;
        }

        /**
         * Processes and parses the POST data sent during a request. This method is used to handle
         * incoming data from client-side submissions and prepares it for further use or validation
         * within the application.
         *
         * @return void
         */
        public function parsePostData(): void
        {}

        /**
         * Sets the callback function for node parameters.
         *
         * @param callable $callback The callback function to set.
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Retrieves raw item data based on the provided object item.
         *
         * @param mixed $objItem The item object to the process.
         * @return array An associative array containing the processed item data.
         * @throws Exception If the nodeParamsCallback is not provided.
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
         * Generates the HTML for the control by binding data, setting up parameters,
         * rendering menu trees, and returning the final HTML string.
         *
         * @return string The generated HTML of the control.
         * @throws Caller
         * @throws Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();

            if (empty($this->objDataSource)) {
                $this->objDataSource = [];
            }

            $this->strParams = [];

            if ($this->objDataSource) {
                foreach ($this->objDataSource as $objObject) {
                    $this->strParams[] = $this->getItemRaw($objObject);
                }
            }

            if ($this->strWrapperClass) {
                $attributes['class'] = $this->strWrapperClass;
            } else {
                $attributes = '';
            }

            $strOut = $this->renderMenuTree($this->strParams);
            $strHtml = $this->renderTag('div', $attributes, null, $strOut);

            $this->objDataSource = [];

            return $strHtml;
        }

        /**
         * Binds data to a data source if it hasn't been rendered yet, and a DataBinder is provided.
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
         * Prepares the object for serialization by ensuring that the node parameters callback
         * is in a state that can be safely serialized. Additionally, it invokes the parent
         * class's sleep method to handle any further serialization preparation.
         *
         * @return array
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            return parent::sleep();
        }

        /**
         * Restores the object state after deserialization by invoking the parent
         * class's wakeup method and updating the node parameters callback using a
         * helper function.
         *
         * @param FormBase $objForm The form object used to assist in reinitializing the
         *                          node parameters callback during the wakeup process.
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
        }

        /**
         * Renders a hierarchical menu tree structure based on the provided parameters.
         *
         * @param array $arrParams An array of associative arrays containing menu item parameters such as id, parent_id, depth, left, right, menu_text, status, redirect_url, homely_url, and target_type.
         *
         * @return string A string containing the HTML representation of the menu tree.
         */

        protected function renderMenuTree(array $arrParams): string
        {
            $strHtml = '<nav id="' . $this->strControlId . '" class="sidebar-nav" aria-label="' . $this->strNavLabel . '">';

            $strHtml .= '<' . $this->strTagName . ' class="sidebar-menu" role="list"' . '>';
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
                    $strHtml .= '<' . $this->strTagName . ' class="sidebar-submenu">';
                    $this->intCurrentDepth++;
                } else if ($this->intCounter > 0) {
                    $strHtml .= '</li>';
                }

                if ($this->intRight == $this->intLeft + 1) {
                    $strHtml .= '<li id="' . $this->strControlId . '_' . $this->intId . '" class="sidebar-item">';
                } else {
                    $strHtml .= '<li id="' . $this->strControlId . '_' . $this->intId . '" class="sidebar-item has-children">';
                }

                $strHtml .= $this->generateMenuItem();
                ++$this->intCounter;
            }

            while ($this->intCurrentDepth > 0) {
                $strHtml .= '</li>' . '</' . $this->strTagName . '>';
                $this->intCurrentDepth--;
            }

            $strHtml .= '</li></' . $this->strTagName . '>';
            $strHtml .= '</nav>';

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

            $menuItem = '<a class="sidebar-link" href="' . $link . '"' . $target . '>';
            $menuItem .= $this->strMenuText;
            $menuItem .= '</a>';

            return $menuItem;
        }

        /**
         * Retrieves a list of child item IDs based on a specified parent value from the given array of objects.
         * If no value is specified, it will retrieve the top-level items.
         *
         * @param array $objArrays The array of objects with hierarchical data.
         * @param null|mixed $value The parent value to search for in the objects. Default is null.
         *
         * @return array An array of child item IDs matching the specified parent value.
         */
        public function getChildren(array $objArrays, mixed $value = null): array
        {
            $objTempArray = [];
            foreach ($objArrays as $objMenu) {
                if($objMenu->ParentId == $value) {
                    $objTempArray[] = $objMenu->Id;
                    array_push($objTempArray, ...$this->getChildren($objArrays, $objMenu->Id));
                }
            }
            return $objTempArray;
        }

        /**
         * Generated method overrides the built-in Control method, causing it to not redraw completely. We restore
         * its functionality here.
         */
        public function refresh(): void
        {
            parent::refresh();
            ControlBase::refresh();
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
                case "Id":
                    return $this->intId;
                case "ParentId":
                    return $this->intParentId;
                case "Depth":
                    return $this->intDepth;
                case "Left":
                    return $this->intLeft;
                case "Right":
                    return $this->intRight;
                case "MenuText":
                    return $this->strMenuText;
                case "Status":
                    return $this->intStatus;
                case "RedirectUrl":
                    return $this->strRedirectUrl;
                case "ExternalUrl":
                    return $this->strExternalUrl;
                case "WrapperClass":
                    return $this->strWrapperClass;
                case "NavLabel":
                    return $this->strNavLabel;
                case "TagName":
                    return $this->strTagName;
                case "DataSource":
                    return $this->objDataSource;

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
                        $this->blnModified = true;
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
                        $this->strTargetType = $mixValue === null ? null : Type::cast($mixValue, Type::STRING);
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
                case "TagName":
                    try {
                        $this->blnModified = true;
                        $this->strTagName = Type::cast($mixValue, Type::STRING);
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