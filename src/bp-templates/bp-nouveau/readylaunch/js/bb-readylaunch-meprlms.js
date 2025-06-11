/**
 * MemberPress Courses JavaScript for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

(function($) {
    'use strict';
    
    /**
     * MemberPress Courses ReadyLaunch functionality
     */
    var BBReadyLaunchMeprlms = {
        
        /**
         * Initialization
         */
        init: function() {
            this.addEvents();
        },

        addEvents: function() {
            $( document ).on( 'click', '.bb-rl-ld-lesson-list .ld-expand-button', this.handleLessonExpand );
        },

        handleLessonExpand: function(e) {
            e.preventDefault();
        },
    };
    
    /**
     * DOM ready
     */
    $(document).ready(function() {
        BBReadyLaunchMeprlms.init();
    });
    
})(jQuery); 