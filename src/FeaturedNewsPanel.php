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

    /**
     * EXAMPLE of using FeaturedNewsPanel and FrontendNewsPanel
     *
     * $this->pnlFeatured = new FeaturedNewsPanel($this);
     * $this->pnlOld = new FrontendNewsPanel($this);
     *
     * $this->pnlFeatured->setDataBinder('FeaturedNews_Bind', $this);
     * $this->pnlOld->setDataBinder('FrontendNews_Bind', $this);
     *
     * protected function FeaturedNews_Bind(FeaturedNewsPanel $pnl): void
     * {
     *      $pnl->DataSource = News::loadFrontPageNews(1, 0);
     * }
     *
     * protected function FrontendNews_Bind(FrontendNewsPanel $pnl): void
     * {
     *      $pnl->DataSource = News::loadFrontPageNews(6, 1);
     * }
     *
     */