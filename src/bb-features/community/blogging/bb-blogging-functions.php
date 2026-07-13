<?php
/**
 * Blogs feature runtime functions.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current request is a blog-related front-end context.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_is_blog_context() {
	return is_home() || is_singular( 'post' ) || is_author() || is_category() || is_tag() || is_date();
}

/**
 * Get the enabled social share platforms.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string[] Enabled platform keys.
 */
function bb_blog_get_enabled_social_links() {
	$defaults = array(
		'facebook' => 1,
		'linkedin' => 1,
		'x'        => 0,
		'whatsapp' => 0,
		'email'    => 0,
	);

	$enabled = array();
	foreach ( $defaults as $platform => $default ) {
		if ( (bool) bp_get_option( 'bb_blog_social_link_' . $platform, $default ) ) {
			$enabled[] = $platform;
		}
	}

	/**
	 * Filter the enabled blog social share platforms.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string[] $enabled Enabled platform keys.
	 */
	return apply_filters( 'bb_blog_get_enabled_social_links', $enabled );
}

/**
 * Whether related posts are enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_is_related_posts_enabled() {
	return (bool) bp_get_option( 'bb_blog_related_posts', 1 );
}

/**
 * Whether the author bio box is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_is_author_bio_enabled() {
	return (bool) bp_get_option( 'bb_blog_author_bio', 1 );
}

/**
 * Build share links for a post, limited to enabled platforms.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $post_id Post ID.
 *
 * @return array[] Array of { label, url, icon } keyed by platform.
 */
function bb_blog_get_share_links( $post_id ) {
	$permalink = rawurlencode( get_permalink( $post_id ) );
	$title     = rawurlencode( get_the_title( $post_id ) );

	$all = array(
		'facebook' => array(
			'label' => __( 'Facebook', 'buddyboss' ),
			'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $permalink,
			'icon'  => 'bb-icons-rl-facebook-logo',
		),
		'linkedin' => array(
			'label' => __( 'Linkedin', 'buddyboss' ),
			'url'   => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $permalink,
			'icon'  => 'bb-icons-rl-linkedin-logo',
		),
		'x'        => array(
			'label' => __( 'X', 'buddyboss' ),
			'url'   => 'https://twitter.com/intent/tweet?url=' . $permalink . '&text=' . $title,
			'icon'  => 'bb-icons-rl-x-logo',
		),
		'whatsapp' => array(
			'label' => __( 'Whatsapp', 'buddyboss' ),
			'url'   => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $permalink,
			'icon'  => 'bb-icons-rl-whatsapp-logo',
		),
		'email'    => array(
			'label' => __( 'Email', 'buddyboss' ),
			'url'   => 'mailto:?subject=' . $title . '&body=' . $permalink,
			'icon'  => 'bb-icons-rl-envelope-simple',
		),
	);

	$links = array_intersect_key( $all, array_flip( bb_blog_get_enabled_social_links() ) );

	/**
	 * Filter the blog share links for a post.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $links   Share links keyed by platform.
	 * @param int   $post_id Post ID.
	 */
	return apply_filters( 'bb_blog_get_share_links', $links, $post_id );
}

/**
 * Get related posts (same categories, excluding the current post).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $post_id Post ID.
 * @param int $limit   Number of posts.
 *
 * @return WP_Query
 */
function bb_blog_get_related_posts( $post_id, $limit = 3 ) {
	$category_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => (int) $limit,
		'post__not_in'        => array( (int) $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);

	if ( ! empty( $category_ids ) && ! is_wp_error( $category_ids ) ) {
		$args['category__in'] = array_map( 'intval', $category_ids );
	} else {
		// No categories — related posts are category-based, so return an empty set.
		$args['post__in'] = array( 0 );
	}

	/**
	 * Filter the related posts query args.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args    WP_Query args.
	 * @param int   $post_id Post ID.
	 */
	return new WP_Query( apply_filters( 'bb_blog_related_posts_query_args', $args, $post_id ) );
}

/**
 * Render the post footer sections (share links, related posts, author bio).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Rendered HTML (may be empty).
 */
