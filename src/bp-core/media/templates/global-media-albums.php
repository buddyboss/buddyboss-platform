<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 */

/* 
 * The template file to display the content of 'all media page'.
 * Making changes to this file is not advised.
 * To override this template file:
 *  - create a folder 'buddyboss-media' inside your active theme (or child theme)
 *  - copy this file and place in the folder mentioned above
 *  - and make changes to the new file (the one you just copied into your theme).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div id="buddypress">

	<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
		<ul>
			<?php $global_media_permalink = trailingslashit( get_permalink( buddyboss_media()->option('all-media-page') ) ); ?>
			<li id="photos-all"><a href="<?php echo esc_url( $global_media_permalink );?>"><?php _e( 'All Photos', 'buddyboss-media' );?></a></li>
			<li class="selected" id="albums-personal"><a href="<?php echo esc_url( $global_media_permalink );?>albums/"><?php _e( 'All Albums', 'buddyboss-media' );?></a></li>
		</ul>
	</div>
		
	<div id="albums-dir-list" class="albums dir-list">

		<?php if ( buddyboss_media_has_albums( ) ) : ?>

			<div id="pag-top" class="pagination no-ajax">

				<div class="pagination-links" id="album-dir-pag-top">

					<?php buddyboss_media_albums_pagination_links(); ?>

				</div>

			</div>

			<ul id="albums-list" class="item-list" role="main">

			<?php while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
				<li id='album-<?php echo buddyboss_media_album_id();?>'>
					<div class="item-avatar">
						<a href='<?php buddyboss_media_album_permalink();?>'>
							<?php buddyboss_media_album_avatar( 'width=50&height=50' ); ?>
						</a>
					</div>

					<div class="item">
						<div class="item-title"><a href='<?php buddyboss_media_album_permalink();?>'><?php buddyboss_media_album_title(); ?></a></div>
						<div class="item-meta">
							<span class="activity photos-count"><?php buddyboss_media_album_photos_count(); ?> / <?php buddyboss_media_album_date(); ?></span>
						</div>

						<div class="item-desc"><?php buddyboss_media_album_short_description(); ?></div>
					</div>

					<div class="clear"></div>
				</li>
			<?php endwhile; ?>

			</ul>

			<div id="pag-bottom" class="pagination no-ajax">

				<div class="pagination-links" id="album-dir-pag-bottom">

					<?php buddyboss_media_albums_pagination_links(); ?>

				</div>

			</div>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'There were no albums found.', 'buddyboss-media' ); ?></p>
			</div>

		<?php endif; ?>


	</div><!-- #albums-dir-list -->

</div>