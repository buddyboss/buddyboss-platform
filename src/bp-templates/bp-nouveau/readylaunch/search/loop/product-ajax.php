<?php
/**
 * ReadyLaunch - Search Loop Product AJAX template.
 *
 * The template for AJAX search results for products.
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
<div class="bp-search-ajax-item bp-search-ajax-item_product">
	<a href="<?php echo esc_url( get_permalink() ); ?>">
		<div class="item-avatar">
			<?php
			if ( $product_thumb ) {
				?>
				<img src="<?php echo esc_url( $product_thumb ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php the_title(); ?>" />
				<?php
			} else {
				?>
				<i class="bb-icon-f <?php echo esc_attr( bp_search_get_post_thumbnail_default( get_post_type(), 'icon' ) ); ?>"></i>
				<?php
			}
			?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a>
		</div>
		<div class="item-desc"><?php wc_get_template( 'single-product/short-description.php' ); ?></div>
		<div class="entry-meta">
			<?php
			$category = wc_get_product_category_list( $product_id );
			if ( $category ) {
				echo wc_get_product_category_list( $product_id, '<span class="middot">&middot;</span>' );
			}
			?>
			<span class="middot">&middot;</span>
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>
	</div>
</div>
