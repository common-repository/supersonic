<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit();


function wpss_head() {
    ?>
    <script type="text/javascript">
        function wpss_removeParam(key, sourceURL) {
            var rtn = sourceURL.split("?")[0],
                param,
                params_arr = [],
                queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
            if (queryString !== "") {
                params_arr = queryString.split("&");
                for (var i = params_arr.length - 1; i >= 0; i -= 1) {
                    param = params_arr[i].split("=")[0];
                    if (param === key) {
                        params_arr.splice(i, 1);
                    }
                }
                rtn = rtn + "?" + params_arr.join("&");
            }
            return rtn;
        }
        function wpss_trimChar(string, charToRemove) {
            while(string.charAt(0)==charToRemove) {
                string = string.substring(1);
            }

            while(string.charAt(string.length-1)==charToRemove) {
                string = string.substring(0,string.length-1);
            }

            return string;
        }
        if ( typeof window.history.pushState == 'function' ) {
            var url = document.location.href;
            url = wpss_removeParam('supersonic', url);
            url = wpss_trimChar(url,'?');
            window.history.pushState({}, "", url);
        }
    </script>
    <?php
}

function wpss_init() {
	if ( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) && isset( $_SERVER["REMOTE_ADDR"] ) && $_SERVER["HTTP_CF_CONNECTING_IP"] != $_SERVER["REMOTE_ADDR"] ) {
		$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
		$_SERVER["REMOTE_HOST"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}
	if ( ! headers_sent() ) {
		$settings = wpss_defaults( get_option( 'wpss_settings' ) );
		if ( isset( $settings['http_header'] ) && $settings['http_header'] == '1' ) {
			header( 'X-WPSS-Powered-By: WP SuperSonic' );
		}
	}
    $settings = wpss_defaults( get_option( 'wpss_settings' ) );
    if ( $settings['wp_head_do_not_logout'] == '1' ) {
        add_action( 'wp_head', 'wpss_disable_supersonic_url', 1);
        add_action( 'wp_head', 'wpss_enable_supersonic_url', 2147483647);
    }
	if ( $settings['content_do_not_logout'] == '1' ) {
	    add_filter( 'the_content', 'wpss_content_disable_supersonic_url', 1 );
        add_filter( 'the_content', 'wpss_content_enable_supersonic_url', 2147483647 );
    }
    if ( $settings['remove_do_not_logout'] == '1' ) {
        add_action('wp_head', 'wpss_head', 1);
    }
}
add_action( 'init', 'wpss_init', 1 );

if ( ! function_exists( 'apache_request_headers' ) ) {
	//
	function apache_request_headers() {
		$arh = array ();
		$rx_http = '/\AHTTP_/';
		foreach ( $_SERVER as $key => $val ) {
			if ( preg_match( $rx_http, $key ) ) {
				$arh_key = preg_replace( $rx_http, '', $key );
				$rx_matches = array ();
				// do some nasty string manipulations to restore the original letter case
				// this should work in most cases
				$rx_matches = explode( '_', $arh_key );
				if ( count( $rx_matches ) > 0 and strlen( $arh_key ) > 2 ) {
					foreach ( $rx_matches as $ak_key => $ak_val )
						$rx_matches[$ak_key] = ucfirst( $ak_val );
					$arh_key = implode( '-', $rx_matches );
				}
				$arh[$arh_key] = $val;
			}
		}
		return $arh;
	}
}

$wpss_bypass = false;

