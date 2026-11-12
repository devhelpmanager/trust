/**
 * Блок «Этапы работы»: подробный текст этапа вместо картинки и короткого текста.
 *
 * - На компьютере (мышь, ширина больше 480 px) — при наведении на карточку, уход мыши
 *   возвращает картинку (правка заказчика; в макете — по нажатию). Щелчок мышью ничего
 *   не переключает, чтобы не закрыть только что открытый наведением этап.
 * - Пальцем и с клавиатуры (Enter, пробел) — по нажатию, повторное нажатие закрывает.
 * Открытыми могут быть несколько этапов. Карточка — кнопка с aria-expanded.
 */
( () => {
	const hoverMode = window.matchMedia( '(hover: hover) and (pointer: fine) and (min-width: 481px)' );
	let pointer = '';

	document.querySelectorAll( '.b-stages__card' ).forEach( ( card ) => {
		const item = card.closest( '.b-stages__item' );
		const detail = card.querySelector( '.b-stages__detail' );
		const set = ( open ) => {
			card.setAttribute( 'aria-expanded', String( open ) );
			item.classList.toggle( 'is-open', open );
			detail.hidden = ! open;
		};

		card.addEventListener( 'pointerdown', ( event ) => {
			pointer = event.pointerType;
		} );
		card.addEventListener( 'pointerenter', ( event ) => {
			if ( 'mouse' !== event.pointerType || ! hoverMode.matches || item.classList.contains( 'is-open' ) ) {
				return;
			}
			// Карточка не становится ниже, пока на ней мышь: иначе курсор мог бы оказаться
			// за её краем, этап закрылся бы и снова открылся (мигание).
			card.style.minHeight = `${ card.offsetHeight }px`;
			item.dataset.hover = '1';
			set( true );
		} );
		card.addEventListener( 'pointerleave', ( event ) => {
			if ( 'mouse' !== event.pointerType || ! item.dataset.hover ) {
				return;
			}
			delete item.dataset.hover;
			card.style.minHeight = '';
			set( false );
		} );
		card.addEventListener( 'click', ( event ) => {
			if ( event.detail > 0 && 'mouse' === pointer && hoverMode.matches ) {
				return;
			}
			delete item.dataset.hover;
			card.style.minHeight = '';
			set( 'true' !== card.getAttribute( 'aria-expanded' ) );
		} );
	} );
} )();
