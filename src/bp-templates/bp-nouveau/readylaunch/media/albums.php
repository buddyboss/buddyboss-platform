<?php
/**
 * ReadyLaunch - Media Albums template.
 *
 * This template handles displaying media albums listing and management.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_group = bp_is_group();
if ( ( ( bp_is_my_profile() && bb_user_can_create_media() ) || ( $bp_is_group && groups_can_user_manage_albums( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) {
	$bp_is_group_albums_support_enabled   = bp_is_group_albums_support_enabled();
	$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
	?>

	<?php
	if ( bp_has_albums( bp_ajax_querystring( 'albums' ) ) ) {
		$count = bp_media_get_total_group_album_count();
		?>
		<div class="bb-media-actions-wrap album-actions-wrap bb-rl-media-actions-wrap">
			<h2 class="bb-title">
				<div class="bb-item-count">
					<?php
					printf(
						wp_kses(
						/* translators: %d is the album count */
							_n(
								'<span class="bb-count">%d</span> Album',
								'<span class="bb-count">%d</span> Albums',
								$count,
								'buddyboss'
							),
							array( 'span' => array( 'class' => true ) )
						),
						(int) $count
					);
					?>
				</div>
			</h2>
			<?php
			if ( ( $bp_is_group && $bp_is_group_albums_support_enabled ) || $bp_is_profile_albums_support_enabled ) {
				?>
				<div class="bb-media-actions">
					<a href="#" id="bb-create-album" class="bb-create-album button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-images"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
				</div>
				<?php
			}
			?>
		</div>

		<?php
		if ( ( $bp_is_group && $bp_is_group_albums_support_enabled ) || $bp_is_profile_albums_support_enabled ) {
			bp_get_template_part( 'media/create-album' );
		}
	}
} else {
	?>
	<div class="bb-media-actions-wrap album-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Albums', 'buddyboss' ); ?></h2>
	</div>
	<?php
}

bp_nouveau_media_hook( 'before', 'media_album_content' );

if ( bp_has_albums( bp_ajax_querystring( 'albums' ) ) ) :
	?>
	<div id="albums-dir-list" class="bb-albums bb-albums-dir-list">
		<?php
		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
			?>
			<ul class="bb-albums-list">
			<?php
		endif;


		while ( bp_album() ) :
			bp_the_album();

			bp_get_template_part( 'media/album-entry' );

		endwhile;

		if ( bp_album_has_more_items() ) :
			?>
			<li class="load-more">
				<a class="button outline" href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</li>
			<?php
		endif;

		if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
			?>
			</ul>
			<?php
		endif;
		?>
	</div>
	<?php

else :
	?>
	<div class="bb-rl-media-none">
		<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-image"></i></div>
		<?php
		bp_nouveau_user_feedback( 'media-album-none' );
		if ( ( $bp_is_group && $bp_is_group_albums_support_enabled ) || $bp_is_profile_albums_support_enabled ) {
			?>
			<div class="bb-media-actions bb-rl-media-none-actions">
				<a href="#" id="bb-create-album" class="bb-create-album button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-images"></i> <?php esc_html_e( 'Create Album', 'buddyboss' ); ?></a>
			</div>
			<?php
			bp_get_template_part( 'media/create-album' );
		}
		?>
	</div>
	<?php
endif;

bp_nouveau_media_hook( 'after', 'media_album_content' );
