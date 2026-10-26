<?php
/**
 * Per-page icon CSS.
 *
 * Elementor enqueues a library's whole stylesheet as soon as one of its icons
 * appears on a page. For the mask based libraries that is expensive: Health
 * Icons alone is about four megabytes, because every icon carries its own SVG
 * as a data URI.
 *
 * So outside the editor these libraries are registered without a stylesheet URL
 * and with a render callback instead. The callback emits the handful of rules
 * the page actually uses, read straight out of the stylesheet by byte offset
 * using the index that build-icon-css-index.mjs writes next to it.
 *
 * Material Design Icons is the one remaining webfont library. It has no index and
 * keeps loading its full stylesheet: its weight is the font file, not the CSS.
 *
 * @package MoxIcon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MoxIcon_Icon_CSS {

	/**
	 * Singleton.
	 *
	 * @var MoxIcon_Icon_CSS|null
	 */
	private static $instance = null;

	/**
	 * Elementor tab name => array( key, definition ).
	 *
	 * @var array|null
	 */
	private $libraries = null;

	/**
	 * Index file relative path => decoded index, or false when unusable.
	 *
	 * @var array
	 */
	private $indexes = array();

	/**
	 * Open stylesheet handles, keyed by absolute path.
	 *
	 * @var array
	 */
	private $handles = array();

	/**
	 * Rules already emitted in the document being rendered.
	 *
	 * @var array
	 */
	private $printed = array();

	/**
	 * Libraries that fell back to their full stylesheet in the document being
	 * rendered.
	 *
	 * @var array
	 */
	private $full = array();

	/**
	 * Handles of every full stylesheet requested on this page.
	 *
	 * @var array
	 */
	private $needed = array();

	/**
	 * Whether the document about to start has no elements.
	 *
	 * @var bool
	 */
	private $empty_document = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'elementor/frontend/builder_content_data', array( $this, 'note_document_data' ), PHP_INT_MAX );
		add_action( 'elementor/frontend/before_get_builder_content', array( $this, 'begin_document' ) );
		add_action( 'elementor/frontend/get_builder_content', array( $this, 'end_document' ) );
		// Just before WordPress prints late styles, see print_late_styles().
		add_action( 'wp_footer', array( $this, 'end_document' ), 19 );
	}

	/**
	 * Remember whether the document about to start has any elements.
	 *
	 * Elementor returns straight after before_get_builder_content for a document
	 * without elements and never fires get_builder_content for it, so
	 * end_document() would not run and the stylesheets begin_document() took off
	 * the queue would stay off it.
	 *
	 * @param array $data Elements data of the document.
	 * @return array
	 */
	public function note_document_data( $data ) {
		$this->empty_document = empty( $data );
		return $data;
	}

	/**
	 * Start a document with a clean slate.
	 *
	 * Elementor caches rendered output per document, together with the style
	 * handles that document added to the queue. A cached document is later shown
	 * on pages where its neighbours are different, so it must carry every rule
	 * and every handle its own icons need. Rules another document emitted are
	 * forgotten here, and library stylesheets already on the queue - whether a
	 * render callback or a cached document put them there - are taken off it so
	 * this document's own enqueue is seen as new. end_document() restores them.
	 */
	public function begin_document() {
		$this->printed = array();
		$this->full    = array();

		// Nothing will be rendered and end_document() will not be called.
		if ( $this->empty_document ) {
			return;
		}

		foreach ( $this->get_libraries() as $library ) {
			$handle = MoxIcon::get_style_handle( $library[0] );
			if ( wp_style_is( $handle, 'enqueued' ) ) {
				$this->needed[ $handle ] = $handle;
			}
		}

		foreach ( $this->needed as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	/**
	 * Finish a document: restore every stylesheet the page needs.
	 *
	 * Also forgets the emitted rules again, because the document that just ended
	 * may be stored separately from the one it was nested in.
	 */
	public function end_document() {
		$this->printed = array();
		$this->full    = array();

		foreach ( $this->needed as $handle ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Elementor render_callback.
	 *
	 * Returns exactly the markup Elementor's own render_font_icon() would
	 * produce, preceded by the rules that markup needs.
	 *
	 * @param array  $icon       array( 'value' => string, 'library' => string ).
	 * @param array  $attributes Icon HTML attributes.
	 * @param string $tag        HTML tag.
	 * @return string
	 */
	public function render( $icon, $attributes = array(), $tag = 'i' ) {
		$icon       = is_array( $icon ) ? $icon : array();
		$attributes = is_array( $attributes ) ? $attributes : array();
		$value      = isset( $icon['value'] ) ? (string) $icon['value'] : '';
		$library    = isset( $icon['library'] ) ? (string) $icon['library'] : '';

		if ( '' === $value ) {
			return '';
		}

		return $this->rules_for( $library, $value ) . $this->element( $value, $attributes, $tag );
	}

	/**
	 * Build the <style> block for one icon, or an empty string.
	 *
	 * @param string $library Elementor tab name.
	 * @param string $value   Full class string of the icon.
	 * @return string
	 */
	private function rules_for( $library, $value ) {
		$libraries = $this->get_libraries();
		if ( ! isset( $libraries[ $library ] ) ) {
			return '';
		}

		list( $key, $definition ) = $libraries[ $library ];

		/**
		 * Filters whether a library must load its complete stylesheet.
		 *
		 * Return true for a library whose icon classes are also used by markup
		 * that does not go through Elementor's icon manager, since those
		 * elements never reach this callback.
		 *
		 * @param bool   $full Whether to enqueue the whole stylesheet.
		 * @param string $key  Library key, e.g. 'healthicons'.
		 */
		if ( apply_filters( 'mox_icon_use_full_stylesheet', false, $key ) ) {
			$this->enqueue_full( $key, $definition );
			return '';
		}

		$index = $this->get_index( $definition );
		if ( ! $index ) {
			$this->enqueue_full( $key, $definition );
			return '';
		}

		$name = $this->icon_name( $value, $definition['prefix'] );
		if ( '' === $name || ! isset( $index['icons'][ $name ] ) ) {
			$this->enqueue_full( $key, $definition );
			return '';
		}

		$path  = MOX_ICON_PATH . $definition['css'];
		$parts = array();

		if ( ! isset( $this->printed[ $key . ':' ] ) ) {
			$base = $this->read( $path, $index['base'] );
			if ( false === $base ) {
				$this->enqueue_full( $key, $definition );
				return '';
			}
			$this->printed[ $key . ':' ] = true;
			$parts[]                     = $base;
		}

		if ( ! isset( $this->printed[ $key . ':' . $name ] ) ) {
			$rule = $this->read( $path, $index['icons'][ $name ] );
			if ( false === $rule ) {
				$this->enqueue_full( $key, $definition );
				return '';
			}
			$this->printed[ $key . ':' . $name ] = true;
			$parts[]                             = $rule;
		}

		if ( empty( $parts ) ) {
			return '';
		}

		$css = implode( "\n", $parts );

		// A stylesheet is not HTML, so refuse to inline anything that could
		// close the style element. Bundled icon CSS never contains these.
		if ( false !== strpos( $css, '<' ) || false !== strpos( $css, '>' ) ) {
			$this->enqueue_full( $key, $definition );
			return '';
		}

		return '<style class="mox-icon-css">' . $css . '</style>';
	}

	/**
	 * Reproduce Elementor's font icon markup.
	 *
	 * @param string $value      Full class string.
	 * @param array  $attributes Icon HTML attributes.
	 * @param string $tag        HTML tag.
	 * @return string
	 */
	private function element( $value, $attributes, $tag ) {
		$tag = is_string( $tag ) && preg_match( '/^[a-zA-Z][a-zA-Z0-9]*$/', $tag ) ? $tag : 'i';

		if ( empty( $attributes['class'] ) ) {
			$attributes['class'] = $value;
		} elseif ( is_array( $attributes['class'] ) ) {
			$attributes['class'][] = $value;
		} else {
			$attributes['class'] .= ' ' . $value;
		}

		if ( class_exists( '\Elementor\Utils' ) && method_exists( '\Elementor\Utils', 'render_html_attributes' ) ) {
			$rendered = \Elementor\Utils::render_html_attributes( $attributes );
		} else {
			$rendered = $this->render_attributes( $attributes );
		}

		return '<' . $tag . ' ' . $rendered . '></' . $tag . '>';
	}

	/**
	 * Fallback attribute renderer, used only if Elementor's helper is gone.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	private function render_attributes( $attributes ) {
		$out = array();
		foreach ( $attributes as $name => $values ) {
			if ( is_array( $values ) ) {
				$values = implode( ' ', $values );
			}
			$out[] = sprintf( '%1$s="%2$s"', esc_attr( $name ), esc_attr( $values ) );
		}
		return implode( ' ', $out );
	}

	/**
	 * Icon name from its class string, e.g. 'home' from
	 * "mox-lucide mox-lucide mox-lucide-home".
	 *
	 * @param string $value  Class string.
	 * @param string $prefix Library base class.
	 * @return string
	 */
	private function icon_name( $value, $prefix ) {
		$needle = $prefix . '-';
		foreach ( preg_split( '/\s+/', trim( $value ) ) as $class ) {
			if ( 0 === strpos( $class, $needle ) ) {
				return substr( $class, strlen( $needle ) );
			}
		}
		return '';
	}

	/**
	 * Load and validate a stylesheet's rule index.
	 *
	 * @param array $definition Library definition.
	 * @return array|false
	 */
	private function get_index( $definition ) {
		if ( empty( $definition['css'] ) ) {
			return false;
		}

		$relative = preg_replace( '/\.css$/', '.rules.json', $definition['css'] );
		if ( $relative === $definition['css'] ) {
			return false;
		}

		if ( array_key_exists( $relative, $this->indexes ) ) {
			return $this->indexes[ $relative ];
		}

		$this->indexes[ $relative ] = false;

		$index_path = MOX_ICON_PATH . $relative;
		$css_path   = MOX_ICON_PATH . $definition['css'];
		if ( ! is_readable( $index_path ) || ! is_readable( $css_path ) ) {
			return false;
		}

		$raw = file_get_contents( $index_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_string( $raw ) ) {
			return false;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['icons'] ) || empty( $data['base'] ) || ! isset( $data['bytes'] ) ) {
			return false;
		}

		// Byte offsets only describe the exact stylesheet they were built from.
		if ( (int) $data['bytes'] !== (int) filesize( $css_path ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Mox Icons: rule index is stale for ' . $definition['css'] . '; loading the full stylesheet. Rebuild it with scripts/build-icon-css-index.mjs.' );
			}
			return false;
		}

		$this->indexes[ $relative ] = $data;
		return $data;
	}

	/**
	 * Read a byte range out of a stylesheet.
	 *
	 * @param string $path Absolute stylesheet path.
	 * @param array  $span array( offset, length ).
	 * @return string|false
	 */
	private function read( $path, $span ) {
		if ( ! is_array( $span ) || 2 !== count( $span ) ) {
			return false;
		}

		$offset = (int) $span[0];
		$length = (int) $span[1];
		if ( $offset < 0 || $length <= 0 ) {
			return false;
		}

		if ( ! isset( $this->handles[ $path ] ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$handle = fopen( $path, 'rb' );
			if ( ! $handle ) {
				return false;
			}
			$this->handles[ $path ] = $handle;
		}

		$handle = $this->handles[ $path ];
		if ( 0 !== fseek( $handle, $offset ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$data = fread( $handle, $length );
		if ( ! is_string( $data ) || strlen( $data ) !== $length ) {
			return false;
		}

		return $data;
	}

	/**
	 * Fall back to the complete stylesheet for a library.
	 *
	 * @param string $key        Library key.
	 * @param array  $definition Library definition.
	 */
	private function enqueue_full( $key, $definition ) {
		if ( isset( $this->full[ $key ] ) || empty( $definition['css'] ) ) {
			return;
		}
		$this->full[ $key ] = true;

		// Registered on init by MoxIcon::register_library_styles().
		$handle                  = MoxIcon::get_style_handle( $key );
		$this->needed[ $handle ] = $handle;
		wp_enqueue_style( $handle );
	}

	/**
	 * Elementor tab name => array( key, definition ).
	 *
	 * @return array
	 */
	private function get_libraries() {
		if ( is_array( $this->libraries ) ) {
			return $this->libraries;
		}

		$this->libraries = array();
		foreach ( MoxIcon::get_library_definitions() as $key => $definition ) {
			if ( empty( $definition['elementor_name'] ) ) {
				continue;
			}
			$this->libraries[ $definition['elementor_name'] ] = array( $key, $definition );
		}

		return $this->libraries;
	}
}
