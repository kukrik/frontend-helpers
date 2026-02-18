<?php
    namespace QCubed\Plugin;

    use QCubed as Q;
    use QCubed\Control\Panel;
    use QCubed\Control\ControlBase;
    use QCubed\Control\FormBase;
    use QCubed\Exception\Caller;
    use QCubed\Exception\DataBind;
    use QCubed\Exception\InvalidCast;
    use Exception;
    use QCubed\Type;

    /**
     * Class NanoGallery
     *
     * If you want to will be overwritten when you update QCubed. To override, make your changes
     * to the NanoGallery.class.php file instead.
     *
     * Plugin "nanogallery2" is the tool of choice for beautiful galleries with eye-catching effects,
     * and user-friendly lightbox for images and videos.
     *
     * Note: Video deployment is not covered here. If desired, the videos need to be developed further.
     *
     * NanoGallery takes full control over gallery and thumbnail design, display animations and hover/touch effects.
     *
     * ### GALLERY SETTINGS ###
     * @property string $ItemsBaseURL Default ''. A global path to media (big images, thumbnail images, videos).
     *                                If defined, the value will be added to the URLs of medias, which don't start with
     *                                a protocol (HTTP, HTTPS, ...).
     *                                Example: itemsBaseURL: 'https://nanogallery2.nanostudio.org/samples/'
     *
     * @property string $GalleryDisplayMode Default 'fullContent'. Define how many thumbnails should be displayed in the gallery.
     * @property integer $GalleryMaxRows Default 2. Maximum number of rows to display. Only for galleryDisplayMode: 'rows'.
     *                                   Not supported in cascading layout.
     * @property integer $GalleryMaxItems Default 0. Maximum number of items per an album. 0 = displays all.
     * @property string $GalleryDisplayTransition Default 'none'. Transition for displaying the gallery. Applied on the
     *                                   whole gallery. Possible values: 'none', 'rotateX', 'slideUp'.
     * @property integer $GalleryDisplayTransitionDuration Default 1000. Duration of the gallery display transition, in milliseconds.
     *
     * ### THE THUMBNAIL LAYOUT ###
     * @property integer $ThumbnailWidth Default 300. Thumbnails image width in pixels, or 'auto'. Use 'auto' for
     *                                   a gallery with justified layout.
     * @property integer ThumbnailHeight Default 200. Thumbnails image height in pixels, or 'auto'. Use 'auto' for
     *                                   a gallery with cascading/masonry layout.
     * @property string $ThumbnailAlignment Default 'fillWidth'. Horizontal thumbnail alignment in the available width.
     *                                   This option is ignored for the justified layout.
     *                                   Possible values: left, right, center, justified (property thumbnailGutterWidth ignored),
     *                                   fillWidth (thumbnails are downscaled to use the full available width).
     * @property integer $ThumbnailGutterWidth Default 2. Sets the horizontal and vertical gutter space between thumbnails.
     * @property integer $ThumbnailGutterHeight
     * @property integer $ThumbnailBorderVertical Default 2. Set the horizontal (left and right) / vertical (top and bottom) thumbnail border
     * @property integer $ThumbnailBorderHorizontal
     *
     * ### THUMBNAIL LABELS ###
     * @property array $ThumbnailLabel The label is composed of a title and a description. Set these settings with the thumbnailLabel property.
     *                                  String 'position': 'overImageOnBottom'. Position of the label on the thumbnail.
     *                                  Possible values: 'overImage', 'onBottom'
     *                                  Depreciated values (v3+): 'overImageOnBottom', 'overImageOnTop', 'overImageOnMiddle'
     *                                  String 'align': 'center'. Horizontal text alignment.
     *                                  Possible values: 'right', 'center', 'left'
     *                                  String 'valign': 'bottom'. Vertical text alignment.
     *                                  Possible values: 'top', 'middle', 'bottom'
     *                                  Boolean 'display': true. Displays or hides the label (title and description).
     *                                  Boolean 'hideIcons': true. Hides or displays the icons besides the title.
     *                                  Integer 'titleMaxLength': 0. Title maximum length to display.
     *                                  Boolean 'titleMultiLine': false. Title can be multiline (not supported with position:'onBottom').
     *                                  String 'title': null. Variable to set the image title (underscores are replaced by spaces).
     *                                  Possible values:
     *                                                  - '%filename': use the filename without a path-'% * filenameNoExt': use the filename without a path and without extension
     *                                  String 'titleFontSize': null. Set the title font size. Example: titleFontSize: '2em'
     *                                  Boolean 'displayDescription': false. Displays or hides the description.
     *                                  Integer 'descriptionMaxLength': 0. Description maximum length to display.
     *                                  Boolean 'descriptionMultiLine': false. Description can be multiline (not supported with position:'onBottom').
     *                                  String 'descriptionFontSize': null. Set the description font size.
     *                                                              Example: descriptionFontSize: '0.8em'
     *
     *
     *
     * ### THUMBNAIL DISPLAY ANIMATION ###
     * @property string $ThumbnailDisplayTransition Default 15. Interval in ms between the display of 2 thumbnails (in ms).
     * @property string $ThumbnailDisplayOrder Default ''. Thumbnail's display order. Possible values: '', 'random'.
     * @property integer $ThumbnailDisplayTransitionDuration Default 'fadeIn'. Transition used to display each individual thumbnail.
     *
     * ### LIGHTBOX: MEDIA DISPLAY ###
     * ### General settings ###
     * @property string $ImageTransition Default 'swipe2'. Display transition from one media to the next one.
     *                                  Possible values: 'slideAppear', 'swipe', 'swipe2'.
     * @property boolean $SlideshowAutoStart Default false. Automatically starts the slideshow when a media is displayed in lightbox.
     * @property integer $SlideshowDelay Default 3000. Duration of the photo display in slideshow mode (in ms).
     *                                  The delay starts when an image is fully downloaded.
     * @property integer $ViewerHideToolsDelay Default 4000. Delay of inactivity before hiding tools and labels.
     *                                  Use value -1 to disable this feature.
     * @property boolean $ViewerFullscreen Default false. Displays the lightbox directly in fullscreen (on a supported browser).
     *
     * ### Lightbox toolbar ###
     * @property array $ViewerToolbar Display options for the lightbox main toolbar.
     *                                 Boolean 'display': false. Displays/hides the main toolbar.
     *                                 String 'position': 'bottom'. Vertical position. Possible values: 'top', 'bottom'.
     *                                 String 'align': 'center'. Horizontal alignment. Possible values: 'left', 'right', 'center'.
     *                                 Boolean 'fullWidth': false. Toolbar is as width as the screen.
     *                                 Integer 'autoMinimize': 800. Breakpoint (in pixels) for switching between minimized
     *                                          and standard toolbar. If the width is lower than this value, the toolbar is
     *                                          switched to minimize.
     *                                 String 'standard'. List of items (tools/labels) to display in the standard toolbar
     *                                          (comma separated). For this toolbar, 'minimizeButton' is additionally available.
     *                                          Default value: viewerToolbar: { standard: 'minimizeButton, label'}
     *                                 String 'minimized'. List of items to display in the minimized toolbar (comma separated).
     *                                          For this toolbar, 'minimizeButton' is additionally available.
     *                                          Default value: viewerToolbar: { minimized: 'minimizeButton, label, fullscreenButton'}
     * ### Lightbox tools ###
     * @property array $ViewerTools Tools in the top corners of the lightbox, over the media.
     *                               String 'topLeft'. Toolbar positioned in the top left corner. Default value:
     *                                      viewerTools : { topLeft: 'pageCounter, playPauseButton'}
     *                               String 'topRight'. Toolbar positioned in the top right corner. Default value:
     *                                      viewerTools : { topRight: 'rotateLeft, rotateRight, zoomButton, closeButton'}.
     *                                      viewerTools: {playPauseButton, zoomButton, rotateLeftButton, rotateRightButton,
     *                                                    fullscreenButton, shareButton, downloadButton, closeButton'}.
     *
     *                              Possible tools: 'previousButton', 'nextButton', 'rotateLeft', 'rotateRight', 'pageCounter',
     *                                              'playPauseButton', 'fullscreenButton', 'infoButton', 'linkOriginalButton',
     *                                               'closeButton', 'downloadButton', 'zoomButton', 'shareButton', 'label'
     *                                               (image title and description), 'shoppingcart', 'customN'To add custom
     *                                               elements in a toolbar, use the label customN, where N is an integer (e.g.
     *                                               custom1, custom2...).
     *
     * @property boolean $LocationHash Default: true. Enables hash tracking. This will activate the browser Back/Forward navigation
     *                                  (browser history support) and Deep Linking of images and photo albums.
     *                                  Must be enabled to allow sharing of images/albums.
     *                                  Note: only one gallery per HTML page should use this feature.
     *
     * @property boolean $AllowHTMLinData Default: false. To enable HTML tags, set the option allowHTMLinData: true.
     *                                  Be aware that this could lead to XSS (cross-site scripting) vulnerability.
     *
     * @see https://nanogallery2.nanostudio.org/documentation.html
     * @link https://nanogallery2.nanostudio.org/ or https://github.com/nanostudio-org/nanogallery2
     *
     * @property array $DataSource
     * @property string $TempUrl
     * @property string $ListTitle
     * @property string $ListDescription
     * @property string $ListAuthor
     *
     * @package QCubed\Plugin
     */

    class NanoGallery extends Panel
    {
        use Q\Control\DataBinderTrait;


        /** @var null|string */
        protected ?string $strItemsBaseURL = null;
        /** @var null|string */
        protected ?string $strGalleryDisplayMode = null;
        /** @var null|integer */
        protected ?int $intGalleryMaxRows = null;
        /** @var null|integer */
        protected ?int $intGalleryMaxItems = null;
        /** @var null|string */
        protected ?string $strGalleryDisplayTransition = null;
        /** @var null|integer */
        protected ?int $intGalleryDisplayTransitionDuration = null;

        /** @var null|integer */
        protected ?int $intThumbnailWidth = null;
        /** @var null|integer */
        protected ?int $intThumbnailHeight = null;
        /** @var null|string */
        protected ?string $strThumbnailAlignment = null;
        /** @var null|integer */
        protected ?int $intThumbnailGutterWidth = null;
        /** @var null|integer */
        protected ?int $intThumbnailGutterHeight = null;
        /** @var null|integer */
        protected ?int $intThumbnailBorderVertical = null;
        /** @var null|integer */
        protected ?int $intThumbnailBorderHorizontal = null;

        /** @var null|string */
        protected ?string $strImageTransition = null;
        /** @var boolean */
        protected ?bool $blnSlideshowAutoStart = null;
        /** @var null|integer */
        protected ?int $intSlideshowDelay = null;
        /** @var null|integer */
        protected ?int $intViewerHideToolsDelay = null;
        /** @var boolean */
        protected ?bool $blnViewerFullscreen = null;
        /** @var null|array */
        protected ?array $arrThumbnailLabel = null;
        /** @var null|array */
        protected ?array $arrViewerToolbar = null;
        /** @var null|array */
        protected ?array $arrViewerTools = null;

        /** @var boolean */
        protected ?bool $blnLocationHash = null;
        /** @var boolean */
        protected ?bool $blnAllowHTMLinData = null;

        /** @var string */
        protected string $strTempUrl = APP_UPLOADS_TEMP_URL;
        /** @var array DataSource, from which the items are picked and rendered */
        protected array $objDataSource;
        protected array $strParams;
        /** @var  callable */
        protected mixed $nodeParamsCallback = null;

        /**
         * Constructor for initializing the control object with a parent object and an optional control ID.
         *
         * @param ControlBase|FormBase $objParentObject The parent object that will contain this control.
         * @param string|null $strControlId An optional ID for the control. If null, an ID will be auto-generated.
         *
         * @return void
         * @throws \Exception
         * @throws Caller If there is an issue during the parent constructor call.
         */
        public function __construct(ControlBase|FormBase $objParentObject, ?string $strControlId = null)
        {
            try {
                parent::__construct($objParentObject, $strControlId);
            } catch (Caller  $objExc) {
                $objExc->incrementOffset();
                throw $objExc;
            }
            $this->registerFiles();
        }

        /**
         * Registers the necessary JavaScript and CSS files for nanoGallery2 and Bootstrap.
         *
         * This method includes the jQuery nanoGallery2 JavaScript file, the nanoGallery2 CSS file,
         * and the Bootstrap CSS file into the application's resources.
         *
         * @return void
         * @throws Caller
         */
        protected function registerFiles(): void
        {
            $this->AddJavascriptFile(FRONTEND_HELPERS_ASSETS_URL . "/nanogallery2/dist/jquery.nanogallery2.js");
            $this->addCssFile(FRONTEND_HELPERS_ASSETS_URL. "/nanogallery2/src/css/nanogallery2.css");
            $this->AddCssFile(QCUBED_BOOTSTRAP_CSS); // make sure they know
        }

        /**
         * Sets the callback function to generate node parameters.
         *
         * @param callable $callback A callback function that defines how to generate node parameters.
         * @return void
         */
        public function createNodeParams(callable $callback): void
        {
            $this->nodeParamsCallback = $callback;
        }

        /**
         * Retrieves and processes item parameters using a callback function to structure specific details into a result array.
         *
         * @param mixed $objItem The item to pass to the callback function for processing.
         *
         * @return array An associative array containing processed parameters such as list description, list author, path, description, author, and status.
         * @throws Exception If the nodeParamsCallback is not provided.
         */
        public function getItem(mixed $objItem): array
        {
            if (!$this->nodeParamsCallback) {
                throw new Exception("Must provide a nodeParamsCallback");
            }
            $params = call_user_func($this->nodeParamsCallback, $objItem);

            $strListDescription = '';
            if (isset($params['list_description'])) {
                $strListDescription = $params['list_description'];
            }
            $strListAuthor = '';
            if (isset($params['list_author'])) {
                $strListAuthor = $params['list_author'];
            }
            $strPath = '';
            if (isset($params['path'])) {
                $strPath = $params['path'];
            }
            $strDescription = '';
            if (isset($params['description'])) {
                $strDescription = $params['description'];
            }
            $strAuthor = '';
            if (isset($params['author'])) {
                $strAuthor = $params['author'];
            }
            $intStatus = '';
            if (isset($params['status'])) {
                $intStatus = $params['status'];
            }

            return [
                'list_descripton' => $strListDescription,
                'list_author' => $strListAuthor,
                'path' => $strPath,
                'descripton' => $strDescription,
                'author' => $strAuthor,
                'status' => $intStatus,
            ];
        }

        /**
         * Sets the callback function using the sleepHelper method and then invokes the parent's sleep method.
         *
         * @return array
         */
        public function sleep(): array
        {
            $this->nodeParamsCallback = ControlBase::sleepHelper($this->nodeParamsCallback);
            return parent::sleep();
        }

        /**
         * Prepares the form by invoking the parent wakeup method and setting up the node parameters callback.
         *
         * @param FormBase $objForm The form instance to be prepared.
         * @return void
         */
        public function wakeup(FormBase $objForm): void
        {
            parent::wakeup($objForm);
            $this->nodeParamsCallback = ControlBase::wakeupHelper($objForm, $this->nodeParamsCallback);
        }

        /**
         * Generates the HTML markup for the control by binding data and formatting it into a structured format.
         *
         * @return string The generated HTML string which includes a JSON configuration and rendered image URLs.
         * @throws Caller
         * @throws Exception
         */
        protected function getControlHtml(): string
        {
            $this->dataBind();
            $strParams = [];
            $strHtml = "";

            if ($this->objDataSource) {
                foreach ($this->objDataSource as $objObject) {
                    $strParams[] = $this->getItem($objObject);
                }
            }

            $strHtml .= "<div data-nanogallery2 ='";
            $strHtml .= json_encode($this->makePHPOptions(),JSON_UNESCAPED_SLASHES);
            $strHtml .= "'>";
            $strHtml .= $this->renderImageUrl($strParams);
            $strHtml .= "</div>";


            $this->objDataSource = [];
            return $strHtml;
        }

        /**
         * Binds the data source to the UI component.
         * If the data source is not set and a data binder is available, it calls the data binder method.
         *
         * @return void
         * @throws Caller
         * @throws DataBind
         */
        public function dataBind(): void
        {
            // Run the DataBinder (if applicable)
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
         * Generates an HTML string of anchor elements based on the provided parameters,
         * constructing URL paths and optionally adding descriptions and authors depending on the status.
         *
         * @param array $arrParams An array of associative arrays, where each associative array contains:
         *                         'list_descripton' (string): A description associated with the list.
         *                         'list_author' (string): An author associated with the list.
         *                         'path' (string): The URL path for the image.
         *                         'descripton' (string): A specific description for the image.
         *                         'author' (string): A specific author for the image.
         *                         'status' (int): The status determining if the URL should be rendered (1 for rendering, others ignored).
         *
         * @return string A concatenated string of HTML anchor elements for valid entries; returns an empty string if no valid entries exist.
         */
        protected function renderImageUrl(array $arrParams): string
        {
            $strHtml = '';

            for ($i = 0; $i < count($arrParams); $i++) {
                $strListDescripton = $arrParams[$i]['list_descripton'];
                $strListAuthor = $arrParams[$i]['list_author'];
                $strPath = $arrParams[$i]['path'];
                $strDescripton = $arrParams[$i]['descripton'];
                $strAuthor = $arrParams[$i]['author'];
                $intStatus = $arrParams[$i]['status'];

                if ($intStatus == 1) {
                    $strHtml .= '<a href="';
                    $strHtml .= '/large' . $strPath . '"';
                    $strHtml .= ' data-ngthumb="';
                    $strHtml .= '/thumbnail' . $strPath . '"';

                    if ($strAuthor || $strListAuthor) {
                        $strHtml .= ' data-ngdesc="';
                        $strHtml .= $strAuthor ?: $strListAuthor;
                        $strHtml .= '">';
                    } else {
                        $strHtml .= '>';
                    }

                    if ($strDescripton || $strListDescripton) {
                        $strHtml .= $strDescripton ?: $strListDescripton;
                    }
                    $strHtml .= '</a>';
                } else {
                    $strHtml .= '';
                }
            }

            return $strHtml;
        }

        /**
         * Generates an associative array of options for PHP, including various settings such as display modes, thumbnail settings, image transitions, slideshow settings, and more.
         *
         * @return array An associative array containing options and their respective values. The keys in the array include itemsBaseURL, galleryDisplayMode, galleryMaxRows, galleryMaxItems, galleryDisplayTransition, galleryDisplayTransitionDuration, thumbnailWidth, thumbnailHeight, thumbnailAlignment, thumbnailGutterWidth, thumbnailGutterHeight, thumbnailBorderVertical, thumbnailBorderHorizontal, imageTransition, slideshowAutoStart, slideshowDelay, viewerHideToolsDelay, viewerFullscreen, thumbnailLabel, viewerToolbar, viewerTools, locationHash, and allowHTMLinData.
         */
        protected function makePHPOptions(): array
        {
            $phpOptions = [];
            if (!is_null($val = $this->ItemsBaseURL)) {$phpOptions['itemsBaseURL'] = $val;}
            if (!is_null($val = $this->GalleryDisplayMode)) {$phpOptions['galleryDisplayMode'] = $val;}
            if (!is_null($val = $this->GalleryMaxRows)) {$phpOptions['galleryMaxRows'] = $val;}
            if (!is_null($val = $this->GalleryMaxItems)) {$phpOptions['galleryMaxItems'] = $val;}
            if (!is_null($val = $this->GalleryDisplayTransition)) {$phpOptions['galleryDisplayTransition'] = $val;}
            if (!is_null($val = $this->GalleryDisplayTransitionDuration)) {$phpOptions['galleryDisplayTransitionDuration'] = $val;}

            if (!is_null($val = $this->ThumbnailWidth)) {$phpOptions['thumbnailWidth'] = $val;}
            if (!is_null($val = $this->ThumbnailHeight)) {$phpOptions['thumbnailHeight'] = $val;}
            if (!is_null($val = $this->ThumbnailAlignment)) {$phpOptions['thumbnailAlignment'] = $val;}
            if (!is_null($val = $this->ThumbnailGutterWidth)) {$phpOptions['thumbnailGutterWidth'] = $val;}
            if (!is_null($val = $this->ThumbnailGutterHeight)) {$phpOptions['thumbnailGutterHeight'] = $val;}
            if (!is_null($val = $this->ThumbnailBorderVertical)) {$phpOptions['thumbnailBorderVertical'] = $val;}
            if (!is_null($val = $this->ThumbnailBorderHorizontal)) {$phpOptions['thumbnailBorderHorizontal'] = $val;}

            if (!is_null($val = $this->ImageTransition)) {$phpOptions['imageTransition'] = $val;}
            if (!is_null($val = $this->SlideshowAutoStart)) {$phpOptions['slideshowAutoStart'] = $val;}
            if (!is_null($val = $this->SlideshowDelay)) {$phpOptions['slideshowDelay'] = $val;}
            if (!is_null($val = $this->ViewerHideToolsDelay)) {$phpOptions['viewerHideToolsDelay'] = $val;}
            if (!is_null($val = $this->ViewerFullscreen)) {$phpOptions['viewerFullscreen'] = $val;}

            if (!is_null($val = $this->ThumbnailLabel)) {$phpOptions['thumbnailLabel'] = $val;}
            if (!is_null($val = $this->ViewerToolbar)) {$phpOptions['viewerToolbar'] = $val;}
            if (!is_null($val = $this->ViewerTools)) {$phpOptions['viewerTools'] = $val;}

            if (!is_null($val = $this->LocationHash)) {$phpOptions['locationHash'] = $val;}
            if (!is_null($val = $this->AllowHTMLinData)) {$phpOptions['allowHTMLinData'] = $val;}
            return $phpOptions;
        }

        /**
         * Magic method to retrieve the values of specific properties dynamically.
         *
         * @param string $strName The name of the property being requested.
         *
         * @return mixed The value of the requested property. The type of the return value depends on the requested property and may include strings, integers, booleans, arrays, or objects.
         * @throws Caller If the requested property is invalid or not accessible.
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case 'ItemsBaseURL': return $this->strItemsBaseURL;
                case 'GalleryDisplayMode': return $this->strGalleryDisplayMode;
                case 'GalleryMaxRows': return $this->intGalleryMaxRows;
                case 'GalleryMaxItems': return $this->intGalleryMaxItems;
                case 'GalleryDisplayTransition': return $this->strGalleryDisplayTransition;
                case 'GalleryDisplayTransitionDuration': return $this->intGalleryDisplayTransitionDuration;

                case 'ThumbnailWidth': return $this->intThumbnailWidth;
                case 'ThumbnailHeight': return $this->intThumbnailHeight;
                case 'ThumbnailAlignment': return $this->strThumbnailAlignment;
                case 'ThumbnailGutterWidth': return $this->intThumbnailGutterWidth;
                case 'ThumbnailGutterHeight': return $this->intThumbnailGutterHeight;
                case 'ThumbnailBorderVertical': return $this->intThumbnailBorderVertical;
                case 'ThumbnailBorderHorizontal': return $this->intThumbnailBorderHorizontal;

                case 'ImageTransition': return $this->strImageTransition;
                case 'SlideshowAutoStart': return $this->blnSlideshowAutoStart;
                case 'SlideshowDelay': return $this->intSlideshowDelay;
                case 'ViewerHideToolsDelay': return $this->intViewerHideToolsDelay;
                case 'ViewerFullscreen': return $this->blnViewerFullscreen;

                case 'ThumbnailLabel': return $this->arrThumbnailLabel;
                case 'ViewerToolbar': return $this->arrViewerToolbar;
                case 'ViewerTools': return $this->arrViewerTools;

                case 'LocationHash': return $this->blnLocationHash;
                case 'AllowHTMLinData': return $this->blnAllowHTMLinData;

                case "TempUrl": return $this->strTempUrl;
                case "DataSource": return $this->objDataSource;

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
         * Dynamically sets the value of a property based on the property name provided.
         * Validates and casts the value to the appropriate type before assigning it to the corresponding property.
         * Modifies the internal state to indicate a change has been made.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property. The value is validated and cast to the expected type.
         *
         * @return void
         *
         * @throws InvalidCast If the value cannot be successfully cast to the expected type.
         * @throws Caller If the property name provided is invalid and not handled by the parent class.
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case 'ItemsBaseURL':
                    try {
                        $this->strItemsBaseURL = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'GalleryDisplayMode':
                    try {
                        $this->strGalleryDisplayMode = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'GalleryMaxRows':
                    try {
                        $this->intGalleryMaxRows = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'GalleryMaxItems':
                    try {
                        $this->intGalleryMaxItems = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'GalleryDisplayTransition':
                    try {
                        $this->strGalleryDisplayTransition = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'GalleryDisplayTransitionDuration':
                    try {
                        $this->intGalleryDisplayTransitionDuration = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                case 'ThumbnailWidth':
                    $this->intThumbnailWidth = $mixValue; // Type::cast($mixValue, Type::INTEGER);
                    $this->blnModified = true;
                    break;
                case 'ThumbnailHeight':
                    $this->intThumbnailHeight = $mixValue; // Type::cast($mixValue, Type::INTEGER);
                    $this->blnModified = true;
                    break;
                case 'ThumbnailAlignment':
                    try {
                        $this->strThumbnailAlignment = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ThumbnailGutterWidth':
                    try {
                        $this->intThumbnailGutterWidth = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ThumbnailGutterHeight':
                    try {
                        $this->intThumbnailGutterHeight = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ThumbnailBorderVertical':
                    try {
                        $this->intThumbnailBorderVertical = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ThumbnailBorderHorizontal':
                    try {
                        $this->intThumbnailBorderHorizontal = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ImageTransition':
                    try {
                        $this->strImageTransition = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'SlideshowAutoStart':
                    try {
                        $this->blnSlideshowAutoStart = Type::cast($mixValue, Type::BOOLEAN);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'SlideshowDelay':
                    try {
                        $this->intSlideshowDelay = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ViewerHideToolsDelay':
                    try {
                        $this->intViewerHideToolsDelay = Type::cast($mixValue, Type::INTEGER);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ViewerFullscreen':
                    try {
                        $this->blnViewerFullscreen = Type::cast($mixValue, Type::BOOLEAN);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                case 'ThumbnailLabel':
                    try {
                        $this->arrThumbnailLabel = Type::cast($mixValue, Type::ARRAY_TYPE);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ViewerToolbar':
                    try {
                        $this->arrViewerToolbar = Type::cast($mixValue, Type::ARRAY_TYPE);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'ViewerTools':
                    try {
                        $this->arrViewerTools = Type::cast($mixValue, Type::ARRAY_TYPE);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'LocationHash':
                    try {
                        $this->blnLocationHash = Type::cast($mixValue, Type::BOOLEAN);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case 'AllowHTMLinData':
                    try {
                        $this->blnAllowHTMLinData = Type::cast($mixValue, Type::BOOLEAN);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "TempUrl":
                    try {
                        $this->strTempUrl = Type::cast($mixValue, Type::STRING);
                        $this->blnModified = true;
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->IncrementOffset();
                        throw $objExc;
                    }
                case "DataSource":
                    $this->blnModified = true;
                    $this->objDataSource = $mixValue;
                    break;

                default:
                    try {
                        parent::__set($strName, $mixValue);
                        break;
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
            }
        }
    }