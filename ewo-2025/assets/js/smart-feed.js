/**
 * EWO 2025 — Smart Feed public page.
 *
 * Progressive-enhancement approach:
 *  - Cards are rendered VISIBLE by default in the HTML (no hidden attribute).
 *  - JS adds the .ewo-sf-invisible class to manage tab filtering and Load More.
 *  - If JS does not run, all cards remain visible (graceful degradation).
 *
 * Initialization fires immediately if the DOM is already parsed (footer script),
 * otherwise waits for DOMContentLoaded.
 */
( function () {
	'use strict';

	var PAGE_SIZE    = 12;
	var visible      = PAGE_SIZE;
	var activeDomain = 'all';

	/* -------------------------------------------------------------------------
	   Core render — hides/shows cards via CSS class
	   ------------------------------------------------------------------------- */

	function renderCards() {
		var all      = Array.prototype.slice.call( document.querySelectorAll( '.ewo-sf-card' ) );
		var matching = all.filter( function ( card ) {
			return activeDomain === 'all' || card.getAttribute( 'data-domain-id' ) === activeDomain;
		} );

		// Hide everything first.
		all.forEach( function ( card ) {
			card.classList.add( 'ewo-sf-invisible' );
		} );

		// Show the first `visible` matching cards.
		matching.slice( 0, visible ).forEach( function ( card ) {
			card.classList.remove( 'ewo-sf-invisible' );
		} );

		// Empty-state message.
		var emptyEl = document.getElementById( 'ewo-sf-empty' );
		if ( emptyEl ) {
			if ( matching.length === 0 ) {
				emptyEl.classList.remove( 'ewo-sf-invisible' );
			} else {
				emptyEl.classList.add( 'ewo-sf-invisible' );
			}
		}

		// Load More button.
		var moreBtn = document.getElementById( 'ewo-sf-load-more' );
		if ( moreBtn ) {
			var remaining = matching.length - visible;
			if ( remaining > 0 ) {
				moreBtn.classList.remove( 'ewo-sf-invisible' );
				moreBtn.textContent = 'Load More (' + remaining + ' more)';
			} else {
				moreBtn.classList.add( 'ewo-sf-invisible' );
			}
		}
	}

	/* -------------------------------------------------------------------------
	   Tab filtering
	   ------------------------------------------------------------------------- */

	function initTabs() {
		var tabs = document.querySelectorAll( '.ewo-sf-tab' );
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				// Deactivate all tabs.
				tabs.forEach( function ( t ) {
					t.classList.remove( 'ewo-sf-tab--active' );
					t.setAttribute( 'aria-selected', 'false' );
				} );
				// Activate clicked tab.
				tab.classList.add( 'ewo-sf-tab--active' );
				tab.setAttribute( 'aria-selected', 'true' );

				activeDomain = tab.getAttribute( 'data-domain' );
				visible      = PAGE_SIZE;
				renderCards();
			} );
		} );
	}

	/* -------------------------------------------------------------------------
	   Load More
	   ------------------------------------------------------------------------- */

	function initLoadMore() {
		var moreBtn = document.getElementById( 'ewo-sf-load-more' );
		if ( ! moreBtn ) return;

		moreBtn.addEventListener( 'click', function () {
			var prevVisible = visible;
			visible += PAGE_SIZE;
			renderCards();

			// Scroll to the first newly revealed card.
			var all      = Array.prototype.slice.call( document.querySelectorAll( '.ewo-sf-card' ) );
			var matching = all.filter( function ( card ) {
				return activeDomain === 'all' || card.getAttribute( 'data-domain-id' ) === activeDomain;
			} );
			var firstNew = matching[ prevVisible ];
			if ( firstNew ) {
				firstNew.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		} );
	}

	/* -------------------------------------------------------------------------
	   Boot — dual-init handles both footer-script timing and deferred loads
	   ------------------------------------------------------------------------- */

	function boot() {
		initTabs();
		initLoadMore();
		renderCards();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

} )();
