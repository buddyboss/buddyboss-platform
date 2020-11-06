<script type="text/html" id="tmpl-bp-invites-filters">
	<div class="group-invites-search subnav-search clearfix" role="search" >
		<div class="bp-search">
			<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form" data-bp-search="{{data.scope}}">
				<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
				<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search', 'buddyboss' ); ?>"/>

				<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit">
					<span class="bb-icons bb-icon-search" aria-hidden="true"></span>
					<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
				</button>
			</form>
		</div>
	</div>
</script>
