(function (window, document, $) {
    function getDialog(id) {
        const el = document.getElementById(id);
        if (!el || el.nodeName !== "DIALOG") return null;
        return el;
    }

    const optionsById = new Map();

    function applyTitle(dialog, title) {
        const titleEl = dialog.querySelector(".modal-title");
        const t = (title || "").trim();
        if (titleEl) titleEl.textContent = t;
        dialog.classList.toggle("has-title", t.length > 0);
    }

    function applyHasCloseButton(dialog, hasCloseButton) {
        const enabled = hasCloseButton !== false;
        dialog.classList.toggle("has-close-button", enabled);

        const btn = dialog.querySelector(".modal-close");
        if (!btn) return;
        btn.style.display = enabled ? "" : "none";
    }

    function applyBackdropMode(dialog, backdrop) {
        const hasDim = backdrop !== false;
        dialog.classList.toggle("has-backdrop", hasDim);
        dialog.classList.toggle("no-backdrop", !hasDim);
    }

    function applySize(dialog, size) {
        dialog.classList.remove("modal-sm", "modal-md", "modal-lg");
        if (size === "sm" || size === "md" || size === "lg") {
            dialog.classList.add("modal-" + size);
        } else {
            dialog.classList.add("modal-md");
        }
    }

    function applyHeaderClass(dialog, headerClass) {
        const header = dialog.querySelector(".modal-header");
        if (!header) return;

        const prev = header.dataset.qcModalHeaderClass;
        if (prev) header.classList.remove(prev);

        const next = (headerClass || "").trim();
        if (next) header.classList.add(next);

        header.dataset.qcModalHeaderClass = next;
    }

    function wire(dialog) {
        if (dialog.dataset.qcModalWired === "1") return;
        dialog.dataset.qcModalWired = "1";

        dialog.addEventListener("click", (e) => {
            const closeBtn = e.target.closest("[data-modal-close='1']");
            if (closeBtn) {
                e.preventDefault();
                if (dialog.open) dialog.close("close");
                return;
            }

            if (e.target !== dialog) return;

            const opts = optionsById.get(dialog.id) || {};
            if (opts.backdrop === "static") return;
            if (opts.backdrop === false) return;

            dialog.close("backdrop");
        });

        dialog.addEventListener(
            "cancel",
            (e) => {
                const opts = optionsById.get(dialog.id) || {};
                if (opts.closeOnEscape === false) {
                    e.preventDefault();
                }
            },
            { passive: false }
        );
    }

    window.qcubed = window.qcubed || {};

    window.qcubed.qcModal = function (controlId, arg2) {
        const dialog = getDialog(controlId);
        if (!dialog) return;

        wire(dialog);

        if (typeof arg2 === "string") {
            const opts = optionsById.get(controlId) || {};

            if (arg2 === "open") {
                if (dialog.open) return;

                document.querySelectorAll("dialog.modal[open]").forEach((d) => {
                    if (d !== dialog) d.close("stack");
                });

                dialog.showModal();

                // Re-apply state in case options changed since initial setup
                applyTitle(dialog, opts.title);
                applyHasCloseButton(dialog, opts.hasCloseButton);

                applyBackdropMode(dialog, opts.backdrop);
                applySize(dialog, opts.size);
                applyHeaderClass(dialog, opts.headerClass);
            } else if (arg2 === "close") {
                if (dialog.open) dialog.close("close");
            }
            return;
        }

        const opts = arg2 || {};
        optionsById.set(controlId, opts);

        if ("title" in opts) applyTitle(dialog, opts.title);
        if ("hasCloseButton" in opts) applyHasCloseButton(dialog, opts.hasCloseButton);
        if ("backdrop" in opts) applyBackdropMode(dialog, opts.backdrop);
        if ("size" in opts) applySize(dialog, opts.size);
        if ("headerClass" in opts) applyHeaderClass(dialog, opts.headerClass);
    };

    // ... existing code ...
    // jQuery plugin wrapper so QCubed (and manual calls) can do: $("#id").qcModal(...)
    if ($ && $.fn) {
        $.fn.qcModal = function (arg2) {
            return this.each(function () {
                const id = this && this.id ? this.id : null;
                if (!id) return;
                window.qcubed.qcModal(id, arg2);
            });
        };
    }
})(window, document, window.jQuery);
