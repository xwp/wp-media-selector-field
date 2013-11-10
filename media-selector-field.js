window.xteamMediaSelectorFrames = {};

jQuery(document).ready(function($){
	var setMedia = function( $el, media, size_wanted ) {
		var $img = $el.find('img').removeAttr('alt');
		var $filename = $el.find('div.filename').text( media.title );
		var style = {};
		var src, size, size_wanted;

		if ( media.type === 'image' ) {
			if ( size_wanted !== 'thumbnail' && media.sizes[size_wanted] ) {
				size         = media.sizes[size_wanted];
				style.width  = size.width;
				style.height = size.height;

				$el.find('div.attachment-preview')
					.add($el.find('div.thumbnail'))
						.css( style );
			}
			else {
				size = media.sizes.thumbnail || media.sizes.medium || media.sizes.full;
				$el.find('div.attachment-preview')
					.add($el.find('div.thumbnail'))
						.removeAttr('style');
			}

			if ( 'number' == typeof size_wanted ) {
				style.width  = size_wanted;
				style.height = size_wanted;

				$img.removeAttr('width')
					.removeAttr('height');

				$el.find('div.attachment-preview')
					.add($el.find('div.thumbnail'))
						.css( style );
			}
			else {
				$img.attr('width', size.width)
					.attr('height', size.height);
			}

			src = size.url;
			$filename.hide();
			$el.addClass('type-image');
		}
		else {
			src = media.icon;
			$filename.show();
			$el.removeClass('type-image');
		}

		$el.find('input').first().val( media.id ).triggerHandler('change.xteamMediaSelector');
		$img.attr('src', src)
			.attr('alt', media.alt);

		return $el;
	};

	$('ul.xteam-media-list.multiple').sortable();

	$('body').on('click', 'a.xteam-media-select', function(e) {
		e.preventDefault();

		var $el     = $(this);
		var entryID = $el.data('entryid');
		var fieldID = $el.data('fieldid');
		var $target = $('#'+fieldID);
		var current = [];

		$target.find('input').each( function( idx, input ) {
			if ( input.value !== '' ) {
				current.push( input.value );
			}
		});
		$target.data( 'current', current );

		if ( window.xteamMediaSelectorFrames[ fieldID ] ) {
			window.xteamMediaSelectorFrames[ fieldID ].open();
			return;
		}

		var _options = {
			className : 'media-frame xteam-media-frame',
			frame     : 'select',
			multiple  : xteamMediaSelector[ entryID ].multiple,
			title     : xteamMediaSelector[ entryID ].frame_title,
			button    : {
				text : xteamMediaSelector[ entryID ].insert_button
			}
		};
		if ( xteamMediaSelector[ entryID ].type !== '_all' ) {
			_options.library = {
				type : xteamMediaSelector[ entryID ].type
			};
		}

		window.xteamMediaSelectorFrames[ fieldID ] = wp.media( _options );

		window.xteamMediaSelectorFrames[ fieldID ].on('open', function() {
			var selection   = window.xteamMediaSelectorFrames[ fieldID ].state().get('selection');
			var current_ids = $( '#'+fieldID ).data('current');

			if ( !current_ids.length )
				return;

			_.forEach( current_ids, function(id) {
				var attachment = wp.media.attachment(id);
				attachment.fetch();
				if ( attachment ) {
					selection.add( [attachment] );
				}
			});
		});

		window.xteamMediaSelectorFrames[ fieldID ].on('select', function() {
			var $target     = $('#'+fieldID);
			var size_wanted = $target.data('size');
			var current     = $target.data('current');
			var $firstItem  = $target.children().first();
			var selection   = window.xteamMediaSelectorFrames[ fieldID ].state().get('selection');

			if ( window.xteamMediaSelectorFrames[ fieldID ].options.multiple ) {
				var $template = $firstItem.clone();
				var $newItem  = null;
				var itemID;
				selection = selection.toJSON();

				$.each( selection, function( idx, item ) {
					itemID = item.id.toString();
					if ( $.inArray(itemID, current) > -1 )
						return;

					$target.append( setMedia( $template.clone(), item, size_wanted ) );
				});

				if ( $target.is('.hidden') ) {
					$firstItem.remove();
				}
			}
			else {
				setMedia( $firstItem, selection.first().toJSON(), size_wanted );
			}

			if ( $target.is('.hidden') ) {
				$target.fadeIn(function() {
					$target.removeClass('hidden');
				});
			}
		});

		window.xteamMediaSelectorFrames[ fieldID ].open();
	})
		.on('mouseenter', 'ul.xteam-media-list li.attachment', function(e) {
			$(this).addClass('details selected');
		})
		.on('mouseleave', 'ul.xteam-media-list li.attachment', function(e) {
			$(this).removeClass('details selected');
		})
		.on('click', 'ul.xteam-media-list a.check', function(e) {
			e.preventDefault();

			var $item   = $(this).closest('li');
			var $target = $item.parent();

			if ( $item.siblings().length ) {
				$item.fadeOut( parseInt($target.data('animate')), function() {
					$item.remove();
				});
			}
			else {
				$target.fadeOut( parseInt( $target.data('animate') ), function() {
					$target.addClass('hidden').removeAttr('style');
					$item.find('input').val('')
				});
			}
		});

	$('#addtag').ajaxComplete( function( e, xhr, settings ) {
		if ( settings.data.indexOf('action=add-tag') < 0 )
			return;

		$('div.xteam-media-selector').each(function() {
			var $list = $('ul.xteam-media-list', this);

			$list.fadeOut(function() {
				$list.addClass('hidden').removeAttr('style');
			})

			$list.children().filter(function(idx) {
				if ( idx == 0 ) {
					$('input', this).val('');
					$('img', this).attr('src', '');
				}
				else {
					$(this).remove();
				}
			});
		});
	});

	$(document).ajaxSuccess(function() {
		$(this).find('ul.xteam-media-list.multiple').sortable();
	});
});
