<?php
/**
 * BP Nouveau - Groups Directory
 *
 * This template handles the groups directory page layout for the ReadyLaunch theme.
 * It includes search filters, create group button, and groups listing with skeleton loading.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_groups_directory_content();
bp_nouveau_template_notices();
?>
<div class="groups-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Groups', 'buddyboss' ); ?><span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? esc_html( bp_get_total_group_count() ) : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			/**
			 * Fires before the display of the groups list filters.
			 *
			 * @since BuddyBoss 2.9.00
			 */
			do_action( 'bb_before_directory_groups_filters' );

			bp_get_template_part( 'common/search-and-filters-bar' );

			if ( is_user_logged_in() && bp_user_can_create_groups() ) {
				?>
				<div class="bb-rl-action-button">
					<a href="<?php echo esc_url( trailingslashit( bp_get_groups_directory_permalink() . 'create' ) ); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small flex items-center"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create New', 'buddyboss' ); ?></a>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<div class="bb-rl-container-inner">
		<div class="groups-directory-container">

			<div class="screen-content groups-directory-content">

				<div id="groups-dir-list" class="groups dir-list bb-rl-groups" data-bp-list="groups" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bp-ajax-loader">';
						?>
						<div class="bb-rl-skeleton-grid <?php bp_nouveau_loop_classes(); ?>">
							<?php for ( $i = 0; $i < 8; $i++ ) : ?>
								<div class="bb-rl-skeleton-grid-block bb-rl-skeleton-grid-block--cover">
									<div class="bb-rl-skeleton-cover bb-rl-skeleton-loader"></div>
									<div class="bb-rl-skeleton-avatar bb-rl-skeleton-loader"></div>
									<div class="bb-rl-skeleton-data">
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									</div>
									<div class="bb-rl-skeleton-loop">
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									</div>
									<div class="bb-rl-skeleton-footer">
										<span class="bb-rl-skeleton-data-bit bb-rl-skeleton-loader"></span>
									</div>
								</div>
							<?php endfor; ?>
						</div>
						<?php
						echo '</div>';
					} else {
						bp_get_template_part( 'groups/groups-loop' );
					}
					?>
				</div><!-- #members-dir-list -->

				<?php
				bp_nouveau_after_groups_directory_content();
				?>
			</div><!-- // .screen-content -->
		</div>
	</div>

</div>

<?php
/**
 * Fires at the bottom of the member directory template file.
 *
 * @since BuddyBoss 2.9.00
 */
do_action( 'bp_after_directory_members_page' );
