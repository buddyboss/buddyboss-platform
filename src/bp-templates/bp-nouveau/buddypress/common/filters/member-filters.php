<?php
/**
 * BP Nouveau Component's members filters template.
 *
 * @since BuddyBoss 3.1.1
 */
?>

<?php

// Check member type enable?
$is_member_type_enabled = bp_member_type_enable_disable();

if ( false === $is_member_type_enabled ) {
	return '';
}

$member_types = bp_get_active_member_types();
$display_arr = array();
foreach ( $member_types as $member_type_id ) {

	if ( !get_post_meta( $member_type_id, '_bp_member_type_enable_filter', true ) ) {
		continue;
	}

	$type_name = bp_get_member_type_key( $member_type_id );
	$type_id = bp_member_type_term_taxonomy_id( $type_name );
	$members_count = count(  bp_member_type_by_type( $type_id ));
	$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );

	if ( !empty( $type_id ) ) {
		$display_arr[] = array(
			'id' => $type_id,
			'member_type_id' => $member_type_id,
			'name' => $member_type_name,
		);
	}

}

if ( isset( $display_arr ) && !empty( $display_arr )) {
	?>
	<div id="member-type-filters" class="component-filters clearfix">
		<div id="member-type-select" class="last filter">
			<label class="bp-screen-reader-text" for="member-type-order-by">
				<span ><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<select id="member-type-order-by" data-bp-filter="members">
					<option value="all"><?php echo __( 'All Types', 'buddyboss' ); ?></option><?php
					foreach ( $display_arr as $member ) {
						?>
						<option value="<?php echo $member['member_type_id']; ?>"><?php echo __( $member['name'], 'buddyboss' ); ?></option><?php
					}
					?>
				</select>
				<span class="select-arrow" aria-hidden="true"></span>
			</div>
		</div>
	</div>
	<?php
}
