(function (window, document, $) {
    "use strict";

    window.qcubed = window.qcubed || {};

    function getRoot(controlId) {
        return document.getElementById(controlId);
    }

    function getItems(root) {
        return root.querySelectorAll(".aggregated-year-item[data-year]");
    }

    function getOlderButton(root) {
        return root.querySelector(".js-aggregated-years-older");
    }

    function getNewerButton(root) {
        return root.querySelector(".js-aggregated-years-newer");
    }

    function getUniqueYears(items) {
        const years = [];

        items.forEach(function (item) {
            const year = item.getAttribute("data-year");
            if (year && years.indexOf(year) === -1) {
                years.push(year);
            }
        });

        return years;
    }

    function render(root) {
        const items = Array.from(getItems(root));
        const olderBtn = getOlderButton(root);
        const newerBtn = getNewerButton(root);

        if (!items.length || !olderBtn || !newerBtn) {
            return;
        }

        let limit = parseInt(root.getAttribute("data-limit"), 10);
        if (isNaN(limit) || limit < 1) {
            limit = 5;
        }

        const years = getUniqueYears(items);
        let page = parseInt(root.getAttribute("data-page"), 10);
        if (isNaN(page) || page < 0) {
            page = 0;
        }

        const start = page * limit;
        const end = start + limit;
        const visibleYears = years.slice(start, end);

        items.forEach(function (item) {
            const year = item.getAttribute("data-year");

            if (visibleYears.indexOf(year) !== -1) {
                item.classList.remove("is-hidden");
            } else {
                item.classList.add("is-hidden");
            }
        });

        if (end >= years.length) {
            olderBtn.classList.add("is-hidden");
        } else {
            olderBtn.classList.remove("is-hidden");
        }

        if (page <= 0) {
            newerBtn.classList.add("is-hidden");
        } else {
            newerBtn.classList.remove("is-hidden");
        }
    }

    function bind(root) {
        if (root.dataset.aggregatedYearsBound === "1") {
            return;
        }

        root.dataset.aggregatedYearsBound = "1";

        root.addEventListener("click", function (e) {
            let page;
            const a = e.target && e.target.closest ? e.target.closest("a") : null;
            if (!a || !root.contains(a)) {
                return;
            }

            if (a.classList.contains("js-aggregated-years-older")) {
                e.preventDefault();

                page = parseInt(root.getAttribute("data-page"), 10);
                if (isNaN(page) || page < 0) {
                    page = 0;
                }

                root.setAttribute("data-page", String(page + 1));
                render(root);
                return;
            }

            if (a.classList.contains("js-aggregated-years-newer")) {
                e.preventDefault();

                page = parseInt(root.getAttribute("data-page"), 10);
                if (isNaN(page) || page < 0) {
                    page = 0;
                }

                root.setAttribute("data-page", String(Math.max(0, page - 1)));
                render(root);
            }
        });
    }

    function init(root, opts) {
        if (!root) {
            return;
        }

        if (opts && typeof opts.limit !== "undefined") {
            root.setAttribute("data-limit", String(opts.limit));
        }

        if (!root.hasAttribute("data-page")) {
            root.setAttribute("data-page", "0");
        }

        bind(root);
        render(root);
    }

    window.qcubed.aggregatedYears = function (controlId, arg2) {
        const root = getRoot(controlId);
        if (!root) {
            return;
        }

        if (typeof arg2 === "string") {
            if (arg2 === "refresh" || arg2 === "init") {
                init(root, {});
            }
            return;
        }

        init(root, arg2 || {});
    };

    if ($ && $.fn) {
        $.fn.aggregatedYears = function (arg2) {
            return this.each(function () {
                const id = this && this.id ? this.id : null;
                if (!id) {
                    return;
                }
                window.qcubed.aggregatedYears(id, arg2);
            });
        };
    }
})(window, document, window.jQuery);