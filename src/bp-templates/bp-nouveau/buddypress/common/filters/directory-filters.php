<?php
/**
 * The template for BP Nouveau Component's directory filters template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/filters/directory-filters.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

?>

<?php
if ( bp_current_component() === 'activity' ) {
	return '';
}
?>


<div id="dir-filters" class="component-filters clearfix">
	
	<div id="<?php bp_nouveau_filter_container_id(); ?>" class="last filter">
		<label class="bp-screen-reader-text" for="<?php bp_nouveau_filter_id(); ?>">
			<span ><?php bp_nouveau_filter_label(); ?></span>
		</label>
		<div class="select-wrap">
			<select id="<?php bp_nouveau_filter_id(); ?>" data-bp-filter="<?php bp_nouveau_filter_component(); ?>">

				<?php bp_nouveau_filter_options(); ?>

			</select>
			<span class="select-arrow" aria-hidden="true"></span>
		</div>
	</div>
</div>
