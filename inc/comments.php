<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit();

function wpss_comment_post_country( $comment_id ) {
	if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
		add_comment_meta( $comment_id, 'ipcountry', $_SERVER['HTTP_CF_IPCOUNTRY'] );
	}
}
add_action( 'comment_post', 'wpss_comment_post_country' );

function wpss_comment_columns( $columns ) {
	$columns['ipcountry'] = __( 'SuperSonic', 'wpss' );
	return $columns;
}
add_filter( 'manage_edit-comments_columns', 'wpss_comment_columns' );

function wpss_comment_column( $column, $comment_ID ) {
	if ( 'ipcountry' == $column ) {
		if ( $meta = get_comment_meta( $comment_ID, $column, true ) ) {
			$wpss_countries = wpss_get_countries();
			$comment = get_comment( $comment_ID );
			$ip = $comment->comment_author_IP;
			$ip_country = $meta;
			$settings = wpss_defaults( get_option( 'wpss_settings' ) );
			$color = '#008800';
			if ( $settings['security']['comment_protection'] == 'deny' ) {
				if ( in_array( $meta, $settings['security']['comment_countries'] ) ) {
					$color = '#880000';
				}
			}
			if ( $settings['security']['comment_protection'] == 'allow' ) {
				if ( ! in_array( $meta, $settings['security']['comment_countries'] ) ) {
					$color = '#880000';
				}
			}
			$cloudflare_action = get_comment_meta( $comment_ID, 'cloudflare_action', true );
			echo '<div style="color:' . $color . ';">' . $wpss_countries[$meta] . '<br/><img style="margin-top:5px;" src="' . plugins_url( '/supersonic/flags/' ) . strtolower( $meta ) . '.png"/><span id="spinner-' . $comment_ID . '" class="spinner"></span></div>';
			echo '<div class="row-actions"><strong>IP</strong>: <span class="delete">';
			echo '<a title="Ban IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\'' . $ip . '\',\'' . $ip_country . '\',\'ban\',\'' . wp_create_nonce( 'wpss_ip_nonce' ) . '\',' . $comment_ID . ');" class="delete">BAN</a></span>';
			echo '<span> | <a title="Challenge IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\'' . $ip . '\',\'' . $ip_country . '\',\'challenge\',\'' . wp_create_nonce( 'wpss_ip_nonce' ) . '\',' . $comment_ID . ');" class="delete">CH</a></span>';
			echo '<span> | <a title="White list IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\'' . $ip . '\',\'' . $ip_country . '\',\'wl\',\'' . wp_create_nonce( 'wpss_ip_nonce' ) . '\',' . $comment_ID . ');" class="delete">WL</a></span>';
			echo '<span> | <a title="Remove IP from CloudFlare lists" href="javascript:void(0);" onclick="wpss_ip_action(\'' . $ip . '\',\'' . $ip_country . '\',\'nul\',\'' . wp_create_nonce( 'wpss_ip_nonce' ) . '\',' . $comment_ID . ');" class="delete">NUL</a></span>';
			echo '<br/>';
			echo '</div>';
			echo '<div id="message-' . $comment_ID . '">' . $cloudflare_action . '</div>';
		}
	}
}
add_filter( 'manage_comments_custom_column', 'wpss_comment_column', 10, 2 );

function wpss_comments_head() {
	echo '<style>
    .column-ipcountry {
      width:170px;
    }
  </style>';
	?>
<script type="text/javascript">
  	function wpss_ip_action(ip,ip_country,mode,nonce,ip_id,scr) {
  		//console.log(1);
  		scr = scr || 'comment';
  		jQuery('#spinner-'+ip_id).css('display','inline');
      jQuery.ajax({
 	       type : "post",
   	     dataType : "json",
     	   url : "<?php echo admin_url('admin-ajax.php'); ?>",
       	 data : {action: 'wpss_ip', ip: ip, ip_country: ip_country, mode: mode, nonce: nonce, scr : scr, ip_id : ip_id },
         success: function(ret) {
         		//console.log(ret);
   	     		if (typeof ret == 'object') {
   	     			if (ret.msg != '') {
 	     				jQuery('#message-'+ip_id).html(ret.msg);
 	     			}
   	     			else if (ret.result != '') {
 	     				jQuery('#message-'+ip_id).html(ret.result);
 	     			}
   	     		}
   	     		else {
   	     			jQuery('#message-'+ip_id).html('Something wrong.');
   	     		}
       	 },
       	 error: function (request, status, error) {
       	 		//console.log(request);
       	 		jQuery('#message-'+ip_id).html(request.statusText);
       	 		if ( scr != 'comment' ) {
       	 			setTimeout(function(){jQuery('#message-'+ip_id).html('');},5000);
       	 		}
    		 },
         complete: function() {
 	       		jQuery('#spinner-'+ip_id).css('display','none');
 	       		if ( scr != 'comment' ) {
 	       			setTimeout(function(){jQuery('#message-'+ip_id).html('');},5000);
 	       		}
   	     }
     	})
     	return false;
  	}
  </script>
<?php
}
add_action( 'admin_head', 'wpss_comments_head' );

add_action( "wp_ajax_wpss_ip", "wpss_ip_ajax" );

