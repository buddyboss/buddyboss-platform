<?php

/**
 * Member joined date — bb_get_member_joined_date() and the ReadyLaunch directory formatter.
 *
 * @group members
 * @group joined_date
 */
class BB_Tests_Members_Joined_Date extends BP_UnitTestCase {

	/**
	 * Original month abbreviations, restored after locale-simulating tests.
	 *
	 * @var array|null
	 */
	protected $month_abbrev_backup = null;

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'bb_get_member_joined_date' ) ) {
			require_once buddypress()->plugin_dir . 'bp-templates/bp-nouveau/includes/members/template-tags.php';
		}

		if ( ! class_exists( 'BB_Readylaunch' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-readylaunch.php';
		}
	}

	public function tear_down() {
		if ( null !== $this->month_abbrev_backup ) {
			$GLOBALS['wp_locale']->month_abbrev = $this->month_abbrev_backup;
			$this->month_abbrev_backup          = null;
		}

		remove_filter( 'bb_get_member_joined_date', 'BB_Readylaunch::bb_rl_modify_member_joined_date', 10 );

		parent::tear_down();
	}

	/**
	 * Create a user with a fixed registration date.
	 *
	 * @param string $registered MySQL datetime (GMT).
	 *
	 * @return int
	 */
	protected function create_user_registered_on( $registered ) {
		global $wpdb;

		$user_id = self::factory()->user->create();
		$wpdb->update( $wpdb->users, array( 'user_registered' => $registered ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		return $user_id;
	}

	/**
	 * Simulate a site locale by replacing the month abbreviations date_i18n() reads.
	 *
	 * @param array $abbrevs Map of English abbreviation => localized abbreviation.
	 */
	protected function set_month_abbrevs( $abbrevs ) {
		global $wp_locale;

		$this->month_abbrev_backup = $wp_locale->month_abbrev;

		foreach ( $abbrevs as $english => $localized ) {
			foreach ( $wp_locale->month as $full ) {
				if ( isset( $wp_locale->month_abbrev[ $full ] ) && $english === $this->month_abbrev_backup[ $full ] ) {
					$wp_locale->month_abbrev[ $full ] = $localized;
				}
			}
		}
	}

	/**
	 * Directory card output, exactly as readylaunch/members/members-loop.php produces it.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	protected function rl_directory_joined_date( $user_id ) {
		add_filter( 'bb_get_member_joined_date', 'BB_Readylaunch::bb_rl_modify_member_joined_date', 10, 3 );
		$output = bb_get_member_joined_date( $user_id );
		remove_filter( 'bb_get_member_joined_date', 'BB_Readylaunch::bb_rl_modify_member_joined_date', 10 );

		return $output;
	}

	/**
	 * The day was always "01" because only month + year reached the formatter.
	 */
	public function test_rl_directory_shows_registered_day_english() {
		$u = $this->create_user_registered_on( '2025-04-21 10:00:00' );

		$this->assertSame( 'Joined 21 Apr 2025', $this->rl_directory_joined_date( $u ) );
	}

	/**
	 * German "März" cannot be parsed by strtotime(); the card showed today's date.
	 */
	public function test_rl_directory_german_month_shows_registered_date() {
		$u = $this->create_user_registered_on( '2025-03-17 10:00:00' );
		$this->set_month_abbrevs( array( 'Mar' => 'März' ) );

		$this->assertSame( 'Joined 17 März 2025', $this->rl_directory_joined_date( $u ) );
	}

	/**
	 * Non-Latin month names never parse; every month showed today's date.
	 */
	public function test_rl_directory_russian_month_shows_registered_date() {
		$u = $this->create_user_registered_on( '2025-03-17 10:00:00' );
		$this->set_month_abbrevs( array( 'Mar' => 'Мар' ) );

		$this->assertSame( 'Joined 17 Мар 2025', $this->rl_directory_joined_date( $u ) );
	}

	/**
	 * The user ID is passed to listeners of the filter.
	 */
	public function test_filter_receives_user_id() {
		$u        = $this->create_user_registered_on( '2025-04-21 10:00:00' );
		$received = null;

		$listener = function ( $value, $register_date, $user_id ) use ( &$received ) {
			$received = $user_id;
			return $value;
		};

		add_filter( 'bb_get_member_joined_date', $listener, 10, 3 );
		bb_get_member_joined_date( $u );
		remove_filter( 'bb_get_member_joined_date', $listener, 10 );

		$this->assertSame( $u, $received );
	}

	/**
	 * Outside the ReadyLaunch directory the output is unchanged (month + year).
	 */
	public function test_default_output_unchanged() {
		$u = $this->create_user_registered_on( '2025-04-21 10:00:00' );

		$this->assertSame( 'Joined Apr 2025', bb_get_member_joined_date( $u ) );
	}

	/**
	 * Direct 2-argument calls keep the previous behaviour.
	 */
	public function test_rl_formatter_two_argument_call_keeps_previous_behaviour() {
		$this->assertSame(
			'Joined 01 Apr 2025',
			BB_Readylaunch::bb_rl_modify_member_joined_date( 'Joined Apr 2025', 'Apr 2025' )
		);
	}

	/**
	 * An unknown user ID falls back to the previous behaviour instead of erroring.
	 */
	public function test_rl_formatter_unknown_user_falls_back() {
		$this->assertSame(
			'Joined 01 Apr 2025',
			BB_Readylaunch::bb_rl_modify_member_joined_date( 'Joined Apr 2025', 'Apr 2025', PHP_INT_MAX )
		);
	}
}
