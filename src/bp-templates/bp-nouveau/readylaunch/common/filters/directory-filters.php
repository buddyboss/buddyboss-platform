<?php
/**
 * The template for BP Nouveau Component's directory filters template
 *
 * This template can be overridden by copying it to yourtheme/readylaunch/common/filters/directory-filters.php.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @version 1.0.0
 */

if ( bp_current_component() === 'activity' ) {
	return '';
}

$bb_get_filter_id = bp_nouveau_get_filter_id();
?>

<div id="bb-rl-dir-filters" class="component-filters clearfix">
	<div id="bb-rl-<?php bp_nouveau_filter_container_id(); ?>" class="last filter bb-rl-filter">
		<label class="bp-screen-reader-text" for="<?php echo esc_attr( $bb_get_filter_id ); ?>">
			<span ><?php bp_nouveau_filter_label(); ?></span>
		</label>
		<div class="select-wrap">
			<select id="<?php echo esc_attr( $bb_get_filter_id ); ?>" data-bp-filter="<?php bp_nouveau_filter_component(); ?>">
				<?php bp_nouveau_filter_options(); ?>
			</select>
			<span class="select-arrow" aria-hidden="true"></span>
		</div>
	</div>
</div>
