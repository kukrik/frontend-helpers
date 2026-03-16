<?php

    namespace QCubed\Plugin;

    use QCubed\Control\BlockControl;
    use QCubed\Control\Label as QLabel;

    /**
     * Class Label
     *
     * Converts\QCubed\Control\Label to a drawing boot strategy according to the client's a desired theme.
     * @package QCubed\Plugin
     */
    class Label extends QLabel
    {
        protected string $strCssClass = "control-label";
        protected string $strTagName = "label";
        protected bool $blnRequired = false;

        /**
         * Retrieves the inner HTML content with an optional required indicator.
         *
         * @return string The inner HTML content, including a required indicator if applicable.
         */
        protected function getInnerHtml(): string
        {
            $strToReturn = BlockControl::getInnerHtml();
            if ($this->blnRequired) {
                $strToReturn = $strToReturn . '<span class="required" aria-required="true"> * </span>';
            }
            return $strToReturn;
        }
    }