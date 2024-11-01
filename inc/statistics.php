<?php

function wpss_cf_statistics() {
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$default_stats = "40";
	$stats = get_option( "wpss_stats_" . $default_stats );
	if ( 1 == 2 && ($stats === false || $stats['time'] + 600 < current_time( 'timestamp' ) || $settings['update_time'] > $stats['time']) ) {
		$stats = array ();
		$stats['time'] = current_time( 'timestamp' );
		$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
		$stats['stats'] = $cf->stats( $settings['cloudflare_domain'], $default_stats );
		update_option( "wpss_stats_" . $default_stats, $stats, 3600 );
	}
	$nonce = wp_create_nonce( "wpss_stats_nonce" );
	include ('views/statistics.php');
}

add_action( "wp_ajax_wpss_stat", "wpss_stat_ajax" );

function wpss_stat_ajax() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], "wpss_stats_nonce" ) ) {
		exit( "No naughty business please." );
	}
	$period = $_REQUEST['period'];
	//
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$default_stats = $period;
	$stats = get_option( "wpss_stats", false );
	if ( $stats === false ) {
		add_option( 'wpss_stats', array (), '', 'no' );
	}
	$stats_period = array ();
	if ( isset( $stats[$period] ) ) {
		$stats_period = $stats[$period];
	}
	if ( $stats_period === false || ! isset( $stats_period['stats'] ) || (intval( $stats_period['time'] ) + 600) < current_time( 'timestamp' ) || $settings['update_time'] > $stats_period['time'] ) {
		$stats_period = array ();
		$stats_period['time'] = current_time( 'timestamp' );
		$cf = new cloudflare_api();
		$stats_period['stats'] = $cf->stats( $settings['cloudflare_zone'], $default_stats );
		$stats[$period] = $stats_period;
		update_option( 'wpss_stats', $stats );
	}
	echo json_encode( $stats_period );
	die();
}