<div class="wrap">
	
	<div class="bp-admin-card section-bp_buddyboss_app-integration">
		<h2><?php _e( 'BuddyBoss App', 'buddyboss' ); ?></h2>
		<p>
		<?php
			printf(
				__( 'Access your community from a native mobile app using the %s. BuddyBoss App is a paid product built by BuddyBoss, providing native iOS and Android apps for WordPress, published under your own Apple and Google Play accounts. The apps are branded to match your site, and sync community data (members, groups, forums, etc.) back and forth with WordPress. If using LearnDash your members can also take their courses in the app.', 'buddyboss' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://buddyboss.com/app',
					__( 'BuddyBoss App', 'buddyboss' )
				)
			);
			?>
		</p>
		<br />
		<?php
			printf(
				'<a href="%s" class="button-primary">%s</a>',
				esc_url( bp_get_admin_url( '?hello=buddyboss-app' ) ),
				__( 'Video Demo', 'buddyboss' )
			);
		?>
		<?php
			printf(
				'<a href="%s" target="_blank" class="button-secondary">%s</a>',
				'https://buddyboss.com/app',
				__( 'More Info', 'buddyboss' )
			);
		?>
	</div>

</div>
