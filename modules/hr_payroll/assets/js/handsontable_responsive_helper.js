/**
 * HR Payroll – Global helper to make any Handsontable responsive.
 * Handles: window resize, browser zoom (Ctrl +/-), container resize,
 *          and RTL header/body alignment.
 * Does NOT interfere with sidebar animation.
 */
(function(window) {
    'use strict';

    if (typeof window._hrp_makeHotResponsive === 'function') {
        return;
    }

    /**
     * Check whether the sidebar is currently animating.
     * Perfex adds/removes 'hide-sidebar'/'show-sidebar' during the animation.
     * We also check jQuery's :animated to be extra safe.
     */
    function isSidebarAnimating() {
        try {
            if (jQuery && jQuery('#side-menu').is(':animated')) return true;
            if (jQuery && jQuery('#wrapper').is(':animated')) return true;
        } catch (e) {}
        return false;
    }

    window._hrp_makeHotResponsive = function(hotInstance) {
        if (!hotInstance || !hotInstance.rootElement) return;

        var el = hotInstance.rootElement;
        var debounceTimer = null;
        var rafPending = false;
        var lastWidth = 0;

        function scheduleRedraw(delay) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                if (isSidebarAnimating()) {
                    // Retry after animation likely finished
                    scheduleRedraw(400);
                    return;
                }

                if (rafPending) return;
                rafPending = true;

                window.requestAnimationFrame(function() {
                    rafPending = false;
                    try {
                        if (!el) return;

                        var newWidth = el.clientWidth;
                        if (newWidth <= 0) return;

                        // Only redraw if the width actually changed meaningfully
                        if (Math.abs(newWidth - lastWidth) < 5) return;
                        lastWidth = newWidth;

                        hotInstance.updateSettings({ width: newWidth });

                        if (typeof hotInstance.refreshDimensions === 'function') {
                            hotInstance.refreshDimensions();
                        }

                        hotInstance.render();
                    } catch (e) {}
                });
            }, delay || 250);
        }

        // Window resize
        window.addEventListener('resize', function() { scheduleRedraw(300); });

        // Browser zoom (Ctrl + wheel)
        window.addEventListener('wheel', function(e) {
            if (e.ctrlKey) scheduleRedraw(300);
        }, { passive: true });

        // Container size changes
        if (typeof ResizeObserver !== 'undefined') {
            try {
                var observer = new ResizeObserver(function() {
                    // Short debounce: if a burst of resizes happens (e.g. sidebar animation),
                    // the timer keeps resetting and only the final layout triggers a redraw.
                    scheduleRedraw(400);
                });
                observer.observe(el);
                hotInstance._hrpObserver = observer;
            } catch (e) {}
        }

        // Initial draw (after DOM is settled)
        scheduleRedraw(500);

        /* ------------------------------------------------------------------
         * RTL header alignment fix (stretchH bug)
         * ---------------------------------------------------------------- */
        if (window._hrp_isRTL) {
            setTimeout(function() {
                try {
                    var settings = hotInstance.getSettings();
                    if (settings.stretchH && settings.stretchH !== 'none') {
                        hotInstance.updateSettings({ stretchH: 'none' });
                        if (typeof hotInstance.refreshDimensions === 'function') {
                            hotInstance.refreshDimensions();
                        }
                        hotInstance.render();
                    }

                    if (hotInstance.view && hotInstance.view.wt && hotInstance.view.wt.wtOverlays) {
                        var overlays = hotInstance.view.wt.wtOverlays;
                        if (typeof overlays.adjustElementsSize === 'function') {
                            overlays.adjustElementsSize(true);
                        }
                    }
                } catch (e) {}
            }, 600);
        }
    };

    /* Auto-wrap the Handsontable constructor */
    if (typeof window.Handsontable === 'function' && !window.Handsontable._hrpWrapped) {
        var OriginalHandsontable = window.Handsontable;

        var WrappedHandsontable = function(element, options) {
            var instance = new OriginalHandsontable(element, options);
            try { window._hrp_makeHotResponsive(instance); } catch (e) {}
            return instance;
        };

        for (var key in OriginalHandsontable) {
            if (OriginalHandsontable.hasOwnProperty(key)) {
                WrappedHandsontable[key] = OriginalHandsontable[key];
            }
        }
        WrappedHandsontable.prototype = OriginalHandsontable.prototype;
        WrappedHandsontable._hrpWrapped = true;

        window.Handsontable = WrappedHandsontable;
    }

})(window);