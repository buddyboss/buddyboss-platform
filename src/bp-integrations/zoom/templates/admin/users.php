<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$users = bp_zoom_get_users();
?>
<a href="<?php
echo esc_url( bp_get_admin_url( add_query_arg( array(
	'page'  => 'bp-integrations',
	'tab'   => 'bp-zoom',
	'flush' => 'true'
), 'admin.php' ) ) )
?>"><?php _e( 'Flush User Cache', 'buddyboss' ); ?></a>
<div>
	<table class="display" width="100%">
		<thead>
		<tr>
			<th><?php _e( 'SN', 'buddyboss' ); ?></th>
			<th><?php _e( 'User ID', 'buddyboss' ); ?></th>
			<th><?php _e( 'Email', 'buddyboss' ); ?></th>
			<th><?php _e( 'Name', 'buddyboss' ); ?></th>
			<th><?php _e( 'Created On', 'buddyboss' ); ?></th>
			<th><?php _e( 'Last Login', 'buddyboss' ); ?></th>
			<th><?php _e( 'Last Client', 'buddyboss' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$count = 1;
		if ( ! empty( $users ) ) {
			foreach ( $users as $user ) {
				?>
				<tr>
					<td><?php echo $count ++; ?></td>
					<td><?php echo $user->id; ?></td>
					<td><?php echo $user->email; ?></td>
					<td><?php echo $user->first_name . ' ' . $user->last_name; ?></td>
					<td><?php echo ! empty( $user->created_at ) ? date( 'F j, Y, g:i a', strtotime( $user->created_at ) ) : "N/A"; ?></td>
					<td><?php echo ! empty( $user->last_login_time ) ? date( 'F j, Y, g:i a', strtotime( $user->last_login_time ) ) : "N/A"; ?></td>
					<td><?php echo ! empty( $user->last_client_version ) ? $user->last_client_version : "N/A"; ?></td>
				</tr>
				<?php
			}
		}
		?>
		</tbody>
	</table>
</div>
