<?php
/**
 * ReadyLaunch: Taxonomy Archive for Event Categories and Tags.
 *
 * Overrides the default WP taxonomy archive because events are stored
 * in bb_events (not wp_posts). Calls bp_events_get_events() directly.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

$queried  = get_queried_object();
$term_id  = $queried ? $queried->term_id : 0;
$taxonomy = $queried ? $queried->taxonomy : '';

// Determine filter key based on taxonomy.
$filter_key = 'bb_event_category' === $taxonomy ? 'category_id' : 'tag_id';

$paged    = max( 1, get_query_var( 'paged', 1 ) );
$per_page = 12;

$result = bp_events_get_events( array(
	$filter_key => $term_id,
	'per_page'  => $per_page,
	'page'      => $paged,
) );

$events = $result['events'];
$total  = $result['total'];
$pages  = ceil( $total / $per_page );

// Category icon if applicable.
$icon_url = ( 'bb_event_category' === $taxonomy && function_exists( 'bp_event_get_category_icon_url' ) )
	? bp_event_get_category_icon_url( $term_id )
	: '';
?>

<div class="bb-rl-events-taxonomy-archive bb-rl-screen-content">

	<div class="bb-rl-events-header">
		<div class="bb-rl-events-header__left">
			<?php if ( $icon_url ) : ?>
				<img src="<?php echo esc_url( $icon_url ); ?>" class="bb-rl-category-icon" style="width:32px;height:32px;vertical-align:middle;margin-right:8px;" />
			<?php endif; ?>
			<h1 class="bb-rl-events-directory__title">
				<?php echo esc_html( single_term_title( '', false ) ); ?>
			</h1>
		</div>
		<div class="bb-rl-events-header__right">
			<a href="<?php echo esc_url( bp_get_events_directory_url() ); ?>" class="bb-rl-btn">
				&larr; <?php esc_html_e( 'All Events', 'buddyboss' ); ?>
			</a>
		</div>
	</div>

	<?php if ( ! empty( $queried->description ) ) : ?>
		<p class="bb-rl-taxonomy-description"><?php echo esc_html( $queried->description ); ?></p>
	<?php endif; ?>

	<?php if ( empty( $events ) ) : ?>
		<p class="bb-rl-notice"><?php esc_html_e( 'No events found in this category.', 'buddyboss' ); ?></p>
	<?php else : ?>
		<div class="bb-rl-events-list">
			<?php foreach ( $events as $event ) : ?>
				<?php
				// Use the event-card partial if it exists.
				$card_template = BP_EVENTS_PLUGIN_DIR . 'src/bp-templates/bp-nouveau/readylaunch/events/event-card.php';
				if ( file_exists( $card_template ) ) {
					$GLOBALS['bp_event'] = $event;
					include $card_template;
				} else {
					?>
					<div class="bb-rl-event-card">
						<h3>
							<a href="<?php echo esc_url( bp_get_event_permalink( $event ) ); ?>">
								<?php echo esc_html( $event->title ); ?>
							</a>
						</h3>
						<span class="bb-rl-event-date">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $event->start_date ) ) ); ?>
						</span>
					</div>
					<?php
				}
				?>
			<?php endforeach; ?>
		</div>

		<?php if ( $pages > 1 ) : ?>
			<div class="bb-rl-pagination">
				<?php
				echo paginate_links( array(
					'total'   => $pages,
					'current' => $paged,
					'format'  => 'page/%#%/',
				) );
				?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

</div>

<?php
get_footer();
