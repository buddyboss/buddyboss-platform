<?php
/**
 * Search Loop - Single Topic Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>


<li class="bb-rl-forum-list-item">

	<div class="bb-rl-forum-cover">

		<?php do_action( 'bbp_theme_before_topic_author_details' ); ?>

		<?php
		bbp_topic_author_link(
			array(
				'sep'       => '<br />',
				'show_role' => true,
			)
		);
		?>

		<?php do_action( 'bbp_theme_after_topic_author_details' ); ?>

	</div><!-- .bb-rl-forum-cover -->

	<div class="bb-rl-card-forum-details">

		<div class="bb-rl-sec-header">
			<h3>
				<a class="bbp-topic-permalink" href="<?php bbp_topic_permalink(); ?>"><?php bbp_topic_title(); ?></a>
			</h3>

			<div class="bb-rl-topic-footer">
			<div class="bb-rl-topic-meta">

			<?php if ( bbp_is_user_home() ) : ?>

				<?php if ( bbp_is_favorites() ) : ?>

					<span class="bb-rl-row-actions">

						<?php do_action( 'bbp_theme_before_topic_favorites_action' ); ?>

						<?php
						bbp_topic_favorite_link(
							array(
								'before'    => '',
								'favorite'  => '+',
								'favorited' => '&times;',
							)
						);
						?>

						<?php do_action( 'bbp_theme_after_topic_favorites_action' ); ?>

					</span>

				<?php elseif ( bbp_is_subscriptions() ) : ?>

					<span class="bb-rl-row-actions">

						<?php do_action( 'bbp_theme_before_topic_subscription_action' ); ?>

						<?php
						bbp_topic_subscription_link(
							array(
								'before'      => '',
								'subscribe'   => '+',
								'unsubscribe' => '&times;',
							)
						);
						?>

						<?php do_action( 'bbp_theme_after_topic_subscription_action' ); ?>

					</span>

				<?php endif; ?>

			<?php endif; ?>

			<?php do_action( 'bbp_theme_before_topic_title' ); ?>

			<?php do_action( 'bbp_theme_after_topic_title' ); ?>

			<?php bbp_topic_pagination(); ?>

			<?php do_action( 'bbp_theme_before_topic_meta' ); ?>

			<div class="bb-rl-topic-meta-item">

				<?php do_action( 'bbp_theme_before_topic_started_by' ); ?>

				<span class="bb-rl-topic-started-by"><?php printf( esc_html__( 'By:%1$s', 'buddyboss' ), bbp_get_topic_author_link( array( 'size' => '14' ) ) ); ?></span>

				<?php do_action( 'bbp_theme_after_topic_started_by' ); ?>

			</div>

			<div class="bb-rl-topic-meta-item">

				<div class="bb-rl-topic-freshness">

					<?php do_action( 'bbp_theme_before_topic_freshness_link' ); ?>

					<?php bbp_topic_freshness_link(); ?>

					<?php do_action( 'bbp_theme_after_topic_freshness_link' ); ?>

				</div>

			</div>

			<?php do_action( 'bbp_theme_after_topic_meta' ); ?>

			<?php bbp_topic_row_actions(); ?>

			</div>
		</div>
		</div>

		<div class="bb-forum-content-wrap">

			<div class="bb-forum-content">
				<?php do_action( 'bbp_theme_before_topic_content' ); ?>

				<?php bbp_topic_content(); ?>

				<?php do_action( 'bbp_theme_after_topic_content' ); ?>
			</div>

		</div>

	</div>

</li>
