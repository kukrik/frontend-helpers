<?php

    namespace QCubed\Plugin\Event;

    use QCubed\Event\EventBase;

    /**
     * Represents the SelectEvent event class that extends from the EventBase class.
     * This class defines constants for event name and JavaScript return parameter.
     */

    class SelectEvent extends EventBase
    {
        public const string EVENT_NAME = 'selectevent';
        public const string JS_RETURN_PARAM = 'ui';
    }