function wpss_footer() {
	global $wpss_bypass;
	if ( $wpss_bypass ) {
		return;
	}
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	if ( isset( $settings['cloudflare_api_key'] ) && isset( $settings['cloudflare_domain'] ) && isset( $_SERVER['HTTP_CF_RAY'] ) ) {
		if ( isset( $_GET['preview'] ) && $_GET['preview'] == 'true' ) {
			return;
		}
		if ( isset( $_REQUEST['supersonic'] ) && $_REQUEST['supersonic'] == untrailingslashit( substr( admin_url(), strlen( trailingslashit( site_url() ) ) ) ) ) {
			return;
		}
		$donotlogout_s = $settings['donotlogout'];
		$donotlogout = explode( "\n", $donotlogout_s );
		foreach ( $donotlogout as $url ) {
			$url = trim( $url );
			if ( fnmatch( $url, $_SERVER["REQUEST_URI"] ) ) {
				return;
			}
		}
		if ( isset($_SERVER['HTTP_HOST'] ) && isset( $settings['cloudflare_domain'] ) && $settings['cloudflare_domain'] != '' && strpos( $_SERVER['HTTP_HOST'], $settings['cloudflare_domain'] ) === false ) {
			return;
		}
		//
		global $wpdb, $wp_query;
		$type = 'other';
		$type2 = 'other';
		$id = 0;
		$url = $_SERVER["REQUEST_URI"];
		$proto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
		$host = $_SERVER['HTTP_HOST'];
		$url2 = $proto . '://' . $host;
		if ( $url2 == site_url() ) {
			$url2 = $_SERVER["REQUEST_URI"];
		}
		else {
			$url2 .= $_SERVER["REQUEST_URI"];
		}
		$url = $url2;
		$table_name = $wpdb->prefix . 'wpss_links';
		$sql = "select * from " . $table_name . " where url = '$url'";
		$row = null;
		if ( $row == null ) {
			if ( $type == 'other' && (is_front_page() || is_home()) ) {
				$type = 'home';
				$type2 = 'home';
			}
			if ( $type == 'other' && is_singular() ) {
				$type = 'singular';
				$type2 = 'singular';				
				$queried_object = $wp_query->get_queried_object();
				if ( $queried_object != null ) {
					$id = $queried_object->ID;
					$type2 = $queried_object->post_type;
				}
				else {
					$type = 'other';
					$type2 = 'other';						
				}
			}
			if ( $type == 'other' && is_category() ) {
				$type = 'tax';
				$type2 = 'category';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
			}
			if ( $type == 'other' && is_tag() ) {
				$type = 'tax';
				$type2 = 'tag';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
			}
			if ( $type == 'other' && is_tax() ) {
				$type = 'tax';
				$queried_object = $wp_query->get_queried_object();
				$id = $queried_object->term_id;
				//$id = $wp_query->queried_object_id;
				$type2 = $queried_object->taxonomy;
			}
			if ( $type == 'other' && is_feed() ) {
				$type = 'feed';
			}
			if ( $type == 'other' && is_date() ) {
				$type = 'date';
				if ( is_day() ) {
					$type2 = 'day';
					$id = get_the_time( 'Ymd' );
				}
				else if ( is_month() ) {
					$type2 = 'month';
					$id = get_the_time( 'Ym' );
				}
				else if ( is_year() ) {
					$type2 = 'year';
					$id = get_the_time( 'Y' );
				}
			}
			if ( $type == 'other' && is_author() ) {
				$type = 'author';
				$queried_object = $wp_query->get_queried_object();
				$id = $wp_query->queried_object_id;
			}
			if ( $type == 'other' && is_search() ) {
				$type = 'search';
			}
			if ( $type == 'other' && is_404() ) {
				$type = '404';
			}
			//
			$wpdb->replace( $table_name, array (
					'url' => $url,
					'type' => $type,
					'type2' => $type2,
					'id' => $id 
			) );
		}
	}
	$wpss_bypass = true;
}
add_action( 'wp_footer', 'wpss_footer' );
add_action( 'template_redirect', 'wpss_footer', 1 );

function wpss_save_post( $post_id, $post = false, $update = true ) {
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( $post === false ) {
		$post = get_post( $post_id );
	}
	//
	wpss_update( $post );
}
add_action( 'save_post', 'wpss_save_post', 10, 3 );
add_action( 'wpss_update_post', 'wpss_save_post', 10, 3 );

$wpss_process_comment = true;

function wpss_save_comment( $comment_id, $comment_approved = 'delete' ) {
	global $wpss_process_comment;
	if ( $wpss_process_comment === true ) {
		$comment = get_comment( $comment_id );
		if ( $comment->comment_approved == 1 ) {
			$post = get_post( $comment->comment_post_ID );
			if ( $post ) {
				wpss_update( $post, 1 );
			}
		}
	}
}
add_action( 'edit_comment', 'wpss_save_comment', 10, 2 );

