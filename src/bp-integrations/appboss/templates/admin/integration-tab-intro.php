<div class="wrap">
	
	<div class="bp-admin-card section-bp_appboss-integration">
		<h2><?php _e( 'AppBoss <span>&mdash; contact sales to activate</span>', 'buddyboss' ); ?></h2>
		<p>
		<?php
			printf(
				__( 'Access your community from a native mobile app using %s. AppBoss is a paid service built by BuddyBoss, providing native iOS and Android apps for WordPress, published under your own Apple and Google Play accounts. The apps are branded to match your site, and sync community data (members, groups, forums, etc.) back and forth with WordPress. If using LearnDash your members can also take their courses in the app.', 'buddyboss' ),
				sprintf(
					'<a href="%s">%s</a>',
					'https://appboss.com',
					__( 'AppBoss', 'buddyboss' )
				)
			);
			?>
		</p>
		<br />
		<?php
			printf(
				'<a href="%s" class="button-secondary">%s</a>',
				esc_url( bp_get_admin_url( '?hello=appboss' ) ),
				__( 'Watch the video', 'buddyboss' )
			);
			?>
	</div>

</div>
