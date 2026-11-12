<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly. ?>
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
			// Builds without upsell links (the wordpress.org edition) name the product without a link.
			$app_link = esc_html__( 'BuddyBoss App', 'buddyboss' );
			// @bbwt-com-start pricing-links
			$app_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				'https://buddyboss.com/app',
				esc_html__( 'BuddyBoss App', 'buddyboss' )
			);
			// @bbwt-com-end pricing-links
			printf(
				/* translators: %s: BuddyBoss App link. */
				esc_html__( 'Access your community from a native mobile app using the %s. BuddyBoss App is a paid product built by BuddyBoss, providing native iOS and Android apps for WordPress, published under your own Apple and Google Play accounts. The apps are branded to match your site, and sync community data (members, groups, forums, etc.) back and forth with WordPress. If using LearnDash your members can take their courses in the app and can even take them offline!', 'buddyboss' ),
				$app_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			);
			?>
		</p>
		<br />
		<?php
			printf(
				'<a href="%s" class="button-primary">%s</a>',
				esc_url( bp_get_admin_url( '?hello=buddyboss-app' ) ),
				esc_html__( 'Video Demo', 'buddyboss' )
			);
		?>
		<?php
			// Builds without upsell links (the wordpress.org edition) show no "More Info" button.
			// @bbwt-com-start pricing-links
			printf(
				'<a href="%s" target="_blank" class="button-secondary">%s</a>',
				'https://buddyboss.com/app',
				esc_html__( 'More Info', 'buddyboss' )
			);
			// @bbwt-com-end pricing-links
		?>
	</div>

</div>
