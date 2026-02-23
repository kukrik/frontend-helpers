(function (window, document) {
    'use strict';

    function safeJsonParse(raw) {
        if (!raw) return {};
        try { return JSON.parse(raw); } catch (e) { return {}; }
    }

    // --- i18n (short + optional fetch) ---
    var i18nCache = Object.create(null);

    function merge(a, b) {
        var out = {};
        var k;
        if (a) for (k in a) out[k] = a[k];
        if (b) for (k in b) out[k] = b[k];
        return out;
    }

    function normalizeLang(lang) {
        if (!lang) return '';
        return String(lang).toLowerCase();
    }

    function getDefaultTranslations() {
        return {
            share_mail_subject_default: 'Check out this link',
            share_copy_success: 'Link copied',
            share_copy_fail: 'Copy failed',
            share_bookmark_hint: 'To bookmark this page, use'
        };
    }

    function getBrowserLang() {
        return normalizeLang((document.documentElement && document.documentElement.lang) || navigator.language || 'et');
    }

    function loadTranslationsFromUrl(url, lang) {
        if (!url || !lang) return Promise.resolve(null);
        if (i18nCache[lang]) return i18nCache[lang];

        i18nCache[lang] = fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .catch(function () { return null; });

        return i18nCache[lang];
    }

    function createTranslator(cfg) {
        var defaults = getDefaultTranslations();
        var lang = normalizeLang(cfg.language) || getBrowserLang();
        var dict = merge(defaults, cfg.translations);

        // Optional async load from JSON file
        if (!cfg.translations && cfg.langPath) {
            var url = String(cfg.langPath).replace(/\/$/, '') + '/' + lang + '.json';
            loadTranslationsFromUrl(url, lang).then(function (remote) {
                if (remote && typeof remote === 'object') {
                    var remoteDict = remote;

                    // We support both formats:
                    // 1) { "share_copy_success": "...", ... }
                    // 2) { "language": "et", "translations": { ... } }
                    if (remote.translations && typeof remote.translations === 'object') {
                        remoteDict = remote.translations;
                    }

                    dict = merge(dict, remoteDict);
                }
            });
        }

        function t(key) {
            return (dict && dict[key]) ? String(dict[key]) : String(defaults[key] || key);
        }

        return { t: t, lang: lang };
    }


    function metaContent(selector) {
        var el = document.querySelector(selector);
        return el ? (el.getAttribute('content') || '').trim() : '';
    }

    function getShareData() {
        var url = metaContent('meta[property="og:url"]') || window.location.href;
        var title = metaContent('meta[property="og:title"]') || document.title || '';
        var text =
            metaContent('meta[property="og:description"]') ||
            metaContent('meta[name="description"]') ||
            '';

        return { url: url, title: title, text: text };
    }

    function openPopup(url) {
        var w = 600;
        var h = 600;

        var y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
        var x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);

        window.open(
            url,
            'share',
            'toolbar=0,status=0,width=' + w + ',height=' + h + ',top=' + y + ',left=' + x
        );
    }

    async function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                return false;
            }
        }

        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '-9999px';
        document.body.appendChild(ta);
        ta.select();

        try {
            var ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (e2) {
            document.body.removeChild(ta);
            return false;
        }
    }

    function ensureToast() {
        var el = document.querySelector('.share-toast');
        if (el) return el;

        el = document.createElement('div');
        el.className = 'share-toast share-toast--info share-toast--pos-top-center';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.hidden = true;

        document.body.appendChild(el);
        return el;
    }

    function setToastType(el, type) {
        el.classList.remove('share-toast--success', 'share-toast--error', 'share-toast--info');
        if (type === 'success') {
            el.classList.add('share-toast--success');
        } else if (type === 'error') {
            el.classList.add('share-toast--error');
        } else {
            el.classList.add('share-toast--info');
        }
    }

    function toast(message, typeOrDuration, maybeDuration) {
        var el = ensureToast();
        var type = 'info';
        var durationMs;

        if (typeof typeOrDuration === 'string') {
            type = typeOrDuration;
            durationMs = maybeDuration;
        } else {
            durationMs = typeOrDuration;
        }

        setToastType(el, type);

        el.textContent = message;

        el.hidden = false;
        el.classList.remove('is-visible');
        void el.offsetWidth;
        el.classList.add('is-visible');

        window.clearTimeout(el._hideTimer);
        el._hideTimer = window.setTimeout(function () {
            el.classList.remove('is-visible');
            window.setTimeout(function () {
                el.hidden = true;
            }, 200);
        }, durationMs || 2200);
    }

    function isMacLike() {
        return /Mac|iPhone|iPad|iPod/i.test(navigator.platform);
    }

    function buildMailtoUrl(subject, body) {
        return 'mailto:?' +
            'subject=' + encodeURIComponent(subject || '') +
            '&body=' + encodeURIComponent(body || '');
    }

    function init(rootOrSelector, options) {
        var root = (typeof rootOrSelector === 'string')
            ? document.querySelector(rootOrSelector)
            : rootOrSelector;

        if (!root) return;

        if (root.getAttribute('data-share-initialized') === '1') return;
        root.setAttribute('data-share-initialized', '1');

        var datasetOptions = safeJsonParse(root.getAttribute('data-share-options'));
        var cfg = Object.assign({
            enableFacebook: true,
            enableX: true,
            enableEmail: true,
            enableCopy: true,
            enablePrint: true,
            enableBookmark: false,

            useNativeShareOnMobile: true,

            toastDurationMs: 2200,

            xTextSource: 'title', // 'title' | 'text'

            // i18n:
            // language: 'et' | 'en' | ...
            // translations: { share_copy_success: '...', ... }  (optional)
            // langPath: '/assets/lang' (optional; fetches /assets/lang/{language}.json)
            language: null,
            translations: null,
            langPath: null
        }, datasetOptions, options || {});

        var i18n = createTranslator(cfg);
        var t = i18n.t;

        root.addEventListener('click', async function (e) {
            var data = getShareData();

            var btnFacebook = e.target.closest('.js-share-facebook');
            if (btnFacebook && cfg.enableFacebook) {
                e.preventDefault();
                openPopup('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(data.url));
                return;
            }

            var btnX = e.target.closest('.js-share-twitter');
            if (btnX && cfg.enableX) {
                e.preventDefault();
                var xText = (cfg.xTextSource === 'text' ? (data.text || data.title) : data.title) || '';
                var xParams = new URLSearchParams({ url: data.url, text: xText });
                openPopup('https://twitter.com/intent/tweet?' + xParams.toString());
                return;
            }

            var btnEmail = e.target.closest('.js-share-via-email');
            if (btnEmail && cfg.enableEmail) {
                e.preventDefault();

                if (cfg.useNativeShareOnMobile && navigator.share) {
                    try {
                        await navigator.share({
                            title: data.title,
                            text: data.text || data.title,
                            url: data.url
                        });
                        return;
                    } catch (err) {
                        // user cancel / fail -> fallback mailto
                    }
                }

                var subject = data.title || t('share_mail_subject_default');
                var body = (data.text ? data.text + '\n\n' : '') + data.url;
                window.location.href = buildMailtoUrl(subject, body);
                return;
            }

            var btnCopy = e.target.closest('.js-copy-link');
            if (btnCopy && cfg.enableCopy) {
                e.preventDefault();
                var ok = await copyToClipboard(data.url);
                toast(
                    ok ? t('share_copy_success') : t('share_copy_fail'),
                    ok ? 'success' : 'error',
                    cfg.toastDurationMs
                );
                return;
            }

            var btnPrint = e.target.closest('.js-print');
            if (btnPrint && cfg.enablePrint) {
                e.preventDefault();
                window.print();
                return;
            }

            var btnBookmark = e.target.closest('.js-add-bookmark');
            if (btnBookmark && cfg.enableBookmark) {
                e.preventDefault();
                toast(
                    t('share_bookmark_hint') + ': ' + (isMacLike() ? 'Cmd + D' : 'Ctrl + D'),
                    'info',
                    3000
                );
            }
        });
    }

    function initAll(selector, options) {
        var nodes = document.querySelectorAll(selector || '.page-share');
        for (var i = 0; i < nodes.length; i++) {
            init(nodes[i], options);
        }
    }

    window.FrontendHelpersSharePage = {
        init: init,
        initAll: initAll,
        toast: function (message, type, durationMs) {
            toast(message, type || 'info', durationMs);
        }
    };

})(window, document);