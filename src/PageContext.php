<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Type;

    /**
     * The PageContext class extends the Panel and represents a UI component that displays contextual information
     * such as a list of older news items. The class is responsible for rendering, managing data sources, and
     * normalizing data for display.
     *
     * @property string $Title The title of the context section.
     * @property string $EmptyText The text to display when the data source is empty.
     * @property array $DataSource An array of items to display in the context section.
     * @property string $TagName The tag name to use for the context section. Default is 'aside'.
     * @property string $WrapperClass The CSS class to apply to the context section wrapper. The default is 'page-context'.
     * @package QCubed\Plugin
     */
    class PageContext extends Panel
    {
        public array $DataSource = [];

        protected string $strTemplate = 'PageContext.tpl.php';

        protected string $strTagName = 'aside';
        protected string $strWrapperClass = 'page-context';

        protected string $strTitle = 'Older news';
        protected string $strEmptyText = 'There are currently no older news items';

        /**
         * Normalizes and filters items from the data source.
         *
         * Expected item structure:
         * [
         *     'title' => 'Some title',
         *     'url'   => '/some-url',
         *     'date'  => '09.03.2026' // optional
         * ]
         *
         * @return array
         */
        public function getNormalizedItems(): array
        {
            $items = $this->DataSource ?? [];
            if (!is_array($items) || !$items) {
                return [];
            }

            $out = [];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $title = isset($item['title']) ? (string)$item['title'] : '';
                $url = isset($item['url']) ? (string)$item['url'] : '';
                $date = isset($item['date']) ? (string)$item['date'] : null;

                if ($title !== '' && $url !== '') {
                    $out[] = [
                        'title' => $title,
                        'url' => $url,
                        'date' => $date ?: null,
                    ];
                }
            }

            return $out;
        }

        /**
         * Generates and returns the HTML for the control by rendering a tag with specified attributes and inner content.
         *
         * @return string
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
         * Retrieves the value of a property dynamically based on its name.
         *
         * @param string $strName
         * @return mixed
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'TagName' => $this->strTagName,
                'WrapperClass' => $this->strWrapperClass,
                'Title' => $this->strTitle,
                'EmptyText' => $this->strEmptyText,
                'DataSource' => $this->DataSource,
                default => parent::__get($strName),
            };
        }

        /**
         * Sets the value of a property dynamically based on its name.
         *
         * @param string $strName
         * @param mixed $mixValue
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
                case 'DataSource':
                    $this->DataSource = Type::cast($mixValue, Type::ARRAY_TYPE);
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }

    /**
     * EXAMPLE: Context integration into the frontend
     *
     * $pageContext = new \QCubed\Plugin\PageContext($this);
     * $pageContext->Title = 'Older news';
     *
     * $pageContext->DataSource = array_map(
     *     function ($n) {
     *         return [
     *             'title' => $n->getTitle(),
     *             'url'   => $n->getTitleSlug(),
     *             'date'  => $n->getPostDate() ? $n->getPostDate()->qFormat('DD.MM.YYYY') : null,
     *         ];
     *     },
     *     News::loadOlderForPageContext(
     *         $objNews->Id,
     *         $objNews->PostDate,
     *         $data->GroupedId,
     *         5
     *     )
     * );
     *
     * ****************************
     *
     * EXAMPLE: Context integration into the frontend gallery list
     *
     * $pageContext = new \QCubed\Plugin\PageContext($this);
     * $pageContext->Title = 'Older galleries';
     *
     * $pageContext->DataSource = array_map(
     *     function ($g) {
     *         return [
     *             'title' => $g->getTitle(),
     *             'url'   => $g->getTitleSlug(),
     *             'date'  => $g->getPostDate() ? $g->getPostDate()->qFormat('DD.MM.YYYY') : null,
     *         ];
     *     },
     *     GalleryList::loadOlderForPageContext(
     *         $objGalleryList->Id,
     *         $objGalleryList->PostDate,
     *         $data->GroupedId,
     *         5
     *     )
     * );
     */