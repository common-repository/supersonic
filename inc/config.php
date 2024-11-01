<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit();

function wpss_defaults( $settings ) {
	$defaults = array (
			'cloudflare_login'      => '',
			'cloudflare_api_key'    => '',
			'cloudflare_domain'     => '',
			'cloudflare_zone'       => '',
			'update_time'           => 0,
			'donotlogout_roles'     => NULL,
			'donotlogout'           => '',
            'robots_disallow'       => 0,
            'content_do_not_logout' => 0,
            'wp_head_do_not_logout' => 1,
            'remove_do_not_logout'  => 0,
            'http_header'           => 0,
			'add_clear'             => '',
			'start_immediatly'      => 0,
			'event_log'             => '0',
			'event_log_clear'       => '7',
			'refresh'               => array (
					'post_this'                     => '',
					'post_add_clear'                => '',
					'page_this'                     => '',
					'page_add_clear'                => '',
					'attachment_add_clear'          => '',
					'product_this'                  => '',
					'product_home'                  => '',
					'product_add_clear'             => '',
					'product_variation_add_clear'   => '',
					'shop_coupon_add_clear'         => '',
					'wooframework_add_clear'        => '',
			),
			'comments'              =>  array (
					'comment_this'                  => '',
					'comment_add_clear'             => '',
			),
			'comment_manual_scope'  => 0,
			'comment_spam'          => 0,
			'comment_not_spam'      => 0,
			'security'              => array (
					'bruteforce_protection'         => '0',
					'bruteforce_attempts'           => '10',
					'bruteforce_reset'              => '300',
					'bruteforce_user_info'          => 0,
					'bruteforce_admin_email'        => 0,
					'bruteforce_all_zones'	        => 0,
					'bruteforce_mode'               => 'ban',
					'login_protection'              => 'disabled',
					'login_countries'               => array (
					),
					'comment_protection'            => 'disabled',
					'comment_countries'             => array (
					),
					'xmlrpc_protection'             => 'disabled',
					'xmlrpc_countries'              => array (
					),
			),
	);
	if ( !is_array( $settings ) ) {
		$settings = array();
	}
	$settings = array_replace_recursive( $defaults, $settings );
	return $settings;
}

function wpss_admin_menu() {
	add_menu_page( __( 'SuperSonic', 'wpss' ), __( 'SuperSonic', 'wpss' ), 'manage_options', 'wpss', 'wpss_config_handler' );
}
add_action( 'admin_menu', 'wpss_admin_menu' );

function wpss_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings = wpss_defaults( get_option( 'wpss_settings' ) );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpss' && isset( $_POST['wpss_cloudflare_login'] ) ) {
			if ( isset( $_POST['wpss_cloudflare_dev_mode'] ) && $_POST['wpss_cloudflare_dev_mode'] ) {
				$settings['dev_mode'] = 1;
			}
			else {
				$settings['dev_mode'] = 0;
			}
		}
		if ( isset( $_GET['wpss_devmode'] ) ) {
			$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
			if ( $_GET['wpss_devmode'] == 'enable' ) {
				$cf->devmode( $settings['cloudflare_zone'], 'on' );
				$settings['dev_mode'] = '1';
				?>
				<div class="updated">
					<p><?php _e( 'Development mode enabled.', 'wpss' ); ?></p>
				</div>
				<?php
			}
			if ( $_GET['wpss_devmode'] == 'disable' ) {
				$cf->devmode( $settings['cloudflare_zone'], 'off' );
				$settings['dev_mode'] = '0';
				?>
				<div class="updated">
					<p><?php _e( 'Development mode disabled.', 'wpss' ); ?></p>
				</div>
				<?php
			}
		}
		$title_add = '';
		if ( isset( $settings['dev_mode'] ) && $settings['dev_mode'] == '1' ) {
			$title_add = ': <span style="color:red;">Dev. Mode Enabled</span>';
		}
		$args = array (
				'id' => 'wpss',
				'title' => 'Supersonic' . $title_add,
				'href' => admin_url( 'admin.php?page=wpss' ),
				'meta' => array (
						'class' => 'wpss' 
				) 
		);
		$wp_admin_bar->add_node( $args );
		$title_add = 'Enable';
		$command = "&wpss_devmode=enable";
		if ( isset( $settings['dev_mode'] ) && $settings['dev_mode'] == '1' ) {
			$title_add = 'Disable';
			$command = "&wpss_devmode=disable";
		}
		$args = array (
				'id' => 'wpss_dev_mode',
				'parent' => 'wpss',
				'title' => 'Development mode: ' . $title_add,
				'href' => admin_url( 'admin.php?page=wpss' . $command ),
				'meta' => array (
						'class' => 'wpss' 
				) 
		);
		$wp_admin_bar->add_node( $args );
		$args = array (
				'id' => 'wpss_purge',
				'parent' => 'wpss',
				'title' => 'Purge ClodFlare cache',
				'href' => admin_url( 'admin.php?page=wpss&tab=tools&wpss_action=clear_all' ),
				'meta' => array (
						'class' => 'wpss' 
				) 
		);
		$wp_admin_bar->add_node( $args );
	}
}
add_action( 'admin_bar_menu', 'wpss_admin_bar_menu', 999 );

