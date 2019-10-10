<?php
/**
 * LearnDash integration group sync helpers
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns LearnDash path.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_path( $path = '' ) {
	return trailingslashit( buddypress()->integrations['learndash']->path ) . trim( $path, '/\\' );
}

/**
 * Returns LearnDash url.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_url( $path = '' ) {
	return trailingslashit( buddypress()->integrations['learndash']->url ) . trim( $path, '/\\' );
}

/**
 * Return specified BuddyBoss LearnDash sync component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ld_sync( $component = null ) {
	global $bp_ld_sync;
	return $component ? $bp_ld_sync->$component : $bp_ld_sync;
}

/**
 * Return array of LearnDash group courses.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_get_group_courses( $bpGroupId ) {
	$generator = bp_ld_sync( 'buddypress' )->sync->generator( $bpGroupId );

	if ( ! $generator->hasLdGroup() ) {
		return [];
	}

	return learndash_group_enrolled_courses( $generator->getLdGroupId() );
}

// forward compatibility
if ( ! function_exists( 'learndash_get_post_type_slug' ) ) {
	/**
	 * Returns array of slugs used by LearnDash integration.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function learndash_get_post_type_slug( $type ) {
		$postTypes = [
			'course'       => 'sfwd-courses',
			'lesson'       => 'sfwd-lessons',
			'topic'        => 'sfwd-topic',
			'quiz'         => 'sfwd-quiz',
			'question'     => 'sfwd-question',
			'transactions' => 'sfwd-transactions',
			'group'        => 'groups',
			'assignment'   => 'sfwd-assignment',
			'essays'       => 'sfwd-essays',
			'certificates' => 'sfwd-certificates',
		];

		return $postTypes[ $type ];
	}
}

function learndash_integration_prepare_price_str( $price ) {
	if ( ! empty( $price ) ) {
		$currency_symbols = array(
			'AED' => '&#1583;.&#1573;', // ?
			'AFN' => '&#65;&#102;',
			'ALL' => '&#76;&#101;&#107;',
			'AMD' => '',
			'ANG' => '&#402;',
			'AOA' => '&#75;&#122;', // ?
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => '&#402;',
			'AZN' => '&#1084;&#1072;&#1085;',
			'BAM' => '&#75;&#77;',
			'BBD' => '&#36;',
			'BDT' => '&#2547;', // ?
			'BGN' => '&#1083;&#1074;',
			'BHD' => '.&#1583;.&#1576;', // ?
			'BIF' => '&#70;&#66;&#117;', // ?
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => '&#36;&#98;',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTN' => '&#78;&#117;&#46;', // ?
			'BWP' => '&#80;',
			'BYR' => '&#112;&#46;',
			'BZD' => '&#66;&#90;&#36;',
			'CAD' => '&#36;',
			'CDF' => '&#70;&#67;',
			'CHF' => '&#67;&#72;&#70;',
			'CLF' => '', // ?
			'CLP' => '&#36;',
			'CNY' => '&#165;',
			'COP' => '&#36;',
			'CRC' => '&#8353;',
			'CUP' => '&#8396;',
			'CVE' => '&#36;', // ?
			'CZK' => '&#75;&#269;',
			'DJF' => '&#70;&#100;&#106;', // ?
			'DKK' => '&#107;&#114;',
			'DOP' => '&#82;&#68;&#36;',
			'DZD' => '&#1583;&#1580;', // ?
			'EGP' => '&#163;',
			'ETB' => '&#66;&#114;',
			'EUR' => '&#8364;',
			'FJD' => '&#36;',
			'FKP' => '&#163;',
			'GBP' => '&#163;',
			'GEL' => '&#4314;', // ?
			'GHS' => '&#162;',
			'GIP' => '&#163;',
			'GMD' => '&#68;', // ?
			'GNF' => '&#70;&#71;', // ?
			'GTQ' => '&#81;',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => '&#76;',
			'HRK' => '&#107;&#110;',
			'HTG' => '&#71;', // ?
			'HUF' => '&#70;&#116;',
			'IDR' => '&#82;&#112;',
			'ILS' => '&#8362;',
			'INR' => '&#8377;',
			'IQD' => '&#1593;.&#1583;', // ?
			'IRR' => '&#65020;',
			'ISK' => '&#107;&#114;',
			'JEP' => '&#163;',
			'JMD' => '&#74;&#36;',
			'JOD' => '&#74;&#68;', // ?
			'JPY' => '&#165;',
			'KES' => '&#75;&#83;&#104;', // ?
			'KGS' => '&#1083;&#1074;',
			'KHR' => '&#6107;',
			'KMF' => '&#67;&#70;', // ?
			'KPW' => '&#8361;',
			'KRW' => '&#8361;',
			'KWD' => '&#1583;.&#1603;', // ?
			'KYD' => '&#36;',
			'KZT' => '&#1083;&#1074;',
			'LAK' => '&#8365;',
			'LBP' => '&#163;',
			'LKR' => '&#8360;',
			'LRD' => '&#36;',
			'LSL' => '&#76;', // ?
			'LTL' => '&#76;&#116;',
			'LVL' => '&#76;&#115;',
			'LYD' => '&#1604;.&#1583;', // ?
			'MAD' => '&#1583;.&#1605;.', // ?
			'MDL' => '&#76;',
			'MGA' => '&#65;&#114;', // ?
			'MKD' => '&#1076;&#1077;&#1085;',
			'MMK' => '&#75;',
			'MNT' => '&#8366;',
			'MOP' => '&#77;&#79;&#80;&#36;', // ?
			'MRO' => '&#85;&#77;', // ?
			'MUR' => '&#8360;', // ?
			'MVR' => '.&#1923;', // ?
			'MWK' => '&#77;&#75;',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => '&#77;&#84;',
			'NAD' => '&#36;',
			'NGN' => '&#8358;',
			'NIO' => '&#67;&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#65020;',
			'PAB' => '&#66;&#47;&#46;',
			'PEN' => '&#83;&#47;&#46;',
			'PGK' => '&#75;', // ?
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PYG' => '&#71;&#115;',
			'QAR' => '&#65020;',
			'RON' => '&#108;&#101;&#105;',
			'RSD' => '&#1044;&#1080;&#1085;&#46;',
			'RUB' => '&#1088;&#1091;&#1073;',
			'RWF' => '&#1585;.&#1587;',
			'SAR' => '&#65020;',
			'SBD' => '&#36;',
			'SCR' => '&#8360;',
			'SDG' => '&#163;', // ?
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&#163;',
			'SLL' => '&#76;&#101;', // ?
			'SOS' => '&#83;',
			'SRD' => '&#36;',
			'STD' => '&#68;&#98;', // ?
			'SVC' => '&#36;',
			'SYP' => '&#163;',
			'SZL' => '&#76;', // ?
			'THB' => '&#3647;',
			'TJS' => '&#84;&#74;&#83;', // ? TJS (guess)
			'TMT' => '&#109;',
			'TND' => '&#1583;.&#1578;',
			'TOP' => '&#84;&#36;',
			'TRY' => '&#8356;', // New Turkey Lira (old symbol used)
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => '',
			'UAH' => '&#8372;',
			'UGX' => '&#85;&#83;&#104;',
			'USD' => '&#36;',
			'UYU' => '&#36;&#85;',
			'UZS' => '&#1083;&#1074;',
			'VEF' => '&#66;&#115;',
			'VND' => '&#8363;',
			'VUV' => '&#86;&#84;',
			'WST' => '&#87;&#83;&#36;',
			'XAF' => '&#70;&#67;&#70;&#65;',
			'XCD' => '&#36;',
			'XDR' => '',
			'XOF' => '',
			'XPF' => '&#70;',
			'YER' => '&#65020;',
			'ZAR' => '&#82;',
			'ZMK' => '&#90;&#75;', // ?
			'ZWL' => '&#90;&#36;',
		);

		return html_entity_decode( $currency_symbols[ $price['code'] ] ) . $price['value'];
	}

	return '';
}

function bp_get_user_course_lesson_data( $couser_id, $user_id ) {
	// Get Lessons
	$lessons_list           = learndash_get_course_lessons_list( $couser_id, $user_id, [ 'num' => - 1 ] );
	$lesson_order           = 0;
	$topic_order            = 0;
	$lessons                = [];
	$status                 = [];
	$status['completed']    = 1;
	$status['notcompleted'] = 0;
	$course_id              = $couser_id;
	$data                   = [];
	$topics                 = [];
	$lesson_topics          = [];
	foreach ( $lessons_list as $lesson ) {
		$lessons[ $lesson_order ] = [
			'name'   => $lesson['post']->post_title,
			'id'     => $lesson['post']->ID,
			'status' => $status[ $lesson['status'] ],
		];

		$course_quiz_list[] = learndash_get_lesson_quiz_list( $lesson['post']->ID, $user_id, $course_id );
		$lesson_topics      = learndash_get_topic_list( $lesson['post']->ID, $course_id )?:[];

		foreach ( $lesson_topics as $topic ) {

			$course_quiz_list[] = learndash_get_lesson_quiz_list( $topic->ID, $user_id, $course_id );

			$topic_progress = learndash_get_course_progress( $user_id, $topic->ID, $course_id );

			$topics[ $topic_order ] = [
				'name'              => $topic->post_title,
				'status'            => $status['notcompleted'],
				'id'                => $topic->ID,
				'associated_lesson' => $lesson['post']->post_title,
			];

			if ( ( isset( $topic_progress['posts'] ) ) && ( ! empty( $topic_progress['posts'] ) ) ) {
				foreach ( $topic_progress['posts'] as $topic_progress ) {

					if ( $topic->ID !== $topic_progress->ID ) {
						continue;
					}

					if ( 1 === $topic_progress->completed ) {
						$topics[ $topic_order ]['status'] = $status['completed'];
					}
				}
			}
			$topic_order ++;
		}
		$lesson_order ++;
	}
	$total_lesson     = count( $lessons );
	$completed_lesson = count( wp_list_filter( $lessons, array( 'status' => 1 ) ) );
	$pending_lesson   = count( wp_list_filter( $lessons, array( 'status' => 0 ) ) );
	if ( $total_lesson > 0 ) {
		$percentage = intval( $completed_lesson * 100 / $total_lesson );
		$percentage = ( $percentage > 100 ) ? 100 : $percentage;
	} else {
		$percentage = 0;
	}

	$total_topics     = count( $topics );
	$completed_topics = count( wp_list_filter( $topics, array( 'status' => 1 ) ) );
	$pending_topics   = count( wp_list_filter( $topics, array( 'status' => 0 ) ) );
	if ( $total_topics > 0 ) {
		$topics_percentage = intval( $completed_topics * 100 / $total_topics );
		$topics_percentage = ( $topics_percentage > 100 ) ? 100 : $topics_percentage;
	} else {
		$topics_percentage = 0;
	}

	$data['all_lesson'] = $lessons;
	$data['total']      = $total_lesson;
	$data['complete']   = $completed_lesson;
	$data['pending']    = $pending_lesson;
	$data['percentage'] = $percentage;
	$data['topics']     = array(
		'all_topics' => $topics,
		'total'      => $total_topics,
		'complete'   => $completed_topics,
		'pending'    => $pending_topics,
		'percentage' => $topics_percentage,

	);

	return $data;
}

function bp_get_user_course_quiz_data( $course_id, $user_id ) {
	global $wpdb;
	$course_quiz_list   = [];
	$quizzes            = [];
	$course_quiz_list[] = learndash_get_course_quiz_list( $course_id );

	$quiz_query_string = "
			SELECT a.activity_id, a.course_id, a.post_id, a.activity_status, a.activity_completed, m.activity_meta_value as activity_percentage
			FROM {$wpdb->prefix}learndash_user_activity a
			LEFT JOIN {$wpdb->prefix}learndash_user_activity_meta m ON a.activity_id = m.activity_id
			WHERE a.user_id = {$user_id}
			AND a.course_id = {$course_id}
			AND a.activity_type = 'quiz'
			AND m.activity_meta_key = 'percentage'
		";

	$user_activities = $wpdb->get_results( $quiz_query_string );

	foreach ( $course_quiz_list as $module_quiz_list ) {
		if ( empty( $module_quiz_list ) ) {
			continue;
		}

		foreach ( $module_quiz_list as $quiz ) {
			if ( isset( $quiz['post'] ) ) {
				foreach ( $user_activities as $activity ) {
					if ( $activity->post_id == $quiz['post']->ID ) {
						$quizzes[] = [
							'name'   => $quiz['post']->post_title,
							'id'     => $quiz['post']->ID,
							'score'  => $activity->activity_percentage,
							'status' => 1,
						];
					} else {
						$quizzes[] = [
							'name'   => $quiz['post']->post_title,
							'id'     => $quiz['post']->ID,
							'score'  => $activity->activity_percentage,
							'status' => 0,
						];
					}
				}
			}
		}
	}

	$total_quizzes     = count( $quizzes );
	$completed_quizzes = count( wp_list_filter( $quizzes, array( 'status' => 1 ) ) );
	$pending_quizzes   = count( wp_list_filter( $quizzes, array( 'status' => 0 ) ) );
	if ( $total_quizzes > 0 ) {
		$percentage = intval( $completed_quizzes * 100 / $total_quizzes );
		$percentage = ( $percentage > 100 ) ? 100 : $percentage;
	} else {
		$percentage = 0;
	}

	$data['all_quizzes'] = $quizzes;
	$data['total']       = $total_quizzes;
	$data['complete']    = $completed_quizzes;
	$data['pending']     = $pending_quizzes;
	$data['percentage']  = $percentage;

	return $data;
}

function bp_ld_time_spent( $course_activity ) {

	$course_time_begin = 0;
	$course_time_end   = 0;
	$header_output     = '';

	if ( ( property_exists( $course_activity, 'activity_started' ) ) || ( !empty( $course_activity->activity_started ) ) ) {
		$course_time_begin = $course_activity->activity_started;
	}

	if ( ( property_exists( $course_activity, 'activity_updated' ) ) || ( !empty( $course_activity->activity_updated ) ) ) {
		$course_time_end = $course_activity->activity_updated;
	}

	if ( property_exists( $course_activity, 'activity_status' ) ) {
		if ( $course_activity->activity_status == true ) {
			if ( ( property_exists( $course_activity, 'activity_completed' ) ) || ( !empty( $course_activity->activity_completed ) ) ) {
				//$course_time_end = learndash_adjust_date_time_display( $activity->activity_completed, 'Y-m-d' );
				$course_time_end = $course_activity->activity_completed;
			}
		}
	}

	if ( ( !empty( $course_time_begin ) ) && ( !empty( $course_time_end ) ) ) {
		$course_time_diff = $course_time_end - $course_time_begin;
		if ( $course_time_diff > 0) {

			if ( $course_time_diff > 86400 ) {
				if ( !empty( $header_output ) ) $header_output .= ' ';
				$header_output .= sprintf( '%d %s', floor($course_time_diff / 86400), _n( 'day', 'days', floor($course_time_diff / 86400), 'buddyboss' ) );
					$course_time_diff %= 86400;
			}

			if ( $course_time_diff > 3600 ) {
				if ( !empty( $header_output ) ) $header_output .= ' ';
				$header_output .= sprintf( '%d %s', floor( $course_time_diff / 3600 ), _n( 'hr', 'hrs', floor( $course_time_diff / 3600 ), 'buddyboss' ) );
				$course_time_diff %= 3600;
			}

			if ( $course_time_diff > 60 ) {
				if ( !empty( $header_output ) ) $header_output .= ' ';
				$header_output .= sprintf( '%d %s', floor( $course_time_diff / 60 ), _n( 'min', 'mins', floor( $course_time_diff / 60 ), 'buddyboss' ) );
					$course_time_diff %= 60;
			}

			if ( $course_time_diff > 0 ) {
				if ( !empty( $header_output ) ) $header_output .= ' ';
				$header_output .= sprintf( '%d %s', $course_time_diff, _n( 'sec', 'secs', $course_time_diff, 'buddyboss' ) );
			}
		} else {
			$header_output = 0;
		}

		if ( $header_output ===  0 ) {
			$header_output = '-';
		}
	} else {
		$header_output = '-';
	}

	return $header_output;
}

function bp_ld_remove_post_ids_param( $query_args ) {
	if ( isset( $query_args['post_ids'] ) ) {
		unset( $query_args['post_ids'] );
	}

	return $query_args;

}

function bpLdCoursePointsEarned( $activity ) {

	$assignments = learndash_get_user_assignments( $activity->post_id, $activity->user_id );
	if ( ! empty( $assignments ) ) {
		foreach ( $assignments as $assignment ) {
			$assignment_points = learndash_get_points_awarded_array( $assignment->ID );
			if ( $assignment_points || learndash_is_assignment_approved_by_meta( $assignment->ID ) ) {
				if ( $assignment_points ) {
					return (int) $assignment_points['current'];
				}
			}
		}
	}

	$post_settings = learndash_get_setting( $activity->post_id );

	if ( isset( $activity->post_type ) && ( 'sfwd-topic' === $activity->post_type || 'sfwd-lessons' === $activity->post_type ) ) {

		if ( 0 === $activity->activity_status ) {
			return 0;
		}

		if ( isset( $post_settings['lesson_assignment_points_enabled'] ) && 'on' === $post_settings['lesson_assignment_points_enabled'] && isset( $post_settings['lesson_assignment_points_amount'] ) && $post_settings['lesson_assignment_points_amount'] > 0 ) {
			return (int) $post_settings['lesson_assignment_points_amount'];
		} else {
			return 0;
		}
	} elseif ( isset( $activity->post_type ) && 'sfwd-courses' === $activity->post_type ) {

		if ( 0 === $activity->activity_status ) {
			return 0;
		}

		if ( isset( $post_settings['course_points_enabled'] ) && 'on' === $post_settings['course_points_enabled'] && isset( $post_settings['course_points'] ) && $post_settings['course_points'] > 0 ) {
			return (int) $post_settings['course_points'];
		} else {
			return 0;
		}
	}
	return 0;
}

function bp_ld_course_points_earned( $course, $user ) {
	global $learndash_post_types;
	$param     = [];
	$param['course_ids'] = $course;
	$param['post_types'] = $learndash_post_types;
	$param['user_ids']        = $user;
	$param['activity_status'] = 'COMPLETED';
	$param['per_page']        = '';

	add_filter( 'learndash_get_activity_query_args', 'bp_ld_remove_post_ids_param', 10, 1 );
	$data = learndash_reports_get_activity( $param );
	remove_filter( 'learndash_get_activity_query_args', 'bp_ld_remove_post_ids_param', 10 );
	$points = 0;
	if ( ! empty( $data ) ) {
		foreach ( $data['results'] as $activity ) {
			$points = $points + bpLdCoursePointsEarned( $activity );
		}
	}

	if ( $points > 0 ) {
		return $points;
	} else {
		return '-';
	}

}

function bp_ld_get_course_all_steps( $course_id, $user_id, $type = 'all' ) {

	global $wpdb;

	$lesson_list = learndash_get_lesson_list( $course_id );

	$steps = array();
	$circle = '';

	foreach( $lesson_list as $lesson ) {

		$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson->ID, 'lesson', 1 );
		$activity = $wpdb->get_row( $sql_str );
		if ( empty( $activity ) ) {
			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson->ID, 'lesson', 0 );
			$activity = $wpdb->get_row( $sql_str );
		}

		if ( $activity ) {
			$activity->post_type = get_post_type( $lesson->ID );
		}


		if ( isset( $activity ) && isset( $activity->activity_status ) && $activity->activity_status == '1' ) {
			$circle = '<div class="i-progress i-progress-completed"><i class="bb-icon-check"></i></div>';
		} else {
			$circle = '<div class="i-progress i-progress-not-completed"><i class="bb-icon-circle"></i></div>';
		}

		if ( 'all' === $type || 'lesson' === $type ) {
			$steps[] = array(
				'id'       => $lesson->ID,
				'title'    => $circle . $lesson->post_title,
				'activity' => $activity,
			);
		}

		$lesson_topics = learndash_get_topic_list( $lesson->ID, $course_id );

		if ( ! empty( $lesson_topics ) ) {
			foreach ( $lesson_topics as $lesson_topic ) {

				$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson_topic->ID, 'topic', 1 );
				$activity = $wpdb->get_row( $sql_str );
				if ( empty( $activity ) ) {
					$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson_topic->ID, 'topic', 0 );
					$activity = $wpdb->get_row( $sql_str );
				}

				if ( $activity ) {
					$activity->post_type = get_post_type( $lesson_topic->ID );
				}

				if ( isset( $activity ) && isset( $activity->activity_status ) && $activity->activity_status == '1' ) {
					$circle = '<div class="i-progress i-progress-completed"><i class="bb-icon-check"></i></div>';
				} else {
					$circle = '<div class="i-progress i-progress-not-completed"><i class="bb-icon-circle"></i></div>';
				}

				if ( 'all' === $type || 'topic' === $type ) {
					$steps[] = array(
						'id'       => $lesson_topic->ID,
						'title'    => $circle . $lesson_topic->post_title,
						'activity' => $activity,
					);
				}

			}
		}

		$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID, $user_id, $course_id );

		if( ! empty( $lesson_quizzes ) ) {
			foreach( $lesson_quizzes as $lesson_quiz ) {

				$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson_quiz['post']->ID, 'quiz', 1 );
				$activity = $wpdb->get_row( $sql_str );
				if ( empty( $activity ) ) {
					$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $lesson_quiz['post']->ID, 'quiz', 0 );
					$activity = $wpdb->get_row( $sql_str );
				}

				if ( $activity ) {
					$activity->post_type = get_post_type( $lesson_quiz['post']->ID );
				}

				if ( isset( $activity ) && isset( $activity->activity_status ) && $activity->activity_status == '1' ) {
					$circle = '<div class="i-progress i-progress-completed"><i class="bb-icon-check"></i></div>';
				} else {
					$circle = '<div class="i-progress i-progress-not-completed"><i class="bb-icon-circle"></i></div>';
				}

				$attempt = learndash_get_user_quiz_attempts_count( $user_id, $lesson_quiz['post']->ID );

				if ( 'all' === $type || 'quiz' === $type ) {
					$steps[] = array(
						'id'       => $lesson_quiz['post']->ID,
						'title'    => $circle . $lesson_quiz['post']->post_title,
						'activity' => $activity,
						'attempt'  => ( $attempt ) ? $attempt : '-',

					);
				}

			}
		}
	}

	$course_quizzes = learndash_get_course_quiz_list( $course_id, $user_id );

	if ( ! empty( $course_quizzes ) ) {
		foreach( $course_quizzes as $course_quiz ) {

			$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $course_quiz['post']->ID, 'quiz', 1 );
			$activity = $wpdb->get_row( $sql_str );
			if ( empty( $activity ) ) {
				$sql_str = $wpdb->prepare("SELECT * FROM " . LDLMS_DB::get_table_name( 'user_activity' ) . " WHERE user_id=%d AND course_id=%d AND post_id=%d AND activity_type=%s AND activity_status=%d LIMIT 1", $user_id, $course_id, $course_quiz['post']->ID, 'quiz', 0 );
				$activity = $wpdb->get_row( $sql_str );
			}

			if ( isset( $activity ) && isset( $activity->activity_status ) && $activity->activity_status == '1' ) {
				$circle = '<div class="i-progress i-progress-completed"><i class="bb-icon-check"></i></div>';
			} else {
				$circle = '<div class="i-progress i-progress-not-completed"><i class="bb-icon-circle"></i></div>';
			}

			$score = '-';
			if ( $activity ) {
				$activity->post_type = get_post_type( $course_quiz['post']->ID );
				$activity_fields = learndash_get_activity_meta_fields( $activity->activity_id );
				$score = bp_ld_quiz_activity_points_percentage( $activity_fields );
			}

			$attempt = learndash_get_user_quiz_attempts_count( $user_id, $course_quiz['post']->ID );

			if ( 'all' === $type || 'quiz' === $type ) {
				$steps[] = array(
					'id'       => $course_quiz['post']->ID,
					'title'    => $circle . $course_quiz['post']->post_title,
					'activity' => $activity,
					'attempt'  => ( $attempt ) ? $attempt : '-',
					'score'    => $score,
				);
			}

		}
	}

	if ( 'assignment' === $type ) {

		$approved_args = array(
			'posts_per_page' => - 1,
			'post_type'      => learndash_get_post_type_slug( 'assignment' ),
			'post_status'    => 'publish',
			'author'         => $user_id,
			'meta_query'     => array(
				array(
					'key'   => 'course_id',
					'value' => $course_id,
				),
			),
		);

		$approved_args['meta_query'][] = array(
			'key'   => 'approval_status',
			'value' => 1,
		);

		$approved_assignments = new WP_Query( $approved_args );

		$pending_args = array(
			'posts_per_page' => - 1,
			'post_type'      => learndash_get_post_type_slug( 'assignment' ),
			'post_status'    => 'publish',
			'author'         => $user_id,
			'meta_query'     => array(
				array(
					'key'   => 'course_id',
					'value' => $course_id,
				),
			),
		);

		$pending_args['meta_query'][] = array(
			'key'     => 'approval_status',
			'compare' => 'NOT EXISTS',
		);

		$pending_assignments = new WP_Query( $pending_args );

		$assignments = array_merge( $approved_assignments->posts, $pending_assignments->posts );

		foreach ( $assignments as $assignment ) {
			$steps[] = array(
				'id'       => $assignment->ID,
				'title'    => $assignment->post_content,
				'graded' => '',
				'score'    => '',
			);
		}
	}

	return $steps;


}

function bp_ld_quiz_activity_points_percentage( $activity ) {
	$awarded_points = intval( bp_ld_quiz_activity_awarded_points( $activity ) );
	$total_points = intval( bp_ld_quiz_activity_total_points( $activity ) );
	if ( ( !empty( $awarded_points ) ) && ( !empty( $total_points ) ) ) {
		return round( 100 * ( intval( $awarded_points ) / intval( $total_points ) ) );
	}
}

/**
 * @param $activity
 *
 * @return mixed
 */
function bp_ld_quiz_activity_total_points( $activity ) {
	if ( !empty( $activity ) ) {
		if ( isset( $activity['total_points'] ) ) {
			return intval($activity['total_points']);
		}
	}
}


/**
 * @param $activity
 *
 * @return mixed
 */
function bp_ld_quiz_activity_awarded_points( $activity ) {
	if ( ( !empty( $activity ) ) ) {
		if ( isset( $activity['points'] ) ) {
			return intval($activity['points']);
		}
	}
}
