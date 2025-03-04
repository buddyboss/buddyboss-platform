<?php
/**
 * The template for medias
 *
 * This template can be overridden by copying it to yourtheme/buddypress/media/index.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();
?>
<div class="bb-rl-members-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Photos', 'buddyboss' ); ?> <span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? bp_core_get_all_member_count() : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
				bp_get_template_part( 'common/search-and-filters-bar' );
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">

		<?php
			/**
			 * Fires before the display of the members.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_before_directory_members' );
		?>

		<div class="bb-rl-members-directory-container flex">

			<?php
				/**
				 * Fires before the display of the members list tabs.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_members_tabs' );

				/**
				 * Fires before the display of the members content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_before_directory_members_content' );
			?>

			<div class="screen-content bb-rl-members-directory-content">

				<div id="bb-rl-members-dir-list" class="members dir-list bb-rl-members" data-bp-list="members" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
						if ( $is_send_ajax_request ) {
							echo '<div id="bp-ajax-loader">';
							?>
							<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
								<?php for ( $i = 0; $i < 8; $i++ ) : ?>
									<div class="bb-rl-skeleton-grid-block">
										<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
										<div class="bb-rl-skeleton-data">
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										</div>
										<div class="bb-rl-skeleton-footer">
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
											<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										</div>
									</div>
								<?php endfor; ?>
							</div>
							<?php
							//bp_nouveau_user_feedback( 'directory-members-loading' );
							echo '</div>';
						} else {
							bp_get_template_part( 'members/members-loop' );
						}
					?>
				</div><!-- #members-dir-list -->

				<?php
					/**
					 * Fires and displays the members content.
					 *
					 * @since BuddyBoss [BBVERSION]
					 */
					do_action( 'bp_directory_members_content' );
				?>
			</div><!-- // .screen-content -->

			<?php

				bp_get_template_part( 'sidebar/right-sidebar' );

				/**
				 * Fires after the display of the members content.
				 *
				 * @since BuddyBoss [BBVERSION]
				 */
				do_action( 'bp_after_directory_members_content' );
			?>

		</div>

		<?php
			/**
			 * Fires after the display of the members.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_after_directory_members' );
		?>
	</div>

</div>
<?php
bp_nouveau_before_media_directory_content();
bp_nouveau_template_notices();
?>

<div class="screen-content">
	<?php
	bp_nouveau_media_hook( 'before_directory', 'list' );

	/**
	 * Fires before the display of the members list tabs.
	 *
	 * @since BuddyPress 1.8.0
	 */
	do_action( 'bp_before_directory_media_tabs' );

	if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
		bp_get_template_part( 'common/nav/directory-nav' );
	endif;
	?>

	<div class="media-options">
		<?php
		bp_get_template_part( 'common/search-and-filters-bar' );
		if ( is_user_logged_in() && bp_is_profile_media_support_enabled() && bb_user_can_create_media() ) :
			?>
			<a class="bb-add-photos button small" id="bp-add-media" href="#">
				<i class="bb-icon-l bb-icon-upload"></i>
				<?php esc_html_e( 'Add Photos', 'buddyboss' ); ?>
			</a>

			<?php
			$bp_is_profile_albums_support_enabled = bp_is_profile_albums_support_enabled();
			if ( $bp_is_profile_albums_support_enabled ) {
				?>
				<a href="#" id="bb-create-album" class="bb-create-album button small">
					<i class="bb-icon-l bb-icon-image-video"></i>
					<?php esc_html_e( 'Create Album', 'buddyboss' ); ?>
				</a>
				<?php
			}

			bp_get_template_part( 'media/uploader' );

			if ( $bp_is_profile_albums_support_enabled ) {
				bp_get_template_part( 'media/create-album' );
			}
		endif;
		?>
	</div>

	<?php
	bp_get_template_part( 'media/theatre' );
	if ( bp_is_profile_video_support_enabled() ) {
		bp_get_template_part( 'video/theatre' );
		bp_get_template_part( 'video/add-video-thumbnail' );
	}
	bp_get_template_part( 'document/theatre' );
	?>

	<div id="media-stream" class="media" data-bp-list="media" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
		<?php
		if ( $is_send_ajax_request ) {
			echo '<div id="bp-ajax-loader">';
			bp_nouveau_user_feedback( 'directory-media-loading' );
			echo '</div>';
		} else {
			bp_get_template_part( 'media/media-loop' );
		}
		?>
	</div><!-- .media -->

	<?php bp_nouveau_after_media_directory_content(); ?>

</div><!-- // .screen-content -->
