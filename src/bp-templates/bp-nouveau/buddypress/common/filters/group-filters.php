<?php
/**
 * BP Nouveau Component's groups filters template.
 *
 * @since BuddyBoss 1.0.0
 */
?>

<?php

// Check group type enable?
if ( false === bp_disable_group_type_creation() ) {
	return '';
}

$group_types = bp_get_active_group_types();
$display_arr = array();
foreach ( $group_types as $group_type_id ) {

	if ( ! get_post_meta( $group_type_id, '_bp_group_type_enable_filter', true ) ) {
		continue;
	}

	$group_key        = bp_group_get_group_type_key( $group_type_id );
	$group_type_label = bp_groups_get_group_type_object( $group_key )->labels['name'];


	if ( ! empty( $group_key ) ) {
		$display_arr[] = array(
			'group_type_label' => $group_type_label,
			'group_type_name'  => $group_key,
		);
	}
}

if ( isset( $display_arr ) && ! empty( $display_arr ) ) {
	?>
	<div id="group-type-filters" class="component-filters clearfix">
		<div id="group-type-select" class="last filter">
			<label class="bp-screen-reader-text" for="group-type-order-by">
				<span ><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<select id="group-type-order-by" data-bp-group-type-filter="<?php bp_nouveau_search_object_data_attr(); ?>">
					<option value=""><?php _e( 'All Types', 'buddyboss' ); ?></option>
					<?php foreach ( $display_arr as $group ) { ?>
						<option
							value="<?php echo $group['group_type_name']; ?>"><?php echo $group['group_type_label']; ?></option>
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
