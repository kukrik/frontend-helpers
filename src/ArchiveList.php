<?php

    /** This file contains the ArchiveList Class */

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\ApplicationBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Exception\DataBind;
    use Exception;
    use QCubed\Project\Application;
    use QCubed\Type;

    /**
     * Class archiveList
     *
     * @property string $TagName
     * @property string $WrapperClass
     * @property mixed $DataSource
     *
     * @package QCubed\Plugin
     */
    class ArchiveList extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        /** @var string TagName */
        protected string $strTagName = 'ul';
        /** @var string WrapperClass */
        protected string $strWrapperClass = 'archive-list';

        /** @var  callable */
        protected mixed $nodeParamsCallback = null;
        /** @var array DataSource, from which the items are picked and rendered */
        protected array $objDataSource;
        /** @var array Raw item data, as returned by getItemRaw() */
        protected array $strParams = [];

        /**
         * Constructor method to initialize the control with a parent object and an optional control ID.
         * It invokes the parent constructor and handles any exceptions, ensuring proper file registration.
         *
         * @param ControlBase|FormBase $objParentObject The parent object to which this control belongs.
         * @param string|null $strControlId An optional string identifier for the control.
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
         * Registers necessary CSS and JavaScript files.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/archive-list.css");
        }

        /**
         * Validates the given input data.
         *
         * @return bool Always returns true, indicating the validation is successful.
         */
        public function validate(): bool
        {
            return true;
        }

        /**
         * Parses the incoming POST data and processes it according to the application's requirements.
         *
         * @return void
         */
        public function parsePostData(): void
        {}

        /**
         * Sets the callback function for node parameters.
         *
         * @param callable $callback The callback function to assign for node parameters.
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Retrieves raw item data by processing the provided object through the configured nodeParamsCallback.
         * Extracts and returns the 'year' and 'status' information from the callback result.
         *
         * @param mixed $objItem The input object to the process using the nodeParamsCallback.
         *
         * @return array An associative array containing 'year' and 'status' keys with their corresponding values.
         * @throws Exception If the nodeParamsCallback is not defined.
         */
        public function getItemRaw(mixed $objItem): array
        {
            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }
            $params = call_user_func($this->nodeParamsCallback, $objItem);

            $calDate = '';
            if (isset($params['date'])) {
                $calDate = $params['date'];
            }
            $strTitle = '';
            if (isset($params['title'])) {
                $strTitle = $params['title'];
            }
            $strUrl = '';
            if (isset($params['url'])) {
                $strUrl = $params['url'];
            }
            $strChange = '';
            if (isset($params['change'])) {
                $strChange = $params['change'];
            }
            $intStatus = '';
            if (isset($params['status'])) {
                $intStatus = $params['status'];
            }

            return [
                'date' => $calDate,
                'title' => $strTitle,
                'url' => $strUrl,
                'change' => $strChange,
                'status' => $intStatus
            ];
        }

        /**
         * Prepares the object for serialization by updating the nodeParamsCallback
         * with the serialized version returned by the sleepHelper method.
         *
         * @return array
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            return parent::sleep();
        }

        /**
         * Restores the object state after deserialization. It updates the
         * nodeParamsCallback using the wakeupHelper method with the provided form object.
         *
         * @param FormBase $objForm The form object used to restore the state.
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
        }

        /**
         * Generates and returns the HTML for the control. It binds data, processes the data source,
         * and constructs the HTML by rendering the menu tree and wrapping it in the appropriate tag.
         *
         * @return string The resulting HTML of the control.
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

            $strOut = $this->renderList($this->strParams);
            $strHtml = $this->renderTag($this->strTagName, $attributes, null, $strOut);

            $this->objDataSource = [];

            return $strHtml;
        }

        /**
         * Binds data to the object by calling the data binder method if the object
         * is not already rendered, there is no data source already present, and
         * a data binder is defined. If an exception occurs during the binding process,
         * the exception offset is incremented before being thrown.
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
         * Renders an HTML list based on the given parameters.
         *
         * @param array $arrParams An array of data where each element represents an item with the following keys:
         *                         - 'date': The date associated with the item.
         *                         - 'title': The title of the item.
         *                         - 'url': The URL for the item link.
         *                         - 'change': Additional change details, if available.
         *                         - 'status': The status of the item; items with statuses 2 or 3 are skipped.
         *
         * @return string The generated HTML string for the list.
         */
        protected function renderList(array $arrParams): string
        {
            $strHtml = '';

            // Let's start the walkthrough
            for ($i = 0; $i < count($arrParams); $i++) {
                $calDate = $arrParams[$i]['date'];
                $strTitle = $arrParams[$i]['title'];
                $strUrl = $arrParams[$i]['url'];
                $strChange = $arrParams[$i]['change'];
                $intStatus = $arrParams[$i]['status'];

                if ($intStatus === 2 || $intStatus === 3) {
                    continue;
                }

                $strHtml .= _nl(_indent('<li>', 1));
                $strHtml .= _nl(_indent('<a href="' . $strUrl . '">',2));
                $strHtml .= '<span class="col-date">' . $calDate . '</span>';

                if (!$strChange) {
                    $strHtml .= '<span class="col-content">' . $strTitle . '</span>';
                } else {
                    $strHtml .= '<span class="col-content">' . $strTitle . '<span class="col-change">' . $strChange . '</span></span>';
                }

                $strHtml .= _nl(_indent('</a>',2));
                $strHtml .= _nl(_indent('</li>', 1));
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
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "WrapperClass": return $this->strWrapperClass;
                case "TagName": return $this->strTagName;
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
                case "WrapperClass":
                    try {
                        $this->blnModified = true;
                        $this->strWrapperClass = Type::cast($mixValue, Type::STRING);
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