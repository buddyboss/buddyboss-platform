<?php
/**
 * Invites paginate Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/invites/parts/bp-invites-paginate.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bp-invites-paginate">
	<# if ( 1 !== data.page ) { #>
	<a href="#previous-page" id="bp-invites-prev-page" class="button invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
		<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
	</a>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
	<a href="#next-page" id="bp-invites-next-page" class="button invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>">
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
		<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>
	</a>
	<# } #>
</script>