function wpss_transition_comment_status( $new_status, $old_status, $comment ) {
	global $wpss_process_comment;
	if ( $old_status == 'approved' || $new_status == 'approved' ) {
		$post = get_post( $comment->comment_post_ID );
		if ( $post ) {
			$wpss_process_comment = false;
			wpss_update( $post, 1 );
		}
	}
}
add_action( 'transition_comment_status', 'wpss_transition_comment_status', 10, 3 );

function wpss_comment_post( $comment_id, $comment_approved ) {
	if ( 1 === $comment_approved ) {
		$comment = get_comment( $comment_id );
		$post = get_post( $comment->comment_post_ID );
		wpss_update( $post, 1 );
	}
}
add_action( 'comment_post', 'wpss_comment_post', 10, 2 );

function wpss_determine_current_user( $user_ID ) {
	$logout = false;
	if ( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ) {
		$logout = true;
	}
	if ( ! is_admin() && ! $logout ) {
		$settings = wpss_defaults( get_option( 'wpss_settings', array() ) );		
		$donotlogout_s = $settings['donotlogout'];
		$donotlogout = explode( "\n", $donotlogout_s );
		foreach ( $donotlogout as $url ) {
			$url = trim( $url );
			if ( fnmatch( $url, $_SERVER["REQUEST_URI"] ) ) {
				return $user_ID;
			}
		}
		if ( isset( $_GET['preview'] ) && $_GET['preview'] == 'true' ) {
			return $user_ID;
		}
		if ( isset( $_REQUEST['supersonic'] ) && untrailingslashit( $_REQUEST['supersonic'] ) == untrailingslashit( substr( admin_url(), strlen( trailingslashit( site_url() ) ) ) ) ) {
			return $user_ID;
		}
		return false;
	}
	else {
		return $user_ID;
	}
}
add_filter( 'determine_current_user', 'wpss_determine_current_user', 1000 );

$disable_supersonic_url = false;

function wpss_disable_supersonic_url() {
    global $disable_supersonic_url;
    $disable_supersonic_url = true;
}
add_action( 'wpss_disable_supersonic_url', 'wpss_disable_supersonic_url' );

function wpss_enable_supersonic_url() {
    global $disable_supersonic_url;
    $disable_supersonic_url = false;
}
add_action( 'wpss_enable_supersonic_url', 'wpss_enable_supersonic_url' );


function wpss_disable_supersonic_url_shortcode( $atts ) {
    wpss_disable_supersonic_url();
    return '';
}
add_shortcode( 'disable_supersonic_url', 'wpss_disable_supersonic_url_shortcode' );

function wpss_enable_supersonic_url_shortcode( $atts ) {
    wpss_enable_supersonic_url();
    return '';
}
add_shortcode( 'enable_supersonic_url', 'wpss_enable_supersonic_url_shortcode' );


function wpss_content_disable_supersonic_url( $content ) {
    wpss_disable_supersonic_url();
    return $content;
}

function wpss_content_enable_supersonic_url( $content ) {
    wpss_enable_supersonic_url();
    return $content;
}

$in_home_url = false;
$add_supersonic_site_home = - 1;

function wpss_home_url( $url, $path, $orig_scheme, $blog_id ) {
    if ( is_customize_preview() ) {
        //return $url;
    }
    global $disable_supersonic_url;
    if ( $disable_supersonic_url ) {
        return $url;
    }
	if ( is_admin() ) {
		return $url;
	}
	global $in_home_url;
	if ( $in_home_url ) {
		return $url;
	}
	$in_home_url = true;
	global $add_supersonic_site_home;
	if ( $add_supersonic_site_home == - 1 ) {
		$settings = wpss_defaults( get_option( 'wpss_settings' ) );
		if ( isset( $settings['donotlogout_roles'] ) && is_array( $settings['donotlogout_roles'] ) && count( $settings['donotlogout_roles'] ) ) {
			if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
				$add_supersonic_site_home = 0;
				global $current_user;
				$user_roles = $current_user->roles;
				foreach ( $user_roles as $role ) {
					if ( isset( $settings['donotlogout_roles'][$role] ) && $settings['donotlogout_roles'][$role] == '1' ) {
						$add_supersonic_site_home = 1;
						break;
					}
				}
			}
		}
	}
	if ( $add_supersonic_site_home == 1 ) {
		$url = add_query_arg( array (
				'supersonic' => untrailingslashit( substr( admin_url(), strlen( trailingslashit( site_url() ) ) ) ) 
		), $url );
	}
	$in_home_url = false;
	return $url;
}
add_filter( 'home_url', 'wpss_home_url', 100, 4 );

