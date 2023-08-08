<?php

/**
 * Forums Admin Metaboxes
 *
 * @package BuddyBoss\Administration
 */

/** Dashboard *****************************************************************/

/**
 * Forums Dashboard Right Now Widget
 *
 * Adds a dashboard widget with forum statistics
 *
 * @since bbPress (r2770)
 *
 * @uses bbp_get_statistics() To get the forum statistics
 * @uses current_user_can() To check if the user is capable of doing things
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses admin_url() To get the administration url
 * @uses add_query_arg() To add custom args to the url
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_content_table_end'
 *                    below the content table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_discussion_table_end'
 *                    below the discussion table
 * @uses do_action() Calls 'bbp_dashboard_widget_right_now_end' below the widget
 */
function bbp_dashboard_widget_right_now() {

	// Get the statistics
	$r = bbp_get_statistics(); ?>

	<div class="table table_content">

		<p class="sub"><?php esc_html_e( 'Forum Discussions', 'buddyboss' ); ?></p>

		<table>

			<tr class="first">

				<?php
					$num  = empty( $r['forum_count'] ) ? 0 : (int) $r['forum_count'];
					$text = _n( 'Forum', 'Forums', $num, 'buddyboss' );
					if ( current_user_can( 'publish_forums' ) ) {
						$link = add_query_arg( array( 'post_type' => bbp_get_forum_post_type() ), admin_url( null, 'edit.php' ) );
						$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
						$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
					}
				?>

				<td class="first b b-forums"><?php echo $num; ?></td>
				<td class="t forums"><?php echo $text; ?></td>

			</tr>

			<tr>

				<?php
					$num  = empty( $r['topic_count'] ) ? 0 : (int) $r['topic_count'];
					$text = _n( 'Discussion', 'Discussions', $num, 'buddyboss' );
				if ( current_user_can( 'publish_topics' ) ) {
					$link = add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td class="first b b-topics"><?php echo $num; ?></td>
				<td class="t topics"><?php echo $text; ?></td>

			</tr>

			<?php if ( bbp_allow_topic_tags() ) : ?>

				<tr>

					<?php
						$num  = empty( $r['topic_tag_count'] ) ? 0 : (int) $r['topic_tag_count'];
						$text = _n( 'Discussion Tag', 'Discussion Tags', $num, 'buddyboss' );
					if ( current_user_can( 'manage_topic_tags' ) ) {
						$link = add_query_arg(
							array(
								'taxonomy'  => bbp_get_topic_tag_tax_id(),
								'post_type' => bbp_get_topic_post_type(),
							),
							admin_url( null, 'edit-tags.php' )
						);
						$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
						$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
					}
					?>

					<td class="first b b-topic_tags"><span class="total-count"><?php echo $num; ?></span></td>
					<td class="t topic_tags"><?php echo $text; ?></td>

				</tr>

			<?php endif; ?>

			<tr>

				<?php
					$num  = empty( $r['reply_count'] ) ? 0 : (int) $r['reply_count'];
					$text = _n( 'Reply', 'Replies', $num, 'buddyboss' );
				if ( current_user_can( 'publish_replies' ) ) {
					$link = add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td class="first b b-replies"><?php echo $num; ?></td>
				<td class="t replies"><?php echo $text; ?></td>

			</tr>

			<?php do_action( 'bbp_dashboard_widget_right_now_content_table_end' ); ?>

		</table>

	</div>


	<div class="table table_discussion">

		<p class="sub"><?php esc_html_e( 'Users &amp; Moderation', 'buddyboss' ); ?></p>

		<table>

			<tr class="first">

				<?php
					$num  = empty( $r['user_count'] ) ? 0 : (int) $r['user_count'];
					$text = _n( 'User', 'Users', $num, 'buddyboss' );
				if ( current_user_can( 'edit_users' ) ) {
					$link = admin_url( null, 'users.php' );
					$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . $text . '</a>';
				}
				?>

				<td class="b b-users"><span class="total-count"><?php echo $num; ?></span></td>
				<td class="last t users"><?php echo $text; ?></td>

			</tr>

			<?php if ( isset( $r['topic_count_hidden'] ) ) : ?>

				<tr>

					<?php
						$num  = $r['topic_count_hidden'];
						$text = _n( 'Hidden Discussion', 'Hidden Discussions', $num, 'buddyboss' );
						$link = add_query_arg( array( 'post_type' => bbp_get_topic_post_type() ), admin_url( null, 'edit.php' ) );
					if ( '0' !== $num ) {
						$link = add_query_arg( array( 'post_status' => bbp_get_spam_status_id() ), $link );
					}
						$num  = '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_topic_title'] ) . '">' . $num . '</a>';
						$text = '<a class="waiting" href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_topic_title'] ) . '">' . $text . '</a>';
					?>

					<td class="b b-hidden-topics"><?php echo $num; ?></td>
					<td class="last t hidden-replies"><?php echo $text; ?></td>

				</tr>

			<?php endif; ?>

			<?php if ( isset( $r['reply_count_hidden'] ) ) : ?>

				<tr>

					<?php
						$num  = $r['reply_count_hidden'];
						$text = _n( 'Hidden Reply', 'Hidden Replies', $r['reply_count_hidden'], 'buddyboss' );
						$link = add_query_arg( array( 'post_type' => bbp_get_reply_post_type() ), admin_url( null, 'edit.php' ) );
					if ( '0' !== $num ) {
						$link = add_query_arg( array( 'post_status' => bbp_get_spam_status_id() ), $link );
					}
						$num  = '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_reply_title'] ) . '">' . $num . '</a>';
						$text = '<a class="waiting" href="' . esc_url( $link ) . '" title="' . esc_attr( $r['hidden_reply_title'] ) . '">' . $text . '</a>';
					?>

					<td class="b b-hidden-replies"><?php echo $num; ?></td>
					<td class="last t hidden-replies"><?php echo $text; ?></td>

				</tr>

			<?php endif; ?>

			<?php if ( bbp_allow_topic_tags() && isset( $r['empty_topic_tag_count'] ) ) : ?>

				<tr>

					<?php
						$num  = empty( $r['empty_topic_tag_count'] ) ? 0 : (int) $r['empty_topic_tag_count'];
						$text = _n( 'Empty Discussion Tag', 'Empty Discussion Tags', $num, 'buddyboss' );
						$link = add_query_arg(
							array(
								'taxonomy'  => bbp_get_topic_tag_tax_id(),
								'post_type' => bbp_get_topic_post_type(),
							),
							admin_url( null, 'edit-tags.php' )
						);
						$num  = '<a href="' . esc_url( $link ) . '">' . $num . '</a>';
						$text = '<a class="waiting" href="' . esc_url( $link ) . '">' . $text . '</a>';
					?>

					<td class="b b-hidden-topic-tags"><?php echo $num; ?></td>
					<td class="last t hidden-topic-tags"><?php echo $text; ?></td>

				</tr>

			<?php endif; ?>

			<?php do_action( 'bbp_dashboard_widget_right_now_discussion_table_end' ); ?>

		</table>

	</div>

	<?php do_action( 'bbp_dashboard_widget_right_now_table_end' ); ?>

	<br class="clear" />

	<?php

	do_action( 'bbp_dashboard_widget_right_now_end' );
}

