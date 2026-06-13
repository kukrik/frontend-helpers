<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;

    class FrontendSportsCalendarArchive extends Panel
    {
        protected ?int $intMenuContentId = null;
        protected ?int $intYear = null;

        protected array $arrYears = [];
        protected array $arrEvents = [];
        protected array $arrGroupedEvents = [];

        public function __construct(mixed $parentObject, ?string $controlId = null)
        {
            parent::__construct($parentObject, $controlId);
            $this->UseWrapper = false;
        }

        public function loadData(): void
        {
            if (!$this->intMenuContentId) {
                $this->arrYears = [];
                $this->arrEvents = [];
                $this->arrGroupedEvents = [];
                return;
            }

            $this->arrYears = $this->loadYears();
            $this->arrEvents = $this->loadEvents();
            $this->arrGroupedEvents = $this->buildGroupedEvents($this->arrEvents);
        }

        public function hasEvents(): bool
        {
            return !empty($this->arrEvents);
        }

        public function getYears(): array
        {
            return $this->arrYears;
        }

        public function getEvents(): array
        {
            return $this->arrEvents;
        }

        public function getGroupedEvents(): array
        {
            return $this->arrGroupedEvents;
        }

        protected function loadYears(): array
        {
            return [];
        }

        protected function loadEvents(): array
        {
            return [];
        }

        protected function buildGroupedEvents(array $events): array
        {
            return [];
        }

        public function __get(string $name): mixed
        {
            switch ($name) {
                case 'MenuContentId':
                    return $this->intMenuContentId;

                case 'Year':
                    return $this->intYear;

                case 'Years':
                    return $this->arrYears;

                case 'Events':
                    return $this->arrEvents;

                case 'GroupedEvents':
                    return $this->arrGroupedEvents;

                default:
                    return parent::__get($name);
            }
        }

        public function __set(string $name, mixed $value): void
        {
            switch ($name) {
                case 'MenuContentId':
                    $this->intMenuContentId = $value ? (int)$value : null;
                    $this->blnModified = true;
                    break;

                case 'Year':
                    $this->intYear = $value ? (int)$value : null;
                    $this->blnModified = true;
                    break;

                default:
                    parent::__set($name, $value);
            }
        }
    }