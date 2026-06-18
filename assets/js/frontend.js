(function () {
    const config = window.OmniaEightControl || {};

    function setupScrollReveal() {
        if (!config.scrollReveal || !('IntersectionObserver' in window)) {
            return;
        }

        const targets = document.querySelectorAll(
            'main section, .wp-block-group, .wp-block-columns, .entry-content > *'
        );

        targets.forEach((element) => element.classList.add('oe-reveal'));

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15 }
        );

        targets.forEach((element) => observer.observe(element));
    }

    function setupCursor() {
        if (!config.cursorEnabled || window.matchMedia('(pointer: coarse)').matches) {
            return;
        }

        const dot = document.createElement('span');
        dot.className = 'oe-cursor-dot';
        dot.setAttribute('aria-hidden', 'true');
        document.body.appendChild(dot);

        window.addEventListener('mousemove', (event) => {
            dot.style.transform = `translate(${event.clientX}px, ${event.clientY}px) translate(-50%, -50%)`;
        });

        document.addEventListener('mouseover', (event) => {
            if (event.target.closest('a, button, input, textarea, select, [role="button"]')) {
                dot.classList.add('is-hovering');
            }
        });

        document.addEventListener('mouseout', (event) => {
            if (event.target.closest('a, button, input, textarea, select, [role="button"]')) {
                dot.classList.remove('is-hovering');
            }
        });
    }

    function init() {
        setupScrollReveal();
        setupCursor();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