/** Forums ********************************************************************/

/**
 * Forum metabox
 *
 * The metabox that holds all of the additional forum information
 *
 * @since bbPress (r2744)
 *
 * @param WP_Post $post Post object.
 *
 * @uses bbp_is_forum_closed() To check if a forum is closed or not
 * @uses bbp_is_forum_category() To check if a forum is a category or not
 * @uses bbp_is_forum_private() To check if a forum is private or not
 * @uses bbp_dropdown() To show a dropdown of the forums for forum parent
 * @uses do_action() Calls 'bbp_forum_metabox'
 */
function bbp_forum_metabox( $post ) {

	// Post ID
	$post_id     = get_the_ID();
	$post_parent = bbp_get_global_post_field( 'post_parent', 'raw' );
	$menu_order  = bbp_get_global_post_field( 'menu_order', 'edit' );
	$group_ids   = bbp_get_forum_group_ids( $post_id );

	/** Type */

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Type:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_forum_type_select"><?php esc_html_e( 'Type:', 'buddyboss' ); ?></label>
		<?php bbp_form_forum_type_dropdown( array( 'forum_id' => $post_id ) ); ?>
	</p>

	<?php

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Status:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_forum_status_select"><?php esc_html_e( 'Status:', 'buddyboss' ); ?></label>
		<?php bbp_form_forum_status_dropdown( array( 'forum_id' => $post_id ) ); ?>
	</p>

	<?php

	/** Visibility ************************************************************/

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Visibility:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_forum_visibility_select"><?php esc_html_e( 'Visibility:', 'buddyboss' ); ?></label>
		<?php bbp_form_forum_visibility_dropdown( array( 'forum_id' => $post_id ) ); ?>
	</p>

	<hr />

	<?php

	/** Parent ****************************************************************/

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Parent:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php esc_html_e( 'Forum Parent', 'buddyboss' ); ?></label>
		<?php
		bbp_dropdown(
			array(
				'post_type'          => bbp_get_forum_post_type(),
				'selected'           => $post_parent,
				'numberposts'        => -1,
				'orderby'            => 'title',
				'order'              => 'ASC',
				'walker'             => '',
				'exclude'            => $post_id,

				// Output-related
				'select_id'          => 'parent_id',
				'tab'                => bbp_get_tab_index(),
				'options_only'       => false,
				'show_none'          => __( '- Select Forum -', 'buddyboss' ),
				'disable_categories' => false,
				'disabled'           => empty( $group_ids ) ? false : true,
			)
		);
		?>
	</p>

	<p>
		<strong class="label"><?php esc_html_e( 'Order:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="menu_order"><?php esc_html_e( 'Forum Order', 'buddyboss' ); ?></label>
		<input name="menu_order" type="number" step="1" size="4" id="menu_order" value="<?php echo esc_attr( $menu_order ); ?>" />
	</p>

	<input name="ping_status" type="hidden" id="ping_status" value="open" />

	<?php
	wp_nonce_field( 'bbp_forum_metabox_save', 'bbp_forum_metabox' );
	do_action( 'bbp_forum_metabox', $post );
}

/** Topics ********************************************************************/

/**
 * Topic metabox
 *
 * The metabox that holds all of the additional topic information
 *
 * @since bbPress (r2464)
 *
 * @param WP_Post $post Post object.
 *
 * @uses bbp_get_topic_forum_id() To get the topic forum id
 * @uses do_action() Calls 'bbp_topic_metabox'
 */
function bbp_topic_metabox( $post ) {

	// Post ID
	$post_id = get_the_ID();

	/** Type */

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Type:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_stick_topic"><?php esc_html_e( 'Type', 'buddyboss' ); ?></label>
		<?php bbp_form_topic_type_dropdown( array( 'topic_id' => $post_id ) ); ?>
	</p>

	<?php

	/** Status ****************************************************************/

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Status:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_open_close_topic"><?php esc_html_e( 'Select whether to open or close the discussion.', 'buddyboss' ); ?></label>
		<?php
		bbp_form_topic_status_dropdown(
			array(
				'select_id' => 'post_status',
				'topic_id'  => $post_id,
			)
		);
		?>
	</p>

	<?php

	/** Parent *****************************************************************/

	?>

	<p>
		<strong class="label"><?php esc_html_e( 'Forum:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php esc_html_e( 'Forum', 'buddyboss' ); ?></label>
		<?php
		bbp_dropdown(
			array(
				'post_type'          => bbp_get_forum_post_type(),
				'selected'           => bbp_get_topic_forum_id( $post_id ),
				'numberposts'        => -1,
				'orderby'            => 'title',
				'order'              => 'ASC',
				'walker'             => '',
				'exclude'            => '',

				// Output-related
				'select_id'          => 'parent_id',
				'tab'                => bbp_get_tab_index(),
				'options_only'       => false,
				'show_none'          => __( '- Select Forum -', 'buddyboss' ),
				'disable_categories' => current_user_can( 'edit_forums' ),
				'disabled'           => '',
			)
		);
		?>
	</p>

	<input name="ping_status" type="hidden" id="ping_status" value="open" />

	<?php
	wp_nonce_field( 'bbp_topic_metabox_save', 'bbp_topic_metabox' );
	do_action( 'bbp_topic_metabox', $post );
}

/** Replies *******************************************************************/

/**
 * Reply metabox
 *
 * The metabox that holds all of the additional reply information
 *
 * @since bbPress (r2464)
 *
 * @param WP_Post $post Post object.
 *
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses do_action() Calls 'bbp_reply_metabox'
 */
function bbp_reply_metabox( $post ) {

	// Post ID
	$post_id = get_the_ID();

	// Get some meta
	$reply_topic_id = bbp_get_reply_topic_id( $post_id );
	$reply_forum_id = bbp_get_reply_forum_id( $post_id );
	$reply_to       = bbp_get_reply_to( $post_id );

	// Allow individual manipulation of reply forum
	if ( current_user_can( 'edit_others_replies' ) || current_user_can( 'moderate' ) ) :
		?>

		<p>
			<strong class="label"><?php esc_html_e( 'Forum:', 'buddyboss' ); ?></strong>
			<label class="screen-reader-text" for="bbp_forum_id"><?php esc_html_e( 'Forum', 'buddyboss' ); ?></label>
			<?php
			bbp_dropdown(
				array(
					'post_type'          => bbp_get_forum_post_type(),
					'selected'           => $reply_forum_id,
					'numberposts'        => - 1,
					'orderby'            => 'title',
					'order'              => 'ASC',
					'walker'             => '',
					'exclude'            => '',

					// Output-related
					'select_id'          => 'bbp_forum_id',
					'tab'                => bbp_get_tab_index(),
					'options_only'       => false,
					'show_none'          => __( '- Select Forum -', 'buddyboss' ),
					'disable_categories' => current_user_can( 'edit_forums' ),
					'disabled'           => '',
				)
			);
			?>
		</p>

	<?php endif; ?>

	<p>
		<strong class="label"><?php esc_html_e( 'Discussion:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="parent_id"><?php esc_html_e( 'Discussion', 'buddyboss' ); ?></label>
		<?php
		bbp_dropdown(
			array(
				'post_type'             => bbp_get_topic_post_type(),
				'selected'              => $reply_topic_id,
				'post_parent'           => $reply_forum_id,
				'numberposts'           => - 1,
				'orderby'               => 'title',
				'order'                 => 'ASC',
				'walker'                => '',
				'exclude'               => '',

				// Output-related
				'select_id'             => 'parent_id',
				'tab'                   => bbp_get_tab_index(),
				'options_only'          => false,
				'show_none'             => __( '- Select Discussion -', 'buddyboss' ),
				'show_none_default_val' => 0,
				'disable_categories'    => current_user_can( 'edit_forums' ),
				'disabled'              => '',
			)
		);
		?>
<!--		<input name="parent_id" id="bbp_topic_id" type="text" value="--><?php // echo esc_attr( $reply_topic_id ); ?><!--" data-ajax-url="--><?php // echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'bbp_suggest_topic' ), admin_url( 'admin-ajax.php', 'relative' ) ) ), 'bbp_suggest_topic_nonce' ); ?><!--" />-->
	</p>

	<p>
		<strong class="label"><?php esc_html_e( 'Reply To:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_reply_to"><?php esc_html_e( 'Reply To', 'buddyboss' ); ?></label>
		<?php
		bbp_dropdown(
			array(
				'post_type'             => bbp_get_reply_post_type(),
				'selected'              => $reply_to,
				'post_parent'           => $reply_topic_id,
				'numberposts'           => - 1,
				'orderby'               => 'title',
				'order'                 => 'ASC',
				'walker'                => '',
				'exclude'               => '',

				// Output-related
				'select_id'             => 'bbp_reply_to',
				'tab'                   => bbp_get_tab_index(),
				'options_only'          => false,
				'show_none'             => __( '- Select Reply -', 'buddyboss' ),
				'show_none_default_val' => 0,
				'disable_categories'    => current_user_can( 'edit_forums' ),
				'disabled'              => '',
			)
		);
		?>
<!--		<input name="bbp_reply_to" id="bbp_reply_to" type="text" value="--><?php // echo esc_attr( $reply_to ); ?><!--" />-->
	</p>

	<input name="ping_status" type="hidden" id="ping_status" value="open" />

	<?php
	wp_nonce_field( 'bbp_reply_metabox_save', 'bbp_reply_metabox' );
	do_action( 'bbp_reply_metabox', $post );
}

/** Users *********************************************************************/

/**
 * Anonymous user information metabox
 *
 * @since bbPress (r2828)
 *
 * @uses bbp_is_reply_anonymous() To check if reply is anonymous
 * @uses bbp_is_topic_anonymous() To check if topic is anonymous
 * @uses get_the_ID() To get the global post ID
 * @uses get_post_meta() To get the author user information
 */
function bbp_author_metabox() {

	// Post ID
	$post_id = get_the_ID();

	// Show extra bits if topic/reply is anonymous
	if ( bbp_is_reply_anonymous( $post_id ) || bbp_is_topic_anonymous( $post_id ) ) :
		?>

		<p>
			<strong class="label"><?php esc_html_e( 'Name:', 'buddyboss' ); ?></strong>
			<label class="screen-reader-text" for="bbp_anonymous_name"><?php esc_html_e( 'Name', 'buddyboss' ); ?></label>
			<input type="text" id="bbp_anonymous_name" name="bbp_anonymous_name" value="<?php echo esc_attr( get_post_meta( $post_id, '_bbp_anonymous_name', true ) ); ?>" />
		</p>

		<p>
			<strong class="label"><?php esc_html_e( 'Email:', 'buddyboss' ); ?></strong>
			<label class="screen-reader-text" for="bbp_anonymous_email"><?php esc_html_e( 'Email', 'buddyboss' ); ?></label>
			<input type="text" id="bbp_anonymous_email" name="bbp_anonymous_email" value="<?php echo esc_attr( get_post_meta( $post_id, '_bbp_anonymous_email', true ) ); ?>" />
		</p>

		<p>
			<strong class="label"><?php esc_html_e( 'Website:', 'buddyboss' ); ?></strong>
			<label class="screen-reader-text" for="bbp_anonymous_website"><?php esc_html_e( 'Website', 'buddyboss' ); ?></label>
			<input type="text" id="bbp_anonymous_website" name="bbp_anonymous_website" value="<?php echo esc_attr( get_post_meta( $post_id, '_bbp_anonymous_website', true ) ); ?>" />
		</p>

	<?php else : ?>

		<p>
			<strong class="label"><?php esc_html_e( 'ID:', 'buddyboss' ); ?></strong>
			<label class="screen-reader-text" for="bbp_author_id"><?php esc_html_e( 'ID', 'buddyboss' ); ?></label>
			<input type="text" id="bbp_author_id" name="post_author_override" value="<?php echo esc_attr( bbp_get_global_post_field( 'post_author' ) ); ?>" data-ajax-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'bbp_suggest_user' ), admin_url( 'admin-ajax.php', 'relative' ) ) ), 'bbp_suggest_user_nonce' ); ?>" />
		</p>

	<?php endif; ?>

	<p>
		<strong class="label"><?php esc_html_e( 'IP:', 'buddyboss' ); ?></strong>
		<label class="screen-reader-text" for="bbp_author_ip_address"><?php esc_html_e( 'IP Address', 'buddyboss' ); ?></label>
		<input type="text" id="bbp_author_ip_address" name="bbp_author_ip_address" value="<?php echo esc_attr( get_post_meta( $post_id, '_bbp_author_ip', true ) ); ?>" disabled="disabled" />
	</p>

	<?php

	do_action( 'bbp_author_metabox', $post_id );
}

