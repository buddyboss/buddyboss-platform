<script type="text/html" id="tmpl-bp-messages-filters">
	<div class="group-messages-search subnav-search clearfix" role="search" >
		<div class="bp-search">
			<form action="" method="get" id="group_messages_search_form" class="bp-messages-search-form" data-bp-search="{{data.scope}}">
				<label for="group_messages_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
				<input type="search" id="group_messages_search" placeholder="<?php esc_attr_e( 'Search', 'buddyboss' ); ?>"/>

				<button type="submit" id="group_messages_search_submit" class="nouveau-search-submit">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
				</button>
			</form>
		</div>
	</div>
</script>
