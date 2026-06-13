<?php

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\DataBind;
    use QCubed\Exception\InvalidCast;
    use Exception;
    use QCubed\Project\Jqui\Sortable;
    use QCubed\Type;

    /**
     * Class SlideWrapper
     *
     * @property boolean $ActivatedLink Default false. If you want to show the link, set the link to true.
     * @property mixed $DataSource
     *
     * @package QCubed\Plugin
     */

    class SortWrapper extends Sortable
    {
        use Q\Control\DataBinderTrait;
        /** @var boolean*/
        protected bool $blnActivatedLink = false;
        /** @var array */
        protected array $objDataSource;
        /** @var  callable */
        protected mixed $nodeParamsCallback = null;
        /** @var  callable */
        protected mixed $cellParamsCallback = null;
        /** @var  callable */
        protected mixed $buttonParamsCallback = null;
        /** @var  callable */
        protected mixed $inputParamsCallback = null;

        protected mixed $strRenderCellHtml;
        protected mixed $strRenderButtonHtml;
        protected mixed $strRenderInputHtml;

        /**
         * Sets the callback function to be used for generating node parameters.
         *
         * @param callable $callback The callback function responsible for creating node parameters.
         *
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Sets a callback function to handle the creation or configuration of control buttons.
         *
         * @param callable $callback The callback function used to define the button parameters or actions.
         *
         * @return void
         */
        public function createControlButtons(callable $callback): void
        {
            $this->buttonParamsCallback = $callback;
        }

        /**
         * Sets a callback used to determine the input parameters for rendering.
         * The callback function will be invoked during the rendering process.
         *
         * @param callable $callback The callback function to be used for generating input parameters.
         *
         * @return void
         */
        public function createRenderInputs(callable $callback): void
        {
            $this->inputParamsCallback = $callback;
        }

        /**
         * Sets a callback function that is used to generate render buttons for each cell.
         *
         * @param callable $callback A callback function that defines how render buttons should be created.
         *
         * @return void
         */
        public function createRenderButtons(callable $callback): void
        {
            $this->cellParamsCallback = $callback;
        }

        /**
         * Uses HTML callback to get each loop in the original array. Relies on the NodeParamsCallback
         * to return information on how to draw each node.
         *
         * @param mixed $objItem
         *
         * @return array|string
         * @throws Exception
         */
        public function getItem(mixed $objItem): array|string
        {
            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }
            $params = call_user_func($this->nodeParamsCallback, $objItem);

            $intId = '';
            if (isset($params['id'])) {
                $intId = $params['id'];
            }
            $intOrder = '';
            if (isset($params['order'])) {
                $intOrder = $params['order'];
            }
            $strCategory = '';
            if (isset($params['category'])) {
                $strCategory = $params['category'];
            }
            $strName = '';
            if (isset($params['name'])) {
                $strName = $params['name'];
            }
            $strUrl = '';
            if (isset($params['url'])) {
                $strUrl = $params['url'];
            }
            $intStatus = '';
            if (isset($params['status'])) {
                $intStatus = $params['status'];
            }

            return [
                'id' => $intId,
                'order' => $intOrder,
                'category' => $strCategory,
                'name' => $strName,
                'url' => $strUrl,
                'status' => $intStatus,
            ];
        }

        /**
         * Calls the cellParamsCallback function to process the provided item and retrieve the associated data.
         *
         * @param mixed $objItem The input item to be processed by the cellParamsCallback function.
         *
         * @return mixed The result returned by the cellParamsCallback function.
         * @throws Exception If the cellParamsCallback function is not set.
         */
        public function getObject(mixed $objItem): mixed
        {
            if (!$this->cellParamsCallback) {
                throw new Exception("Must provide a cellParamsCallback");
            }
            return call_user_func($this->cellParamsCallback, $objItem);
        }

        /**
         * Retrieves button parameters for the provided item using the buttonParamsCallback.
         *
         * @param mixed $objItem The item for which button parameters need to be retrieved.
         *
         * @return mixed Returns the button parameters obtained from the callback.
         * @throws Exception If the buttonParamsCallback is not defined.
         */
        public function getButtons(mixed $objItem): mixed
        {
            if (!$this->buttonParamsCallback) {
                throw new Exception("Must provide a buttonParamsCallback");
            }
            return call_user_func($this->buttonParamsCallback, $objItem);
        }

        /**
         * Processes the provided item using the inputParamsCallback to retrieve input parameters.
         *
         * @param mixed $objItem The item to be processed by the inputParamsCallback.
         *
         * @return mixed The result of the callback execution.
         * @throws Exception If the inputParamsCallback is not provided.
         */
        public function getInput(mixed $objItem): mixed
        {
            if (!$this->inputParamsCallback) {
                throw new Exception("Must provide an inputParamsCallback");
            }
            return call_user_func($this->inputParamsCallback, $objItem);
        }

        /**
         * Prepares the object for serialization by processing callback properties
         * using the sleepHelper method and then invoking the parent's sleep method.
         *
         * @return array An array of object properties to serialize.
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            $this->buttonParamsCallback = ControlBase::sleepHelper($this->buttonParamsCallback);
            $this->cellParamsCallback = ControlBase::sleepHelper($this->cellParamsCallback);
            $this->inputParamsCallback = ControlBase::sleepHelper($this->inputParamsCallback);
            return parent::sleep();
        }

        /**
         * Initializes object properties with callbacks specific to the form's context.
         *
         * @param FormBase $objForm The form instance used for context during the wakeup process.
         *
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
            $this->buttonParamsCallback = ControlBase::wakeupHelper($objForm, $this->buttonParamsCallback);
            $this->cellParamsCallback = ControlBase::wakeupHelper($objForm, $this->cellParamsCallback);
            $this->inputParamsCallback = ControlBase::wakeupHelper($objForm, $this->inputParamsCallback);
        }

        /**
         * Returns the HTML for the control.
         *
         * @return string
         * @throws Caller
         * @throws Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();
            $strParams = [];
            $strButtons = [];
            $strObjects = [];
            $strInputs = [];

            if ($this->objDataSource) {
                foreach ($this->objDataSource as $objObject) {
                    $strParams[] = $this->getItem($objObject);
                    if ($this->cellParamsCallback) {
                        $strObjects[] = $this->getObject($objObject);
                    }
                    if ($this->buttonParamsCallback) {
                        $strButtons[] = $this->getButtons($objObject);
                    }
                    if ($this->inputParamsCallback) {
                        $strInputs[] = $this->getInput($objObject);
                    }
                }
            }

            $strHtml = $this->renderTag('div', null, null, $this->renderTree($strParams, $strObjects, $strButtons, $strInputs));

            $this->objDataSource = [];

            return $strHtml;
        }

        /**
         * Binds the data source to the UI component.
         * If the data source is not set and a data binder is available, it calls the data binder method.
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
         * Renders a hierarchical tree structure based on provided parameters, objects, buttons, and inputs.
         * Utilizes various callbacks to format nodes with specific styles, buttons, and inputs.
         *
         * @param array $arrParams Array containing configuration data for each node, including an id, status, category, name, and url.
         * @param array $arrObjects Array of objects used for rendering additional content in each node's "div-buttons" section.
         * @param array $arrButtons Array of buttons to be included within each node's "events" section if a button callback is defined.
         * @param array $arrInputs Array of input elements to be included in each node's "status-info" section if an input callback is defined.
         *
         * @return string The rendered HTML string representing the hierarchical tree structure.
         */
        public function renderTree(array $arrParams, array $arrObjects, array $arrButtons, array $arrInputs): string
        {
            $strHtml = '';

            for ($i = 0; $i < count($arrParams); $i++) {
                $intId = $arrParams[$i]['id'];
                $intStatus = $arrParams[$i]['status'];
                $strCategory = $arrParams[$i]['category'];
                $strName = $arrParams[$i]['name'];
                $strUrl = $arrParams[$i]['url'];

                if ($this->cellParamsCallback) {
                    $this->strRenderCellHtml = $arrObjects[$i];
                }

                if ($this->buttonParamsCallback) {
                    $this->strRenderButtonHtml = $arrButtons[$i];
                }

                if ($this->inputParamsCallback) {
                    $this->strRenderInputHtml = $arrInputs[$i];
                }

                if ($intStatus !== 2) {
                    $strHtml .= _nl('<div id ="' . $this->strControlId . '_' . $intId . '" data-value="' . $intId . '" class="div-block">');
                } else {
                    $strHtml .= _nl('<div id ="' . $this->strControlId . '_' . $intId . '" data-value="' . $intId . '" class="div-block inactivated">');
                }

                $strHtml .= _nl(_indent('<div class="events">', 1));
                $strHtml .= _nl(_indent('<span class="icon-set reorder"><i class="fa fa-bars"></i></span>', 2));

                if ($this->buttonParamsCallback) {
                    $strHtml .= _nl(_indent($this->strRenderButtonHtml, 2));
                }

                $strHtml .= _nl(_indent('</div>', 1));

                $strHtml .= _nl(_indent('<div class="div-info">', 1));

                if ($strCategory) {
                    $strHtml .= _nl(_indent('<span class="category">' . $strCategory . ' | </span>', 2));
                }

                if (!$this->blnActivatedLink) {
                    $strHtml .= _nl(_indent('<span>' . $strName . '</span>', 2));
                } else {
                    if ($strUrl) {
                        $strHtml .= _nl(_indent('<a class="view-link" href="' . $strUrl . '"  target="_blank" >' . $strName . '</a>', 2));
                    } else {
                        $strHtml .= _nl(_indent('<span>' . $strName . '</span>', 2));
                    }
                }

                $strHtml .= _nl(_indent('</div>', 1));

                if ($this->inputParamsCallback) {
                    $strHtml .= _nl(_indent('<div class="status-info">', 1));
                    $strHtml .= _nl(_indent($this->strRenderInputHtml, 2));
                    $strHtml .= _nl(_indent('</div>', 1));
                }

                if ($this->cellParamsCallback) {
                    $strHtml .= _nl(_indent('<div class="div-buttons">', 1));
                    $strHtml .= _nl(_indent($this->strRenderCellHtml, 2));
                    $strHtml .= _nl(_indent('</div>', 1));
                }
                $strHtml .= _nl('</div>');
            }

            return $strHtml;
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
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "ActivatedLink": return $this->blnActivatedLink;
                case "DataSource": return $this->objDataSource;

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
         * Sets the value of a property dynamically by the provided property name.
         * This method handles a variety of predefined properties and performs type casting
         * and validation where necessary. If the property is not recognized, it delegates
         * the request to the parent implementation.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to be assigned to the property. The type of the value depends on the property.
         *
         * @return void
         *
         * @throws InvalidCast If the provided value cannot be cast to the expected type for the property.
         * @throws Caller If the property name is unknown, and the parent handler does not recognize it.
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case "ActivatedLink":
                    try {
                        $this->blnActivatedLink = Type::cast($mixValue, Type::BOOLEAN);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                case "DataSource":
                    $this->blnModified = true;
                    $this->objDataSource = $mixValue;
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                        break;
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }
    }