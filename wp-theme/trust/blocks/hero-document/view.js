/**
 * «Первый экран документа»: кнопка «Распечатать» (без JavaScript она скрыта — печатать нечем).
 */
( () => {
	document.querySelectorAll( '[data-trust-print]' ).forEach( ( button ) => {
		button.hidden = false;
		button.addEventListener( 'click', () => window.print() );
	} );
} )();
