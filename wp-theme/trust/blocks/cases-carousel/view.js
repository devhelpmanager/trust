/**
 * Блок «Карусель кейсов»: стрелки «Предыдущий / Следующий кейс» (по кругу, как в макете),
 * счётчик «01 / 04», клавиши ← →, когда фокус на стрелках, перетаскивание пальцем и мышью
 * (правка заказчика: на компьютере тоже листается «свайпом»). Пока тянут, лента идёт за
 * указателем; отпустили дальше 60 px — следующий или предыдущий слайд, ближе — лента
 * возвращается. После перетаскивания нажатие на скриншот не открывает его на весь экран.
 * Соседние слайды скрыты от скринридеров, их ссылки недоступны с клавиатуры (inert); нажатие на
 * выглядывающий соседний слайд переходит к нему.
 */
( () => {
	const START = 8; // Сдвиг, после которого жест считается перетаскиванием, а не нажатием.
	const SWIPE = 60; // Сдвиг, после которого слайд меняется.

	function init( root ) {
		const track = root.querySelector( '[data-carousel-track]' );
		const slides = track ? Array.from( track.querySelectorAll( '[data-carousel-slide]' ) ) : [];
		const current = root.querySelector( '[data-carousel-current]' );
		const prev = root.querySelector( '[data-carousel-prev]' );
		const next = root.querySelector( '[data-carousel-next]' );
		const total = slides.length;
		if ( total < 2 || ! prev || ! next ) {
			return;
		}

		let index = 0;

		function go( to ) {
			index = ( to + total ) % total;
			track.style.setProperty( '--carousel-index', String( index ) );
			slides.forEach( ( slide, i ) => {
				const on = i === index;
				slide.classList.toggle( 'is-active', on );
				// Соседний слайд скрыт от скринридеров, его ссылки недоступны (inert). Сам слайд не inert:
				// иначе браузер не отдаёт нажатия на нём — его нельзя было бы потянуть или выбрать.
				if ( on ) {
					slide.removeAttribute( 'aria-hidden' );
				} else {
					slide.setAttribute( 'aria-hidden', 'true' );
				}
				slide.querySelectorAll( '.b-carousel__open, .b-carousel__text' ).forEach( ( part ) => ( part.inert = ! on ) );
			} );
			if ( current ) {
				current.textContent = String( index + 1 ).padStart( 2, '0' );
			}
		}

		prev.addEventListener( 'click', () => go( index - 1 ) );
		next.addEventListener( 'click', () => go( index + 1 ) );

		[ prev, next ].forEach( ( button ) =>
			button.addEventListener( 'keydown', ( event ) => {
				if ( 'ArrowLeft' === event.key || 'ArrowRight' === event.key ) {
					event.preventDefault();
					go( index + ( 'ArrowLeft' === event.key ? -1 : 1 ) );
				}
			} )
		);

		// Перетаскивание. Вертикальная прокрутка страницы пальцем остаётся браузеру (touch-action: pan-y).
		let drag = null;
		let dragged = false;

		function finish( event ) {
			if ( ! drag ) {
				return;
			}
			const dx = drag.active && event ? event.clientX - drag.x : 0;
			const active = drag.active;
			drag = null;
			track.classList.remove( 'is-dragging' );
			track.style.setProperty( '--carousel-drag', '0px' );
			if ( active ) {
				dragged = true; // Нажатие после перетаскивания не считается (см. click ниже).
				window.setTimeout( () => ( dragged = false ), 0 );
				if ( Math.abs( dx ) > SWIPE ) {
					go( index + ( dx < 0 ? 1 : -1 ) );
				}
			}
		}

		track.addEventListener( 'pointerdown', ( event ) => {
			if ( ( 'mouse' === event.pointerType && 0 !== event.button ) || ! event.isPrimary ) {
				return;
			}
			drag = { x: event.clientX, y: event.clientY, id: event.pointerId, active: false };
		} );

		track.addEventListener( 'pointermove', ( event ) => {
			if ( ! drag || event.pointerId !== drag.id ) {
				return;
			}
			const dx = event.clientX - drag.x;
			const dy = event.clientY - drag.y;
			if ( ! drag.active ) {
				if ( Math.abs( dx ) < START || Math.abs( dx ) < Math.abs( dy ) ) {
					if ( Math.abs( dy ) > START ) {
						drag = null; // Вертикальный жест — это прокрутка страницы.
					}
					return;
				}
				drag.active = true;
				track.classList.add( 'is-dragging' );
				// Мышь «привязываем» к ленте, чтобы отпускание за её пределами тоже засчиталось. Касание
				// браузер уже привязал к элементу под пальцем (события и так доходят до ленты): перехват
				// снял бы эту привязку, и жест оборвался бы.
				if ( 'mouse' === event.pointerType && track.setPointerCapture ) {
					try {
						track.setPointerCapture( event.pointerId );
					} catch ( e ) {}
				}
			}
			event.preventDefault();
			track.style.setProperty( '--carousel-drag', `${ dx }px` );
		} );

		track.addEventListener( 'pointerup', finish );
		track.addEventListener( 'pointercancel', () => finish( null ) );
		// Только сама лента: событие от картинки или ссылки внутри всплывает сюда же и не значит, что жест прерван.
		track.addEventListener( 'lostpointercapture', ( event ) => event.target === track && drag && drag.active && finish( null ) );

		// После перетаскивания не открывать скриншот и не переходить по ссылке.
		track.addEventListener(
			'click',
			( event ) => {
				if ( dragged ) {
					event.preventDefault();
					event.stopPropagation();
				}
			},
			true
		);
		track.addEventListener( 'dragstart', ( event ) => event.preventDefault() );

		// Нажатие на выглядывающий соседний слайд — переход к нему.
		track.addEventListener( 'click', ( event ) => {
			const slide = event.target.closest( '[data-carousel-slide]' );
			if ( slide && ! slide.classList.contains( 'is-active' ) ) {
				go( slides.indexOf( slide ) );
			}
		} );

		go( 0 );
	}

	document.querySelectorAll( '[data-carousel]' ).forEach( init );
} )();
