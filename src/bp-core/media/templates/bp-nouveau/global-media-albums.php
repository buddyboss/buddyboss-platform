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

<?php bp_nouveau_before_activity_directory_content(); ?>

<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav">

	<?php bp_nouveau_template_notices(); ?>

		<nav
				class="iactivity-type-navs main-navs bp-navs dir-navs"
				role="navigation"
				aria-label="<?php esc_attr_e( 'Directory menu', 'buddypress' ); ?>"
		>
			<ul class="component-navigation activity-nav">
				<?php $global_media_permalink = trailingslashit( _get_page_link( buddyboss_media()->option('all-media-page') ) ); ?>
				<li class="dynamic" id="photos-all">
					<a href="<?php echo esc_url( $global_media_permalink );?>"><?php _e( 'All Photos', 'buddyboss-media' );?></a>
				</li>
				<li class="dynamic selected" id="albums-personal">
					<a href="<?php echo esc_url( $global_media_permalink );?>albums/"><?php _e( 'All Albums', 'buddyboss-media' );?></a>
				</li>
			</ul>
		</nav>

	<div class="screen-content">

		<div id="albums-dir-list" class="albums dir-list">

			<?php if ( buddyboss_media_has_albums( ) ) : ?>

			<div class="bp-paginatio top">
				<?php buddyboss_media_albums_pagination_links(); ?>
			</div>

				<ul id="albums-list" class="item-list bp-list" role="main">

					<?php while ( buddyboss_media_albums() ) : buddyboss_media_the_album(); ?>
						<li id='album-<?php echo buddyboss_media_album_id();?>' class="item-entry">

							<div class="list-wrap">

								<div class="item-avatar">
									<a href='<?php buddyboss_media_album_permalink();?>'>
										<?php buddyboss_media_album_avatar( 'width=50&height=50' ); ?>
									</a>
								</div>

								<div class="item">
									<div class="item-title">
										<a href='<?php buddyboss_media_album_permalink();?>'><?php buddyboss_media_album_title(); ?></a>
									</div>
									<div class="item-meta">
										<span class="activity photos-count"><?php buddyboss_media_album_photos_count(); ?> / <?php buddyboss_media_album_date(); ?></span>
									</div>
									<div class="clear"></div>
									<div class="item-desc"><?php buddyboss_media_album_short_description(); ?></div>
								</div>

								<div class="clear"></div>

							</div><!-- list-wrap -->
						</li>
					<?php endwhile; ?>

				</ul>

				<div class="bp-pagination bottom">
					<?php buddyboss_media_albums_pagination_links(); ?>
				</div>

			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( 'There were no albums found.', 'buddyboss-media' ); ?></p>
				</div>

			<?php endif; ?>

		</div><!-- #albums-dir-list -->

	</div><!-- // .screen-content -->
</div>