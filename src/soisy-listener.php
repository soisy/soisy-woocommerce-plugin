<?php
	
	if (!isset( $_REQUEST['action']))
		die('-1');
	
	
	require_once( '../../../wp-load.php' );
	ini_set('html_errors', 0);

//Typical headers
	header('Content-Type: application/json; charset=utf-8');
	//send_nosniff_header();

//Disable caching
	//header('Cache-Control: no-cache');
	header('Cache-Control: public');
	//header('Pragma: no-cache');
	//header_remove( 'Pragma' );
	
	
	
	$action = esc_attr(trim($_REQUEST['action']));
	
	$allowed_actions = [
		'order_status'
	];
	
	if ( in_array( $action, $allowed_actions ) ) {
		do_action( "soisy_ajax_{$action}" );
	}
