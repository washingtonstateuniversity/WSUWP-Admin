<?php

class WSUWP_Admin {
	/**
	 * @since 1.0.0
	 *
	 * @var WSUWP_Admin
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance. Initiate hooks when
	 * called the first time.
	 *
	 * @since 1.0.0
	 *
	 * @return \WSUWP_Admin
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_Admin();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Adds hooks used by the plugin.
	 *
	 * @since 1.0.0
	 */
	public function setup_hooks() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include __DIR__ . '/class-wsu-cli-spine-option.php';
			WP_CLI::add_command( 'spine_option', 'WSU_CLI_Spine_Option' );
		}

		add_action( 'init', array( $this, 'remove_general_template_actions' ), 10 );

		add_filter( 'wp_kses_allowed_html', array( $this, 'filter_allowed_html_tags' ), 10, 1 );
		add_filter( 'manage_pages_columns', array( $this, 'add_last_updated_column' ) );
		add_filter( 'manage_posts_columns', array( $this, 'add_last_updated_column' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'last_updated_column_data' ), 10, 2 );
		add_filter( 'srm_max_redirects', array( $this, 'srm_max_redirects' ), 10, 1 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_project_site' ), 10, 3 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_sites_site' ), 10, 3 );
		add_action( 'wpmu_new_blog', array( $this, 'preconfigure_sites_with_s3_uploads' ), 10, 3 );
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

		// Editorial Access Manager.
		add_filter( 'eam_post_types', array( $this, 'filter_eam_post_types' ), 10 );

		// WP Document Revisions.
		add_action( 'init', array( $this, 'add_document_revisions_visibility_support' ), 12 );
		add_filter( 'wsuwp_content_visibility_caps', array( $this, 'add_document_revisions_visibility_caps' ), 10 );
		add_filter( 'document_revisions_enable_webdav', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'document_revisions_headers' ), 10, 1 );

		add_filter( 'wp_redirect', array( $this, 'prevent_unauthorized_plugin_redirect' ) );
		add_filter( 'option_wpseo', array( $this, 'filter_wpseo_options' ) );
		add_filter( 'wpseo_submenu_pages', array( $this, 'filter_wpseo_submenu' ) );
		add_action( 'init', array( $this, 'remove_wpseo_admin_bar_menu' ), 99 );
		add_filter( 'all_plugins', array( $this, 'all_plugins' ), 10 );

		add_filter( 'user_has_cap', array( $this, 'user_can_switch_users' ), 10, 4 );

		add_filter( 'wsuwp_sso_create_new_user', array( $this, 'create_auto_users' ) );
		add_filter( 'wsuwp_sso_create_new_network_user', '__return_true' );

		add_filter( 'nocache_headers', array( $this, 'filter_404_no_cache_headers' ), 10 );
		add_filter( 'post_password_expires', array( $this, 'filter_post_password_expires' ) );

		add_filter( 'tablepress_wp_search_integration', '__return_false' );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'wp39550_disable_real_mime_check' ), 10, 4 );
	}

	/**
	 * Removes several default actions added by WordPress to output RSD, manifest, and
	 * shortlink information in the header.
	 *
	 * @since 0.6.18
	 */
	public function remove_general_template_actions() {
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
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
		$columns = array_merge( $columns, array(
			'wsu_last_updated' => 'Last Updated',
		) );

		return $columns;
	}

	/**
	 * Filters the post types managed with Editorial Access Manager.
	 *
	 * @since 1.0.7
	 *
	 * @return array
	 */
	public function filter_eam_post_types() {
		return array(
			'post' => 'post',
			'page' => 'page',
			'document' => 'document',
			'tribe_events' => 'tribe_events',
			'idonate_fund' => 'idonate_fund',
			'gs-factsheet' => 'gs-factsheet',
		);
	}

	/**
	 * Add support for WSUWP Content Visibility to WP Document Revisions.
	 */
	public function add_document_revisions_visibility_support() {
		add_post_type_support( 'document', 'wsuwp-content-visibility' );
	}

	/**
	 * Modifies the list of capabilities monitored by WSUWP Content Visibility and adds
	 * support for WP Document Revisions.
	 *
	 * @param array $caps
	 *
	 * @return array
	 */
	public function add_document_revisions_visibility_caps( $caps ) {
		$caps[] = 'read_document';

		return $caps;
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
		$revisions = wp_get_post_revisions( $post_id, array(
			'numberposts' => 1,
		) );

		// Calculate the last updated display based on our current timezone.
		$current_time = time() + ( get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

		foreach ( $revisions as $revision ) {
			echo esc_html( get_the_author_meta( 'display_name', $revision->post_author ) );
			echo '<br>';

			// If within 24 hours, show a human readable version instead
			if ( ( $current_time - strtotime( $revision->post_date ) ) < DAY_IN_SECONDS ) {
				echo esc_html( human_time_diff( $current_time, strtotime( $revision->post_date ) ) . ' ago' );
			} else {
				echo esc_html( date( 'Y/m/d', strtotime( $revision->post_date ) ) );
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
		return 2000;
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
		if ( ! in_array( $domain, array( 'project.wsu.edu', 'project.wsu.dev' ), true ) ) {
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
		update_option( 'widget_mention_me', array(
			2 => array(
				'title' => '',
				'num_to_show' => 5,
				'avatar_size' => 32,
				'show_also_post_followups' => false,
				'show_also_comment_followups' => false,
			),
			'_multiwidget' => 1,
		) );

		update_option( 'widget_p2_recent_tags', array(
			2 => array(
				'title' => '',
				'num_to_show' => 15,
			),
			'_multiwidget' => 1,
		) );

		update_option( 'widget_p2_recent_comments', array(
			2 => array(
				'title' => '',
				'num_to_show' => 5,
				'avatar_size' => 32,
			),
			'_multiwidget' => 1,
		) );

		update_option( 'sidebars_widgets', array(
			'wp_inactive_widgets' => array(),
			'sidebar-1' => array(
				0 => 'search-2',
				1 => 'mention_me-2',
				2 => 'p2_recent_tags-2',
				3 => 'p2_recent_comments-2',
				4 => 'recent-posts-2',
			),
			'sidebar-2' => array(),
			'sidebar-3' => array(),
			'array_version' => 3,
		) );

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
		if ( ! in_array( $domain, array( 'sites.wsu.edu', 'sites.wsu.dev' ), true ) ) {
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
	 * Sets new sites on specific domains to use S3 uploads on creation.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $blog_id
	 * @param int    $user_id
	 * @param string $domain
	 */
	public function preconfigure_sites_with_s3_uploads( $blog_id, $user_id, $domain ) {
		$forced_s3_domains = array(
			'labs.wsu.edu',
			'project.wsu.edu',
			'sites.wsu.edu',
			'hub.wsu.edu',
			'faculty.business.wsu.edu',
		);

		if ( ! in_array( $domain, $forced_s3_domains, true ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		update_option( 's3_uploads_enabled', 'enabled' );
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

		if ( ! isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) {
			return true;
		}

		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );

		$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
		if ( 0 !== strpos( $hash, '$P$B' ) ) {
			return true;
		}

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

		/**
		 * mime_content_type() is not handled by the S3 stream wrapper, so we
		 * handle content type detection differently when S3 uploads are enabled.
		 */
		if ( function_exists( 's3_uploads_enabled' ) && s3_uploads_enabled() ) {
			include_once __DIR__ . '/upstream-file-mime-type-mapping.php';
			$mime_type_mapping = wsuwp_file_default_mimetype_mapping();
			$mime_type = S3_Uploads_Local_Stream_Wrapper::getMimeType( $file, $mime_type_mapping );
		} else {
			$mime_type = mime_content_type( $file );
		}

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

		$r['body']['themes'] = wp_json_encode( $themes );

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
			foreach ( $uc_content_types as $uc_content_type ) {
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
			'make-plus/make-plus.php',
			'wp-api-menus/wp-api-menus.php',
			'wordpress-seo/wp-seo.php',
		);

		/**
		 * Some plugins should not be network activated.
		 */
		if ( is_network_admin() ) {
			foreach ( $site_only_plugins as $site_only_plugin ) {
				if ( isset( $plugins[ $site_only_plugin ] ) ) {
					unset( $plugins[ $site_only_plugin ] );
				}
			}
		}

		$current_site_address = $current_blog->domain . $current_blog->path;

		// Allow all plugins at the top level site in production and dev.
		if ( 'wp.wsu.edu/' === $current_site_address || 'wp.wsu.dev' === $current_blog->domain ) {
			return $plugins;
		}

		$plugin_access_list = array(
			'buddypress/bp-loader.php' => array(
				'dev.hub.wsu.edu/murrow/',
				'hub.wsu.edu/murrow-alumni/',
				'connect.murrow.wsu.edu/',
				'magazine.wsu.edu/mystory/',
			),
			'wsuwp-json-web-template/wsuwp-json-web-template.php' => array(
				'dev.admission.wsu.edu/',
				'stage.admission.wsu.edu/',
				'admission.wsu.edu/',
			),
			'co-authors-plus/co-authors-plus.php' => array(
				'dev.magazine.wsu.edu/',
				'magazine.wsu.edu/',
				'stage.magazine.wsu.edu/',
				'hydrogen.wsu.edu/',
				'wp.wsu.dev/magazine/',
			),
			'woocommerce/woocommerce.php' => array(
				'ucomm.wsu.edu/promos/',
				'stage.wsupress.wsu.edu/',
				'wsupress.wsu.edu/',
				'dev.wsupress.wsu.edu/',
				'wp.wsu.dev/wsupress/',
			),
			'wsuws-woocommerce-payment-gateway/wsuws-woocommerce-payment-gateway.php' => array(
				'stage.wsupress.wsu.edu/',
				'wsupress.wsu.edu/',
			),
			'make-plus/make-plus.php' => array(
				'aswsu.wsu.edu/',
			),
			'wsu-people-directory/wsu-people-directory.php' => array(
				'dev.people.wsu.edu/',
				'people.wsu.edu/',
				'people.wsu.dev/',
				'stage.murrow.wsu.edu/',
				'dev.murrow.wsu.edu/',
				'murrow.wsu.edu/',
			),
			'wsuwp-ucomm-asset-request/wsu-ucomm-assets-registration.php' => array(
				'ucomm.wsu.edu/',
				'dev.ucomm.wsu.edu/',
			),
			'wsuwp-tls/wsuwp-tls.php' => array(
				'wp2.wsu.edu/',
			),
			'wsu-news-announcements/wsu-news-announcements.php' => array(
				'news.wsu.edu/',
				'news.wsu.edu/announcements/',
			),
			'wsuwp-deployment/wsuwp-deployment.php' => array(
				'wp2.wsu.edu/',
			),
			'the-events-calendar-community-events/tribe-community-events.php' => array(
				'calendar.wsu.edu/',
				'nursing.wsu.edu/',
				'momsweekend.wsu.edu/',
				'footballweekends.wsu.edu/',
				'research.wsu.edu/',
			),
			'wsu-idonate/wsuwp-plugin-idonate.php' => array(
				'foundation.wsu.edu/',
				'hub.wsu.edu/foundation-sandbox/',
			),
			'wsuwp-rest-email-proxy/plugin.php' => array(), // Only allow on main site.
			'wp-api-menus/wp-api-menus.php' => array(
				'financialaid.wsu.edu/'
			),
			'wordpress-seo/wp-seo.php' => array(
				'spokane.wsu.edu/',
				'nursing.wsu.edu/',
				'ip.wsu.edu/',
				'education.wsu.edu/',
				'cereo.wsu.edu/',
				'provost.wsu.edu/',
				'advance.wsu.edu/',
				'economicdevelopment.wsu.edu/',
				'ascc.wsu.edu/',
				'mcnair.wsu.edu/',
				'mps.wsu.edu/',
				'materials.wsu.edu/',
				'finearts.wsu.edu/',
				'business.wsu.edu/powerbreakfast/',
				'summer.wsu.edu/',
				'forlang.wsu.edu/',
				'puyallup.wsu.edu/lnm/',
				'puyallup.wsu.edu/',
				'puyallup.wsu.edu/lcs/',
				'puyallup.wsu.edu/agbuffers/',
				'puyallup.wsu.edu/ecards/',
				'puyallup.wsu.edu/ecotoxicology/',
				'puyallup.wsu.edu/hort/',
				'puyallup.wsu.edu/plantclinic/',
				'puyallup.wsu.edu/poplar/',
				'ppo.puyallup.wsu.edu/',
				'puyallup.wsu.edu/soils/',
				'puyallup.wsu.edu/turf/',
				'puyallup.wsu.edu/water/',
				'nutrition.wsu.edu/',
				'schoolipm.wsu.edu/',
				'vcea.wsu.edu/fiz/',
				'etm.wsu.edu/',
				'hub.wsu.edu/marsha/',
				'hws.wsu.edu/',
				'spokane.wsu.edu/research/',
				'spokane.wsu.edu/extra/',
				'methane.wsu.edu/',
				'spokane.wsu.edu/studentaffairs/',
				'spokane.wsu.edu/future/',
				'spokane.wsu.edu/current/',
				'spokane.wsu.edu/academic/',
				'spokane.wsu.edu/studentlife/',
				'pnwcosmos.org/',
				'cougarsuccess.wsu.edu/',
				'nursing.wsu.edu/research/',
				'business.wsu.edu/cougfund/',
				'news.wsu.edu/',
				'askdruniverse.wsu.edu/',
				'ip.wsu.edu/learn-english/',
				'ip.wsu.edu/on-campus/',
				'spokane.wsu.edu/library/',
				'wsu.edu/',
				'spokane.wsu.edu/studentinvolvement/',
				'labs.wsu.edu/sprc/',
				'business.wsu.edu/dividend/',
				'spokane.wsu.edu/about/',
				'spokane.wsu.edu/communications/',
				'spokane.wsu.edu/alumniandfriends/',
				'ppo.puyallup.wsu.edu/sod/',
				'ppo.puyallup.wsu.edu/bcf/',
				'ppo.puyallup.wsu.edu/ct/',
				'ppo.puyallup.wsu.edu/pmr/',
				'ppo.puyallup.wsu.edu/net/',
				'studentinsurance.wsu.edu/',
				'counsel.wsu.edu/',
				'adcaps.wsu.edu/',
				'spokane.wsu.edu/facilities/',
				'business.wsu.edu/vancouver-blog/',
				'forlang.wsu.edu/newsletter/',
				'spokane.wsu.edu/emergency-management/',
				'labs.wsu.edu/vandam/',
				'spokane.wsu.edu/hr/',
				'spokane.wsu.edu/campus-security/',
				'wsu.edu/impact/',
				'techservices.wsu.edu/',
				'spokane.wsu.edu/alert/',
				'spokane.wsu.edu/its/',
				'firstyear.wsu.edu/',
				'labs.wsu.edu/m3robotics/',
				'wstwinregistry.org/',
				'labs.wsu.edu/guedes/',
				'spokane.wsu.edu/wellness/',
				'campaign.wsu.edu/',
				'medicine.wsu.edu/features/',
				'labs.wsu.edu/knoblauch/',
				'puyallup.wsu.edu/smallfruit/',
				'labs.wsu.edu/gcwblab/',
				'labs.wsu.edu/populationgenomics/',
				'innovators.wsu.edu/',
				'solardec.wsu.edu/',
				'healthcomm.murrow.wsu.edu/',
				'summeradmin.wsu.edu/',
				'hub.wsu.edu/justin-hope-playground/',
				'hub.wsu.edu/jeremy/',
				'teachingacademy.westregioncvm.org/',
				'business.wsu.edu/print/',
				'hub.wsu.edu/ssheilah/',
				'gradschool.wsu.edu/pdi/',
				'cewp.wsu.edu/',
				'gradschool.wsu.edu/',
			),
		);

		foreach ( $plugin_access_list as $plugin_key => $plugin_sites ) {
			if ( ! in_array( $current_site_address, $plugin_sites, true ) && isset( $plugins[ $plugin_key ] ) ) {
				unset( $plugins[ $plugin_key ] );
			}
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
			if ( $user && wsuwp_is_global_admin( $user->ID ) ) {
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
	 * Remove default "no cache headers" added by WordPress for 404 pages.
	 *
	 * @param $headers
	 *
	 * @return mixed
	 */
	public function filter_404_no_cache_headers( $headers ) {
		global $wp_query;

		if ( $wp_query->is_404 && isset( $headers['Pragma'] ) ) {
			unset( $headers['Expires'] );
			unset( $headers['Cache-Control'] );
			unset( $headers['Pragma'] );
		}

		return $headers;
	}

	/**
	 * Filters the post password cookie to be a session cookie rather than one
	 * that expires in 10 days.
	 *
	 * @param int $expires The number of seconds the post password expires cookie is valid for.
	 *
	 * @return int Modified post password expires value.
	 */
	public function filter_post_password_expires( $expires ) {
		return 0;
	}

	/**
	 * Restores the ability to upload non-image files in WordPress 4.7.1 and 4.7.2.
	 *
	 * This is temporary until https://core.trac.wordpress.org/ticket/39550 is fixed.
	 *
	 * Thanks to Sergey! http://profiles.wordpress.org/sergeybiryukov/
	 *
	 * @param $data
	 * @param $file
	 * @param $filename
	 * @param $mimes
	 *
	 * @return array
	 */
	public function wp39550_disable_real_mime_check( $data, $file, $filename, $mimes ) {
		$wp_filetype = wp_check_filetype( $filename, $mimes );

		$ext = $wp_filetype['ext'];
		$type = $wp_filetype['type'];
		$proper_filename = $data['proper_filename'];

		return compact( 'ext', 'type', 'proper_filename' );
	}
}
