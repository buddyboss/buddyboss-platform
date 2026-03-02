<?php
/**
 * The template for BP Nouveau Component filters template
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\Templates
 *
 * @version 1.0.0
 */

$bp_current_component = bp_current_component();

// Member scope as dropdown.
if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) { ?>
	<div id="bb-rl-<?php echo esc_attr( $bp_current_component ); ?>-scope-filters" class="component-filters clearfix">
		<div id="bb-rl-<?php echo esc_attr( $bp_current_component ); ?>-scope-select" class="last filter bb-rl-scope-filter bb-rl-filter">
			<label class="bb-rl-filter-label" for="bb-rl-<?php echo esc_attr( $bp_current_component ); ?>-scope-options">
				<span><?php esc_html_e( 'Filter', 'buddyboss' ); ?></span>
			</label>
			<div class="select-wrap">
				<select id="bb-rl-<?php echo esc_attr( $bp_current_component ); ?>-scope-options" data-bp-<?php echo esc_attr( $bp_current_component ); ?>-scope-filter="<?php echo esc_attr( $bp_current_component ); ?>">
					<?php
					while ( bp_nouveau_nav_items() ) :
						bp_nouveau_nav_item();
						?>
							<option id="<?php bp_nouveau_nav_id(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
							<?php
							if ( "bb-rl-{$bp_current_component}-all" === bp_nouveau_get_nav_id() ) {
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
