<?php
/**
 * TEMPORARY PROBE - not for commit.
 *
 * @group core
 * @group zzslash
 */
class BB_Probe_Slash extends BP_UnitTestCase {

	/** Write $row so that storage holds it VERBATIM (one wp_slash, undone by update_metadata). */
	private function store( $user_id, $key, $row ) {
		bp_update_user_meta( $user_id, $key, wp_slash( $row ) );
	}

	private function check( $label, $user_id, $key, $inner, $expected_json ) {
		$row  = bp_get_user_meta( $user_id, $key, true );
		$json = isset( $row[ $inner ]['data']['bbp_media'] ) ? $row[ $inner ]['data']['bbp_media'] : '(gone)';
		$dec  = json_decode( $json, true );
		$name = is_array( $dec ) && isset( $dec[0]['name'] ) ? $dec[0]['name'] : '-';
		printf(
			"  %-26s %-46s parse=%-7s name=%-18s %s\n",
			$label, $json, ( null === $dec ? 'FAILED' : 'ok' ), $name,
			( $json === $expected_json ? 'VERBATIM' : '*** MUTATED ***' )
		);
		return $json;
	}

	public function test_probe_dispose_strips_a_slash_layer_from_surviving_siblings() {
		$u   = $this->factory->user->create();
		$att = $this->factory->post->create( array( 'post_type' => 'attachment', 'post_author' => $u ) );

		// The exact string the FIXED handler produces and stores.
		$json = wp_json_encode( array( array( 'id' => $att, 'name' => 'résumé.pdf' ) ) );
		echo "\nhandler-produced JSON (what storage must hold): $json\n\n";

		$row = array(
			'doomed'   => array( 'data_key' => 'doomed',   '_draft_saved_at' => 1000, 'data' => array( 'bbp_reply_content' => 'a' ) ),
			'survivor' => array( 'data_key' => 'survivor', '_draft_saved_at' => 2000, 'data' => array( 'bbp_media' => $json ) ),
		);

		$this->store( $u, 'bb_user_topic_reply_draft', $row );
		echo "[1] straight after a correct write\n";
		$this->check( 'survivor', $u, 'bb_user_topic_reply_draft', 'survivor', $json );

		// bb_draft_dispose() single-inner-key branch = member discard,
		// budget eviction, and the nightly expiry sweep.
		bb_draft_dispose( $u, 'bb_user_topic_reply_draft', 'doomed' );
		echo "[2] after bb_draft_dispose( ..., 'doomed' )  <-- survivor was NOT the target\n";
		$this->check( 'survivor', $u, 'bb_user_topic_reply_draft', 'survivor', $json );

		// Repeat: each dispose of any other inner key strips another layer.
		foreach ( array( 'x1', 'x2' ) as $n => $tmp ) {
			$cur = bp_get_user_meta( $u, 'bb_user_topic_reply_draft', true );
			$cur[ $tmp ] = array( 'data_key' => $tmp, '_draft_saved_at' => 1, 'data' => array( 'bbp_reply_content' => 'z' ) );
			// Written the way the FIXED publish handlers do it.
			bp_update_user_meta( $u, 'bb_user_topic_reply_draft', wp_slash( $cur ) );
			bb_draft_dispose( $u, 'bb_user_topic_reply_draft', $tmp );
			echo '[' . ( $n + 3 ) . "] after another unrelated dispose\n";
			$this->check( 'survivor', $u, 'bb_user_topic_reply_draft', 'survivor', $json );
		}

		$this->assertTrue( true );
	}

	public function test_probe_salvage_write_path() {
		$u   = $this->factory->user->create();
		$att = $this->factory->post->create( array( 'post_type' => 'attachment', 'post_author' => $u ) );

		// Activity-shape draft: video[] is a real ARRAY, so no JSON layer -
		// but plain member text with a backslash is still exposed.
		$draft = array(
			'data_key' => 'draft_user',
			'data'     => array(
				'content' => 'path C:\\temp\\notes and a quote \\" here',
				'video'   => array( array( 'id' => $att, 'js_preview' => str_repeat( 'A', 150000 ) ) ),
			),
		);
		bp_update_user_meta( $u, 'draft_user', wp_slash( $draft ) );

		$before = bp_get_user_meta( $u, 'draft_user', true );
		$ok     = bb_draft_salvage_oversized_draft( $u, 'draft_user' );
		$after  = bp_get_user_meta( $u, 'draft_user', true );

		printf(
			"\n[salvage] salvaged=%s\n  content before: %s\n  content after : %s\n  %s\n",
			$ok ? 'yes' : 'no',
			$before['data']['content'],
			$after['data']['content'],
			( $before['data']['content'] === $after['data']['content'] ? 'INTACT' : '*** MUTATED ***' )
		);

		$this->assertTrue( true );
	}
}