/**
 * Filter the Dashboard "at a glance" items and append bbPress elements to it.
 *
 * @since BBPress 2.6.0 (r5268)
 * @since BuddyBoss 2.4.00
 *
 * @param array $elements
 * @return array
 */
function bbp_filter_dashboard_glance_items( $elements = array() ) {

	// Bail if user cannot spectate.
	if ( ! current_user_can( 'spectate' ) ) {
		return $elements;
	}

	// Get the statistics.
	$r = bbp_get_statistics(
		array(
			'count_pending_topics'  => false,
			'count_private_topics'  => false,
			'count_spammed_topics'  => false,
			'count_trashed_topics'  => false,
			'count_pending_replies' => false,
			'count_private_replies' => false,
			'count_spammed_replies' => false,
			'count_trashed_replies' => false,
			'count_empty_tags'      => false,
		)
	);

	// Users.
	if ( isset( $r['user_count'] ) ) {
		$link = admin_url( 'users.php' );
		/* translators: %s: number of users */
		$text       = sprintf( _n( '%s User', '%s Users', $r['user_count_int'], 'buddyboss' ), $r['user_count'] );
		$elements[] = current_user_can( 'edit_users' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-users">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Forums.
	if ( isset( $r['forum_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_forum_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of forums */
		$text       = sprintf( _n( '%s Forum', '%s Forums', $r['forum_count_int'], 'buddyboss' ), $r['forum_count'] );
		$elements[] = current_user_can( 'publish_forums' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-forums">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Topics.
	if ( isset( $r['topic_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_topic_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of topics */
		$text       = sprintf( _n( '%s Discussion', '%s Discussions', $r['topic_count_int'], 'buddyboss' ), $r['topic_count'] );
		$elements[] = current_user_can( 'publish_topics' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-topics">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Replies.
	if ( isset( $r['reply_count'] ) ) {
		$link = add_query_arg(
			array(
				'post_type' => bbp_get_reply_post_type(),
			),
			admin_url( 'edit.php' )
		);
		/* translators: %s: number of replies */
		$text       = sprintf( _n( '%s Reply', '%s Replies', $r['reply_count_int'], 'buddyboss' ), $r['reply_count'] );
		$elements[] = current_user_can( 'publish_replies' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-replies">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Topic Tags.
	if ( bbp_allow_topic_tags() && isset( $r['topic_tag_count'] ) ) {
		$link = add_query_arg(
			array(
				'taxonomy'  => bbp_get_topic_tag_tax_id(),
				'post_type' => bbp_get_topic_post_type(),
			),
			admin_url( 'edit-tags.php' )
		);
		/* translators: %s: number of topic tags */
		$text       = sprintf( _n( '%s Discussion Tag', '%s Discussion Tags', $r['topic_tag_count_int'], 'buddyboss' ), $r['topic_tag_count'] );
		$elements[] = current_user_can( 'manage_topic_tags' )
			? '<a href="' . esc_url( $link ) . '" class="bbp-glance-topic-tags">' . esc_html( $text ) . '</a>'
			: esc_html( $text );
	}

	// Filter & return.
	return apply_filters( 'bbp_dashboard_at_a_glance', $elements, $r );
}
