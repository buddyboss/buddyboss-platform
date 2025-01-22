<?php
/**
 * The template for BP Nouveau Component's members filters template
 *
 * This template can be overridden by copying it to yourtheme/readylaunch/common/filters/member-filters.php.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

// Check profile type enable?
$is_member_type_enabled = bp_member_type_enable_disable();

if ( false === $is_member_type_enabled ) {
	return '';
}

$args = array(
	'meta_query' => array(
		array(
			'key'   => '_bp_member_type_enable_filter',
			'value' => 1,
		),
	)
);

if ( bp_is_members_directory() ) {
	$args['meta_query'][] = array(
		'key'   => '_bp_member_type_enable_remove',
		'value' => 0,
	);
}

// Get active member types
$member_types = bp_get_active_member_types( $args );

if ( ! empty( $member_types ) ) {
	?>
	<div id="member-type-filters" class="component-filters clearfix">
		<div id="member-type-select" class="last filter">
			<label class="bp-screen-reader-text" for="member-type-order-by">
				<span><?php bp_nouveau_filter_label(); ?></span>
			</label>
			<div class="select-wrap">
				<select id="member-type-order-by" data-bp-member-type-filter="members">
					<option value=""><?php _e( 'All Types', 'buddyboss' ); ?></option><?php
					foreach ( $member_types as $member_type_id ) {
						$type_name        = bp_get_member_type_key( $member_type_id );
						$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );
						?>
						<option value="<?php echo esc_attr( $member_type_id ); ?>">
							<?php echo esc_attr( $member_type_name ); ?>
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

// Mmember scope as dropdown.
if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) { ?>
	<div id="members-scope-filters" class="component-filters clearfix">
		<div id="members-scope-select" class="last filter bb-rl-filter">
			<label class="bp-screen-reader-text" for="members-scope-options">
				<span>Filter</span>
			</label>
			<div class="select-wrap">
				<select id="members-scope-options" data-bp-member-scope-filter="members">
					<?php
					while ( bp_nouveau_nav_items() ) :
						bp_nouveau_nav_item();
						?>
						<option id="<?php bp_nouveau_nav_id(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
							<?php bp_nouveau_nav_link_text(); ?>
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
