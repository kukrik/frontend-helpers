<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Control\DataBinderTrait;
    use QCubed\Exception\Caller;
    use QCubed\Exception\DataBind;

    /**
     * An abstract class that extends the Panel class and incorporates data-binding functionality through the DataBinderTrait.
     */
    abstract class BindablePanel extends Panel
    {
        use DataBinderTrait;

        /**
         * Run DataBinder before rendering the template.
         *
         * @return string The generated HTML string for the control.
         * @throws Caller
         * @throws DataBind
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();
            return parent::getControlHtml();
        }

        /**
         * Binds data to the control using the assigned DataBinder if available and not already rendered.
         *
         * @return void
         * @throws Caller If an exception occurs within the DataBinder.
         */
        public function dataBind(): void
        {
            if ($this->hasDataBinder() && !$this->blnRendered) {
                try {
                    $this->callDataBinder();
                } catch (Caller $objExc) {
                    $objExc->incrementOffset();
                    throw $objExc;
                }
            }
        }
    }