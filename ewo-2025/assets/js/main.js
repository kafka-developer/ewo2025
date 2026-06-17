( function () {
	var navigation = document.getElementById( 'site-navigation' );

	if ( ! navigation ) {
		return;
	}

	var button = navigation.querySelector( '.menu-toggle' );
	var menu = navigation.querySelector( '#primary-menu' );

	if ( ! button || ! menu ) {
		return;
	}

	button.addEventListener( 'click', function () {
		var expanded = button.getAttribute( 'aria-expanded' ) === 'true';

		button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		navigation.classList.toggle( 'is-open', ! expanded );
	} );

	menu.addEventListener( 'click', function ( event ) {
		if ( event.target && event.target.tagName === 'A' ) {
			button.setAttribute( 'aria-expanded', 'false' );
			navigation.classList.remove( 'is-open' );
		}
	} );
}() );
