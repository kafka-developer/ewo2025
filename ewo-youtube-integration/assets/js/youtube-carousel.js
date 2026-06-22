/**
 * EWO YouTube — single-feature video slider (vanilla JS, no dependencies).
 *
 * Shows one featured video at a time with autoplay, hover/focus pause,
 * prev/next arrows, pagination dots, and touch swipe. Respects
 * prefers-reduced-motion (no autoplay) and degrades to the first slide
 * if the script never runs.
 */
( function () {
	'use strict';

	var AUTOPLAY_DELAY = 6000;

	function initFeature( root ) {
		var viewport = root.querySelector( '.ewo-youtube-feature__viewport' );
		var track = root.querySelector( '.ewo-youtube-feature__track' );
		var prev = root.querySelector( '.ewo-youtube-feature__arrow--prev' );
		var next = root.querySelector( '.ewo-youtube-feature__arrow--next' );
		var dotsWrap = root.querySelector( '.ewo-youtube-feature__dots' );

		if ( ! viewport || ! track || ! track.children.length ) {
			return;
		}

		var slides = Array.prototype.slice.call( track.children );
		var count = slides.length;
		var index = 0;
		var timer = null;
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function buildDots() {
			if ( ! dotsWrap ) {
				return;
			}

			dotsWrap.innerHTML = '';

			for ( var i = 0; i < count; i++ ) {
				( function ( idx ) {
					var dot = document.createElement( 'button' );
					dot.type = 'button';
					dot.className = 'ewo-youtube-feature__dot';
					dot.setAttribute( 'aria-label', 'Go to video ' + ( idx + 1 ) );
					dot.addEventListener( 'click', function () {
						goTo( idx );
						restart();
					} );
					dotsWrap.appendChild( dot );
				} )( i );
			}
		}

		function update() {
			track.style.transform = 'translateX(' + ( -index * 100 ) + '%)';

			for ( var s = 0; s < slides.length; s++ ) {
				slides[ s ].setAttribute( 'aria-hidden', s === index ? 'false' : 'true' );
			}

			if ( dotsWrap ) {
				var dots = dotsWrap.children;
				for ( var i = 0; i < dots.length; i++ ) {
					var isActive = i === index;
					dots[ i ].classList.toggle( 'is-active', isActive );
					dots[ i ].setAttribute( 'aria-current', isActive ? 'true' : 'false' );
				}
			}
		}

		function goTo( target ) {
			index = ( ( target % count ) + count ) % count;
			update();
		}

		function start() {
			if ( reduceMotion || count < 2 ) {
				return;
			}
			stop();
			timer = window.setInterval( function () {
				goTo( index + 1 );
			}, AUTOPLAY_DELAY );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function restart() {
			stop();
			start();
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				goTo( index - 1 );
				restart();
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				goTo( index + 1 );
				restart();
			} );
		}

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		root.addEventListener( 'focusin', stop );
		root.addEventListener( 'focusout', start );

		// Touch swipe.
		var startX = null;
		viewport.addEventListener( 'touchstart', function ( event ) {
			startX = event.touches[ 0 ].clientX;
			stop();
		}, { passive: true } );
		viewport.addEventListener( 'touchend', function ( event ) {
			if ( null === startX ) {
				return;
			}
			var delta = event.changedTouches[ 0 ].clientX - startX;
			if ( Math.abs( delta ) > 40 ) {
				goTo( index + ( delta < 0 ? 1 : -1 ) );
			}
			startX = null;
			start();
		}, { passive: true } );

		if ( count < 2 ) {
			root.classList.add( 'ewo-youtube-feature--single' );
		}

		root.classList.add( 'ewo-youtube-feature--ready' );
		buildDots();
		update();
		start();
	}

	function initAll() {
		var nodes = document.querySelectorAll( '[data-ewo-feature]' );
		Array.prototype.forEach.call( nodes, initFeature );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
