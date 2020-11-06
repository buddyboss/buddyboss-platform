<?php $total = bbp_get_topic_reply_count( get_the_ID() ) ?>
<div class="bp-search-ajax-item bp-search-ajax-item_topic">
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bbp_get_topic_permalink(get_the_ID()) )); ?>">
		<div class="item-avatar">
			<img
				src="<?php echo bbp_get_forum_thumbnail_src( bbp_get_forum_id( get_the_ID() ) ) ?: bp_search_get_post_thumbnail_default( get_post_type() ); ?>"
				class="avatar forum-avatar"
				height="150"
				width="150"
				alt=""
			/>
		</div>
		<div class="item">
            <div class="item-title">
                <?php echo stripslashes( wp_strip_all_tags( bbp_get_topic_title( get_the_ID() ) ) );?>
            </div>
			<div class="item-desc">
				<?php
            	//@todo remove %d?
				printf( _n( '%d reply', '%d replies', $total, 'buddyboss' ), $total ); ?>
			</div>
			<?php
			$discussion_tags = get_the_terms( get_the_ID(), bbpress()->topic_tag_tax_id );
			$tags_count      = ( is_array( $discussion_tags ) || is_object( $discussion_tags ) ) ? count( $discussion_tags ) : 0;
			$loop_count = 1;
			if ( ! empty( $discussion_tags ) ) {
				?>
				<div class="item-tags">
					<span class="item-tag-cap">
						<?php
						esc_html_e( 'Tags:', 'buddyboss' );
						?>
					</span>
					<?php
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
	</a>
</div>
