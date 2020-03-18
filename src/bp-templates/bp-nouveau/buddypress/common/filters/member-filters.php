<?php
/**
 * BP Nouveau Component's members filters template.
 *
 * @since BuddyBoss 1.0.0
 */

// Check profile type enable?
$is_member_type_enabled = bp_member_type_enable_disable();

if ( false === $is_member_type_enabled ) {
	return '';
}

$member_types = bp_get_active_member_types();
$display_arr  = array();

foreach ( $member_types as $member_type_id ) {

	if ( !get_post_meta( $member_type_id, '_bp_member_type_enable_filter', true ) ) {
		continue;
	}

	$type_name        = bp_get_member_type_key( $member_type_id );
	$type_id          = bp_member_type_term_taxonomy_id( $type_name );
	$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_singular_name', true );

	$display_arr[] = array(
		'id'             => $type_id,
		'member_type_id' => $member_type_id,
		'name'           => $member_type_name,
	);

}

if ( isset( $display_arr ) && !empty( $display_arr )) {
	?>
	<div id="member-type-filters" class="component-filters clearfix">
		<div id="member-type-select" class="last filter">
			<label class="bp-screen-reader-text" for="member-type-order-by">
				<span ><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<select id="member-type-order-by" data-bp-member-type-filter="members">
					<option value=""><?php _e( 'All Types', 'buddyboss' ); ?></option><?php
					foreach ( $display_arr as $member ) {
						?>
						<option value="<?php echo $member['member_type_id']; ?>">
							<?php echo $member['name']; ?>
						</option>
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
