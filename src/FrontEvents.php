<?php

    /** This file contains the FrontEvents Class */

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
    use QCubed\QDateTime;
    use QCubed\Type;

    /**
     * Class FrontEvents
     * @property int $EventId
     * @property string $SectionTitle
     * @property string $EmptyText
     * @property string $DateFormat
     * @property string $Locale
     * @property string $LocalePath
     * @property string $DefaultLocale
     * @property array $AllowedLocales
     * @property array $MonthLabels
     * @property bool $EnableNavigation
     * @property bool $HighlightUpcoming
     * @property callable $NodeParamsCallback
     * @property mixed $DataSource
     *
     * @package QCubed\Plugin
     */
    class FrontEvents extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        protected ?int $intEventId = null;

        /** @var null|string SectionTitle */
        protected ?string $strSectionTitle = null;
        /** @var string EmptyText */
        protected string $strEmptyText = 'There are currently no upcoming events.';
        /** @var array AllowedLocales*/
        private array $arrAllowedLocales = ['et'];
        /** @var string DefaultLocale */
        private string $strDefaultLocale = 'et';
        /** @var string Locale */
        private string $strLocale = 'et';
        /** @var null|array MonthLabels */
        private ?array $arrMonthLabels = null;
        /** @var string LocalePath */
        protected string $strLocalePath = FRONTEND_HELPERS_ASSETS_DIR . "/lang";
        /** @var string DateFormat */
        protected string $strDateFormat = 'DD.MM.YYYY';
        /** @var bool EnableNavigation */
        protected bool $blnEnableNavigation = false;
        /** @var bool HighlightUpcoming */
        protected bool $blnHighlightUpcoming = false;
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

            $this->UseWrapper = false;
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
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/front-events.css");
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

            $intId = 0;
            if (isset($params['id'])) {
                $intId = (int)$params['id'];
            }

            $strUrl = '';
            if (isset($params['url'])) {
                $strUrl = $params['url'];
            }

            $strDate = '';
            if (isset($params['native_date'])) {
                $strDate = $params['native_date'];
            }

            $strTime = '';
            if (isset($params['time'])) {
                $strTime = $params['time'];
            }

            $strTitle = '';
            if (isset($params['title'])) {
                $strTitle = $params['title'];
            }

            $strLocation = '';
            if (isset($params['location'])) {
                $strLocation = $params['location'];
            }

            $strUpdateText = '';
            if (isset($params['update_text'])) {
                $strUpdateText = $params['update_text'];
            }

            return [
                'id' => $intId,
                'url' => $strUrl,
                'native_date' => $strDate,
                'time' => $strTime,
                'title' => $strTitle,
                'location' => $strLocation,
                'update_text' => $strUpdateText,
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
         * @param string $locale
         *
         * @return $this
         */
        public function setLocale(string $locale): self
        {
            $locale = strtolower(trim($locale));

            if (in_array($locale, $this->arrAllowedLocales, true)) {
                $this->strLocale = $locale;
            } else {
                $this->strLocale = $this->strDefaultLocale;
            }

            $this->arrMonthLabels = null;

            return $this;
        }

        /**
         * @return array
         */
        private function monthLabels(): array
        {
            if ($this->arrMonthLabels !== null) {
                return $this->arrMonthLabels;
            }

            $file = $this->strLocalePath . "/datelang.{$this->strLocale}.php";

            if (!is_file($file)) {
                $file = $this->strLocalePath . "/datelang.{$this->strDefaultLocale}.php";
            }

            $cfg = require $file;

            return $this->arrMonthLabels = ($cfg['months'] ?? []);
        }

        /**
         * @param null|\QCubed\QDateTime $dtt
         *
         * @return string
         */
        public function monthLabelFromQDateTime(?QDateTime $dtt): string
        {
            if (!$dtt || $dtt->isDateNull()) {
                return '';
            }

            $months = $this->monthLabels();
            return $months[$dtt->Month] ?? '';
        }

        /**
         * @param bool $bln
         *
         * @return $this
         */
        public function highlightUpcoming(bool $bln = true): self
        {
            $this->blnHighlightUpcoming = $bln;
            return $this;
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

            $strOut = $this->renderEvents($this->strParams);
            $strHtml = $this->renderTag('span', null, null, $strOut);

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
         * Renders HTML for a sidebar menu displaying a list of years based on the provided parameters.
         * Ignores entries with specific statuses and generates HTML elements accordingly.
         *
         * @param array $arrParams An array of parameters, where each element contains 'year' and 'status' keys.
         *
         * @return string A string containing the generated HTML for the sidebar menu.
         * @throws \DateMalformedStringException
         * @throws Caller
         */
        protected function renderEvents(array $arrParams): string
        {
            $strHtml = '';

            $strHtml .= _nl(_indent('<h2>' . $this->escapeString($this->strSectionTitle) . '</h2>', 1));

            if (!count($arrParams)) {
                $strHtml .= _nl(_indent('<div class="event-item">', 1));
                $strHtml .= _nl(_indent('<div class="no-events">', 2));
                $strHtml .= _nl(_indent($this->escapeString($this->strEmptyText), 3));
                $strHtml .= _nl(_indent('</div>', 2));
                $strHtml .= _nl(_indent('</div>', 1));

                return $strHtml;
            }

            $today = new QDateTime(date('Y-m-d'));
            $todayYmd = (int)$today->qFormat('YYYYMMDD');

            // Let's start the walkthrough
            foreach ($arrParams as $row) {
                $intId         = (int)($row['id'] ?? 0);
                $strUrl        = (string)($row['url'] ?? '');
                $dtt           = $row['native_date'] ?? null;
                $strTime       = (string)($row['time'] ?? '');
                $strTitle      = (string)($row['title'] ?? '');
                $strLocation   = (string)($row['location'] ?? '');
                $strUpdateText = (string)($row['update_text'] ?? '');

                if (!$dtt instanceof QDateTime || $dtt->isDateNull()) {
                    continue;
                }

                $eventYmd = (int)$dtt->qFormat('YYYYMMDD');
                $isUpcomingOrToday = ($eventYmd >= $todayYmd);


                if ($this->blnHighlightUpcoming && $isUpcomingOrToday) {
                    $upcomingClass = ' is-upcoming';
                } else {
                    $upcomingClass = '';
                }

                $year = $dtt->Year;
                $day  = $dtt->qFormat('DD');

                $strHtml .= _nl(_indent('<a data-id="' . $intId . '" href="' . $this->escapeString($strUrl) . '">', 1));
                $strHtml .= _nl(_indent('<div class="event-item' . $upcomingClass . '">', 2));
                $strHtml .= _nl(_indent('<div class="event-date">', 3));
                $strHtml .= _nl(_indent('<div class="date-month">' . $this->monthLabelFromQDateTime($dtt) . '</div>', 4));
                $strHtml .= _nl(_indent('<div class="date-day-wrapper">', 4));
                $strHtml .= _nl(_indent('<span class="date-day">' . $day . '</span>', 5));
                $strHtml .= _nl(_indent('</div>', 4));
                $strHtml .= _nl(_indent('<div class="date-year">' . $year . '</div>', 4));
                $strHtml .= _nl(_indent('</div>', 3));
                $strHtml .= _nl(_indent('<div class="event-info">', 3));
                $strHtml .= _nl(_indent('<h4 class="event-title">' . $this->escapeString($strTitle) . '</h4>', 4));

                if ($strTime !== '') {
                    $strHtml .= _nl(_indent('<div class="event-time">' . $dtt->qFormat($this->strDateFormat) . ' (' . $strTime . ')' . '</div>', 4));
                } else {
                    $strHtml .= _nl(_indent('<div class="event-time">' . $dtt->qFormat($this->strDateFormat) . '</div>', 4));
                }

                $strHtml .= _nl(_indent('<div class="event-location">' . $this->escapeString($strLocation) . '</div>', 4));

                if ($strUpdateText !== '') {
                    $strHtml .= _nl(_indent('<div class="event-change">' . $this->escapeString($strUpdateText) . '</div>', 4));
                }

                $strHtml .= _nl(_indent('</div>', 3));
                $strHtml .= _nl(_indent('</div>', 2));
                $strHtml .= _nl(_indent('</a>', 1));
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
         * @param null|string $s
         *
         * @return string
         */
        private function escapeString(?string $s): string {
            return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
                case 'EventId': return $this->intEventId;
                case "SectionTitle": return $this->strSectionTitle;
                case "EmptyText": return $this->strEmptyText;
                case "AllowedLocales": return $this->arrAllowedLocales;
                case "DefaultLocale": return $this->strDefaultLocale;
                case "Locale": return $this->strLocale;
                case "LocalePath": return $this->strLocalePath;
                case "EnableNavigation": return $this->blnEnableNavigation;
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
                case '_EventId': // Internal only. Do not use. Used by JS above to track selections.
                    try {
                        $data = Type::cast($mixValue, Type::INTEGER);
                        $this->intEventId = $data;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;

                case "SectionTitle":
                    try {
                        $this->blnModified = true;
                        $this->strSectionTitle = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                    case "EmptyText":
                        try {
                        $this->blnModified = true;
                        $this->strEmptyText = Type::cast($mixValue, Type::STRING);
                        }
                        catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case "AllowedLocales":
                        try {
                            $this->blnModified = true;
                            $this->arrAllowedLocales = Type::cast($mixValue, Type::ARRAY_TYPE);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case "DefaultLocale":
                        try {
                            $this->blnModified = true;
                            $this->strDefaultLocale = Type::cast($mixValue, Type::STRING);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case "Locale":
                        try {
                            $this->blnModified = true;
                            $this->strLocale = Type::cast($mixValue, Type::STRING);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case "LocalePath":
                        try {
                            $this->blnModified = true;
                            $this->strLocalePath = Type::cast($mixValue, Type::STRING);
                        } catch (InvalidCast $objExc) {
                            $objExc->IncrementOffset();
                            throw $objExc;
                        }
                        break;

                    case "EnableNavigation":
                        try {
                            $this->blnModified = true;
                            $this->blnEnableNavigation = Type::cast($mixValue, Type::BOOLEAN);
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

    /**
     * EXAMPLE of using FrontEvents
     *
     * $this->objEvents = new FrontEvents($this);
     * $this->objEvents->setDataBinder('Events_Bind');
     * $this->objEvents->createNodeParams([$this, 'Events_Draw']);
     * $this->objEvents->highlightUpcoming(true);
     * $this->objEvents->SectionTitle = 'Tulevased sündmused';
     * $this->objEvents->EnableNavigation = true;
     * $this->objEvents->addAction(new SelectEvent(), new Ajax('objEvents_Click'));
     *
     * public function Events_Draw(EventsCalendar $objEvent): array
     * {
     *      $$a['id'] = $objEvent->Id;
     *      $a['url'] = $objEvent->TitleSlug;
     *      $a['native_date'] = $objEvent->BeginningEvent;
     *      $a['time'] = $objEvent->StartTime ?? '';
     *      $a['title'] = $objEvent->Title;
     *      $a['location'] = $objEvent->EventPlace;
     *      $a['update_text'] = ($objEvent->EventsChangesId && $objEvent->EventsChanges)
     *          ? (string)$objEvent->EventsChanges->Title
     *          : '';
     *      return $a;
     * }
     *
     * protected function Events_Bind(): void
     * {
     *      $this->objEvents->DataSource = EventsCalendar::queryArray(
     *          QQ::Equal(QQN::EventsCalendar()->Status, 1),
     *          QQ::Clause(
     *              QQ::OrderBy(QQN::EventsCalendar()->BeginningEvent, false), // false = DESC
     *              QQ::LimitInfo(5),
     *              QQ::Expand(QQN::EventsCalendar()->EventsChanges)
     *          )
     *      );
     * }
     *
     * protected function objEvents_Click(ActionParams $params): void
     * {
     *      Q\Project\Application::displayAlert($this->objEvents->EventId); (For testing purposes)
     * }
     */