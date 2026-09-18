<?php
/**
 * ReadyLaunch - Blog post card template (one card per loop iteration).
 *
 * Shared markup for the grid and list layouts — the two arrangements are
 * CSS-only (`.bb-rl-blog-grid--list` modifier on the container).
 *
 * The status tag and the more-options menu are gated by two independent flags
 * (`bb_rl_blog_card_show_status` / `bb_rl_blog_card_show_menu`), both seeded
 * from the `bb_blog_card_context` query var — mirroring the non-RL sibling
 * `bp-templates/bp-nouveau/buddypress/blog/loop-post.php`. This lets a
 * bookmark list keep the menu while hiding the status tag (or vice-versa).
 *
 * @since   BuddyBoss 3.2.0
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

$bb_rl_blog_categories  = get_the_category();
$bb_rl_blog_author_id   = (int) get_the_author_meta( 'ID' );
$bb_rl_blog_author_url  = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $bb_rl_blog_author_id ) : get_author_posts_url( $bb_rl_blog_author_id );
$bb_rl_blog_author_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $bb_rl_blog_author_id ) : get_the_author();

if ( '' === trim( (string) $bb_rl_blog_author_name ) ) {
	$bb_rl_blog_author_name = __( 'Unknown Member', 'buddyboss' );
}
$bb_rl_blog_status        = get_post_status();
$bb_rl_blog_status_obj    = get_post_status_object( $bb_rl_blog_status );
$bb_rl_blog_comment_count = (int) get_comments_number();

// Display labels per the design (WP's own label for anything unmapped).
$bb_rl_blog_status_labels = array(
	'publish' => __( 'Published', 'buddyboss' ),
	'pending' => __( 'In Review', 'buddyboss' ),
	'draft'   => __( 'Draft', 'buddyboss' ),
	'future'  => __( 'Scheduled', 'buddyboss' ),
);
$bb_rl_blog_status_label  = isset( $bb_rl_blog_status_labels[ $bb_rl_blog_status ] )
	? $bb_rl_blog_status_labels[ $bb_rl_blog_status ]
	: ( $bb_rl_blog_status_obj ? $bb_rl_blog_status_obj->label : ucfirst( $bb_rl_blog_status ) );

// Card context, mirrored from the non-RL sibling
// (bp-templates/bp-nouveau/buddypress/blog/loop-post.php). Empty on the public
// blog archive; set to 'member-posts' or 'bookmarks' on the member profile
// Blogs tab. Drives the status-tag and menu defaults below and is passed to
// each filter so consumers can branch on it. Unknown/absent values fall back
// to '' so the public archive keeps its historical defaults (no status tag,
// no menu, inline status for non-publish posts).
$bb_rl_blog_card_context = get_query_var( 'bb_blog_card_context' );
$bb_rl_blog_card_context = in_array( $bb_rl_blog_card_context, array( 'member-posts', 'bookmarks' ), true ) ? $bb_rl_blog_card_context : '';

/**
 * Filter whether to show the post status tag on the card image (member
 * profile Blogs tab). When off, non-publish statuses show inline after
 * the title instead.
 *
 * @since BuddyBoss 3.2.0
 *
 * @param bool   $show    Whether to show the on-image status tag.
 * @param string $context Card context: '', 'member-posts', or 'bookmarks'.
 */
$bb_rl_blog_show_status_tag = (bool) apply_filters( 'bb_rl_blog_card_show_status', false, $bb_rl_blog_card_context );

/**
 * Filter whether to show the card more-options menu, independently of the
 * status tag above. Defaults to the resolved status-tag flag so the historical
 * coupling (the menu appeared whenever the status tag did) is preserved for
 * existing consumers; bookmark lists can override this to keep the menu while
 * hiding the status tag (or vice-versa) without touching the other.
 *
 * @since BuddyBoss 3.2.0
 *
 * @param bool   $show    Whether to show the more-options menu.
 * @param string $context Card context: '', 'member-posts', or 'bookmarks'.
 */
