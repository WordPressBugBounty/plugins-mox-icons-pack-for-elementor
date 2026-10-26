<?php
/**
 * Icon library adapter for Elementor's icon manager.
 *
 * One adapter serves every bundled library — webfont based (Material Design
 * Icons) and CSS mask based (Lucide, Tabler, Simple Line Icons, …) alike —
 * because they all expose the same shape: a namespaced base class plus one
 * modifier class per icon.
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MoxIcon_Icon_Library {

	/**
	 * Definition keys that must be present for the library to be registered.
	 *
	 * @var string[]
	 */
	private static $required = array( 'label', 'version', 'elementor_name', 'prefix', 'css', 'json', 'label_icon' );

	/**
	 * Settings key of this library, e.g. 'lucide'.
	 *
	 * @var string
	 */
	private $key;

	/**
	 * Library definition from MoxIcon::get_library_definitions().
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param string $key    Settings key.
	 * @param array  $config Library definition.
	 */
	public function __construct( $key, $config ) {
		$this->key    = $key;
		$this->config = is_array( $config ) ? $config : array();
	}

	/**
	 * Whether the definition is complete and its assets are present on disk.
	 *
	 * A library with a missing asset is skipped rather than registered as an
	 * empty tab, which is what Elementor would otherwise show.
	 *
	 * @return bool
	 */
	public function is_valid() {
		foreach ( self::$required as $required_key ) {
			if ( empty( $this->config[ $required_key ] ) ) {
				$this->log_invalid( 'missing "' . $required_key . '" in definition' );
				return false;
			}
		}

		foreach ( array( 'css', 'json' ) as $asset_key ) {
			if ( ! file_exists( MOX_ICON_PATH . $this->config[ $asset_key ] ) ) {
				$this->log_invalid( 'missing asset ' . $this->config[ $asset_key ] );
				return false;
			}
		}

		return true;
	}

	/**
	 * Unique library name, used as the Elementor tab key.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->config['elementor_name'];
	}

	/**
	 * Display label.
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->config['label'];
	}

	/**
	 * Class prefix Elementor appends the icon name to.
	 *
	 * Returns "<base> <base>-" so the rendered class carries both the base
	 * class that defines the font or mask and the per-icon modifier.
	 *
	 * @return string
	 */
	public function get_prefix() {
		return $this->config['prefix'] . ' ' . $this->config['prefix'] . '-';
	}

	/**
	 * Base class Elementor puts in front of the prefix.
	 *
	 * Must never be empty: Elementor falls back to
	 * str_replace( '-', '', prefix ) and that can produce an unnamespaced,
	 * collision-prone class such as "icon".
	 *
	 * @return string
	 */
	public function get_display_prefix() {
		return $this->config['prefix'];
	}

	/**
	 * URL of the library stylesheet.
	 *
	 * @return string
	 */
	public function get_css_url() {
		return MOX_ICON_URL . $this->config['css'];
	}

	/**
	 * URL of the icon manifest.
	 *
	 * Elementor fetches this with the browser cache enabled and does not add a
	 * cache buster of its own, so the plugin version is appended here. Without
	 * it a browser can pair a fresh stylesheet with a stale manifest after an
	 * update.
	 *
	 * @return string
	 */
	public function get_json_url() {
		return add_query_arg( 'ver', MOX_ICON_VERSION, MOX_ICON_URL . $this->config['json'] );
	}

	/**
	 * Library version string.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->config['version'];
	}

	/**
	 * Full class string of the icon shown on this library's tab.
	 *
	 * @return string
	 */
	public function get_label_icon() {
		return $this->config['label_icon'];
	}

	/**
	 * Build the config array for Elementor's additional_tabs filter.
	 *
	 * In the editor the icon picker has to be able to show every icon, so the
	 * complete stylesheet is handed over. Everywhere else the stylesheet URL is
	 * left out — which is what stops Elementor from enqueueing it, see
	 * Frontend::maybe_enqueue_icon_font() — and a render callback emits only the
	 * rules the page uses instead.
	 *
	 * The editor config is serialised to JSON for the editor's JavaScript, so the
	 * callback must never be present in that branch.
	 *
	 * @return array
	 */
	public function get_config() {
		$config = array(
			'name'          => $this->get_name(),
			'label'         => $this->get_label(),
			'enqueue'       => array(),
			'prefix'        => $this->get_prefix(),
			'displayPrefix' => $this->get_display_prefix(),
			'labelIcon'     => $this->get_label_icon(),
			'ver'           => $this->get_version(),
			'fetchJson'     => $this->get_json_url(),
			'native'        => false,
		);

		if ( self::is_edit_mode() ) {
			$config['url'] = $this->get_css_url();
			return $config;
		}

		$config['render_callback'] = array( MoxIcon_Icon_CSS::instance(), 'render' );

		return $config;
	}

	/**
	 * Whether this request is the Elementor editor document itself.
	 *
	 * The preview iframe is deliberately not included: it renders icons through
	 * PHP just like the front end, so it should see the same output.
	 *
	 * @return bool
	 */
	private static function is_edit_mode() {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->editor ) ) {
			return false;
		}
		return (bool) \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Record why a library was skipped, for debug builds only.
	 *
	 * @param string $reason Human readable reason.
	 */
	private function log_invalid( $reason ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Mox Icons: skipped library "' . $this->key . '" — ' . $reason );
		}
	}
}
