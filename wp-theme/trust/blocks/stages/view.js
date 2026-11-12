/**
 * Блок «Этапы работы»: по нажатию на карточку вместо картинки и короткого текста
 * показывается подробный текст, повторное нажатие возвращает картинку.
 *
 * Как в макете (holst-stranitsy/Main → Stages): этапы раскрываются независимо,
 * открытыми могут быть несколько. Карточка — кнопка с aria-expanded; работает с клавиатуры.
 */
( () => {
	document.querySelectorAll( '.b-stages__card' ).forEach( ( card ) => {
		card.addEventListener( 'click', () => {
			const open = 'true' !== card.getAttribute( 'aria-expanded' );

			card.setAttribute( 'aria-expanded', String( open ) );
			card.closest( '.b-stages__item' ).classList.toggle( 'is-open', open );
			card.querySelectorAll( '.b-stages__pic, .b-stages__short' ).forEach( ( el ) => {
				el.hidden = open;
			} );
			card.querySelector( '.b-stages__detail' ).hidden = ! open;
		} );
	} );
} )();
