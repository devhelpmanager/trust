/**
 * Блок «Реквизиты»: «Скопировать реквизиты» — строки «Название: значение» в буфер обмена;
 * подпись кнопки на 2,5 секунды меняется на «Скопировано» (aria-live — скринридер её прочитает).
 */
( () => {
	const fallback = ( text ) => {
		const area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
		document.body.append( area );
		area.select();
		try {
			document.execCommand( 'copy' );
		} catch ( e ) {}
		area.remove();
	};

	document.querySelectorAll( '[data-requisites-copy]' ).forEach( ( button ) => {
		const block = button.closest( '.b-req' );
		const label = button.querySelector( '[data-requisites-copy-label]' );
		const initial = label.textContent;
		let timer;
		button.addEventListener( 'click', () => {
			const text = [ ...block.querySelectorAll( '[data-requisites] > div' ) ]
				.map( ( row ) => `${ row.querySelector( 'dt' ).textContent.trim() }: ${ row.querySelector( 'dd' ).textContent.trim() }` )
				.join( '\n' );
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).catch( () => fallback( text ) );
			} else {
				fallback( text );
			}
			label.textContent = button.dataset.copied;
			clearTimeout( timer );
			timer = setTimeout( () => {
				label.textContent = initial;
			}, 2500 );
		} );
	} );
} )();
