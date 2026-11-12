/**
 * Окно «Выберите тип страницы» и панель «Тип страницы» в редакторе страниц (ТЗ, раздел 6.1).
 *
 * Без сборки: wp.element, wp.data, wp.components. Данные типов — window.trustPageTypes
 * (Trust\Page_Types::editor_assets()).
 *
 * - Новая пустая страница: окно выбора; выбор ставит шаблон и вставляет набор блоков
 *   (только блоки, которые уже есть в теме).
 * - Смена типа позже (панель «Тип страницы» или «Шаблон»): блоки не пересоздаются; если
 *   страница пустая — предлагается вставить набор.
 * - Блоки только своего типа («Кейсы», «Ответы специалистов») скрыты на остальных типах.
 */
( function ( wp, config ) {
	if ( ! wp || ! config || ! wp.plugins || ! wp.data ) {
		return;
	}

	const el = wp.element.createElement;
	const { Fragment, useState, useEffect } = wp.element;
	const { select, dispatch, subscribe, useSelect } = wp.data;
	const { Modal, Button } = wp.components;
	const SettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );

	const NOTICE_ID = 'trust-page-type-set';
	const PANEL = 'trust-page-types/trust-page-type';
	let baseAllowed = null;
	let applying = false;

	/** Тип страницы по файлу шаблона. */
	function typeOf( template ) {
		const file = ! template || 'default' === template ? '' : template;
		return config.types.find( ( type ) => type.template === file ) || config.types.find( ( type ) => 'default' === type.key );
	}

	/** Блоки набора, которые уже зарегистрированы. */
	function availableBlocks( type ) {
		return type.blocks.filter( ( block ) => !! wp.blocks.getBlockType( block.name ) );
	}

	/** Пустая ли страница (нет блоков или один пустой абзац). */
	function isEmpty() {
		const blocks = select( 'core/block-editor' ).getBlocks();
		return 0 === blocks.length || ( 1 === blocks.length && wp.blocks.isUnmodifiedDefaultBlock( blocks[ 0 ] ) );
	}

	/**
	 * Список разрешённых блоков для типа: блоки чужих типов скрыты. Исходный список — из
	 * настроек редактора (сервер, Trust\Allowed_Blocks); меняются тоже настройки редактора,
	 * иначе он перезаписал бы список при следующем обновлении своих настроек.
	 */
	function applyAllowed( typeKey ) {
		if ( null === baseAllowed ) {
			const settings = select( 'core/editor' ).getEditorSettings();
			if ( ! Array.isArray( settings.allowedBlockTypes ) ) {
				return;
			}
			baseAllowed = settings.allowedBlockTypes;
		}
		const allowed = baseAllowed.filter( ( name ) => ! config.exclusive[ name ] || config.exclusive[ name ] === typeKey );
		dispatch( 'core/editor' ).updateEditorSettings( { allowedBlockTypes: allowed } );
	}

	/** Вставляет набор блоков типа; пустой абзац заменяется. Возвращает число блоков. */
	function insertSet( type ) {
		const blocks = availableBlocks( type ).map( ( block ) => wp.blocks.createBlock( block.name, block.attributes || {} ) );
		if ( ! blocks.length ) {
			return 0;
		}
		const current = select( 'core/block-editor' ).getBlocks();
		if ( 1 === current.length && wp.blocks.isUnmodifiedDefaultBlock( current[ 0 ] ) ) {
			dispatch( 'core/block-editor' ).replaceBlocks( current[ 0 ].clientId, blocks );
		} else {
			dispatch( 'core/block-editor' ).insertBlocks( blocks );
		}
		// Без выделения справа остаётся панель страницы, а не настройки последнего блока.
		dispatch( 'core/block-editor' ).clearSelectedBlock();
		dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
		return blocks.length;
	}

	/** Ставит шаблон типа (без подсказки о наборе — её показывает только смена шаблона вручную). */
	function setTemplate( type ) {
		applying = true;
		dispatch( 'core/editor' ).editPost( { template: type.template } );
		applyAllowed( type.key );
		applying = false;
	}

	/** Окно выбора типа. */
	function TypeModal( props ) {
		const current = typeOf( props.template );
		return el(
			Modal,
			{
				title: 'Выберите тип страницы',
				className: 'trust-page-types',
				onRequestClose: props.onClose,
			},
			el( 'p', { className: 'trust-page-types__lead' }, 'Тип задаёт шаблон страницы и набор блоков по макету. Блоки потом можно удалять, добавлять и переставлять, а тип — сменить на панели «Тип страницы» справа.' ),
			el(
				'div',
				{ className: 'trust-page-types__grid' },
				config.types.map( ( type ) => {
					const count = availableBlocks( type ).length;
					return el(
						'button',
						{
							key: type.key,
							type: 'button',
							className: 'trust-page-types__card' + ( 'change' === props.mode && type.key === current.key ? ' is-current' : '' ),
							'aria-current': 'change' === props.mode && type.key === current.key ? 'true' : undefined,
							'data-type': type.key,
							onClick: () => props.onChoose( type ),
						},
						el( 'img', { className: 'trust-page-types__thumb', src: type.thumb, alt: '', loading: 'lazy' } ),
						el( 'span', { className: 'trust-page-types__title' }, type.label ),
						el( 'span', { className: 'trust-page-types__desc' }, type.description ),
						el( 'span', { className: 'trust-page-types__count' }, count ? 'Блоков в наборе: ' + count : 'Набор блоков пока пустой' )
					);
				} )
			)
		);
	}

	/** Окно, панель и слежение за шаблоном. */
	function PageTypes() {
		const { isNew, template, postId } = useSelect( ( s ) => ( {
			isNew: s( 'core/editor' ).isEditedPostNew(),
			template: s( 'core/editor' ).getEditedPostAttribute( 'template' ),
			postId: s( 'core/editor' ).getCurrentPostId(),
		} ) );
		const [ mode, setMode ] = useState( null );
		const isBlog = !! config.blogPage && postId === config.blogPage;
		const current = typeOf( template );

		useEffect( () => {
			if ( isNew && ! isBlog && isEmpty() && current.key === 'default' ) {
				setMode( 'new' );
			}
			// Панель «Тип страницы» раскрыта при первом знакомстве; дальше — как оставил пользователь.
			const prefs = select( 'core/preferences' );
			if ( prefs && ! prefs.get( 'trust/page-types', 'panelShown' ) ) {
				if ( ! select( 'core/editor' ).isEditorPanelOpened( PANEL ) ) {
					dispatch( 'core/editor' ).toggleEditorPanelOpened( PANEL );
				}
				dispatch( 'core/preferences' ).set( 'trust/page-types', 'panelShown', true );
			}
		}, [] );

		useEffect( () => {
			applyAllowed( current.key );
		}, [ current.key ] );

		const choose = ( type ) => {
			const empty = isEmpty();
			setTemplate( type );
			setMode( null );
			if ( 'new' === mode || empty ) {
				const count = insertSet( type );
				dispatch( 'core/notices' ).createNotice( 'info', count ? `Тип «${ type.label }»: вставлено блоков — ${ count }.` : `Тип «${ type.label }».`, { type: 'snackbar' } );
			} else {
				dispatch( 'core/notices' ).createNotice( 'info', `Тип «${ type.label }». Блоки страницы не менялись.`, { type: 'snackbar' } );
			}
		};

		return el(
			Fragment,
			null,
			mode && el( TypeModal, { mode, template, onChoose: choose, onClose: () => setMode( null ) } ),
			SettingPanel && el(
				SettingPanel,
				{ name: 'trust-page-type', title: 'Тип страницы', className: 'trust-page-type-panel' },
				isBlog && el( 'p', { className: 'trust-page-type-panel__blog' }, 'Это страница блога («Настройки → Чтение → Страница записей»). На сайте вместо её блоков выводится список статей; используются заголовок страницы и её SEO-поля.' ),
				el( 'p', { className: 'trust-page-type-panel__name' }, current.label ),
				el( 'p', { className: 'trust-page-type-panel__desc' }, current.description ),
				el( Button, { variant: 'secondary', onClick: () => setMode( 'change' ) }, 'Сменить тип' )
			)
		);
	}

	wp.plugins.registerPlugin( 'trust-page-types', { render: PageTypes } );

	// Шаблон сменили вручную («Шаблон» в боковой панели): на пустой странице — предложить набор.
	let lastTemplate = null;
	subscribe( () => {
		const editor = select( 'core/editor' );
		if ( ! editor || ! editor.getCurrentPostId() ) {
			return;
		}
		const template = editor.getEditedPostAttribute( 'template' ) || '';
		if ( null === lastTemplate ) {
			lastTemplate = template;
			return;
		}
		if ( template === lastTemplate ) {
			return;
		}
		lastTemplate = template;
		if ( applying ) {
			return;
		}
		const type = typeOf( template );
		applyAllowed( type.key );
		if ( isEmpty() && availableBlocks( type ).length ) {
			dispatch( 'core/notices' ).createNotice( 'info', `Страница пустая. Вставить набор блоков типа «${ type.label }»?`, {
				id: NOTICE_ID,
				isDismissible: true,
				actions: [ { label: 'Вставить набор', onClick: () => insertSet( type ) } ],
			} );
		}
	} );
}( window.wp, window.trustPageTypes ) );
