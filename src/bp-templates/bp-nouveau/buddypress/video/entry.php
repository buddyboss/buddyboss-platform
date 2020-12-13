<?php
/**
 * BuddyBoss - Video Entry
 *
 * @since BuddyBoss 1.0.0
 */

?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_video_id(); ?>" data-date-created="<?php bp_video_date_created(); ?>">

    <div class="bb-video-thumb">
        <!--<a class="bb-open-video-theatre bb-video-cover-wrap"
           data-id="<?php bp_video_id(); ?>"
           data-attachment-full="<?php bp_video_attachment_image(); ?>"
           data-activity-id="<?php bp_video_activity_id(); ?>"
           data-privacy="<?php bp_video_privacy(); ?>"
           data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>"
           data-album-id="<?php bp_video_album_id(); ?>"
           data-group-id="<?php bp_video_group_id(); ?>"
		   data-attachment-id="<?php bp_video_attachment_id(); ?>"
           href="#">
           <img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy"/>
        </a>-->
        <video id="video-<?php bp_video_id(); ?>" class="video-js" controls preload="auto" poster="<?php bp_video_attachment_image(); ?>" data-setup='{"fluid": true}'>
            <source src="<?php bp_video_link(); ?>" type="<?php bp_video_type(); ?>"></source>
        </video>
        <?php
		$video_privacy  = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
		$can_manage = ( true === (bool) $video_privacy['can_manage'] ) ? true : false;
		if ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_video() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) : ?>
            <div class="bb-video-check-wrap">
                <input id="bb-video-<?php bp_video_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_video_id(); ?>" name="bb-video-select" />
                <label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Select', 'buddyboss' ); ?>" for="bb-video-<?php bp_video_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
            </div>
        <?php endif; ?>
    </div>

</li>
