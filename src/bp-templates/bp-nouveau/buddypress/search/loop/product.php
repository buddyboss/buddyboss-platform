<?php
/**
 * Template for displaying the search results of the product
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/product.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$product = wc_get_product( get_the_ID() ); ?>
<li class="bp-search-item bp-search-item_product">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<?php
				if ( get_the_post_thumbnail_url() ) {
					?>
					<img src="<?php echo esc_url( get_the_post_thumbnail_url() ); ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php esc_attr( get_the_title() ); ?>" />
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
				<?php
				$category = wc_get_product_category_list( get_the_ID() );
				if ( $category ) {
					echo wc_get_product_category_list( get_the_ID(), '<span class="middot">&middot;</span>' );
				}
				?>

			</span>
			<span class="middot">&middot;</span>
			<div class="item-extra">
				<?php echo wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix(); ?>
			</div>

		</div>


	</div>
</li>
