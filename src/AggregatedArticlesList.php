<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Type;
    use Throwable;

    /**
     * The AggregatedArticlesList class is responsible for managing and rendering
     * a list of aggregated articles. It provides functionality for normalizing
     * data sources, customizing display modes, and generating HTML and JavaScript
     * for the list.
     *
     * Constants:
     * - MODE_STANDARD: Represents the standard display mode.
     * - MODE_YEARS: Represents the year-based display mode.
     *
     * Properties:
     * - $arrDataSource: The data source for the article list.
     * - $strTemplate: The template file used for rendering the list.
     * - $strTagName: The HTML tag name for the wrapper element.
     * - $strWrapperClass: The CSS class for the wrapper element.
     * - $strDisplayMode: The display mode for the list (standard or years).
     * - $intYearsPerPage: The number of years to display per a page in a year-based mode.
     * - $strJavaScripts: The URL of a JavaScript file used for year-based actions.
     *
     * @property array $DataSource The data source for the article list.
     * @property string $Template The template file used for rendering the list.
     * @property string $TagName The HTML tag name for the wrapper element.
     * @property string $WrapperClass The CSS class for the wrapper element.
     * @property string $DisplayMode The display mode for the list (standard or years).
     * @property int $YearsPerPage The number of years to display per a page in a year-based mode.
     * @property string $JavaScripts The URL of a JavaScript file used for year-based actions.
     * @property string $ShowNewerYearsLabel The label for the button to show newer years.
     * @property string $ShowOlderYearsLabel The label for the button to show older years.
     *
     * @package QCubed\Plugin
     */
    class AggregatedArticlesList extends Panel
    {
        public const MODE_STANDARD = 'standard';
        public const MODE_YEARS = 'years';

        protected array $arrDataSource = [];
        protected string $strTemplate = 'AggregatedArticlesList.tpl.php';
        protected string $strTagName = 'div';
        protected string $strWrapperClass = 'aggregated-articles-list-wrapper';
        protected string $strDisplayMode = self::MODE_STANDARD;
        protected int $intYearsPerPage = 5;

        protected string $strShowOlderYearsLabel = 'Show older years';
        protected string $strShowNewerYearsLabel = 'Show newer years';

        protected string $strJavaScripts = FRONTEND_HELPERS_ASSETS_URL . "/js/aggregated-articles-years.min.js";

        /**
         * Processes the data source and generates an array of normalized items.
         * Each item in the data source is processed through the extractRowData method,
         * and only items that result in non-null data are included in the output.
         *
         * @return array An array of normalized data items extracted from the data source.
         */
        public function getNormalizedItems(): array
        {
            if (!$this->arrDataSource) {
                return [];
            }

            $out = [];

            foreach ($this->arrDataSource as $item) {
                $row = $this->extractRowData($item);

                if ($row !== null) {
                    $out[] = $row;
                }
            }

            return $out;
        }

        /**
         * Extracts and processes row data from a mixed input item and returns it in a structured array format.
         * This method handles both array and object inputs, ensuring robust parsing for required fields.
         *
         * @param mixed $item The input data item, which can be an array or an object containing title, title_slug, and year information.
         *
         * @return array|null An associative array containing 'title', 'href', and 'year' keys, or null if required data is missing or invalid.
         */
        protected function extractRowData(mixed $item): ?array
        {
            if (is_array($item)) {
                $title = isset($item['title']) ? trim((string)$item['title']) : '';
                $titleSlug = isset($item['title_slug']) ? trim((string)$item['title_slug']) : '';
                $year = isset($item['year']) && $item['year'] !== '' ? (int)$item['year'] : null;
            } else {
                try {
                    $title = trim((string)$item->Title);
                } catch (Throwable) {
                    $title = '';
                }

                try {
                    $titleSlug = trim((string)$item->TitleSlug);
                } catch (Throwable) {
                    $titleSlug = '';
                }

                try {
                    $year = $item->Year !== null && $item->Year !== ''
                        ? (int)$item->Year
                        : null;
                } catch (Throwable) {
                    $year = null;
                }
            }

            if ($title === '' || $titleSlug === '') {
                return null;
            }

            return [
                'title' => $title,
                'href' => $titleSlug,
                'year' => $year,
            ];
        }

        /**
         * Prepares and returns an array of data based on the current state of the object.
         * Includes display mode, normalized items, and pagination information.
         *
         * @return array The prepared data containing mode, items, and years per a page.
         */
        public function getPreparedData(): array
        {
            return [
                'mode' => $this->strDisplayMode,
                'items' => $this->getNormalizedItems(),
                'years_per_page' => $this->intYearsPerPage,
            ];
        }

        /**
         * Generates and returns the HTML representation of the control.
         * Renders the specified tag with optional attributes, utilizing the class property and inner content.
         *
         * @return string The generated HTML for the control.
         */
        protected function getControlHtml(): string
        {
            return $this->renderTag(
                $this->strTagName,
                $this->strWrapperClass ? ['class' => $this->strWrapperClass] : null,
                null,
                $this->getInnerHtml()
            );
        }

        /**
         * Generates and returns the JavaScript code to be executed at the end of the rendering process.
         * Adds specific initialization scripts for the year-based display mode if applicable.
         *
         * @return string The finalized JavaScript code to be executed on the client-side.
         */
        public function getEndScript(): string
        {
            $str = parent::getEndScript();

            // ainult aastapõhise režiimi puhul
            if ($this->strDisplayMode === self::MODE_YEARS) {
                $str .= sprintf(
                    '
if (window.jQuery && jQuery.fn && jQuery.fn.aggregatedYears) {
    jQuery("#%s").aggregatedYears({limit: %d});
}',
                    $this->ControlId,
                    $this->intYearsPerPage
                );
            }

            return $str;
        }


        /**
         * Retrieves the value of a property dynamically based on its name.
         *
         * This method allows controlled access to certain properties of the class, either returning their values
         * or delegating the request to the parent class if the property does not match predefined cases.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property or the result from the parent __get method.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'TagName' => $this->strTagName,
                'WrapperClass' => $this->strWrapperClass,
                'DataSource' => $this->arrDataSource,
                'DisplayMode' => $this->strDisplayMode,
                'YearsPerPage' => $this->intYearsPerPage,
                'ShowNewerYearsLabel' => $this->strShowNewerYearsLabel,
                'ShowOlderYearsLabel' => $this->strShowOlderYearsLabel,
                default => parent::__get($strName),
            };
        }

        /**
         * Sets the value of the specified property dynamically based on the property name and value provided.
         * Validates and assigns the value to the appropriate property, casting it to the required type.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         *
         * @throws Caller Thrown if an invalid value is assigned to the 'DisplayMode' property.
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

                case 'DataSource':
                    $this->arrDataSource = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;

                case 'DisplayMode':
                    $value = Type::cast($mixValue, Type::STRING);

                    if (!in_array($value, [self::MODE_STANDARD, self::MODE_YEARS], true)) {
                        throw new Caller('Invalid DisplayMode: ' . $value);
                    }

                    $this->strDisplayMode = $value;
                    break;

                case 'YearsPerPage':
                    $value = (int)$mixValue;
                    $this->intYearsPerPage = max(1, $value);
                    break;

                case 'ShowNewerYearsLabel':
                    $this->strShowNewerYearsLabel = Type::cast($mixValue, Type::STRING);
                    break;

                case 'ShowOlderYearsLabel':
                    $this->strShowOlderYearsLabel = Type::cast($mixValue, Type::STRING);
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }