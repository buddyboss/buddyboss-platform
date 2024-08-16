<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );}

class BB_Core_Exception extends Exception { }

class BB_Log_Exception extends BB_Core_Exception {
	public function __construct( $message, $code = 0, Exception $previous = null ) {
		$classname = get_class( $this );
		BB_Core_Utils::error_log( "{$classname}: {$message}" );
		parent::__construct( $message, $code, $previous );
	}
}

class BB_Create_Exception extends BB_Core_Exception { }
