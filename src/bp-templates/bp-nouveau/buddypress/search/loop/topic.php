<?php
$topic_id = get_the_ID();
$total = bbp_get_topic_reply_count( $topic_id ) ?>
<li class="bp-search-item bp-search-item_topic">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_topic_permalink( $topic_id ); ?>">
				<img
					src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( $topic_id ) ) ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>"
					class="avatar forum-avatar"
					height="150"
					width="150"
					alt=""
				/>
			</a>
		</div>

		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php bbp_topic_permalink( $topic_id ); ?>"><?php bbp_topic_title( $topic_id ); ?></a>
			</h3>
			<div class="entry-content entry-summary">
				<?php echo wp_trim_words( bbp_get_topic_content( $topic_id ), 30, '...' ) ?>
			</div>
			<div class="entry-meta">
				<span class="reply-count">
					<?php printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="freshness">
					<?php bbp_topic_freshness_link( $topic_id ) ?>
				</span>
			</div>
			<?php
			$discussion_tags = get_the_terms( $topic_id, bbpress()->topic_tag_tax_id );

			if ( ! empty( $discussion_tags ) ) {
				?>
				<div class="item-tags">
					<span class="item-tag-cap">
						<?php
						esc_html_e( 'Tags:', 'buddyboss' );
						?>
					</span>
					<?php
					$tags_count = ( is_array( $discussion_tags ) || is_object( $discussion_tags ) ) ? count( $discussion_tags ) : 0;
					$loop_count = 1;
					foreach ( $discussion_tags as $key => $discussion_tag ) {
						?>
						<span class="discussion-tag">
							<?php
							echo esc_html( $discussion_tag->name );
							if( $tags_count != $loop_count ){
								echo ", ";
							}
							?>
						</span>
						<?php
						$loop_count++;
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</li>
