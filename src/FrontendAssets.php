<?php

    namespace QCubed\Plugin;

    final class FrontendAssets
    {
        private static ?array $manifest = null;

        protected string $strFrontendRootPath = FRONTEND_DIR;

        private static function manifest(): array
        {
            if (self::$manifest !== null) return self::$manifest;

            $path = __DIR__ . '/../../frontend/assets/css/manifest.json'; // kohanda
            if (!file_exists($path)) {
                // fallback dev jaoks
                return self::$manifest = [
                    'fonts' => '/frontend/assets/css/src/fonts.css',
                    'base' => '/frontend/assets/css/src/base.css',
                    'breakpoints' => '/frontend/assets/css/src/breakpoints.css',
                    'styles' => '/frontend/assets/css/src/styles.css',
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

        public static function cssLink(string $key): string
        {
            $href = self::css($key);
            return $href ? '<link href="' . htmlspecialchars($href, ENT_QUOTES) . '" rel="stylesheet" />' : '';
        }
    }