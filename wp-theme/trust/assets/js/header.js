/**
 * Шапка: выпадающие списки главного меню и мобильное меню.
 *
 * Главное меню (от 1180 px), как в макете holst-portfolio/Header: на устройствах с мышью
 * список открывается при наведении, везде — по клику на пункт. Клик сразу после открытия
 * наведением (600 мс) не закрывает список. Закрывается по Esc (фокус возвращается на пункт),
 * клику вне меню, уходу фокуса и уходу мыши.
 *
 * Мобильное меню (уже 1180 px): открывается кнопкой-бургером; пункты с вложенными
 * раскрываются по одному. Esc закрывает меню и возвращает фокус на бургер. При переходе
 * на широкий экран меню закрывается.
 *
 * Класс js у <html> ставит встроенный скрипт в <head> (Trust\Assets::js_class).
 * Без JavaScript списки открываются по наведению и фокусу, а мобильное меню показано целиком.
 */
( () => {
	const HOVER_GUARD_MS = 600;

	/**
	 * Открыто ли модальное окно (например, «Заказать обратный звонок»).
	 * Тогда Esc закрывает только окно, а меню под ним остаётся как было.
	 */
	const modalOpen = () => null !== document.querySelector( 'dialog[open]' );

	/**
	 * Выпадающие списки главного меню.
	 */
	function initMainNav() {
		const nav = document.querySelector( '.main-nav' );
		if ( ! nav ) {
			return;
		}

		const canHover = window.matchMedia( '(hover: hover)' );
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

			toggleOf( item ).addEventListener( 'click', () => {
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
			if ( 'Escape' === event.key && openItem && ! modalOpen() ) {
				close( openItem, openItem.contains( document.activeElement ) );
			}
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( openItem && ! openItem.contains( event.target ) ) {
				close( openItem );
			}
		} );
	}

	/**
	 * Мобильное меню.
	 */
	function initMobileNav() {
		const burger = document.querySelector( '.header-burger' );
		const menu = burger && document.getElementById( burger.getAttribute( 'aria-controls' ) );
		if ( ! menu ) {
			return;
		}

		const wide = window.matchMedia( '(min-width: 1180px)' );
		const isOpen = () => 'true' === burger.getAttribute( 'aria-expanded' );

		function setOpen( open, returnFocus = false ) {
			burger.setAttribute( 'aria-expanded', String( open ) );
			menu.classList.toggle( 'is-open', open );
			if ( returnFocus ) {
				burger.focus();
			}
		}

		function setSection( item, open ) {
			item.classList.toggle( 'is-open', open );
			item.querySelector( '.mobile-nav__toggle' ).setAttribute( 'aria-expanded', String( open ) );
		}

		burger.addEventListener( 'click', () => setOpen( ! isOpen() ) );

		menu.querySelectorAll( '.mobile-nav__toggle' ).forEach( ( toggle ) => {
			const item = toggle.closest( '.mobile-nav__item' );
			toggle.addEventListener( 'click', () => {
				const willOpen = ! item.classList.contains( 'is-open' );
				menu.querySelectorAll( '.mobile-nav__item.is-open' ).forEach( ( other ) => setSection( other, false ) );
				setSection( item, willOpen );
			} );
		} );

		document.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key && isOpen() && ! modalOpen() ) {
				setOpen( false, true );
			}
		} );

		wide.addEventListener( 'change', ( event ) => {
			if ( event.matches && isOpen() ) {
				setOpen( false );
			}
		} );
	}

	initMainNav();
	initMobileNav();
} )();