$in_site_url = false;
$add_supersonic_site_url = - 1;

function wpss_site_url( $url, $path, $orig_scheme, $blog_id ) {
    if ( is_customize_preview() ) {
        //return $url;
    }
    global $disable_supersonic_url;
    if ( $disable_supersonic_url ) {
        return $url;
    }
	if ( is_admin() ) {
		return $url;
	}
	global $in_site_url;
	if ( $in_site_url ) {
		return $url;
	}
	$in_site_url = true;
	if ( $path == '/wp-comments-post.php' ) {
		global $add_supersonic_site_url;
		if ( $add_supersonic_site_url == - 1 ) {
			$add_supersonic_site_url = 0;
			$settings = wpss_defaults( get_option( 'wpss_settings' ) );
			if ( isset( $settings['donotlogout_roles'] ) && is_array( $settings['donotlogout_roles'] ) && count( $settings['donotlogout_roles'] ) ) {
				if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
					global $current_user;
					$user_roles = $current_user->roles;
					foreach ( $user_roles as $role ) {
						if ( isset( $settings['donotlogout_roles'][$role] ) && $settings['donotlogout_roles'][$role] == '1' ) {
							$add_supersonic_site_url = 1;
							break;
						}
					}
				}
			}
		}
		if ( $add_supersonic_site_url == 1 ) {
			$url = add_query_arg( array (
					'supersonic' => untrailingslashit( substr( admin_url(), strlen( trailingslashit( site_url() ) ) ) ) 
			), $url );
		}
	}
	$in_site_url = false;
	return $url;
}
add_filter( 'site_url', 'wpss_site_url', 100, 4 );

function wpss_robots_txt( $output, $public ) {
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	if ( isset( $settings['robots_disallow'] ) && $settings['robots_disallow'] == '1' ) {
		$output .= "Disallow: /*?*supersonic=\n";
	}
	return $output;
}
add_filter( 'robots_txt', 'wpss_robots_txt', 10, 2 );

function wpss_wp_get_current_commenter( $commenter ) {
	$commenter['comment_author'] = '';
	$commenter['comment_author_email'] = '';
	$commenter['comment_author_url'] = '';
	return $commenter;
}
add_filter( 'wp_get_current_commenter', 'wpss_wp_get_current_commenter', 100, 1 );

