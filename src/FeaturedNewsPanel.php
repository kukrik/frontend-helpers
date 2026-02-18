<?php

    namespace QCubed\Plugin;

    /**
     * Represents a panel that displays featured news items.
     * Extends the BindablePanel to provide a templated and bindable structure.
     *
     * @property string $strTemplate Specifies the template file used for rendering the panel.
     * @property array $DataSource Contains an array of News objects to be displayed in the panel.
     */
    class FeaturedNewsPanel extends BindablePanel
    {
        protected string $strTemplate = 'FeaturedNewsPanel.tpl.php';

        public array $DataSource = [];
    }