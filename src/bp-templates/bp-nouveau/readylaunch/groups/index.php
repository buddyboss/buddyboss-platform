<?php
/**
 * BP Nouveau - Groups Directory
 *
 * This template can be overridden by copying it to yourtheme/readylaunch/groups/index.php.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_groups_directory_content();
bp_nouveau_template_notices();
?>
<div class="groups-directory-wrapper">
	<div class="bb-rl-secondary-header flex items-center">
		<div class="bb-rl-entry-heading">
			<h2><?php esc_html_e( 'Groups', 'buddyboss' ); ?><span class="bb-rl-heading-count"><?php echo ! $is_send_ajax_request ? bp_get_total_group_count() : ''; ?></span></h2>
		</div>
		<div class="bb-rl-sub-ctrls flex items-center">
			<?php
			/**
			 * Fires before the display of the groups list filters.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bb_before_directory_groups_filters' );

			bp_get_template_part( 'common/search-and-filters-bar' );
			?>
			<div class="bb-rl-action-button">
				<a href="" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small flex items-center"><i class="bb-icons-rl-plus"></i><?php esc_html_e( 'Create New', 'buddyboss' ); ?></a>
			</div>
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
						// bp_nouveau_user_feedback( 'directory-groups-loading' );
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
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bp_after_directory_members_page' );
