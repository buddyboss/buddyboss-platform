<?php
/**
 * BuddyBoss - Members Media Albums
 *
 * @since BuddyBoss 1.0.0
 */
?>

	<h2 class="screen-heading member-media-screen"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>

    <transition name="modal">
        <div class="modal-mask bb-white bbm-model-wrap">
            <div class="modal-wrapper">
                <div id="boss-media-create-album-popup" class="modal-container">

					<?php do_action( 'buddyboss_media_before_create_album' ); ?>

                    <header class="flex bb-model-header">
                        <h4><?php _e( 'Create Album', 'buddyboss' ); ?></h4>
                        <a class="push-right bb-model-close-button" href="#"><i class="feather icon-x"></i></a>
                    </header>

                    <div class="bb-field-wrap">
                        <label for="bb-album-title" class="bb-label"><?php _e( 'Title', 'buddyboss' ); ?></label>
                        <input id="bb-album-title" ref="title" type="text" placeholder="<?php _e( 'Enter Album Title', 'buddyboss' ); ?>" />
                    </div>

                    <div class="bb-field-wrap">
                        <label for="bb-enter-description" class="bb-label"><?php _e( 'Description', 'buddyboss' ); ?></label>
                        <textarea id="bb-enter-description" ref="description" placeholder="<?php _e( 'Enter Description', 'buddyboss' ); ?>"></textarea>
                    </div>

                    <footer class="flex align-items-center bb-model-footer">
                        <div class="bb-dropdown-wrap bb-hover">
							<?php $privacy_options = BP_Media_Privacy::instance()->get_visibility_options(); ?>
                            <select>
								<?php foreach ( $privacy_options as $k => $option ) {
									?>
                                    <option value="<?php echo $k; ?>"><?php echo $option; ?></option>
									<?php
								} ?>
                            </select>
                        </div>
                        <a class="button push-right" href="#"><?php _e( 'Create Album', 'buddyboss' ); ?></a>
                    </footer>

					<?php do_action( 'buddyboss_media_after_create_album' ); ?>
                </div>
            </div>
        </div>
    </transition>

<?php bp_nouveau_member_hook( 'before', 'media_album_content' ); ?>

<?php if ( bp_has_albums() ) : ?>

    <div id="members-photos-dir-list" class="bb-member-photos bb-photos-dir-list">

		<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        <ul class="bb-photo-list grid">
			<?php endif; ?>

			<?php
			while ( bp_album() ) :
				bp_the_album();
				?>

				<?php bp_album_title(); ?>

			<?php endwhile; ?>

			<?php if ( bp_album_has_more_items() ) : ?>

                <li class="load-more">
                    <a href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
                </li>

			<?php endif; ?>

			<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
        </ul>
	<?php endif; ?>

    </div>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'member-media-album-none' ); ?>

<?php endif; ?>


<?php
bp_nouveau_member_hook( 'after', 'media_album_content' );
