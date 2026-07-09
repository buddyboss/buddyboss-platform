<?php
/**
 * ReadyLaunch - Blog archive header template.
 *
 * Title with post count on the left; view switcher (grid/list) and the
 * Category / Activity filters on the right.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_rl_blog_title       = __( 'Blog', 'buddyboss' );
$bb_rl_blog_description = '';

if ( is_category() || is_tag() ) {
	$bb_rl_blog_title       = single_term_title( '', false );
	$bb_rl_blog_description = term_description();
} elseif ( is_author() ) {
	/* translators: %s: author display name. */
	$bb_rl_blog_title = sprintf( __( 'Posts by %s', 'buddyboss' ), get_the_author_meta( 'display_name', (int) get_query_var( 'author' ) ) );
} elseif ( is_date() ) {
	$bb_rl_blog_title = get_the_archive_title();
}

// The Activity (sort) selection carries across category navigation.
$bb_rl_blog_order    = strtolower( (string) get_query_var( 'order' ) );
$bb_rl_blog_is_asc   = 'asc' === $bb_rl_blog_order;
$bb_rl_blog_home_url = get_post_type_archive_link( 'post' );
$bb_rl_blog_sort_arg = array(
	'orderby' => 'date',
	'order'   => 'asc',
);

$bb_rl_blog_categories = get_categories(
	array(
		'hide_empty' => true,
		'orderby'    => 'name',
	)
);

$bb_rl_blog_current_cat = is_category() ? (int) get_queried_object_id() : 0;
$bb_rl_blog_current_url = is_category() ? get_category_link( $bb_rl_blog_current_cat ) : $bb_rl_blog_home_url;
?>
<div class="bb-rl-secondary-header bb-rl-blog-archive-header">
	<div class="bb-rl-entry-heading">
		<h1 class="bb-rl-blog-archive-header__title">
			<?php echo esc_html( $bb_rl_blog_title ); ?>
			<span class="bb-rl-heading-count"><?php echo esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) ); ?></span>
		</h1>
		<?php if ( ! empty( $bb_rl_blog_description ) ) : ?>
			<div class="bb-rl-blog-archive-header__description"><?php echo wp_kses_post( $bb_rl_blog_description ); ?></div>
		<?php endif; ?>
	</div>

	<div class="bb-rl-blog-archive-header__controls">
		<div class="bb-rl-blog-view-switcher" role="group" aria-label="<?php esc_attr_e( 'Blog layout', 'buddyboss' ); ?>">
			<button type="button" class="bb-rl-blog-view-switcher__button is-active" data-bb-rl-blog-view="grid" aria-pressed="true" aria-label="<?php esc_attr_e( 'Grid view', 'buddyboss' ); ?>">
				<i class="bb-icons-rl bb-icons-rl-squares-four" aria-hidden="true"></i>
			</button>
			<button type="button" class="bb-rl-blog-view-switcher__button" data-bb-rl-blog-view="list" aria-pressed="false" aria-label="<?php esc_attr_e( 'List view', 'buddyboss' ); ?>">
				<i class="bb-icons-rl bb-icons-rl-rows" aria-hidden="true"></i>
			</button>
		</div>

		<span class="bb-rl-blog-header-divider" aria-hidden="true"></span>

		<?php if ( ! empty( $bb_rl_blog_categories ) ) : ?>
			<label class="bb-rl-blog-filter">
				<span class="bb-rl-blog-filter__label"><?php esc_html_e( 'Category', 'buddyboss' ); ?></span>
				<select class="bb-rl-blog-filter__select" data-bb-rl-blog-filter="category">
					<option value="<?php echo esc_url( $bb_rl_blog_is_asc ? add_query_arg( $bb_rl_blog_sort_arg, $bb_rl_blog_home_url ) : $bb_rl_blog_home_url ); ?>"><?php esc_html_e( 'All', 'buddyboss' ); ?></option>
					<?php
					foreach ( $bb_rl_blog_categories as $bb_rl_blog_category ) :
						$bb_rl_blog_category_url = get_category_link( $bb_rl_blog_category );

						if ( $bb_rl_blog_is_asc ) {
							$bb_rl_blog_category_url = add_query_arg( $bb_rl_blog_sort_arg, $bb_rl_blog_category_url );
						}
						?>
						<option value="<?php echo esc_url( $bb_rl_blog_category_url ); ?>" <?php selected( $bb_rl_blog_current_cat, (int) $bb_rl_blog_category->term_id ); ?>><?php echo esc_html( $bb_rl_blog_category->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<label class="bb-rl-blog-filter">
			<span class="bb-rl-blog-filter__label"><?php esc_html_e( 'Activity', 'buddyboss' ); ?></span>
			<select class="bb-rl-blog-filter__select" data-bb-rl-blog-filter="sort">
				<option value="<?php echo esc_url( $bb_rl_blog_current_url ); ?>" <?php selected( ! $bb_rl_blog_is_asc ); ?>><?php esc_html_e( 'Newest', 'buddyboss' ); ?></option>
				<option value="<?php echo esc_url( add_query_arg( $bb_rl_blog_sort_arg, $bb_rl_blog_current_url ) ); ?>" <?php selected( $bb_rl_blog_is_asc ); ?>><?php esc_html_e( 'Oldest', 'buddyboss' ); ?></option>
			</select>
		</label>

		<?php
		/**
		 * Fires inside the ReadyLaunch blog archive header, after the filters.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_blog_archive_header_actions' );
		?>
	</div>
</div>
