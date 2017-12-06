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

		// Don't send submit for review emails in Duplicate and Merge Posts.
		add_filter( 'duplicate_post_notification_message', '__return_false' );
		// Don't enable the submit for review feature in Duplicate and Merge Posts.
		add_filter( 'duplicate_post_allow_submit_for_review', '__return_false' );

		add_filter( 'http_request_args', array( $this, 'hide_custom_themes_from_update_check' ), 10, 2 );

		// Taxonomy related hooks.
		add_action( 'init', array( $this, 'add_taxonomies_to_pages' ) );
		add_action( 'init', array( $this, 'add_taxonomies_to_media' ) );
		add_action( 'init', array( $this, 'register_university_center_taxonomies' ), 20 );

		add_action( 'init', array( $this, 'add_excerpts_to_pages' ) );

		// Editorial Access Manager.
		add_filter( 'eam_post_types', array( $this, 'filter_eam_post_types' ), 10 );

		// WP Document Revisions.
		add_action( 'init', array( $this, 'add_document_revisions_visibility_support' ), 12 );
		add_filter( 'wsuwp_content_visibility_caps', array( $this, 'add_document_revisions_visibility_caps' ), 10 );
		add_filter( 'document_revisions_enable_webdav', '__return_false' );

		add_filter( 'wp_redirect', array( $this, 'prevent_unauthorized_plugin_redirect' ) );
		add_filter( 'option_wpseo', array( $this, 'filter_wpseo_options' ) );
		add_filter( 'wpseo_submenu_pages', array( $this, 'filter_wpseo_submenu' ) );
		add_action( 'init', array( $this, 'remove_wpseo_admin_bar_menu' ), 99 );
		add_filter( 'all_plugins', array( $this, 'all_plugins' ), 10 );

		add_filter( 'user_has_cap', array( $this, 'user_can_switch_users' ), 10, 4 );

		add_filter( 'wsuwp_sso_create_new_user', array( $this, 'create_auto_users' ) );
		add_filter( 'wsuwp_sso_create_new_network_user', '__return_true' );

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
	 * Add excerpt support to pages.
	 *
	 * @since 1.1.7
	 */
	public function add_excerpts_to_pages() {
		add_post_type_support( 'page', 'excerpt' );
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
			'wsu-people-directory/wsu-people-directory.php' => array(
				'dev.people.wsu.edu/',
				'people.wsu.edu/',
				'people.wsu.dev/',
				'stage.murrow.wsu.edu/',
				'dev.murrow.wsu.edu/',
				'murrow.wsu.edu/',
				'nursing.wsu.edu/',
				'stage.pharmacy.wsu.edu/',
				'physics.wsu.edu/',
				'stage.physics.wsu.edu/',
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
				'stage.pharmacy.wsu.edu', // Remove once pharmacy.wsu.edu is live.
				'pharmacy.wsu.edu',
			),
			'wsu-idonate/wsuwp-plugin-idonate.php' => array(
				'foundation.wsu.edu/',
				'hub.wsu.edu/foundation-sandbox/',
			),
			'wsuwp-rest-email-proxy/plugin.php' => array(), // Only allow on main site.
			'wp-api-menus/wp-api-menus.php' => array(
				'financialaid.wsu.edu/',
				'stage.pharmacy.wsu.edu/',
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
			'wsuwp-extended-polylang/plugin.php' => array(
				'financialaid.wsu.edu/',
			),
			'polylang/polylang.php' => array(
				'financialaid.wsu.edu/',
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
