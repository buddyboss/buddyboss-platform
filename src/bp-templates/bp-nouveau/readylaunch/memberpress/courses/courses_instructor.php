<?php
/**
 * Template for section lesson lists for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/courses_instructor.php.
 *
 * @since 2.9.00
 *
 * @package BuddyBoss\MemberpressLMS
 */

use memberpress\courses\lib\Utils;
use memberpress\courses as base;
do_action( base\SLUG_KEY . '_classroom_start_instructor' );
?>
<div class="tile mpcs-instructor">
	<div class="tile-icon">
		<?php
		echo wp_kses_post(
			Utils::get_avatar(
				get_the_author_meta( 'ID' ),
				'500'
			)
		);
		?>
	</div>
	<div class="tile-content">
		<div class="bb-rl-instructor-data-wrapper">
			<div class="flex bb-rl-instructor-data-header">
				<div class="tile-title"><?php echo esc_html( get_the_author_meta( 'first_name' ) . ' ' . get_the_author_meta( 'last_name' ) ); ?></div>
				<div class="tile-subtitle"><?php echo esc_html__( 'Course Instructor', 'buddyboss' ); ?></div>
			</div>
			<ul class="tile-socials">
				<?php if ( ! empty( get_the_author_meta( 'facebook' ) ) ) { ?>
					<li><a href="<?php the_author_meta( 'facebook' ); ?>" title="Facebook" target="_blank" id="facebook"><i class="mpcs-facebook-squared"></i></a></li>
				<?php } ?>

				<?php if ( ! empty( get_the_author_meta( 'twitter' ) ) ) { ?>
					<li><a href="<?php the_author_meta( 'twitter' ); ?>" title="twitter" target="_blank" id="twitter"><i class="mpcs-twitter-squared"></i></a></li>
				<?php } ?>

				<?php if ( ! empty( get_the_author_meta( 'Instagram' ) ) ) { ?>
					<li><a href="<?php the_author_meta( 'Instagram' ); ?>" title="instagram" target="_blank" id="instagram"><i class="mpcs-instagram-1"></i></a></li>
				<?php } ?>

				<?php if ( ! empty( get_the_author_meta( 'youtube' ) ) ) { ?>
					<li><a href="<?php the_author_meta( 'youtube' ); ?>" title="youtube" target="_blank" id="youtube"><i class="mpcs-youtube"></i></a></li>
				<?php } ?>
			</ul>
			<div class="tile-description"><?php echo wp_kses_post( wpautop( get_the_author_meta( 'description' ) ) ); ?></div>

			<div class="tile-meta">
				<?php if ( ! empty( get_the_author_meta( 'user_email' ) ) ) { ?>
					<p><?php esc_html_e( 'Email:', 'buddyboss' ); ?> <a href="mailto:<?php the_author_meta( 'user_email' ); ?>" ><?php the_author_meta( 'user_email' ); ?></a></p>
				<?php } ?>

				<?php if ( ! empty( get_the_author_meta( 'user_url' ) ) ) { ?>
					<p><?php esc_html_e( 'Website:', 'buddyboss' ); ?> <a href="<?php the_author_meta( 'user_url' ); ?>" target="_blank" ><?php the_author_meta( 'user_url' ); ?></a></p>
				<?php } ?>


			</div>
		</div>
		<div class="bb-rl-instructor-follow">
			<?php
			global $authordata;
			if ( isset( $authordata->ID ) && function_exists( 'bb_meprlms_enable' ) && bb_meprlms_enable() ) {
				$profile_url = bp_core_get_user_domain( $authordata->ID );
				?>
				<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-button--instructor" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'View profile', 'buddyboss' ); ?></a>
				<?php
			}
			?>
		</div>
	</div>
</div>

<?php do_action( base\SLUG_KEY . '_classroom_end_instructor' ); ?>
