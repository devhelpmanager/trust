/**
 * Просмотр картинок на весь экран (правка заказчика): блок «Картинка» (ссылка
 * a[data-trust-lightbox] на оригинал) и картинки в тексте (.prose img без ссылки).
 *
 * Окно — <dialog> поверх страницы: картинка целиком по размеру экрана, подпись, кнопка
 * «Закрыть». Закрывается кнопкой, Esc, нажатием мимо картинки; фокус возвращается на
 * картинку. На телефоне у блока «Картинка» открывается картинка для телефона, если она есть.
 * Без JavaScript ссылка блока «Картинка» открывает файл картинки.
 */
( () => {
	let dialog = null;
	let img = null;
	let caption = null;
	let trigger = null;

	/** Окно просмотра (создаётся при первом открытии). */
	function build() {
		dialog = document.createElement( 'dialog' );
		dialog.className = 'lightbox';
		dialog.setAttribute( 'aria-label', 'Картинка на весь экран' );
		dialog.innerHTML =
			'<button type="button" class="lightbox__close" aria-label="Закрыть">' +
			'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
			'</button>' +
			'<figure class="lightbox__figure"><img class="lightbox__img" alt="" decoding="async"><figcaption class="lightbox__caption" hidden></figcaption></figure>';
		img = dialog.querySelector( '.lightbox__img' );
		caption = dialog.querySelector( '.lightbox__caption' );
		dialog.querySelector( '.lightbox__close' ).addEventListener( 'click', close );
		// Нажатие мимо картинки и подписи закрывает окно.
		dialog.addEventListener( 'click', ( e ) => {
			if ( ! e.target.closest( '.lightbox__img, .lightbox__caption, .lightbox__close' ) ) {
				close();
			}
		} );
		dialog.addEventListener( 'cancel', ( e ) => {
			e.preventDefault();
			close();
		} );
		img.addEventListener( 'load', () => dialog.classList.remove( 'is-loading' ) );
		document.body.appendChild( dialog );
	}

	/** Самый большой вариант картинки из srcset (или src). */
	function largest( image ) {
		let best = image.currentSrc || image.src;
		let max = 0;
		( image.getAttribute( 'srcset' ) || '' ).split( ',' ).forEach( ( part ) => {
			const [ url, size ] = part.trim().split( /\s+/ );
			const w = parseInt( size, 10 );
			if ( url && w > max ) {
				max = w;
				best = url;
			}
		} );
		return best;
	}

	/** Подпись картинки: «Рис. N» и описание у блока «Картинка», иначе — подпись figure. */
	function captionOf( el ) {
		const figure = el.closest( 'figure' );
		const cap = figure && figure.querySelector( 'figcaption' );
		if ( ! cap ) {
			return null;
		}
		const num = cap.querySelector( '.b-image__num' );
		const text = cap.querySelector( '.b-image__text' );
		return {
			num: num ? num.textContent.trim() : '',
			text: ( num || text ? ( text ? text.textContent : '' ) : cap.textContent ).replace( /\s+/g, ' ' ).trim(),
		};
	}

	/**
	 * Открывает картинку.
	 *
	 * @param {string}      src    Адрес картинки.
	 * @param {string}      alt    Описание.
	 * @param {HTMLElement} source Что нажали (сюда вернётся фокус).
	 */
	function open( src, alt, source ) {
		if ( ! dialog ) {
			build();
		}
		trigger = source;
		dialog.classList.add( 'is-loading' );
		img.alt = alt || '';
		img.src = src;
		if ( img.complete ) {
			dialog.classList.remove( 'is-loading' );
		}
		const cap = captionOf( source );
		caption.textContent = '';
		if ( cap && ( cap.num || cap.text ) ) {
			if ( cap.num ) {
				const n = document.createElement( 'span' );
				n.className = 'lightbox__num';
				n.textContent = cap.num;
				caption.appendChild( n );
				caption.appendChild( document.createTextNode( ' ' ) );
			}
			caption.appendChild( document.createTextNode( cap.text ) );
			caption.hidden = false;
		} else {
			caption.hidden = true;
		}
		document.documentElement.classList.add( 'lightbox-open' );
		dialog.showModal();
		dialog.querySelector( '.lightbox__close' ).focus();
	}

	function close() {
		if ( ! dialog || ! dialog.open ) {
			return;
		}
		dialog.close();
		document.documentElement.classList.remove( 'lightbox-open' );
		img.removeAttribute( 'src' );
		if ( trigger ) {
			trigger.focus();
		}
	}

	// Блок «Картинка»: ссылка на оригинал.
	document.addEventListener( 'click', ( e ) => {
		const link = e.target.closest( 'a[data-trust-lightbox]' );
		if ( ! link || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 ) {
			return;
		}
		e.preventDefault();
		const mobile = link.dataset.fullMobile && window.matchMedia( '(max-width: 600px)' ).matches;
		const picture = link.querySelector( 'img' );
		open( mobile ? link.dataset.fullMobile : link.href, picture ? picture.alt : '', link );
	} );

	// Картинки в тексте (блок «Блок с текстом», текст статей) без своей ссылки.
	function initProse() {
		document.querySelectorAll( '.prose img' ).forEach( ( image ) => {
			if ( image.closest( 'a, button, [data-trust-lightbox]' ) || image.classList.contains( 'is-zoomable' ) ) {
				return;
			}
			image.classList.add( 'is-zoomable' );
			image.tabIndex = 0;
			image.setAttribute( 'role', 'button' );
			image.setAttribute( 'aria-label', image.alt ? 'Открыть на весь экран: ' + image.alt : 'Открыть картинку на весь экран' );
			const show = () => open( largest( image ), image.alt, image );
			image.addEventListener( 'click', show );
			image.addEventListener( 'keydown', ( e ) => {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					show();
				}
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initProse );
	} else {
		initProse();
	}
} )();
