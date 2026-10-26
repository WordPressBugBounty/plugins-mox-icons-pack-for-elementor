<?php
/**
 * Plugin Name: Mox Icons - Icon Pack for Elementor
 * Plugin URI: https://moxcreative.com/
 * Description: Extend Elementor with 15 popular icon libraries including Heroicons, Material Design Icons, Lucide, Tabler Icons, Phosphor Icons, Bootstrap Icons, Iconoir, Feather Icons, Fluent UI Color Icons, Pixelarticons, Material Symbols Light, Myna UI Icons, and Health Icons.
 * Version: 1.1.7
 * Author: Moxcreative
 * Author URI: https://moxcreative.com/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mox-icons-pack-for-elementor
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bail early if PHP version is too old (runtime check).
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error is-dismissible"><p>';
		printf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			esc_html__( 'Mox Icons requires PHP %1$s or higher. Your server is running PHP %2$s. Please upgrade PHP or contact your hosting provider.', 'mox-icons-pack-for-elementor' ),
			'7.4',
			esc_html( PHP_VERSION )
		);
		echo '</p></div>';
	} );
	return;
}

// Plugin constants
define( 'MOX_ICON_VERSION', '1.1.7' );
define( 'MOX_ICON_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOX_ICON_URL', plugin_dir_url( __FILE__ ) );
define( 'MOX_ICON_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if Elementor is active before loading plugin.
 */
function mox_icon_check_elementor() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'mox_icon_elementor_missing_notice' );
		return;
	}
	if ( ! version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
		add_action( 'admin_notices', 'mox_icon_elementor_version_notice' );
		return;
	}

	$class_file = MOX_ICON_PATH . 'includes/class-mox-icon.php';
	if ( ! file_exists( $class_file ) ) {
		add_action( 'admin_notices', 'mox_icon_missing_files_notice' );
		return;
	}

	try {
		require_once $class_file;
		MoxIcon::instance();
	} catch ( \Exception $e ) {
		// Log the error for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Mox Icons: Failed to initialize — ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
		}
		add_action( 'admin_notices', function () use ( $e ) {
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo esc_html__( 'Mox Icons encountered an error during initialization. ', 'mox-icons-pack-for-elementor' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				echo '<br><code>' . esc_html( $e->getMessage() ) . '</code>';
			}
			echo '</p></div>';
		} );
	}
}
add_action( 'plugins_loaded', 'mox_icon_check_elementor' );

function mox_icon_elementor_missing_notice() {
	echo '<div class="notice notice-error is-dismissible"><p>';
	echo esc_html__( 'Mox Icons requires Elementor to be installed and activated.', 'mox-icons-pack-for-elementor' );
	echo '</p></div>';
}

function mox_icon_elementor_version_notice() {
	echo '<div class="notice notice-warning is-dismissible"><p>';
	/* translators: %s: Minimum required version */
	printf( esc_html__( 'Mox Icons requires Elementor version %s or higher.', 'mox-icons-pack-for-elementor' ), '3.5.0' );
	echo '</p></div>';
}

function mox_icon_missing_files_notice() {
	echo '<div class="notice notice-error is-dismissible"><p>';
	echo esc_html__( 'Mox Icons: Required plugin files are missing. Please deactivate the plugin, delete it, and reinstall from a fresh zip file.', 'mox-icons-pack-for-elementor' );
	echo '</p></div>';
}

register_activation_hook( __FILE__, 'mox_icon_activate' );
/**
 * Store the default library toggles on first activation.
 *
 * Defaults are derived from the library definitions so a newly bundled library
 * can never be missed here. Elementor is not required to be active yet, so the
 * class file is loaded directly.
 */
function mox_icon_activate() {
	$class_file = MOX_ICON_PATH . 'includes/class-mox-icon.php';
	if ( ! file_exists( $class_file ) ) {
		return;
	}
	require_once $class_file;

	add_option( 'mox_icon_settings', MoxIcon::get_default_settings() );
}

register_uninstall_hook( __FILE__, 'mox_icon_uninstall' );
/**
 * Remove the plugin option, on every site of a network install.
 */
function mox_icon_uninstall() {
	if ( ! is_multisite() ) {
		delete_option( 'mox_icon_settings' );
		return;
	}

	$paged = 1;
	do {
		$site_ids = get_sites(
			array(
				'fields'                 => 'ids',
				'number'                 => 100,
				'offset'                 => ( $paged - 1 ) * 100,
				'update_site_meta_cache' => false,
			)
		);

		foreach ( $site_ids as $site_id ) {
			switch_to_blog( $site_id );
			delete_option( 'mox_icon_settings' );
			restore_current_blog();
		}

		++$paged;
	} while ( count( $site_ids ) === 100 );
}
