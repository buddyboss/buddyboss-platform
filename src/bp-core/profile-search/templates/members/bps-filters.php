<?php
/**
 * BP Profile Search - filters template
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$form_data = bp_profile_search_escaped_form_data();
if ( empty( $form_data->fields ) ) {
	return false;
}
?>
	<p class='bp_ps_filters'>

<?php
foreach ( $form_data->fields as $f ) {
	if ( 'hidden' === $f->display ) {
		continue;
	}

	$filter = bp_ps_print_filter( $f );
	$filter = apply_filters( 'bp_ps_print_filter', $filter, $f );

	?>
		<strong><?php echo $f->label; ?></strong> <span><?php echo $filter; ?></span><br>
	<?php
}

if ( ! empty( $form_data->action ) ) {
	?>
		<a href='<?php echo $form_data->action; ?>'><?php _e( 'Clear', 'buddyboss' ); ?></a>
	<?php
}
?>
	</p>
<?php