function wpss_update( $post, $comment = 0 ) {
	global $wpdb;
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$count_rows = 0;
	$post_type = $post->post_type;
	//
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_this'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_this'] )) ) {
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'singular\' and id = ' . $post->ID;
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 1 
			) );
			$count_rows ++;
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_home'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_home'] )) ) {
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'home\'';
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 2 
			) );
			$count_rows ++;
		}
		$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
				'url' => home_url(),
				'priority' => 2 
		) );
		$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
				'url' => home_url( '/' ),
				'priority' => 2 
		) );
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_tax'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_tax'] )) ) {
		$taxonomies = get_taxonomies( '', 'names' );
		$terms = wp_get_post_terms( $post->ID, $taxonomies );
		foreach ( $terms as $term ) {
			while ( $term ) {
				$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'tax\' and id = ' . $term->term_id;
				$rows = $wpdb->get_results( $sql );
				foreach ( $rows as $row ) {
					$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
							'url' => $row->url,
							'priority' => 3 
					) );
					$count_rows ++;
				}
				if ( $term->parent ) {
					$term = get_term( $term->parent, $term->taxonomy );
				}
				else {
					$term = 0;
				}
			}
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_author'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_author'] )) ) {
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'author\' and id = ' . $post->post_author;
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 4 
			) );
			$count_rows ++;
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_date'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_date'] )) ) {
		$id_in = get_the_time( 'Y', $post ) . ',' . get_the_time( 'Ym', $post ) . ',' . get_the_time( 'Ymd', $post );
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'author\' and id in (' . $id_in . ')';
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 5 
			) );
			$count_rows ++;
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_other'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_other'] )) ) {
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'other\'';
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 100 
			) );
			$count_rows ++;
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_404'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_404'] )) ) {
		$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where type = \'404\'';
		$rows = $wpdb->get_results( $sql );
		foreach ( $rows as $row ) {
			$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
					'url' => $row->url,
					'priority' => 100 
			) );
			$count_rows ++;
		}
	}
	if ( ($comment == 0 && isset( $settings['refresh'][$post_type . '_add_clear'] )) || ($comment && isset( $settings['comments'] ) && isset( $settings['comments']['comment_add_clear'] )) ) {
		$add_clear = explode( "\n", $settings['refresh'][$post_type . '_add_clear'] );
		if ( $comment ) {
			$add_clear = explode( "\n", $settings['comments']['comment_add_clear'] );
		}
		foreach ( $add_clear as $url ) {
			$url = trim( $url );
			$url = str_replace( '*', '%', $url );
			$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where url like \'' . $url . '\'';
			$rows = $wpdb->get_results( $sql );
			foreach ( $rows as $row ) {
				$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
						'url' => $row->url,
						'priority' => 100 
				) );
				$count_rows ++;
			}
		}
	}
	if ( isset( $settings['add_clear'] ) ) {
		$add_clear = explode( "\n", $settings['add_clear'] );
		foreach ( $add_clear as $url ) {
			$url = trim( $url );
			if ( strpos( $url, '*' ) !== false ) {
				$url = str_replace( '*', '%', $url );
				$sql = 'select url from ' . $wpdb->prefix . 'wpss_links where url like \'' . $url . '\'';
				$rows = $wpdb->get_results( $sql );
				foreach ( $rows as $row ) {
					$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
							'url' => $row->url,
							'priority' => 100 
					) );
					$count_rows ++;
				}
			}
			else {
				$wpdb->replace( $wpdb->prefix . "wpss_clear", array (
						'url' => $url,
						'priority' => 1 
				) );
			}
		}
	}
	if ( $count_rows ) {
		$sql = 'delete from ' . $wpdb->prefix . 'wpss_links where url in (select url from ' . $wpdb->prefix . 'wpss_clear)';
		$wpdb->query( $sql );
	}
	if ( isset( $settings['start_immediatly'] ) && $settings['start_immediatly'] == '1' ) {
		wpss_clear_f();
	}
	else {
		$schedule = wp_next_scheduled( 'wpss_clear' );
		if ( ! $schedule || $schedule > time() + 65 ) {
			wp_schedule_single_event( time() - 60, 'wpss_clear' );
		}
	}
}

function wpss_admin_init() {
	global $wpss_bypass;
	$wpss_bypass = true;
}

add_action( 'admin_footer', 'wpss_admin_footer' );
function wpss_admin_footer() {
	$add_supersonic_site_url = 0;
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	if ( isset( $settings['donotlogout_roles'] ) && is_array( $settings['donotlogout_roles'] ) && count( $settings['donotlogout_roles'] ) ) {
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			global $current_user;
			$user_roles = $current_user->roles;
			foreach ( $user_roles as $role ) {
				if ( isset( $settings['donotlogout_roles'][$role] ) && $settings['donotlogout_roles'][$role] == '1' ) {
					$add_supersonic_site_url = 1;
					break;
				}
			}
		}
	}
	if ( $add_supersonic_site_url == 1 ) {
		$url = add_query_arg( array (
				'supersonic' => untrailingslashit( substr( admin_url(), strlen( trailingslashit( site_url() ) ) ) )
		), site_url() );
		?>
<script type="text/javascript">
<!--
	jQuery(document).ready(function() {
		jQuery('#wp-admin-bar-site-name').children( 'a' ).first().attr( 'href', '<?php echo $url; ?>' );
		jQuery('#wp-admin-bar-site-name-default').children( 'li' ).first().children( 'a' ).first().attr( 'href', '<?php echo $url; ?>' );
	});
//-->
</script>
		<?php 
	}	
}
