/**
 * Divit RobotTXT — Admin JavaScript
 *
 * Handles:
 *   - Quick-insert directive buttons
 *   - Load recommended template (with confirmation)
 *   - Validate button (AJAX, server-side)
 *
 * No jQuery dependency. Vanilla JS only.
 * `divitRobotsConfig` is set via wp_localize_script in PHP.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

( function () {
	'use strict';

	/** @type {HTMLTextAreaElement|null} */
	var editor = null;

	/** @type {HTMLElement|null} */
	var validationResult = null;

	// ── Boot ────────────────────────────────────────────────────────────────

	document.addEventListener( 'DOMContentLoaded', function () {
		editor           = document.getElementById( 'divit_robottxt_content' );
		validationResult = document.getElementById( 'divit-validation-result' );

		if ( ! editor ) {
			return;
		}

		bindTemplateButton();
		bindValidateButton();
		bindQuickInsertButtons();
	} );

	// ── Template button ──────────────────────────────────────────────────────

	/**
	 * Load the recommended template into the editor, with confirmation.
	 */
	function bindTemplateButton() {
		var btn = document.getElementById( 'divit-btn-template' );

		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var msg = divitRobotsConfig.i18n.confirmTemplate;

			if ( ! window.confirm( msg ) ) {
				return;
			}

			editor.value = divitRobotsConfig.defaultTemplate;
			editor.focus();
			clearValidationResult();
		} );
	}

	// ── Validate button ──────────────────────────────────────────────────────

	/**
	 * Send the editor content to the server for validation via AJAX.
	 */
	function bindValidateButton() {
		var btn = document.getElementById( 'divit-btn-validate' );

		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			setValidationResult( 'neutral', divitRobotsConfig.i18n.validating );
			btn.disabled = true;

			var body = new URLSearchParams();
			body.append( 'action', 'divit_robottxt_validate' );
			body.append( 'nonce', divitRobotsConfig.validateNonce );
			body.append( 'content', editor.value );

			fetch( divitRobotsConfig.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				},
				body: body.toString(),
			} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}
				return response.json();
			} )
			.then( function ( json ) {
				if ( ! json.success ) {
					setValidationResult( 'error', divitRobotsConfig.i18n.hasErrors, json.data ? json.data.errors : [] );
					return;
				}

				var data = json.data;

				if ( ! data.valid ) {
					setValidationResult( 'error', divitRobotsConfig.i18n.hasErrors, data.errors );
					return;
				}

				if ( data.warnings && data.warnings.length > 0 ) {
					setValidationResult( 'warning', divitRobotsConfig.i18n.hasWarnings, data.warnings );
					return;
				}

				setValidationResult( 'valid', divitRobotsConfig.i18n.validOk );
			} )
			.catch( function () {
				setValidationResult( 'error', divitRobotsConfig.i18n.networkError );
			} )
			.finally( function () {
				btn.disabled = false;
			} );
		} );
	}

	// ── Quick-insert buttons ─────────────────────────────────────────────────

	/**
	 * Insert a directive snippet at the current cursor position in the editor.
	 */
	function bindQuickInsertButtons() {
		var buttons = document.querySelectorAll( '.divit-quick-insert' );

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var value = btn.dataset.value || '';

				insertAtCursor( editor, '\n' + value );
				clearValidationResult();
				editor.focus();
			} );
		} );
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Insert text at the current cursor position inside a textarea,
	 * falling back to appending if the Selection API is unavailable.
	 *
	 * @param {HTMLTextAreaElement} textarea
	 * @param {string}              text
	 */
	function insertAtCursor( textarea, text ) {
		var start = textarea.selectionStart;
		var end   = textarea.selectionEnd;

		var before = textarea.value.substring( 0, start );
		var after  = textarea.value.substring( end );

		textarea.value = before + text + after;

		// Restore cursor position after the inserted text.
		var newPos = start + text.length;
		textarea.selectionStart = newPos;
		textarea.selectionEnd   = newPos;
	}

	/**
	 * Display the validation result area.
	 *
	 * @param {'valid'|'error'|'warning'|'neutral'} type
	 * @param {string}   message  Primary message text.
	 * @param {string[]} [items]  Optional list of error/warning items.
	 */
	function setValidationResult( type, message, items ) {
		if ( ! validationResult ) {
			return;
		}

		// Reset state classes.
		validationResult.className = 'divit-validation-result';

		if ( 'valid' === type ) {
			validationResult.classList.add( 'is-valid' );
		} else if ( 'error' === type ) {
			validationResult.classList.add( 'is-error' );
		} else if ( 'warning' === type ) {
			validationResult.classList.add( 'is-warning' );
		}

		var html = escapeHtml( message );

		if ( items && items.length > 0 ) {
			html += '<ul>';
			items.forEach( function ( item ) {
				html += '<li>' + escapeHtml( item ) + '</li>';
			} );
			html += '</ul>';
		}

		validationResult.innerHTML = html;
	}

	/**
	 * Clear the validation result area.
	 */
	function clearValidationResult() {
		if ( ! validationResult ) {
			return;
		}
		validationResult.className = 'divit-validation-result';
		validationResult.innerHTML = '';
	}

	/**
	 * Escape a string for safe insertion as innerHTML.
	 *
	 * @param {string} str
	 * @returns {string}
	 */
	function escapeHtml( str ) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;',
		};
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return map[ c ];
		} );
	}

}() );
