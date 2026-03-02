<?php
/**
 * ReadyLaunch - Group filters template.
 *
 * This template handles filtering options for group directories
 * including type filters, status filters, and search functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Check group type enable?
if ( false !== bp_disable_group_type_creation() ) {

	// No need to show the group type select dropdown.
	$group_type = bp_get_current_group_directory_type();
	if ( ! empty( $group_type ) ) {
		return '';
	}


	$args = array(
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
		'meta_query' => array(
			array(
				'key'   => '_bp_group_type_enable_filter',
				'value' => 1,
			),
		),
	);

	// Get active group types.
	if ( bp_is_groups_directory() ) {
		$args['meta_query'][] = array(
			'key'   => '_bp_group_type_enable_remove',
			'value' => 0,
		);
	}

	$group_types = bp_get_active_group_types( $args );

	if ( ! empty( $group_types ) ) {
		?>
		<div id="group-type-filters" class="component-filters clearfix bb-rl-filter">
			<div id="group-type-select" class="last filter">
				<label class="bb-rl-filter-label" for="group-type-order-by">
					<span><?php esc_html_e( 'Type', 'buddyboss' ); ?></span>
				</label>
				<div class="select-wrap">
					<select id="group-type-order-by" data-bp-group-type-filter="<?php bp_nouveau_search_object_data_attr(); ?>">
						<option value=""><?php esc_html_e( 'All', 'buddyboss' ); ?></option>
						<?php
						foreach ( $group_types as $group_type_id ) {
							$group_type_key   = bp_group_get_group_type_key( $group_type_id );
							$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
							?>
								<option value="<?php echo esc_attr( $group_type_key ); ?>"><?php echo esc_attr( $group_type_label ); ?></option>
							<?php
						}
						?>
					</select>
					<span class="select-arrow" aria-hidden="true"></span>
				</div>
			</div>
		</div>
		<?php
	}
}

// Group scope as dropdown.
if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) {
	?>
	<div id="bb-rl-groups-scope-filters" class="component-filters clearfix">
		<div id="bb-rl-groups-scope-select" class="last filter bb-rl-scope-filter bb-rl-filter">
			<label class="bb-rl-filter-label" for="bb-rl-groups-scope-options">
				<span><?php esc_html_e( 'Filter', 'buddyboss' ); ?></span>
			</label>
			<div class="select-wrap">
				<select id="bb-rl-groups-scope-options" data-bp-groups-scope-filter="groups" data-dropdown-align="true">
					<?php
					while ( bp_nouveau_nav_items() ) :
						bp_nouveau_nav_item();
						if ( 'bb-rl-groups-create' === bp_nouveau_get_nav_id() ) {
							break;
						}
						?>
						<option id="<?php bp_nouveau_nav_id(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
							<?php
							if ( 'bb-rl-groups-all' === bp_nouveau_get_nav_id() ) {
								esc_html_e( 'All', 'buddyboss' );
							} else {
								bp_nouveau_nav_link_text();
							}
							?>
						</option>
						<?php
					endwhile;
					?>
				</select>
				<span class="select-arrow" aria-hidden="true"></span>
			</div>
		</div>
	</div>
	<?php
}
