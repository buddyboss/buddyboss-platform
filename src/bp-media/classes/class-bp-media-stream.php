<?php
/**
 * BuddyBoss Media Stream Classes
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.7.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Media Stream.
 *
 * Class BP_Media_Stream
 */
class BP_Media_Stream {

	/**
	 * Media file path.
	 *
	 * @var string
	 */
	private $path = '';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id = '';

	/**
	 * Stream Media.
	 *
	 * @var string
	 */
	private $stream = '';

	/**
	 * Buffer Media.
	 *
	 * @var int
	 */
	private $buffer = 102400;

	/**
	 * Initial start length.
	 *
	 * @var int
	 */
	private $start = - 1;

	/**
	 * Initial end length.
	 *
	 * @var int
	 */
	private $end = - 1;

	/**
	 * File size.
	 *
	 * @var int
	 */
	private $size = 0;

	/**
	 * BP_Media_Stream constructor.
	 *
	 * @param string $file_path     File path.
	 * @param int    $attachment_id Attachment ID.
	 */
	public function __construct( $file_path, $attachment_id ) {
		$this->path          = $file_path;
		$this->attachment_id = $attachment_id;
	}

	/**
	 * Open stream
	 */
	private function open() {
		// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, WordPress.CodeAnalysis.AssignmentInCondition.Found, WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! ( $this->stream = fopen( $this->path, 'rb' ) ) ) {
			die( 'Could not open stream for reading' );
		}
	}

	/**
	 * Set proper header to serve the media content.
	 */
	private function set_header() {
		ob_get_clean();
		$type = get_post_mime_type( $this->attachment_id );
		header( "Content-Type: $type" );
		header( 'Cache-Control: max-age=2592000, public' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 2592000 ) . ' GMT' );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', @filemtime( $this->path ) ) . ' GMT' );
		$this->start = 0;
		$this->size  = filesize( $this->path );
		$this->end   = $this->size - 1;
		header( 'Accept-Ranges: 0-' . $this->end );

		if ( isset( $_SERVER['HTTP_RANGE'] ) ) {

			$c_start = $this->start;
			$c_end   = $this->end;

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			list( , $range ) = explode( '=', $_SERVER['HTTP_RANGE'], 2 );
			if ( strpos( $range, ',' ) !== false ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $this->start-$this->end/$this->size" );
				exit;
			}
			if ( '-' === $range ) {
				$c_start = $this->size - substr( $range, 1 );
			} else {
				$range   = explode( '-', $range );
				$c_start = $range[0];

				$c_end = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? $range[1] : $c_end;
			}
			$c_end = ( $c_end > $this->end ) ? $this->end : $c_end;
			if ( $c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $this->start-$this->end/$this->size" );
				exit;
			}
			$this->start = $c_start;
			$this->end   = $c_end;
			$length      = $this->end - $this->start + 1;
			fseek( $this->stream, $this->start );
			header( 'HTTP/1.1 206 Partial Content' );
			header( 'Content-Length: ' . $length );
			header( "Content-Range: bytes $this->start-$this->end/" . $this->size );
		} else {
			header( 'Content-Length: ' . $this->size );
		}
	}

	/**
	 * Close currently opened stream.
	 */
	private function end() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $this->stream );
		exit;
	}

	/**
	 * Perform the streaming of calculated range.
	 */
	private function stream() {
		$i = $this->start;
		set_time_limit( 0 );
		while ( ! feof( $this->stream ) && $i <= $this->end ) {
			$bytes_to_read = $this->buffer;
			if ( ( $i + $bytes_to_read ) > $this->end ) {
				$bytes_to_read = $this->end - $i + 1;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			$data = fread( $this->stream, $bytes_to_read );
			echo $data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			flush();
			$i += $bytes_to_read;
		}
	}

	/**
	 * Start streaming media content
	 */
	public function start() {
		$this->open();
		$this->set_header();
		$this->stream();
		$this->end();
	}
}
