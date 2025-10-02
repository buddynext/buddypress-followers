/**
 * WordPress dependencies
 */
(function() {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, TextControl, RangeControl, Placeholder } = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;

	/**
	 * Register: Following Block.
	 */
	registerBlockType( 'bp-follow/following', {
		title: __( "Users I'm Following", 'buddypress-followers' ),
		description: __( 'Show a list of member avatars that the logged-in user is following.', 'buddypress-followers' ),
		category: 'buddypress',
		icon: 'admin-users',
		keywords: [ 'buddypress', 'follow', 'following', 'users', 'members', 'community' ],
		attributes: {
			title: {
				type: 'string',
				default: "Users I'm Following"
			},
			maxUsers: {
				type: 'number',
				default: 16
			},
			userId: {
				type: 'number',
				default: 0
			}
		},
		supports: {
			html: false,
			align: true,
			spacing: {
				margin: true,
				padding: true
			}
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { title, maxUsers, userId } = attributes;
			const blockProps = useBlockProps();

			let placeholderContent;
			if ( userId > 0 ) {
				placeholderContent = el( Fragment, {},
					el( 'p', {},
						__( 'This block will display members that user #', 'buddypress-followers' ) + userId + __( ' is following.', 'buddypress-followers' )
					),
					el( 'p', {}, __( 'Max members:', 'buddypress-followers' ) + ' ' + maxUsers )
				);
			} else {
				placeholderContent = el( Fragment, {},
					el( 'p', {}, __( 'This block will display members that the logged-in user is following.', 'buddypress-followers' ) ),
					el( 'p', {}, __( 'Max members:', 'buddypress-followers' ) + ' ' + maxUsers )
				);
			}

			return el( 'div', blockProps,
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Settings', 'buddypress-followers' ) },
						el( TextControl, {
							label: __( 'Title', 'buddypress-followers' ),
							value: title,
							onChange: function( value ) {
								setAttributes( { title: value } );
							}
						}),
						el( RangeControl, {
							label: __( 'Max members to show', 'buddypress-followers' ),
							value: maxUsers,
							onChange: function( value ) {
								setAttributes( { maxUsers: value } );
							},
							min: 1,
							max: 50
						}),
						el( TextControl, {
							label: __( 'User ID', 'buddypress-followers' ),
							value: userId,
							onChange: function( value ) {
								setAttributes( { userId: parseInt( value ) || 0 } );
							},
							type: 'number',
							help: __( 'Leave as 0 to show following list for logged-in user. Enter a specific user ID to show that user\'s following list.', 'buddypress-followers' )
						})
					)
				),
				el( Placeholder, {
					icon: 'admin-users',
					label: title || __( "Users I'm Following", 'buddypress-followers' )
				}, placeholderContent )
			);
		},
		save: function() {
			return null;
		}
	} );
})();
