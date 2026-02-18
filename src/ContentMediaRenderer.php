<?php

    namespace QCubed\Plugin;

    use ContentCoverMedia;
    use Files;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\Plugin\NanoGalleryCover;
    use QCubed\Type;

    /**
     * ContentMediaRenderer
     *
     * Renders frontend HTML output depending on media_type_id:
     * 1 = Image
     * 2 = MiniGallery (NanoGalleryCover)
     * 3 = Video (iframe embed)
     *
     * Optional: show description + author under media box.
     *
     * @property string $TempUrl URL to the temp folder where uploaded files are stored.
     * @property int $MediaTypeId Media type ID.
     * @property int $ContentCoverMediaId Content cover media ID.
     * @property string $EmptyMediaUrl Fallback image URL to show when media is missing and RequireMedia=true.
     * @property string $EmptyMediaAlt Alt text for the fallback image.
     * @property bool $RequireMedia If true, show a fallback image when media is missing/invalid.
     * @property bool $InfoLabelShow Show description + author under the media box.
     * @property string $ItemsBaseURL ItemsBaseURL for NanoGalleryCover.
     * @property string $MultiImagesIconPath MultiImagesIconPath for NanoGalleryCover.
     * @property array $ViewerToolbar ViewerToolbar for NanoGalleryCover.
     * @property array $ViewerTools ViewerTools for NanoGalleryCover.
     * @property string $StartItem StartItem for NanoGalleryCover.
     *
     * @package Frontend
     */
    class ContentMediaRenderer extends Panel
    {
        /** @var string URL to the temp folder where uploaded files are stored. */
        protected string $strTempUrl = APP_UPLOADS_TEMP_URL;
        /** @var null|int Media type ID. */
        protected ?int $intMediaTypeId = null;
        /** @var null|int Content cover media ID. */
        protected ?int $intContentCoverMediaId = null;
        /** @var bool If true, show a fallback image when media is missing/invalid. */

        protected bool $blnRequireMedia = false;
        /** @var null|string Fallback image URL to show when media is missing and RequireMedia=true. */
        protected ?string $strEmptyMediaUrl = null;
        /** @var string Alt text for the fallback image. */
        protected string $strEmptyMediaAlt = 'No media';

        /** @var bool Show description + author under the media box. */
        protected bool $blnInfoLabelShow = false;
        /** @var null|ContentCoverMedia Cached cover a media object. */
        protected ?ContentCoverMedia $objCoverMedia = null;
        /** @var null|NanoGalleryCover NanoGalleryCover control. */
        protected ?NanoGalleryCover $objCover = null;

        /** @var null|string ItemsBaseURL for NanoGalleryCover. */
        protected ?string $strItemsBaseURL = null;
        /** @var null|string MultiImagesIconPath for NanoGalleryCover. */
        protected ?string $strMultiImagesIconPath = null;
        /** @var null|array ViewerToolbar for NanoGalleryCover. */
        protected ?array $arrViewerToolbar = null;
        /** @var null|array ViewerTools for NanoGalleryCover. */
        protected ?array $arrViewerTools = null;
        /** @var null|string StartItem for NanoGalleryCover. */
        protected ?string $strStartItem = null;

        /**
         * Constructor for initializing the control with a specified parent object and optional control ID.
         *
         * @param ControlBase|FormBase $objParentObject The parent object to which this control belongs.
         * @param string|null $strControlId Optional control ID. If not provided, a unique ID will be generated.
         *
         * @return void
         * @throws Caller
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            parent::__construct($objParentObject, $strControlId);

            $this->UseWrapper = false;
        }

        /**
         * Retrieves the cover media object associated with the current instance.
         *
         * @return ContentCoverMedia|null The cover media object if available, or null if not set.
         * @throws Caller
         */
        protected function getCoverMedia(): ?ContentCoverMedia
        {
            if (!$this->intContentCoverMediaId) {
                return null;
            }

            if (!$this->objCoverMedia) {
                $this->objCoverMedia = ContentCoverMedia::load($this->intContentCoverMediaId);
            }

            return $this->objCoverMedia;
        }

        /**
         * Renders information labels for content, including description and author details,
         * if they are provided.
         *
         * @param ContentCoverMedia $objCoverMedia An object containing the description and author data for the content.
         *
         * @return string The rendered HTML for the information labels, or an empty string if no description or author data is available.
         * @throws Caller
         */
        protected function renderInfoLabels(ContentCoverMedia $objCoverMedia): string
        {
            $desc = trim((string)$objCoverMedia->getDescription());
            $author = trim((string)$objCoverMedia->getAuthor());

            if ($desc === '' && $author === '') {
                return '';
            }

            $strHtml = _nl(_indent('<div class="content-media-info">', 1));

            if ($desc !== '') {
                $strHtml .= _nl(_indent('<span class="content-media-description">', 2));
                $strHtml .= _nl(_indent(htmlspecialchars($desc), 3));
                $strHtml .= _nl(_indent('</span>', 2));
            }

            if ($author !== '') {
                $strHtml .= _nl(_indent('<span class="content-media-author">', 2));
                $strHtml .= _nl(_indent(htmlspecialchars($author), 3));
                $strHtml .= _nl(_indent('</span>', 2));
            }

            return $strHtml;
        }

        /**
         * Renders an image within a media box container if a valid image file is available.
         *
         * @param ContentCoverMedia $objCoverMedia An object containing image file data.
         *
         * @return string The rendered HTML for the image, or an empty string if no valid image file is found.
         * @throws Caller
         */
        protected function renderImage(ContentCoverMedia $objCoverMedia): string
        {
            $objFile = Files::load($objCoverMedia->getPictureId());
            if (!$objFile) {
                return '';
            }

            $src = $this->strTempUrl . $objFile->getPath();

            $strHtml = '';
            $strHtml .= _nl(_indent('<div class="media-box">',1));
            $strHtml .= _nl(_indent('<img src="' . $src . '" alt="">',2));
            $strHtml .= _nl(_indent('</div>',1));

            return $strHtml;
        }

        /**
         * Renders a mini gallery within a media box container using the provided cover media data.
         *
         * @param ContentCoverMedia $objCoverMedia An object containing cover media data required for rendering the gallery.
         *
         * @return string The rendered HTML for the mini gallery.
         * @throws Caller
         */
        protected function renderMiniGallery(ContentCoverMedia $objCoverMedia): string
        {
            if (!$this->objCover) {
                $this->objCover = new NanoGalleryCover($this);
            }

            $this->objCover->ContentCoverMediaId = $objCoverMedia->getId();

            if ($this->strItemsBaseURL !== null) {
                $this->objCover->ItemsBaseURL = $this->strItemsBaseURL;
            }

            if ($this->strMultiImagesIconPath !== null) {
                $this->objCover->MultiImagesIconPath = $this->strMultiImagesIconPath;
            }

            if ($this->arrViewerToolbar !== null) {
                $this->objCover->ViewerToolbar = $this->arrViewerToolbar;
            }

            if ($this->arrViewerTools !== null) {
                $this->objCover->ViewerTools = $this->arrViewerTools;
            }

            if ($this->strStartItem !== null) {
                $this->objCover->StartItem = $this->strStartItem;
            }

            $strHtml = '';
            $strHtml .= _nl(_indent('<div class="media-box">',1));
            $strHtml .= _nl(_indent($this->objCover->render(false),2));
            $strHtml .= _nl(_indent('</div>',1));

            return $strHtml;
        }

        /**
         * Renders a video embed within a media box container if a valid embed code is provided.
         *
         * @param ContentCoverMedia $objCoverMedia An object containing video embed data.
         *
         * @return string The rendered HTML for the video embed, or an empty string if no embed code is available.
         * @throws Caller
         */
        protected function renderVideo(ContentCoverMedia $objCoverMedia): string
        {
            $embed = $objCoverMedia->getVideoEmbed();
            if ($embed === '') {
                return '';
            }

            $strHtml = _nl(_indent('<div class="media-box is-16x9">',1));
            $strHtml .= _nl(_indent($embed,2));
            $strHtml .= _nl(_indent('</div>',1));

            return $strHtml;
        }

        /**
         * Renders a placeholder media box when no primary media content is available.
         *
         * @return string The rendered HTML for the empty media placeholder, or an empty string if no valid URL is set.
         */
        protected function renderEmptyMedia(): string
        {
            if (!$this->strEmptyMediaUrl) {
                return '';
            }

            $src = htmlspecialchars($this->strEmptyMediaUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $alt = htmlspecialchars($this->strEmptyMediaAlt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $strHtml = _nl('<div class="content-media">');
            $strHtml .= _nl(_indent('<div class="media-box is-empty">', 1));
            $strHtml .= _nl(_indent('<img src="' . $src . '" alt="' . $alt . '">', 2));
            $strHtml .= _nl(_indent('</div>', 1));
            $strHtml .= _nl('</div>');

            return $strHtml;
        }

        /**
         * Generates the HTML representation of the control based on the media type and associated content.
         *
         * @return string The HTML string of the control. An empty string is returned if no media type is defined.
         *                or if there is no valid media content to render.
         * @throws Caller
         */
        protected function getControlHtml(): string
        {
            // If the frontpage requires media, and we have no type configured, show fallback (if provided).
            if (!$this->intMediaTypeId) {
                return $this->blnRequireMedia ? $this->renderEmptyMedia() : '';
            }

            $objCoverMedia = $this->getCoverMedia();
            if (!$objCoverMedia) {
                return $this->blnRequireMedia ? $this->renderEmptyMedia() : '';
            }

            $mediaHtml = '';

            switch ($this->intMediaTypeId) {
                case 1:
                    $mediaHtml = $this->renderImage($objCoverMedia);
                    break;

                case 2:
                    $mediaHtml = $this->renderMiniGallery($objCoverMedia);
                    break;

                case 3:
                    $mediaHtml = $this->renderVideo($objCoverMedia);
                    break;
            }

            if ($mediaHtml === '') {
                return '';
            }

            $strHtml = _nl('<div class="content-media">');
            $strHtml .= $mediaHtml;

            if ($this->blnInfoLabelShow) {
                $strHtml .= _nl($this->renderInfoLabels($objCoverMedia));
            }

            $strHtml .= _nl('</div>');

            return $strHtml;
        }

        /**
         * Magic method to retrieve property values dynamically.
         *
         * @param string $strName The name of the property being accessed.
         *
         * @return mixed The value of the requested property or the result from the parent::__get method if the
         *     property is not defined.
         * @throws Caller
         */
        public function __get(string $strName): mixed
        {
            return match ($strName) {
                'TempUrl' => $this->strTempUrl,
                'MediaTypeId' => $this->intMediaTypeId,
                'ContentCoverMediaId' => $this->intContentCoverMediaId,

                'RequireMedia' => $this->blnRequireMedia,
                'EmptyMediaUrl' => $this->strEmptyMediaUrl,
                'EmptyMediaAlt' => $this->strEmptyMediaAlt,

                'InfoLabelShow' => $this->blnInfoLabelShow,
                'ItemsBaseURL' => $this->strItemsBaseURL,
                'MultiImagesIconPath' => $this->strMultiImagesIconPath,
                'ViewerToolbar' => $this->arrViewerToolbar,
                'ViewerTools' => $this->arrViewerTools,
                'StartItem' => $this->strStartItem,
                default => parent::__get($strName),
            };
        }

        /**
         * Magic method to set the value of a property.
         *
         * This method handles the assignment of values to specific properties of the object.
         * If the property name matches one of the predefined cases, the value is cast to the appropriate type.
         * For unsupported properties, the parent implementation of `__set` is called.
         *
         * @param string $strName The name of the property being set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         * @throws Caller
         * @throws InvalidCast
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'TempUrl':
                    $this->strTempUrl = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'MediaTypeId':
                    $this->intMediaTypeId = Type::Cast($mixValue, Type::INTEGER);
                    break;

                case 'ContentCoverMediaId':
                    $this->intContentCoverMediaId = Type::Cast($mixValue, Type::INTEGER);
                    $this->objCoverMedia = null; // reset cached object
                    break;

                case 'RequireMedia':
                    $this->blnRequireMedia = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'EmptyMediaUrl':
                    $this->strEmptyMediaUrl = $mixValue === null ? null : Type::Cast($mixValue, Type::STRING);
                    break;

                case 'EmptyMediaAlt':
                    $this->strEmptyMediaAlt = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'InfoLabelShow':
                    $this->blnInfoLabelShow = Type::Cast($mixValue, Type::BOOLEAN);
                    break;

                case 'ItemsBaseURL':
                    $this->strItemsBaseURL = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'MultiImagesIconPath':
                    $this->strMultiImagesIconPath = Type::Cast($mixValue, Type::STRING);
                    break;

                case 'ViewerToolbar':
                    $this->arrViewerToolbar = Type::Cast($mixValue, Type::ARRAY_TYPE);
                    break;

                case 'ViewerTools':
                    $this->arrViewerTools = Type::Cast($mixValue, Type::ARRAY_TYPE);
                    break;

                case 'StartItem':
                    $this->strStartItem = Type::Cast($mixValue, Type::STRING);
                    break;

                default:
                    parent::__set($strName, $mixValue);
                    return;
            }

            $this->blnModified = true;
        }
    }

