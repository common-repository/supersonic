<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

function wpss_clear_urls( $cf, $settings, $urls, $urls_rows, $url ) {
	global $wpdb;
	$ret = $cf->zone_file_purge( $settings['cloudflare_zone'], $urls );	
	if ( ! isset( $ret->success ) || $ret->success != '1' ) {
		$schedule = wp_next_scheduled( 'wpss_clear' );
		if ( $schedule > time() + 65 ) {
			wp_schedule_single_event( time() + 65, 'wpss_clear' );
		}
		if ( isset( $ret->message ) ) {
			wpss_log( 20, 'Purge failed: ' . $ret->message . ' for &quot;' . $url . '&quot;' );
		}
		else if ( $ret->msg ) {
			wpss_log( 20, 'Purge failed: ' . $ret->msg . ' for &quot;' . $url . '&quot;' );
		}
		else {
			wpss_log( 20, 'Purge failed: unknown error for &quot;' . $url . '&quot;' );
		}
		return false;
	}
	else {
		foreach ( $urls_rows as $url_row ) {
			$wpdb->delete( $wpdb->prefix . 'wpss_clear', array (
					'url' => $url_row
			) );
		}
	}
	return true;
}
	
function wpss_clear_f( $max_count = 3000 ) {
	if ( empty( $max_count ) ) {
		$max_count = 3000;
	}
	global $wpdb;
	$count_row = 0;
	$settings = wpss_defaults( get_option( 'wpss_settings', array() ) );
	$cf = new cloudflare_api();
	$sql = 'select url from ' . $wpdb->prefix . 'wpss_clear order by priority';
	$rows = $wpdb->get_results( $sql );
	$urls = array();
	$urls_rows = array();
	foreach ( $rows as $row ) {
		if ( $count_row > $max_count ) {
			$schedule = wp_next_scheduled( 'wpss_clear' );
			if ( $schedule > time() + 65 ) {
				wp_schedule_single_event( time() + 60, 'wpss_clear' );
			}
			break;
		}
		$count_row++;
		$url = trim( $row->url );
		if ( strpos( $url, '/' ) === 0 ) {
			$url = site_url() . $url;
		}
		if ( trim( $url ) == '' ) {
			$url = trailingslashit( site_url() );
		}
		$urls[] = $url;
		$urls_rows[] = $row->url;
		if ( sizeof( $urls ) >= 29 ) {
			$ret_clear = wpss_clear_urls( $cf, $settings, $urls, $urls_rows, $url );
			unset( $urls );
			unset( $urls_rows );
			$urls = array();
			$urls_rows = array();
			if ( $ret_clear === false ) {
				break;
			}
		}		
	}
	if ( sizeof( $urls ) > 0 ) {
		wpss_clear_urls( $cf, $settings, $urls, $urls_rows, $url );
		unset( $urls );
		unset( $urls_rows );
		$urls = array();
		$urls_rows = array();			
	}		
	if ( ! wp_next_scheduled( 'wpss_clear' ) ) {
		wp_schedule_event( time() + 65, 'hourly', 'wpss_clear' );
	}
	if ( ! wp_next_scheduled( 'wpss_log_clear' ) ) {
		wp_schedule_event( time(), 'hourly', 'wpss_log_clear' );
	}
}
add_action( 'wpss_clear', 'wpss_clear_f' );

