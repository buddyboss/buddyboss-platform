<?php
/**
 * ReadyLaunch - Add Media template.
 *
 * This template handles the add media functionality and button display.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ( ( bp_is_my_profile() && bb_user_can_create_media() ) || ( bp_is_group() && is_user_logged_in() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>

	<div class="bb-media-actions-wrap">
		<h2 class="bb-title">
			<div class="bb-item-count">
				<?php
				$count = 0;
				if ( bp_is_group() ) {
					$count = bp_media_get_total_group_media_count();
				} elseif ( bp_is_my_profile() ) {
					$count = bp_get_total_media_count();
				}
				printf(
					wp_kses(
					/* translators: %d is the photo count */
						_n(
							'<span class="bb-count">%s</span> Photo',
							'<span class="bb-count">%s</span> Photos',
							bp_core_number_format( $count ),
							'buddyboss'
						),
						array( 'span' => array( 'class' => true ) )
					),
					esc_html( bp_core_number_format( $count ) )
				);

				unset( $count );
				?>
			</div>
		</h2>
		<div class="bb-media-actions">
			<a href="#" id="bp-add-media" class="bb-add-media button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Add Photos', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'media/uploader' );

} else {
	?>
	<div class="bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
	</div>
	<?php
}