function wpss_save_config() {
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	if ( ! $settings ) {
		$settings = array ();
	}
	if ( isset( $_GET['tab'] ) ) {
		$tab = $_GET['tab'];
	}
	else {
		$tab = 'cloudflare';
	}
	if ( $tab == 'cloudflare' ) {
		$settings['cloudflare_login'] = sanitize_text_field( $_POST['wpss_cloudflare_login'] );
		$settings['cloudflare_api_key'] = sanitize_text_field( $_POST['wpss_cloudflare_api_key'] );
		$settings['cloudflare_zone'] = sanitize_text_field( $_POST['wpss_cloudflare_zone'] );
		$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
		$settings['cloudflare_domain'] = sanitize_text_field( $_POST['wpss_cloudflare_domain'] );
		$zone_details = $cf->get_zone( $settings['cloudflare_zone'] );
		if ( empty( $zone_details->result ) || $zone_details->result != 'error' ) {
			$settings['cloudflare_domain'] = $zone_details->result->name;
		}
		if ( isset( $_POST['wpss_cloudflare_dev_mode'] ) ) {
			$cf->devmode( $settings['cloudflare_zone'], 'on' );
		}
		else {
			$cf->devmode( $settings['cloudflare_zone'], 'off' );
		}
	}
	if ( $tab == 'options' ) {
		$settings['donotlogout_roles'] = array();
		if ( isset( $_POST['wpss_donotlogout_roles'] ) && is_array( $_POST['wpss_donotlogout_roles'] ) ) {
			foreach ( $_POST['wpss_donotlogout_roles'] as $key => $val ) {
				$settings['donotlogout_roles'][sanitize_text_field( $key )] = 1;
			}
		}
		$settings['donotlogout'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['wpss_donotlogout'] ) ) ); 
		$settings['add_clear'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['wpss_add_clear'] ) ) );
		$settings['start_immediatly'] = 0;
		if ( isset( $_POST['wpss_start_immediatly'] ) && $_POST['wpss_start_immediatly'] = '1' ) {
			$settings['start_immediatly'] = '1';
		}
		$settings['http_header'] = 0;
		if ( isset( $_POST['wpss_http_header'] ) && $_POST['wpss_http_header'] == '1' ) {
			$settings['http_header'] = '1';
		}
        $settings['remove_do_not_logout'] = 0;
        if ( isset( $_POST['wpss_remove_do_not_logout'] ) && $_POST['wpss_remove_do_not_logout'] == '1' ) {
            $settings['remove_do_not_logout'] = '1';
        }
        $settings['content_do_not_logout'] = 0;
        if ( isset( $_POST['wpss_content_do_not_logout'] ) && $_POST['wpss_content_do_not_logout'] == '1' ) {
            $settings['content_do_not_logout'] = '1';
        }
        $settings['wp_head_do_not_logout'] = 0;
        if ( isset( $_POST['wpss_wp_head_do_not_logout'] ) && $_POST['wpss_wp_head_do_not_logout'] == '1' ) {
            $settings['wp_head_do_not_logout'] = '1';
        }
		$settings['robots_disallow'] = 0;
		if ( isset( $_POST['wpss_robots_disallow'] ) && $_POST['wpss_robots_disallow'] == '1' ) {
			$settings['robots_disallow'] = '1';
		}
		$settings['event_log'] = 0;
		if ( isset( $_POST['wpss_event_log'] ) && $_POST['wpss_event_log'] == '1' ) {
			$settings['event_log'] = 1;
		}
		$settings['event_log_clear'] = sanitize_text_field( $_POST['wpss_event_log_clear'] );
	}
	if ( $tab == 'post_types' ) {
		$settings['refresh'] = array();
		if ( isset( $_POST['refresh'] ) ) {
			$post_types = get_post_types( array (
					'public' => true
			), 'objects' );		
			foreach ( $post_types as $post_type ) {
				if ( isset( $_POST['refresh'][$post_type->name . '_this'] ) ) {
					$settings['refresh'][$post_type->name . '_this'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_home'] ) ) {
					$settings['refresh'][$post_type->name . '_home'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_tax'] ) ) {
					$settings['refresh'][$post_type->name . '_tax'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_author'] ) ) {
					$settings['refresh'][$post_type->name . '_author'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_date'] ) ) {
					$settings['refresh'][$post_type->name . '_date'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_search'] ) ) {
					$settings['refresh'][$post_type->name . '_search'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_404'] ) ) {
					$settings['refresh'][$post_type->name . '_404'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_other'] ) ) {
					$settings['refresh'][$post_type->name . '_other'] = 1;
				}
				if ( isset( $_POST['refresh'][$post_type->name . '_add_clear'] ) ) {
					$settings['refresh'][$post_type->name . '_add_clear'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['refresh'][$post_type->name . '_add_clear'] ) ) );
				}
			}
		}
		$settings['comments'] = array();
		if ( isset( $_POST['comments'] ) ) {
			if ( isset( $_POST['comments']['comment_this'] ) ) {
				$settings['comments']['comment_this'] = 1;
			}
			if ( isset( $_POST['comments']['comment_home'] ) ) {
				$settings['comments']['comment_home'] = 1;
			}
			if ( isset( $_POST['comments']['comment_tax'] ) ) {
				$settings['comments']['comment_tax'] = 1;
			}
			if ( isset( $_POST['comments']['comment_author'] ) ) {
				$settings['comments']['comment_author'] = 1;
			}
			if ( isset( $_POST['comments']['comment_date'] ) ) {
				$settings['comments']['comment_date'] = 1;
			}
			if ( isset( $_POST['comments']['comment_search'] ) ) {
				$settings['comments']['comment_search'] = 1;
			}
			if ( isset( $_POST['comments']['comment_404'] ) ) {
				$settings['comments']['comment_404'] = 1;
			}
			if ( isset( $_POST['comments']['comment_other'] ) ) {
				$settings['comments']['comment_other'] = 1;
			}
			if ( isset( $_POST['comments']['comment_add_clear'] ) ) {
				$settings['comments']['comment_add_clear'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['comments']['comment_add_clear'] ) ) );
			}
		}
	}
	if ( $tab == 'comments' ) {		
		$settings['comment_manual_scope'] = sanitize_text_field( $_POST['comment_manual_scope'] );
		$settings['comment_spam'] = sanitize_text_field( $_POST['comment_spam'] );
		$settings['comment_not_spam'] = sanitize_text_field( $_POST['comment_not_spam'] );
	}
	if ( $tab == 'security' ) {
		$settings['security']['bruteforce_protection'] = sanitize_text_field( $_POST['bruteforce_protection'] );
		$settings['security']['bruteforce_attempts'] = intval( $_POST['bruteforce_attempts'] );
		$settings['security']['bruteforce_reset'] = intval( $_POST['bruteforce_reset'] );
		$settings['security']['bruteforce_mode'] = sanitize_text_field( $_POST['bruteforce_mode'] );
		$settings['security']['bruteforce_all_zones'] = sanitize_text_field( $_POST['bruteforce_all_zones'] );
		$settings['security']['bruteforce_user_info'] = 0;
		if ( $_POST['bruteforce_user_info'] == '1' ) {
			$settings['security']['bruteforce_user_info'] = 1;
		}
		$settings['security']['bruteforce_admin_email'] = 0;
		if ( $_POST['bruteforce_admin_email'] == '1' ) {
			$settings['security']['bruteforce_admin_email'] = 1;
		}
		//
		$settings['security']['login_protection'] = sanitize_text_field( $_POST['login_protection'] );
		$settings['security']['login_countries'] = array();
		if ( isset( $_POST['login_countries'] ) && is_array( $_POST['login_countries'] ) ) {
			foreach ( $_POST['login_countries'] as $country ) {
				$settings['security']['login_countries'][] = sanitize_text_field( $country );
			}
		}
		$settings['security']['comment_protection'] = sanitize_text_field( $_POST['comment_protection'] );
		$settings['security']['comment_countries'] = array();
		if ( isset( $_POST['comment_countries'] ) && is_array( $_POST['comment_countries'] ) ) {
			foreach ( $_POST['comment_countries'] as $country ) {
				$settings['security']['comment_countries'][] = sanitize_text_field( $country );
			}
		}
		$settings['security']['xmlrpc_protection'] = $_POST['xmlrpc_protection'];
		$settings['security']['xmlrpc_countries'] = array();
		if ( isset( $_POST['xmlrpc_countries'] ) && is_array( $_POST['comment_countries'] ) ) {
			foreach ( $_POST['xmlrpc_countries'] as $country ) {
				$settings['security']['xmlrpc_countries'][] = sanitize_text_field( $country );
			}
		}
	}
	$settings['update_time'] = current_time( 'timestamp' );
	update_option( 'wpss_settings', $settings );
}

function wpss_config_handler() {
	echo '<div class="wrap">';
	echo '<div id="icon-settings" class="icon32"><br></div>';
	echo '<h2>Wordpress SuperSonic with CloudFlare</h2>';
	if ( isset( $_GET['doclear'] ) && $_GET['doclear'] == 1 ) {
		do_action( 'wpss_clear' );
	}
	global $wpdb;
	$sql = "select count(*) to_clear from " . $wpdb->prefix . "wpss_clear";
	$to_clear = $wpdb->get_row( $sql );
	if ( $to_clear->to_clear ) {
		?>
		<div class="error">
			<p>
   				<?php _e( 'There are <b>'.$to_clear->to_clear.'</b> pages in queue to purge from CloudFlare cache.', 'wpss' ); ?>&nbsp;
    			<a href="?page=wpss&tab=log&doclear=1"><?php _e( 'Purge now!', 'wpss' ); ?></a>&nbsp;<br />
				<a href="?page=wpss&tab=tools&wpss_action=clear_all"><?php _e( 'Purge everything!', 'wpss' ); ?></a>
			</p>
		</div>
		<?php
	}
	if ( isset( $_POST["wpss-config-submit"] ) && $_POST["wpss-config-submit"] == 'Y' ) {
		check_admin_referer( "wpss-config" );
		wpss_save_config();
		$url_parameters = isset( $_GET['tab'] ) ? 'tab=' . $_GET['tab'] : 'updated=true';
		?>
		<div class="updated">
			<p><?php _e( 'Settings updated!', 'wpss' ); ?></p>
		</div>
		<?php
		if ( ! isset( $_GET['tab'] ) || $_GET['tab'] == 'cloudflare' ) {
			$settings = wpss_defaults( get_option( 'wpss_settings' ) );
			$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
			$url = site_url() . '/?testcf';
			$ret = $cf->list_zones();			
			if ( $ret->success != '1' ) {
				$msg = '';
				if ( is_object( $ret ) ) {
					$msg = '<b>' . $ret->msg . '</b>';
				}
				?>
				<div class="error">
					<p><?php _e( 'CloudFlare test not passed! '.$msg, 'wpss' ); ?></p>
				</div>
				<?php
			}
			else {
				?>
				<div class="updated">
					<p><?php _e( 'CloudFlare test passed.', 'wpss' ); ?></p>
				</div>
				<?php
			}
		}
	}
	else if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'tools' ) {
		$settings = wpss_defaults( get_option( 'wpss_settings' ) );
		$tools_action = false;
		if ( isset( $_POST['tools_action'] ) ) {
			$tools_action = $_POST['tools_action'];
		}
		if ( $tools_action ) {
			if ( $tools_action == 'url_list' ) {
				$wpss_list_clear = $_POST['wpss_list_clear'];
				$links = explode( "\n", $wpss_list_clear );
				$count_rows = 0;
				foreach ( $links as $link ) {
					$link = trim( $link );
					$count_rows ++;
					$wpdb->insert( $wpdb->prefix . "wpss_clear", array (
							'url' => $link,
							'priority' => 1 
					) );
				}
				if ( $count_rows ) {
					$sql = 'delete from ' . $wpdb->prefix . 'wpss_links where url in (select url from ' . $wpdb->prefix . 'wpss_clear)';
					$wpdb->query( $sql );
					wp_schedule_single_event( time(), 'wpss_clear' );
					?>
					<div class="updated">
						<p><?php _e( 'Cached files will be purged in next wp-cron run.', 'wpss' ); ?></p>
					</div>
					<?php
				}
			}
			if ( $tools_action == 'ban_ip' || $tools_action == 'wl_ip' || $tools_action == 'nul_ip' ) {
				$wpss_list_ip = $_POST['wpss_list_ip'];
				$ips = explode( "\n", $wpss_list_ip );
				$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
				$errors = '';
				foreach ( $ips as $ip ) {
					$ip = trim( $ip );
					if ( $errors == '' ) {
						if ( $tools_action == 'ban_ip' ) {
							$ret = $cf->ban( $ip, 'WP Supersonic manual' );
						}
						if ( $tools_action == 'wl_ip' ) {
							$ret = $cf->wl( $ip, 'WP Supersonic manual' );
						}
						if ( $tools_action == 'nul_ip' ) {
							$ret = $cf->nul( $ip, 'WP Supersonic manual' );
						}
						if ( $ret->success != '1' ) {
							$errors = $ret->msg . ' for ' . $ip;
						}
					}
				}
				if ( $errors == '' ) {
					?>
					<div class="updated">
						<p><?php _e( 'Operation completed.', 'wpss' ); ?></p>
					</div>
					<?php
				}
				else {
					?>
					<div class="error">
						<p><?php echo _e( 'Operation failed:', 'wpss' ).' '.$errors; ?></p>
					</div>
					<?php
				}
			}
		}
		else if ( isset( $_GET['wpss_action'] ) && $_GET['wpss_action'] == 'clear_all' ) {
			$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
			$ret = $cf->fpurge_ts( $settings['cloudflare_zone'] );
			if ( $ret->success != '1' ) {
				$msg = '';
				if ( is_object( $ret ) ) {
					$msg = $ret->msg;
				}
				?>
				<div class="error">
					<p><?php _e( 'CloudFlare error: <b>'.$msg.'</b>', 'wpss' ); ?></p>
				</div>
				<?php
			}
			else {
				$table_name = $wpdb->prefix . 'wpss_links';
				$wpdb->query( 'TRUNCATE ' . $table_name );
				$table_name = $wpdb->prefix . 'wpss_clear';
				$wpdb->query( 'TRUNCATE ' . $table_name );
				?>
				<div class="updated">
					<p><?php _e( 'CloudFlare cache purged.', 'wpss' ); ?></p>
				</div>
				<?php
			}
		}
		else if ( isset( $_GET['wpss_action'] ) && $_GET['wpss_action'] == 'clear_cached' ) {
			$count_rows = 0;
			$sql = 'select url from ' . $wpdb->prefix . 'wpss_links';
			$rows = $wpdb->get_results( $sql );
			foreach ( $rows as $row ) {
				$wpdb->insert( $wpdb->prefix . "wpss_clear", array (
						'url' => $row->url,
						'priority' => 1 
				) );
				$count_rows ++;
			}
			if ( $count_rows ) {
				$sql = 'delete from ' . $wpdb->prefix . 'wpss_links where url in (select url from ' . $wpdb->prefix . 'wpss_clear)';
				$wpdb->query( $sql );
				wp_schedule_single_event( time(), 'wpss_clear' );
				?>
				<div class="updated">
					<p><?php _e( 'Cached files will be purged in next wp-cron run.', 'wpss' ); ?></p>
				</div>
				<?php
			}
			else {
				?>
				<div class="updated">
					<p><?php _e( 'There are no files to purge.', 'wpss' ); ?></p>
				</div>
				<?php
			}
		}
	}
	$settings = wpss_defaults( get_option( 'wpss_settings' ) );
	$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
	$cf_zone_setings = $cf->devmode( $settings['cloudflare_zone'] );
	if ( $cf_zone_setings->success == 'success' ) {
		//$settings['cf_settings'] = $cf_zone_setings->result->value;
		if ( $cf_zone_setings->result->value == 'on' ) {
			$settings['dev_mode'] = 1;
		}
		else {
			$settings['dev_mode'] = 0;
		}
		$updated = update_option( 'wpss_settings', $settings );
	}
	if ( isset( $_GET['tab'] ) ) wpss_config_handler_tabs( $_GET['tab'] );
	else wpss_config_handler_tabs( 'cloudflare' );
	echo '</div>';
	echo '<!-- wrap -->';
}

function wpss_config_handler_tabs( $current = 'cloudflare' ) {
	$settings = wpss_defaults( get_option( 'wpss_settings', array() ) );
	if ( ! isset( $settings['security']['bruteforce_protection'] ) ) {
		$settings['security']['bruteforce_protection'] = 0;
	}
	if ( ! isset( $settings['security']['bruteforce_attempts'] ) ) {
		$settings['security']['bruteforce_attempts'] = 10;
	}
	if ( ! isset( $settings['security']['bruteforce_reset'] ) ) {
		$settings['security']['bruteforce_reset'] = 300;
	}
	$tabs = array (
			'cloudflare' => 'CF Settings',
			'options' => 'Options',
			'tools' => 'CF Tools',
			'post_types' => 'Purge Settings',
			'comments' => 'Comments',
			'security' => 'Security',
			'log' => 'Log',
			'statistics' => 'Statistics',
			'documentation' => 'Documentation',
			'donate' => 'Donate' 
	);
	$tab_hints = array (
			'cloudflare' => 'Cloudflare Settings',
			'options' => 'Options',
			'tools' => 'CloudFlare Tools',
			'post_types' => 'Purge Settings on posts, pages and comments',
			'comments' => 'Comments',
			'security' => 'Security',
			'log' => 'Log',
			'statistics' => 'Statistics',
			'documentation' => 'Documentation',
			'donate' => 'Donate' 
	);
	
	$cloudflare_zones = array();
	if ( isset( $settings['cloudflare_login'] ) && isset( $settings['cloudflare_api_key'] ) ) {
		$cf = new cloudflare_api( $settings['cloudflare_login'], $settings['cloudflare_api_key'] );
		$do = true;
		$page = 1;
		while ( $do ) {
			$cloudflare_zones_list = $cf->list_zones( $page );
			if ( empty( $cloudflare_zones_list->result ) || $cloudflare_zones_list->result != 'error' ) {
				foreach ( $cloudflare_zones_list->result as $zone ) {
					$cloudflare_zones[$zone->id] = array( 'id' => $zone->id, 'zone_name' => $zone->name );
				}
				if ( $cloudflare_zones_list->result_info->page < $cloudflare_zones_list->result_info->total_pages ) {
					$page++;
				}
				else {
					$do = false;
				}
			}
			else {
				$do = false;
			}
		}
	}	
	?>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content" style="">
	    <?php
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ($tab == $current) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=wpss&tab=$tab' title='" . $tab_hints[$tab] . "'>$name</a>";
		}
		echo '</h2>';
		$form_method = 'POST';
		if ( $tab == 'log' ) {
			$form_method = 'GET';
		}
		if ( $current != 'tools' && $current != 'statistics' && $current != 'documentation' && $current != 'log' && $current != 'donate' ) {
			?>
	    	<form id="wpss_settings" method="<?php echo $form_method; ?>"
					action="<?php /* echo admin_url( 'admin.php?page=wpss&tab='.$tab ); */ ?>">
		<?php
		}
		wp_nonce_field( "wpss-config" );
		if ( isset( $_GET['tab'] ) ) $tab = $_GET['tab'];
		else $tab = 'cloudflare';
		if ( $tab == 'cloudflare' ) {
			?>
			<table class="form-table">
				<tr>
					<th><label for="wpss_cloudflare_login">CloudFlare login:</label></th>
					<td><input style="width: 340px;" id="wpss_cloudflare_login"
						name="wpss_cloudflare_login" type="email"
						value="<?php echo $settings['cloudflare_login']; ?>" /><br /></td>
				</tr>
				<tr>
					<th><label for="wpss_cloudflare_api_key">CloudFlare API key:</label></th>
					<td><input style="width: 340px;" id="wpss_cloudflare_api_key"
						name="wpss_cloudflare_api_key" type="text"
						value="<?php echo $settings['cloudflare_api_key']; ?>" /><br /> 
						<span class="description">CloudFlare API key You can find <a href="https://www.cloudflare.com/my-account">here</a>.
						</span>
					</td>
				</tr>
<?php /* ?>				
				<tr>
					<th><label for="wpss_cloudflare_api_key">CloudFlare domain:</label></th>
					<td><input style="width: 340px;" id="wpss_cloudflare_domain"
						name="wpss_cloudflare_domain" type="text"
						value="<?php echo $settings['cloudflare_domain']; ?>" /><br /> <span
						class="description">Domain must be added and activated on Your
							CloudFlare account.</span></td>
				</tr>
<?php */ ?>				
				<tr>
					<th><label for="wpss_cloudflare_zone">CloudFlare zone:</label></th>
					<td>
						<select name="wpss_cloudflare_zone">
							<?php foreach ( $cloudflare_zones as $cloudflare_zone ) : ?>
								<?php 								
									$selected = '';
									if ( isset( $settings['cloudflare_zone'] ) && $settings['cloudflare_zone'] == $cloudflare_zone['id'] ) {
										$selected = ' selected';
									}
								?>
								<option <?php echo $selected; ?> value="<?php echo $cloudflare_zone['id']; ?>"><?php echo $cloudflare_zone['zone_name']; ?></option>
							<?php endforeach; ?>
						</select><br/>
						<span class="description">Domain must be added and activated on Your CloudFlare account.</span>
					</td>
				</tr>
				<tr>
					<th><label for="wpss_cloudflare_dev_mode">Development mode:</label></th>
					<td><input id="wpss_cloudflare_dev_mode"
						name="wpss_cloudflare_dev_mode" type="checkbox" value="1"
						<?php echo ($settings['dev_mode']=='1')?'checked':''; ?>> <span
						class="description">Enable ClodFlare development mode.</span></td>
				</tr>
			</table>
			<?php
		}
		if ( $tab == 'options' ) {
			?>
			<table class="form-table">
						<tr>
							<th><label for="wpss_donotlogout">Do not logout on URLs:</label></th>
							<td><textarea style="width: 340px; height: 100px;"
									id="wpss_donotlogout" name="wpss_donotlogout"><?php echo $settings['donotlogout']; ?></textarea><br />
								<span class="description"> By default SuperSonic serves all
									Wordpress front end pages as for non logged in users. Here you
									can define paths for pages to disable this future.<br /> You can
									use widcard *. Ex: /user-area*. One URL per line. Use only
									relative path to site URL, ex: /path.<br /> <strong>In
										CloudFlare page rules You must define page rule for these URLs
										with Custom caching <font color="red">Bypass cache</font>.
								</strong>
							</span></td>
						</tr>
						<tr>
							<th><label for="wpss_donotlogout_roles">Do not logout user roles:</label></th>
							<td>
	            	<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role ) {
				echo "<input value='1' type='checkbox' name='wpss_donotlogout_roles[" . strtolower( $role['name'] ) . "]' id='wpss_donotlogout_roles[" . strtolower( $role['name'] ) . "]' " . ((isset( $settings['donotlogout_roles'][strtolower( $role['name'] )] ) && $settings['donotlogout_roles'][strtolower( $role['name'] )] == '1') ? 'checked' : '') . "><label for='wpss_donotlogout_roles[" . strtolower( $role['name'] ) . "]'>" . $role['name'] . "</label>&nbsp; ";
			}
			?>
	            	<br /> <br /> <span class="description"> Do not logout
									specified user roles. It is done by adding <i>supersonic</i>
									parameter to all front end pages with value <i>wp-admin</i>.<br />
									<strong> In CloudFlare page rules You must have rule with <i>URL
											pattern: /*wp-admin*</i> <font color="red">Bypass cache</font>.<br />
										If You have rule with URL pattern: <i>/wp-admin*</i> or <i>/wp-admin/*</i>
										You can change it to: <i>/*wp-admin*</i>.<br /> This rule must
										be before Cache everything rule.
								</strong>
							</span>
							</td>
						</tr>
                        <tr>
                            <th><label for="wpss_remove_do_not_logout">Remove supersonic=wp-admin parameter</label></th>
                            <td><input id="wpss_remove_do_not_logout" name="wpss_remove_do_not_logout"
                                       type="checkbox" value="1"
                                    <?php echo ((isset($settings['remove_do_not_logout']) && $settings['remove_do_not_logout']=='1')?'checked':''); ?>> Enable
                                <br /> <br /> <span class="description"> Removes <b>supersonic=wp-admin</b> paremeter from browser url bar. It is done with JavaScript when page loads.
                                                    </span></td>
                        </tr>
                        <tr>
                            <th><label for="wpss_wp_head_do_not_logout">Do not logout on wp_head</label></th>
                            <td><input id="wpss_wp_head_do_not_logout" name="wpss_wp_head_do_not_logout"
                                       type="checkbox" value="1"
                                    <?php echo ((isset($settings['wp_head_do_not_logout']) && $settings['wp_head_do_not_logout']=='1')?'checked':''); ?>> Disable
                                <br /> <br /> <span class="description"> Disables do not logout feature on wp_head action. When disabled (checked) prevent link modification in &lt;HEAD&gt; section.
                                            </span></td>
                        </tr>
                        <tr>
                            <th><label for="wpss_content_do_not_logout">Do not logout on content</label></th>
                            <td><input id="wpss_content_do_not_logout" name="wpss_content_do_not_logout"
                                       type="checkbox" value="1"
                                    <?php echo ((isset($settings['content_do_not_logout']) && $settings['content_do_not_logout']=='1')?'checked':''); ?>> Disable
                                <br /> <br /> <span class="description"> Disables (when checked) do not logout feature on post/page content.
                                    </span></td>
                        </tr>
						<tr>
							<th><label for="wpss_robots_disallow">robots.txt</label></th>
							<td><input id="wpss_robots_disallow" name="wpss_robots_disallow"
								type="checkbox" value="1"
								<?php echo ((isset($settings['robots_disallow']) && $settings['robots_disallow']=='1')?'checked':''); ?>> Enable
								<br /> <br /> <span class="description"> Add Disallow: <em><b>/*?*supersonic=</b></em>
									to robots.txt
							</span></td>
						</tr>
						<tr>
							<th><label for="wpss_add_clear">Additional URLs to clear:</label></th>
							<td><textarea style="width: 340px; height: 100px;"
									id="wpss_add_clear" name="wpss_add_clear"><?php echo $settings['add_clear']; ?></textarea><br />
								<span class="description"> Define URLs to clear from CloudFlare
									cache on every event (every post types or comments are created,
									edited, deleted.) You can use widcard *. Ex: /sitemap*. One URL
									per line. Use only relative path to site URL, ex: /path.<br />
							</span></td>
						</tr>
						<tr>
							<th><label for="wpss_start_immediatly">Purge immediatly</label></th>
							<td><input id="wpss_start_immediatly" name="wpss_start_immediatly"
								type="checkbox" value="1"
								<?php echo ($settings['start_immediatly']=='1')?'checked':''; ?>> Enable
								<br /> <br /> <span class="description"> Starts purging URLs from
									CloudFlare cache immediatly, without waiting for wp-cron. </span>
							</td>
						</tr>
						<tr>
							<th><label for="wpss_http_header">Set Supersonic header</label></th>
							<td><input id="wpss_http_header" name="wpss_http_header"
								type="checkbox" value="1"
								<?php echo ((isset($settings['http_header']) && $settings['http_header']=='1'))?'checked':''; ?>> Enable
								<br /> <br /> <span class="description"> Set this header to
									assist in identifying supersonic. </span></td>
						</tr>
						<tr>
							<th><label for="wpss_event_log">Enable event logging</label></th>
							<td><input id="wpss_event_log" name="wpss_event_log"
								type="checkbox" value="1"
								<?php echo ($settings['event_log']=='1')?'checked':''; ?>> Enable
                                <span class="description"> </span></td>
						</tr>
						<tr>
							<th><label style="vertical-align: top;" for="wpss_event_log_clear">Delete
									log entries older than</label></th>
							<td><input type="number" min="0" max="370"
								id="wpss_event_log_clear" name="wpss_event_log_clear" size="3"
								value="<?php echo $settings['event_log_clear']; ?>" /> days <br />
								<span class="description">0 - disable deleting log entries</span>
							</td>
						</tr>
					</table>
			<?php
		}
		if ( $tab == 'tools' ) {
			?>
			<table class="form-table">
						<tr>
							<th></th>
							<td><a
								href="<?php echo admin_url( 'admin.php?page=wpss&tab=tools&wpss_action=clear_all' ); ?>"
								class="button">Purge CloudFlare cache</a><br /> <span
								class="description">Purge all data from CloudFlare cache. It
									includes all static content.</span></td>
						</tr>
						<tr>
							<th></th>
							<td><a
								href="<?php echo admin_url( 'admin.php?page=wpss&tab=tools&wpss_action=clear_cached' ); ?>"
								class="button">Purge cached files</a><br /> <span
								class="description">Purge all content send to users and
									registered by SuperSonic plugin since last purging.</span></td>
						</tr>
						<tr>
							<th><label for="wpss_list_clear">List of URLs to clear:</label></th>
							<td>
								<form id="wpss_settings" method="<?php echo $form_method; ?>"
									action="<?php /* echo admin_url( 'admin.php?page=wpss&tab='.$tab ); */ ?>">
									<textarea style="width: 340px; height: 100px;"
										id="wpss_list_clear" name="wpss_list_clear"></textarea>
									<br /> <span class="description"> Enter single URL or list of
										URLs to purge from CloudFlare cache. Each URL in new line.<br />
									</span> <input type="hidden" id="tools_action"
										name="tools_action" value=""> <input type="button"
										name="url_list" class="button" value="Purge list"
										onclick="jQuery('#tools_action').val('url_list');this.form.submit();">
								</form>
							</td>
						</tr>
						<tr>
							<th><label for="wpss_list_ip">List of IP adresses:</label></th>
							<td>
								<form id="wpss_settings" method="<?php echo $form_method; ?>"
									action="<?php /* echo admin_url( 'admin.php?page=wpss&tab='.$tab ); */ ?>">
									<textarea style="width: 340px; height: 100px;" id="wpss_list_ip"
										name="wpss_list_ip"></textarea>
									<br /> <span class="description"> Enter single IP or list of IPs
										to Ban, White list or remove from list. Each IP in new line.<br />
									</span> <input type="hidden" id="tools_action_ip"
										name="tools_action" value=""> <input type="button"
										name="ban_ip" class="button" value="Ban"
										onclick="jQuery('#tools_action_ip').val('ban_ip');this.form.submit();">
									<input type="button" name="wl_ip" class="button"
										value="White list"
										onclick="jQuery('#tools_action_ip').val('wl_ip');this.form.submit();">
									<input type="button" name="wl_ip" class="button" value="Nul"
										onclick="jQuery('#tools_action_ip').val('nul_ip');this.form.submit();">
								</form>
							</td>
						</tr>
						<tr>
							<th></th>
							<td><a
								href="https://www.cloudflare.com/a/firewall/<?php echo $settings['cloudflare_domain']; ?>/ip_firewall"
								target="_blank">CloudFlare Firewall</a></td>
						</tr>
					</table>
			<?php
		}
		if ( $tab == 'post_types' ) {
			$post_types = get_post_types( array (
					'public' => true 
			), 'objects' );
			foreach ( $post_types as $post_type ) {
				$name = $post_type->name;
				echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . $post_type->labels->name . '</span></h3><div class="inside">';
				echo '<table class="form-table">';
				?>
						<tr>
						<th colspan="2">
								Specify the pages and feeds to purge from CloudFlare cache when <?php echo $name; ?> are created, edited or deleted<br />
							<br />
								<?php
				$item_name = $name . '_' . 'this';
				$item_label = $post_type->labels->singular_name;
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_home';
				$item_label = 'Home page';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_tax';
				$item_label = 'Taxonomy pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_author';
				$item_label = 'Author pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_date';
				$item_label = 'Date pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_search';
				$item_label = 'Search pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_404';
				$item_label = '404 pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_other';
				$item_label = 'Other pages';
				?>
								<input id="<?php echo $item_name; ?>"
							name="refresh[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['refresh'][$item_name])?($settings['refresh'][$item_name]=='1'?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
								<?php
				$item_name = $name . '_add_clear';
				$item_label = 'Additional pages';
				?>
							</th>
					</tr>
					<tr>
						<th><label style="vertical-align: top;"
							for="<?php echo $item_name; ?>"><?php echo $item_label; ?> </label>
						</th>
						<td><textarea style="width: 50%; height: 60px;"
								id="<?php echo $item_name; ?>"
								name="refresh[<?php echo $item_name; ?>]"><?php echo $settings['refresh'][$item_name]; ?></textarea><br />
							<span class="description">Specify additional pages to purge from
								Cloudflare cache. You can use widcard *. Ex: /sitemap*.xml. One
								URL per line. Use only relative path to site URL, ex: /path.</span>
						</td>
					</tr>
						<?php
				echo '</table>';
				echo '</div></div></div>';
			}
			// print_r($settings);
		}
		if ( $tab == 'post_types' ) {
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'Comments' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			$name = 'comment';
			?>
					<tr>
						<th colspan="2">Specify the pages and feeds to purge from
							CloudFlare cache when comments are created, edited or deleted<br />
							<br />
							<?php
			$item_name = $name . '_' . 'this';
			$item_label = "Post";
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_home';
			$item_label = 'Home page';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_tax';
			$item_label = 'Taxonomy pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_author';
			$item_label = 'Author pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_date';
			$item_label = 'Date pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_search';
			$item_label = 'Search pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_404';
			$item_label = '404 pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_other';
			$item_label = 'Other pages';
			?>
							<input id="<?php echo $item_name; ?>"
							name="comments[<?php echo $item_name; ?>]" type="checkbox"
							value="1"
							<?php echo isset($settings['comments'][$item_name])?(($settings['comments'][$item_name]=='1')?'checked':''):''; ?>>
							<label for="<?php echo $item_name; ?>"><?php echo $item_label; ?></label><br />
							<?php
			$item_name = $name . '_add_clear';
			$item_label = 'Additional pages';
			?>
						</th>
					</tr>
					<tr>
						<th><label style="vertical-align: top;"
							for="<?php echo $item_name; ?>"><?php echo $item_label; ?> </label>
						</th>
						<td><textarea style="width: 50%; height: 60px;"
								id="<?php echo $item_name; ?>"
								name="comments[<?php echo $item_name; ?>]"><?php echo $settings['comments'][$item_name]; ?></textarea><br />
							<span class="description">Specify additional pages to purge from
								Cloudflare cache. You can use widcard *. Ex: /sitemap*.xml. One
								URL per line. Use only relative path to site URL, ex: /path.</span>
						</td>
					</tr>
					<?php
			echo '</table>';
			echo '</div></div></div>';
		}
		if ( $tab == 'comments' ) {
			//
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'Comments' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			?>
					<tr>
						<th><label style="vertical-align: top;" for="comment_manual_scope">Action scope</label></th>
						<td>
							<select name="comment_manual_scope" id="comment_manual_scope">
								<option value="0" <?php echo $settings['comment_manual_scope'] == '0' ? 'selected' : ''; ?>>This zone only</option>
								<option value="1"  <?php echo $settings['comment_manual_scope'] == '1' ? 'selected' : ''; ?>>All user zones</option>
							</select>
							<br /> <span class="description">Action scope for all comments actions (manual and automatic).</span>
						</td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;" for="comment_spam">Spam comment action</label></th>
						<td>
							<select name="comment_spam" id="comment_spam">
								<option value="0" <?php echo $settings['comment_spam'] == '0' ? 'selected' : ''; ?>>None</option>
								<option value="1"  <?php echo $settings['comment_spam'] == '1' ? 'selected' : ''; ?>>Challenge IP</option>
								<option value="2"  <?php echo $settings['comment_spam'] == '2' ? 'selected' : ''; ?>>Ban IP</option>
							</select>
							<br /> <span class="description">Perform automatic action for comment user IP when comment is marked as spam. Works with other plugins (like Akismet).</span>
						</td>
					</tr>
					<tr>
					<th><label style="vertical-align: top;" for="comment_not_spam">Not spam comment action</label></th>
						<td>
							<select name="comment_not_spam" id="comment_not_spam">
								<option value="0" <?php echo $settings['comment_not_spam'] == '0' ? 'selected' : ''; ?>>None</option>
								<option value="1"  <?php echo $settings['comment_not_spam'] == '1' ? 'selected' : ''; ?>>Delete rule</option>
							</select>
							<br /> <span class="description">Perform automatic action for comment user IP when comment is removed from spam.</span>
						</td>
					</tr>
			<?php 					
			echo '</table>';
			echo '</div></div></div>';
		}
		if ( $tab == 'security' ) {
			//
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'Brute force protection' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			//
			$protection_name = 'login';
			?>
					<tr>
						<th><label style="vertical-align: top;" for="bruteforce_protection">Protection
								mode</label></th>
						<td><select name="bruteforce_protection">
								<option value="0"
									<?php echo $settings['security']['bruteforce_protection']=='0'?'selected':''; ?>>Disabled</option>
								<option value="1"
									<?php echo $settings['security']['bruteforce_protection']=='1'?'selected':''; ?>>Enabled</option>
						</select> <br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;" for="bruteforce_attempts">Allowed
								login attempts</label></th>
						<td><input type="number" min="3" max="100"
							name="bruteforce_attempts" size="3"
							value="<?php echo $settings['security']['bruteforce_attempts']; ?>" />
							<br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;" for="bruteforce_reset">Reset
								time</label></th>
						<td><input type="number" min="1" max="10080"
							name="bruteforce_reset" size="3"
							value="<?php echo $settings['security']['bruteforce_reset']; ?>" />
							minutes <br /> <span class="description">Time between login attemts. After this time incorrect login attemts count will be reseted.</span></td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;" for="bruteforce_user_info">Inform
								user about remaining login attempts</label></th>
						<td><input type="checkbox" value="1" name="bruteforce_user_info"
							<?php echo ($settings['security']['bruteforce_user_info']==1)?'checked':''; ?> />
							<br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;"
							for="bruteforce_admin_email">Send email to administrator when IP
								has been blocked</label>
						</th>
						<td><input type="checkbox" value="1" name="bruteforce_admin_email"
							<?php echo ($settings['security']['bruteforce_admin_email']==1)?'checked':''; ?> />
							<br /> <span class="description"></span>
						</td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;"
							for="bruteforce_all_zones">Action scope</label></th>
						<td>
							<select name="bruteforce_all_zones" id="bruteforce_all_zones">
								<option value="0" <?php echo $settings['security']['bruteforce_all_zones'] == '0' ? 'selected' : ''; ?>>This zone only</option>
								<option value="1"  <?php echo $settings['security']['bruteforce_all_zones'] == '1' ? 'selected' : ''; ?>>All user zones</option>
							</select>
							<br /> <span class="description"></span>
						</td>
					</tr>
					<tr>
						<th><label style="vertical-align: top;"
							for="bruteforce_mode">Action</label></th>
						<td>
							<select name="bruteforce_mode" id="bruteforce_mode">
								<option value="ban" <?php echo $settings['security']['bruteforce_mode'] == 'ban' ? 'selected' : ''; ?>>Ban IP</option>
								<option value="challenge" <?php echo $settings['security']['bruteforce_mode'] == 'challenge' ? 'selected' : ''; ?>>Challenge IP</option>
							</select>
							<br /> <span class="description">Ban - block IP address, Challenge - display Cloudflare challene page.</span>
						</td>
					</tr>
					<?php
			echo '</table>';
			echo '</div></div></div>';
			//
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'Login protection' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			//
			$protection_name = 'login';
			?>
					<tr>
						<th><label style="vertical-align: top;"
							for="<?php echo $protection_name; ?>_mode">Protection mode</label>
						</th>
						<td><select name="<?php echo $protection_name; ?>_protection">
								<option value="disabled"
									<?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
								<option value="deny"
									<?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny
									selected countries</option>
						</select> <br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th></th>
						<td>
							<fieldset>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br />
									<select name="<?php echo $protection_name; ?>-selectfrom"
										id="<?php echo $protection_name; ?>-selectfrom" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			$wpss_countries = wpss_get_countries();
			if ( is_array( $wpss_countries ) ) {
				if ( !isset( $settings['security'] ) ) {
					$settings['security'] = array();
				}
				if ( !isset( $settings['security'][$protection_name . '_countries'] ) ) {
					$settings['security'][$protection_name . '_countries'] = array();
				}
				foreach ( $wpss_countries as $code => $name ) {
					if ( !in_array( $code, $settings['security'][$protection_name . '_countries'] ) ) {
						echo '<option value="' . $code . '">' . $name . '</option>';
					}
				}
			}
			?>
								</select>
								</div>
								<div style="display: inline-block; text-align: center;">
									<a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-add" class="button">Add
										&raquo;</a><br /> <a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo;
										Remove</a>
								</div>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-countries">Selected</label><br />
									<select name="<?php echo $protection_name; ?>_countries[]"
										id="<?php echo $protection_name; ?>-countries" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			if ( is_array( $settings['security'][$protection_name . '_countries'] ) ) {
				foreach ( $settings['security'][$protection_name . '_countries'] as $code ) {
					echo '<option value="' . $code . '">' . $wpss_countries[$code] . '</option>';
				}
			}
			?>
								</select>
								</div>
							</fieldset> <script type="text/javascript">
								jQuery(document).ready(function() {
	    							jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
	        							jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
	                						jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
	    							jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
	        							jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
	            							jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
								});
							</script>
						</td>
					</tr>
					<?php
			echo '</table>';
			echo '</div></div></div>';
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'Comment protection' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			//
			$protection_name = 'comment';
			?>
					<tr>
						<th><label style="vertical-align: top;"
							for="<?php echo $protection_name; ?>_mode">Protection mode</label>
						</th>
						<td><select name="<?php echo $protection_name; ?>_protection">
								<option value="disabled"
									<?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
								<option value="deny"
									<?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny
									selected countries</option>
						</select> <br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th></th>
						<td>
							<fieldset>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br />
									<select name="<?php echo $protection_name; ?>-selectfrom"
										id="<?php echo $protection_name; ?>-selectfrom" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			$wpss_countries = wpss_get_countries();
			if ( is_array( $wpss_countries ) ) {
				foreach ( $wpss_countries as $code => $name ) {
					if ( empty( $settings['security'][$protection_name . '_countries'] ) || ! in_array( $code, $settings['security'][$protection_name . '_countries'] ) ) {
						echo '<option value="' . $code . '">' . $name . '</option>';
					}
				}
			}
			?>
								</select>
								</div>
								<div style="display: inline-block; text-align: center;">
									<a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-add" class="button">Add
										&raquo;</a><br /> <a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo;
										Remove</a>
								</div>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-countries">Selected</label><br />
									<select name="<?php echo $protection_name; ?>_countries[]"
										id="<?php echo $protection_name; ?>-countries" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			if ( is_array( $settings['security'][$protection_name . '_countries'] ) ) {
				foreach ( $settings['security'][$protection_name . '_countries'] as $code ) {
					echo '<option value="' . $code . '">' . $wpss_countries[$code] . '</option>';
				}
			}
			?>
								</select>
								</div>
							</fieldset> <script type="text/javascript">
								jQuery(document).ready(function() {
	    							jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
	        							jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
	                						jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
	    							jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
	        							jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
	            							jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
								});
							</script>
						</td>
					</tr>
					<?php
			echo '</table>';
			echo '</div></div></div>';
			//
			echo '<div class="metabox-holder"><div class="postbox "><div class="handlediv" title=""><br /></div><h3 class="hndle"><span>' . 'XML-RPC protection' . '</span></h3><div class="inside">';
			echo '<table class="form-table">';
			$protection_name = 'xmlrpc';
			?>
					<tr>
						<th><label style="vertical-align: top;"
							for="<?php echo $protection_name; ?>_mode">Protection mode</label>
						</th>
						<td><select name="<?php echo $protection_name; ?>_protection">
								<option value="disabled"
									<?php echo $settings['security'][$protection_name.'_protection']=='disabled'?'selected':''; ?>>Disabled</option>
								<option value="deny"
									<?php echo $settings['security'][$protection_name.'_protection']=='deny'?'selected':''; ?>>Deny
									selected countries</option>
						</select> <br /> <span class="description"></span></td>
					</tr>
					<tr>
						<th></th>
						<td>
							<fieldset>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-selectfrom">Available</label><br />
									<select name="<?php echo $protection_name; ?>-selectfrom"
										id="<?php echo $protection_name; ?>-selectfrom" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			$wpss_countries = wpss_get_countries();
			if ( is_array( $wpss_countries ) ) {
				if ( !isset( $settings['security'] ) ) {
					$settings['security'] = array();
				}
				if ( !isset( $settings['security'][$protection_name . '_countries'] ) ) {
					$settings['security'][$protection_name . '_countries'] = array();
				}				
				foreach ( $wpss_countries as $code => $name ) {
					if ( !in_array( $code, $settings['security'][$protection_name . '_countries'] )	) {
						echo '<option value="' . $code . '">' . $name . '</option>';
					}
				}
			}
			?>
									</select>
								</div>
								<div style="display: inline-block; text-align: center;">
									<a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-add" class="button">Add
										&raquo;</a><br /> <a href="JavaScript:void(0);"
										id="<?php echo $protection_name; ?>-btn-remove" class="button">&laquo;
										Remove</a>
								</div>
								<div style="display: inline-block; text-align: center;">
									<label for="<?php echo $protection_name; ?>-countries">Selected</label><br />
									<select name="<?php echo $protection_name; ?>_countries[]"
										id="<?php echo $protection_name; ?>-countries" multiple
										size="10" style="min-width: 250px; max-width: 250px;">
								<?php
			if ( is_array( $settings['security'][$protection_name . '_countries'] ) ) {
				foreach ( $settings['security'][$protection_name . '_countries'] as $code ) {
					echo '<option value="' . $code . '">' . $wpss_countries[$code] . '</option>';
				}
			}
			?>
									</select>
								</div>
							</fieldset> <script type="text/javascript">
								jQuery(document).ready(function() {
	    							jQuery('#<?php echo $protection_name; ?>-btn-add').click(function(){
		        						jQuery('#<?php echo $protection_name; ?>-selectfrom option:selected').each( function() {
	                						jQuery('#<?php echo $protection_name; ?>-countries').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
	    							jQuery('#<?php echo $protection_name; ?>-btn-remove').click(function(){
	        							jQuery('#<?php echo $protection_name; ?>-countries option:selected').each( function() {
	            							jQuery('#<?php echo $protection_name; ?>-selectfrom').append("<option value='"+jQuery(this).val()+"'>"+jQuery(this).text()+"</option>");
	            							jQuery(this).remove();
	        							});
	    							});
								});
							</script>
						</td>
					</tr>
					<?php
			echo '</table>';
			echo '</div></div></div>';
			?>
	 				<script type="text/javascript">
	 					jQuery(document).ready(function() {
	 						jQuery('#wpss_settings').submit(function (event) {
	 							jQuery('#login-countries option').prop('selected', true);
	 							jQuery('#comment-countries option').prop('selected', true);
	 							jQuery('#xmlrpc-countries option').prop('selected', true);
	 						});
	 					});
	 				</script>
	 				<?php
			// print_r($settings);
		}
		if ( $tab == 'log' ) {
			$table = new WPSS_Log_List_Table();
			$table->prepare_items();
			$message = '';
			if ( 'delete' === $table->current_action() ) {
				$message = '<div class="updated below-h2" id="message"><p>' . sprintf( __( 'Items deleted: %d', 'wpss' ), count( $_REQUEST['id'] ) ) . '</p></div>';
			}
			?>
					<form method="post">
						<input type="hidden" name="page"
							value="<?php echo $_REQUEST['page'] ?>" /> <input type="hidden"
							name="tab" value="log" />
	    			<?php
			$table->display();
			?>
	    		</form>
	    		<?php
		}
		if ( $tab == 'statistics' ) {
			?>
					<div class="metabox-holder">
						<div class="postbox">
							<div class="handlediv" title="">
								<br />
							</div>
							<h3 class="hndle">
								<span>Statistics</span>
							</h3>
							<div class="inside">
								<table class="form-table">
									<tr>
										<td>
									<?php wpss_cf_statistics(); ?>
								</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
					<?php
		}
		if ( $tab == 'documentation' ) {
			?><style>
	.doc img {
		border: 10px solid #ffffff;
		float: none;
	}
	</style>
					<div class="doc">
	
						<h2>
							<strong>Requirements</strong>
						</h2>
						<ol>
							<li>You must have CloudFlare account.</li>
							<li>Your domain must be added to CloudFlare.</li>
						</ol>
						<h2></h2>
						<h2>
							<strong>Caching HTML content</strong>
						</h2>
						If you want to cache all content including posts, pages,
						categories, tags, etc. you must add at least three page rules to
						Cloudflare:
						<ol>
							<li>URL pattern: /*.php* Custom caching: Bypass cache</li>
							<li>URL pattern: /*wp-admin* Custom caching: Bypass cache</li>
							<li>URL pattern: /* Custom caching: Cache everything Edge cache
								expire TTL: 1 week Browser cache expire TTL: 30 minutes</li>
						</ol>
						Page rules order is very important.
						<h2>
							<strong>CloudFlare Settings</strong>
						</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_cloudflare_settings.png"
							alt="" width="830" height="459" /> &nbsp;
						<h2>
							<strong>Options</strong>
						</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_options.png"
							alt="" width="819" height="729" /> &nbsp;
						<h2>Tools</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_tools.png"
							alt="" width="900" height="627" />
						<h2></h2>
						<h2>
							<strong>Cache Purge</strong>
						</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_cache_purge.png"
							alt="" width="959" height="1796" /> &nbsp;
						<h2>Security</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/supersonic_security.png"
							alt="" width="958" height="1826" /> &nbsp;
						<h2>Comments</h2>
						<img class="alignnone"
							src="https://www.wp-supersonic.com/wp-content/uploads/2015/04/superconic_comments.png"
							alt="" width="1079" height="304" />
	
					</div><?php
		}
		if ( $tab == 'donate' ) {
			?>
	Wordpress SuperSonic with CloudFlare has required a great deal of time and effort to develop. If it's been useful to you then you can support this development by making a small donation. This will act as an incentive for me to carry on developing it, providing countless hours of support, and including any enhancements that are suggested.<br />
					<br />
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post"
						target="_top">
						<input type="hidden" name="cmd" value="_s-xclick"> <input
							type="hidden" name="hosted_button_id" value="DYTX6AJZP7V7C"> <input
							type="image"
							src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif"
							border="0" name="submit"
							alt="PayPal � The safer, easier way to pay online."> <img alt=""
							border="0"
							src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif"
							width="1" height="1">
					</form>
					<?php
		}
		?>
				<p class="submit" style="clear: both;">
				<?php
		if ( $current != 'tools' && $current != 'statistics' && $current != 'documentation' && $current != 'log' && $current != 'donate' ) {
			?>
	  		<input type="submit" name="Submit" class="button-primary"
							value="<?php echo (($current == 'cloudflare')?"Update Settings and Test CloudFlare connection":"Update Settings"); ?>" />
						<input type="hidden" name="wpss-config-submit" value="Y" />
					</p>
	
				</form>
	   		<?php
		}
		?>
				</div>
		</div>
		<!-- post-body -->
	</div>
	<!-- poststuff -->
	<div style="clear: both;"></div>
	<?php
}
