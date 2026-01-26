<div class="wrap">
	
	<div class="bp-admin-card section-bp_buddyboss_app-integration">
		<h2>
			<?php
			$meta_icon = bb_admin_icons( 'bp_buddyboss_app-integration' );
			if ( ! empty( $meta_icon ) ) {
				echo '<i class="' . esc_attr( $meta_icon ) . '"></i>';
			}
			esc_html_e( 'BuddyBoss App', 'buddyboss' );
			?>
		</h2>
		<p>
		<?php
			printf(
				esc_html__( 'Access your community from a native mobile app using the %s. BuddyBoss App is a paid product built by BuddyBoss, providing native iOS and Android apps for WordPress, published under your own Apple and Google Play accounts. The apps are branded to match your site, and sync community data (members, groups, forums, etc.) back and forth with WordPress. If using LearnDash your members can take their courses in the app and can even take them offline!', 'buddyboss' ),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://buddyboss.com/app',
					esc_html__( 'BuddyBoss App', 'buddyboss' )
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
