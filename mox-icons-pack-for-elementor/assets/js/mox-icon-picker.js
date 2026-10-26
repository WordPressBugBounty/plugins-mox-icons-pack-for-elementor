/**
 * Cache Mox catalogs for the current editor session. Elementor's normalized
 * catalogs can exceed localStorage's quota and stop the picker before rendering.
 *
 * This patches undocumented Elementor internals, so every way it can fail to
 * attach reports itself on the console instead of silently restoring the quota
 * bug this adapter exists to prevent.
 */
( function ( $ ) {
	'use strict';

	var RETRY_INTERVAL = 250;
	var RETRY_LIMIT = 40; // Roughly ten seconds.

	var cache = Object.create( null );
	var attempts = 0;
	var warned = false;

	function warn( message ) {
		if ( warned || ! window.console || typeof window.console.warn !== 'function' ) {
			return;
		}
		warned = true;
		window.console.warn(
			'[Mox Icons] ' + message +
			' Large icon libraries may fail to open in the icon picker. Please report this with your Elementor version.'
		);
	}

	/**
	 * @return {string} 'done', 'pending' when Elementor is not ready yet, or
	 *                  'unsupported' when the store no longer looks patchable.
	 */
	function tryInstall() {
		var manager = window.elementor && window.elementor.iconManager;
		var store = manager && manager.store;

		if ( ! store ) {
			return 'pending';
		}

		if ( store.moxIconCacheInstalled ) {
			return 'done';
		}

		if ( typeof store.save !== 'function' || typeof store.get !== 'function' ) {
			return 'unsupported';
		}

		var originalSave = store.save;
		var originalGet = store.get;

		store.save = function ( library ) {
			if ( library && typeof library.name === 'string' && library.name.indexOf( 'moxicon-' ) === 0 ) {
				cache[ 'elementor_' + library.name + '_icons' ] = library;
				return;
			}

			return originalSave.apply( this, arguments );
		};

		store.get = function ( key ) {
			if ( typeof key === 'string' && key.indexOf( 'elementor_moxicon-' ) === 0 && key.slice( -6 ) === '_icons' ) {
				return cache[ key ];
			}

			return originalGet.apply( this, arguments );
		};

		store.moxIconCacheInstalled = true;

		return 'done';
	}

	function installIconCache() {
		var result = tryInstall();

		if ( 'done' === result ) {
			return;
		}

		if ( 'unsupported' === result ) {
			warn( "Elementor's icon store no longer exposes save() and get(), so the editor-session cache was not installed." );
			return;
		}

		attempts++;

		if ( attempts >= RETRY_LIMIT ) {
			warn( 'Elementor’s icon manager did not become available, so the editor-session cache was not installed.' );
			return;
		}

		// window.setTimeout is absent in the Node regression harness.
		if ( typeof window.setTimeout === 'function' ) {
			window.setTimeout( installIconCache, RETRY_INTERVAL );
		}
	}

	$( window ).on( 'elementor:init', installIconCache );
	installIconCache();
}( jQuery ) );
