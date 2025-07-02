/**
 * ReadyLaunch LifterLMS Integration JavaScript
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 */

(function($) {
    'use strict';

    // ReadyLaunch LifterLMS object
    window.BBReadylaunchLifterLMS = window.BBReadylaunchLifterLMS || {};

    /**
     * Initialize ReadyLaunch LifterLMS functionality
     */
    BBReadylaunchLifterLMS.init = function() {
        // Initialize course grid/list toggle
        BBReadylaunchLifterLMS.initCourseViewToggle();
    };

    /**
     * Initialize course view toggle (grid/list)
     */
    BBReadylaunchLifterLMS.initCourseViewToggle = function() {
        $('.bb-rl-lifterlms-view-toggle').on('click', function(e) {
            e.preventDefault();
            
            
        });
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BBReadylaunchLifterLMS.init();
    });

    // Initialize on AJAX content load (for dynamic content)
    $(document).on('bb_rl_content_loaded', function() {
        BBReadylaunchLifterLMS.init();
    });

})(jQuery); 