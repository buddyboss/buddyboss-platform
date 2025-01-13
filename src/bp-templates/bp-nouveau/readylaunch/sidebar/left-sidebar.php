<?php
/**
 * The left sidebar for ReadyLaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$enable_groups   = bb_load_readylaunch()->bb_is_sidebar_enabled_for_groups();
$current_user_id = bp_loggedin_user_id();
?>

<div id="secondary" class="widget-area sm-grid-1-1" role="complementary">
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'bb-top-readylaunchpanel',
			'menu_id'        => '',
			'container'      => false,
			'fallback_cb'    => false,
			'menu_class'     => 'bb-top-readylaunchpanel-menu',
		)
	);

	if ( $enable_groups && $current_user_id ) {
		$group_args = array(
			'user_id'  => $current_user_id,
			'per_page' => 6,
		);

		?>
		<div class="">
			<h2><?php echo esc_html__( 'My Groups', 'buddyboss' ); ?></h2>
			<?php
			if ( bp_has_groups( $group_args ) ) {
				?>
				<ul id="groups-list" class="item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
					<?php
					while ( bp_groups() ) {
						bp_the_group();
						?>
						<li <?php bp_group_class(); ?>>
							<?php
							if ( ! bp_disable_group_avatar_uploads() ) {
								?>
								<div class="item-avatar">
									<a href="<?php bp_group_permalink(); ?>">
										<?php bp_group_avatar_thumb(); ?>
									</a>
								</div>
								<?php
							}
							?>
							<div class="item">
								<div class="item-title">
									<?php bp_group_link(); ?>
								</div>
								<div class="item-meta">
	                            <span class="activity">
	                                <?php printf( esc_html__( 'active %s', 'buddyboss' ), esc_html( bp_get_group_last_active() ) ); ?>
	                            </span>
								</div>
							</div>
						</li>
						<?php
					}
					?>
				</ul>

				<div class="more-block">
					<a href="<?php echo esc_url( bp_get_groups_directory_permalink() ); ?>" class="count-more">
						<?php esc_html_e( 'Show More', 'buddyboss' ); ?>
						<i class="bb-icon-l bb-icon-angle-right"></i>
					</a>
				</div>
				<?php
			} else {
				?>
				<div class="widget-error">
					<?php esc_html_e( 'There are no groups to display.', 'buddyboss' ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	wp_nav_menu(
		array(
			'theme_location' => 'bb-bottom-readylaunchpanel',
			'menu_id'        => '',
			'container'      => false,
			'fallback_cb'    => false,
			'menu_class'     => 'bb-bottom-readylaunchpanel-menu',
		)
	);
	?>
</div>
