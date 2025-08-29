/**
 * BuddyBoss Mothership Add-ons JavaScript
 */

jQuery(document).ready(function($) {
    // Handle add-on activation
    $('.mosh-activate-addon').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var plugin = button.data('plugin');
        
        button.prop('disabled', true).text('Activating...');
        
        $.ajax({
            url: MoshAddons.ajax_url,
            type: 'POST',
            data: {
                action: 'mosh_addon_activate',
                plugin: plugin,
                nonce: MoshAddons.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.removeClass('button-primary').addClass('button-secondary')
                          .text(MoshAddons.deactivate)
                          .removeClass('mosh-activate-addon')
                          .addClass('mosh-deactivate-addon');
                } else {
                    alert(response.data || 'Activation failed');
                }
            },
            error: function() {
                alert('Activation failed');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Handle add-on deactivation
    $('.mosh-deactivate-addon').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var plugin = button.data('plugin');
        
        button.prop('disabled', true).text('Deactivating...');
        
        $.ajax({
            url: MoshAddons.ajax_url,
            type: 'POST',
            data: {
                action: 'mosh_addon_deactivate',
                plugin: plugin,
                nonce: MoshAddons.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.removeClass('button-secondary').addClass('button-primary')
                          .text(MoshAddons.activate)
                          .removeClass('mosh-deactivate-addon')
                          .addClass('mosh-activate-addon');
                } else {
                    alert(response.data || 'Deactivation failed');
                }
            },
            error: function() {
                alert('Deactivation failed');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Handle add-on installation
    $('.mosh-install-addon').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var plugin = button.data('plugin');
        
        button.prop('disabled', true).text('Installing...');
        
        $.ajax({
            url: MoshAddons.ajax_url,
            type: 'POST',
            data: {
                action: 'mosh_addon_install',
                plugin: plugin,
                nonce: MoshAddons.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.activated) {
                        button.removeClass('button-primary').addClass('button-secondary')
                              .text(MoshAddons.deactivate)
                              .removeClass('mosh-install-addon')
                              .addClass('mosh-deactivate-addon')
                              .attr('data-plugin', response.data.basename);
                    } else {
                        button.removeClass('button-primary').addClass('button-secondary')
                              .text(MoshAddons.activate)
                              .removeClass('mosh-install-addon')
                              .addClass('mosh-activate-addon')
                              .attr('data-plugin', response.data.basename);
                    }
                    alert(response.data.message);
                } else {
                    alert(response.data || MoshAddons.install_failed);
                }
            },
            error: function() {
                alert(MoshAddons.install_failed);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
