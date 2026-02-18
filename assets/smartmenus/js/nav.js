$(function() {
    $('.main-menu').smartmenus({
            mainMenuSubOffsetX: 0,
            mainMenuSubOffsetY: 12,

            subMenusSubOffsetX: -4, // visual air
            subMenusSubOffsetY: 0,

            hideTimeout: 250,   // <-- VERY IMPORTANT
            showTimeout: 0
        }
    );

    // DESIRED:
    // mainMenuSubOffsetY: 10–14px
    // subMenusSubOffsetX: 4–8px

    const openBtn = document.querySelector('.mobile-nav-toggle');
    const closeBtn = document.querySelector('.mobile-nav-close');
    const nav = document.getElementById('.mobile-nav');
    const backdrop = document.querySelector('.mobile-backdrop');

    function openNav() {
        nav.classList.add('is-open');
        document.body.classList.add('is-mobile-nav-open');

        nav.setAttribute('aria-hidden', 'false');
        openBtn.setAttribute('aria-expanded', 'true');
        backdrop.hidden = false;
    }

    function closeNav() {
        nav.classList.remove('is-open');
        document.body.classList.remove('is-mobile-nav-open');

        nav.setAttribute('aria-hidden', 'true');
        openBtn.setAttribute('aria-expanded', 'false');
        backdrop.hidden = true;
    }

    openBtn.addEventListener('click', openNav);
    closeBtn.addEventListener('click', closeNav);
    backdrop.addEventListener('click', closeNav);

    // Accordion
    document.querySelectorAll('.mobile-menu li.has-children').forEach(li => {
        const submenu = li.querySelector(':scope > ul.submenu');
        const link = li.querySelector(':scope > a');

        if (!submenu || !link) return;

        // mark that there is actually a submenu (for CSS)
        li.classList.add('has-submenu');

        link.addEventListener('click', (e) => {
            // on mobile: parent does not navigate, but opens
            e.preventDefault();

            const isOpen = li.classList.contains('is-open');

            // 1) close all others at the same level
            const siblings = li.parentElement.querySelectorAll(':scope > li.has-children.is-open');
            siblings.forEach(sib => {
                if (sib !== li) {
                    sib.classList.remove('is-open');
                    // also close all descendants
                    sib.querySelectorAll('li.has-children.is-open').forEach(d => d.classList.remove('is-open'));
                }
            });

            // 2) oggle this item
            if (isOpen) {
                li.classList.remove('is-open');
                // if you close it, close all the subordinates too
                li.querySelectorAll('li.has-children.is-open').forEach(d => d.classList.remove('is-open'));
            } else {
                li.classList.add('is-open');
            }
        });
    });
});