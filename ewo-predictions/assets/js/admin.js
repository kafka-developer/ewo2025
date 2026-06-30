/**
 * EWO Predictions — admin UI JavaScript.
 *
 * Handles:
 *  1. Confidence slider display
 *  2. Domain → Subdomain cascade on add/edit form
 *  3. Domain → Subdomain cascade on filter bar
 */
( function () {
	'use strict';

	/* ── Confidence slider ── */
	function initConfidenceSlider() {
		var slider  = document.getElementById( 'pred-confidence' );
		var display = document.getElementById( 'ewo-pred-conf-display' );
		if ( ! slider || ! display ) return;

		slider.addEventListener( 'input', function () {
			display.textContent = slider.value + '%';
			updateSliderColor( slider );
		} );
		updateSliderColor( slider );
	}

	function updateSliderColor( slider ) {
		var v   = parseInt( slider.value, 10 );
		var pct = v + '%';
		var color = v >= 80 ? '#4ade80' : v >= 60 ? '#d7a84b' : '#f87171';
		slider.style.background = 'linear-gradient(to right, ' + color + ' ' + pct + ', rgba(255,255,255,0.15) ' + pct + ')';
	}

	/* ── Cascade: domain → subdomain ── */
	function initFormCascade() {
		var domainSel = document.getElementById( 'pred-domain' );
		var subSel    = document.getElementById( 'pred-subdomain' );
		if ( ! domainSel || ! subSel ) return;

		domainSel.addEventListener( 'change', function () {
			var domainId = domainSel.value;
			if ( ! domainId || domainId === '0' ) {
				clearSubdomains( subSel );
				return;
			}
			loadSubdomains( domainId, subSel, 0 );
		} );
	}

	/* ── Cascade: filter bar domain → subdomain ── */
	function initFilterCascade() {
		var domainSel = document.getElementById( 'ewo-pred-domain-filter' );
		var subSel    = document.getElementById( 'ewo-pred-subdomain-filter' );
		if ( ! domainSel || ! subSel ) return;

		domainSel.addEventListener( 'change', function () {
			var domainId    = domainSel.value;
			var curSubVal   = parseInt( subSel.value, 10 );

			if ( ! domainId || domainId === '0' ) {
				var opts = Array.prototype.slice.call( subSel.options );
				opts.forEach( function ( opt, i ) {
					if ( i === 0 ) return;
					opt.hidden   = false;
					opt.disabled = false;
				} );
				return;
			}

			var opts = Array.prototype.slice.call( subSel.options );
			var anyVisible = false;
			opts.forEach( function ( opt, i ) {
				if ( i === 0 ) return;
				var match = opt.getAttribute( 'data-domain' ) === domainId;
				opt.hidden   = ! match;
				opt.disabled = ! match;
				if ( match ) anyVisible = true;
			} );

			if ( ! anyVisible || subSel.options[ subSel.selectedIndex ]?.hidden ) {
				subSel.value = '0';
			}
		} );
	}

	function loadSubdomains( domainId, subSel, selectedId ) {
		var cfg = window.ewoPred || {};
		if ( ! cfg.ajaxUrl ) return;

		var body = new FormData();
		body.append( 'action', 'ewo_pred_subdomains' );
		body.append( 'nonce', cfg.nonce );
		body.append( 'domain_id', domainId );

		fetch( cfg.ajaxUrl, { method: 'POST', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( ! resp.success ) return;
				clearSubdomains( subSel );
				resp.data.forEach( function ( s ) {
					var opt      = document.createElement( 'option' );
					opt.value    = s.id;
					opt.textContent = s.name;
					if ( parseInt( s.id, 10 ) === selectedId ) opt.selected = true;
					subSel.appendChild( opt );
				} );
			} );
	}

	function clearSubdomains( subSel ) {
		while ( subSel.options.length > 1 ) {
			subSel.remove( 1 );
		}
	}

	/* ── Boot ── */
	document.addEventListener( 'DOMContentLoaded', function () {
		initConfidenceSlider();
		initFormCascade();
		initFilterCascade();
	} );

} )();
