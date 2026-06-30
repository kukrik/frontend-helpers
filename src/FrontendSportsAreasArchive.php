<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Query\QQ;

    /**
     * Class FrontendSportsAreasArchive
     *
     * @property string $WrapperClass
     * @property string $NoDocumentsText
     * @property int|null $SportsAreaId
     * @property \SportsAreas[] $SportsAreas
     * @property \SportsContentTypes[] $ContentTypes
     * @property \SportsTables[] $Documents
     * @property array $DocumentsByType
     * @property Accordion $Accordion
     *
     * Represents a frontend component for displaying and managing archived documents
     * associated with different sports areas. It includes functionality for loading
     * sports areas, their content types, and documents, as well as organizing these
     * documents into an accordion-style UI for user interaction.
     */
    class FrontendSportsAreasArchive extends Panel
    {
        protected const TYPE_GUIDE = 1;
        protected const TYPE_RESULTS = 2;
        protected const TYPE_SCHEDULE = 3;
        protected const TYPE_DOCUMENT = 4;

        protected string $strWrapperClass = 'sports-areas-archive';
        protected string $strNoDocumentsText = 'There are currently no documents available for this sport.';
        protected string $strRootUrl = APP_UPLOADS_URL;

        protected ?int $intSportsAreaId = null;

        protected array $arrSportsAreas = [];
        protected array $arrContentTypes = [];
        protected array $arrDocuments = [];
        protected array $arrDocumentsByType = [];

        protected Accordion $objAccordion;

        protected array $arrContentTypePriority = [
            self::TYPE_RESULTS,
            self::TYPE_GUIDE,
            self::TYPE_SCHEDULE,
            self::TYPE_DOCUMENT
        ];

        /**
         * Constructor for initializing the object with its parent and optional control ID.
         *
         * Sets up the accordion control and its default properties.
         *
         * @param mixed $parentObject The parent object that this instance is associated with.
         * @param string|null $controlId An optional identifier for the control.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(mixed $parentObject, ?string $controlId = null)
        {
            parent::__construct($parentObject, $controlId);
            $this->UseWrapper = false;

            $this->objAccordion = new Accordion($this);
            $this->objAccordion->ShowMultiple = false;
            $this->objAccordion->Collapse = true;
        }

        /**
         * Loads and prepares data required for the application view, including sports areas,
         * content types, and documents. Initializes and resolves initial selections, updates
         * the accordion control, and organizes documents by type.
         *
         * @return void
         * @throws Caller
         * @throws InvalidCast
         */
        public function loadData(): void
        {
            $this->arrSportsAreas = $this->loadSportsAreas();
            $this->arrContentTypes = $this->loadContentTypes();
            $this->arrDocuments = [];
            $this->arrDocumentsByType = [];

            $this->objAccordion->Items = [];

            $this->resolveInitialSelection();

            if (!$this->intSportsAreaId) {
                return;
            }

            $this->arrDocuments = $this->loadDocuments($this->intSportsAreaId);
            $this->arrDocumentsByType = $this->buildDocumentsByType($this->arrDocuments);

            $activePanel = $this->findFirstAvailableContentTypeId($this->arrDocumentsByType);

            if ($activePanel !== null) {
                $this->objAccordion->ActivePanel = $activePanel;
            }

            $this->buildAccordionItems();
        }

        /**
         * Checks if there are any documents available.
         *
         * @return bool Returns true if there are documents, otherwise false.
         */
        public function hasDocuments(): bool
        {
            return !empty($this->arrDocuments);
        }

        /**
         * Checks if there are documents of the specified content type.
         *
         * @param int $contentTypeId The ID of the content type to check for documents.
         *
         * @return bool True if documents exist for the given content type, false otherwise.
         */
        public function hasDocumentsByType(int $contentTypeId): bool
        {
            return !empty($this->arrDocumentsByType[$contentTypeId]);
        }

        /**
         * Retrieves an array of documents based on the provided content type ID.
         *
         * @param int $contentTypeId The ID of the content type to filter documents by.
         *
         * @return array An array of documents associated with the specified content type ID.
         */
        public function getDocumentsByType(int $contentTypeId): array
        {
            return $this->arrDocumentsByType[$contentTypeId] ?? [];
        }

        /**
         * Retrieves the active sports area based on the currently set identifier.
         * Returns null if no sports area is active or the ID does not match any available sports area.
         *
         * @return \SportsAreas|null The active sports area or null if not found.
         */
        public function getActiveSportsArea(): ?\SportsAreas
        {
            if (!$this->intSportsAreaId) {
                return null;
            }

            foreach ($this->arrSportsAreas as $objSportsArea) {
                if ((int)$objSportsArea->getId() === $this->intSportsAreaId) {
                    return $objSportsArea;
                }
            }

            return null;
        }

        /**
         * Loads and retrieves a list of enabled sports areas from the database.
         * The sports areas are returned in alphabetical order by their name.
         *
         * @return array The array of sports areas objects that are enabled and sorted by name.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function loadSportsAreas(): array
        {
            return \SportsAreas::queryArray(
                QQ::equal(\QQN::SportsAreas()->IsEnabled, 1),
                [
                    QQ::orderBy(\QQN::SportsAreas()->Name, true)
                ]
            );
        }

        /**
         * Loads content types that are active (status = 1) and orders them by ID in descending order.
         *
         * @return array An array of SportsContentTypes objects that match the criteria.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function loadContentTypes(): array
        {
            return \SportsContentTypes::queryArray(
                QQ::equal(\QQN::SportsContentTypes()->Status, 1),
                [
                    QQ::orderBy(\QQN::SportsContentTypes()->Id, true)
                ]
            );
        }

        /**
         * Kui spordiala on controllerist/sidebari klikist määratud, jätame selle alles
         * ka siis, kui dokumente pole. Siis saab kasutaja tühja teate.
         *
         * Kui spordiala pole määratud, valime esimese spordiala, millel on dokumente.
         *
         * @throws Caller
         * @throws InvalidCast
         */
        protected function resolveInitialSelection(): void
        {
            if ($this->intSportsAreaId) {
                return;
            }

            foreach ($this->arrSportsAreas as $objSportsArea) {
                $sportsAreaId = (int)$objSportsArea->getId();

                if ($this->sportsAreaHasDocuments($sportsAreaId)) {
                    $this->intSportsAreaId = $sportsAreaId;
                    return;
                }
            }

            $this->intSportsAreaId = null;
        }

        /**
         * Kontrollib, kas antud spordialaga seotud dokumente on olemas.
         *
         * @param int $sportsAreaId Spordiala identifikaator, millele soovitakse dokumentide olemasolu kontrollida.
         *
         * @return bool Tagastab tõene, kui spordiala on seotud dokumentidega, vastasel juhul vale.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function sportsAreaHasDocuments(int $sportsAreaId): bool
        {
            return \SportsTables::queryCount(
                    QQ::andCondition(
                        QQ::equal(\QQN::SportsTables()->SportsAreasId, $sportsAreaId),
                        QQ::equal(\QQN::SportsTables()->Status, 1),
                        QQ::isNotNull(\QQN::SportsTables()->FilesId)
                    )
                ) > 0;
        }

        /**
         * Loads documents associated with a given sports area ID.
         *
         * Retrieves an array of documents that are active and associated with a particular sports area.
         * It ensures only documents with non-null file IDs and status set to active are included.
         * The result is sorted by content type ID (ascending), show date (descending), and title (ascending).
         *
         * @param int $sportsAreaId The ID of the sports area whose documents need to be loaded.
         *
         * @return array An array of documents matching the criteria for the specified sports area.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function loadDocuments(int $sportsAreaId): array
        {
            return \SportsTables::queryArray(
                QQ::andCondition(
                    QQ::equal(\QQN::SportsTables()->SportsAreasId, $sportsAreaId),
                    QQ::equal(\QQN::SportsTables()->Status, 1),
                    QQ::isNotNull(\QQN::SportsTables()->FilesId)
                ),
                [
                    QQ::orderBy(
                        \QQN::SportsTables()->SportsContentTypesId,
                        true,
                        \QQN::SportsTables()->ShowDate,
                        false,
                        \QQN::SportsTables()->Title,
                        true
                    )
                ]
            );
        }

        /**
         * Groups the given documents by their associated sports content type ID.
         * Populates an array where the key is the sports content type ID and the value is
         * an array of documents belonging to that type.
         *
         * @param array $documents The array of document objects to be grouped.
         *
         * @return array An associative array where the keys are sports content type IDs
         *               and the values are arrays of document objects.
         */
        protected function buildDocumentsByType(array $documents): array
        {
            $result = [];

            foreach ($documents as $objDocument) {
                $typeId = (int)$objDocument->getSportsContentTypesId();
                $result[$typeId][] = $objDocument;
            }

            return $result;
        }

        /**
         * Determines the first available content type ID from a prioritized list.
         * If no prioritized content type is available, falls back to any available type.
         *
         * @param array $documentsByType An associative array where the key is the content type ID
         *                               and the value is an array of documents for that type.
         *
         * @return int|null The ID of the first available content type, or null if none are found.
         */
        protected function findFirstAvailableContentTypeId(array $documentsByType): ?int
        {
            foreach ($this->arrContentTypePriority as $typeId) {
                if (!empty($documentsByType[$typeId])) {
                    return $typeId;
                }
            }

            foreach ($documentsByType as $typeId => $items) {
                if (!empty($items)) {
                    return (int)$typeId;
                }
            }

            return null;
        }

        /**
         * Build accordion items based on content type priority and available documents.
         *
         * The method organizes content types by their IDs, filters them according
         * to the defined priority list, and checks whether they have associated documents.
         * For each valid content type, an accordion item is created and added.
         *
         * @return void
         * @throws Caller
         * @throws InvalidCast
         */
        protected function buildAccordionItems(): void
        {
            $contentTypesById = [];

            foreach ($this->arrContentTypes as $objContentType) {
                $contentTypesById[(int)$objContentType->getId()] = $objContentType;
            }

            foreach ($this->arrContentTypePriority as $contentTypeId) {
                if (empty($contentTypesById[$contentTypeId])) {
                    continue;
                }

                if (!$this->hasDocumentsByType($contentTypeId)) {
                    continue;
                }

                $objContentType = $contentTypesById[$contentTypeId];

                $this->objAccordion->addItem(
                    $contentTypeId,
                    (string)$objContentType->getName(),
                    $this->renderDocumentsHtml($contentTypeId)
                );
            }
        }

        /**
         * Generates and returns an HTML string for rendering a list of documents
         * based on their content type.
         *
         * @param int $contentTypeId The ID of the content type used to fetch relevant documents.
         *
         * @return string The generated HTML string containing the list of documents. Returns an empty string if no documents are found.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function renderDocumentsHtml(int $contentTypeId): string
        {
            $documents = $this->getDocumentsByType($contentTypeId);

            if (!$documents) {
                return '';
            }

            $html = _nl('<div class="sports-areas-document-list">');

            foreach ($documents as $objDocument) {
                $date = $this->renderDocumentDate($objDocument);
                $url = $this->renderDocumentUrl($objDocument);
                $title = trim((string)$objDocument->getTitle());

                if ($title === '') {
                    $title = $objDocument->getFiles()?->getName() ?? 'Dokument';
                }

                $html .= _nl(_indent(
                    '<a class="sports-areas-document-row" href="' . $url . '" target="_blank" rel="noopener">',
                    1
                ));

                if ($date !== '') {
                    $html .= _nl(_indent(
                        '<span class="sports-areas-document-date">' . $date . '</span>',
                        2
                    ));
                }

                $html .= _nl(_indent(
                    '<span class="sports-areas-document-link">' . $title . '</span>',
                    2
                ));

                $html .= _nl(_indent('</a>', 1));
            }

            $html .= _nl('</div>');

            return $html;
        }

        /**
         * Renders the date of the provided document in the format 'DD.MM.YYYY'.
         *
         * @param \SportsTables $objDocument The document object containing the date to render.
         *
         * @return string The formatted date as a string, or an empty string if the date is not available.
         * @throws Caller
         */
        public function renderDocumentDate(\SportsTables $objDocument): string
        {
            $date = $objDocument->getShowDate();

            if (!$date) {
                return '';
            }

            return $date->qFormat('DD.MM.YYYY');
        }

        /**
         * Generates and returns the URL for a given document. If the document does not
         * have an associated file ID or the file cannot be loaded, a default URL is returned.
         *
         * @param \SportsTables $objDocument The document object containing details like the file ID.
         *
         * @return string The generated document URL or a default placeholder URL if the file is unavailable.
         * @throws Caller
         * @throws InvalidCast
         */
        public function renderDocumentUrl(\SportsTables $objDocument): string
        {
            if (!$objDocument->getFilesId()) {
                return '#';
            }

            $objFile = \Files::load((int)$objDocument->getFilesId());

            if (!$objFile) {
                return '#';
            }

            return $this->strRootUrl . $objFile->getPath();
        }

        /**
         * Generates and returns the HTML for the control area of sports-related content.
         *
         * The HTML includes a header with the active sports area's name if it exists,
         * and displays either a message indicating that there are no documents or the content
         * rendered by the accordion component. The output is wrapped in a styled container.
         *
         * @return string The rendered HTML string for the control area.
         * @throws Caller
         */
        protected function getControlHtml(): string
        {
            $objActiveSportsArea = $this->getActiveSportsArea();

            $html = '';

            if ($objActiveSportsArea) {
                $html .= _nl('<div class="sports-areas-header">');
                $html .= _nl(_indent(
                    '<h2 class="sports-areas-title">' . $objActiveSportsArea->getName() . '</h2>',
                    1
                ));
                $html .= _nl('</div>');
            }

            if (!$this->hasDocuments() || !$objActiveSportsArea) {
                $html .= _nl('<div class="sports-areas-empty">' . $this->strNoDocumentsText . '</div>');
            } else {
                $html .= _r($this->objAccordion);
            }

            return $this->renderTag(
                'div',
                $this->strWrapperClass ? ['class' => $this->strWrapperClass] : null,
                null,
                $html
            );
        }

        /**
         * Retrieves the value of a property dynamically based on the property name.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property, or the parent implementation for undefined properties.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'WrapperClass' => $this->strWrapperClass,
                'NoDocumentsText' => $this->strNoDocumentsText,
                'SportsAreaId' => $this->intSportsAreaId,
                'SportsAreas' => $this->arrSportsAreas,
                'ContentTypes' => $this->arrContentTypes,
                'Documents' => $this->arrDocuments,
                'DocumentsByType' => $this->arrDocumentsByType,
                'Accordion' => $this->objAccordion,
                default => parent::__get($strName),
            };
        }

        /**
         * Sets the value of a property dynamically based on the provided name.
         * Handles specific cases for `WrapperClass`, `NoDocumentsText`, and `SportsAreaId`.
         * For other properties, delegates the setting process to the parent class.
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
                case 'WrapperClass':
                    $this->strWrapperClass = (string)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'NoDocumentsText':
                    $this->strNoDocumentsText = (string)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'SportsAreaId':
                    $this->intSportsAreaId = $mixValue ? (int)$mixValue : null;
                    $this->blnModified = true;
                    break;

                default:
                    parent::__set($strName, $mixValue);
            }
        }
    }