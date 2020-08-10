<?php
/**
 * BuddyBoss - Media Entry
 *
 * @since BuddyBoss 1.0.0
 */

$attachment_id = bp_get_media_attachment_id();
$download_url  = bp_media_download_link( $attachment_id, bp_get_media_id() );
$group_id      = bp_get_media_group_id();
$move_id       = '';
$move_type     = '';

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_media_user_id();
	$move_type = 'profile';
}
?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_media_id(); ?>" data-date-created="<?php bp_media_date_created(); ?>">

    <div class="bb-photo-thumb">
        <div class="media-action-wrap">
            <a href="<?php echo $download_url; ?>" class="media-action_download" data-id="<?php bp_media_id(); ?>" data-activity-id="<?php bp_media_activity_id(); ?>" data-balloon-pos="up" data-balloon="<?php _e( 'Download', 'buddyboss' ); ?>">
                <i class="bb-icon-download"></i>
            </a>

            <a href="#" class="media-action_more" data-balloon-pos="up" data-balloon="<?php _e( 'More actions', 'buddyboss' ); ?>">
                <i class="bb-icon-menu-dots-v"></i>
            </a>
            <div class="media-action_list">
                <ul>
                    <li class="copy_download_file_url">
                        <a href="<?php echo $download_url; ?>"><?php _e( 'Copy Download Link', 'buddyboss' ); ?></a>
                    </li>
                    <li class="move_file">
                        <a href="#" data-action="media" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-media-move"><?php _e( 'Move', 'buddyboss' ); ?></a>
                    </li>
<!--                    <li class="delete_file">-->
<!--                        <a class="media-file-delete" data-item-activity-id="--><?php //bp_media_activity_id(); ?><!--" data-item-from="media" data-item-id="--><?php //bp_media_id(); ?><!--" data-type="media" href="#">--><?php //_e( 'Delete', 'buddyboss' ); ?><!--</a>-->
<!--                    </li>-->
                </ul>
            </div>
        </div> <!--.media-action-wrap-->
        <a class="bb-open-media-theatre bb-photo-cover-wrap"
           data-id="<?php bp_media_id(); ?>"
           data-attachment-full="<?php bp_media_attachment_image(); ?>"
           data-activity-id="<?php bp_media_activity_id(); ?>"
           data-privacy="<?php bp_media_privacy(); ?>"
           data-parent-activity-id="<?php bp_media_parent_activity_id(); ?>"
           data-album-id="<?php bp_media_album_id(); ?>"
           data-group-id="<?php bp_media_group_id(); ?>"
		   data-attachment-id="<?php bp_media_attachment_id(); ?>"
           href="#">
           <img src="<?php echo buddypress()->plugin_url; ?>bp-templates/bp-nouveau/images/placeholder.png" data-src="<?php bp_media_attachment_image_thumbnail(); ?>" alt="<?php bp_media_title(); ?>" class="lazy"/>
        </a>
        <?php
		$media_privacy  = bp_media_user_can_manage_media( bp_get_media_id(), bp_loggedin_user_id() );
		$can_manage = ( true === (bool) $media_privacy['can_manage'] ) ? true : false;
		if ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_media() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) : ?>
            <div class="bb-media-check-wrap">
                <input id="bb-media-<?php bp_media_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_media_id(); ?>" name="bb-media-select" />
                <label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php _e( 'Select', 'buddyboss' ); ?>" for="bb-media-<?php bp_media_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
            </div>
        <?php endif; ?>
    </div>

</li>
