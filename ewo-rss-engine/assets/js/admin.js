/**
 * EWO RSS Engine — admin scripts.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {

		/* ----- Confirm before clearing logs ----- */
		var clearBtn = document.querySelector( '.ewo-rss-wrap .button.delete' );
		if ( clearBtn ) {
			clearBtn.addEventListener( 'click', function ( e ) {
				if ( ! window.confirm( 'Clear all import logs?' ) ) {
					e.preventDefault();
				}
			} );
		}

		/* ----- Expand / collapse domain cards ----- */
		document.querySelectorAll( '.ewo-kw-domain-header' ).forEach( function ( header ) {
			header.addEventListener( 'click', function () {
				var bodyId = header.getAttribute( 'data-toggle' );
				if ( ! bodyId ) return;
				var body = document.getElementById( bodyId );
				if ( ! body ) return;
				var icon = header.querySelector( '.ewo-kw-toggle-icon' );
				var hidden = body.style.display === 'none';
				body.style.display = hidden ? '' : 'none';
				if ( icon ) icon.textContent = hidden ? '▼' : '▶';
			} );
		} );

		/* ----- Expand / collapse subdomain sections ----- */
		document.querySelectorAll( '.ewo-kw-subdomain-header' ).forEach( function ( header ) {
			header.addEventListener( 'click', function () {
				var bodyId = header.getAttribute( 'data-toggle' );
				if ( ! bodyId ) return;
				var body = document.getElementById( bodyId );
				if ( ! body ) return;
				var icon = header.querySelector( '.ewo-kw-toggle-icon' );
				var hidden = body.style.display === 'none';
				body.style.display = hidden ? '' : 'none';
				if ( icon ) icon.textContent = hidden ? '▼' : '▶';
			} );
		} );

		/* ----- Inline keyword edit toggle ----- */
		document.querySelectorAll( '.ewo-kw-edit-toggle' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var kwId = btn.getAttribute( 'data-kw-id' );
				var editRow = document.getElementById( 'ewo-kw-edit-' + kwId );
				if ( editRow ) {
					editRow.style.display = editRow.style.display === 'none' ? '' : 'none';
					var input = editRow.querySelector( '.ewo-kw-edit-input' );
					if ( input && editRow.style.display !== 'none' ) {
						input.focus();
					}
				}
			} );
		} );

		document.querySelectorAll( '.ewo-kw-edit-cancel' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var kwId = btn.getAttribute( 'data-kw-id' );
				var editRow = document.getElementById( 'ewo-kw-edit-' + kwId );
				if ( editRow ) {
					editRow.style.display = 'none';
				}
			} );
		} );

	} );
} )();
