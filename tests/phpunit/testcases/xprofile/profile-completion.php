<?php

/**
 * Profile Completion widget labels.
 *
 * The progress array is cached per user (bp_profile_completion_widgets) without a language,
 * so labels must be translated when the widget data is formatted, not when progress is built.
 *
 * @group xprofile
 * @group profile-completion
 */
class BP_Tests_XProfile_Profile_Completion extends BP_UnitTestCase {

	/**
	 * Label prefix applied by the fake translation filters.
	 *
	 * @var string
	 */
	protected $prefix = '';

	public function tearDown(): void {
		remove_filter( 'bp_get_the_profile_group_name', array( $this, 'translate_group_name' ) );
		remove_filter( 'gettext', array( $this, 'translate_text' ), 10 );
		parent::tearDown();
	}

	public function translate_group_name( $name ) {
		return $this->prefix . $name;
	}

	public function translate_text( $translation, $text, $domain ) {
		if ( 'buddyboss' === $domain && in_array( $text, array( 'Profile Photo', 'Cover Photo' ), true ) ) {
			return $this->prefix . $text;
		}

		return $translation;
	}

	/**
	 * The progress array (what gets cached) keeps the raw group name.
	 */
	public function test_user_progress_keeps_raw_group_name() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create( array( 'name' => 'Details' ) );
		self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $g,
				'type'           => 'textbox',
			)
		);
		self::set_current_user( $u );

		$this->prefix = 'FR ';
		add_filter( 'bp_get_the_profile_group_name', array( $this, 'translate_group_name' ) );

		$progress = bp_xprofile_get_user_progress( array( $g ), array() );

		$this->assertSame( 'Details', $progress['groups'][ $g ]['group_name'] );
	}

	/**
	 * Group labels are filtered at format time, in the language of the current request.
	 */
	public function test_formatted_group_label_follows_current_filter() {
		$progress = array(
			'groups'           => array(
				7 => array(
					'group_name'             => 'Details',
					'group_total_fields'     => 2,
					'group_completed_fields' => 1,
				),
			),
			'total_fields'     => 2,
			'completed_fields' => 1,
		);

		add_filter( 'bp_get_the_profile_group_name', array( $this, 'translate_group_name' ) );

		$this->prefix = 'FR ';
		$formatted    = bp_xprofile_get_user_progress_formatted( $progress );
		$this->assertSame( 'FR Details', $formatted['groups'][0]['label'] );

		// Same cached array, different request language.
		$this->prefix = 'IT ';
		$formatted    = bp_xprofile_get_user_progress_formatted( $progress );
		$this->assertSame( 'IT Details', $formatted['groups'][0]['label'] );
	}

	/**
	 * Photo labels ignore the name stored in the cache and use the current translation.
	 */
	public function test_formatted_photo_labels_ignore_cached_name() {
		$progress = array(
			'photo_type'       => array(
				'profile_photo' => array(
					'is_uploaded' => 0,
					'name'        => 'Photo de profil',
				),
				'cover_photo'   => array(
					'is_uploaded' => 1,
					'name'        => 'Photo de couverture',
				),
			),
			'total_fields'     => 2,
			'completed_fields' => 1,
		);

		$this->prefix = 'IT ';
		add_filter( 'gettext', array( $this, 'translate_text' ), 10, 3 );

		$formatted = bp_xprofile_get_user_progress_formatted( $progress );

		$this->assertSame( 'IT Profile Photo', $formatted['groups'][0]['label'] );
		$this->assertSame( 'IT Cover Photo', $formatted['groups'][1]['label'] );
	}
}