function wpss_ip_ajax() {
	if ( ! wp_verify_nonce( $_REQUEST['nonce'], "wpss_ip_nonce" ) ) {
		exit( "No naughty business please." );
	}
	$ip = $_REQUEST['ip'];
	$ip_country = $_REQUEST['ip_country'];
	$mode = $_REQUEST['mode'];
	$scr = $_REQUEST['scr'];
	$ip_id = $_REQUEST['ip_id'];
	//
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$default_stats = $period;
	$stats = get_option( "wpss_stats_" . $default_stats );
	//
	$cf = new cloudflare_api();
	$all_zones = false;
	if ( isset( $settings['comment_manual_scope'] ) && $settings['comment_manual_scope'] == '1' ) {
		$all_zones = true;
	}
	if ( $mode == 'ban' ) {
		$ret = $cf->ban( $ip, 'WP Supersonic from comment', $all_zones );
		$event = ($scr == 'comment' ? 4 : 14);
		if ( $ret->result == 'error' ) {
			wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip, $ip_country );
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );		
			}
		} 
		else {
			wpss_log( $event, '', $ip, $ip_country );
			$ret->msg = 'Banned (manually)';
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'Benned (manually)' );		
			}
		}
	}
	if ( $mode == 'wl' ) {
		$ret = $cf->wl( $ip, 'WP Supersonic from comment', $all_zones );
		$event = ($scr == 'comment' ? 7 : 17);
		if ( $ret->result == 'error' ) {
			wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip, $ip_country );
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );		
			}
		} 
		else {
			wpss_log( $event, '', $ip, $ip_country );
			$ret->msg = 'Whitelisted (manually)';
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'Whitelisted (manually)' );		
			}
		}
	}
	if ( $mode == 'challenge' ) {
		$ret = $cf->challenge( $ip, 'WP Supersonic from comment', $all_zones );
		$event = ($scr == 'comment' ? 11 : 21);
		if ( $ret->result == 'error' ) {
			wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip, $ip_country );
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );
			}				
		} 
		else {
			wpss_log( $event, '', $ip, $ip_country );
			$ret->msg = 'Challenge (manually)';
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'Challenge (manually)' );		
			}
		}
	}
	if ( $mode == 'nul' ) {
		$ret = $cf->nul( $ip, $all_zones );		
		$event = ($scr == 'comment' ? 8 : 18);
		if ( $ret->result == 'error' ) {
			wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip, $ip_country );
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );
			}				
		} 
		else {
			wpss_log( $event, '', $ip, $ip_country );
			$ret->msg = 'Deleted rule (manually)';
			if ( $scr == 'comment' ) {
				update_comment_meta( $ip_id, 'cloudflare_action', 'Deleted rule (manually)' );		
			}
		}
	}
	echo json_encode( $ret );
	die();
}


add_action( 'wp_set_comment_status', 'wpss_wp_set_comment_status', 9999, 2 );
function wpss_wp_set_comment_status( $comment_id, $comment_status ) {	
	if ( $comment_status == 'spam' ) {
		$settings = wpss_defaults( get_option( 'wpss_settings' ) );
		$ip = get_comment_author_IP( $comment_id );
		if ( $settings['comment_spam'] != '0' && isset( $ip ) && $ip != '' ) {
			$all_zones = false;
			if ( isset( $settings['comment_manual_scope'] ) && $settings['comment_manual_scope'] == '1' ) {
				$all_zones = true;
			}
			$cf = new cloudflare_api();
			if ( $settings['comment_spam'] == '1' ) {
				$ret = $cf->challenge( $ip, 'WP Supersonic from comment spam', $all_zones );
				$event = 11;
				if ( $ret->result == 'error' ) {					
					wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip );
					update_comment_meta( $comment_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );
				}
				else {
					wpss_log( $event, '', $ip );
					$ret->msg = 'Challenge (manually)';
					update_comment_meta( $comment_id, 'cloudflare_action', 'Challenge (marked as spam)' );
				}
			}
			if ( $settings['comment_spam'] == '2' ) {
				$event = 4;
				$ret = $cf->ban( $ip, 'WP Supersonic from comment spam', $all_zones );
				if ( $ret->result == 'error' ) {
					wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip );
					update_comment_meta( $comment_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );
				}
				else {
					wpss_log( $event, '', $ip );
					$ret->msg = 'Challenge (manually)';
					update_comment_meta( $comment_id, 'cloudflare_action', 'Ban (marked as spam)' );
				}
			}
		}
	}
}

add_action( 'unspammed_comment', 'wcss_unspammed_comment', 9999 );
function wcss_unspammed_comment( $comment_id ) {
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$ip = get_comment_author_IP( $comment_id );
	if ( $settings['comment_not_spam'] == '1' && isset( $ip ) && $ip != '' ) {
		$all_zones = false;
		if ( isset( $settings['comment_manual_scope'] ) && $settings['comment_manual_scope'] == '1' ) {
			$all_zones = true;
		}
		$cf = new cloudflare_api();
		$ret = $cf->nul( $ip, $all_zones );
		$event = 8;
		if ( $ret->result == 'error' ) {
			wpss_log( $event, 'CloudFlare error: ' . $ret->msg, $ip, $ip_country );
			update_comment_meta( $comment_id, 'cloudflare_action', 'CloudFlare error: ' . $ret->msg );
		} 
		else {
			wpss_log( $event, '', $ip, $ip_country );
			update_comment_meta( $comment_id, 'cloudflare_action', 'Deleted rule (unspamed comment)' );		
		}
	}
}
