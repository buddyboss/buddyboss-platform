<?php
/**
 * BuddyBoss - Media Single Album
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php
global $media_album_template;
$album_id = (int) bp_action_variable( 0 );
?>

<?php if ( bp_has_albums( array( 'include' => $album_id ) ) ) : ?>
	<?php
	while ( bp_album() ) :
		bp_the_album();

	    $total_media = $media_album_template->album->media['total'];
		?>
        <div id="bp-media-single-album">
            <div class="album-single-view" <?php echo $total_media == 0 ? 'no-photos' : ''; ?>>

                <div class="bb-single-album-header text-center">
                    <h4 class="bb-title" id="bp-single-album-title"><?php bp_album_title(); ?></h4>
                    <?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>
                        <input type="text" value="<?php bp_album_title(); ?>" placeholder="<?php _e( 'Title', 'buddyboss' ); ?>" id="bb-album-title" style="display: none;" />
                        <a href="#" id="bp-edit-album-title"><?php _e( 'edit', 'buddyboss' ); ?></a>
                        <a href="#" id="bp-save-album-title" style="display: none;" ><?php _e( 'save', 'buddyboss' ); ?></a>
                        <a href="#" id="bp-cancel-edit-album-title" style="display: none;" ><?php _e( 'cancel', 'buddyboss' ); ?></a>
                    <?php endif; ?>
                    <p>
                        <span><?php bp_core_format_date( $media_album_template->album->date_created ); ?></span><span class="bb-sep">&middot;</span><span><?php printf( _n( '%s photo', '%s photos', $media_album_template->album->media['total'], 'buddyboss' ), number_format_i18n( $media_album_template->album->media['total'] ) ); ?></span>
                    </p>
                </div>

	            <?php if ( bp_is_my_profile() || ( bp_is_group() && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) : ?>

                    <div class="bb-album-actions">
                        <a class="bb-delete button small outline error" id="bb-delete-album" href="#">
                            <?php _e( 'Delete Album', 'buddyboss' ); ?>
                        </a>

                        <a class="bb-add-photos button small outline" id="bp-add-media" href="#" >
                            <?php _e( 'Add Photos', 'buddyboss' ); ?>
                        </a>

	                    <?php if ( bp_is_my_profile() && ! bp_is_group() ) : ?>

                            <?php $privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>

                            <select id="bb-album-privacy">
                                <?php foreach ( $privacy_options as $k => $option ) { ?>
                                    <?php $selected = ''; if ( $k == bp_get_album_privacy() ) $selected = 'selected="selected"' ; ?>
                                    <option <?php echo $selected; ?> value="<?php echo $k; ?>"><?php echo $option; ?></option>
                                <?php } ?>
                            </select>

	                    <?php endif; ?>
                    </div>

	            <?php bp_get_template_part( 'media/uploader' ); ?>

	            <?php endif; ?>

                <?php bp_get_template_part( 'media/actions' ); ?>

                <div id="media-stream" class="media" data-bp-list="media">

                    <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'album-media-loading' ); ?></div>

                </div>

            </div>
        </div>
	<?php endwhile; ?>
<?php endif; ?>
