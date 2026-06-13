(function (window, document, $) {
    "use strict";

    window.qcubed = window.qcubed || {};

    function getRoot(controlId) {
        return document.getElementById(controlId);
    }

    function getItems(root) {
        return root.querySelectorAll(".page-links-group-item[data-group-id]");
    }

    function getMoreButton(root) {
        return root.querySelector(".js-page-links-more");
    }

    function getResetButton(root) {
        return root.querySelector(".js-page-links-reset");
    }

    function render(root) {
        const items = Array.from(getItems(root));
        const moreBtn = getMoreButton(root);
        const resetBtn = getResetButton(root);

        if (!items.length || !moreBtn || !resetBtn) {
            return;
        }

        let limit = parseInt(root.getAttribute("data-limit"), 10);

        if (isNaN(limit) || limit < 1) {
            limit = items.length;
        }

        let visibleCount = parseInt(root.getAttribute("data-visible-count"), 10);

        if (isNaN(visibleCount) || visibleCount < 1) {
            visibleCount = limit;
        }

        items.forEach(function (item, index) {
            if (index < visibleCount) {
                item.classList.remove("is-hidden");
            } else {
                item.classList.add("is-hidden");
            }
        });

        if (visibleCount >= items.length) {
            moreBtn.classList.add("is-hidden");
        } else {
            moreBtn.classList.remove("is-hidden");
        }

        if (visibleCount <= limit) {
            resetBtn.classList.add("is-hidden");
        } else {
            resetBtn.classList.remove("is-hidden");
        }
    }

    function bind(root) {
        if (root.dataset.pageLinksGroupsBound === "1") {
            return;
        }

        root.dataset.pageLinksGroupsBound = "1";

        root.addEventListener("click", function (e) {
            const a = e.target && e.target.closest ? e.target.closest("a") : null;

            if (!a || !root.contains(a)) {
                return;
            }

            if (a.classList.contains("js-page-links-more")) {
                e.preventDefault();

                let limit = parseInt(root.getAttribute("data-limit"), 10);
                let visibleCount = parseInt(root.getAttribute("data-visible-count"), 10);

                if (isNaN(limit) || limit < 1) {
                    limit = 1;
                }

                if (isNaN(visibleCount) || visibleCount < 1) {
                    visibleCount = limit;
                }

                root.setAttribute("data-visible-count", String(visibleCount + limit));
                render(root);
                return;
            }

            if (a.classList.contains("js-page-links-reset")) {
                e.preventDefault();

                let startLimit = parseInt(root.getAttribute("data-limit"), 10);

                if (isNaN(startLimit) || startLimit < 1) {
                    startLimit = 1;
                }

                root.setAttribute("data-visible-count", String(startLimit));
                render(root);

                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
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

        let limit = parseInt(root.getAttribute("data-limit"), 10);

        if (isNaN(limit) || limit < 1) {
            limit = getItems(root).length;
        }

        if (!root.hasAttribute("data-visible-count")) {
            root.setAttribute("data-visible-count", String(limit));
        }

        bind(root);
        render(root);
    }

    window.qcubed.pageLinksGroups = function (controlId, arg2) {
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
        $.fn.pageLinksGroups = function (arg2) {
            return this.each(function () {
                const id = this && this.id ? this.id : null;

                if (!id) {
                    return;
                }

                window.qcubed.pageLinksGroups(id, arg2);
            });
        };
    }
})(window, document, window.jQuery);