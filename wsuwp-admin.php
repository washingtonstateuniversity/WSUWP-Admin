<?php
/*
Plugin Name: WSU Admin
Plugin URI: https://web.wsu.edu/
Description: Customized portions of the admin area of WordPress for Washington State University
Author: washingtonstateuniversity, jeremyfelt
Version: 0.6.14
*/

class WSU_Admin {
	/**
	 * Setup hooks.
	 */
	public function __construct() {
		if ( defined('WP_CLI') && WP_CLI ) {
			include __DIR__ . '/includes/wp-cli-spine-option.php';
		}

		add_filter( 'wp_kses_allowed_html', array( $this, 'filter_allowed_html_tags' ), 10, 1 );
		add_filter( 'manage_pages_columns', array( $this, 'add_last_updated_column' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_last_updated_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_filter( 'srm_max_redirects', array( $this, 'srm_max_redirects' ), 10, 1 );
		add_filter( 'document_revisions_enable_webdav', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'document_revisions_headers' ), 10, 1 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_project_site' ), 10, 3 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_sites_site' ), 10, 3 );
		add_action( 'wsuwp_project_flush_rewrite_rules', array( $this, 'flush_rewrite_rules' ), 10 );

		// Don't send submit for review emails in Duplicate and Merge Posts.
		add_filter( 'duplicate_post_notification_message', '__return_false' );
		// Don't enable the submit for review feature in Duplicate and Merge Posts.
		add_filter( 'duplicate_post_allow_submit_for_review', '__return_false' );

		add_filter( 'http_request_args', array( $this, 'hide_custom_themes_from_update_check' ), 10, 2 );

		// Taxonomy related hooks.
		add_action( 'init', array( $this, 'add_taxonomies_to_pages' ) );
		add_action( 'init', array( $this, 'add_taxonomies_to_media' ) );
		add_action( 'init', array( $this, 'register_university_center_taxonomies' ), 20 );

		add_filter( 'wp_redirect', array( $this, 'prevent_unauthorized_plugin_redirect' ) );
		add_filter( 'option_wpseo', array( $this, 'filter_wpseo_options' ) );
		add_filter( 'wpseo_submenu_pages', array( $this, 'filter_wpseo_submenu' ) );
		add_action( 'init', array( $this, 'remove_wpseo_admin_bar_menu' ), 99 );
		add_filter( 'all_plugins', array( $this, 'all_plugins' ), 10 );

		add_filter( 'user_has_cap', array( $this, 'user_can_switch_users' ), 10, 4 );

		add_filter( 'wsuwp_sso_create_new_user', array( $this, 'create_auto_users' ) );

		// Prevent WordPress from dropping tables for a deleted site.
		add_filter( 'wpmu_drop_tables', '__return_empty_array' );

		// Adjust defaults included with Shortcake Bakery
		add_filter( 'shortcake_bakery_shortcode_classes', array( $this, 'filter_shortcake_bakery_shortcodes' ) );
		add_action( 'after_setup_theme', array( $this, 'remove_shortcode_bakery_embed_button' ), 999 );

		add_filter( 'nocache_headers', array( $this, 'filter_404_no_cache_headers' ), 10 );
	}

	/**
	 * Filter the allowed list of HTML tags for non super-admins.
	 *
	 * @param array $tags An associative array containing allowed tags and attributes.
	 *
	 * @return array Modified array of tags and attributes.
	 */
	public function filter_allowed_html_tags( $tags ) {
		$tags['div']['tabindex'] = true;

		return $tags;
	}

	/**
	 * Add a column to posts and pages for Last Updated.
	 *
	 * @param array $columns List of columns.
	 *
	 * @return array Modified list of columns.
	 */
	public function add_last_updated_column( $columns ) {
		$columns = array_merge( $columns, array( 'wsu_last_updated' => 'Last Updated' ) );

		return $columns;
	}

