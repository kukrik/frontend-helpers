(function ($) {
    $.fn.qcCookie = function (options) {
        options = $.extend({
            googleId: null,
            lifeTime: 365,
            consentCookieName: 'cookie_consent'
        }, options);

        function showCookieBanner($banner) {
            $banner.addClass('visible');
        }

        function hideCookieBanner($banner) {
            $banner.removeClass('visible');
        }

        function getCookie(name) {
            var cookies = document.cookie ? document.cookie.split(';') : [];

            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i].trim();

                if (cookie.indexOf(name + '=') === 0) {
                    return decodeURIComponent(cookie.substring(name.length + 1));
                }
            }

            return null;
        }

        function setCookie(name, value, days) {
            var expires = new Date();
            expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);

            document.cookie =
                name + '=' + encodeURIComponent(value) +
                '; expires=' + expires.toUTCString() +
                '; path=/' +
                '; SameSite=Lax';
        }

        function gaLoad() {
            if (!options.googleId || window.__gaLoaded) {
                return;
            }

            window.__gaLoaded = true;

            var script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(options.googleId);
            document.head.appendChild(script);

            window.dataLayer = window.dataLayer || [];

            window.gtag = window.gtag || function () {
                window.dataLayer.push(arguments);
            };

            window.gtag('js', new Date());
            window.gtag('config', options.googleId);
        }

        return this.each(function () {
            var $banner = $(this);
            var consent = getCookie(options.consentCookieName);

            $banner.find('[data-cookie-consent]').on('click', function () {
                var value = $(this).data('cookie-consent');

                setCookie(options.consentCookieName, value, options.lifeTime);
                hideCookieBanner($banner);

                if (value === 'all') {
                    gaLoad();
                }
            });

            if (!consent) {
                showCookieBanner($banner);
                return;
            }

            if (consent === 'all') {
                gaLoad();
            }
        });

        return this;
    }
})(jQuery);