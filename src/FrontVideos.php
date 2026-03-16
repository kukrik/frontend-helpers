<?php

    /** This file contains the FrontVideos Class */

    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\Control\FormBase;
    use QCubed\Control\ControlBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\DataBind;
    use QCubed\Exception\InvalidCast;
    use QCubed\Type;

    /**
     * Class FrontVideos
     *
     * Lightweight frontend control for rendering the latest published video.
     *
     * Expected DataSource format:
     * [
     *     [
     *         'embed' => '<iframe ...></iframe>',
     *         'post_date' => '2026-03-07 10:00:00'
     *     ]
     * ]
     *
     * @property string $SectionTitle
     * @property string $EmptyText
     * @property mixed $DataSource
     *
     * @package QCubed\Plugin
     */
    class FrontVideos extends ControlBase
    {
        use Q\Control\DataBinderTrait;

        /** @var string */
        protected string $strSectionTitle = 'Last added video';

        /** @var string */
        protected string $strEmptyText = 'There is currently no video.';

        /** @var array */
        protected array $objDataSource = [];

        /**
         * @param ControlBase|FormBase $objParentObject
         * @param string|null $strControlId
         * @throws \Exception
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            try {
                parent::__construct($objParentObject, $strControlId);
            } catch (Caller $objExc) {
                $objExc->incrementOffset();
                throw $objExc;
            }

            $this->UseWrapper = false;
        }

        /**
         * @return bool
         */
        public function validate(): bool
        {
            return true;
        }

        /**
         * @return void
         */
        public function parsePostData(): void
        {
        }

        /**
         * @return string
         * @throws Caller
         * @throws \Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();

            $strOut = $this->renderVideos($this->objDataSource);
            $strHtml = $this->renderTag('span', null, null, $strOut);

            // Reset after render to avoid an accidental stale state.
            $this->objDataSource = [];

            return $strHtml;
        }

        /**
         * @return void
         * @throws Caller
         * @throws DataBind
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

        /**
         * @param array $arrDataSource
         * @return string
         */
        protected function renderVideos(array $arrDataSource): string
        {
            $strHtml = '';

            $strHtml .= _nl(_indent('<h2>' . $this->escapeString($this->strSectionTitle) . '</h2>', 1));

            if (empty($arrDataSource) || empty($arrDataSource[0]['embed'])) {
                $strHtml .= _nl(_indent('<div class="event-item">', 1));
                $strHtml .= _nl(_indent('<div class="no-events">', 2));
                $strHtml .= _nl(_indent($this->escapeString($this->strEmptyText), 3));
                $strHtml .= _nl(_indent('</div>', 2));
                $strHtml .= _nl(_indent('</div>', 1));

                return $strHtml;
            }

            $strEmbed = trim((string)$arrDataSource[0]['embed']);

            $strHtml .= _nl(_indent('<div class="content-media">', 1));
            $strHtml .= _nl(_indent('<div class="media-box is-16x9">', 2));
            $strHtml .= _nl(_indent($strEmbed, 3));
            $strHtml .= _nl(_indent('</div>', 2));
            $strHtml .= _nl(_indent('</div>', 1));

            return $strHtml;
        }

        /**
         * @param string|null $s
         * @return string
         */
        private function escapeString(?string $s): string
        {
            return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        /**
         * @param string $strName
         * @return mixed
         * @throws Caller
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case 'SectionTitle':
                    return $this->strSectionTitle;

                case 'EmptyText':
                    return $this->strEmptyText;

                case 'DataSource':
                    return $this->objDataSource;

                default:
                    try {
                        return parent::__get($strName);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }

        /**
         * @param string $strName
         * @param mixed $mixValue
         * @return void
         * @throws Caller
         * @throws InvalidCast
         * @throws \Exception
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'SectionTitle':
                    try {
                        $this->blnModified = true;
                        $this->strSectionTitle = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;

                case 'EmptyText':
                    try {
                        $this->blnModified = true;
                        $this->strEmptyText = Type::cast($mixValue, Type::STRING);
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;

                case 'DataSource':
                    $this->objDataSource = is_array($mixValue) ? $mixValue : [];
                    $this->blnModified = true;
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }
    }
