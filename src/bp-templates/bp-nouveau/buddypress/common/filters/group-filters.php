<?php
/**
 * The template for BP Nouveau Component's groups filters template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/filters/group-filters.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Check group type enable?
if ( false === bp_disable_group_type_creation() ) {
	return '';
}

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
	<div id="group-type-filters" class="component-filters clearfix">
		<div id="group-type-select" class="last filter">
			<label class="bp-screen-reader-text" for="group-type-order-by">
				<span><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<select id="group-type-order-by"
				        data-bp-group-type-filter="<?php bp_nouveau_search_object_data_attr() ?>">
					<option value=""><?php _e( 'All Types', 'buddyboss' ); ?></option><?php
					foreach ( $group_types as $group_type_id ) {
						$group_type_key   = bp_group_get_group_type_key( $group_type_id );
						$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
						?>
						<option
						value="<?php echo esc_attr( $group_type_key ); ?>"><?php echo esc_attr( $group_type_label ); ?></option><?php
					}
					?>
				</select>
				<span class="select-arrow" aria-hidden="true"></span>
			</div>
		</div>
	</div>
	<?php
}
