/**
 * Popover Maker - Frontend JavaScript
 *
 * @package Popover_Maker
 */

(function() {
    'use strict';

    /**
     * Initialize the popover functionality.
     */
    function popmInit() {
        var overlay = document.querySelector('.popm-overlay');
        if (!overlay) {
            return;
        }

        // Auto-open: add body class immediately
        popmOpen();

        // Bind event listeners
        bindCloseButton();
        bindOverlayClick();
        bindEscKey();
    }

    /**
     * Open the popover (add body class for scroll lock, focus close button).
     */
    function popmOpen() {
        document.body.classList.add('popm-active');

        // Focus management: focus the close button for accessibility
        var closeBtn = document.querySelector('.popm-close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    /**
     * Close the popover and set dismissal cookie.
     */
    function popmClose() {
        var overlay = document.querySelector('.popm-overlay');
        if (!overlay) {
            return;
        }

        // Get data before removing from DOM
        var popoverId = overlay.getAttribute('data-popover-id');
        var cookieDays = parseInt(overlay.getAttribute('data-cookie-days'), 10);

        // Remove from DOM
        overlay.parentNode.removeChild(overlay);

        // Remove body class
        document.body.classList.remove('popm-active');

        // Set dismissal cookie if cookie_days > 0
        if (cookieDays > 0) {
            popmSetCookie(popoverId, cookieDays);
        }
    }

    /**
     * Set the dismissal cookie.
     *
     * @param {string} popoverId The popover post ID.
     * @param {number} days Number of days until cookie expires.
     */
    function popmSetCookie(popoverId, days) {
        var cookieName = 'popm_dismissed_' + popoverId;
        var expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = cookieName + '=1; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
    }

    /**
     * Bind click event to close button.
     */
    function bindCloseButton() {
        var closeBtn = document.querySelector('.popm-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                popmClose();
            });
        }
    }

    /**
     * Bind click event to overlay (close when clicking backdrop).
     */
    function bindOverlayClick() {
        var overlay = document.querySelector('.popm-overlay');
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                // Only close if clicking the overlay itself, not the popup content
                if (e.target === overlay) {
                    popmClose();
                }
            });
        }
    }

    /**
     * Bind ESC key to close popover.
     */
    function bindEscKey() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                var overlay = document.querySelector('.popm-overlay');
                if (overlay) {
                    popmClose();
                }
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', popmInit);
    } else {
        popmInit();
    }

})();
