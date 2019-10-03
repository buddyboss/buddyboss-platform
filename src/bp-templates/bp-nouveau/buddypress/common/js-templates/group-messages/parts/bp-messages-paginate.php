<script type="text/html" id="tmpl-bp-messages-paginate">
	<# if ( 1 !== data.page ) { #>
	<a href="#previous-page" id="bp-messages-prev-page" class="button message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
		<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
	</a>
	<# } #>

	<# if ( data.total_page !== data.page ) { #>
	<a href="#next-page" id="bp-messages-next-page" class="button message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>">
		<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span>
		<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span>
	</a>
	<# } #>
</script>
