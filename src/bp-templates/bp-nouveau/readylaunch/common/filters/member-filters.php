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

if ( $is_member_type_enabled ) {
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
	
	// Get active member types.
	$member_types = bp_get_active_member_types( $args );
	
	if ( ! empty( $member_types ) ) {
		?>
		<div id="bb-rl-member-type-filters" class="component-filters clearfix">
			<div id="bb-rl-member-type-select" class="last filter bb-rl-filter">
				<label class="bb-rl-filter-label" for="bb-rl-member-type-order-by">
					<span><?php esc_html_e( 'Type', 'buddyboss' ); ?></span>
				</label>
				<div class="select-wrap">
					<select id="bb-rl-member-type-order-by" data-bp-member-type-filter="members">
						<option value=""><?php _e( 'All', 'buddyboss' ); ?></option><?php
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
}

// Member scope as dropdown.
if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) { ?>
	<div id="bb-rl-members-scope-filters" class="component-filters clearfix">
		<div id="bb-rl-members-scope-select" class="last filter bb-rl-scope-filter bb-rl-filter">
			<label class="bb-rl-filter-label" for="bb-rl-members-scope-options">
				<span><?php esc_html_e( 'Filter', 'buddyboss' ); ?></span>
			</label>
			<div class="select-wrap">
				<select id="bb-rl-members-scope-options" data-bp-member-scope-filter="members">
					<?php
					while ( bp_nouveau_nav_items() ) :
						bp_nouveau_nav_item();
						?>
						<option id="<?php bp_nouveau_nav_id(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
							<?php 
								if ( 'bb-rl-members-all' === bp_nouveau_get_nav_id() ) {
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
