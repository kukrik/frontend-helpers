<?php

    namespace QCubed\Plugin;

    use QCubed as Q;
    use Exception;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Type;

    /**
     * The FrontResults class extends the Panel and represents a UI component that displays contextual information
     * such as a list of older news items. The class is responsible for rendering, managing data sources, and
     * normalizing data for display.
     *
     * @property string $Title The title of the context section.
     * @property string $EmptyText The text to display when the data source is empty.
     * @property array $DataSource An array of items to display in the context section.
     * @property callable $ItemParamsCallback A callback function that will be used to process item parameters.
     * @property string $TagName The tag name to use for the context section. Default is 'aside'.
     * @property string $WrapperClass The CSS class to apply to the context section wrapper. The default is 'page-context'.
     *
     * @package QCubed\Plugin
     */
    class FrontResults extends Panel
    {
        use Q\Control\DataBinderTrait;

        public array $objDataSource = [];

        protected string $strTemplate = 'FrontResults.tpl.php';

        protected string $strTagName = 'ul';
        protected string $strWrapperClass = 'event-list';

        protected string $strTitle = 'Results';
        protected string $strEmptyText = 'There are currently no results';


        /** @var callable|null */
        protected mixed $itemParamsCallback = null;

        /**
         * Assigns a callable to the item parameters callback and marks the state as modified.
         *
         * @param callable $callback A callable function that will be assigned to the item parameters' callback.
         *
         * @return void
         */
        public function createItemParams(callable $callback): void
        {
            $this->itemParamsCallback = $callback;
            $this->blnModified = true;
        }

        /**
         * Normalizes and filters items from the data source, ensuring each item in the output contains the required
         * fields.
         *
         * The method processes items from the data source, which can be either arrays or objects. If items are arrayed,
         * their structure is checked and required fields are extracted. For object items, a callback is used to
         * determine the parameters. The output is limited to the specified maximum count.
         *
         * @return array The normalized list of items, where each item is an associative array with 'title', 'url', and
         *     optionally 'date'. If the data source is invalid or empty, an empty array is returned.
         * @throws \Exception
         */
        public function getNormalizedItems(): array
        {
            $items = $this->DataSource ?? [];
            if (!is_array($items) || !$items) {
                return [];
            }

            $out = [];

            foreach ($items as $item) {
                if (is_array($item)) {
                    $title = isset($item['title']) ? (string)$item['title'] : '';
                    $url = isset($item['url']) ? (string)$item['url'] : '';
                    $date = isset($item['date']) ? (string)$item['date'] : null;

                    if ($title !== '' && $url !== '') {
                        $out[] = ['title' => $title, 'url' => $url, 'date' => $date ?: null];
                    }
                    continue;
                }

                if (!$this->itemParamsCallback) {
                    throw new Exception('FrontResults: itemParamsCallback is required when DataSource contains objects.');
                }

                $params = call_user_func($this->itemParamsCallback, $item);
                if (!is_array($params)) {
                    continue;
                }

                $title = isset($params['title']) ? (string)$params['title'] : '';
                $url = isset($params['url']) ? (string)$params['url'] : '';
                $date = isset($params['date']) ? (string)$params['date'] : null;

                if ($title !== '' && $url !== '') {
                    $out[] = ['title' => $title, 'url' => $url, 'date' => $date ?: null];
                }
            }

            return $out;
        }

        /**
         * Generates the HTML output for the control.
         *
         * The method handles data binding, processes the data source to build parameters, and renders
         * the control's HTML tag with its inner content.
         *
         * @return string The rendered HTML for the control.
         * @throws Caller
         */
        protected function getControlHtml(): string
        {
           $this->dataBind();

            if (empty($this->objDataSource)) {
                $this->objDataSource = [];
            }

            return $this->renderTag(
                $this->strTagName,
                $this->strWrapperClass ? ['class' => $this->strWrapperClass] : null,
                null,
                $this->getInnerHtml()
            );
        }


        /**
         * Binds data to the control by executing the DataBinder, if available.
         *
         * This method checks if a DataBinder is set and whether the control has not yet been rendered.
         * If those conditions are satisfied, it invokes the DataBinder.
         * Any exceptions thrown during the process are caught, their stack trace offset is incremented,
         * and they are rethrown for further handling.
         *
         * @return void
         * @throws Caller If an error occurs while calling the DataBinder.
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
         * Retrieves the value of a property dynamically based on its name.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property if it exists, or the result of the parent's __get method
         *     otherwise.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'TagName' => $this->strTagName,
                'WrapperClass' => $this->strWrapperClass,
                'Title' => $this->strTitle,
                'EmptyText' => $this->strEmptyText,
                'DataSource' =>$this->objDataSource,
                default => parent::__get($strName),
            };
        }

        /**
         * Sets the value of a property dynamically based on its name.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         * @throws Caller
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'TagName':
                    $this->strTagName = Type::cast($mixValue, Type::STRING);
                    break;
                case 'WrapperClass':
                    $this->strWrapperClass = Type::cast($mixValue, Type::STRING);
                    break;
                case 'Title':
                    $this->strTitle = Type::cast($mixValue, Type::STRING);
                    break;
                case 'EmptyText':
                    $this->strEmptyText = Type::cast($mixValue, Type::STRING);
                    break;
                case "DataSource":
                    $this->objDataSource = $mixValue;
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }

/**
 * EXAMPLE: FrontResults integration into the frontend
 *
 * $frontResults = new \QCubed\Plugin\FrontResults($this);
 * $frontResults->Title = 'Results';
 * $frontResults->EmptyText= 'There are currently no results.';
 *
 *
 */