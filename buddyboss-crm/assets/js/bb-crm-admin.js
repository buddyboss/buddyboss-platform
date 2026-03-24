/**
 * BuddyBoss CRM Admin JavaScript
 *
 * @package BuddyBossCRM
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * BuddyBoss CRM Admin Object
     */
    const BbCrmAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            console.log('BuddyBoss CRM Admin initialized');
        },

        /**
         * Bind Events
         */
        bindEvents: function() {
            // Confirm delete actions
            $(document).on('click', '.submitdelete', this.confirmDelete);
        },

        /**
         * Confirm Delete
         */
        confirmDelete: function(e) {
            if (!confirm(bbCrmAdmin.strings.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        BbCrmAdmin.init();
    });

})(jQuery);
