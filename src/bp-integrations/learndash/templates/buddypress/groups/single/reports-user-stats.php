<?php
/**
 * Course Reports User Stats
 *
 * @package BuddyBoss\Integrations\LearnDash\Reports
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo Should we be labeling Teacher and Student here?
 */
if ($courseId): ?>
	<h3 class="ld-report-course-name"><?php echo $course->post_title; ?></h3>
<?php endif; ?>

<div class="ld-report-user-stats">
	<div class="user-info">
		<div class="user-avatar">
			<a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user->ID ) ); ?></a>
		</div>
		<div class="user-name">
			<h5 class="list-title member-name"><a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo bp_core_get_user_displayname( $user->ID ); ?></a></h5>
			<p class="item-meta"><?php echo groups_is_user_admin($user->ID, $group->id)? __('Teacher', 'buddyboss') : __('Student', 'buddyboss'); ?></p>
		</div>
	</div>

	<?php
	if ( $courseId && isset( $_GET ) && isset( $_GET['step'] ) && ( 'all' === $_GET['step'] || 'sfwd-courses' === $_GET['step'] ) ) {
		$progress = learndash_course_progress( array(
			'user_id'   => $user->ID,
			'course_id' => $course->ID,
			'array'     => true
		) );
		?>
		<div class="user-info">
			<div class="progress-bar" data-percent="<?php echo $progress['percentage']; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p><?php printf(
					__('<b>%d out of %d</b> %s completed', 'buddyboss'),
					learndash_course_get_completed_steps($user->ID, $course->ID),
					$totalSteps = learndash_get_course_steps_count($course->ID),
					_n('step', 'steps', $totalSteps, 'buddyboss')
				); ?></p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-lessons' === $_GET['step'] ) {
		$data = bp_get_user_course_lesson_data( $_GET['course'], $user->ID );
		?>
		<div class="user-info">
			<div class="progress-bar" data-percent="<?php echo $data['percentage']; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p><?php printf(
					__('<b>%d out of %d</b> %s completed', 'buddyboss'),
					$data['complete'],
					$data['total'],
					_n( LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'lessons' ), $data['total'], 'buddyboss')
				); ?></p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-topic' === $_GET['step'] ) {
		$data = bp_get_user_course_lesson_data( $_GET['course'], $user->ID );
		?>
		<div class="user-info">
			<div class="progress-bar" data-percent="<?php echo $data['topics']['percentage']; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p><?php printf(
					__('<b>%d out of %d</b> %s completed', 'buddyboss'),
					$data['topics']['complete'],
					$data['topics']['total'],
					_n( LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'topics' ), $data['topics']['total'], 'buddyboss')
				); ?></p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-quiz' === $_GET['step'] ) {
		$data = bp_get_user_course_quiz_data( $_GET['course'], $user->ID );
		?>
		<div class="user-info">
			<div class="progress-bar" data-percent="<?php echo $data['percentage']; ?>" data-duration="1000" data-color="#BCE3A9,#60AF37"></div>
		</div>
		<div class="user-steps">
			<p><?php printf(
					__('<b>%d out of %d</b> %s completed', 'buddyboss'),
					$data['complete'],
					$data['total'],
					_n( LearnDash_Custom_Label::get_label( 'quiz' ), LearnDash_Custom_Label::get_label( 'quizzes' ), $data['total'], 'buddyboss')
				); ?></p>
		</div>
		<?php
	} elseif ( isset( $_GET ) && isset( $_GET['step'] ) && 'sfwd-assignment' === $_GET['step'] ) {

	}


	if ($points = learndash_get_user_course_points($user->ID)): ?>
		<div class="user-points">
			<p><?php printf(
				__('<b>%d</b> %s earned', 'buddyboss'),
				$points,
				_n('point', 'points', $points, 'buddyboss')
			); ?></p>
		</div>
	<?php endif; ?>

	<?php if (bp_is_active('messages') && $user->ID != bp_loggedin_user_id()): ?>
		<div class="user-message">
			<?php
				$link = apply_filters(
					'bp_get_send_private_message_link',
					wp_nonce_url(
						bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_activity_get_user_mentionname($user->ID)
					)
				);

				echo bp_get_send_message_button([
					'link_href' => $link,
					'link_text' => __( 'Message', 'buddyboss' ),
				]);
			?>
		</div>
	<?php endif; ?>
</div>
