/**
 * Блок «Отправить резюме»: выбор вакансии кнопками других блоков.
 *
 * «Откликнуться» (вакансии) и «Подать заявку» (стажировка) — ссылки на #resume с
 * data-apply-vacancy: выбирают вакансию в списке формы (нет такой — «Без вакансии»), а если
 * резюме уже отправлено — сначала возвращают форму («Отправить ещё одно резюме»). Прокрутку к
 * форме делает сама ссылка; фокус переходит в поле «Имя и фамилия». С другой страницы ссылка ведёт
 * на ?vacancy=…#resume — вакансия выбирается при загрузке.
 */
( () => {
	const form = document.querySelector( '.b-resume__form' );
	const select = form && form.querySelector( '[data-resume-vacancy]' );
	if ( ! select ) {
		return;
	}

	const pick = ( value ) => {
		const option = [ ...select.options ].find( ( item ) => item.value === value );
		select.value = option ? option.value : select.options[ 0 ].value;
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	};

	document.addEventListener( 'click', ( event ) => {
		const link = event.target.closest( '[data-apply-vacancy]' );
		if ( ! link ) {
			return;
		}
		const sent = form.dataset.success ? document.getElementById( form.dataset.success ) : null;
		if ( sent && ! sent.hidden ) {
			const again = sent.querySelector( '[data-form-again]' );
			if ( again ) {
				again.click();
			}
		}
		pick( link.dataset.applyVacancy );
		const name = form.querySelector( '[name="name"]' );
		if ( name ) {
			// После прокрутки к форме (переход по ссылке #resume) — фокус в первое поле.
			requestAnimationFrame( () => name.focus( { preventScroll: true } ) );
		}
	} );

	const fromUrl = new URLSearchParams( window.location.search ).get( 'vacancy' );
	if ( fromUrl ) {
		pick( fromUrl );
	}
} )();
