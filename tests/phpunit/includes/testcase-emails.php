<?php
require_once dirname( __FILE__ ) . '/testcase.php';

class BP_UnitTestCase_Emails extends BP_UnitTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once( bboss()->plugin_dir . '/bboss-core/admin/bboss-core-admin-schema.php' );

		/*
		 * WP's test suite wipes out BP's email posts.
		 * We must reestablish them before our tests can be successfully run.
		 */
		bboss_core_install_emails();
	}

	public static function tearDownAfterClass(): void {
		$emails = get_posts( array(
			'fields'                 => 'ids',
			'post_status'            => 'any',
			'post_type'              => bboss_get_email_post_type(),
			'posts_per_page'         => 200,
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		) );

		if ( $emails ) {
			foreach ( $emails as $email_id ) {
				wp_delete_post( $email_id, true );
			}
		}

		parent::tearDownAfterClass();
	}
}
