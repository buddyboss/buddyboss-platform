<?php
/**
 * BuddyPress Media - Users Photos
 *
 * @package WordPress
 * @subpackage BuddyBoss Media
 */
?>

<?php //do_action( 'template_notices' ); ?>

<h2 class="entry-title"><?php _e( 'Albums', 'buddyboss-media' );?>
	<?php
	if( bp_is_my_profile() ){
		global $bp;
		$create_album_url = $bp->displayed_user->domain . buddyboss_media_component_slug() . '/albums/';
		$create_album_url = esc_url(add_query_arg( 'album', 'new', $create_album_url ));
		?>
		<a href="<?php echo esc_url( $create_album_url );?>" class="button album-create bp-title-button">
			<?php _e( 'Create an Album', 'buddyboss-media' );?>
		</a>
		<?php
	}

	//Has an albums to show
	$has_album = false;
	?>
</h2>

	<form action="" method="post" id="albums-directory-form" class="dir-form">

		<div id="albums-dir-list" class="albums dir-list">

			<?php if ( buddyboss_media_has_albums( ) ) : ?>

				<div class="bp-pagination top">
					<?php buddyboss_media_albums_pagination_links(); ?>
				</div>

				<ul id="members-list" class="<?php bp_nouveau_loop_classes(); ?>" role="main">

				<?php while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
					<?php if ( buddyboss_media_user_can_access_album() ): ?>

					<?php if( false === $has_album ) {
							$has_album = true;
						} ?>

						<li id='album-<?php echo buddyboss_media_album_id();?>' class="vcard">
							<div class="list-wrap">

								<div class="item-avatar">
									<a href='<?php buddyboss_media_album_permalink();?>'>
										<?php buddyboss_media_album_avatar( 'width=50&height=50' ); ?>
									</a>
								</div>

								<div class="item">

									<div class="item-block">

										<div class="item-title member-name">
											<a href='<?php buddyboss_media_album_permalink();?>'><?php buddyboss_media_album_title(); ?></a>
										</div>

										<div class="item-meta last-activity">
											<span class="activity photos-count">
												<?php buddyboss_media_album_photos_count(); ?> / <?php buddyboss_media_album_date(); ?>
											</span>
										</div>

										<div class="user-update" style="width: 100%;">
											<p class="update">
												<?php buddyboss_media_album_short_description(); ?>
											</p>
										</div>
									</div>

								</div>

								<div class="clear"></div>
							</div><!-- .list-wrap -->
						</li>

					<?php endif; ?>
				<?php endwhile; ?>

				</ul>

				<div class="bp-pagination bottom">
					<?php buddyboss_media_albums_pagination_links(); ?>
				</div>

			<?php endif; ?>

			<?php if ( false === $has_album ): ?>
				<div id="message" class="info">
					<p><?php _e( 'There were no albums found.', 'buddyboss-media' ); ?></p>
				</div>
			<?php endif; ?>

		</div><!-- #albums-dir-list -->

	</form><!-- #albums-directory-form -->
