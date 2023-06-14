<?php
/**
 * BP Profile Search - filters template
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

	$F = bp_profile_search_escaped_form_data();
if ( empty( $F->fields ) ) {
	return false;
}
?>
	<p class='bp_ps_filters'>

<?php
foreach ( $F->fields as $f ) {
	if ( 'hidden' == $f->display ) {
		continue;
	}

	$filter = bp_ps_print_filter( $f );
	$filter = apply_filters( 'bp_ps_print_filter', $filter, $f );

	?>
		<strong><?php echo $f->label; ?></strong> <span><?php echo $filter; ?></span><br>
	<?php
}

if ( ! empty( $F->action ) ) {
	?>
		<a href='<?php echo $F->action; ?>'><?php _e( 'Clear', 'buddyboss' ); ?></a>
	<?php
}
?>
	</p>
<?php

// BP Profile Search - end of template.
