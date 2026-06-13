<?php

    namespace QCubed\Plugin;

    use Files;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Type;
    use Throwable;

    /**
     * Class representing a panel for managing and displaying a list of page links.
     * Offers support for multiple display modes, hierarchical grouping, and configurable behaviors
     * like expandable views and new tab openings. The implementation includes advanced capabilities
     * for processing data sources, extracting and normalizing data, and structuring
     * attachment groups.
     *
     * @property string $TagName The HTML tag name to use for the list container. Defaults to 'div'.
     * @property string $WrapperClass The CSS class to apply to the wrapper div.
     * @property array $DataSource An array of data items to be displayed in the list.
     * @property string $DisplayMode The display mode for the list. Can be one of the following:
     *                              - 'flat': Displays a flat list of links without any grouping.
     *                              - 'grouped': Groups links into categories based on the 'group_title' property.
     *                              - 'mixed': Combines 'flat' and 'grouped' modes.
     *                              - 'attachment_groups': Organizes links into attachment groups.
     * @property bool $OpenInNewTab Determines whether links should open in a new tab. Defaults to true.
     * @property bool $Expandable Enables expandable attachment groups. Defaults to false.
     * @property int $LimitCount The maximum number of items to display in an expandable attachment group.
     *                           Only applies when 'Expandable' is true and 'DisplayMode' is 'attachment_groups'.
     * @property string $MoreLabel The label to display for the "See more..." link in expandable attachment groups.
     * @property string $ResetLabel The label to display for the "Back to the start" link in expandable attachment groups.
     *
     * @package QCubed\Plugin
     */
    class PageLinks extends Panel
    {
        public const MODE_FLAT = 'flat';
        public const MODE_GROUPED = 'grouped';
        public const MODE_MIXED = 'mixed';
        public const MODE_ATTACHMENT_GROUPS = 'attachment_groups';

        public const TYPE_DESTINATION = 1;
        public const TYPE_ATTACHMENT = 2;
        public const TYPE_ATTACHMENT_GROUP = 3;

        protected array $arrDataSource = [];
        protected string $strTemplate = 'PageLinks.tpl.php';
        protected string $strTagName = 'div';
        protected string $strWrapperClass = 'links-list-wrapper';
        protected string $strDisplayMode = self::MODE_MIXED;
        protected bool $blnOpenInNewTab = true;

        protected bool $blnExpandable = false;
        protected int $intLimitCount = 0;
        protected string $strMoreLabel = 'See more...';
        protected string $strResetLabel = 'Back to the start';

        protected string $strJavaScripts = FRONTEND_HELPERS_ASSETS_URL . '/js/page-links-groups.min.js';

        /**
         * Processes the data source and extracts normalized items based on specific criteria.
         *
         * @return array An array of normalized items, each containing keys such as 'name', 'href', 'group_title', and optionally 'target'.
         *               Returns an empty array if the data source is unavailable or no valid items are found.
         * @throws Caller
         * @throws InvalidCast
         */
        public function getNormalizedItems(): array
        {
            if (!$this->arrDataSource) {
                return [];
            }

            $out = [];

            foreach ($this->arrDataSource as $item) {
                $row = $this->extractRowData($item);

                if ($row === null) {
                    continue;
                }

                $href = $this->resolveHref($row['link_type_id'], $row['url'], $row['files_id']);

                if ($href === '') {
                    continue;
                }

                $out[] = [
                    'name' => $row['name'],
                    'href' => $href,
                    'group_title' => $row['group_title'],
                    'target' => $this->blnOpenInNewTab ? '_blank' : null,
                ];
            }

            return $out;
        }

        /**
         * Prepares and returns structured data based on the current display mode and available items.
         * If the display mode is configured for attachment groups, it returns empty groups and populates attachment groups.
         * Otherwise, it organizes items into grouped and ungrouped categories based on their group title.
         *
         * @return array An associative array containing:
         * - 'mode': The current display mode.
         * - 'ungrouped': Items without a group title.
         * - 'groups': Items organized into groups by their group titles.
         * - 'attachment_groups': Populated attachment groups if in attachment group mode; otherwise, an empty array.
         * @throws Caller
         * @throws InvalidCast
         */
        public function getPreparedData(): array
        {
            if ($this->strDisplayMode === self::MODE_ATTACHMENT_GROUPS) {
                return [
                    'mode' => $this->strDisplayMode,
                    'ungrouped' => [],
                    'groups' => [],
                    'attachment_groups' => $this->getPreparedAttachmentGroups(),
                ];
            }

            $items = $this->getNormalizedItems();

            $ungrouped = [];
            $groups = [];

            foreach ($items as $item) {
                $groupTitle = trim((string)$item['group_title']);

                if ($groupTitle === '') {
                    $ungrouped[] = $item;
                    continue;
                }

                if (!isset($groups[$groupTitle])) {
                    $groups[$groupTitle] = [];
                }

                $groups[$groupTitle][] = $item;
            }

            return [
                'mode' => $this->strDisplayMode,
                'ungrouped' => $ungrouped,
                'groups' => $groups,
                'attachment_groups' => [],
            ];
        }

        /**
         * Processes the data source to prepare groups of attachments with associated metadata.
         *
         * @return array Returns an array of prepared attachment groups. Each group includes an ID, a title.
         *               and a list of items with their respective names and resolved href links.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function getPreparedAttachmentGroups(): array
        {
            $out = [];

            foreach ($this->arrDataSource as $group) {
                $groupId = (int)($group['id'] ?? 0);
                $groupTitle = trim((string)($group['title'] ?? ''));
                $items = $group['items'] ?? [];

                $preparedItems = [];

                foreach ($items as $item) {
                    $row = $this->extractRowData($item);

                    if ($row === null) {
                        continue;
                    }

                    $preparedItems[] = [
                        'name' => $row['name'],
                        'href' => $this->resolveHref($row['link_type_id'], $row['url'], $row['files_id']),
                    ];
                }

                if (!$preparedItems) {
                    continue;
                }

                $out[] = [
                    'id' => $groupId,
                    'title' => $groupTitle,
                    'items' => $preparedItems,
                ];
            }

            return $out;
        }

        /**
         * Extracts and normalizes row data from the given item.
         *
         * @param mixed $item The input data item. It can be an array or an object
         *                    containing the properties needed for extraction.
         *
         * @return array|null Returns an associative array with the extracted data, including 'name', 'url',
         *                    'files_id', 'link_type_id', and 'group_title'. Returns null if 'name' is missing or empty.
         */
        protected function extractRowData(mixed $item): ?array
        {
            if (is_array($item)) {
                $name = array_key_exists('name', $item) ? trim((string)$item['name']) : '';
                $url = array_key_exists('url', $item) ? trim((string)$item['url']) : '';
                $filesId = array_key_exists('files_id', $item) && $item['files_id'] !== null
                    ? (int)$item['files_id']
                    : null;
                $linkTypeId = array_key_exists('link_type_id', $item) && $item['link_type_id'] !== null
                    ? (int)$item['link_type_id']
                    : null;
                $groupTitle = array_key_exists('link_category', $item)
                    ? trim((string)$item['link_category'])
                    : '';
            } else {
                try {
                    $name = trim((string)$item->Name);
                } catch (Throwable) {
                    $name = '';
                }

                try {
                    $url = trim((string)$item->Url);
                } catch (Throwable) {
                    $url = '';
                }

                try {
                    $filesId = $item->FilesId !== null ? (int)$item->FilesId : null;
                } catch (Throwable) {
                    $filesId = null;
                }

                try {
                    $linkTypeId = $item->LinkTypeId !== null ? (int)$item->LinkTypeId : null;
                } catch (Throwable) {
                    $linkTypeId = null;
                }

                try {
                    $groupTitle = trim((string)$item->LinkCategory);
                } catch (Throwable) {
                    $groupTitle = '';
                }
            }

            if ($name === '') {
                return null;
            }

            return [
                'name' => $name,
                'url' => $url,
                'files_id' => $filesId,
                'link_type_id' => $linkTypeId,
                'group_title' => $groupTitle,
            ];
        }

        /**
         * Resolves and generates the appropriate href link based on the link type ID,
         * URL, and an optional file ID.
         *
         * @param int|null $linkTypeId Optional link type identifier used to determine the resolution method.
         * @param string $url The URL to be used if applicable based on the link type.
         * @param int|null $filesId Optional file ID used to load file-specific information when resolving the href.
         *
         * @return string Returns the resolved href link as a string. If the resolution fails or criteria
         *                are not met, an empty string is returned.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function resolveHref(?int $linkTypeId, string $url, ?int $filesId): string
        {
            if ($linkTypeId === self::TYPE_DESTINATION) {
                return $url;
            }

            if (
                in_array($linkTypeId, [self::TYPE_ATTACHMENT, self::TYPE_ATTACHMENT_GROUP], true)
                && $filesId
            ) {
                $objFile = Files::load($filesId);

                if ($objFile) {
                    try {
                        return trim(APP_UPLOADS_URL . $objFile->Path);
                    } catch (Throwable) {
                        return '';
                    }
                }
            }

            return '';
        }

        /**
         * Generates and returns the HTML string for the control element.
         *
         * @return string Returns the generated HTML string based on the tag name, wrapper class, and inner content.
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
         * Generates the end script for the control, potentially appending script logic for expandable attachment groups.
         *
         * @return string Returns the complete end script as a string. If the display mode is set to attachment groups.
         *                an expandable mode is enabled, and a limit count is specified; additional JavaScript logic is included
         *                to handle group expansion using jQuery.
         */
        public function getEndScript(): string
        {
            $str = parent::getEndScript();

            if (
                $this->strDisplayMode === self::MODE_ATTACHMENT_GROUPS
                && $this->blnExpandable
                && $this->intLimitCount > 0
            ) {
                $str .= sprintf(
                    '
if (window.jQuery && jQuery.fn && jQuery.fn.pageLinksGroups) {
    jQuery("#%s").pageLinksGroups({limit: %d});
}',
                    $this->ControlId,
                    $this->intLimitCount
                );
            }

            return $str;
        }

        /**
         * Dynamically retrieves the value of a property based on its name.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed Returns the value of the requested property, or delegates to the parent implementation
         *               if the property name does not match any predefined cases.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'TagName' => $this->strTagName,
                'WrapperClass' => $this->strWrapperClass,
                'DataSource' => $this->arrDataSource,
                'DisplayMode' => $this->strDisplayMode,
                'OpenInNewTab' => $this->blnOpenInNewTab,
                'Expandable' => $this->blnExpandable,
                'LimitCount' => $this->intLimitCount,
                'MoreLabel' => $this->strMoreLabel,
                'ResetLabel' => $this->strResetLabel,
                default => parent::__get($strName),
            };
        }

        /**
         * Dynamically sets the value of a given property based on the provided name and value.
         * Validates and assigns the input according to the property's expected type or throws an exception for invalid data.
         * Marks the object as modified after successfully setting a property.
         *
         * @param string $strName The name of the property to dynamically set.
         * @param mixed $mixValue The value to assign to the specified property. The type of the value depends on the property being set.
         *
         * @return void Does not return a value.
         *
         * @throws Caller If an invalid value is assigned to the 'DisplayMode' property.
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

                    $allowed = [
                        self::MODE_FLAT,
                        self::MODE_GROUPED,
                        self::MODE_MIXED,
                        self::MODE_ATTACHMENT_GROUPS,
                    ];

                    if (!in_array($value, $allowed, true)) {
                        throw new Caller('Invalid DisplayMode: ' . $value);
                    }

                    $this->strDisplayMode = $value;
                    break;

                case 'OpenInNewTab':
                    $this->blnOpenInNewTab = Type::cast($mixValue, Type::BOOLEAN);
                    break;

                case 'Expandable':
                    $this->blnExpandable = Type::cast($mixValue, Type::BOOLEAN);
                    break;

                case 'LimitCount':
                    $this->intLimitCount = max(0, (int)$mixValue);
                    break;

                case 'MoreLabel':
                    $this->strMoreLabel = Type::cast($mixValue, Type::STRING);
                    break;

                case 'ResetLabel':
                    $this->strResetLabel = Type::cast($mixValue, Type::STRING);
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }