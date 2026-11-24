/**
 * Плеер видео по нажатию (ТЗ, п. 8.3): блок «Видео» и «Видеоотзывы». До нажатия на странице лишь
 * обложка, без запросов к YouTube, Rutube или VK. Встроенный плеер — <iframe> с автозапуском,
 * файл MP4/WebM — <video controls autoplay>. Фокус переходит на плеер.
 *
 * Разметка: обёртка с data-video-embed (адрес плеера) или data-video-file (файл) и
 * data-video-title; внутри — ссылка [data-video-play] и место для плеера [data-video-frame].
 * Обёртка получает класс is-playing. Без data-атрибутов ссылка просто открывает видео.
 *
 * После запуска ссылка-кнопка удаляется со страницы (её растянутая на карточку область не должна
 * перехватывать нажатия), а плеер — без обрезки родителем: скругление у самого плеера (в Safari на
 * iPhone плеер внутри скруглённой рамки с overflow: hidden может не получать касания — паузу нельзя
 * было поставить).
 */
( () => {
	document.querySelectorAll( '[data-video-embed], [data-video-file]' ).forEach( ( box ) => {
		const play = box.querySelector( '[data-video-play]' );
		const frame = box.querySelector( '[data-video-frame]' );
		const { videoEmbed, videoFile, videoTitle } = box.dataset;
		if ( ! play || ! frame ) {
			return;
		}
		play.addEventListener( 'click', ( event ) => {
			event.preventDefault();
			let player;
			if ( videoEmbed ) {
				player = document.createElement( 'iframe' );
				player.src = videoEmbed;
				player.title = videoTitle || play.getAttribute( 'aria-label' );
				player.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture; clipboard-write';
				player.allowFullscreen = true;
				player.referrerPolicy = 'strict-origin-when-cross-origin';
			} else {
				player = document.createElement( 'video' );
				player.src = videoFile;
				player.controls = true;
				player.autoplay = true;
				player.playsInline = true;
				if ( videoTitle ) {
					player.setAttribute( 'aria-label', videoTitle );
				}
			}
			player.tabIndex = 0;
			player.className = 'video-player';
			frame.appendChild( player );
			box.classList.add( 'is-playing' );
			play.remove();
			player.focus();
		} );
	} );
} )();
