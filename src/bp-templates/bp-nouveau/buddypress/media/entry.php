<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_media_id(); ?>" data-date-created="<?php bp_media_date_created(); ?>">

    <div class="bb-photo-thumb">
        <a class="bb-open-media-theatre bb-photo-cover-wrap"
           data-id="<?php bp_media_id(); ?>"
           data-attachment-full="<?php bp_media_attachment_image(); ?>"
           data-activity-id="<?php bp_media_activity_id(); ?>"
           data-privacy="<?php bp_media_privacy(); ?>"
           data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>"
           data-album-id="<?php bp_media_album_id(); ?>"
           data-group-id="<?php bp_media_group_id(); ?>"
           href="#">
           <img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_media_attachment_image_thumbnail(); ?>" alt="<?php bp_media_title(); ?>" class="lazy"/>
        </a>
        <?php if ( bp_is_my_profile() || ( bp_is_group() && ( ( bp_is_group_media() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) || ( bp_is_group_albums() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) ) : ?>
            <div class="bb-media-check-wrap">
                <input id="bb-media-<?php bp_media_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_media_id(); ?>" name="bb-media-select" />
                <label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Select', 'buddyboss' ); ?>" for="bb-media-<?php bp_media_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
            </div>
        <?php endif; ?>
    </div>

</li>
