<?php
/**
 * BuddyBoss Media Classes
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BP_Media_Rotation' ) ) {
	/**
	 * Fixes media rotation from mobile uploads
	 *
	 * @since BuddyPress 1.1.1
	 */
	class BP_Media_Rotation {

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss 1.1.1
		 */
		function __construct() {
			add_action( 'bp_init', array( $this, 'init' ) );
		}

		function init() {
			add_action( 'bp_media_attachment_uploaded', array( $this, 'attachment_uploaded' ) );
		}

		/**
		 * Attachment uploaded and fix rotation
		 *
		 * @param $id
		 *
		 * @since BuddyBoss 1.1.1
		 */
		function attachment_uploaded( $id ) {
			global $bp_media_rotation_fix_id;
			$bp_media_rotation_fix_id = $id;

			$attachment = get_post( $id );
			$path       = get_attached_file( $id );

			if ( in_array( $attachment->post_mime_type, array( 'image/jpeg', 'image/png', 'image/gif' ) ) && ! empty( $path ) && file_exists( $path ) ) {
				// Add a fallback on shutdown in the case that memory runs out
				add_action( 'shutdown', array( $this, 'rotation_shutdown_fallback' ) );

				$status = $this->fix_rotation( $path );

				if ( ! empty( $status ) ) {
					$attachment_meta = wp_generate_attachment_metadata( $id, $path );
					wp_update_attachment_metadata( $id, $attachment_meta );
				}
			}
		}

		/**
		 * Attempt to capture a failed image rotation due to memory exhaustion
		 *
		 * @since BuddyBoss 1.1.1
		 */
		function rotation_shutdown_fallback() {
			global $bp_media_rotation_fix_id;

			$error = error_get_last();

			// Make sure an error was thrown from this file
			if ( empty( $error ) || empty( $error['file'] ) || (int) $error['type'] !== 1
				 || $error['file'] !== __FILE__ ) {
				return;
			}

			@header( 'HTTP/1.1 200 OK' );

			$aid        = $bp_media_rotation_fix_id;
			$attachment = get_post( $aid );
			$name       = $url = null;

			if ( $attachment !== null ) {
				$name    = $attachment->post_title;
				$url_nfo = wp_get_attachment_image_src( $aid );
				$url     = is_array( $url_nfo ) && ! empty( $url_nfo ) ? $url_nfo[0] : null;
			}

			$result = array(
				'status'        => ( $attachment !== null ),
				'attachment_id' => (int) $aid,
				'url'           => esc_url( $url ),
				'name'          => esc_attr( $name ),
			);

			echo htmlspecialchars( json_encode( $result ), ENT_NOQUOTES );
			exit( 0 );
		}

		/**
		 * Fix rotation of mobile uploaded images
		 *
		 * @since BuddyBoss 1.1.1
		 * @param $source
		 *
		 * @return bool
		 */
		function fix_rotation( $source ) {
			if ( ! file_exists( $source ) ) {
				return false;
			}

			$exif = null;
			$ort  = 0;

			if ( function_exists( 'exif_read_data' ) ) {
				$exif = @exif_read_data( $source );
				$ort  = isset( $exif['Orientation'] ) ? $exif['Orientation'] : 0;
			}

			if ( $ort > 1 ) {
				$destination = $source;
				$size        = @getimagesize( $source ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

				$width  = $size[0];
				$height = $size[1];

				$sourceImage      = imagecreatefromjpeg( $source );
				$destinationImage = imagecreatetruecolor( $width, $height );
				// Specifies the color of the uncovered zone after the rotation
				$bgd_color = imagecolorallocatealpha( $destinationImage, 0, 0, 0, 127 );

				imagecopyresampled( $destinationImage, $sourceImage, 0, 0, 0, 0, $width, $height, $width, $height );

				switch ( $ort ) {
					case 2:
						$this->flip_image( $dimg );
						break;
					case 3:
						$destinationImage = imagerotate( $destinationImage, 180, $bgd_color );
						break;
					case 4:
						$this->flip_image( $dimg );
						break;
					case 5:
						$this->flip_image( $destinationImage );
						$destinationImage = imagerotate( $destinationImage, - 90, $bgd_color );
						break;
					case 6:
						$destinationImage = imagerotate( $destinationImage, - 90, $bgd_color );
						break;
					case 7:
						$this->flip_image( $destinationImage );
						$destinationImage = imagerotate( $destinationImage, - 90, $bgd_color );
						break;
					case 8:
						$destinationImage = imagerotate( $destinationImage, 90, $bgd_color );
						break;
				}

				return imagejpeg( $destinationImage, $destination, 100 );
			}
		}

		/**
		 * Flips the image
		 *
		 * @param $image
		 *
		 * @return bool
		 */
		function flip_image( &$image ) {
			$x      = 0;
			$y      = 0;
			$height = null;
			$width  = null;

			if ( $width < 1 ) {
				$width = imagesx( $image );
			}

			if ( $height < 1 ) {
				$height = imagesy( $image );
			}

			if ( function_exists( 'imageistruecolor' ) && imageistruecolor( $image ) ) {
				$tmp = imagecreatetruecolor( 1, $height );
			} else {
				$tmp = imagecreate( 1, $height );
			}

			$x2 = $x + $width - 1;

			for ( $i = (int) floor( ( $width - 1 ) / 2 ); $i >= 0; $i -- ) {
				imagecopy( $tmp, $image, 0, 0, $x2 - $i, $y, 1, $height );
				imagecopy( $image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height );
				imagecopy( $image, $tmp, $x + $i, $y, 0, 0, 1, $height );
			}

			imagedestroy( $tmp );

			return true;
		}
	}
}
