<div class="wrap">
	
	<div class="bp-admin-card no-table section-bp-woocommerce-integration">
		<h2><?php _e( 'WooCommerce <span>&mdash; requires plugin to activate</span>', 'buddyboss' ); ?></h2>
		<p><?php
			printf(
				__('BuddyBoss Platform has integration settings for %s. If using LearnDash we add the ability to easily enroll users into courses based on their purchased WooCommerce product. If using our BuddyBoss Theme we also include styling for WooCommerce.', 'buddyboss'),
				sprintf(
					'<a href="%s">%s</a>',
					'https://wordpress.org/plugins/woocommerce/',
					__('WooCommerce', 'buddyboss')
				)
			)
		?></p>
		<br />
		<div class="bp-admin-card-bottom">
			<?php
				printf(
					'<a href="%s" class="button-secondary">%s</a>',
					'https://wordpress.org/plugins/woocommerce/',
					__('Get WooCommerce', 'buddyboss')
				);
			?>
		</div>
	</div>

</div>