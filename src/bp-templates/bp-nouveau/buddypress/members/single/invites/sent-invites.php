<?php
bp_nouveau_member_hook( 'before', 'invites_sent_template' );
?>

<?php

if ( isset( $_GET['email'] ) ) {
	?>
	<aside class="bp-feedback bp-send-invites bp-template-notice success">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
				$text = __( 'Invitations were sent successfully to the following email addresses:', 'buddyboss' );
				echo trim ($text.' '. filter_input( INPUT_GET, 'email', FILTER_SANITIZE_STRING ) );
			?>
		</p>
		<script>window.history.replaceState(null, null, window.location.pathname);</script>
	</aside>
	<?php
}

?>

<h2 class="screen-heading general-settings-screen">
	<?php _e( 'Sent Invites', 'buddyboss' ); ?>
</h2>

<p class="info invite-info">
	<?php _e( 'You have sent invitation emails to the following people.', 'buddyboss' ); ?>
</p>

<table class="invite-settings bp-tables-user" id="<?php echo esc_attr( 'member-invites-table' ); ?>">
	<thead>
	<tr>
		<th class="title"><?php esc_html_e( 'Name', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Email', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Invited', 'buddyboss' ); ?></th>
		<th class="title"><?php esc_html_e( 'Status', 'buddyboss' ); ?></th>
	</tr>
	</thead>

	<tbody>

	<?php
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
	$sent_invites_pagination_count = apply_filters( 'sent_invites_pagination_count', 25 );
	$args = array(
		'posts_per_page' => $sent_invites_pagination_count,
		'post_type'      => bp_get_invite_post_type(),
		'author'         => bp_loggedin_user_id(),
		'paged'          => $paged,
	);
	$the_query = new WP_Query( $args );

	if($the_query->have_posts()) {

		while ( $the_query->have_posts() ) : $the_query->the_post();
			?>
			<tr>
				<td class="field-name">
					<span><?php echo get_post_meta( get_the_ID(), '_bp_invitee_name', true ); ?></span>
				</td>
				<td class="field-email">
					<span><?php echo get_post_meta( get_the_ID(), '_bp_invitee_email', true ); ?></span>
				</td>
				<td class="field-email">
					<span>
						<?php
						$date = get_the_date( '',get_the_ID() );
						echo $date;
						?>
					</span>
				</td>
				<td class="field-email">
					<?php
					$class = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'registered' : 'revoked-access';
					$alert_message = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Are you sure you want to revoke invite?', 'buddyboss' );
					$icon = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? 'dashicons-yes' : 'dashicons-dismiss';
					$title = ( '1' === get_post_meta( get_the_ID(), '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Revoke Invite', 'buddyboss' );
					$revoke_link = bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_invites_slug() . '/revoke-invite';
					?>
					<span><a data-revoke-access="<?php echo esc_url( $revoke_link ); ?>" data-name="<?php echo esc_attr( $alert_message ); ?>" id="<?php echo esc_attr( get_the_ID() ); ?>" class="<?php echo esc_attr( $class ); ?>" href="javascript:void(0);"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span><?php echo $title; ?></a></span>
				</td>
			</tr>
			<?php
		endwhile;

	} else {
		?>
		<tr>
			<td colspan="4" class="field-name">
				<span><?php esc_html_e( 'You have\'t sent any invitations to the people.', 'buddyboss' ); ?></span>
			</td>
		</tr>
		<?php
	}

	$total_pages = $the_query->max_num_pages;

	if ( $total_pages > 1 ){

		$current_page = max(1, get_query_var('paged'));

		echo paginate_links(array(
			'base' => get_pagenum_link(1) . '%_%',
			'format' => '?paged=%#%',
			'current' => $current_page,
			'total' => $total_pages,
			'prev_text'    => __('« Prev'),
			'next_text'    => __('Next »'),
		));
	}

	wp_reset_postdata();
	?>

	</tbody>
</table>
<?php
bp_nouveau_member_hook( 'after', 'invites_sent_template' );

