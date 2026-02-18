<?php

    namespace QCubed\Plugin;

    /**
     * Represents a frontend panel for displaying news items.
     * Extends the functionality of the BindablePanel base class.
     * The panel integrates with a specified template for rendering.
     */
    class FrontendNewsPanel extends BindablePanel
    {
        protected string $strTemplate = 'FrontendNewsPanel.tpl.php';

        public array $DataSource = [];
    }