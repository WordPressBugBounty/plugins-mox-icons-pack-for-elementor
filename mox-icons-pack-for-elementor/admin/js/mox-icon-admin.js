/* global jQuery */
(function ($) {
	'use strict';

	// wp_localize_script normally provides this; fall back so a failed
	// localization cannot take the rest of the page's behaviour down with it.
	var l10n = window.moxIconAdmin || {};
	var activeLabel = l10n.activeLabel || 'Active';
	var inactiveLabel = l10n.inactiveLabel || 'Inactive';

	var STORAGE_KEY = 'moxIconAdminActiveTab';

	function readStoredTab() {
		try {
			return window.sessionStorage.getItem(STORAGE_KEY);
		} catch (e) {
			return null;
		}
	}

	function storeTab(target) {
		try {
			window.sessionStorage.setItem(STORAGE_KEY, target);
		} catch (e) {
			// Private browsing or blocked storage; remembering the tab is optional.
		}
	}

	$(function () {
		var $tabs = $('.moxicon-tab-btn');

		// Live toggle: update card state and status tag when checkbox changes
		$('.moxicon-toggle input[type="checkbox"]').on('change', function () {
			var $card = $(this).closest('.moxicon-library-card');
			var $statusTag = $card.find('.moxicon-status-tag');
			var isChecked = $(this).is(':checked');

			$card.toggleClass('is-active', isChecked);

			$statusTag
				.toggleClass('moxicon-tag--active', isChecked)
				.toggleClass('moxicon-tag--inactive', !isChecked)
				.text(isChecked ? activeLabel : inactiveLabel);
		});

		function activateTab(target, moveFocus) {
			var $button = $tabs.filter('[data-target="' + target + '"]');
			if (!$button.length) {
				return;
			}

			$tabs
				.removeClass('is-active')
				.attr({ 'aria-selected': 'false', tabindex: '-1' });
			$button
				.addClass('is-active')
				.attr({ 'aria-selected': 'true', tabindex: '0' });

			$('.moxicon-tab-pane').removeClass('is-active');
			$('#' + target).addClass('is-active');

			if (moveFocus) {
				$button.trigger('focus');
			}

			storeTab(target);
		}

		$tabs.on('click', function (e) {
			e.preventDefault();
			activateTab($(this).data('target'), false);
		});

		// Arrow-key navigation, per the WAI-ARIA tabs pattern.
		$tabs.on('keydown', function (e) {
			var index = $tabs.index(this);
			var next = null;

			switch (e.key) {
				case 'ArrowRight':
					next = (index + 1) % $tabs.length;
					break;
				case 'ArrowLeft':
					next = (index - 1 + $tabs.length) % $tabs.length;
					break;
				case 'Home':
					next = 0;
					break;
				case 'End':
					next = $tabs.length - 1;
					break;
				default:
					return;
			}

			e.preventDefault();
			activateTab($tabs.eq(next).data('target'), true);
		});

		// The server already opens the settings tab after a save. Otherwise
		// restore whichever tab this browser session left open.
		if (window.location.search.indexOf('settings-updated') === -1) {
			var stored = readStoredTab();
			if (stored && $tabs.filter('[data-target="' + stored + '"]').length) {
				activateTab(stored, false);
			}
		} else {
			storeTab('tab-settings');
		}
	});
})(jQuery);