function bb_blog_render_post_footer_sections() {
	$post_id = get_the_ID();

	if ( empty( $post_id ) ) {
		return '';
	}

	// The blog page sections render only through the BuddyBoss Theme or
	// ReadyLaunch blog templates — never into a third-party theme.
	if ( ! function_exists( 'buddyboss_theme_get_option' ) && ! bb_is_readylaunch_enabled() ) {
		return '';
	}

	ob_start();

	// Whether buddyboss-theme is active, so its own equivalent sections (when
	// enabled via Redux options) can suppress ours and avoid duplication.
	$bb_blog_theme_active = function_exists( 'buddyboss_theme_get_option' );

	// When ReadyLaunch renders the current blog surface the theme's template
	// (and its share/author/related sections) never outputs, so the theme's
	// Redux options must not suppress ours.
	if ( $bb_blog_theme_active && bb_is_readylaunch_enabled() && bb_blog_rl_is_enabled() && bb_blog_is_blog_context() ) {
		$bb_blog_theme_active = false;
	}

	/**
	 * Filter whether to suppress the blog share row because buddyboss-theme
	 * renders its own share box (Redux option `blog_share_box`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $suppress Whether to suppress the share row.
	 * @param int  $post_id  Post ID.
	 */
	$bb_blog_suppress_share = apply_filters( 'bb_blog_suppress_share_for_theme', $bb_blog_theme_active && buddyboss_theme_get_option( 'blog_share_box' ), $post_id );

	// Social share.
	$share_links = bb_blog_get_share_links( $post_id );
	if ( ! $bb_blog_suppress_share && ! empty( $share_links ) ) {
		?>
		<div class="bb-blog-share">
			<span class="bb-blog-share__label"><?php esc_html_e( 'Share', 'buddyboss' ); ?></span>
			<?php foreach ( $share_links as $platform => $link ) : ?>
				<a class="bb-blog-share__link bb-blog-share__link--<?php echo esc_attr( $platform ); ?>" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $link['label'] ); ?>">
					<i class="bb-icons-rl <?php echo esc_attr( $link['icon'] ); ?>"></i>
				</a>
			<?php endforeach; ?>
			<?php
			/**
			 * Fires inside the share row, after the share links, for extensions
			 * that add controls alongside sharing (e.g. Pro's bookmark button).
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param int $post_id Post ID.
			 */
			do_action( 'bb_blog_share_links_after', $post_id );
			?>
		</div>
		<?php
	} elseif ( ! $bb_blog_suppress_share && has_action( 'bb_blog_share_links_after' ) ) {
		// Share row is empty (all social toggles off), but an extension still
		// wants to render here -- output a standalone wrapper so it isn't lost.
		?>
		<div class="bb-blog-share bb-blog-share--standalone">
			<?php
			/** This action is documented above. */
			do_action( 'bb_blog_share_links_after', $post_id );
			?>
		</div>
		<?php
	}

	/**
	 * Filter whether to suppress the blog author bio box because
	 * buddyboss-theme renders its own author box (Redux option `blog_author_box`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $suppress Whether to suppress the author bio box.
	 * @param int  $post_id  Post ID.
	 */
	$bb_blog_suppress_author_bio = apply_filters( 'bb_blog_suppress_author_bio_for_theme', $bb_blog_theme_active && buddyboss_theme_get_option( 'blog_author_box' ), $post_id );

	// Author bio.
	if ( ! $bb_blog_suppress_author_bio && bb_blog_is_author_bio_enabled() ) {
		$author_id  = (int) get_post_field( 'post_author', $post_id );
		$author_bio = get_the_author_meta( 'description', $author_id );
		$author_url = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $author_id ) : get_author_posts_url( $author_id );
		?>
		<div class="bb-blog-author-bio">
			<div class="bb-blog-author-bio__left">
				<a class="bb-blog-author-bio__avatar" href="<?php echo esc_url( $author_url ); ?>">
					<?php
					if ( function_exists( 'bp_core_fetch_avatar' ) ) {
						echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- avatar HTML built by BuddyPress.
							array(
								'item_id' => $author_id,
								'html'    => true,
							)
						);
					} else {
						echo get_avatar( $author_id, 96 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core avatar HTML.
					}
					?>
				</a>
				<h4 class="bb-blog-author-bio__name">
					<a href="<?php echo esc_url( $author_url ); ?>">
						<?php echo esc_html( function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $author_id ) : get_the_author_meta( 'display_name', $author_id ) ); ?>
					</a>
				</h4>
			</div>
			<div class="bb-blog-author-bio__right">
				<div class="bb-blog-author-bio__content">
					<?php if ( ! empty( $author_bio ) ) : ?>
						<p class="bb-blog-author-bio__description"><?php echo esc_html( $author_bio ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter whether to suppress the blog related posts section because
	 * buddyboss-theme renders its own related posts (Redux option `blog_related_switch`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $suppress Whether to suppress the related posts section.
	 * @param int  $post_id  Post ID.
	 */
	$bb_blog_suppress_related = apply_filters( 'bb_blog_suppress_related_for_theme', $bb_blog_theme_active && buddyboss_theme_get_option( 'blog_related_switch' ), $post_id );

	// Related posts.
	if ( ! $bb_blog_suppress_related && bb_blog_is_related_posts_enabled() ) {
		$related = bb_blog_get_related_posts( $post_id );
		if ( $related->have_posts() ) {
			?>
			<div class="bb-blog-related">
				<h3 class="bb-blog-related__title"><?php esc_html_e( 'Related Posts', 'buddyboss' ); ?></h3>
				<div class="bb-blog-related__grid">
					<?php
					while ( $related->have_posts() ) {
						$related->the_post();
						?>
						<a class="bb-blog-related__item" href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<span class="bb-blog-related__image"><?php the_post_thumbnail( 'medium' ); ?></span>
							<?php endif; ?>
							<span class="bb-blog-related__item-title"><?php the_title(); ?></span>
							<span class="bb-blog-related__date"><?php echo esc_html( get_the_date() ); ?></span>
						</a>
						<?php
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
			<?php
		}
	}

	$html = ob_get_clean();

	/**
	 * Filter the rendered blog post footer sections HTML.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $html    Rendered HTML.
	 * @param int    $post_id Post ID.
	 */
	return apply_filters( 'bb_blog_render_post_footer_sections', $html, $post_id );
}

/**
 * Append footer sections to single post content in any theme.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $content Post content.
 *
 * @return string
 */
function bb_blog_append_post_footer_sections( $content ) {
	static $rendered = array();

	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id = get_the_ID();
	if ( isset( $rendered[ $post_id ] ) ) {
		return $content;
	}
	$rendered[ $post_id ] = true;

	return $content . bb_blog_render_post_footer_sections();
}

/**
 * Render the ReadyLaunch toolbar on the member profile Blogs screens.
 *
 * One row per the design: sub-tab pills (My Blogs / Bookmarked, counts gated
 * by the Content Counts setting) on the left; the Newest/Oldest sort dropdown
 * and the Member Blogging add-on's Create button on the right. Hooked to both
 * the My Blogs and Bookmarked screens; the default profile sub-nav is hidden
 * on these screens via `blog.scss`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_rl_member_blog_toolbar() {
	if ( ! function_exists( 'bb_is_readylaunch_enabled' ) || ! bb_is_readylaunch_enabled() ) {
		return;
	}

	if ( ! function_exists( 'buddypress' ) || ! function_exists( 'bp_is_user' ) || ! bp_is_user() ) {
		return;
	}

	// Re-home the add-on's Create button inside the toolbar (the add-on hooks
	// the same actions at priority 10; this runs at 5, so removal wins).
	$bb_blog_render_create = false;
	if ( function_exists( 'bb_member_blog_render_add_new_button' ) ) {
		$bb_blog_render_create = remove_action( 'bb_blog_member_posts_before', 'bb_member_blog_render_add_new_button' );
		remove_action( 'bb_blog_bookmarks_before', 'bb_member_blog_render_add_new_button' );
	}

	$bb_blog_create_button = '';
	if ( $bb_blog_render_create ) {
		ob_start();
		bb_member_blog_render_add_new_button();
		$bb_blog_create_button = trim( ob_get_clean() );
	}

	$bb_blog_nav_items = array();
	if ( isset( buddypress()->members->nav ) ) {
		$bb_blog_nav_items = buddypress()->members->nav->get_secondary(
			array(
				'parent_slug'     => 'blog',
				'user_has_access' => true,
			)
		);
	}
	$bb_blog_nav_items = ! empty( $bb_blog_nav_items ) ? array_values( (array) $bb_blog_nav_items ) : array();

	$bb_blog_is_bookmarks = function_exists( 'bp_is_current_action' ) && bp_is_current_action( 'bookmarks' );

	$bb_blog_sort = isset( $_GET['bb-sort'] ) ? sanitize_key( wp_unslash( $_GET['bb-sort'] ) ) : 'newest'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort.
	if ( ! in_array( $bb_blog_sort, array( 'newest', 'oldest' ), true ) ) {
		$bb_blog_sort = 'newest';
	}

	$bb_blog_displayed_id = bp_displayed_user_id();
	$bb_blog_is_owner     = bp_is_my_profile() || current_user_can( 'edit_others_posts' );
	$bb_blog_counts_on    = function_exists( 'bb_enable_content_counts' ) && bb_enable_content_counts();
	$bb_blog_tab_base     = trailingslashit( bp_displayed_user_domain() . 'blog' );
	$bb_blog_sort_base    = $bb_blog_is_bookmarks ? $bb_blog_tab_base . 'bookmarks/' : $bb_blog_tab_base;
	// A single tab still renders (visitors don't get the own-profile
	// "Bookmarked" subnav but should still see the "My Blogs" pill).
	$bb_blog_show_tabs    = count( $bb_blog_nav_items ) > 0;
	?>
	<div class="bb-rl-member-blog__toolbar">
		<?php if ( $bb_blog_show_tabs ) : ?>
			<div class="bb-rl-member-blog__tabs">
				<?php
				foreach ( $bb_blog_nav_items as $bb_blog_nav_item ) {
					$bb_blog_count = null;

					if ( $bb_blog_counts_on && 'blog' === $bb_blog_nav_item->slug ) {
						if ( function_exists( 'bb_member_blog_get_profile_posts_count' ) ) {
							$bb_blog_count = bb_member_blog_get_profile_posts_count( $bb_blog_displayed_id );
						} else {
							$bb_blog_count_query = new WP_Query(
								array(
									'post_type'      => 'post',
									'author'         => $bb_blog_displayed_id,
									'post_status'    => $bb_blog_is_owner ? array( 'publish', 'draft', 'pending', 'future' ) : array( 'publish' ),
									'posts_per_page' => 1,
									'fields'         => 'ids',
								)
							);
							$bb_blog_count       = (int) $bb_blog_count_query->found_posts;
						}
					} elseif ( $bb_blog_counts_on && 'bookmarks' === $bb_blog_nav_item->slug && function_exists( 'bb_blog_pro_get_user_bookmarks' ) ) {
						$bb_blog_count = count( (array) bb_blog_pro_get_user_bookmarks( $bb_blog_displayed_id ) );
					}

					$bb_blog_label = wp_strip_all_tags( $bb_blog_nav_item->name );
					if ( null !== $bb_blog_count ) {
						/* translators: 1: sub-tab label, 2: item count. */
						$bb_blog_label = sprintf( __( '%1$s (%2$s)', 'buddyboss' ), $bb_blog_label, number_format_i18n( $bb_blog_count ) );
					}

					$bb_blog_is_current = bp_current_action() === $bb_blog_nav_item->slug;

					printf(
						'<a class="bb-rl-member-blog__tab%s" href="%s"%s>%s</a>',
						$bb_blog_is_current ? ' bb-rl-member-blog__tab--active' : '',
						esc_url( $bb_blog_nav_item->link ),
						$bb_blog_is_current ? ' aria-current="page"' : '',
						esc_html( $bb_blog_label )
					);
				}
				?>
			</div>
		<?php endif; ?>
		<div class="bb-rl-member-blog__toolbar-actions">
			<label class="bb-rl-blog-filter bb-rl-blog-filter--sort">
				<i class="bb-icons-rl bb-icons-rl-funnel-simple" aria-hidden="true"></i>
				<select class="bb-rl-blog-filter__select" data-bb-rl-blog-filter="sort" aria-label="<?php esc_attr_e( 'Sort posts', 'buddyboss' ); ?>">
					<option value="<?php echo esc_url( $bb_blog_sort_base ); ?>" <?php selected( 'newest' === $bb_blog_sort ); ?>><?php esc_html_e( 'Newest', 'buddyboss' ); ?></option>
					<option value="<?php echo esc_url( add_query_arg( 'bb-sort', 'oldest', $bb_blog_sort_base ) ); ?>" <?php selected( 'oldest' === $bb_blog_sort ); ?>><?php esc_html_e( 'Oldest', 'buddyboss' ); ?></option>
				</select>
			</label>
			<?php if ( '' !== $bb_blog_create_button ) : ?>
				<span class="bb-rl-blog-header-divider" aria-hidden="true"></span>
				<?php echo $bb_blog_create_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the add-on renderer. ?>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
add_action( 'bb_blog_member_posts_before', 'bb_blog_rl_member_blog_toolbar', 5 );
add_action( 'bb_blog_bookmarks_before', 'bb_blog_rl_member_blog_toolbar', 5 );
