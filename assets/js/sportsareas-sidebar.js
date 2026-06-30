(function (window, document, $) {
    "use strict";

    window.qcubed = window.qcubed || {};

    function parseSportsAreaId(value) {
        var parsed = parseInt(value, 10);
        return isNaN(parsed) ? null : parsed;
    }

    function getRoot(controlId) {
        return document.getElementById(controlId);
    }

    function getSportsAreaLinks(root) {
        return root.querySelectorAll(".sidebar-link[data-sports-area-id]");
    }

    function getActiveLink(root) {
        return root.querySelector(".sidebar-link.is-active[data-sports-area-id]");
    }

    function getActiveSportsAreaId(root) {
        var active = getActiveLink(root);
        if (!active) {
            return null;
        }

        return parseSportsAreaId(active.getAttribute("data-sports-area-id"));
    }

    function setActiveLink(root, sportsAreaId) {
        var links = getSportsAreaLinks(root);

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var linkSportsAreaId = parseSportsAreaId(link.getAttribute("data-sports-area-id"));

            if (linkSportsAreaId === sportsAreaId) {
                link.classList.add("is-active");
                link.setAttribute("aria-current", "true");
            } else {
                link.classList.remove("is-active");
                link.removeAttribute("aria-current");
            }
        }
    }

    function recordSportsArea(root, sportsAreaId) {
        if (window.qcubed && typeof window.qcubed.recordControlModification === "function") {
            window.qcubed.recordControlModification(root.id, "_SportsAreaId", sportsAreaId);
        }

        if (window.jQuery) {
            window.jQuery(root).trigger("selectsportsarea");
        }
    }

    function setSportsAreaAndTrigger(root, sportsAreaId) {
        if (sportsAreaId === null) {
            return;
        }

        var currentActiveSportsAreaId = getActiveSportsAreaId(root);

        if (currentActiveSportsAreaId === sportsAreaId) {
            return;
        }

        setActiveLink(root, sportsAreaId);
        recordSportsArea(root, sportsAreaId);
    }

    function bindClicks(root) {
        if (root.dataset.sportsAreasSidebarBound === "1") {
            return;
        }

        root.dataset.sportsAreasSidebarBound = "1";

        root.addEventListener("click", function (e) {
            var a = e.target && e.target.closest ? e.target.closest("a") : null;

            if (!a || !root.contains(a)) {
                return;
            }

            if (a.classList.contains("sidebar-link") && a.hasAttribute("data-sports-area-id")) {
                var sportsAreaId = parseSportsAreaId(a.getAttribute("data-sports-area-id"));

                if (sportsAreaId === null) {
                    return;
                }

                e.preventDefault();
                setSportsAreaAndTrigger(root, sportsAreaId);
            }
        });
    }

    function init(root) {
        if (!root) {
            return;
        }

        bindClicks(root);
    }

    window.qcubed.sportsAreasSidebar = function (controlId, arg2) {
        var root = getRoot(controlId);

        if (!root) {
            return;
        }

        if (typeof arg2 === "string") {
            if (arg2 === "refresh" || arg2 === "init") {
                init(root);
            }
            return;
        }

        init(root);
    };

    if ($ && $.fn) {
        $.fn.sportsAreasSidebar = function (arg2) {
            return this.each(function () {
                var id = this && this.id ? this.id : null;

                if (!id) {
                    return;
                }

                window.qcubed.sportsAreasSidebar(id, arg2);
            });
        };
    }
})(window, document, window.jQuery);