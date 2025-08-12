<?php
/**
 * The ReadyLaunch template for BuddyBoss Activity templates.
 *
 * This template handles the main activity stream page layout for the ReadyLaunch theme.
 * It includes the activity post form, search filters, activity loop, and loading placeholders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();

bp_nouveau_before_activity_directory_content();
?>
<div class="bb-rl-activity-wrap">
	<div class="bb-rl-content-wrapper">
		<div class="bb-rl-inner-container bb-rl-activity-page">
			<?php
			if ( is_user_logged_in() ) :
				bp_get_template_part( 'activity/post-form' );
			endif;

			bp_nouveau_template_notices();
			?>
			<div class="bb-rl-screen-content">
				<div class="bb-rl-activity-filters-container">
					<?php
					if ( bb_is_enabled_activity_topics() ) {
						$topics = function_exists( 'bb_activity_topics_manager_instance' ) ? bb_activity_topics_manager_instance()->bb_get_activity_topics() : array();
						if ( ! empty( $topics ) ) {
							$current_slug = function_exists( 'bb_topics_manager_instance' ) ? bb_topics_manager_instance()->bb_get_topic_slug_from_url() : '';
							?>
							<div class="activity-topic-selector">
								<ul>
									<li>
										<a href="<?php echo esc_url( bp_get_activity_directory_permalink() ); ?>"><?php esc_html_e( 'All', 'buddyboss' ); ?></a>
									</li>
									<?php
									foreach ( $topics as $topic ) {
										$li_class = '';
										$a_class  = '';
										if ( ! empty( $current_slug ) && $current_slug === $topic['slug'] ) {
											$li_class = 'selected';
											$a_class  = 'selected active';
										}
										echo '<li class="bb-topic-selector-item ' . esc_attr( $li_class ) . '"><a href="' . esc_url( add_query_arg( 'bb-topic', $topic['slug'] ) ) . '" data-topic-id="' . esc_attr( $topic['topic_id'] ) . '" class="bb-topic-selector-link ' . esc_attr( $a_class ) . '">' . esc_html( $topic['name'] ) . '</a></li>';
									}
									?>
								</ul>
							</div>
							<div class="bb-rl-activity-filters-separator"></div>
							<?php
						}
					}
					if ( ! bp_nouveau_is_object_nav_in_sidebar() ) :
						bp_get_template_part( 'common/search-and-filters-bar' );
					endif;
					bp_nouveau_activity_hook( 'before_directory', 'list' );
					?>
				</div>

				<div id="bb-rl-activity-stream" class="activity" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
					<?php
					if ( $is_send_ajax_request ) {
						echo '<div id="bb-rl-ajax-loader">';
						for ( $i = 0; $i < 2; $i++ ) {
							?>
							<div class="bb-rl-activity-placeholder">
								<div class="bb-rl-activity-placeholder_head">
									<div class="bb-rl-activity-placeholder_avatar bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_details">
										<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
										<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									</div>
								</div>
								<div class="bb-rl-activity-placeholder_content">
									<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
								</div>
								<div class="bb-rl-activity-placeholder_actions">
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
									<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
								</div>
							</div>
							<?php
						}
						echo '</div>';
					} else {
						bp_get_template_part( 'activity/activity-loop' );
					}
					?>
				</div><!-- .activity -->

				<?php bp_nouveau_after_activity_directory_content(); ?>
			</div><!-- // .screen-content -->
		</div><!-- // .bb-rl-inner-container -->
	</div>

	<?php
	ob_start();
	bp_get_template_part( 'sidebar/right-sidebar' );
	$sidebar = ob_get_clean();

	if ( ! empty( $sidebar ) ) {
		echo '<div class="bb-rl-secondary-container">' . wp_kses_post( $sidebar ) . '</div>';
	}
	?>

</div>
