<?php
/**
 * Tests for the Settings 2.0 document/video extension list helpers and the
 * empty document list repair (PROD-10342).
 *
 * @package BuddyBoss\Tests\Document
 */

/**
 * Media extension list getter, backfill and repair tests.
 *
 * @group document
 * @group media_extensions
 */
class BB_Tests_Media_Extensions_Settings extends BP_UnitTestCase {

	/**
	 * Load the media settings callbacks (admin-only file).
	 */
	public function setUp(): void {
		parent::setUp();

		$callbacks = buddypress()->plugin_dir . 'bp-core/admin/settings/media/callbacks.php';
		if ( ! function_exists( 'bb_media_get_saved_extensions' ) && file_exists( $callbacks ) ) {
			require_once $callbacks;
		}

		if ( ! function_exists( 'bb_media_get_saved_extensions' ) ) {
			$this->markTestSkipped( 'Media settings callbacks are not loadable in this context.' );
		}
	}

	/**
	 * A default document list in the stored shape.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'bb_doc_1'  => array(
				'extension'   => '.pdf',
				'mime_type'   => 'application/pdf',
				'description' => 'PDF',
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
			'bb_doc_77' => array(
				'extension'   => '.mp4',
				'mime_type'   => 'video/mp4',
				'description' => 'MP4',
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
			'bb_doc_80' => array(
				'extension'   => '.mov',
				'mime_type'   => 'video/quicktime',
				'description' => 'MOV',
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
		);
	}

	/**
	 * A legacy list: .mp4 and .mov saved as custom entries under numeric keys.
	 *
	 * @return array
	 */
	protected function legacy_list() {
		$defaults = $this->defaults();

		return array(
			'bb_doc_1' => $defaults['bb_doc_1'],
			'1'        => array(
				'extension'   => '.MP4',
				'mime_type'   => 'video/mp4',
				'description' => 'custom mp4',
				'is_default'  => 0,
				'is_active'   => 1,
				'icon'        => '',
			),
			'2'        => array(
				'extension'   => '.mov',
				'mime_type'   => 'video/quicktime',
				'description' => 'custom mov',
				'is_default'  => 0,
				'is_active'   => 0,
				'icon'        => '',
			),
		);
	}

	/**
	 * Never saved: the defaults are returned.
	 */
	public function test_saved_extensions_never_saved_returns_defaults() {
		bp_delete_option( 'bp_document_extensions_support' );

		$this->assertSame( $this->defaults(), bb_media_get_saved_extensions( 'bp_document_extensions_support', $this->defaults() ) );
	}

	/**
	 * Saved empty: a deliberate "delete all" stays empty.
	 */
	public function test_saved_extensions_saved_empty_stays_empty() {
		bp_update_option( 'bp_document_extensions_support', array() );

		$this->assertSame( array(), bb_media_get_saved_extensions( 'bp_document_extensions_support', $this->defaults() ) );
	}

	/**
	 * The backfill skips a default whose extension is already saved under another key.
	 */
	public function test_backfill_skips_extensions_already_in_the_list() {
		bp_update_option( 'bp_document_extensions_support', $this->legacy_list() );

		$result = bb_media_get_saved_extensions( 'bp_document_extensions_support', $this->defaults() );

		$this->assertSame( $this->legacy_list(), $result, 'The legacy list must come back unchanged.' );
		$this->assertArrayNotHasKey( 'bb_doc_77', $result );
		$this->assertArrayNotHasKey( 'bb_doc_80', $result );

		$types = array_map( 'strtolower', wp_list_pluck( $result, 'extension' ) );
		$this->assertSame( count( $types ), count( array_unique( $types ) ), 'No extension may be listed twice.' );
	}

	/**
	 * The backfill still adds a default that is missing from the list.
	 */
	public function test_backfill_adds_missing_default() {
		$defaults = $this->defaults();
		bp_update_option( 'bp_document_extensions_support', array( 'bb_doc_1' => $defaults['bb_doc_1'] ) );

		$result = bb_media_get_saved_extensions( 'bp_document_extensions_support', $defaults );

		$this->assertSame( array( 'bb_doc_1', 'bb_doc_77', 'bb_doc_80' ), array_keys( $result ) );
	}

