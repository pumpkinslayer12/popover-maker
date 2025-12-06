/**
 * Popover Maker - Frontend JavaScript
 *
 * @package Popover_Maker
 */

(function() {
    'use strict';

    // Timestamp when popover was opened (for engagement tracking)
    var popmOpenTime = null;

    /**
     * Initialize the popover functionality.
     */
    function popmInit() {
        var overlay = document.querySelector('.popm-overlay');
        if (!overlay) {
            return;
        }

        // Store open timestamp for engagement tracking
        popmOpenTime = Date.now();

        // Auto-open: add body class immediately
        popmOpen();

        // Track view
        popmTrackView();

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

        // Track close with duration before removing
        popmTrackClose();

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
        // Add Secure flag for HTTPS environments.
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = cookieName + '=1; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax' + secure;
    }

    /**
     * Track view via AJAX.
     */
    function popmTrackView() {
        if (typeof popmData === 'undefined') {
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', popmData.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=popm_track_view&nonce=' + popmData.nonce + '&popover_id=' + popmData.popoverId);
    }

    /**
     * Track close with duration via AJAX.
     *
     * Duration determines engagement: >=5 seconds = engaged, <5 seconds = bounced.
     */
    function popmTrackClose() {
        if (typeof popmData === 'undefined') {
            return;
        }

        // Calculate duration in seconds
        var duration = popmOpenTime ? (Date.now() - popmOpenTime) / 1000 : 0;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', popmData.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(
            'action=popm_track_close' +
            '&nonce=' + popmData.nonce +
            '&popover_id=' + popmData.popoverId +
            '&duration=' + duration
        );
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
