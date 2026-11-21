/**
 * Блок «Видео»: плеер загружается только по нажатию на кнопку (ТЗ, п. 8.3) — до этого на
 * странице лишь обложка, без запросов к YouTube, Rutube или VK. Встроенный плеер — <iframe>
 * с автозапуском, файл MP4 — <video controls autoplay>. Фокус переходит на плеер.
 * Неизвестная ссылка — кнопка просто открывает её (без data-атрибута плеера).
 */
( () => {
	document.querySelectorAll( '.b-video' ).forEach( ( figure ) => {
		const play = figure.querySelector( 'a.b-video__play' );
		const frame = figure.querySelector( '.b-video__frame' );
		const { videoEmbed, videoFile, videoTitle } = figure.dataset;
		if ( ! play || ( ! videoEmbed && ! videoFile ) ) {
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
			frame.appendChild( player );
			figure.classList.add( 'is-playing' );
			player.focus();
		} );
	} );
} )();
