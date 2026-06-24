/**
 * EWO RSS Engine — Strategic Domains 3-column UI.
 *
 * Handles: column selection, AJAX load, inline add/edit, 3-dot menus,
 * search filtering, and feed generation.
 */
( function () {
	'use strict';

	var cfg    = window.ewoDomains || {};
	var i18n   = cfg.i18n || {};

	/* =========================================================
	   State
	   ======================================================= */

	var state = {
		domainId:      0,
		domainName:    '',
		subdomainId:   0,
		subdomainName: '',
	};

	/* =========================================================
	   Helpers
	   ======================================================= */

	function esc( str ) {
		var d = document.createElement( 'div' );
		d.appendChild( document.createTextNode( String( str ) ) );
		return d.innerHTML;
	}

	function attr( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	function el( id ) {
		return document.getElementById( id );
	}

	function show( element ) {
		if ( element ) { element.style.display = ''; element.removeAttribute( 'aria-hidden' ); }
	}

	function hide( element ) {
		if ( element ) { element.style.display = 'none'; element.setAttribute( 'aria-hidden', 'true' ); }
	}

	function setHtml( element, html ) {
		if ( element ) element.innerHTML = html;
	}

	function setBadge( id, count ) {
		var e = el( id );
		if ( e ) e.textContent = count;
	}

	function flash( message, isError ) {
		var notice = el( 'ewo-domains-notice' );
		if ( ! notice ) return;
		notice.textContent = message;
		notice.className   = 'ewo-domains-flash' + ( isError ? ' ewo-domains-flash--error' : '' );
		show( notice );
		clearTimeout( notice._timer );
		notice._timer = setTimeout( function () { hide( notice ); }, 5000 );
	}

	function closeAllMenus() {
		document.querySelectorAll( '.ewo-row-menu.is-open' ).forEach( function ( m ) {
			m.classList.remove( 'is-open' );
			var btn = m.previousElementSibling;
			if ( btn ) btn.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	/* =========================================================
	   AJAX
	   ======================================================= */

	function post( action, data, onSuccess, onError ) {
		var body = new FormData();
		body.append( 'action', 'ewo_domains_' + action );
		body.append( 'nonce',  cfg.nonce );
		if ( data ) {
			Object.keys( data ).forEach( function ( k ) {
				var v = data[ k ];
				if ( Array.isArray( v ) ) {
					v.forEach( function ( item ) { body.append( k + '[]', item ); } );
				} else {
					body.append( k, v );
				}
			} );
		}

		fetch( cfg.ajaxUrl, { method: 'POST', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( resp ) {
				if ( resp.success ) {
					if ( onSuccess ) onSuccess( resp.data );
				} else {
					var msg = ( resp.data && resp.data.message ) ? resp.data.message : i18n.error;
					flash( msg, true );
					if ( onError ) onError( msg );
				}
			} )
			.catch( function () {
				flash( i18n.error, true );
				if ( onError ) onError( i18n.error );
			} );
	}

	/* =========================================================
	   Column 2 activation helpers
	   ======================================================= */

	function activateCol( panelId ) {
		var p = el( panelId );
		if ( p ) p.classList.remove( 'ewo-col-panel--inactive' );
	}

	function deactivateCol( panelId ) {
		var p = el( panelId );
		if ( p ) p.classList.add( 'ewo-col-panel--inactive' );
	}

	/* =========================================================
	   Row rendering
	   ======================================================= */

	function makeDomainRow( d ) {
		return '<li class="ewo-col-row" data-id="' + attr( d.id ) + '" data-name="' + attr( d.name ) + '" role="option" tabindex="0">' +
			'<div class="ewo-row-main">' +
				'<span class="ewo-row-name">' + esc( d.name ) + '</span>' +
				'<span class="ewo-row-meta">' + esc( d.subdomain_count || 0 ) + ' ' + esc( i18n.subdomains ) + '</span>' +
			'</div>' +
			makeMenuHtml( d.id, d.name, 'domain' ) +
		'</li>';
	}

	function makeSubdomainRow( s ) {
		return '<li class="ewo-col-row" data-id="' + attr( s.id ) + '" data-name="' + attr( s.name ) + '" role="option" tabindex="0">' +
			'<div class="ewo-row-main">' +
				'<span class="ewo-row-name">' + esc( s.name ) + '</span>' +
				'<span class="ewo-row-meta">' + esc( s.keyword_count || 0 ) + ' ' + esc( i18n.keywords ) + '</span>' +
			'</div>' +
			makeMenuHtml( s.id, s.name, 'subdomain' ) +
		'</li>';
	}

	function makeKeywordRow( k ) {
		var badge = k.active
			? '<span class="ewo-kw-badge ewo-kw-badge--active">' + esc( i18n.active ) + '</span>'
			: '<span class="ewo-kw-badge ewo-kw-badge--inactive">' + esc( i18n.inactive ) + '</span>';

		return '<li class="ewo-col-row ewo-kw-row" data-id="' + attr( k.id ) + '" data-name="' + attr( k.keyword ) + '" data-active="' + ( k.active ? '1' : '0' ) + '" role="option" tabindex="0">' +
			'<label class="ewo-kw-check-wrap" onclick="event.stopPropagation();">' +
				'<input type="checkbox" class="ewo-kw-check" value="' + attr( k.id ) + '" />' +
			'</label>' +
			'<div class="ewo-row-main">' +
				'<span class="ewo-row-name">' + esc( k.keyword ) + '</span>' +
			'</div>' +
			badge +
			makeMenuHtml( k.id, k.keyword, 'keyword' ) +
		'</li>';
	}

	function makeMenuHtml( id, name, type ) {
		return '<div class="ewo-row-menu-wrap">' +
			'<button type="button" class="ewo-row-menu-btn" aria-haspopup="true" aria-expanded="false"><span aria-hidden="true">⋯</span></button>' +
			'<div class="ewo-row-menu" role="menu">' +
				'<button type="button" class="ewo-menu-item ewo-menu-edit" data-id="' + attr( id ) + '" data-name="' + attr( name ) + '" data-type="' + attr( type ) + '" role="menuitem">' + esc( i18n.edit ) + '</button>' +
				'<button type="button" class="ewo-menu-item ewo-menu-delete ewo-menu-danger" data-id="' + attr( id ) + '" data-type="' + attr( type ) + '" role="menuitem">' + esc( i18n.delete ) + '</button>' +
			'</div>' +
		'</div>';
	}

	function emptyHtml( message ) {
		return '<li class="ewo-col-empty">' + esc( message ) + '</li>';
	}

	/* =========================================================
	   Search filtering (client-side)
	   ======================================================= */

	function filterList( inputEl, listEl ) {
		var q = inputEl.value.toLowerCase().trim();
		var rows = listEl.querySelectorAll( '.ewo-col-row' );
		var visible = 0;
		rows.forEach( function ( row ) {
			var name = ( row.getAttribute( 'data-name' ) || '' ).toLowerCase();
			var show_ = q === '' || name.indexOf( q ) !== -1;
			row.style.display = show_ ? '' : 'none';
			if ( show_ ) visible++;
		} );
		// empty state
		var empty = listEl.querySelector( '.ewo-col-empty' );
		if ( empty ) empty.style.display = ( visible === 0 ) ? '' : 'none';
	}

	/* =========================================================
	   Column 1: Domains
	   ======================================================= */

	function initDomainSearch() {
		var inp  = el( 'ewo-search-domains' );
		var list = el( 'ewo-domains-list' );
		if ( inp && list ) {
			inp.addEventListener( 'input', function () { filterList( inp, list ); } );
		}
	}

	function initAddDomain() {
		var btn    = el( 'ewo-btn-add-domain' );
		var form   = el( 'ewo-add-domain-form' );
		var inp    = el( 'ewo-new-domain-name' );
		var save   = el( 'ewo-save-domain' );
		var cancel = el( 'ewo-cancel-domain' );

		if ( ! btn ) return;

		btn.addEventListener( 'click', function () {
			toggleAddForm( form, inp );
		} );

		cancel.addEventListener( 'click', function () {
			hide( form );
			inp.value = '';
		} );

		save.addEventListener( 'click', function () {
			var name = inp.value.trim();
			if ( ! name ) { inp.focus(); return; }
			save.disabled = true;

			post( 'add_domain', { name: name }, function ( data ) {
				save.disabled = false;
				hide( form );
				inp.value = '';

				var list = el( 'ewo-domains-list' );
				var ph   = list.querySelector( '.ewo-col-empty' );
				if ( ph ) ph.remove();

				list.insertAdjacentHTML( 'beforeend', makeDomainRow( data ) );
				setBadge( 'ewo-domains-count', data.total );
				flash( i18n.saved );
			}, function () { save.disabled = false; } );
		} );

		inp.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) save.click();
			if ( e.key === 'Escape' ) cancel.click();
		} );
	}

	function onDomainClick( row ) {
		// deselect previous
		document.querySelectorAll( '#ewo-domains-list .ewo-col-row--selected' ).forEach( function ( r ) {
			r.classList.remove( 'ewo-col-row--selected' );
		} );
		row.classList.add( 'ewo-col-row--selected' );

		state.domainId   = parseInt( row.getAttribute( 'data-id' ), 10 );
		state.domainName = row.getAttribute( 'data-name' ) || '';

		// Reset column 3.
		resetKeywordsCol();

		// Load subdomains.
		loadSubdomains( state.domainId, state.domainName );
	}

	/* =========================================================
	   Column 2: Subdomains
	   ======================================================= */

	function loadSubdomains( domainId, domainName ) {
		var list = el( 'ewo-subdomains-list' );
		list.classList.add( 'ewo-col-list--loading' );
		setHtml( list, '<li class="ewo-col-list-spinner">…</li>' );
		activateCol( 'ewo-col-subdomains' );

		post( 'get_subdomains', { domain_id: domainId }, function ( data ) {
			list.classList.remove( 'ewo-col-list--loading' );

			// Breadcrumb.
			var bc = el( 'ewo-sub-breadcrumb' );
			var bcName = el( 'ewo-bc-domain-name' );
			if ( bcName ) bcName.textContent = domainName;
			show( bc );

			// Controls.
			show( el( 'ewo-sub-controls' ) );

			// List.
			if ( data.subdomains.length === 0 ) {
				setHtml( list, emptyHtml( i18n.noSubdomains ) );
			} else {
				var html = '';
				data.subdomains.forEach( function ( s ) { html += makeSubdomainRow( s ); } );
				setHtml( list, html );
			}
			setBadge( 'ewo-subdomains-count', data.subdomains.length );

			// Re-run search if active.
			var inp = el( 'ewo-search-subdomains' );
			if ( inp && inp.value ) filterList( inp, list );
		} );
	}

	function initSubdomainSearch() {
		var inp  = el( 'ewo-search-subdomains' );
		var list = el( 'ewo-subdomains-list' );
		if ( inp && list ) {
			inp.addEventListener( 'input', function () { filterList( inp, list ); } );
		}
	}

	function initAddSubdomain() {
		var btn    = el( 'ewo-btn-add-subdomain' );
		var form   = el( 'ewo-add-subdomain-form' );
		var inp    = el( 'ewo-new-subdomain-name' );
		var save   = el( 'ewo-save-subdomain' );
		var cancel = el( 'ewo-cancel-subdomain' );

		if ( ! btn ) return;

		btn.addEventListener( 'click', function () {
			toggleAddForm( form, inp );
		} );

		cancel.addEventListener( 'click', function () {
			hide( form );
			inp.value = '';
		} );

		save.addEventListener( 'click', function () {
			var name = inp.value.trim();
			if ( ! name || ! state.domainId ) { inp.focus(); return; }
			save.disabled = true;

			post( 'add_subdomain', { domain_id: state.domainId, name: name }, function ( data ) {
				save.disabled = false;
				hide( form );
				inp.value = '';

				var list = el( 'ewo-subdomains-list' );
				var ph   = list.querySelector( '.ewo-col-empty' );
				if ( ph ) ph.remove();

				list.insertAdjacentHTML( 'beforeend', makeSubdomainRow( data ) );
				setBadge( 'ewo-subdomains-count', data.total );
				flash( i18n.saved );
			}, function () { save.disabled = false; } );
		} );

		inp.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) save.click();
			if ( e.key === 'Escape' ) cancel.click();
		} );
	}

	function onSubdomainClick( row ) {
		document.querySelectorAll( '#ewo-subdomains-list .ewo-col-row--selected' ).forEach( function ( r ) {
			r.classList.remove( 'ewo-col-row--selected' );
		} );
		row.classList.add( 'ewo-col-row--selected' );

		state.subdomainId   = parseInt( row.getAttribute( 'data-id' ), 10 );
		state.subdomainName = row.getAttribute( 'data-name' ) || '';

		loadKeywords( state.subdomainId, state.subdomainName, state.domainName );
	}

	/* =========================================================
	   Column 3: Keywords
	   ======================================================= */

	function resetKeywordsCol() {
		state.subdomainId   = 0;
		state.subdomainName = '';
		hide( el( 'ewo-kw-breadcrumb' ) );
		hide( el( 'ewo-kw-controls' ) );
		hide( el( 'ewo-add-keyword-form' ) );
		setHtml( el( 'ewo-keywords-list' ), emptyHtml( i18n.selectSubdomain ) );
		setBadge( 'ewo-keywords-count', 0 );
		deactivateCol( 'ewo-col-keywords' );
		document.querySelectorAll( '#ewo-subdomains-list .ewo-col-row--selected' ).forEach( function ( r ) {
			r.classList.remove( 'ewo-col-row--selected' );
		} );
	}

	function loadKeywords( subdomainId, subdomainName, domainName ) {
		var list = el( 'ewo-keywords-list' );
		list.classList.add( 'ewo-col-list--loading' );
		setHtml( list, '<li class="ewo-col-list-spinner">…</li>' );
		activateCol( 'ewo-col-keywords' );

		post( 'get_keywords', { subdomain_id: subdomainId }, function ( data ) {
			list.classList.remove( 'ewo-col-list--loading' );

			// Breadcrumb.
			var bcDom = el( 'ewo-bc-kw-domain' );
			var bcSub = el( 'ewo-bc-kw-subdomain' );
			if ( bcDom ) bcDom.textContent = domainName || data.domain_name;
			if ( bcSub ) bcSub.textContent = subdomainName || data.subdomain_name;
			show( el( 'ewo-kw-breadcrumb' ) );

			// Controls.
			show( el( 'ewo-kw-controls' ) );

			// List.
			if ( data.keywords.length === 0 ) {
				setHtml( list, emptyHtml( i18n.noKeywords ) );
			} else {
				var html = '';
				data.keywords.forEach( function ( k ) { html += makeKeywordRow( k ); } );
				setHtml( list, html );
			}
			setBadge( 'ewo-keywords-count', data.keywords.length );

			// Re-run search.
			var inp = el( 'ewo-search-keywords' );
			if ( inp && inp.value ) filterList( inp, list );
		} );
	}

	function initKeywordSearch() {
		var inp  = el( 'ewo-search-keywords' );
		var list = el( 'ewo-keywords-list' );
		if ( inp && list ) {
			inp.addEventListener( 'input', function () { filterList( inp, list ); } );
		}
	}

	function initAddKeyword() {
		var btn    = el( 'ewo-btn-add-keyword' );
		var form   = el( 'ewo-add-keyword-form' );
		var inp    = el( 'ewo-new-keyword-name' );
		var active = el( 'ewo-new-keyword-active' );
		var save   = el( 'ewo-save-keyword' );
		var cancel = el( 'ewo-cancel-keyword' );

		if ( ! btn ) return;

		btn.addEventListener( 'click', function () {
			toggleAddForm( form, inp );
		} );

		cancel.addEventListener( 'click', function () {
			hide( form );
			inp.value = '';
		} );

		save.addEventListener( 'click', function () {
			var kw = inp.value.trim();
			if ( ! kw || ! state.subdomainId ) { inp.focus(); return; }
			save.disabled = true;

			post( 'add_keyword', {
				subdomain_id: state.subdomainId,
				keyword:      kw,
				active:       active && active.checked ? '1' : '0',
			}, function ( data ) {
				save.disabled = false;
				hide( form );
				inp.value = '';
				if ( active ) active.checked = true;

				var list = el( 'ewo-keywords-list' );
				var ph   = list.querySelector( '.ewo-col-empty' );
				if ( ph ) ph.remove();

				list.insertAdjacentHTML( 'beforeend', makeKeywordRow( data ) );
				setBadge( 'ewo-keywords-count', data.total );
				flash( i18n.saved );
			}, function () { save.disabled = false; } );
		} );

		inp.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) save.click();
			if ( e.key === 'Escape' ) cancel.click();
		} );
	}

	function initGenerateFeeds() {
		var btn = el( 'ewo-btn-generate' );
		if ( ! btn ) return;

		btn.addEventListener( 'click', function () {
			var list    = el( 'ewo-keywords-list' );
			var checked = list ? Array.from( list.querySelectorAll( '.ewo-kw-check:checked' ) ).map( function ( c ) { return parseInt( c.value, 10 ); } ) : [];

			btn.disabled    = true;
			btn.textContent = i18n.generating;

			var payload = { subdomain_id: state.subdomainId };
			if ( checked.length > 0 ) {
				payload.keyword_ids = checked;
			}

			post( 'generate_feeds', payload, function ( data ) {
				btn.disabled    = false;
				btn.innerHTML   = '⚡ Generate Feeds';
				flash( data.message || i18n.saved );
			}, function () {
				btn.disabled  = false;
				btn.innerHTML = '⚡ Generate Feeds';
			} );
		} );
	}

	/* =========================================================
	   Inline add form toggle
	   ======================================================= */

	function toggleAddForm( form, input ) {
		if ( ! form ) return;
		var hidden = form.style.display === 'none';
		if ( hidden ) {
			show( form );
			if ( input ) { input.value = ''; input.focus(); }
		} else {
			hide( form );
		}
	}

	/* =========================================================
	   Inline edit
	   ======================================================= */

	function startInlineEdit( row, type ) {
		if ( row.classList.contains( 'ewo-row--editing' ) ) return;
		row.classList.add( 'ewo-row--editing' );

		var id      = row.getAttribute( 'data-id' );
		var current = row.getAttribute( 'data-name' ) || '';
		var main    = row.querySelector( '.ewo-row-main' );
		var menu    = row.querySelector( '.ewo-row-menu-wrap' );

		// Stash original HTML so we can restore on cancel.
		row._origMain = main ? main.outerHTML : '';

		var editHtml = '<div class="ewo-row-edit-wrap">' +
			'<input type="text" class="ewo-row-edit-input" value="' + attr( current ) + '" maxlength="191" />' +
			'<button type="button" class="button button-primary button-small ewo-edit-save" data-id="' + attr( id ) + '" data-type="' + attr( type ) + '">' + esc( i18n.save ) + '</button>' +
			'<button type="button" class="button button-small ewo-edit-cancel">' + esc( i18n.cancel ) + '</button>' +
		'</div>';

		if ( main ) main.outerHTML = editHtml;
		if ( menu ) hide( menu );

		var newInput = row.querySelector( '.ewo-row-edit-input' );
		if ( newInput ) {
			newInput.focus();
			newInput.select();

			newInput.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' )  row.querySelector( '.ewo-edit-save' ).click();
				if ( e.key === 'Escape' ) row.querySelector( '.ewo-edit-cancel' ).click();
			} );
		}
	}

	function cancelInlineEdit( row ) {
		row.classList.remove( 'ewo-row--editing' );
		var wrap = row.querySelector( '.ewo-row-edit-wrap' );
		var menu = row.querySelector( '.ewo-row-menu-wrap' );
		if ( wrap && row._origMain ) {
			wrap.outerHTML = row._origMain;
			delete row._origMain;
		}
		if ( menu ) show( menu );
	}

	function saveInlineEdit( row, saveBtn ) {
		var id   = saveBtn.getAttribute( 'data-id' );
		var type = saveBtn.getAttribute( 'data-type' );
		var inp  = row.querySelector( '.ewo-row-edit-input' );
		if ( ! inp ) return;
		var name = inp.value.trim();
		if ( ! name ) { inp.focus(); return; }

		saveBtn.disabled = true;

		var action = ( type === 'domain' ) ? 'update_domain'
			: ( type === 'subdomain' ) ? 'update_subdomain'
			: 'update_keyword';

		var payload = { id: id };
		if ( type === 'keyword' ) {
			payload.keyword = name;
		} else {
			payload.name = name;
		}

		post( action, payload, function ( data ) {
			row.classList.remove( 'ewo-row--editing' );
			row.setAttribute( 'data-name', data.name || data.keyword || name );

			var wrap = row.querySelector( '.ewo-row-edit-wrap' );
			var menu = row.querySelector( '.ewo-row-menu-wrap' );
			var newName = data.name || data.keyword || name;

			var metaHtml = '';
			if ( type === 'domain' ) {
				var metaEl = wrap ? wrap.closest( 'li' ).querySelector( '.ewo-row-meta' ) : null;
				var metaText = metaEl ? metaEl.textContent : '0 ' + i18n.subdomains;
				metaHtml = '<span class="ewo-row-meta">' + esc( metaText ) + '</span>';
			} else if ( type === 'subdomain' ) {
				var metaEl2 = wrap ? wrap.closest( 'li' ).querySelector( '.ewo-row-meta' ) : null;
				var metaText2 = metaEl2 ? metaEl2.textContent : '0 ' + i18n.keywords;
				metaHtml = '<span class="ewo-row-meta">' + esc( metaText2 ) + '</span>';
			}

			var newMain = '<div class="ewo-row-main"><span class="ewo-row-name">' + esc( newName ) + '</span>' + metaHtml + '</div>';
			if ( wrap ) wrap.outerHTML = newMain;
			if ( menu ) show( menu );

			// Update menu data-name attributes.
			row.querySelectorAll( '[data-name]' ).forEach( function ( e ) {
				if ( e !== row ) e.setAttribute( 'data-name', newName );
			} );
			if ( menu ) {
				menu.querySelectorAll( '.ewo-menu-edit' ).forEach( function ( e ) {
					e.setAttribute( 'data-name', newName );
				} );
			}

			flash( i18n.saved );
		}, function () { saveBtn.disabled = false; } );
	}

	/* =========================================================
	   Delete
	   ======================================================= */

	function deleteItem( id, type ) {
		if ( ! window.confirm( i18n.confirmDelete ) ) return;

		var action = ( type === 'domain' ) ? 'delete_domain'
			: ( type === 'subdomain' ) ? 'delete_subdomain'
			: 'delete_keyword';

		post( action, { id: id }, function ( data ) {
			var listId = ( type === 'domain' ) ? 'ewo-domains-list'
				: ( type === 'subdomain' ) ? 'ewo-subdomains-list'
				: 'ewo-keywords-list';
			var badgeId = ( type === 'domain' ) ? 'ewo-domains-count'
				: ( type === 'subdomain' ) ? 'ewo-subdomains-count'
				: 'ewo-keywords-count';

			var list = el( listId );
			var row  = list ? list.querySelector( '[data-id="' + id + '"]' ) : null;
			if ( row ) row.remove();

			setBadge( badgeId, data.total );

			if ( list && ! list.querySelector( '.ewo-col-row' ) ) {
				var msg = ( type === 'domain' ) ? 'No strategic domains yet. Add one above.'
					: ( type === 'subdomain' ) ? i18n.noSubdomains
					: i18n.noKeywords;
				setHtml( list, emptyHtml( msg ) );
			}

			// If deleted domain was selected, reset columns 2 & 3.
			if ( type === 'domain' && state.domainId === parseInt( id, 10 ) ) {
				state.domainId   = 0;
				state.domainName = '';
				setHtml( el( 'ewo-subdomains-list' ), emptyHtml( i18n.selectDomain ) );
				setBadge( 'ewo-subdomains-count', 0 );
				hide( el( 'ewo-sub-breadcrumb' ) );
				hide( el( 'ewo-sub-controls' ) );
				deactivateCol( 'ewo-col-subdomains' );
				resetKeywordsCol();
			}

			if ( type === 'subdomain' && state.subdomainId === parseInt( id, 10 ) ) {
				resetKeywordsCol();
			}

			flash( i18n.saved );
		} );
	}

	/* =========================================================
	   Event delegation — single listener per list
	   ======================================================= */

	function delegateList( listId, onRowClick ) {
		var list = el( listId );
		if ( ! list ) return;

		list.addEventListener( 'click', function ( e ) {
			// 3-dot menu button.
			var menuBtn = e.target.closest( '.ewo-row-menu-btn' );
			if ( menuBtn ) {
				e.stopPropagation();
				closeAllMenus();
				var menu = menuBtn.nextElementSibling;
				if ( menu ) {
					menu.classList.add( 'is-open' );
					menuBtn.setAttribute( 'aria-expanded', 'true' );
				}
				return;
			}

			// Edit menu item.
			var editBtn = e.target.closest( '.ewo-menu-edit' );
			if ( editBtn ) {
				e.stopPropagation();
				closeAllMenus();
				var row = editBtn.closest( '.ewo-col-row' );
				if ( row ) startInlineEdit( row, editBtn.getAttribute( 'data-type' ) );
				return;
			}

			// Delete menu item.
			var delBtn = e.target.closest( '.ewo-menu-delete' );
			if ( delBtn ) {
				e.stopPropagation();
				closeAllMenus();
				deleteItem( delBtn.getAttribute( 'data-id' ), delBtn.getAttribute( 'data-type' ) );
				return;
			}

			// Inline-edit save.
			var saveBtn = e.target.closest( '.ewo-edit-save' );
			if ( saveBtn ) {
				e.stopPropagation();
				var row2 = saveBtn.closest( '.ewo-col-row' );
				if ( row2 ) saveInlineEdit( row2, saveBtn );
				return;
			}

			// Inline-edit cancel.
			var cancelBtn = e.target.closest( '.ewo-edit-cancel' );
			if ( cancelBtn ) {
				e.stopPropagation();
				var row3 = cancelBtn.closest( '.ewo-col-row' );
				if ( row3 ) cancelInlineEdit( row3 );
				return;
			}

			// Checkbox — don't select row.
			if ( e.target.classList.contains( 'ewo-kw-check' ) ) return;

			// Row click → selection.
			var row4 = e.target.closest( '.ewo-col-row' );
			if ( row4 && ! row4.classList.contains( 'ewo-row--editing' ) ) {
				onRowClick( row4 );
			}
		} );

		// Keyboard: Enter/Space selects row.
		list.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				var row = e.target.closest( '.ewo-col-row' );
				if ( row && ! row.classList.contains( 'ewo-row--editing' ) ) {
					e.preventDefault();
					onRowClick( row );
				}
			}
		} );
	}

	/* =========================================================
	   Close menus on outside click
	   ======================================================= */

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.ewo-row-menu-wrap' ) ) {
			closeAllMenus();
		}
	} );

	/* =========================================================
	   Boot
	   ======================================================= */

	document.addEventListener( 'DOMContentLoaded', function () {
		initDomainSearch();
		initAddDomain();
		initSubdomainSearch();
		initAddSubdomain();
		initKeywordSearch();
		initAddKeyword();
		initGenerateFeeds();

		delegateList( 'ewo-domains-list',    onDomainClick );
		delegateList( 'ewo-subdomains-list', onSubdomainClick );
		// Keywords list: no selection action, but still needs menu/edit delegation.
		delegateList( 'ewo-keywords-list',   function () {} );
	} );

} )();