$bb_rl_blog_show_menu = (bool) apply_filters( 'bb_rl_blog_card_show_menu', $bb_rl_blog_show_status_tag, $bb_rl_blog_card_context );
?>
<article class="bb-rl-blog-card" id="post-<?php the_ID(); ?>">
	<div class="bb-rl-blog-card__image">
		<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
			<?php
			if ( has_post_thumbnail() ) :
				the_post_thumbnail( 'medium_large', array( 'class' => 'img-responsive' ) );
			else :
				?>
				<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_cover_image.jpeg' ); ?>" alt="<?php esc_attr_e( 'Blog post placeholder image', 'buddyboss' ); ?>">
			<?php endif; ?>
		</a>
		<?php if ( $bb_rl_blog_show_status_tag ) : ?>
			<span class="bb-rl-blog-card__status-tag bb-rl-blog-card__status-tag--<?php echo esc_attr( $bb_rl_blog_status ); ?>"><?php echo esc_html( $bb_rl_blog_status_label ); ?></span>
		<?php endif; ?>
	</div>
	<div class="bb-rl-blog-card__content">
		<h2 class="bb-rl-blog-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<?php if ( ! $bb_rl_blog_show_status_tag && 'publish' !== $bb_rl_blog_status ) : ?>
				<span class="bb-rl-blog-card__status bb-rl-blog-card__status--<?php echo esc_attr( $bb_rl_blog_status ); ?>"><?php echo esc_html( $bb_rl_blog_status_label ); ?></span>
			<?php endif; ?>
		</h2>
		<?php if ( $bb_rl_blog_show_menu ) : ?>
			<div class="bb-rl-blog-card__menu">
				<button type="button" class="bb-rl-blog-card__menu-toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
					<i class="bb-icons-rl bb-icons-rl-dots-three" aria-hidden="true"></i>
				</button>
				<ul class="bb-rl-blog-card__menu-list">
					<?php
					/**
					 * Filter whether the "View Post" item appears in the blog card
					 * menu. Add-ons that inject owner actions (e.g. Member
					 * Blogging's Edit/Delete) suppress the redundant item — the
					 * card already carries its own "View Post" button — so the menu
					 * shows only the management actions.
					 *
					 * @since BuddyBoss 3.2.0
					 *
					 * @param bool $show    Whether to show the View Post menu item.
					 * @param int  $post_id Post ID.
					 */
					if ( apply_filters( 'bb_blog_card_show_view_post_menu_item', true, get_the_ID() ) ) :
						?>
						<li><a href="<?php the_permalink(); ?>"><i class="bb-icons-rl bb-icons-rl-eye" aria-hidden="true"></i><?php esc_html_e( 'View Post', 'buddyboss' ); ?></a></li>
						<?php
					endif;
					?>
					<?php
					/**
					 * Fires inside the blog card more-options menu (member profile
					 * cards) — used to add extra items, e.g. the Member Blogging
					 * add-on's Edit Post link.
					 *
					 * @since BuddyBoss 3.2.0
					 *
					 * @param int $post_id Post ID.
					 */
					do_action( 'bb_blog_card_menu_items', get_the_ID() );
					?>
				</ul>
			</div>
		<?php endif; ?>
		<div class="bb-rl-blog-card__byline">
			<span class="bb-rl-blog-card__date"><?php echo esc_html( get_the_date() ); ?></span>
			<?php if ( ! empty( $bb_rl_blog_categories ) && ! is_wp_error( $bb_rl_blog_categories ) ) : ?>
				<a class="bb-rl-blog-card__category" href="<?php echo esc_url( get_category_link( $bb_rl_blog_categories[0] ) ); ?>" title="<?php echo esc_attr( $bb_rl_blog_categories[0]->name ); ?>"><?php echo esc_html( $bb_rl_blog_categories[0]->name ); ?></a>
			<?php endif; ?>
		</div>
		<div class="bb-rl-blog-card__author">
			<a href="<?php echo esc_url( $bb_rl_blog_author_url ); ?>" class="item-avatar bb-rl-author-avatar">
				<?php
				if ( function_exists( 'bp_core_fetch_avatar' ) ) {
					echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- avatar HTML built by BuddyPress.
						array(
							'item_id' => $bb_rl_blog_author_id,
							'html'    => true,
						)
					);
				}
				?>
			</a>
			<span class="bb-rl-author-name">
				<?php
				printf(
					/* translators: %s: linked author display name. */
					esc_html__( 'By %s', 'buddyboss' ),
					'<a href="' . esc_url( $bb_rl_blog_author_url ) . '">' . esc_html( $bb_rl_blog_author_name ) . '</a>'
				);
				?>
			</span>
		</div>
		<div class="bb-rl-blog-card__excerpt"><?php the_excerpt(); ?></div>
		<a class="bb-rl-blog-card__view" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'View Post', 'buddyboss' ); ?>
			<i class="bb-icons-rl bb-icons-rl-caret-right" aria-hidden="true"></i>
		</a>
		<a class="bb-rl-blog-card__comments" href="<?php echo esc_url( get_comments_link() ); ?>">
			<i class="bb-icons-rl bb-icons-rl-chat-teardrop" aria-hidden="true"></i>
			<?php echo esc_html( number_format_i18n( $bb_rl_blog_comment_count ) ); ?>
		</a>
	</div>
</article>
