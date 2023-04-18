<?php
/**
 * Invites filters Templates
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/invites/parts/bp-invites-filters.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-bp-invites-filters">
	<div class="group-invites-search subnav-search clearfix" role="search" >
		<div class="bp-search">
			<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form search-form-has-reset" data-bp-search="{{data.scope}}">
				<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
				<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search', 'buddyboss' ); ?>"/>

				<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit search-form_submit">
					<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
					<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
				</button>
				<button type="reset" class="search-form_reset">
					<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
				</button>
			</form>
		</div>
	</div>
</script>
