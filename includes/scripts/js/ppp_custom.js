var tweetLengthYellow = 100;
var tweetLengthRed    = 117;

(function ($) {
	$('.share-time-selector').timepicker({ 'step': 15 });
	$('.share-date-selector').datepicker({
		dateFormat: 'mm/dd/yy',
		minDate: 0
	});

	$('#bitly-login').click( function(e) {
		e.preventDefault();

		var data   = {};
		var button = $('#bitly-login');
		button.removeClass('button-primary');
		button.addClass('button-secondary');
		button.css('opacity', '.5');
		$('.spinner').show();
		$('#ppp-bitly-invalid-login').hide();
		data.action   = 'ppp_bitly_connect';
		data.username = $('#bitly-username').val();
		data.password = $('#bitly-password').val();

		$.post(ajaxurl, data, function(response) {
			if (response == '1') {
				var url = $('#bitly-redirect-url').val();
				window.location.replace( url );
			} else if (response === 'INVALID_LOGIN') {
				$('.spinner').hide();
				$('#ppp-bitly-invalid-login').show();
				button.addClass('button-primary');
				button.removeClass('button-secondary');
				button.css('opacity', '1');
			}
		});
	});

	$('#fb-page').change( function() {
		var data = {};
		var select = $('#fb-page');
		select.attr('disabled', 'disabled');
		select.css('opacity', '.5');
		select.next('.spinner').show();
		data.action   = 'fb_set_page';
		data.account = select.val();
		select.width('75%');

		$.post(ajaxurl, data, function(response) {
			select.removeAttr('disabled');
			select.css('opacity', '1');
			select.next('.spinner').hide();
			select.width('100%');
		});
	});

	$('#ppp-tabs li').click( function(e) {
		e.preventDefault();
		$('#ppp-tabs li').removeClass('tabs');
		$(this).addClass('tabs');
		var clickedId = $(this).children(':first').attr('href');

		$('#ppp_schedule_metabox .wp-tab-panel').hide();
		$(clickedId).show();
		return false;
	});

	$('#ppp-social-connect-tabs a').click( function(e) {
		e.preventDefault();
		$('#ppp-social-connect-tabs a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		var clickedId = $(this).attr('href');

		$('.ppp-social-connect').hide();
		$(clickedId).show();
		return false;
	});

	var PPP_General_Configuration = {
		init: function() {
			this.share_on_publish();
			this.featured_image();
		},
		share_on_publish: function() {
			$('.ppp-toggle-share-on-publish').change( function() {
				var status = $(this).val();
				var target_wrapper = $(this).parent().next('.ppp-fields');

				if ( status == -1 ) {
					target_wrapper.hide();
				} else if( status == 0 ) {
					target_wrapper.show();
					target_wrapper.find('.ppp-share-on-publish').hide();
					target_wrapper.find('.ppp-schedule-share').show();
				} else {
					target_wrapper.show();
					target_wrapper.find('.ppp-share-on-publish').show();
					target_wrapper.find('.ppp-schedule-share').hide();
				}

			});
		},
		featured_image: function() {

			// WP 3.5+ uploader
			var file_frame;
			window.formfield = '';

			$('body').on('click', '.ppp-upload-file-button', function(e) {

				e.preventDefault();

				var button = $(this);

				window.formfield = $(this).closest('.ppp-repeatable-upload-wrapper');

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					file_frame.open();
					return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media( {
					frame: 'post',
					state: 'insert',
					title: button.data( 'uploader-title' ),
					button: {
						text: button.data( 'uploader-button-text' )
					},
					multiple: $( this ).data( 'multiple' ) == '0' ? false : true  // Set to true to allow multiple files to be selected
				} );

				file_frame.on( 'menu:render:default', function( view ) {
					// Store our views in an object.
					var views = {};

					// Unset default menu items
					view.unset( 'library-separator' );
					view.unset( 'gallery' );
					view.unset( 'featured-image' );
					view.unset( 'embed' );

					// Initialize the views in our view object.
					view.set( views );
				} );

				// When an image is selected, run a callback.
				file_frame.on( 'insert', function() {

					var selection = file_frame.state().get('selection');
					selection.each( function( attachment, index ) {
						attachment = attachment.toJSON();
						// place first attachment in field
						window.formfield.find( '.ppp-repeatable-attachment-id-field' ).val( attachment.id );
						window.formfield.find( '.ppp-repeatable-upload-field' ).val( attachment.url ).change();
					});
				});

				// Finally, open the modal
				file_frame.open();
			});


			// WP 3.5+ uploader
			var file_frame;
			window.formfield = '';

			$('body').on( 'change', '.ppp-upload-field', function(e) {
				if ( $(this).val() == '' ) {
					var attachment_field = $(this).prev( '.ppp-repeatable-attachment-id-field' );
					attachment_field.val( '' );
				}
			});

		},
	}
	PPP_General_Configuration.init();

	var PPP_Twitter_Configuration = {
		init: function() {
			this.add();
			this.duplicate();
			this.remove();
			this.view_all();
			this.share_on_publish();
			this.count_length();
			this.check_timestamps();
			this.show_hide_conflict_warning();
		},
		clone_repeatable: function(row, clear_data) {
			if ( typeof clear_data === 'undefined' ) {
				clear_data = true;
			}

			// Retrieve the highest current key
			var key = highest = 1;
			row.parent().find( '.ppp-repeatable-row' ).each(function() {
				var current = $(this).data( 'key' );
				if( parseInt( current ) > highest ) {
					highest = current;
				}
			});
			key = highest += 1;

			clone = row.clone();

			/** manually update any select box values */
			clone.find( 'select' ).each(function() {
				$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
			});

			clone.removeClass( 'ppp-add-blank' );
			clone.removeClass( 'ppp-row-warning' );
			clone.attr( 'data-key', key );

			if ( clear_data ) {
				clone.find( 'td input, td select, textarea' ).val( '' );
			}

			clone.find( 'input, select, textarea' ).each(function() {
				var name = $( this ).attr( 'name' );

				name = name.replace( /\[(\d+)\]/, '[' + parseInt( key ) + ']');

				$( this ).attr( 'name', name ).attr( 'id', name );
				$( this ).removeClass('hasDatepicker');
				$( this ).prop('readonly', false);
			});

			clone.find( '.ppp-upload-file' ).show();

			return clone;
		},
		add: function() {
			$( 'body' ).on( 'click', '.submit .ppp-add-repeatable', function(e) {
				e.preventDefault();
				var button = $( this ),
				row = button.parent().parent().prevAll('tr').not('.past-share').first(),
				clone = PPP_Twitter_Configuration.clone_repeatable(row);
				clone.find( '.ppp-text-length' ).text('0').css('background-color', '#339933');
				clone.insertAfter( row );

				$('.share-time-selector').timepicker({ 'step': 15 });
				$('.share-date-selector').datepicker({ dateFormat : 'mm/dd/yy', minDate: 0});
			});
		},
		duplicate: function() {
			$( 'body' ).on( 'click', '.ppp-clone-tweet', function(e) {
				e.preventDefault();
				var button = $( this ),
				row = button.parent().parent(),
				clone = PPP_Twitter_Configuration.clone_repeatable(row, false);
				clone.find('.share-time-selector').val('');
				clone.find('.share-date-selector').val('');
				clone.find('.ppp-action-icons a').show();
				clone.removeClass('past-share');
				clone.insertBefore( '.ppp-repeatable-twitter:last' );

				$('.share-time-selector').timepicker({ 'step': 15 });
				$('.share-date-selector').datepicker({ dateFormat : 'mm/dd/yy', minDate: 0});
			});
		},
		remove: function() {
			$( 'body' ).on( 'click', '.ppp-remove-repeatable', function(e) {
				e.preventDefault();

				var row        = $(this).parent().parent( 'tr' ),
					type       = $(this).data('type'),
					repeatable = 'tr.ppp-repeatable-' + type,
					count      = $(repeatable).length;

				if( count > 1 ) {
					$( 'input, select', row ).val( '' );
					row.fadeOut( 'fast' ).remove();
				} else {
					row.find('input').val('').trigger('change');
					if ( type == 'linkedin' ) {
						$('.ppp-repeatable-textarea').val('');
					}
				}

				PPP_Twitter_Configuration.show_hide_conflict_warning();

				/* re-index after deleting */
				$(repeatable).each( function( rowIndex ) {
					$(this).find( 'input, select' ).each(function() {
						var name = $( this ).attr( 'name' );
						name = name.replace( /\[(\d+)\]/, '[' + rowIndex+ ']');
						$( this ).attr( 'name', name ).attr( 'id', name );
					});
				});
			});
		},
		view_all: function() {
			$('.ppp-view-all').click( function (e) {
				e.preventDefault();
				$('.ppp-tweet-fields table .past-share').slideToggle();
			});
		},
		share_on_publish: function() {
			$('#tw #ppp_share_on_publish').click( function() {
				$(this).parent().siblings('.ppp_share_on_publish_text').toggle();
			});
		},
		count_length: function() {
			$( 'body' ).on( 'keyup change focusout', '#tw .ppp-tweet-text-repeatable, #tw .ppp-share-text, #tw .ppp-upload-field, #tw .ppp-tw-featured-image-input', function(e) {

				if ( e.shiftKey || e.ctrlKey || e.altKey ) {
					return;
				}

				var input = $(this);

				if ( input.attr('name') == '_ppp_share_on_publish_text' ) {
					var lengthField = input.next('.ppp-text-length');
					var length      = input.val().length;
				} else if ( input.hasClass('ppp-tw-featured-image-input' ) ) {
					var textWrapper = input.parent().prev();
					var lengthField = textWrapper.find('.ppp-text-length');
					var length      = textWrapper.find('.ppp-tweet-text-repeatable').val().length;
				} else if ( input.hasClass('ppp-upload-field') ) {
					var textWrapper = input.parent().parent().prev();
					var lengthField = textWrapper.find('.ppp-text-length');
					var length      = textWrapper.find('.ppp-tweet-text-repeatable').val().length;
				} else {
					var lengthField = input.next('.ppp-text-length');
					var length      = input.val().length;
				}

				if ( length < tweetLengthYellow ) {
					lengthField.css('background-color', '#339933');
				} else if ( length >= tweetLengthYellow && length <= tweetLengthRed ) {
					lengthField.css('background-color', '#CC9933');
				} else if ( length > tweetLengthRed ) {
					lengthField.css('background-color', '#FF3333');
				}

				lengthField.text(length);
			});
		},
		check_timestamps: function() {
			$( 'body' ).on( 'change', '.share-date-selector, .share-time-selector', function(e) {
				var row = $(this).parent().parent();

				var date = $(row).find('.share-date-selector').val();
				var time = $(row).find('.share-time-selector').val();
				if ( date == '' ||  time == '' ) {
					return false;
				}

				var data = {
					'action': 'ppp_has_schedule_conflict',
					'date'  : date,
					'time'  : time
				};

				$.post(ajaxurl, data, function(response) {
					if ( response == 1 ) {
						$(row).addClass( 'ppp-row-warning' );
					} else {
						$(row).removeClass( 'ppp-row-warning' );
					}

					PPP_Twitter_Configuration.show_hide_conflict_warning();
				});

			});
		},
		show_hide_conflict_warning: function() {
			if ( $('.ppp-repeatable-table > tbody > tr.ppp-row-warning').length > 0 ) {
				$('#ppp-show-conflict-warning').slideDown();
			} else {
				$('#ppp-show-conflict-warning').slideUp();
			}
		}

	}
	PPP_Twitter_Configuration.init();

	$( 'body' ).on( 'focusin', '.ppp-tweet-text-repeatable', function() {
		$('.ppp-repeatable-upload-wrapper').animate({
			width: '100px'
		}, 200, function() {});
	});

	$( 'body' ).on( 'focusout', '.ppp-tweet-text-repeatable', function() {
		$('.ppp-repeatable-upload-wrapper').animate({
			width: '200px'
		}, 200, function() {});
	});

	// Save dismiss state
	$( '.notice.is-dismissible' ).on('click', '.notice-dismiss', function ( event ) {
		event.preventDefault();
		var $this   = $(this);
		var service = $this.parent().data( 'service' );

		if( ! service ){
			return;
		}

		var data = {
			action: 'ppp_dismiss_notice-' + service,
			url: ajaxurl,
			nag: 'ppp-dismiss-refresh-' + service,
		}

		$.post(ajaxurl, data, function(response) {});

	});

})(jQuery);
