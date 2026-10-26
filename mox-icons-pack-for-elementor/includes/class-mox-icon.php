<?php
/**
 * Core plugin class.
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MoxIcon {

	private static $instance = null;

	/**
	 * Enabled, valid libraries; null until first requested.
	 *
	 * @var MoxIcon_Icon_Library[]|null
	 */
	private $enabled_libraries = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {
		$files = array(
			'includes/class-mox-icon-css.php',
			'includes/libraries/class-mox-icon-library.php',
			'includes/class-mox-icon-admin.php',
		);

		foreach ( $files as $file ) {
			$path = MOX_ICON_PATH . $file;
			if ( ! file_exists( $path ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( 'Mox Icons: Missing required file — ' . $path );
				}
				throw new \RuntimeException(
					'Mox Icons: Missing required plugin file. Please reinstall the plugin.'
				);
			}
			require_once $path;
		}
	}

	private function init_hooks() {
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'register_icon_libraries' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_icon_picker_styles' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_icon_picker_scripts' ) );
		add_action( 'init', array( $this, 'register_library_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_forced_library_styles' ) );
		new MoxIcon_Admin();
	}

	/**
	 * Style handle of a library's complete stylesheet.
	 *
	 * @param string $key Library key, e.g. 'mdi'.
	 * @return string
	 */
	public static function get_style_handle( $key ) {
		return 'mox-icon-library-' . sanitize_key( $key );
	}

	/**
	 * Register every library's complete stylesheet on each request.
	 *
	 * Elementor's element cache stores the handles a document enqueued and, on a
	 * cache hit, enqueues them by name without running the icon render callback.
	 * A handle that is only registered inside that callback would then resolve to
	 * nothing, so the handles have to exist before any content is printed.
	 */
	public function register_library_styles() {
		foreach ( self::get_library_definitions() as $key => $definition ) {
			if ( empty( $definition['css'] ) ) {
				continue;
			}

			wp_register_style(
				self::get_style_handle( $key ),
				MOX_ICON_URL . $definition['css'],
				array(),
				MOX_ICON_VERSION
			);
		}
	}

	/**
	 * Enqueue the complete stylesheet of every library the
	 * mox_icon_use_full_stylesheet filter asks for.
	 *
	 * The filter exists for icon classes written by markup that never reaches the
	 * render callback, so it has to be honoured here rather than only there.
	 */
	public function enqueue_forced_library_styles() {
		$settings = get_option( 'mox_icon_settings', array() );

		foreach ( array_keys( self::get_library_definitions() ) as $key ) {
			if ( isset( $settings[ $key ] ) && ! $settings[ $key ] ) {
				continue;
			}

			/** This filter is documented in includes/class-mox-icon-css.php */
			if ( apply_filters( 'mox_icon_use_full_stylesheet', false, $key ) ) {
				wp_enqueue_style( self::get_style_handle( $key ) );
			}
		}
	}

	/**
	 * Keep large Mox icon catalogs out of Elementor's persistent browser cache.
	 */
	public function enqueue_editor_icon_picker_scripts() {
		wp_enqueue_script(
			'mox-icon-picker',
			MOX_ICON_URL . 'assets/js/mox-icon-picker.js',
			array( 'jquery', 'elementor-editor' ),
			MOX_ICON_VERSION,
			true
		);
	}

	/**
	 * Enqueue editor-only adjustments for the Elementor icon library list.
	 */
	public function enqueue_editor_icon_picker_styles() {
		wp_enqueue_style(
			'mox-icon-picker',
			MOX_ICON_URL . 'assets/css/mox-icon-picker.css',
			array(),
			MOX_ICON_VERSION
		);
	}

	/**
	 * Register icon libraries with Elementor.
	 *
	 * @param array $tabs Existing Elementor icon manager tabs.
	 * @return array
	 */
	public function register_icon_libraries( $tabs ) {
		foreach ( $this->get_enabled_libraries() as $library ) {
			$tabs[ $library->get_name() ] = $library->get_config();
		}

		return $tabs;
	}

	/**
	 * Enabled libraries whose assets are present, built once per request.
	 *
	 * Elementor applies the additional_tabs filter every time it renders an icon,
	 * so the option lookup, the definitions and the asset checks must not be
	 * repeated on each call. The config itself is still built per call, because
	 * it differs between the editor and the front end.
	 *
	 * @return MoxIcon_Icon_Library[]
	 */
	private function get_enabled_libraries() {
		if ( is_array( $this->enabled_libraries ) ) {
			return $this->enabled_libraries;
		}

		$this->enabled_libraries = array();
		$settings                = get_option( 'mox_icon_settings', array() );

		foreach ( self::get_library_definitions() as $key => $definition ) {
			// Default to enabled if the setting has never been stored.
			$enabled = isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : true;
			if ( ! $enabled ) {
				continue;
			}

			$library = new MoxIcon_Icon_Library( $key, $definition );
			if ( ! $library->is_valid() ) {
				continue;
			}

			$this->enabled_libraries[] = $library;
		}

		return $this->enabled_libraries;
	}

	/**
	 * Get all library definitions used by Elementor registration and admin UI.
	 *
	 * Every library is described here and rendered by MoxIcon_Icon_Library, so
	 * labels, versions, counts and asset paths have a single source of truth.
	 *
	 * The 'prefix' is the namespaced base class. Elementor receives
	 * "<prefix> <prefix>-" as its prefix and "<prefix>" as its displayPrefix,
	 * producing markup such as <i class="mox-lucide mox-lucide-home">.
	 *
	 * @return array
	 */
	public static function get_library_definitions() {
		return array(
			'heroicons_outline' => array(
				'label'          => __( 'Heroicons Outline', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Clean stroke-based icons by Tailwind Labs.', 'mox-icons-pack-for-elementor' ),
				'count'          => 324,
				'license'        => 'MIT',
				'credit'         => 'https://heroicons.com',
				'version'        => '2.2.0',
				'elementor_name' => 'moxicon-heroicons-outline',
				'prefix'         => 'hi-outline',
				'css'            => 'assets/libs/heroicons/heroicons-outline.css',
				'json'           => 'assets/libs/heroicons/icons-outline.json',
				'label_icon'     => 'hi-outline hi-outline-home',
			),
			'heroicons_solid'   => array(
				'label'          => __( 'Heroicons Solid', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Filled variant of Heroicons by Tailwind Labs.', 'mox-icons-pack-for-elementor' ),
				'count'          => 324,
				'license'        => 'MIT',
				'credit'         => 'https://heroicons.com',
				'version'        => '2.2.0',
				'elementor_name' => 'moxicon-heroicons-solid',
				'prefix'         => 'hi-solid',
				'css'            => 'assets/libs/heroicons/heroicons-solid.css',
				'json'           => 'assets/libs/heroicons/icons-solid.json',
				'label_icon'     => 'hi-solid hi-solid-home',
			),
			'mdi'               => array(
				'label'          => __( 'Material Design Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Community-maintained icon library by Pictogrammers.', 'mox-icons-pack-for-elementor' ),
				'count'          => 7447,
				'license'        => 'Apache 2.0',
				'credit'         => 'https://pictogrammers.com/library/mdi/',
				'version'        => '7.4.47',
				'elementor_name' => 'moxicon-mdi',
				'prefix'         => 'mdi',
				'css'            => 'assets/libs/mdi/materialdesignicons.min.css',
				'json'           => 'assets/libs/mdi/icons.json',
				'label_icon'     => 'mdi mdi-material-design',
			),
			'simplelineicons'   => array(
				'label'          => __( 'Simple Line Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Simple and minimal line icons.', 'mox-icons-pack-for-elementor' ),
				'count'          => 189,
				'license'        => 'MIT',
				'credit'         => 'https://simplelineicons.github.io/',
				'version'        => '1.2.1',
				'elementor_name' => 'moxicon-simplelineicons',
				'prefix'         => 'mox-sli',
				'css'            => 'assets/libs/simplelineicons/simple-line-icons.css',
				'json'           => 'assets/libs/simplelineicons/icons.json',
				'label_icon'     => 'mox-sli mox-sli-screen-smartphone',
			),
			'lucide'            => array(
				'label'          => __( 'Lucide', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Beautiful and consistent open-source stroke icons.', 'mox-icons-pack-for-elementor' ),
				'count'          => 1962,
				'license'        => 'ISC',
				'credit'         => 'https://lucide.dev/icons/',
				'version'        => '1.17.0',
				'elementor_name' => 'moxicon-lucide',
				'prefix'         => 'mox-lucide',
				'css'            => 'assets/libs/lucide/lucide.css',
				'json'           => 'assets/libs/lucide/icons.json',
				'label_icon'     => 'mox-lucide mox-lucide-home',
			),
			'tabler'            => array(
				'label'          => __( 'Tabler Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Pixel-perfect outline icons for interfaces and dashboards.', 'mox-icons-pack-for-elementor' ),
				'count'          => 5093,
				'license'        => 'MIT',
				'credit'         => 'https://tabler.io/icons',
				'version'        => '3.44.0',
				'elementor_name' => 'moxicon-tabler',
				'prefix'         => 'mox-tabler',
				'css'            => 'assets/libs/tabler/tabler-icons.css',
				'json'           => 'assets/libs/tabler/icons.json',
				'label_icon'     => 'mox-tabler mox-tabler-home',
			),
			'phosphor'          => array(
				'label'          => __( 'Phosphor Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Flexible regular-weight icons with a friendly visual style.', 'mox-icons-pack-for-elementor' ),
				'count'          => 1512,
				'license'        => 'MIT',
				'credit'         => 'https://phosphoricons.com/',
				'version'        => '2.1.1',
				'elementor_name' => 'moxicon-phosphor',
				'prefix'         => 'mox-phosphor',
				'css'            => 'assets/libs/phosphor/phosphor-icons.css',
				'json'           => 'assets/libs/phosphor/icons.json',
				'label_icon'     => 'mox-phosphor mox-phosphor-house',
			),
			'bootstrap_icons'   => array(
				'label'          => __( 'Bootstrap Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Official open-source icon set from the Bootstrap team.', 'mox-icons-pack-for-elementor' ),
				'count'          => 2078,
				'license'        => 'MIT',
				'credit'         => 'https://icons.getbootstrap.com/',
				'version'        => '1.13.1',
				'elementor_name' => 'moxicon-bootstrap-icons',
				'prefix'         => 'mox-bootstrap',
				'css'            => 'assets/libs/bootstrap-icons/bootstrap-icons.css',
				'json'           => 'assets/libs/bootstrap-icons/icons.json',
				'label_icon'     => 'mox-bootstrap mox-bootstrap-house',
			),
			'iconoir'           => array(
				'label'          => __( 'Iconoir', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Clean, modern open-source icons for product interfaces.', 'mox-icons-pack-for-elementor' ),
				'count'          => 1383,
				'license'        => 'MIT',
				'credit'         => 'https://iconoir.com/',
				'version'        => '7.11.0',
				'elementor_name' => 'moxicon-iconoir',
				'prefix'         => 'mox-iconoir',
				'css'            => 'assets/libs/iconoir/iconoir.css',
				'json'           => 'assets/libs/iconoir/icons.json',
				'label_icon'     => 'mox-iconoir mox-iconoir-home',
			),
			'feather'           => array(
				'label'          => __( 'Feather Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Simple, elegant open-source stroke icons.', 'mox-icons-pack-for-elementor' ),
				'count'          => 287,
				'license'        => 'MIT',
				'credit'         => 'https://feathericons.com/',
				'version'        => '4.29.2',
				'elementor_name' => 'moxicon-feather',
				'prefix'         => 'mox-feather',
				'css'            => 'assets/libs/feather/feather-icons.css',
				'json'           => 'assets/libs/feather/icons.json',
				'label_icon'     => 'mox-feather mox-feather-home',
			),
			'fluent_color'      => array(
				'label'          => __( 'Fluent UI System Color Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Colorful Fluent UI system icons by Microsoft.', 'mox-icons-pack-for-elementor' ),
				'count'          => 890,
				'license'        => 'MIT',
				'credit'         => 'https://github.com/microsoft/fluentui-system-icons',
				'version'        => '1.2.21',
				'elementor_name' => 'moxicon-fluent-color',
				'prefix'         => 'mox-fluent-color',
				'css'            => 'assets/libs/fluent-color/fluent-color.css',
				'json'           => 'assets/libs/fluent-color/icons.json',
				'label_icon'     => 'mox-fluent-color mox-fluent-color-home-24',
			),
			'pixelarticons'     => array(
				'label'          => __( 'Pixelarticons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Pixel-perfect retro interface icons by Gerrit Halfmann.', 'mox-icons-pack-for-elementor' ),
				'count'          => 1172,
				'license'        => 'MIT',
				'credit'         => 'https://github.com/halfmage/pixelarticons',
				'version'        => '1.2.7',
				'elementor_name' => 'moxicon-pixelarticons',
				'prefix'         => 'mox-pixelarticons',
				'css'            => 'assets/libs/pixelarticons/pixelarticons.css',
				'json'           => 'assets/libs/pixelarticons/icons.json',
				'label_icon'     => 'mox-pixelarticons mox-pixelarticons-home',
			),
			'material_symbols_light' => array(
				'label'          => __( 'Material Symbols Light', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Google Material Symbols, light weight, outline rounded style.', 'mox-icons-pack-for-elementor' ),
				'count'          => 3978,
				'license'        => 'Apache 2.0',
				'credit'         => 'https://fonts.google.com/icons',
				'version'        => '1.2.93',
				'elementor_name' => 'moxicon-material-symbols-light',
				'prefix'         => 'mox-material-symbols-light',
				'css'            => 'assets/libs/material-symbols-light/material-symbols-light.css',
				'json'           => 'assets/libs/material-symbols-light/icons.json',
				'label_icon'     => 'mox-material-symbols-light mox-material-symbols-light-home-outline-rounded',
			),
			'mynaui'            => array(
				'label'          => __( 'Myna UI Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Clean and elegant open-source UI icons by Praveen Juge.', 'mox-icons-pack-for-elementor' ),
				'count'          => 2892,
				'license'        => 'MIT',
				'credit'         => 'https://mynaui.com/icons',
				'version'        => '1.2.19',
				'elementor_name' => 'moxicon-mynaui',
				'prefix'         => 'mox-mynaui',
				'css'            => 'assets/libs/mynaui/mynaui.css',
				'json'           => 'assets/libs/mynaui/icons.json',
				'label_icon'     => 'mox-mynaui mox-mynaui-home',
			),
			'healthicons'       => array(
				'label'          => __( 'Health Icons', 'mox-icons-pack-for-elementor' ),
				'description'    => __( 'Open-source health and medical icons by Resolve to Save Lives.', 'mox-icons-pack-for-elementor' ),
				'count'          => 3391,
				'license'        => 'MIT',
				'credit'         => 'https://healthicons.org/',
				'version'        => '1.2.13',
				'elementor_name' => 'moxicon-healthicons',
				'prefix'         => 'mox-healthicons',
				'css'            => 'assets/libs/healthicons/healthicons.css',
				'json'           => 'assets/libs/healthicons/icons.json',
				'label_icon'     => 'mox-healthicons mox-healthicons-heart',
			),
		);
	}

	/**
	 * Get default enabled settings for all libraries.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array_fill_keys( array_keys( self::get_library_definitions() ), true );
	}
}
