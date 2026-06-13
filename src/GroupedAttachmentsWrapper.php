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
     * Class GroupedAttachmentsWrapper
     *
     * Extends the Sortable class and provides functionality for managing and rendering grouped attachments
     * with specific configurations, such as node parameters, control buttons, and input elements.
     * The class supports data binding and tree-based rendering of HTML.
     *
     * @property bool $ActivatedLink
     * @property array $DataSource
     *
     * @package QCubed\Plugin
     */
    class GroupedAttachmentsWrapper extends Sortable
    {
        use Q\Control\DataBinderTrait;

        protected bool $blnActivatedLink = false;
        protected array $objDataSource = [];

        protected mixed $nodeParamsCallback = null;
        protected mixed $buttonParamsCallback = null;

        protected mixed $strRenderButtonHtml = '';

        /**
         * Creates and assigns a callback for node parameters.
         *
         * @param callable $callback The callback function to handle node parameters.
         *
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Creates and assigns a callback for control button parameters.
         *
         * @param callable $callback The callback function to handle control button parameters.
         *
         * @return void
         */
        public function createControlButtons(callable $callback): void
        {
            $this->buttonParamsCallback = $callback;
        }

        /**
         * Retrieves item details or structure based on the provided object.
         *
         * @param mixed $objItem The input object used to retrieve item details.
         *
         * @return array|string Returns an associative array containing item details or a string if applicable.
         * @throws Exception If the nodeParamsCallback is not set.
         */
        public function getItem(mixed $objItem): array|string
        {
            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }

            $params = call_user_func($this->nodeParamsCallback, $objItem);

            return [
                'id'       => $params['id'] ?? '',
                'order'    => $params['order'] ?? '',
                'name'     => $params['name'] ?? '',
                'url'      => $params['url'] ?? '',
                'status'   => $params['status'] ?? '',
                'post_date' => $params['post_date'] ?? '',
                'post_updated_date' => $params['post_updated_date'] ?? '',
                'items'    => $params['items'] ?? [],
            ];
        }

        /**
         * Retrieves button data by processing the provided input through a callback function.
         *
         * @param mixed $objItem The input item data to be processed by the button parameters callback.
         *
         * @return mixed The result of the callback function applied to the input item data.
         *
         * @throws Exception If the buttonParamsCallback is not set.
         */
        public function getButtons(mixed $objItem): mixed
        {
            if (!$this->buttonParamsCallback) {
                throw new Exception("Must provide a buttonParamsCallback");
            }

            return call_user_func($this->buttonParamsCallback, $objItem);
        }

        /**
         * Serializes the current state of the object by preparing callback parameters and delegating to the parent serialization method.
         *
         * @return array An array representing the serialized state of the object.
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            $this->buttonParamsCallback = ControlBase::sleepHelper($this->buttonParamsCallback);

            return parent::sleep();
        }

        /**
         * Initializes the current instance with callbacks derived from the provided form object.
         *
         * @param FormBase $objForm The form object used to retrieve the necessary parameters for initialization.
         *
         * @return void This method does not return a value.
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);

            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
            $this->buttonParamsCallback = ControlBase::wakeupHelper($objForm, $this->buttonParamsCallback);
        }

        /**
         * Generates and returns the HTML string for the control element based on the data source.
         *
         * This method processes the data source to bind data and generate HTML elements,
         * including parameters, buttons, and inputs when applicable. It then composes
         * a rendered HTML output using the provided data and clears the data source upon completion.
         *
         * @return string The fully rendered HTML string for the control element.
         * @throws Caller
         * @throws DataBind
         * @throws \Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();

            $arrParams = [];
            $arrButtons = [];

            if ($this->objDataSource) {
                foreach ($this->objDataSource as $objObject) {
                    $arrParams[] = $this->getItem($objObject);

                    if ($this->buttonParamsCallback) {
                        $arrButtons[] = $this->getButtons($objObject);
                    }
                }
            }

            $strHtml = $this->renderTag(
                'div',
                null,
                null,
                $this->renderTree($arrParams, $arrButtons)
            );

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
         * Renders a tree structure in HTML based on the provided parameters, buttons, and inputs.
         *
         * @param array $arrParams An array of tree node parameters, where each node includes details
         *                         such as 'id', 'status', 'category', 'name', and 'items'.
         * @param array $arrButtons An array of button configurations to be rendered for each node.
         *
         * @return string An HTML string representing the rendered tree structure, including nodes, buttons,
         *                and inputs based on the provided configurations.
         */
        public function renderTree(array $arrParams, array $arrButtons): string
        {
            $strHtml = '';

            for ($i = 0; $i < count($arrParams); $i++) {
                $intId = $arrParams[$i]['id'];
                $intStatus = $arrParams[$i]['status'];
                $strName = $arrParams[$i]['name'];
                $arrItems = $arrParams[$i]['items'];

                $this->strRenderButtonHtml = $arrButtons[$i] ?? '';

                $strCssClass = ($intStatus !== 2) ? 'div-block grouped-attachments-block' : 'div-block grouped-attachments-block inactivated';

                $strHtml .= _nl('<div id="' . $this->strControlId . '_' . $intId . '" data-value="' . $intId . '" class="' . $strCssClass . '">');

                $strHtml .= _nl(_indent('<div class="events grouped-events">',1));
                $strHtml .= _nl(_indent('<span class="icon-set grouped-reorder"><i class="fa fa-bars"></i></span>', 2));

                if ($this->buttonParamsCallback) {
                    $strHtml .= _nl(_indent($this->strRenderButtonHtml, 2));
                }

                $strHtml .= _nl(_indent('</div>', 1));

                $strHtml .= _nl(_indent('<div class="grouped-category">', 1));

                if ($strName) {
                    $strHtml .= _nl(_indent('<span class="category">' . htmlspecialchars($strName) . '</span>', 2));
                }

                $strHtml .= _nl(_indent('</div>',1));

                $strHtml .= _nl(_indent('<div class="grouped-attachments-list">', 1));

                if ($arrItems) {
                    foreach ($arrItems as $arrItem) {
                        $strHtml .= $this->renderAttachmentItem($arrItem);
                    }
                }

                $strHtml .= _nl(_indent('</div>',1));
                $strHtml .= _nl('</div>');
            }

            return $strHtml;
        }

        /**
         * Renders the HTML representation of an attachment item.
         *
         * @param array $arrItem An associative array containing details of the attachment item. Expected keys:
         *                       - 'id' (int|string): The identifier of the attachment item.
         *                       - 'name' (string): The name of the attachment item.
         *                       - 'url' (string): The URL associated with the attachment item.
         *                       - 'status' (int): The status of the attachment item, where a specific value may adjust the CSS class.
         *
         * @return string The generated HTML string for the attachment item.
         */
        protected function renderAttachmentItem(array $arrItem): string
        {
            $intId = $arrItem['id'] ?? '';
            $strName = $arrItem['name'] ?? '';
            $strUrl = $arrItem['url'] ?? '';
            $intStatus = $arrItem['status'] ?? '';
            $dttPostDate = $arrItem['post_date'] ?? '';
            $dttPostUpdatedDate = $arrItem['post_update_date'] ?? '';

            $strHtml = '';
            $strCssClass = ($intStatus !== 2) ? 'grouped-attachment-row' : 'grouped-attachment-row disabled';


            $strHtml .= _nl(_indent('<div class="' . $strCssClass . '" data-value="' . $intId . '">', 2));

            $strHtml .= _nl(_indent('<div class="attachment-info">', 3));

            if ($strUrl) {
                $strHtml .= _nl(_indent('<a class="view-link" href="' . $strUrl . '" target="_blank">', 4));
                $strHtml .= _nl(_indent($strName, 5));
                $strHtml .= _nl(_indent('</a>', 4));
            } else {
                $strHtml .= _nl(_indent($strName, 4));
            }

            $strHtml .= _nl(_indent('</div>', 3));

            $strHtml .= _nl(_indent('<div class="date-info">', 3));
            $strHtml .= _nl(_indent($dttPostDate, 4));
            $strHtml .= _nl(_indent('</div>', 3));

            $strHtml .= _nl(_indent('<div class="date-info">', 3));
            $strHtml .= _nl(_indent($dttPostUpdatedDate, 4));
            $strHtml .= _nl(_indent('</div>', 3));



            $strHtml .= _nl(_indent('</div>', 2));

            return $strHtml;
        }

        /**
         * Refreshes the current state of the object and triggers the parent and base control refresh logic.
         *
         * @return void This method does not return a value.
         */
        public function refresh(): void
        {
            parent::refresh();
            ControlBase::refresh();
        }

        /**
         * Magic method to retrieve the value of a property by its name.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property. Returns specific values for "ActivatedLink" or
         *     "DataSource". For other properties, it attempts to retrieve the value from the parent class.
         *
         * @throws Caller If the property is not found in the parent class, an exception is thrown with an adjusted
         *     call stack offset.
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "ActivatedLink":
                    return $this->blnActivatedLink;

                case "DataSource":
                    return $this->objDataSource;

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
         * Dynamically sets the value of a property based on the provided name and value.
         *
         * @param string $strName The name of the property being set.
         * @param mixed $mixValue The value to assign to the specified property.
         *
         * @return void
         *
         * @throws InvalidCast If the provided $mixValue cannot be cast to the expected type for the property.
         * @throws Caller If an invalid or inaccessible property name is provided.
         * @throws \Exception
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
                    $this->objDataSource = $mixValue ?: [];
                    $this->blnModified = true;
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