<?php

    namespace QCubed\Plugin;

    use QCubed\ApplicationBase;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Project\Application;
    use QCubed\Query\QQ;

    /**
     * Class FrontendSportsCalendarArchive extends the Panel class and provides functionality
     * to manage and display a sports calendar archive. It handles data loading, event categorization,
     * and attachment mapping for sports-related events for a frontend interface.
     *
     * @property string $NoEventsText The text to display when there are no events available.
     * @property string $DefaultInstitutionTitle The default title for institutions not specified in the data.
     * @property int $MenuContentId The ID of the menu content associated with the sports calendar archive.
     * @property int $Year The year for which events are displayed.
     * @property array $Years An array of years available for selection.
     * @property array $Events An array of events loaded from the database.
     * @property array $GroupedEvents An array of events grouped by institution or "own".
     * @property bool $EnableNavigation Whether navigation is enabled for the calendar.
     * @property int $EventId The ID of the selected event.
     */
    class FrontendSportsCalendarArchive extends Panel
    {
        protected const TYPE_GUIDE = 1;
        protected const TYPE_RESULTS = 2;
        protected const TYPE_SCHEDULE = 3;
        protected const TYPE_DOCUMENT = 4;

        protected string $strWrapperClass = 'sports-calendar-archive';
        protected string $strNoEventsText = 'There are currently no events in the sports calendar.';
        protected string $strDefaultInstitutionTitle = 'Default institution title';
        protected bool $blnEnableNavigation = false;
        protected ?int $intEventId = null;

        protected ?int $intMenuContentId = null;
        protected ?int $intYear = null;

        protected array $arrYears = [];
        protected array $arrEvents = [];
        protected array $arrGroupedEvents = [];
        protected array $arrAttachmentMap = [];
        protected array $arrAttachmentTypesMap = [];

        protected string $strTemplate = 'FrontendSportsCalendarArchive.tpl.php';

        /**
         * Constructor for the class used to initialize an instance with a parent object and an optional control ID.
         *
         * @param mixed $parentObject The parent object with which this instance is associated.
         * @param string|null $controlId An optional identifier for the control. Defaults to null if not provided.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(mixed $parentObject, ?string $controlId = null)
        {
            parent::__construct($parentObject, $controlId);
            $this->UseWrapper = false;
        }

        /**
         * Loads data required for the internal properties by initializing arrays
         * for years, events, and grouped events. If the menu content ID is not set,
         * the method exits without processing.
         *
         * @return void Does not return a value.
         * @throws Caller
         * @throws InvalidCast
         */
        public function loadData(): void
        {
            $this->arrYears = [];
            $this->arrEvents = [];
            $this->arrGroupedEvents = [];
            $this->arrAttachmentMap = [];
            $this->arrAttachmentTypesMap = [];

            if (!$this->intMenuContentId) {
                return;
            }

            $this->arrYears = $this->loadYears();
            $this->arrEvents = $this->loadEvents();
            $this->arrAttachmentMap = $this->loadAttachmentMap($this->arrEvents);
            $this->arrGroupedEvents = $this->buildGroupedEvents($this->arrEvents);
        }

        /**
         * Checks whether there are any events available.
         *
         * @return bool Returns true if there are events, otherwise false.
         */
        public function hasEvents(): bool
        {
            return !empty($this->arrEvents);
        }

        /**
         * Retrieves the list of years stored in the class.
         *
         * Returns an array containing the years maintained by the class instance.
         * The data is typically preloaded or set during the object lifecycle.
         *
         * @return array An array of years managed by the class.
         */
        public function getYears(): array
        {
            return $this->arrYears;
        }

        /**
         * Retrieves the list of events stored in the current instance.
         *
         * This method returns an array of event data that has been previously loaded or set
         * within the instance. It does not perform any additional computations or database queries.
         *
         * @return array An array of event objects or data.
         */
        public function getEvents(): array
        {
            return $this->arrEvents;
        }

        /**
         * Retrieves an array of events grouped based on certain criteria.
         *
         * @return array Returns a multidimensional array containing grouped events.
         */
        public function getGroupedEvents(): array
        {
            return $this->arrGroupedEvents;
        }

        /**
         * Loads and retrieves an array of years.
         *
         * @return array Returns an array containing years data.
         */
        protected function loadYears(): array
        {
            return [];
        }

        /**
         * Loads events based on the provided menu content ID and optional year constraints.
         *
         * This method queries the Sports Calendar database table to retrieve events that
         * match the specified menu content ID and have a status of active. If a year is specified,
         * the results are further filtered to include only events from the given year. The results
         * are then ordered by the beginning event date, start time, and title.
         *
         * @return array An array of SportsCalendar objects matching the specified conditions.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function loadEvents(): array
        {
            if (!$this->intMenuContentId) {
                return [];
            }

            $condition = QQ::andCondition(
                QQ::equal(\QQN::SportsCalendar()->MenuContentId, $this->intMenuContentId),
                QQ::equal(\QQN::SportsCalendar()->Status, 1)
            );

            if ($this->intYear) {
                $condition = QQ::andCondition(
                    $condition,
                    QQ::equal(\QQN::SportsCalendar()->Year, $this->intYear)
                );
            }

            return \SportsCalendar::queryArray(
                $condition,
                [
                    QQ::orderBy(
                        \QQN::SportsCalendar()->BeginningEvent,
                        true,
                        \QQN::SportsCalendar()->StartTime,
                        true,
                        \QQN::SportsCalendar()->Title,
                        true
                    )
                ]
            );
        }

        /**
         * Renders a formatted event date range based on the start and end dates of the event.
         *
         * @param \SportsCalendar $objEvent The event object containing the start and optionally the end date.
         *
         * @return string Returns a formatted string representing the event date range. If the event has no start date.
         *                an empty string is returned. If the event lacks an end date, only the start date is formatted.
         * @throws Caller
         */
        public function renderEventDate(\SportsCalendar $objEvent): string
        {
            $start = $objEvent->getBeginningEvent();
            $end = method_exists($objEvent, 'getEndEvent') ? $objEvent->getEndEvent() : null;

            if (!$start) {
                return '';
            }

            if (!$end) {
                return $start->qFormat('DD.MM.YYYY');
            }

            if ($start->qFormat('YYYY') === $end->qFormat('YYYY')) {
                return $start->qFormat('DD.MM') . ' - ' . $end->qFormat('DD.MM.YYYY');
            }

            return $start->qFormat('DD.MM.YYYY') . ' - ' . $end->qFormat('DD.MM.YYYY');
        }

        /**
         * Renders the name of the event change associated with the given event.
         *
         * @param \SportsCalendar $objEvent The event object containing event change information.
         *
         * @return string Returns the name of the event change as a string or an empty string if no event change is found.
         * @throws Caller
         * @throws InvalidCast
         */
        public function renderEventChange(\SportsCalendar $objEvent): string
        {
            if (!$objEvent->getEventsChangesId()) {
                return '';
            }

            $objChange = $objEvent->getEventsChanges();

            return $objChange ? (string)$objChange->getTitle() : '';
        }

        /**
         * Loads a mapping of attachments associated with the given events.
         *
         * The method retrieves data from the SportsTables database for the specified events
         * and constructs a mapping based on calendar IDs and content type IDs. It also updates
         * an internal map of attachment types.
         *
         * @param array $events An array of event objects from which the attachment map is derived.
         *
         * @return array Returns an associative array where the keys are calendar IDs and the values are true.
         * @throws Caller
         * @throws InvalidCast
         */
        protected function loadAttachmentMap(array $events): array
        {
            if (!$events) {
                return [];
            }

            $ids = [];

            foreach ($events as $objEvent) {
                $ids[] = (int)$objEvent->getId();
            }

            $items = \SportsTables::queryArray(
                QQ::andCondition(
                    QQ::in(\QQN::SportsTables()->SportsCalendarId, $ids),
                    QQ::equal(\QQN::SportsTables()->Status, 1)
                ),
                [
                    QQ::orderBy(
                        \QQN::SportsTables()->SportsContentTypesId,
                        true
                    )
                ]
            );

            $map = [];
            $this->arrAttachmentTypesMap = [];

            foreach ($items as $item) {
                $calendarId = (int)$item->getSportsCalendarId();
                $typeId = (int)$item->getSportsContentTypesId();

                $map[$calendarId] = true;

                $objType = $item->getSportsContentTypes();
                if ($objType) {
                    $this->arrAttachmentTypesMap[$calendarId][$typeId] = $objType->getName();
                }
            }

            return $map;
        }

        /**
         * Retrieves the attachment type labels associated with the given sports calendar ID.
         *
         * @param int $sportsCalendarId The ID of the sports calendar for which attachment type labels are retrieved.
         *
         * @return array Returns an array of attachment type labels corresponding to the provided sports calendar ID.
         */
        public function getAttachmentTypeLabels(int $sportsCalendarId): array
        {
            return array_values($this->arrAttachmentTypesMap[$sportsCalendarId] ?? []);
        }

        /**
         * Determines if there are active attachments for a given sports calendar ID.
         *
         * @param int $sportsCalendarId The ID of the sports calendar to check for active attachments.
         *
         * @return bool Returns true if active attachments exist, otherwise false.
         */
        public function hasActiveAttachments(int $sportsCalendarId): bool
        {
            return !empty($this->arrAttachmentMap[$sportsCalendarId]);
        }

        /**
         * Determines if the given event has content suitable for a modal display.
         *
         * @param \SportsCalendar $objEvent The event object to evaluate for modal content.
         *
         * @return bool Returns true if the event has active attachments or non-empty content, otherwise false.
         * @throws Caller
         */
        public function hasModalContent(\SportsCalendar $objEvent): bool
        {
            if ($this->hasActiveAttachments((int)$objEvent->getId())) {
                return true;
            }

            if (trim((string)$objEvent->getContent()) !== '') {
                return true;
            }

            return false;
        }

        /**
         * Constructs and organizes events into groups based on specific criteria.
         *
         * @param array $events An array of events to be grouped.
         *
         * @return array Returns a multidimensional array where events are organized into groups.
         */
        protected function buildGroupedEvents(array $events): array
        {
            $groups = [];

            foreach ($events as $objEvent) {
                $institutionId = ($objEvent->getOrganizingInstitutionId() ?? 0);

                if ($institutionId === 0) {
                    $key = 'own';
                    $title = $this->DefaultInstitutionTitle;
                    $sort = 0;
                } else {
                    $objInstitution = $objEvent->getOrganizingInstitution();

                    $key = 'institution_' . $institutionId;
                    $title = $objInstitution?->getName() ?? '';
                    $sort = $objInstitution?->getSortOrder() ?? 9999;
                }

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'title' => $title,
                        'sort_order' => $sort,
                        'events' => [],
                    ];
                }

                $groups[$key]['events'][] = $objEvent;
            }

            uasort($groups, static function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            });

            return array_values($groups);
        }

        /**
         * Generates and retrieves the HTML string for the control element.
         *
         * @return string Returns the rendered HTML of the control element as a string.
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
         * Generates and returns the JavaScript code for managing user interactions and dynamic behaviors
         * of the control, including paginated item displays and event handling for custom actions.
         *
         * This method builds a script that handles features like scrolling, item visibility toggling,
         * and user interactions such as clicking "show more" or resetting the view to the default state.
         * It also integrates with external event systems to trigger additional functionality.
         *
         * @return string The generated JavaScript code to be executed on the client side.
         * @throws Caller
         */
        public function getEndScript(): string
        {
            $strJS = parent::getEndScript();

            $enableNavigation = $this->blnEnableNavigation ? 'true' : 'false';
            $rootId = $this->ControlId;

            $strCtrlJs = <<<FUNC
(function() {
  var root = document.getElementById("$rootId");
  if (!root) { return; }

  root.addEventListener("click", function(e) {
    var a = e.target && e.target.closest ? e.target.closest("a[data-id]") : null;
    if (!a || !root.contains(a)) { return; }

    var id = a.getAttribute("data-id");
    if (!id) { return; }

    if (window.qcubed && typeof window.qcubed.recordControlModification === "function") {
      window.qcubed.recordControlModification(root.id, "_EventId", id);
    }

    if ($enableNavigation) {
      return;
    }

    e.preventDefault();

    if (window.jQuery) {
      window.jQuery(root).trigger("selectevent");
    }
  }, false);
})();

FUNC;

            Application::executeJavaScript($strCtrlJs, ApplicationBase::PRIORITY_HIGH);

            return $strJS;
        }

        /**
         * Magic getter method to retrieve property values based on the provided name.
         *
         * This method allows dynamic access to various properties of the object, including
         * MenuContentId, Year, Years, Events, and GroupedEvents. For unsupported properties,
         * the method falls back to the parent implementation.
         *
         * @param string $strName
         *
         * @return mixed The value of the requested property, or the parent implementation's result if the property is not recognized.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'WrapperClass' => $this->strWrapperClass,
                'NoEventsText' => $this->strNoEventsText,
                'DefaultInstitutionTitle' => $this->strDefaultInstitutionTitle,
                'MenuContentId' => $this->intMenuContentId,
                'Year' => $this->intYear,
                'Years' => $this->arrYears,
                'Events' => $this->arrEvents,
                'GroupedEvents' => $this->arrGroupedEvents,
                'EnableNavigation' => $this->blnEnableNavigation,
                'EventId' => $this->intEventId,
                default => parent::__get($strName),
            };
        }

        /**
         * Magic method to set the value of a property dynamically.
         *
         * This method allows setting specific properties (`MenuContentId` and `Year`)
         * with appropriate type casting and marks the object as modified. If the property
         * name does not match predefined cases, it delegates the setting operation to the parent class.
         *
         * @param string $strName
         * @param mixed $mixValue
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

                case 'NoEventsText':
                    $this->strNoEventsText = (string)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'DefaultInstitutionTitle':
                    $this->strDefaultInstitutionTitle = (string)$mixValue;
                    $this->blnModified = true;
                    break;

                case 'MenuContentId':
                    $this->intMenuContentId = $mixValue ? (int)$mixValue : null;
                    $this->blnModified = true;
                    break;

                case 'Year':
                    $this->intYear = $mixValue ? (int)$mixValue : null;
                    $this->blnModified = true;
                    break;

                case 'EnableNavigation':
                    $this->blnEnableNavigation = (bool)$mixValue;
                    $this->blnModified = true;
                    break;

                case '_EventId': // Internal only. Do not use. Used by JS above to track selections.
                    $this->intEventId = $mixValue ? (int)$mixValue : null;
                    $this->blnModified = true;
                    break;

                default:
                    parent::__set($strName, $mixValue);
            }
        }
    }