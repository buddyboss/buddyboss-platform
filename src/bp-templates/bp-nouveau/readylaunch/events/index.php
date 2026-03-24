<?php
/**
 * ReadyLaunch: Events Directory.
 *
 * Template for /events — site-wide events calendar and list.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-events-directory bb-rl-screen-content">

	<div class="bb-rl-events-header">
		<div class="bb-rl-events-header__left">
			<h1 class="bb-rl-events-directory__title">
				<?php esc_html_e( 'Events', 'buddyboss' ); ?>
			</h1>
		</div>

		<div class="bb-rl-events-header__right">
			<?php if ( bp_events_user_can_create( bp_loggedin_user_id() ) ) : ?>
				<a href="<?php echo esc_url( bp_get_events_directory_url() . 'create/' ); ?>"
				   class="bb-rl-btn bb-rl-btn--primary bb-rl-events-create-btn">
					<?php esc_html_e( 'Create Event', 'buddyboss' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="bb-rl-events-filters">
		<div class="bb-rl-events-view-toggle">
			<button class="bb-rl-view-toggle__btn is-active" data-view="month" aria-label="<?php esc_attr_e( 'Month view', 'buddyboss' ); ?>">
				<i class="bb-icon-calendar-month"></i>
			</button>
			<button class="bb-rl-view-toggle__btn" data-view="week" aria-label="<?php esc_attr_e( 'Week view', 'buddyboss' ); ?>">
				<i class="bb-icon-calendar-week"></i>
			</button>
			<button class="bb-rl-view-toggle__btn" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'buddyboss' ); ?>">
				<i class="bb-icon-list"></i>
			</button>
		</div>

		<div class="bb-rl-events-filter-tabs">
			<button class="bb-rl-filter-tab is-active" data-filter="upcoming">
				<?php esc_html_e( 'Upcoming', 'buddyboss' ); ?>
			</button>
			<button class="bb-rl-filter-tab" data-filter="this-week">
				<?php esc_html_e( 'This Week', 'buddyboss' ); ?>
			</button>
			<button class="bb-rl-filter-tab" data-filter="this-month">
				<?php esc_html_e( 'This Month', 'buddyboss' ); ?>
			</button>
		</div>

		<?php
		$event_categories = taxonomy_exists( 'bb_event_category' )
			? get_terms( array( 'taxonomy' => 'bb_event_category', 'hide_empty' => false ) )
			: array();
		if ( ! empty( $event_categories ) && ! is_wp_error( $event_categories ) ) :
		?>
		<div class="bb-rl-events-category-filter">
			<select id="bb-rl-events-category-select" class="bb-rl-select">
				<option value=""><?php esc_html_e( 'All Categories', 'buddyboss' ); ?></option>
				<?php foreach ( $event_categories as $cat ) : ?>
					<option value="<?php echo esc_attr( $cat->term_id ); ?>"
						<?php if ( function_exists( 'bp_event_get_category_icon_url' ) && bp_event_get_category_icon_url( $cat->term_id ) ) : ?>
							data-icon="<?php echo esc_url( bp_event_get_category_icon_url( $cat->term_id ) ); ?>"
						<?php endif; ?>
					>
						<?php echo esc_html( $cat->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>
	</div>

	<div id="bb-rl-events-calendar" class="bb-rl-events-calendar" data-view="<?php echo esc_attr( bb_get_events_default_calendar_view() ); ?>">
		<?php /* Calendar rendered via JavaScript using REST API data */ ?>
		<div class="bb-rl-events-calendar__loading">
			<span class="bb-rl-loading-spinner"></span>
			<span><?php esc_html_e( 'Loading events...', 'buddyboss' ); ?></span>
		</div>
	</div>

	<div id="bb-rl-events-list" class="bb-rl-events-list" style="display:none;">
		<?php bp_get_template_part( 'events/events-loop' ); ?>
	</div>

</div>
