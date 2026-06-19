/**
 * EWO RSS Engine — admin scripts.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		// Confirm before clearing logs.
		var clearForm = document.querySelector( '.ewo-rss-wrap .button.delete' );
		if ( clearForm ) {
			clearForm.addEventListener( 'click', function ( event ) {
				if ( ! window.confirm( 'Clear all import logs?' ) ) {
					event.preventDefault();
				}
			} );
		}
	} );
} )();