	/**
	 * Display last updated data in our custom posts and page table column.
	 *
	 * @param string $column  Column being output.
	 * @param int    $post_id ID of the post row being output.
	 */
	public function last_updated_column_data( $column, $post_id ) {
		if ( 'wsu_last_updated' !== $column ) {
			return;
		}

		// Retrieve the last revision for this post, which should also be the last updated record.
		$revisions = wp_get_post_revisions( $post_id, array( 'numberposts' => 1 ) );

		// Calculate the last updated display based on our current timezone.
		$current_time = time() + ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

		foreach ( $revisions as $revision ) {
			echo get_the_author_meta('display_name', $revision->post_author );
			echo '<br>';

			// If within 24 hours, show a human readable version instead
			if ( ( $current_time - strtotime( $revision->post_date ) ) < DAY_IN_SECONDS ) {
				echo human_time_diff( $current_time, strtotime( $revision->post_date ) ) . ' ago';
			} else {
				echo date( 'Y/m/d', strtotime( $revision->post_date ) );
			}
			break;
		}
	}

	/**
	 * Filter the number of redirects supported by Safe Redirect Manager from the default of 150.
	 *
	 * @return int Number of redirects supported.
	 */
	public function srm_max_redirects() {
		return 500;
	}

	/**
	 * Preconfigure a Project site to reduce the overall setup experience.
	 *
	 *     - Use latest posts instead of page on front.
	 *     - Restrict to logged in users by default.
	 *     - Use the WSU Project (P2) theme rather than the Spine.
	 *     - Force HTTPS
	 *     - Configure default P2 related widgets in the sidebar.
	 *     - Flush rewrite rules.
	 *
	 * @param int    $blog_id ID of the site being created.
	 * @param int    $user_id ID of the user creating the site.
	 * @param string $domain  Domain of the site being created.
	 */
	public function preconfigure_project_site( $blog_id, $user_id, $domain ) {
		// Only apply these defaults to project sites.
		if ( ! in_array( $domain, array( 'project.wsu.edu', 'project.wsu.dev' ) ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		// Show posts on the front page rather than a page.
		update_option( 'show_on_front', 'posts' );

		// Activate the WSU Project theme by default.
		update_option( 'stylesheet', 'p2-wsu' );
		update_option( 'template', 'p2-wsu' );

		// Restrict access to logged in users only.
		update_option( 'blog_public', 2 );

		// We're only prepared for SSL on production.
		if ( 'project.wsu.edu' === $domain ) {
			// Replace HTTP with HTTPS in the site and home URLs.
			$site_url = get_option( 'siteurl' );
			$site_url = str_replace( 'http://', 'https://', $site_url );
			update_option( 'siteurl', $site_url );
			$home_url = get_option( 'home' );
			$home_url = str_replace( 'http://', 'https://', $home_url );
			update_option( 'home', $home_url );
		}

		// Setup common P2 widgets.
		update_option( 'widget_mention_me', array( 2 => array( 'title' => '', 'num_to_show' => 5, 'avatar_size' => 32, 'show_also_post_followups' => false, 'show_also_comment_followups' => false ), '_multiwidget' => 1 ) );
		update_option( 'widget_p2_recent_tags', array( 2 => array( 'title' => '', 'num_to_show' => 15 ), '_multiwidget' => 1 ) );
		update_option( 'widget_p2_recent_comments', array( 2 => array( 'title' => '', 'num_to_show' => 5, 'avatar_size' => 32 ), '_multiwidget' => 1 ) );
		update_option( 'sidebars_widgets',       array ( 'wp_inactive_widgets' => array (), 'sidebar-1' => array ( 0 => 'search-2', 1 => 'mention_me-2', 2 => 'p2_recent_tags-2', 3 => 'p2_recent_comments-2', 4 => 'recent-posts-2' ), 'sidebar-2' => array (), 'sidebar-3' => array (), 'array_version' => 3 ) );

		wp_schedule_single_event( time() + 5, 'wsuwp_project_flush_rewrite_rules' );
		wp_cache_delete( 'alloptions', 'options' );
		restore_current_blog();

		refresh_blog_details( $blog_id );
	}

	/**
	 * Preconfigure a student portfolio site to reduce the overall setup experience.
	 *
	 *     - Use latest posts instead of page on front.
	 *     - Restrict to logged in users by default.
	 *     - Force HTTPS
	 *
	 * @param int    $blog_id ID of the site being created.
	 * @param int    $user_id ID of the user creating the site.
	 * @param string $domain  Domain of the site being created.
	 */
	public function preconfigure_sites_site( $blog_id, $user_id, $domain ) {
		// Only apply these defaults to sites sites. ;)
		if ( ! in_array( $domain, array( 'sites.wsu.edu', 'sites.wsu.dev' ) ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		// Show posts on the front page rather than a page. Sites are primarily for
		// student portfolios and will likely have a log format.
		update_option( 'show_on_front', 'posts' );

		// Restrict access to logged in users only.
		update_option( 'blog_public', 2 );

		// We're prepared for SSL everywhere on production.
		if ( 'sites.wsu.edu' === $domain ) {
			// Replace HTTP with HTTPS in the site and home URLs.
			$site_url = get_option( 'siteurl' );
			$site_url = str_replace( 'http://', 'https://', $site_url );
			update_option( 'siteurl', $site_url );
			$home_url = get_option( 'home' );
			$home_url = str_replace( 'http://', 'https://', $home_url );
			update_option( 'home', $home_url );
		}

		wp_cache_delete( 'alloptions', 'options' );
		restore_current_blog();

		refresh_blog_details( $blog_id );
	}

	/**
	 * Flush the rewrite rules on the site.
	 */
	public function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Check if the entry of a password is still required for a document.
	 *
	 * This is a duplicate of the WordPress function and is used only because
	 * we don't have a proper `$post` object built with `post_password`
	 * included when we need to do our check.
	 *
	 * @see WordPress post_password_required()
	 *
	 * @param string $post_password Password possibly assigned to a post.
	 *
	 * @return bool True if a password is required. False if not.
	 */
	private function post_password_required( $post_password ) {
		if ( empty( $post_password ) ) {
			return false;
		}

		if ( ! isset( $_COOKIE['wp-postpass_' . COOKIEHASH] ) ) {
			return true;
		}

		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );

		$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		if ( 0 !== strpos( $hash, '$P$B' ) )
			return true;

		return ! $hasher->CheckPassword( $post_password, $hash );
	}

	/**
	 * Determine what Content-Type header should be sent with a document revisions
	 * request. If we don't filter this, text/html is used by default as WordPress
	 * sets the header before the WP Document Revisions plugin is able to.
	 *
	 * @global wpdb $wpdb
	 *
	 * @param array $headers List of headers currently set for this request.
	 *
	 * @return array Modified list of headers.
	 */
	public function document_revisions_headers( $headers ) {
		global $wpdb, $wp;

		if ( 'documents/([0-9]{4})/([0-9]{1,2})/([^.]+)\.[A-Za-z0-9]{3,4}/?$' !== $wp->matched_rule ) {
			return $headers;
		}

		// Only modify headers for document revisions.
		if ( isset( $wp->query_vars['post_type'] ) && 'document' !== $wp->query_vars['post_type'] ) {
			return $headers;
		}

		// Retrieve post_content for the post matching this document request. This post_content is really
		// the ID of the attachment the document is a mask for.
		$post_data = $wpdb->get_row( $wpdb->prepare( "SELECT post_content, post_password FROM $wpdb->posts WHERE post_type='document' AND post_name = %s", sanitize_title( $wp->query_vars['name'] ) ) );
		$post_id = absint( $post_data->post_content );
		$post_password = $post_data->post_password;

		if ( empty( absint( $post_id ) ) ) {
			return $headers;
		}

		// If the document has a password assigned and the cookie does not exist, don't modify.
		if ( $this->post_password_required( $post_password ) ) {
			return $headers;
		}

		// Remove the default WordPress Link header.
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

		// Remove the WP-API LINK header.
		remove_action( 'template_redirect', 'json_output_link_header', 11 );

		$file = get_attached_file( $post_id );

		// No file exists to load.
		if ( empty( $file ) ) {
			return $headers;
		}

		$mime_type = mime_content_type( $file );
		$file_size = filesize( $file );
		if ( empty( $mime_type ) ) {
			return $headers;
		}

		$headers['Content-Length'] = $file_size;
		$headers['Content-Type'] = $mime_type;
		$headers['Accept-Ranges'] = 'bytes';

		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Catch any checks for theme updates and remove our custom themes from the object. We don't
	 * want to deal with false positives because of themes in the repository with the same slug.
	 *
	 * @param array  $r   Arguments being passed to this HTTP request.
	 * @param string $url URL being used for the HTTP request.
	 *
	 * @return array Modified list of arguments.
	 */
	public function hide_custom_themes_from_update_check( $r, $url ) {
		if ( 0 !== strpos( $url, 'https://api.wordpress.org/themes/update-check' ) ) {
			return $r;
		}

		$themes = json_decode( $r['body']['themes'] );

		unset( $themes->themes->spine );
		unset( $themes->themes->brand );

		// Non-Spine CAHNRS Themes
		unset( $themes->themes->wsu );
		unset( $themes->themes->wip );

		$r['body']['themes'] = json_encode( $themes );

		return $r;
	}

	/**
	 * Register built in taxonomies - Categories and Tags - to pages.
	 */
	public function add_taxonomies_to_pages() {
		register_taxonomy_for_object_type( 'category', 'page' );
		register_taxonomy_for_object_type( 'post_tag', 'page' );
	}

	/**
	 * Register built in taxonomies - Categories and Tags - to media.
	 */
	public function add_taxonomies_to_media() {
		register_taxonomy_for_object_type( 'category', 'attachment' );
		register_taxonomy_for_object_type( 'post_tag', 'attachment' );
	}

	/**
	 * Register our central taxonomies as applicable to the content types created by
	 * the University Center plugin.
	 */
	public function register_university_center_taxonomies() {
		if ( function_exists( 'wsuwp_uc_get_object_type_slugs' ) ) {
			$uc_content_types = wsuwp_uc_get_object_type_slugs();
			foreach( $uc_content_types as $uc_content_type ) {
				register_taxonomy_for_object_type( 'wsuwp_university_category', $uc_content_type );
				register_taxonomy_for_object_type( 'wsuwp_university_location', $uc_content_type );
			}
		}
	}

	/**
	 * Prevent unauthorized redirects from some plugins.
	 *
	 * @param string $location The URL string for redirect.
	 *
	 * @return bool|string False if a redirect is not desired. The original string if it is.
	 */
	public function prevent_unauthorized_plugin_redirect( $location ) {
		$avoid_url = admin_url( 'admin.php?page=wpseo_dashboard&intro=1' );

		if ( $location === $avoid_url ) {
			return false;
		}

		return $location;
	}

	/**
	 * Provide overriding options for some WordPress SEO settings.
	 *
	 * @param array $option
	 *
	 * @return array
	 */
	public function filter_wpseo_options( $option ) {
		$override = array(
			'ignore_tour' => true,
			'tracking_popup_done' => true,
			'seen_about' => true,
			'yoast_tracking' => false,
			'disableadvanced_meta' => true,
		);
		$option = wp_parse_args( $override, $option );

		return $option;
	}

	/**
	 * Remove the advanced and licenses sub-menus from WordPress SEO as access to
	 * these is not necessary throughout the platform.
	 *
	 * @param array $menu
	 *
	 * @return array
	 */
	public function filter_wpseo_submenu( $menu ) {
		foreach ( $menu as $key => $val ) {
			if ( isset( $val[4] ) && 'wpseo_advanced' === $val[4] ) {
				unset( $menu[ $key ] );
			}

			if ( isset( $val[4] ) && 'wpseo_licenses' === $val[4] ) {
				unset( $menu[ $key ] );
			}
		}

		return $menu;
	}

	/**
	 * Remove the admin bar menu inserted by WordPress SEO. Ours is too
	 * custom to accomodate this.
	 */
	public function remove_wpseo_admin_bar_menu() {
		remove_action( 'admin_bar_menu', 'wpseo_admin_bar_menu', 95 );
	}

	/**
	 * Manage a poor, manual access list for some plugins.
	 *
	 * @param array $plugins List of plugins found.
	 *
	 * @return array Modified list of plugins.
	 */
	public function all_plugins( $plugins ) {
		global $current_blog;

		$site_only_plugins = array(
			'wsuwp-deployment/wsuwp-deployment.php',
			'buddypress/bp-loader.php',
			'wsuwp-json-web-template/wsuwp-json-web-template.php',
			'co-authors-plus/co-authors-plus.php',
			'woocommerce/woocommerce.php',
		);

		$wt_allowed_sites = array(
			'wp.wsu.edu/',
			'dev.admission.wsu.edu/',
			'stage.admission.wsu.edu/',
			'admission.wsu.edu/',
		);

		$bp_allowed_sites = array(
			'wp.wsu.edu/',
			'dev.hub.wsu.edu/murrow/',
			'hub.wsu.edu/murrow-alumni/',
			'connect.murrow.wsu.edu/',
			'magazine.wsu.edu/mystory/',
		);

		$cap_allowed_sites = array(
			'wp.wsu.edu/',
			'dev.magazine.wsu.edu/',
			'magazine.wsu.edu/',
			'stage.magazine.wsu.edu/',
			'hydrogen.wsu.edu/',
			'wp.wsu.dev/',
			'wp.wsu.dev/magazine/',
		);

		$woo_allowed_sites = array(
			'wp.wsu.edu/',
			'wp.wsu.dev/',
			'ucomm.wsu.edu/promos/',
		);

		$people_allowed_sites = array(
			'wp.wsu.edu/',
			'dev.people.wsu.edu/',
			'people.wsu.edu/',
			'wp.wsu.dev/',
			'people.wsu.dev/',
		);

		$ucomm_assets_allowed_sites = array(
			'wp.wsu.edu/',
			'ucomm.wsu.edu/',
			'dev.ucomm.wsu.edu/',
			'wp.wsu.dev/',
		);

		$wsuwp_tls_allowed_sites = array(
			'wp.wsu.edu/',
			'wp2.wsu.edu/',
			'wp.wsu.dev/',
		);

		$wsu_news_announcements_allowed_sites = array(
			'wp.wsu.edu/',
			'news.wsu.edu/',
			'news.wsu.edu/announcements/',
			'wp.wsu.dev/',
		);

		$community_events_allowed_sites = array(
			'wp.wsu.edu/',
			'wp.wsu.dev/',
			'calendar.wsu.edu/',
			'nursing.wsu.edu/',
			'momsweekend.wsu.edu/',
			'footballweekends.wsu.edu/',
		);

		$wsuwp_deployment_allowed_sites = array(
			'wp.wsu.edu/',
			'wp2.wsu.edu/',
			'wp.wsu.dev/',
		);

		/**
		 * Some plugins should not be network activated.
		 */
		if ( is_network_admin() ) {
			foreach( $site_only_plugins as $site_only_plugin ) {
				if ( isset( $plugins[ $site_only_plugin ] ) ) {
					unset( $plugins[ $site_only_plugin ] );
				}
			}
		}

		/**
		 * BuddyPress is only allowed on specific sites at the moment.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $bp_allowed_sites ) && isset( $plugins['buddypress/bp-loader.php'] ) ) {
			unset( $plugins['buddypress/bp-loader.php'] );
		}

		/**
		 * WSUWP JSON Web Template is only allowed on specific sites at the moment.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $wt_allowed_sites ) && isset( $plugins['wsuwp-json-web-template/wsuwp-json-web-template.php'] ) ) {
			unset( $plugins['wsuwp-json-web-template/wsuwp-json-web-template.php'] );
		}

		/**
		 * Co Authors Plus is only allowed on specific sites at the moment.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $cap_allowed_sites ) && isset( $plugins['co-authors-plus/co-authors-plus.php'] ) ) {
			unset( $plugins['co-authors-plus/co-authors-plus.php'] );
		}

		/**
		 * WooCommerce is only allowed on specific sites at the moment.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $woo_allowed_sites ) && isset( $plugins['woocommerce/woocommerce.php'] ) ) {
			unset( $plugins['woocommerce/woocommerce.php'] );
		}

		/**
		 * WSU People Directory is only allowed on specific sites at the moment.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $people_allowed_sites ) && isset( $plugins['wsu-people-directory/wsu-people-directory.php'] ) ) {
			unset( $plugins['wsu-people-directory/wsu-people-directory.php'] );
		}

		/**
		 * The UComm Asset request plugin is only allowed on ucomm.wsu.edu and dev sites.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $ucomm_assets_allowed_sites ) && isset( $plugins['wsuwp-ucomm-asset-request/wsu-ucomm-assets-registration.php'] ) ) {
			unset( $plugins['wsuwp-ucomm-asset-request/wsu-ucomm-assets-registration.php'] );
		}

		/**
		 * WSUWP TLS is an admin plugin that should only be available on the main site.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $wsuwp_tls_allowed_sites ) && isset( $plugins['wsuwp-tls/wsuwp-tls.php'] ) ) {
			unset( $plugins['wsuwp-tls/wsuwp-tls.php'] );
		}

		/**
		 * The WSU News & Announcements plugin is made to work with WSU News only.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $wsu_news_announcements_allowed_sites ) && isset( $plugins['wsu-news-announcements/wsu-news-announcements.php'] ) ) {
			unset( $plugins['wsu-news-announcements/wsu-news-announcements.php'] );
		}

		/**
		 * The plugin we use to manage deployments should be restricted.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $wsuwp_deployment_allowed_sites ) && isset( $plugins['wsuwp-deployment/wsuwp-deployment.php'] ) ) {
			unset( $plugins['wsuwp-deployment/wsuwp-deployment.php'] );
		}

		/**
		 * The Events Calendar Community Events is restricted to meet license requirements.
		 */
		if ( ! in_array( $current_blog->domain . $current_blog->path, $community_events_allowed_sites ) && isset( $plugins['the-events-calendar-community-events/tribe-community-events.php'] ) ) {
			unset( $plugins['the-events-calendar-community-events/tribe-community-events.php'] );
		}

		return $plugins;
	}

	/**
	 * Determine if the user can switch users using the user switching plugin.
	 *
	 * @param array   $allcaps All capabilities set for the user right now.
	 * @param array   $caps    The capabilities being checked.
	 * @param array   $args    Arguments passed with the has_cap() call.
	 * @param WP_User $user    The current user being checked.
	 *
	 * @return array Modified list of capabilities for the user.
	 */
	public function user_can_switch_users( $allcaps, $caps, $args, $user ) {
		if ( 'switch_to_user' === $args[0] ) {
			if ( $user && wsuwp_is_global_admin( $user->ID  ) ) {
				$allcaps['switch_to_user'] = true;
			} else {
				unset( $allcaps['switch_to_user'] );
			}
		}

		return $allcaps;
	}

	/**
	 * Account for a manual flag to enable auto user creation when a user has a
	 * valid WSU NID. This will only work currently if the option has been set for
	 * a site via WP-CLI.
	 *
	 * @return bool
	 */
	public function create_auto_users() {
		$auto_users_enabled = get_option( 'wsu_enable_auto_users', false );

		if ( 'enabled' === $auto_users_enabled ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter the default list of shortcodes provided by the Shortcake Bakery plugin.
	 *
	 * @return array
	 */
	public function filter_shortcake_bakery_shortcodes() {
		return array(
			// 'Shortcake_Bakery\Shortcodes\ABC_News',
			// 'Shortcake_Bakery\Shortcodes\Facebook',
			'Shortcake_Bakery\Shortcodes\Flickr',
			'Shortcake_Bakery\Shortcodes\Giphy',
			// 'Shortcake_Bakery\Shortcodes\Guardian',
			// 'Shortcake_Bakery\Shortcodes\Iframe',
			'Shortcake_Bakery\Shortcodes\Image_Comparison',
			'Shortcake_Bakery\Shortcodes\Infogram',
			'Shortcake_Bakery\Shortcodes\Instagram',
			'Shortcake_Bakery\Shortcodes\Livestream',
			// 'Shortcake_Bakery\Shortcodes\Rap_Genius',
			'Shortcake_Bakery\Shortcodes\PDF',
			// 'Shortcake_Bakery\Shortcodes\Playbuzz',
			'Shortcake_Bakery\Shortcodes\Scribd',
			// 'Shortcake_Bakery\Shortcodes\Script',
			// 'Shortcake_Bakery\Shortcodes\Silk',
			'Shortcake_Bakery\Shortcodes\SoundCloud',
			'Shortcake_Bakery\Shortcodes\Twitter',
			// 'Shortcake_Bakery\Shortcodes\Videoo',
			'Shortcake_Bakery\Shortcodes\Vimeo',
			'Shortcake_Bakery\Shortcodes\Vine',
			'Shortcake_Bakery\Shortcodes\YouTube',
		);
	}

	/**
	 * Remove the "Insert Embed" button added by Shortcake Bakery and defer to the standard
	 * "Insert Media" button.
	 */
	public function remove_shortcode_bakery_embed_button() {
		if ( class_exists( 'Shortcake_Bakery' ) ) {
			remove_action( 'media_buttons', array( Shortcake_Bakery::get_instance(), 'action_media_buttons' ) );
		}
	}

	/**
	 * Remove default "no cache headers" added by WordPress for 404 pages.
	 *
	 * @param $headers
	 *
	 * @return mixed
	 */
	public function filter_404_no_cache_headers( $headers ) {
		global $wp_query;

		if ( $wp_query->is_404 && isset( $headers['Pragma'] ) ) {
			unset( $headers[ 'Expires' ] );
			unset( $headers[ 'Cache-Control' ] );
			unset( $headers[ 'Pragma' ] );
		}

		return $headers;
	}
}
new WSU_Admin();
