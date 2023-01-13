<?php
/**
 * BP Nouveau messages paginate template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/messages/parts/bp-messages-paginate.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bp-messages-paginate">
	<# if ( 1 !== data.page ) { #>
	<button id="bp-messages-prev-page"class="button messages-button">
		<span class="dashicons dashicons-arrow-left"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
	</button>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
	<button id="bp-messages-next-page"class="button messages-button">
		<span class="dashicons dashicons-arrow-right"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
	</button>
	<# } #>
</script>