<?php
/**
 * BuddyBoss Groups Widgets
 *
 * @package BuddyBoss\Groups\Widgets
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register widgets for groups component.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_register_widgets() {
	add_action(
		'widgets_init',
		function() {
			register_widget( 'BBoss_Groups_Widget' );
		}
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_register_widgets' ) && ! function_exists( 'groups_register_widgets' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_register_widgets()}.
	 *
	 * @deprecated Use bboss_groups_register_widgets() instead.
	 */
	function groups_register_widgets() {
		return bboss_groups_register_widgets();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_register_widgets', 'groups_register_widgets' );

/**
 * AJAX callback for the Groups List widget.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_ajax_widget_groups_list() {

	check_ajax_referer( 'groups_widget_groups_list' );

	switch ( isset( $_POST['filter'] ) ? sanitize_text_field( wp_unslash( $_POST['filter'] ) ) : '' ) {
		case 'newest-groups':
			$type = 'newest';
			break;
		case 'recently-active-groups':
			$type = 'active';
			break;
		case 'popular-groups':
			$type = 'popular';
			break;
		case 'alphabetical-groups':
			$type = 'alphabetical';
			break;
	}

	$per_page = isset( $_POST['max_groups'] ) ? intval( $_POST['max_groups'] ) : 5;

	$groups_args = array(
		'user_id'  => 0,
		'type'     => $type,
		'per_page' => $per_page,
		'max'      => $per_page,
	);

	if ( bboss_has_groups( $groups_args ) ) : ?>
		<?php echo '0[[SPLIT]]'; ?>
		<?php
		while ( bboss_groups() ) :
			bboss_the_group();
			?>
			<li <?php bboss_group_class(); ?>>
				<?php if ( ! bboss_disable_group_avatar_uploads() ) : ?>
					<div class="item-avatar">
						<a href="<?php bboss_group_permalink(); ?>" data-bb-hp-group="<?php echo esc_attr( bboss_get_group_id() ); ?>"><?php bboss_group_avatar_thumb(); ?></a>
					</div>
				<?php endif; ?>

				<div class="item">
					<div class="item-title"><?php bboss_group_link(); ?></div>
					<div class="item-meta">
						<span class="activity">
						<?php
						if ( 'newest' == $type ) {
							/* translators: %s: human-readable time since the group was created. */
							printf( esc_html__( 'created %s', 'buddyboss' ), esc_html( bboss_get_group_date_created() ) );
						} elseif ( 'popular' == $type ) {
							bboss_group_member_count();
						} else {
							/* translators: %s: human-readable time since the group was last active. */
							printf( esc_html__( 'active %s', 'buddyboss' ), esc_html( bboss_get_group_last_active() ) );
						}
						?>
						</span>
					</div>
				</div>
			</li>
		<?php endwhile; ?>

		<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
		<input type="hidden" name="groups_widget_max" id="groups_widget_max" value="<?php echo esc_attr( isset( $_POST['max_groups'] ) ? absint( $_POST['max_groups'] ) : 0 ); ?>" />

	<?php else : ?>

		<?php echo '-1[[SPLIT]]<li>' . esc_html__( 'No groups matched the current filter.', 'buddyboss' ); ?>

		<?php
	endif;

}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_ajax_widget_groups_list' ) && ! function_exists( 'groups_ajax_widget_groups_list' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_ajax_widget_groups_list()}.
	 *
	 * @deprecated Use bboss_groups_ajax_widget_groups_list() instead.
	 */
	function groups_ajax_widget_groups_list() {
		return bboss_groups_ajax_widget_groups_list();
	}
}
// @bbwt-com-end compat-layer

add_action( 'wp_ajax_widget_groups_list', 'groups_ajax_widget_groups_list' );
add_action( 'wp_ajax_nopriv_widget_groups_list', 'groups_ajax_widget_groups_list' );
