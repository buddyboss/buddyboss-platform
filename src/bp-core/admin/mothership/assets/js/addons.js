/**
 * BuddyBoss Addons JavaScript
 */

(function($) {
    'use strict';

    var BBAddonsManager = {
        
        /**
         * Initialize.
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events.
         */
        bindEvents: function() {
            $(document).on('click', '.bb-addon-install', this.installAddon);
            $(document).on('click', '.bb-addon-activate', this.activateAddon);
            $(document).on('click', '.bb-addon-deactivate', this.deactivateAddon);
        },

        /**
         * Install addon.
         */
        installAddon: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $card = $button.closest('.bb-addon-card');
            var plugin = $button.data('plugin');
            var type = $button.data('type') || 'add-on';
            
            if (!plugin) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $card.addClass('loading');
            $button.text(type === 'plugin' ? 'Installing Plugin...' : 'Installing Add-on...');
            
            $.ajax({
                url: BBAddons.ajax_url,
                type: 'POST',
                data: {
                    action: 'bb_addon_install',
                    plugin: plugin,
                    type: type,
                    _ajax_nonce: BBAddons.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.activated) {
                            // Addon was activated successfully
                            BBAddonsManager.updateCardStatus($card, 'active', response.data.basename);
                            BBAddonsManager.showNotice(response.data.message, 'success');
                        } else {
                            // Addon was installed but not activated
                            BBAddonsManager.updateCardStatus($card, 'inactive', response.data.basename);
                            BBAddonsManager.showNotice(response.data.message, 'success');
                        }
                    } else {
                        BBAddonsManager.showNotice(response.data || BBAddons.install_failed, 'error');
                        $button.prop('disabled', false);
                        $button.text('Install');
                    }
                },
                error: function() {
                    BBAddonsManager.showNotice(BBAddons.install_failed, 'error');
                    $button.prop('disabled', false);
                    $button.text('Install');
                },
                complete: function() {
                    $card.removeClass('loading');
                }
            });
        },

        /**
         * Activate addon.
         */
        activateAddon: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $card = $button.closest('.bb-addon-card');
            var plugin = $button.data('plugin');
            var type = $button.data('type') || 'add-on';
            
            if (!plugin) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $card.addClass('loading');
            $button.text('Activating...');
            
            $.ajax({
                url: BBAddons.ajax_url,
                type: 'POST',
                data: {
                    action: 'bb_addon_activate',
                    plugin: plugin,
                    type: type,
                    _ajax_nonce: BBAddons.nonce
                },
                success: function(response) {
                    if (response.success) {
                        BBAddonsManager.updateCardStatus($card, 'active', plugin);
                        BBAddonsManager.showNotice(response.data, 'success');
                    } else {
                        BBAddonsManager.showNotice(response.data, 'error');
                        $button.prop('disabled', false);
                        $button.text(BBAddons.activate);
                    }
                },
                error: function() {
                    BBAddonsManager.showNotice('An error occurred while activating.', 'error');
                    $button.prop('disabled', false);
                    $button.text(BBAddons.activate);
                },
                complete: function() {
                    $card.removeClass('loading');
                }
            });
        },

        /**
         * Deactivate addon.
         */
        deactivateAddon: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $card = $button.closest('.bb-addon-card');
            var plugin = $button.data('plugin');
            var type = $button.data('type') || 'add-on';
            
            if (!plugin) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true);
            $card.addClass('loading');
            $button.text('Deactivating...');
            
            $.ajax({
                url: BBAddons.ajax_url,
                type: 'POST',
                data: {
                    action: 'bb_addon_deactivate',
                    plugin: plugin,
                    type: type,
                    _ajax_nonce: BBAddons.nonce
                },
                success: function(response) {
                    if (response.success) {
                        BBAddonsManager.updateCardStatus($card, 'inactive', plugin);
                        BBAddonsManager.showNotice(response.data, 'success');
                    } else {
                        BBAddonsManager.showNotice(response.data, 'error');
                        $button.prop('disabled', false);
                        $button.text(BBAddons.deactivate);
                    }
                },
                error: function() {
                    BBAddonsManager.showNotice('An error occurred while deactivating.', 'error');
                    $button.prop('disabled', false);
                    $button.text(BBAddons.deactivate);
                },
                complete: function() {
                    $card.removeClass('loading');
                }
            });
        },

        /**
         * Update card status.
         */
        updateCardStatus: function($card, status, plugin) {
            var $status = $card.find('.bb-addon-status-badge');
            var $actions = $card.find('.bb-addon-actions');
            
            // Update status badge
            $status.removeClass('bb-addon-status-active bb-addon-status-inactive bb-addon-status-not-installed');
            
            if (status === 'active') {
                $status.addClass('bb-addon-status-active').text(BBAddons.active);
                
                // Update actions
                $actions.html(
                    '<button class="button bb-addon-deactivate" data-plugin="' + plugin + '">' +
                    BBAddons.deactivate +
                    '</button>'
                );
                
            } else if (status === 'inactive') {
                $status.addClass('bb-addon-status-inactive').text(BBAddons.inactive);
                
                // Update actions
                $actions.html(
                    '<button class="button button-primary bb-addon-activate" data-plugin="' + plugin + '">' +
                    BBAddons.activate +
                    '</button>'
                );
            }
            
            // Update card data attribute
            $card.attr('data-plugin', plugin);
        },

        /**
         * Show notice.
         */
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $(
                '<div class="notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
                '</div>'
            );
            
            // Insert notice after page title
            $('.wrap h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
            
            // Handle manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BBAddonsManager.init();
    });

})(jQuery);