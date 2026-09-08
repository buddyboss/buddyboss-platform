<?php
/**
 * TEMPORARY PROBE - not for commit.
 *
 * @group core
 * @group zzact
 */
class BB_Probe_Act extends BP_UnitTestCase {

	public function test_probe_activity_handler_slash_state_per_transport() {
		$typed = 'path C:\\temp\\notes';   // what the member actually types
		echo "\nmember typed: $typed\n\n";

		// ---- transport A: in-page XHR. jQuery serializes the object, PHP
		// rebuilds a nested array, WordPress slashes the leaves.
		$xhr = wp_slash( array( 'data_key' => 'draft_user', 'data' => array( 'content' => $typed ) ) );
		$is_array_branch = is_array( $xhr );           // handler: skips json_decode
		$stored_xhr = wp_unslash( $xhr );              // update_metadata()
		printf(
			"transport A (XHR, nested array)\n  is_array => json_decode SKIPPED: %s\n  stored content: %s   %s\n\n",
			$is_array_branch ? 'yes' : 'no',
			$stored_xhr['data']['content'],
			( $stored_xhr['data']['content'] === $typed ? 'INTACT' : '*** MUTATED ***' )
		);

		// ---- transport B: unload sendBeacon. One JSON.stringify string.
        $json      = wp_json_encode( array( 'data_key' => 'draft_user', 'data' => array( 'content' => $typed ) ) );
		$posted    = wp_slash( $json );                       // WordPress slashes $_POST
		$decoded   = json_decode( stripslashes( $posted ), true );  // the handler's line
		$stored_bc = wp_unslash( $decoded );                  // update_metadata()
		printf(
			"transport B (beacon, JSON string)\n  JSON.stringify : %s\n  after handler json_decode(stripslashes()): %s\n  stored content : %s   %s\n",
			$json,
			$decoded['data']['content'],
			$stored_bc['data']['content'],
			( $stored_bc['data']['content'] === $typed ? 'INTACT' : '*** MUTATED ***' )
		);

		$this->assertTrue( true );
	}
}
