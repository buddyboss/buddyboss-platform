/* global BP_LD_REPORTS_DATA */
(function($) {
    var BB_TutorLMS = {

        init: function() {
            if ( jQuery( '.buddyboss_page_bp-integrations .section-bb_tutorlms_posts_activity_settings_section' ).length ) {
                jQuery( '.bp-feed-post-type-checkbox' ).each(
                    function() {
                        var post_type = $( this ).data( 'post_type' );

                        if ( true === this.checked ) {
                            $( '.bp-feed-post-type-comment-' + post_type )
                                .closest( 'tr' )
                                .show();
                        }
                    }
                );

                jQuery( '.buddyboss_page_bp-integrations .section-bb_tutorlms_posts_activity_settings_section' ).on(
                    'click',
                    '.bp-feed-post-type-checkbox',
                    function () {
                        var post_type    = jQuery( this ).data( 'post_type' ),
                            commentField = jQuery( '.bp-feed-post-type-comment-' + post_type );

                        if ( true === this.checked ) {
                            commentField
                                .closest( 'tr' )
                                .show();
                        } else {
                            commentField
                                .prop( 'checked', false )
                                .closest( 'tr' )
                                .hide();
                        }
                    }
                );
            }
        },
    };

    $(
        function() {
            BB_TutorLMS.init();
        }
    );
})( jQuery );
