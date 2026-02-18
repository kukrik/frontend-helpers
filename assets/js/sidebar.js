// sidebar.js
(function () {
    const SPEED_MS = 220;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function normalizePath(href) {
        try {
            const u = new URL(href, window.location.origin);
            let p = u.pathname || '/';
            p = decodeURIComponent(p);
            p = p.replace(/\/+$/, '') || '/';
            return p;
        } catch (e) {
            return null;
        }
    }

    function getDirectSubmenu(li) {
        return li.querySelector(':scope > ul.sidebar-submenu');
    }

    function openAnimated(li) {
        const submenu = getDirectSubmenu(li);
        if (!submenu) return;

        li.classList.add('is-open');

        if (prefersReducedMotion()) {
            submenu.style.maxHeight = 'none';
            return;
        }

        // Kui oli "none", mõõtmine ei tööta – nullime enne
        submenu.style.maxHeight = '0px';
        // järgmises kaadris paneme sihtkõrguse
        requestAnimationFrame(() => {
            const h = submenu.scrollHeight;
            submenu.style.maxHeight = h + 'px';
            // pärast animatsiooni "lukustame" none peale, et sisu kasvamine ei lõhuks
            window.setTimeout(() => {
                if (li.classList.contains('is-open')) {
                    submenu.style.maxHeight = 'none';
                }
            }, SPEED_MS + 30);
        });
    }

    function closeAnimated(li) {
        const submenu = getDirectSubmenu(li);
        if (!submenu) return;

        if (prefersReducedMotion()) {
            li.classList.remove('is-open');
            submenu.style.maxHeight = '0px';
            return;
        }

        // Kui open-is "none", peame enne võtma päris kõrguse
        const startHeight = submenu.scrollHeight;
        submenu.style.maxHeight = startHeight + 'px';

        requestAnimationFrame(() => {
            submenu.style.maxHeight = '0px';
            li.classList.remove('is-open');
        });
    }

    function closeDescendants(li) {
        li.querySelectorAll('li.has-children.is-open').forEach(d => {
            closeAnimated(d);
        });
    }

    function closeSiblings(li) {
        const parentUl = li.parentElement;
        if (!parentUl) return;

        parentUl.querySelectorAll(':scope > li.has-children.is-open').forEach(sib => {
            if (sib !== li) {
                closeDescendants(sib);
                closeAnimated(sib);
            }
        });
    }

    function ensureToggle(li) {
        let toggle = li.querySelector(':scope > .sidebar-toggle');
        if (toggle) return toggle;

        toggle = document.createElement('span');
        toggle.className = 'sidebar-toggle';
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('tabindex', '0');
        toggle.setAttribute('aria-label', 'Ava alammenüü');

        toggle.innerHTML = `
    <svg class="sidebar-toggle-icon" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
      <path d="M7 4.5 L13 10 L7 15.5" />
    </svg>
  `;

        li.appendChild(toggle);
        return toggle;
    }

    function toggleBranch(li) {
        const isOpen = li.classList.contains('is-open');

        // 1) sulge kõik samal tasemel olevad teised (nagu mobiilis)
        closeSiblings(li);

        // 2) toggle see item + sulge descendants kinni panemisel
        if (isOpen) {
            closeDescendants(li);
            closeAnimated(li);
        } else {
            openAnimated(li);
        }
    }

    function isModifiedClick(e) {
        // lase kasutajal avada link uues tabis vms (ära takista)
        return e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1;
    }

    // Init: accordion käitumine
    document.querySelectorAll('.sidebar-nav li.has-children').forEach(li => {
        const submenu = getDirectSubmenu(li);
        const link = li.querySelector(':scope > a.sidebar-link');

        if (!submenu || !link) return;

        li.classList.add('has-submenu');

        const toggle = ensureToggle(li);

        // 1) Klikk ükskõik kus rea peal (link on block) avab/sulgeb
        link.addEventListener('click', (e) => {
            if (isModifiedClick(e)) return;

            // Kui haru on kinni -> avame ja EI navigeeri
            if (!li.classList.contains('is-open')) {
                e.preventDefault();
                toggleBranch(li);
                return;
            }

            // Kui haru on lahti -> lubame navigeerida (ei preventDefault)
        });

        // 2) Noole klikk töötab endiselt (kui keegi eelistab sinna vajutada)
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleBranch(li);
        });

        toggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleBranch(li);
            }
        });
    });

    // URL järgi aktiivne rada + automaatne avamine (ja korrektne max-height)
    const current = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
    const links = Array.from(document.querySelectorAll('.sidebar-nav a.sidebar-link[href]'));

    let best = null;
    let bestLen = -1;

    links.forEach(a => {
        const p = normalizePath(a.getAttribute('href'));
        if (!p) return;

        // täpne match
        if (p === current) {
            best = a;
            bestLen = p.length;
            return;
        }

        // prefiks-match (pikim võidab)
        if (p !== '/' && (current === p || current.startsWith(p + '/'))) {
            if (p.length > bestLen) {
                best = a;
                bestLen = p.length;
            }
        }
    });

    if (best) {
        best.classList.add('is-active');

        // Ava parent-harud alt üles, et scrollHeight oleks õige
        const chain = [];
        let node = best.closest('li.has-children');
        while (node) {
            chain.unshift(node);
            node = node.parentElement ? node.parentElement.closest('li.has-children') : null;
        }

        // Kui tahad “ainult üks haru lahti”, siis aktiivse raja avamisel
        // sulgeme sama taseme teised igal tasemel:
        chain.forEach(li => {
            closeSiblings(li);
            openAnimated(li);
        });
    }
})();