<?php
/**
 * ReadyLaunch - Search Loop Product template.
 *
 * The template for search results for products.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$product_id    = get_the_ID();
$product       = wc_get_product( $product_id );
$product_thumb = get_the_post_thumbnail_url();
?>
<li class="bp-search-item bp-search-item_product">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( $product_thumb ) {
					?>
					<img src="<?php echo esc_url( $product_thumb ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php esc_attr( get_the_title() ); ?>" />
					<?php
				} else {
					?>
					<i class="bb-icon-f <?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
					<?php
				}
				?>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'buddyboss' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>
			<?php wc_get_template( 'single-product/short-description.php' ); ?>
			<span class="entry-meta">
				<span class="item-meta-category">
					<?php
					$category = wc_get_product_category_list( $product_id );
					if ( $category ) {
						echo wc_get_product_category_list( $product_id, '<span class="middot">&middot;</span>' );
					}
					?>
				</span>
				<span class="middot">&middot;</span>
				<div class="item-meta-amount">
					<?php echo wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix(); ?>
				</div>
			</span>
		</div>
	</div>
</li>
