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
 * Закреплённая шапка (правка заказчика): шапка всегда вверху экрана (position: sticky в CSS).
 * От 1180 px при прокрутке вниз верхняя строка уезжает и остаётся полоса меню (класс is-compact),
 * при прокрутке вверх шапка выезжает целиком. После перехода по якорю шапка остаётся свёрнутой,
 * чтобы не закрыть начало раздела. Полоса по нижнему краю шапки заполняется по мере прокрутки.
 *
 * Класс js у <html> ставит встроенный скрипт в <head> (Trust\Assets::js_class).
 * Без JavaScript списки открываются по наведению и фокусу, а мобильное меню показано целиком;
 * шапка закреплена целиком, полосы прокрутки нет.
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

		// Кнопка списка: сам пункт или стрелка рядом со ссылкой на страницу раздела.
		const toggleOf = ( item ) => item.querySelector( ':scope > .main-nav__toggle, :scope > .main-nav__split > .main-nav__toggle' );

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

	/**
	 * Закреплённая шапка: сворачивание до полосы меню и полоса прокрутки страницы.
	 */
	function initSticky() {
		const header = document.querySelector( '.site-header' );
		const top = header && header.querySelector( '.site-header__top' );
		if ( ! top ) {
			return;
		}

		const STEP = 6; // Меньшие сдвиги не меняют направление (дрожание тачпада).
		const ANCHOR_MS = 1500; // Сколько шапка остаётся свёрнутой после перехода по якорю.
		const wide = window.matchMedia( '(min-width: 1180px)' );
		const progress = header.querySelector( '.scroll-progress' );
		const root = document.documentElement;
		let lastY = window.scrollY;
		let anchorUntil = location.hash.length > 1 ? Date.now() + ANCHOR_MS : 0;
		let queued = false;

		const setCompact = ( on ) => header.classList.toggle( 'is-compact', on );

		function update() {
			queued = false;
			const y = Math.max( 0, window.scrollY );

			if ( progress ) {
				const max = root.scrollHeight - window.innerHeight;
				progress.style.setProperty( '--scroll-progress', max > 0 ? Math.min( 1, y / max ).toFixed( 4 ) : '0' );
			}

			// Пока верхняя строка не прокручена целиком, свёрнутая шапка оставила бы пустую полосу.
			if ( ! wide.matches || y <= top.offsetHeight ) {
				setCompact( false );
			} else if ( Date.now() < anchorUntil || y > lastY + STEP ) {
				setCompact( true );
			} else if ( y < lastY - STEP ) {
				setCompact( false );
			} else {
				return; // Мелкий сдвиг: копим до STEP.
			}
			lastY = y;
		}

		const queue = () => {
			if ( ! queued ) {
				queued = true;
				window.requestAnimationFrame( update );
			}
		};

		window.addEventListener( 'scroll', queue, { passive: true } );
		window.addEventListener( 'resize', queue );
		wide.addEventListener( 'change', queue );

		// Переход по якорю на этой странице: шапка свёрнута, начало раздела не закрыто.
		document.addEventListener( 'click', ( event ) => {
			const link = event.target.closest ? event.target.closest( 'a[href*="#"]' ) : null;
			if ( ! link || link.hasAttribute( 'data-trust-callback' ) ) {
				return;
			}
			const url = new URL( link.href, location.href );
			if ( url.hash.length > 1 && url.pathname === location.pathname && url.search === location.search ) {
				anchorUntil = Date.now() + ANCHOR_MS;
				queue();
			}
		} );
		window.addEventListener( 'hashchange', () => {
			anchorUntil = Date.now() + ANCHOR_MS;
			queue();
		} );
		// Пользователь сам крутит страницу — шапка снова следит за направлением.
		[ 'wheel', 'touchstart' ].forEach( ( type ) => window.addEventListener( type, () => ( anchorUntil = 0 ), { passive: true } ) );

		// Фокус с клавиатуры на логотипе, телефоне или кнопке звонка — шапка целиком (сразу её
		// показывает CSS, :focus-within) и остаётся такой, пока страницу не прокрутят вниз.
		top.addEventListener( 'focusin', () => {
			anchorUntil = 0;
			setCompact( false );
		} );

		update();
	}

	initMainNav();
	initMobileNav();
	initSticky();
} )();
