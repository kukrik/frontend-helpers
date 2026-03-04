<?php

    namespace QCubed\Plugin;

    /**
     *
     */
    final class FrontendAssets
    {
        private static ?array $manifest = null;

        /**
         * @return array|string[]
         */
        private static function manifest(): array
        {
            if (self::$manifest !== null) return self::$manifest;

            $path = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/frontend/assets/css/manifest.json';

            if (!is_file($path)) {
                // dev fallback
                return self::$manifest = [
                    'fonts' => '/frontend/assets/css/src/fonts.css',
                    'base' => '/frontend/assets/css/src/base.css',
                    'styles' => '/frontend/assets/css/src/styles.css',
                    'breakpoints' => '/frontend/assets/css/src/breakpoints.css',
                ];
            }

            self::$manifest = json_decode(file_get_contents($path), true) ?: [];
            return self::$manifest;
        }

        public static function css(string $key): string
        {
            $m = self::manifest();
            return $m[$key] ?? '';
        }

        /**
         * @param string $key
         *
         * @return string
         */
        public static function cssLink(string $key): string
        {
            $href = self::css($key);
            return $href ? '<link href="' . htmlspecialchars($href, ENT_QUOTES) . '" rel="stylesheet" />' : '';
        }
    }