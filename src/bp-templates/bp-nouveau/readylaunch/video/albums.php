<?php
/**
 * ReadyLaunch - Video Albums template.
 *
 * Template for displaying video albums.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_group = bp_is_group();
if ( bp_is_my_profile() || ( $bp_is_group && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) {
	$bp_is_group_albums_support_enabled   = bp_is_group_albums_support_enabled();
	$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
	?>
	<div class="bb-video-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>
		<?php
		if ( $bp_is_group && $bp_is_group_albums_support_enabled ) {
			?>
			<div class="bb-video-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button small outline"><i class="bb-icons-rl-plus"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
			</div>
			<?php
		} elseif ( $bp_is_profile_albums_support_enabled ) {
			?>
			<div class="bb-video-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button small outline"><i class="bb-icons-rl-plus"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
			</div>
		<?php } ?>
	</div>

	<?php
	if ( $bp_is_group && $bp_is_group_albums_support_enabled ) {
		bp_get_template_part( 'video/create-album' );
	} elseif ( $bp_is_profile_albums_support_enabled ) {
		bp_get_template_part( 'video/create-album' );
	}
}

bp_nouveau_video_hook( 'before', 'video_album_content' );

if ( bp_has_video_albums( bp_ajax_querystring( 'albums' ) ) ) {
	?>

	<div id="albums-dir-list" class="bb-albums bb-albums-dir-list">

		<?php
		$paged_page = filter_input( INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $paged_page ) || 1 === $paged_page ) {
			?>
			<ul class="bb-albums-list">
			<?php
		}

		while ( bp_video_album() ) :
			bp_the_video_album();

			bp_get_template_part( 'video/album-entry' );

		endwhile;

		if ( bp_video_album_has_more_items() ) {
			?>
			<li class="load-more">
				<a class="button outline" href="<?php bp_video_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</li>
			<?php
		}

		if ( empty( $paged_page ) || 1 === $paged_page ) {
			?>
			</ul>
			<?php
		}
		?>
	</div>
	<?php
} else {
	bp_get_template_part( 'video/no-video' );
}

bp_nouveau_video_hook( 'after', 'video_album_content' );
