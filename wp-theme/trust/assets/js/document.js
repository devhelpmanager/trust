/**
 * Документ: содержание подсвечивает текущий раздел (aria-current="location"); на телефоне
 * «Содержание» сворачивается после выбора раздела. Текущий раздел — последний H2, верх которого
 * поднялся выше 40 % высоты окна (как в макетах); до первого раздела — первый.
 */
( () => {
	const links = [ ...document.querySelectorAll( '[data-doc-toc-link]' ) ];
	if ( ! links.length ) {
		return;
	}
	const ids = [ ...new Set( links.map( ( link ) => decodeURIComponent( link.hash.slice( 1 ) ) ) ) ];
	const heads = ids.map( ( id ) => document.getElementById( id ) ).filter( Boolean );
	let current = '';
	let frame = 0;

	const update = () => {
		frame = 0;
		const line = window.innerHeight * 0.4;
		let active = heads[ 0 ];
		heads.forEach( ( head ) => {
			if ( head.getBoundingClientRect().top <= line ) {
				active = head;
			}
		} );
		if ( ! active || active.id === current ) {
			return;
		}
		current = active.id;
		links.forEach( ( link ) => {
			if ( decodeURIComponent( link.hash.slice( 1 ) ) === current ) {
				link.setAttribute( 'aria-current', 'location' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );
	};

	const schedule = () => {
		if ( ! frame ) {
			frame = requestAnimationFrame( update );
		}
	};

	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	update();

	// Телефон: после выбора раздела список сворачивается.
	document.querySelectorAll( 'details[data-doc-toc]' ).forEach( ( details ) => {
		details.addEventListener( 'click', ( event ) => {
			if ( event.target.closest( '[data-doc-toc-link]' ) ) {
				details.open = false;
			}
		} );
	} );
} )();
