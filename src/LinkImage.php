<?php

    namespace QCubed\Plugin;

    use QCubed\Control\Panel;
    use QCubed\Exception\Caller;
    use QCubed\Exception\InvalidCast;
    use QCubed\QString;
    use QCubed\Type;

    /**
     * Class LinkImage
     *
     * @property string $AlternateText is rendered as the HTML "alt" tag
     * @property string $ImageUrl is the url of the image to be used
     *
     * @property string $LeftText
     * @property string $RightText
     * @property string $SpanClass
     * @property string $WrapperClass
     * @property string $Url
     * @property bool $Target
     *
     */
    class LinkImage extends Panel
    {
        /** @var  string|null */
        protected ?string $strAlternateText = '';
        /** @var  string|null */
        protected ?string $strImageUrl = '';
        /** @var  string|null */
        protected ?string $strLeftText = '';
        /** @var  string|null */
        protected ?string $strRightText = '';
        /** @var  string|null */
        protected ?string $strWrapperClass = '';
        /** @var  string|null */
        protected ?string $strSpanClass = '';
        /** @var  string|null */
        protected ?string $strUrl = '';
        /** @var  string|null */
        protected ?string $strTarget = '';

        /**
         * This method will render the control, itself, and will return the rendered HTML as a string
         *
         * As an abstract method, any class extending ControlBase must implement it.  This ensures that
         * each control has its own specific HTML.
         *
         * When outputting HTML, you should call GetHtmlAttributes to get the attributes for the main control.
         *
         * If you are outputting a complex control and need to include IDs in subcontrols, your IDs should be of the
         * form:
         *    $parentControl->ControlId. '_' . $strSubcontrolId.
         * The underscore indicates that actions and post-data should be routed first to the parent control, and the
         * parent control will handle the rest.
         *
         * @return string
         */
        protected function getControlHtml(): string
        {
            $attributes = [];

            if ($this->strUrl) {
                $attributes['href'] = $this->strUrl;
            } else {
                $attributes['href'] = '#';
            }
            if ($this->strWrapperClass) {
                $attributes['class'] = $this->strWrapperClass;
            }
            if ($this->strTarget) {
                $attributes['target'] = $this->strTarget;
            }

            if ($this->strLeftText) {
                return $this->renderTag('a', $attributes, null, $this->getLeftHtml());
            } else if ($this->strRightText) {
                return $this->renderTag('a', $attributes, null, $this->getRightHtml());
            } else {
                return $this->renderTag('a', $attributes, null, $this->getImgHtml());
            }
        }

        /**
         * @return string
         */
        protected function getImgHtml(): string
        {
            $attributes = [];
            if ($this->strAlternateText) {
                $attributes['alt'] = $this->strAlternateText;
            }
            if ($this->strImageUrl) {
                $attributes['src'] = $this->strImageUrl;
            }

            return $this->renderTag('img', $attributes, null, null, true);
        }

        /**
         * @return string
         */
        protected function getSpanHtml(): string
        {
            if ($this->strLeftText) {
                $override = $this->strLeftText;
            } else {
                $override = $this->strRightText;
            }

            return $this->renderTag('span', ['class' => $this->strSpanClass], null, QString::htmlEntities($override));
        }

        /**
         * @return string
         */
        protected function getLeftHtml(): string
        {
            return _nl($this->getSpanHtml()) . $this->getImgHtml();
        }

        /**
         * @return string
         */
        protected function getRightHtml(): string
        {
            return _nl($this->getImgHtml()) . $this->getSpanHtml();
        }

        /**
         * Magic method for retrieving the value of a property.
         *
         * @param string $strName The name of the property to retrieve.
         *
         * @return mixed The value of the requested property, or throws an exception if the property does not exist.
         * @throws Caller If the property is not accessible or does not exist.
         * @throws \Exception
         */
        public function __get(string $strName): mixed
        {
            switch ($strName) {
                case "AlternateText":
                    return $this->strAlternateText;
                case "ImageUrl":
                    return $this->strImageUrl;
                case "LeftText":
                    return $this->strLeftText;
                case "RightText":
                    return $this->strRightText;
                case "WrapperClass":
                    return $this->strWrapperClass;
                case "SpanClass":
                    return $this->strSpanClass;
                case "Url":
                    return $this->strUrl;
                case "Target":
                    return $this->strTarget;

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
         * Magic method to set the value of a property.
         *
         * @param string $strName The name of the property to set.
         * @param mixed $mixValue The value to assign to the property.
         *
         * @return void
         * @throws InvalidCast If the provided value cannot be cast to the expected type.
         * @throws Caller If the property name is invalid or cannot be handled by the parent.
         * @throws \Exception
         */
        public function __set(string $strName, mixed $mixValue): void
        {
            switch ($strName) {
                case "AlternateText":
                    try {
                        $this->blnModified = true;
                        $this->strAlternateText = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "ImageUrl":
                    try {
                        $this->blnModified = true;
                        $this->strImageUrl = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "LeftText":
                    try {
                        $this->blnModified = true;
                        $this->strLeftText = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "RightText":
                    try {
                        $this->blnModified = true;
                        $this->strRightText = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "WrapperClass":
                    try {
                        $this->blnModified = true;
                        $this->strWrapperClass = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "SpanClass":
                    try {
                        $this->blnModified = true;
                        $this->strSpanClass = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "Url":
                    try {
                        $this->blnModified = true;
                        $this->strUrl = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                case "Target":
                    try {
                        $this->blnModified = true;
                        $this->strTarget = Type::cast($mixValue, Type::STRING);
                        break;
                    } catch (InvalidCast $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }

                default:
                    try {
                        parent::__set($strName, $mixValue);
                    } catch (Caller $objExc) {
                        $objExc->incrementOffset();
                        throw $objExc;
                    }
                    break;
            }
        }
    }
