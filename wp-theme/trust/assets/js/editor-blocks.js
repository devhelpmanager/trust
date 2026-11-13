/**
 * Редактор блоков: заголовки только H2–H4 (ТЗ, п. 8.1). H1 страницы — в первом экране,
 * H5 и H6 в текстах сайта не используются.
 */
( () => {
	if ( ! window.wp || ! wp.hooks ) {
		return;
	}
	wp.hooks.addFilter( 'blocks.registerBlockType', 'trust/heading-levels', ( settings, name ) => {
		if ( 'core/heading' !== name || ! settings.attributes || ! settings.attributes.levelOptions ) {
			return settings;
		}
		return {
			...settings,
			attributes: {
				...settings.attributes,
				levelOptions: { ...settings.attributes.levelOptions, default: [ 2, 3, 4 ] },
			},
		};
	} );
} )();
