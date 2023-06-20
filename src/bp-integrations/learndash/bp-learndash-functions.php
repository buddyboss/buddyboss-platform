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
		return array();
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
		$postTypes = array(
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
		);

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

		return html_entity_decode( $currency_symbols[ $price['code'] ], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . $price['value'];
	}

	return '';
}

/**
 * Function to get list of certificates the user has
 *
 * @since BuddyBoss 1.2.0
 *
 * @param string $user_id
 *
 * @return array|bool
 */
function bp_learndash_get_users_certificates( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Course Certificate
	 **/
	$user_courses = ld_get_mycourses( $user_id, array() );
	$certificates = array();
	foreach ( $user_courses as $course_id ) {

		$certificateLink = learndash_get_course_certificate_link( $course_id, $user_id );
		$filename        = "Certificate.pdf";
		$course_title    = get_the_title( $course_id );
		$certificate_id  = learndash_get_setting( $course_id, 'certificate' );
		$image           = '';

		if ( ! empty( $certificate_id ) ) {
			$certificate_data = get_post( $certificate_id );
			$filename         = sanitize_file_name( $course_title ) . "-" . sanitize_file_name( $certificate_data->post_title ) . ".pdf";
			$image            = wp_get_attachment_url( get_post_thumbnail_id( $certificate_id ) );
		}

		$date = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

		if ( ! empty( $certificateLink ) ) {
			$certificate           = new \stdClass();
			$certificate->ID       = $course_id;
			$certificate->link     = $certificateLink;
			$certificate->title    = get_the_title( $course_id );
			$certificate->filename = $filename;
			$certificate->date     = date_i18n( "Y-m-d h:i:s", $date );
			$certificate->time     = $date;
			$certificate->type     = 'course';
			$certificates[]        = $certificate;
		}
	}

	/**
	 * Quiz Certificate
	 **/
	$quizzes  = get_user_meta( $user_id, '_sfwd-quizzes', true );
	$quiz_ids = empty( $quizzes ) ? array() : wp_list_pluck( $quizzes, 'quiz' );
	if ( ! empty( $quiz_ids ) ) {
		$quiz_total_query_args = array(
			'post_type' => 'sfwd-quiz',
			'fields'    => 'ids',
			'orderby'   => 'title', //$atts['quiz_orderby'],
			'order'     => 'ASC', //$atts['quiz_order'],
			'nopaging'  => true,
			'post__in'  => $quiz_ids
		);
		$quiz_query            = new \WP_Query( $quiz_total_query_args );
		$quizzes_tmp           = array();
		foreach ( $quiz_query->posts as $post_idx => $quiz_id ) {
			foreach ( $quizzes as $quiz_idx => $quiz_attempt ) {
				if ( $quiz_attempt['quiz'] == $quiz_id ) {
					$quiz_key                 = $quiz_attempt['time'] . '-' . $quiz_attempt['quiz'];
					$quizzes_tmp[ $quiz_key ] = $quiz_attempt;
					unset( $quizzes[ $quiz_idx ] );
				}
			}
		}
		$quizzes = $quizzes_tmp;
		krsort( $quizzes );
		if ( ! empty( $quizzes ) ) {
			foreach ( $quizzes as $quizdata ) {
				if ( ! in_array( $quizdata['quiz'], wp_list_pluck( $certificates, 'ID' ) ) ) {
					$quiz_settings         = learndash_get_setting( $quizdata['quiz'] );
					$certificate_post_id   = intval( $quiz_settings['certificate'] );
					$certificate_post_data = get_post( $certificate_post_id );
					$certificate_data      = learndash_certificate_details( $quizdata['quiz'], $user_id );
					if ( ! empty( $certificate_data['certificateLink'] ) && $certificate_data['certificate_threshold'] <= $quizdata['percentage'] / 100 ) {
						$filename              = sanitize_file_name( get_the_title( $quizdata['quiz'] ) ) . "-" . sanitize_file_name( get_the_title( $certificate_post_id ) ) . ".pdf";
						$certificate           = new \stdClass();
						$certificate->ID       = $quizdata['quiz'];
						$certificate->link     = $certificate_data['certificateLink'];
						$certificate->title    = get_the_title( $quizdata['quiz'] );
						$certificate->filename = $filename;
						$certificate->date     = date_i18n( "Y-m-d h:i:s", $quizdata['time'] );
						$certificate->time     = $quizdata['time'];
						$certificate->type     = 'quiz';
						$certificates[]        = $certificate;
					}
				}

			}
		}
	}

	usort( $certificates, function ( $a, $b ) {
		return strcmp( $b->time, $a->time );
	} );

	return $certificates;
}

/**
 * Get the course style view
 *
 * @since BuddyBoss 1.2.0
 *
 * @return string
 */
function bp_learndash_page_display() {

	if ( empty( $_COOKIE['courseview'] ) || $_COOKIE['courseview'] == '' ) {

		if ( function_exists( 'bp_get_view' ) ):
			$view = bp_get_view();
		else:
			$view = 'grid';
		endif;
	} else {
		$view = $_COOKIE['courseview'];
	}

	return $view;
}

/**
 * Check if there is any certificated created by the admin and if so then show the certificate tab or else hide the tab
 *
 * @since BuddyBoss 1.2.0
 *
 * @return $value bool
 */
function bp_core_learndash_certificates_enables() {
	static $cache = null;

	$value = false;
	$args  = array(
		'post_type'   => 'sfwd-certificates',
		'post_status' => 'publish',
		'numberposts' => 1,
		'fields'      => 'ids',
		// 'numberposts' => 1 -> We just check here if certification available then display tab in profile section.
		// So if we get only one course then we can verify it like certificate available or not.
	);

	if ( null === $cache ) {
		$query = get_posts( $args );
	} else {
		$query = $cache;
	}

	if ( ! empty( $query ) && count( $query ) > 0 ) {
		$value = true;
	}

	return $value;
}

/**
 * Social Group Sync View Tutorial button.
 *
 * @since BuddyBoss 1.5.8
 */
function bb_tutorial_social_group_sync() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62877,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * LearnDash Group Sync View Tutorial button.
 *
 * @since BuddyBoss 1.5.8
 */
function bb_tutorial_learndash_group_sync() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62877,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * My Courses Tab View Tutorial button.
 *
 * @since BuddyBoss 1.5.8
 */
function bb_profiles_tutorial_my_courses() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 83110,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * LeanDash permalink slug for profile courses.
 *
 * @since BuddyBoss 1.5.9
 *
 * @return string
 */
function bb_learndash_profile_courses_slug() {
	return ltrim( apply_filters( 'bp_learndash_profile_courses_slug', \LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ) ), '/' );
}
