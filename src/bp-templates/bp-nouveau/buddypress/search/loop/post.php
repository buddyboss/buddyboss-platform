<?php
$result = bp_search_is_post_restricted( get_the_ID(), get_current_user_id(), 'post' );
?>
<li class="bp-search-item bp-search-item_post <?php echo esc_attr( $result['post_class'] ); ?>">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php the_permalink(); ?>">
				<img src="<?php echo $result['post_thumbnail']; ?>" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="<?php the_title() ?>"/>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s',
					'buddyboss' ),
					the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h3>

			<div class="entry-content entry-summary">
				<?php echo $result['post_content']; ?>
			</div>

			<?php if ( get_post_type() == 'post' ) { ?>
				<div class="entry-meta">
					<span class="author">
						<?php printf( esc_html__( 'By %s', 'buddyboss' ), get_the_author_link() ) ?>
					</span> <span class="middot">&middot;</span> <span class="published">
						<?php the_date() ?>
					</span>
				</div>
			<?php } ?>
		</div>
	</div>
</li>
