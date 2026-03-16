(function (window, document, $) {
    "use strict";

    window.qcubed = window.qcubed || {};

    const instances = new Map();

    function parseYear(value) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? null : parsed;
    }

    function getRoot(controlId) {
        return document.getElementById(controlId);
    }

    function getItems(root) {
        return root.querySelectorAll(".year-item");
    }

    function getMoreLink(root) {
        return root.querySelector(".sidebar-more-link");
    }

    function getYearLinks(root) {
        return root.querySelectorAll(".sidebar-link[data-year]");
    }

    function getActiveLink(root) {
        return root.querySelector(".sidebar-link.is-active[data-year]");
    }

    function getActiveYear(root) {
        var active = getActiveLink(root);
        if (!active) { return null; }
        return parseYear(active.getAttribute("data-year"));
    }

    function getActiveIndex(root) {
        var active = root.querySelector(".sidebar-link.is-active");
        if (!active) { return null; }

        var li = active.closest ? active.closest(".year-item") : null;
        if (!li) { return null; }

        var idx = parseInt(li.getAttribute("data-index"), 10);
        return isNaN(idx) ? null : idx;
    }

    function getNewestYear(root) {
        var first = root.querySelector(".sidebar-link[data-year]");
        if (!first) { return null; }

        return parseYear(first.getAttribute("data-year"));
    }

    function getYearFromUrl() {
        try {
            var url = new URL(window.location.href);
            var year = url.searchParams.get("year");
            if (!year) { return null; }
            return parseYear(year);
        } catch (e) {
            return null;
        }
    }

    function scrollToRoot(root) {
        try {
            root.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
        } catch (e) {
            root.scrollIntoView(true);
        }
    }

    function updateUrl(year) {
        try {
            var url = new URL(window.location.href);

            if (year === null || typeof year === "undefined" || year === "") {
                url.searchParams.delete("year");
            } else {
                url.searchParams.set("year", String(year));
            }

            window.history.replaceState(window.history.state || {}, "", url.toString());
        } catch (e) {
            // ignore
        }
    }

    function setMoreMode(root, isResetMode) {
        var state = instances.get(root.id) || {};
        var link = getMoreLink(root);
        if (!link) { return; }

        link.dataset.mode = isResetMode ? "reset" : "more";
        link.textContent = isResetMode
            ? (state.resetLabel || "Back to the start")
            : (state.moreLabel || "See more...");
    }

    function setActiveLink(root, year) {
        var links = getYearLinks(root);

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var linkYear = parseYear(link.getAttribute("data-year"));

            if (linkYear === year) {
                link.classList.add("is-active");
                link.setAttribute("aria-current", "true");
            } else {
                link.classList.remove("is-active");
                link.removeAttribute("aria-current");
            }
        }
    }

    function recordYear(root, year) {
        if (window.qcubed && typeof window.qcubed.recordControlModification === "function") {
            window.qcubed.recordControlModification(root.id, "_Year", year);
        }

        if (window.jQuery) {
            window.jQuery(root).trigger("selectyear");
        }
    }

    function setYearAndTrigger(root, year) {
        if (year === null) { return; }

        var currentActiveYear = getActiveYear(root);
        if (currentActiveYear === year) {
            updateUrl(year);
            return;
        }

        setActiveLink(root, year);
        updateUrl(year);
        recordYear(root, year);
    }

    function showOlderChunk(root, page) {
        var state = instances.get(root.id) || {};
        var perPage = Math.max(1, parseInt(state.limit, 10) || 5);

        var items = getItems(root);
        var total = items.length;

        var start = perPage + (page * perPage);
        var end = start + perPage - 1;

        items.forEach(function (li) {
            var idx = parseInt(li.getAttribute("data-index"), 10);
            var keepNewest = idx < perPage;
            var inOlderWindow = (idx >= start && idx <= end);

            if (keepNewest || inOlderWindow) {
                li.classList.remove("is-hidden");
            } else {
                li.classList.add("is-hidden");
            }
        });

        var nextStart = start + perPage;
        setMoreMode(root, nextStart >= total);
    }

    function resetToStart(root, triggerNewest) {
        var state = instances.get(root.id) || {};
        var perPage = Math.max(1, parseInt(state.limit, 10) || 5);

        var items = getItems(root);

        items.forEach(function (li) {
            var idx = parseInt(li.getAttribute("data-index"), 10);

            if (idx < perPage) {
                li.classList.remove("is-hidden");
            } else {
                li.classList.add("is-hidden");
            }
        });

        root.dataset.page = "0";
        setMoreMode(root, false);

        if (triggerNewest) {
            var newest = getNewestYear(root);
            var activeYear = getActiveYear(root);

            if (newest !== null && activeYear !== newest) {
                setYearAndTrigger(root, newest);
            }
        }
    }

    function ensureDefaultActive(root) {
        var activeYear = getActiveYear(root);
        if (activeYear !== null) { return; }

        var yearFromUrl = getYearFromUrl();
        if (yearFromUrl !== null) {
            var linkFromUrl = root.querySelector('.sidebar-link[data-year="' + yearFromUrl + '"]');
            if (linkFromUrl) {
                setActiveLink(root, yearFromUrl);
                return;
            }
        }

        var newestYear = getNewestYear(root);
        if (newestYear !== null) {
            setActiveLink(root, newestYear);
        }
    }

    function ensureActiveVisible(root) {
        var state = instances.get(root.id) || {};
        var perPage = Math.max(1, parseInt(state.limit, 10) || 5);

        var activeIdx = getActiveIndex(root);
        if (activeIdx === null) {
            root.dataset.page = "0";
            setMoreMode(root, false);
            return;
        }

        if (activeIdx < perPage) {
            root.dataset.page = "0";
            setMoreMode(root, false);
            return;
        }

        var page = Math.floor((activeIdx - perPage) / perPage);
        showOlderChunk(root, page);
        root.dataset.page = String(page + 1);
    }

    function bindClicks(root) {
        if (root.dataset.yearSidebarBound === "1") {
            return;
        }

        root.dataset.yearSidebarBound = "1";

        root.addEventListener("click", function (e) {
            var a = e.target && e.target.closest ? e.target.closest("a") : null;
            if (!a || !root.contains(a)) { return; }

            var action = a.getAttribute("data-action");

            if (action === "show-more") {
                e.preventDefault();

                var mode = a.dataset.mode || "more";

                if (mode === "reset") {
                    resetToStart(root, true);
                    scrollToRoot(root);
                    return;
                }

                var page = parseInt(root.dataset.page || "0", 10);
                if (isNaN(page)) { page = 0; }

                showOlderChunk(root, page);
                root.dataset.page = String(page + 1);
                scrollToRoot(root);
                return;
            }

            if (a.classList.contains("sidebar-link") && a.hasAttribute("data-year") && !action) {
                var year = parseYear(a.getAttribute("data-year"));
                if (year === null) { return; }

                e.preventDefault();
                setYearAndTrigger(root, year);
            }
        });

        window.addEventListener("popstate", function () {
            var yearFromUrl = getYearFromUrl();

            if (yearFromUrl !== null) {
                setActiveLink(root, yearFromUrl);
                ensureActiveVisible(root);
                return;
            }

            var newestYear = getNewestYear(root);
            if (newestYear !== null) {
                setActiveLink(root, newestYear);
                ensureActiveVisible(root);
            }
        });
    }

    function init(root, opts) {
        if (!root) { return; }

        var current = instances.get(root.id) || {};
        instances.set(root.id, {
            limit: opts && typeof opts.limit !== "undefined" ? opts.limit : (current.limit || 5),
            moreLabel: opts && typeof opts.moreLabel !== "undefined" ? opts.moreLabel : (current.moreLabel || "See more..."),
            resetLabel: opts && typeof opts.resetLabel !== "undefined" ? opts.resetLabel : (current.resetLabel || "Back to the start")
        });

        root.dataset.page = root.dataset.page || "0";

        bindClicks(root);
        setMoreMode(root, false);
        ensureDefaultActive(root);
        ensureActiveVisible(root);
    }

    window.qcubed.yearSidebar = function (controlId, arg2) {
        var root = getRoot(controlId);
        if (!root) { return; }

        if (typeof arg2 === "string") {
            if (arg2 === "refresh" || arg2 === "init") {
                init(root, instances.get(controlId) || {});
            }
            return;
        }

        init(root, arg2 || {});
    };

    if ($ && $.fn) {
        $.fn.yearSidebar = function (arg2) {
            return this.each(function () {
                var id = this && this.id ? this.id : null;
                if (!id) { return; }
                window.qcubed.yearSidebar(id, arg2);
            });
        };
    }
})(window, document, window.jQuery);