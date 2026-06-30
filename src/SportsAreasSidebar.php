<?php

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\ApplicationBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Exception\DataBind;
    use QCubed\Project\Application;
    use QCubed\Type;
    use Exception;

    /**
     * Class SportsAreasSidebar
     *
     * @property string $WrapperClass
     * @property string $NavLabel
     * @property string $TagName
     * @property array $DataSource
     * @property int|null $ActiveSportsAreaId
     * @property int|null $SportsAreaId
     * @property callable $NodeParamsCallback
     *
     * @package QCubed\Plugin
     */
    class SportsAreasSidebar extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        protected string $strWrapperClass = 'nested-sidebar';
        protected string $strNavLabel = 'Sports areas';
        protected string $strTagName = 'ul';

        protected ?int $intActiveSportsAreaId = null;
        protected ?int $intSportsAreaId = null;

        protected mixed $nodeParamsCallback = null;
        protected array $objDataSource = [];
        protected array $strParams = [];

        /**
         * Initializes a new instance of the class, setting the parent object and control ID.
         *
         * @param ControlBase|FormBase $objParentObject The parent object for this control.
         * @param string|null $strControlId Optional control ID for this instance.
         *
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            try {
                parent::__construct($objParentObject, $strControlId);
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
                throw $objExc;
            }

            $this->registerFiles();
        }

        /**
         * Registers required CSS and JavaScript files for the frontend helpers.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/sidebar.css");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/js/sportsareas-sidebar.js"); // Pärast tihendan sportsareas-sidebar.min.js-ks
        }

        /**
         * Validates the current state or attributes of the object.
         *
         * @return bool Returns true if the validation succeeds, otherwise false.
         */
        public function validate(): bool
        {
            return true;
        }

        /**
         * Parses and processes post data from the request.
         *
         * @return void This method does not return any value.
         */
        public function parsePostData(): void
        {}

        /**
         * Sets the node parameters callback function.
         *
         * @param callable $callback The callback function to be set for node parameters.
         *
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Retrieves raw item data based on the provided object and node parameters callback.
         *
         * @param mixed $objItem The input object for which raw data is fetched.
         *
         * @return array Returns an associative array with keys 'id', 'name', and 'is_enabled'.
         *               Each key corresponds to the processed data extracted using the nodeParamsCallback.
         * @throws Exception If the nodeParamsCallback is not provided.
         */
        public function getItemRaw(mixed $objItem): array
        {

            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }
            $params = call_user_func($this->nodeParamsCallback, $objItem);

            return [
                'id' => $params['id'] ?? null,
                'name' => $params['name'] ?? '',
                'is_enabled' => $params['is_enabled'] ?? 2
            ];
        }

        /**
         * Prepares the object for serialization by processing the node parameters callback.
         *
         * @return array Returns an array of properties to be serialized.
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            return parent::sleep();
        }

        /**
         * Handles the wakeup process for the current form and initializes the node parameters callback.
         *
         * @param FormBase $objForm The form object associated with this wakeup process.
         *
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
        }

        /**
         * Returns the name of the jQuery setup function associated with the control.
         *
         * This method provides the identifier for the jQuery-based initialization
         * logic tied to the specific control, allowing it to be properly configured
         * or integrated within a JavaScript environment.
         *
         * @return string The name of the jQuery setup function.
         */
        protected function getJqSetupFunction(): string
        {
            return 'sportsAreasSidebar';
        }

        /**
         * Executes a control command to set up a jQuery widget associated with the current control.
         *
         * @return void
         */
        protected function makeJqWidget(): void
        {
            Application::executeControlCommand(
                $this->ControlId,
                $this->getJqSetupFunction(),
                [],
                ApplicationBase::PRIORITY_HIGH
            );
        }

        /**
         * Generates and returns the HTML output for the control.
         *
         * This method binds data, processes the data source, applies the wrapper class
         * if specified, and composes the final HTML output by rendering content within
         * a div element.
         *
         * @return string The complete HTML string for the control.
         * @throws Caller
         * @throws DataBind
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();

            if (empty($this->objDataSource)) {
                $this->objDataSource = [];
            }

            $this->strParams = [];

            foreach ($this->objDataSource as $objObject) {
                $this->strParams[] = $this->getItemRaw($objObject);
            }

            $attributes = [];

            if ($this->strWrapperClass) {
                $attributes['class'] = $this->strWrapperClass;
            }

            $strOut = $this->renderSportsAreas($this->strParams);
            $strHtml = $this->renderTag('div', $attributes, null, $strOut);

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
         * Renders the HTML markup for a list of sports areas based on the provided parameters.
         *
         * @param array $arrParams An array of parameters where each entry represents a sports area
         *                         with keys 'id' (int), 'name' (string), and 'is_enabled' (int).
         *
         * @return string The generated HTML string representing the sports areas navigation structure.
         */
        protected function renderSportsAreas(array $arrParams): string
        {
            $items = [];

            foreach ($arrParams as $node) {
                $id = isset($node['id']) ? (int)$node['id'] : 0;
                $name = trim((string)($node['name'] ?? ''));
                $isEnabled = isset($node['is_enabled']) ? (int)$node['is_enabled'] : 2;

                if ($id <= 0 || $name === '') {
                    continue;
                }

                if ($isEnabled === 2) {
                    continue;
                }

                $items[] = [
                    'id' => $id,
                    'name' => $name
                ];
            }

            $activeId = $this->intActiveSportsAreaId ?? $this->intSportsAreaId;

            $strHtml = _nl(_indent('<nav class="sidebar-nav" aria-label="' . $this->strNavLabel . '">', 1));
            $strHtml .= _nl(_indent('<' . $this->strTagName . ' class="sidebar-menu" role="list">', 2));

            foreach ($items as $item) {
                $id = (int)$item['id'];
                $name = $item['name'];

                $liClass = 'sidebar-item sports-area-item';

                $linkClass = 'sidebar-link';
                if ($activeId !== null && $id === (int)$activeId) {
                    $linkClass .= ' is-active';
                }

                $ariaCurrent = ($activeId !== null && $id === (int)$activeId)
                    ? ' aria-current="true"'
                    : '';

                $strHtml .= _nl(_indent('<li class="' . $liClass . '">', 3));
                $strHtml .= _nl(_indent(
                    '<a class="' . $linkClass . '" data-sports-area-id="' . $id . '" href="?sports_area_id=' . $id . '"' . $ariaCurrent . '>' . $name . '</a>',
                    4
                ));
                $strHtml .= _nl(_indent('</li>', 3));
            }

            $strHtml .= _nl(_indent('</' . $this->strTagName . '>', 2));
            $strHtml .= _nl(_indent('</nav>', 1));

            return $strHtml;
        }

        /**
         * Refreshes the state of the object by invoking the parent refresh method
         * and performing additional control base refresh operations.
         *
         * @return void No return value.
         */
        public function refresh(): void
        {
            parent::refresh();
            ControlBase::refresh();
        }

        /**
         * Retrieves the value of a specified property.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed Returns the value of the requested property, or attempts to retrieve it from the parent class.
         * @throws Caller If the property does not exist, an exception is thrown after incrementing the offset.
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "WrapperClass": return $this->strWrapperClass;
                case "NavLabel": return $this->strNavLabel;
                case "TagName": return $this->strTagName;
                case "DataSource": return $this->objDataSource;
                case "ActiveSportsAreaId": return $this->intActiveSportsAreaId;
                case "SportsAreaId": return $this->intSportsAreaId;

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
         * Allows the dynamic setting of property values based on the given property name and value.
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
                case "WrapperClass":
                    $this->blnModified = true;
                    $this->strWrapperClass = Type::cast($mixValue, Type::STRING);
                    break;

                case "NavLabel":
                    $this->blnModified = true;
                    $this->strNavLabel = Type::cast($mixValue, Type::STRING);
                    break;

                case "TagName":
                    $this->blnModified = true;
                    $this->strTagName = Type::cast($mixValue, Type::STRING);
                    break;

                case "ActiveSportsAreaId":
                    $this->blnModified = true;
                    $this->intActiveSportsAreaId = $mixValue === null ? null : Type::cast($mixValue, Type::INTEGER);
                    break;

                case "SportsAreaId": // optional: if you want to set it manually from the server as well
                    $this->blnModified = true;
                    $this->intSportsAreaId = $mixValue === null ? null : Type::cast($mixValue, Type::INTEGER);
                    break;

                case "_SportsAreaId": // Internal only. Do not use. Used by JS to track selections.
                    $this->intSportsAreaId = Type::cast($mixValue, Type::INTEGER);
                    $this->intActiveSportsAreaId = $this->intSportsAreaId;
                    $this->blnModified = true;
                    break;

                case "DataSource":
                    $this->objDataSource = $mixValue;
                    $this->blnModified = true;
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }
    }