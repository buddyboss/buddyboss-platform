<?php
/**
 * BuddyBoss - Members Media Single Album
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
                    <h4 class="bb-title"><?php bp_album_title(); ?></h4>
<!--                    <input v-if="can_edit&&edit" type="text" v-model="album.title" v-on:keydown.enter="update_album"/>-->
<!--                    <a href="#" v-if="can_edit&&!edit"-->
<!--                       @click.prevent="edit_album_name">--><?php //_e( 'edit', 'buddyboss' ); ?><!--</a>-->
<!--                    <a href="#" v-if="can_edit&&edit"-->
<!--                       @click.prevent="cancel_edit_album_name">--><?php //_e( 'save', 'buddyboss' ); ?><!--</a>-->
                    <p>
                        <span><?php bp_core_format_date( $media_album_template->album->date_created ); ?></span><span class="bb-sep">&middot;</span><span><?php echo $total_media; ?> <?php _e( 'photos', 'buddyboss' ); ?></span>
                    </p>
                </div>

                <div class="bb-album-actions">
<!--                    <a class="bb-delete button small outline error" href="#" v-if="can_edit"-->
<!--                       @click.prevent="delete_album">--><?php //_e( 'Delete Album', 'buddyboss' ); ?><!--</a>-->
                    <a class="bb-add-photos button small outline" id="bp-add-media" href="#" ><?php _e( 'Add Photos', 'buddyboss' ); ?></a>
<!--					--><?php //$privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>
<!--                    <select>-->
<!--						--><?php //foreach ( $privacy_options as $k => $option ) {
//							?>
<!--                            <option value="--><?php //echo $k; ?><!--">--><?php //echo $option; ?><!--</option>-->
<!--							--><?php
//						} ?>
<!--                    </select>-->
                </div>

	            <?php bp_get_template_part( 'members/single/media/uploader' ); ?>

                <?php if ( $total_media > 0 ) {
                    ?>
                    <ul class="bb-photo-list grid">
                        <?php foreach( $media_album_template->album->media['medias'] as $media ) {
                            ?>
                            <li data-id="<?php echo $media->id; ?>" class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3">

                                <div class="bb-photo-thumb">
                                    <a class="bb-photo-cover-wrap" href="#">
                                        <img src="<?php echo $media->attachment_data->thumb; ?>"/></a>
                                </div>

                            </li>
                            <?php
                        } ?>
                    </ul>
                    <?php
                } ?>

            </div>
        </div>
	<?php endwhile; ?>
<?php endif; ?>
