/**
 * Блок «Карусель кейсов»: стрелки «Предыдущий / Следующий кейс» (по кругу, как в макете),
 * счётчик «01 / 04», листание пальцем (сдвиг больше 40 px по горизонтали) и клавишами ← →,
 * когда фокус на стрелках. Соседние слайды недоступны с клавиатуры и для скринридеров (inert):
 * ссылка на кейс есть только у текущего.
 */
( () => {
	const SWIPE = 40;

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
				slide.inert = ! on;
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

		// Пальцем: вертикальная прокрутка страницы остаётся браузеру (touch-action: pan-y).
		let start = null;
		track.addEventListener( 'pointerdown', ( event ) => {
			if ( 'mouse' !== event.pointerType ) {
				start = { x: event.clientX, y: event.clientY };
			}
		} );
		track.addEventListener( 'pointerup', ( event ) => {
			if ( ! start ) {
				return;
			}
			const dx = event.clientX - start.x;
			const dy = event.clientY - start.y;
			start = null;
			if ( Math.abs( dx ) > SWIPE && Math.abs( dx ) > Math.abs( dy ) ) {
				go( index + ( dx < 0 ? 1 : -1 ) );
			}
		} );
		track.addEventListener( 'pointercancel', () => ( start = null ) );

		go( 0 );
	}

	document.querySelectorAll( '[data-carousel]' ).forEach( init );
} )();
