<?php

    /** This file contains the Sidebar Class */

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
     * Class YearSidebar
     *
     * @property string $WrapperClass
     * @property string $NavLabel
     * @property string $TagName
     * @property mixed $DataSource
     * @property int $Limit
     * @property string $MoreLabel
     * @property string $ResetLabel
     * @property bool $ShowMore
     * @property int|null $ActiveYear
     * @property int|null $Year
     *
     * @package QCubed\Plugin
     */
    class YearSidebar extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        /** @var string WrapperClass */
        protected string $strWrapperClass = 'nested-sidebar';
        /** @var string SubTagClass */
        protected string $strNavLabel = 'Side menu years';
        /** @var string SubTagName */
        protected string $strTagName = 'ul';

        /** Show N years at the beginning (the rest hidden until "See more...") */
        protected int $intLimit = 5;
        protected bool $blnShowMore = true;
        protected string $strMoreLabel = 'See more...';
        protected string $strResetLabel = 'Back to the start';

        protected ?int $intActiveYear = null;
        protected ?int $intYear = null;

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
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL . "/css/sidebar.css");
            $this->addJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/js/sidebar.js");
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

            $intYear = '';
            if (isset($params['year'])) {
                $intYear = $params['year'];
            }
            $intStatus = '';
            if (isset($params['status'])) {
                $intStatus = $params['status'];
            }

            return [
                'year' => $intYear,
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

            $strOut = $this->renderYears($this->strParams);
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
         * Renders HTML for a sidebar menu displaying a list of years based on the provided parameters.
         * Ignores entries with specific statuses and generates HTML elements accordingly.
         *
         * @param array $arrParams An array of parameters, where each element contains 'year' and 'status' keys.
         *
         * @return string A string containing the generated HTML for the sidebar menu.
         */
        protected function renderYears(array $arrParams): string
        {
            // 1) normalize: filter + dedupe + sort DESC
            $years = [];
            foreach ($arrParams as $node) {
                $year = isset($node['year']) ? (int)$node['year'] : 0;
                $status = isset($node['status']) ? (int)$node['status'] : 0;

                if ($year <= 0) {
                    continue;
                }
                if ($status === 2 || $status === 3) {
                    continue;
                }

                $years[$year] = $year;
            }
            rsort($years);
            $years = array_values($years);

            $limit = max(1, $this->intLimit);

            $strHtml = _nl(_indent('<nav class="sidebar-nav" aria-label="' . $this->strNavLabel . '">',1));
            $strHtml .= _nl(_indent('<' . $this->strTagName . ' class="sidebar-menu" role="list">', 2));

            $total = count($years);
            $activeYear = $this->intActiveYear ?? $this->intYear;

            for ($i = 0; $i < $total; $i++) {
                $year = (int)$years[$i];

                // Visibility:
                // - 0..limit-1 (the newest block) always visible
                // - the rest hidden until JS shows "parent block" by "parent block"
                $isHidden = ($i >= $limit);

                $liClass = 'sidebar-item year-item';
                if ($isHidden) {
                    $liClass .= ' is-hidden';
                }

                $linkClass = 'sidebar-link';
                if ($activeYear !== null && $year === $activeYear) {
                    $linkClass .= ' is-active';
                }

                $strHtml .= _nl(_indent('<li class="' . $liClass . '" data-index="' . $i . '">', 3));
                $strHtml .= _nl(_indent(
                    '<a class="' . $linkClass . '" data-year="' . $year . '" href="?year=' . $year . '">' . $year . '</a>',
                    4
                ));
                $strHtml .= _nl(_indent('</li>', 3));
            }

            // "See more..." only appears if there is something older than the latest block
            if ($this->blnShowMore && $total > $limit) {
                $strHtml .= _nl(_indent('<li class="sidebar-item sidebar-more">', 3));
                $strHtml .= _nl(_indent(
                    '<a class="sidebar-link sidebar-more-link" href="#" data-action="show-more">' . $this->strMoreLabel . '</a>',
                    4
                ));
                $strHtml .= _nl(_indent('</li>', 3));
            }

            $strHtml .= _nl(_indent('</' . $this->strTagName . '>', 2));
            $strHtml .= _nl(_indent('</nav>', 1));

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

            $limit = max(1, $this->intLimit);
            $moreLabel = $this->strMoreLabel ?: 'See more...';
            $resetLabel = $this->strResetLabel ?: 'Back to the start';

            $strCtrlJs = <<<FUNC
(function() {
  var root = document.getElementById("$this->ControlId");
  if (!root) { return; }

  root.dataset.page = root.dataset.page || "0";

  function scrollToRoot() {
    try {
      root.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
    } catch (e) {
      root.scrollIntoView(true);
    }
  }

  function getItems() {
    return root.querySelectorAll(".year-item");
  }

  function getMoreLink() {
    return root.querySelector(".sidebar-more-link");
  }

  function setMoreMode(isResetMode) {
    var link = getMoreLink();
    if (!link) { return; }
    link.dataset.mode = isResetMode ? "reset": "more";
    link.textContent = isResetMode ? {$this->escapeJsString($resetLabel)} : {$this->escapeJsString($moreLabel)};
  }

  function getActiveIndex() {
    var active = root.querySelector(".sidebar-link.is-active");
    if (!active) { return null; }
    var li = active.closest ? active.closest(".year-item") : null;
    if (!li) { return null; }
    var idx = parseInt(li.getAttribute("data-index"), 10);
    return isNaN(idx) ? null : idx;
  }

  function getNewestYear() {
    var first = root.querySelector(".sidebar-link[data-year]");
    if (!first) { return null; }
    var y = parseInt(first.getAttribute("data-year"), 10);
    return isNaN(y) ? null : y;
  }

    function setYearAndTrigger(year) {
    if (year === null || isNaN(year)) { return; }

    try {
      var url = new URL(window.location.href);
      url.searchParams.set("year", String(year));
      window.history.replaceState(window.history.state || {}, "", url.toString());
    } catch (e) {
      // ignore URL update if unsupported
    }

    if (window.qcubed && typeof window.qcubed.recordControlModification === "function") {
      window.qcubed.recordControlModification(root.id, "_Year", year);
    }
    if (window.jQuery) {
      window.jQuery(root).trigger("selectyear");
    }
  }

  function showOlderChunk(page) {
    var items = getItems();
    var total = items.length;
    var perPage = $limit;

    var start = perPage + (page * perPage);
    var end = start + perPage - 1;

    items.forEach(function(li) {
      var idx = parseInt(li.getAttribute("data-index"), 10);
      var keepNewest = idx < perPage;
      var inOlderWindow = (idx >= start && idx <= end);

      if (keepNewest || inOlderWindow) {
        li.classList.remove("is-hidden");
      } else {
        li.classList.add("is-hidden");
      }
    });

    var nextStart = start + perPage;
    setMoreMode(nextStart >= total);
  }

  function resetToStart(triggerNewest) {
    var items = getItems();
    var perPage = $limit;

    items.forEach(function(li) {
      var idx = parseInt(li.getAttribute("data-index"), 10);
      if (idx < perPage) {
        li.classList.remove("is-hidden");
      } else {
        li.classList.add("is-hidden");
      }
    });

    root.dataset.page = "0";
    setMoreMode(false);

    if (triggerNewest) {
      var newest = getNewestYear();
      var active = root.querySelector(".sidebar-link.is-active");
      var activeYear = active ? parseInt(active.getAttribute("data-year"), 10) : null;

      if (newest !== null && (activeYear === null || isNaN(activeYear) || activeYear !== newest)) {
        setYearAndTrigger(newest);
      }
    }
  }

  function ensureActiveVisible() {
    var activeIdx = getActiveIndex();
    if (activeIdx === null) { return; }

    var perPage = $limit;
    if (activeIdx < perPage) {
      root.dataset.page = "0";
      setMoreMode(false);
      return;
    }

    var page = Math.floor((activeIdx - perPage) / perPage);
    showOlderChunk(page);
    root.dataset.page = String(page + 1);
  }

  function ensureDefaultActive() {
    var hasActive = root.querySelector(".sidebar-link.is-active");
    if (hasActive) { return; }

    var newestLink = root.querySelector(".sidebar-link[data-year]");
    if (newestLink) {
      newestLink.classList.add("is-active");
    }
  }

  setMoreMode(false);
  ensureDefaultActive();
  ensureActiveVisible();


  root.addEventListener("click", function(e) {
    var a = e.target && e.target.closest ? e.target.closest("a") : null;
    if (!a) { return; }

    var action = a.getAttribute("data-action");

    if (action === "show-more") {
      e.preventDefault();

      var mode = a.dataset.mode || "more";
      if (mode === "reset") {
        resetToStart(true);
        scrollToRoot();
        return;
      }

      var page = parseInt(root.dataset.page || "0", 10);
      if (isNaN(page)) { page = 0; }

      showOlderChunk(page);
      root.dataset.page = String(page + 1);
      scrollToRoot();
      return;
    }

    if (a.classList.contains("sidebar-link") && a.hasAttribute("data-year") && !action) {
      var year = parseInt(a.getAttribute("data-year"), 10);
      if (isNaN(year)) { return; }

      e.preventDefault();
      setYearAndTrigger(year);
    }
  });
})();
FUNC;

            Application::executeJavaScript($strCtrlJs, ApplicationBase::PRIORITY_HIGH);

            return $strJS;
        }


        /**
         * Escapes a given string to make it safe for use in JavaScript by encoding it as a JSON string.
         *
         * @param string $s The input string to be escaped.
         *
         * @return string The escaped string, formatted as a JSON-encoded value.
         */
        private function escapeJsString(string $s): string
        {
            return json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
                case "NavLabel": return $this->strNavLabel;
                case "TagName": return $this->strTagName;
                case "DataSource": return $this->objDataSource;

                case "Limit": return $this->intLimit;
                case "MoreLabel": return $this->strMoreLabel;
                case "ShowMore": return $this->blnShowMore;
                case "ResetLabel": return $this->strResetLabel;
                case "ActiveYear": return $this->intActiveYear;
                case "Year": return $this->intYear;

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

                case "NavLabel":
                    try {
                        $this->blnModified = true;
                        $this->strNavLabel = Type::cast($mixValue, Type::STRING);
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

                case "Limit":
                    try {
                        $this->blnModified = true;
                        $this->intLimit = max(0, Type::cast($mixValue, Type::INTEGER));
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                case "MoreLabel":
                    try {
                        $this->blnModified = true;
                        $this->strMoreLabel = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                case "ResetLabel":
                    try {
                        $this->blnModified = true;
                        $this->strResetLabel = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                case "ShowMore":
                    $this->blnModified = true;
                    $this->blnShowMore = (bool)$mixValue;
                    break;

                case "ActiveYear":
                    try {
                        $this->blnModified = true;
                        $this->intActiveYear = $mixValue === null ? null : Type::cast($mixValue, Type::INTEGER);
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                    break;

                case "_Year": // Internal only. Do not use. Used by JS to track selections.
                    try {
                        $this->intYear = Type::cast($mixValue, Type::INTEGER);
                        $this->intActiveYear = $this->intYear;
                        $this->blnModified = true;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;

                case "Year": // optional: if you want to set it manually from the server as well
                    try {
                        $this->intYear = $mixValue === null ? null : Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
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
     * EXAMPLE of using YearSidebar
     *
     * $this->tblYearSidebar = new Q\Plugin\Control\YearSidebar($this);
     * $this->tblYearSidebar->setDataBinder('Years_Bind');
     * $this->tblYearSidebar->createNodeParams([$this, 'Years_Draw']);
     * $this->tblYearSidebar->MoreLabel = 'Vaata veel...';
     * $this->tblYearSidebar->ResetLabel = 'Tagasi algusesse';
     * $this->tblYearSidebar->addAction(new Q\Plugin\Event\SelectYear(), new Q\Action\Ajax('updateArchiveList_Click'));
     *
     * public function Years_Draw(Years $objYear): array
     * {
     *      $a['year'] = $objYear->Year;
     *      $a['status'] = $objYear->StatusId;
     *      return $a;
     * }
     *
     * protected function Years_Bind(): void
     * {
     *      $this->tblYearSidebar->DataSource = Years::queryArray(
     *          QQ::all(),
     *          [
     *              QQ::groupBy(QQN::years()->Year),
     *              QQ::orderBy(QQN::years()->Year, false) // DESC
     *          ]
     *      );
     * }
     *
     * protected function updateArchiveList_Click(ActionParams $params): void
     * {
     *  Q\Project\Application::displayAlert($this->tblYearSidebar->Year); (For testing purposes)
     * }
     */