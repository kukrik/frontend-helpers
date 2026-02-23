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