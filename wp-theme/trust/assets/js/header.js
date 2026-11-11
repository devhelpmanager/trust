/**
 * Шапка: выпадающие списки главного меню.
 *
 * Как в макете (holst-portfolio/Header): на устройствах с мышью список открывается
 * при наведении, везде — по клику на пункт. Клик сразу после открытия наведением
 * (600 мс) не закрывает список. Закрывается по Esc (фокус возвращается на пункт),
 * клику вне меню, уходу фокуса и уходу мыши.
 *
 * Без JavaScript списки открываются по наведению и фокусу (CSS, html:not(.js)).
 */
( () => {
	document.documentElement.classList.add( 'js' );

	const nav = document.querySelector( '.main-nav' );
	if ( ! nav ) {
		return;
	}

	const canHover = window.matchMedia( '(hover: hover)' );
	const HOVER_GUARD_MS = 600;
	let openItem = null;
	let hoverOpenedAt = 0;

	const toggleOf = ( item ) => item.querySelector( ':scope > .main-nav__toggle' );

	function close( item, returnFocus = false ) {
		item.classList.remove( 'is-open' );
		const toggle = toggleOf( item );
		toggle.setAttribute( 'aria-expanded', 'false' );
		if ( returnFocus ) {
			toggle.focus();
		}
		if ( openItem === item ) {
			openItem = null;
		}
	}

	function open( item ) {
		if ( openItem && openItem !== item ) {
			close( openItem );
		}
		item.classList.add( 'is-open' );
		toggleOf( item ).setAttribute( 'aria-expanded', 'true' );
		openItem = item;
	}

	nav.querySelectorAll( '.main-nav__item--parent' ).forEach( ( item ) => {
		const toggle = toggleOf( item );

		item.addEventListener( 'mouseenter', () => {
			if ( ! canHover.matches ) {
				return;
			}
			hoverOpenedAt = Date.now();
			open( item );
		} );

		item.addEventListener( 'mouseleave', () => {
			if ( canHover.matches ) {
				close( item );
			}
		} );

		toggle.addEventListener( 'click', () => {
			if ( ! item.classList.contains( 'is-open' ) ) {
				open( item );
			} else if ( Date.now() - hoverOpenedAt > HOVER_GUARD_MS ) {
				close( item );
			}
		} );

		item.addEventListener( 'focusout', ( event ) => {
			if ( ! item.contains( event.relatedTarget ) ) {
				close( item );
			}
		} );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && openItem ) {
			close( openItem, openItem.contains( document.activeElement ) );
		}
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( openItem && ! openItem.contains( event.target ) ) {
			close( openItem );
		}
	} );
} )();
