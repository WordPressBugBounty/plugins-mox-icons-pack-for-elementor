<?php
/**
 * Admin settings page handler.
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MoxIcon_Admin {

	/**
	 * Hook suffix of the settings screen.
	 */
	const SCREEN = 'toplevel_page_mox-icon-settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . MOX_ICON_BASENAME, array( $this, 'add_action_links' ) );
	}

	public function add_menu_page() {
		add_menu_page(
			__( 'Mox Icons Settings', 'mox-icons-pack-for-elementor' ),
			__( 'Mox Icons', 'mox-icons-pack-for-elementor' ),
			'manage_options',
			'mox-icon-settings',
			array( $this, 'render_settings_page' ),
			MOX_ICON_URL . 'assets/images/mox-icons-menu-icon.svg',
			80 // Position
		);
	}

	public function register_settings() {
		register_setting(
			'mox_icon_settings_group',
			'mox_icon_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => MoxIcon::get_default_settings(),
				'show_in_rest'      => false,
			)
		);
	}

	public function sanitize_settings( $input ) {
		$input     = is_array( $input ) ? $input : array();
		$keys      = array_keys( MoxIcon::get_library_definitions() );
		$sanitized = array();
		foreach ( $keys as $key ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}
		return $sanitized;
	}

	public function enqueue_admin_assets( $hook ) {
		// The admin menu icon needs styling on every screen, so it rides along
		// with core's admin-menu stylesheet instead of costing its own request.
		wp_add_inline_style( 'admin-menu', $this->get_menu_icon_css() );

		if ( self::SCREEN !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'mox-icon-admin',
			MOX_ICON_URL . 'admin/css/mox-icon-admin.css',
			array(),
			MOX_ICON_VERSION
		);
		wp_enqueue_script(
			'mox-icon-admin',
			MOX_ICON_URL . 'admin/js/mox-icon-admin.js',
			array( 'jquery' ),
			MOX_ICON_VERSION,
			true
		);
		wp_localize_script(
			'mox-icon-admin',
			'moxIconAdmin',
			array(
				'activeLabel'   => __( 'Active', 'mox-icons-pack-for-elementor' ),
				'inactiveLabel' => __( 'Inactive', 'mox-icons-pack-for-elementor' ),
			)
		);
	}

	/**
	 * CSS for the plugin's admin menu icon.
	 *
	 * @return string
	 */
	private function get_menu_icon_css() {
		return '#adminmenu #toplevel_page_mox-icon-settings .wp-menu-image{display:flex;align-items:center;justify-content:center}'
			. '#adminmenu #toplevel_page_mox-icon-settings .wp-menu-image img{display:block;width:18px;height:18px;padding:0;object-fit:contain;opacity:.85}'
			. '#adminmenu #toplevel_page_mox-icon-settings.current .wp-menu-image img,'
			. '#adminmenu #toplevel_page_mox-icon-settings.wp-has-current-submenu .wp-menu-image img,'
			. '#adminmenu #toplevel_page_mox-icon-settings:hover .wp-menu-image img{opacity:1}';
	}

	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=mox-icon-settings' ) ) . '">'
			. esc_html__( 'Settings', 'mox-icons-pack-for-elementor' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require_once MOX_ICON_PATH . 'admin/partials/mox-icon-admin-display.php';
	}
}