	/**
	 * A toggle-only save on a legacy list keeps the admin's states and adds no duplicates.
	 */
	public function test_toggle_save_on_legacy_list_writes_no_duplicates() {
		bp_update_option( 'bp_document_extensions_support', $this->legacy_list() );

		$saved = bb_media_sanitize_extensions(
			array(
				'bb_doc_1' => 0,
				'1'        => 1,
				'2'        => 1,
			),
			'bp_document_extensions_support'
		);

		$this->assertSame( array( 'bb_doc_1', '1', '2' ), array_map( 'strval', array_keys( $saved ) ) );
		$this->assertSame( 0, $saved['bb_doc_1']['is_active'] );
		$this->assertSame( 1, $saved['2']['is_active'] );
	}

	/**
	 * An empty payload is still the "delete all" save.
	 */
	public function test_empty_payload_saves_empty_list() {
		$this->assertSame( array(), bb_media_sanitize_extensions( array(), 'bp_document_extensions_support' ) );
	}

	/**
	 * Repair, Add-ons defaults not loaded: the empty row is deleted so the Add-ons seed fills it later.
	 */
	public function test_repair_deletes_empty_row_when_defaults_unavailable() {
		if ( function_exists( 'bp_media_allowed_document_type' ) ) {
			$this->markTestSkipped( 'The default document list is loaded in this run.' );
		}

		bp_update_option( 'bp_document_extensions_support', array() );

		bb_repair_empty_document_extensions_on_upgrade();

		$this->assertFalse( bp_get_option( 'bp_document_extensions_support', false ) );
	}

	/**
	 * Repair leaves a non-empty list, a missing row and the video list alone.
	 */
	public function test_repair_leaves_other_values_alone() {
		bp_update_option( 'bp_document_extensions_support', $this->legacy_list() );
		bp_update_option( 'bp_video_extensions_support', array() );

		bb_repair_empty_document_extensions_on_upgrade();

		$this->assertSame( $this->legacy_list(), bp_get_option( 'bp_document_extensions_support' ) );
		$this->assertSame( array(), bp_get_option( 'bp_video_extensions_support' ) );

		bp_delete_option( 'bp_document_extensions_support' );
		bb_repair_empty_document_extensions_on_upgrade();
		$this->assertFalse( bp_get_option( 'bp_document_extensions_support', false ) );
	}

	/**
	 * Repair, defaults loaded: the empty list is replaced with the defaults.
	 *
	 * Runs last: it declares a stand-in for the Add-ons default list function.
	 */
	public function test_repair_saves_defaults_when_available() {
		if ( ! function_exists( 'bp_media_allowed_document_type' ) ) {
			/**
			 * Stand-in for the BuddyBoss Add-ons default document list.
			 *
			 * @return array
			 */
			function bp_media_allowed_document_type() {
				return array(
					'bb_doc_1' => array(
						'extension'   => '.pdf',
						'mime_type'   => 'application/pdf',
						'description' => 'PDF',
						'is_default'  => 1,
						'is_active'   => 1,
						'icon'        => '',
					),
				);
			}
		}

		bp_update_option( 'bp_document_extensions_support', array() );

		bb_repair_empty_document_extensions_on_upgrade();

		$this->assertSame( bp_media_allowed_document_type(), bp_get_option( 'bp_document_extensions_support' ) );

		// A later deliberate "delete all" stays empty in the getter.
		bp_update_option( 'bp_document_extensions_support', array() );
		$this->assertSame( array(), bb_media_get_extension_data( 'bp_document_extensions_support' ) );

		// Never saved: the getter falls back to the defaults.
		bp_delete_option( 'bp_document_extensions_support' );
		$this->assertSame( array( 'bb_doc_1' ), array_keys( bb_media_get_extension_data( 'bp_document_extensions_support' ) ) );
	}
}
