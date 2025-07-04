<?php
/**
 * ReadyLaunch - Search Loop Post template.
 *
 * The template for search results for posts.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$search_post_id = get_the_ID();
$result         = bp_search_is_post_restricted( $search_post_id, get_current_user_id(), 'post' );
?>
<li class="bp-search-item bp-search-item_post <?php echo esc_attr( $result['post_class'] ); ?>">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( $result['has_thumb'] ) {
					?>
					<img src="<?php echo esc_url( $result['post_thumbnail'] ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php echo esc_attr( get_the_title() ); ?>"/>
					<?php
				} else {
					?>
					<i class="bb-icon-f <?php echo esc_attr( $result['post_thumbnail'] ); ?>"></i>
					<?php
				}
				?>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php the_permalink(); ?>"
				title="
				<?php
					echo esc_attr(
						/* translators: %s title attribute. */
						sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) )
					);
					?>
					"
				rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<div class="entry-content entry-summary">
				<?php
				$get_the_post_content = apply_filters( 'the_content', get_the_content( '', false, $search_post_id ) );
				// Render Divi shortcodes and other as well.
				ob_start();
				echo do_shortcode( $get_the_post_content );
				$get_the_post_content = ob_get_clean();

				$get_the_post_content = strip_shortcodes( wp_strip_all_tags( $get_the_post_content ) );
				$get_the_post_content = bp_create_excerpt(
					$get_the_post_content,
					100,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				);

				echo wp_kses_post( $get_the_post_content );
				?>
			</div>

			<div class="entry-meta">
				<span class="author">
					<?php
					/* translators: %s author name */
					printf( esc_html__( 'By %s', 'buddyboss' ), get_the_author_link() );
					?>
				</span>
				<span class="middot">&middot;</span>
				<span class="published">
					<?php echo wp_kses_post( get_the_date() ); ?>
				</span>
			</div>
		</div>
	</div>
</li>
