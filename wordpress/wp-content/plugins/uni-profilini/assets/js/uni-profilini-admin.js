uni_profilini.mediaManager = {};

jQuery( document ).ready( function( $ ) {
    'use strict';

    //***********************
    // social icons
    //***********************

    //
    var uniProfiliniSiEl = $('.uni-profilini-si').fontIconPicker({
        theme: 'fip-darkgrey',
        emptyIconValue    : ''
    });
    uniProfiliniSiEl.setIcons(uni_profilini.fa_icons);

        if ( $(".uni-profilini-si-repeat").length > 0 ) {
            $(".uni-profilini-si-repeat").each(function() {
                $(this).repeatable_fields({
                    wrapper: '.uni-profilini-si-wrapper',
                    container: '.uni-profilini-si-container',
                    row: '.uni-profilini-si-row',
                    add: '.uni-profilini-si-add',
                    remove: '.uni-profilini-si-remove',
                    move: '.uni-profilini-si-move',
                    template: '.uni-profilini-si-template',
                    is_sortable: true,
                    before_add: null,
                    after_add: uni_profilini_si_after_add_icon,
                    before_remove: null,
                    after_remove: null,
                    sortable_options: null,
                    row_count_placeholder: '{{row-count}}',
                });
            });
        }

    // uni_after_add_suboption
	function uni_profilini_si_after_add_icon(container, new_row) {
			var row_count = $(container).attr('data-rf-row-count');

			row_count++;

			$('*', new_row).each(function() {
				$.each(this.attributes, function(index, element) {
					this.value = this.value.replace('{{row-count}}', row_count - 1);
				});
			});

			$(container).attr('data-rf-row-count', row_count);

            // init fontIconPicker
            var newUniProfiliniSiEl = new_row.find('.uni-profilini-si-inputs-holder input').first();

            newUniProfiliniSiEl.fontIconPicker({
                theme: 'fip-darkgrey',
                emptyIconValue    : '',
                source : uni_profilini.fa_icons
            });

	}


    //***********************
    // media uploader
    //***********************

    $.extend( uni_profilini.mediaManager, { controller: {} } );

    uni_profilini.mediaManager.controller.AvatarCropper = wp.media.controller.Cropper.extend({
    	doCrop: function( attachment ) {
    		var cropDetails     = attachment.get( 'cropDetails' ),
    			avatarSettings  = this.get( 'avatarSettings' ),
    			ratio           = cropDetails.width / cropDetails.height,
                attach_id       = attachment.get( 'id' );

    		// Use crop measurements when flexible in both directions.
    		if ( avatarSettings.flex_width && avatarSettings.flex_height ) {
    			cropDetails.dst_width  = cropDetails.width;
    			cropDetails.dst_height = cropDetails.height;

    		// Constrain flexible side based on image ratio and size of the fixed side.
    		} else {
    			cropDetails.dst_width  = avatarSettings.flex_width  ? avatarSettings.height * ratio : avatarSettings.width;
    			cropDetails.dst_height = avatarSettings.flex_height ? avatarSettings.width  / ratio : avatarSettings.height;
    		}

    		return wp.ajax.post( 'uni_profilini_crop_image', {
    			nonce: attachment.get( 'nonces' ).profilini_edit,
    			id: attach_id,
                user_id: this.get( 'uid' ),
    			context: 'avatar_crop',
    			cropDetails: cropDetails
    		} );
    	}
    });

	/**
	 * wp.media.profiliniMediaManager
	 * @namespace
	 */
	wp.media.profiliniMediaManager = {
	    profiliniData: {},
		set: function( id ) {

			wp.media.post( 'uni_profilini_get_avatar_thumb_html', {
				user_id: wp.media.profiliniMediaManager.profiliniData.uid,
				id: id,
				//nonce: wp.media.profiliniMediaManager.profiliniData.attachment.get( 'nonces' ).profilini_edit,
			}).done( function( html ) {
				if ( html == '0' ) {
					window.alert( 'Error!' );
					return;
				}
                if ( id == -1 ) {
                    $( '.js-uni-profilini-avatar-add' ).removeClass('uni-profilini-avatar-added');
                } else {
                    $( '.js-uni-profilini-avatar-add' ).addClass('uni-profilini-avatar-added');
                }
				$( '.js-uni-profilini-avatar-container' ).html( html );
			});
		},
		remove: function() {
			wp.media.profiliniMediaManager.set( -1 );
		},
		frame: function() {
			if ( this._frame ) {
			    console.log('1');
				wp.media.frame = this._frame;
				return this._frame.setState( 'library' );
			}

            // more info in 'wp.media.view.AttachmentFilters'
            wp.media.view.settings.post.id = null;

            this._frame = wp.media({
				button: {
					text: wp.media.view.l10n.select,
					close: false
				},
				states: [
					new wp.media.controller.Library({
						title: 'Select image for avatar',
						library: wp.media.query({ type: 'image' }),
						multiple: false,
						date: false,
						priority: 20,
                        uploadedTo: null,
						suggestedWidth: 500,
						suggestedHeight: 500
					}),
					new uni_profilini.mediaManager.controller.AvatarCropper({
						imgSelectOptions: this.calculateImageSelectOptions,
                        avatarSettings: uni_profilini.avatar_settings,
                        uid: wp.media.profiliniMediaManager.profiliniData.uid
					})
				]
			});

			this._frame.on( 'select', this.onSelect, this );
			this._frame.on( 'cropped', this.onCropped, this );
			this._frame.on( 'skippedcrop', this.onSkippedCrop, this );

            return this._frame;
		},
        onSelect: function() {
            var attachment = this._frame.state().get( 'selection' ).first().toJSON();

            if ( uni_profilini.avatar_settings.width === attachment.width
                && uni_profilini.avatar_settings.height === attachment.height
                && ! uni_profilini.avatar_settings.flex_width && ! uni_profilini.avatar_settings.flex_height ) {
				wp.media.profiliniMediaManager.setImageFromAttachment( attachment );
				this._frame.close();
			} else {
				this._frame.setState( 'cropper' );
			}
		},
        onCropped: function( croppedImage ) {
            //console.log(croppedImage);
			wp.media.profiliniMediaManager.setImageFromAttachment( croppedImage );
		},
        calculateImageSelectOptions: function( attachment, controller ) {

			var params     = controller.get( 'avatarSettings' ),
				flexWidth  = !! parseInt( params.flex_width, 10 ),
				flexHeight = !! parseInt( params.flex_height, 10 ),
				realWidth  = attachment.get( 'width' ),
				realHeight = attachment.get( 'height' ),
				xInit = parseInt( params.width, 10 ),
				yInit = parseInt( params.height, 10 ),
				ratio = xInit / yInit,
				xImg  = xInit,
				yImg  = yInit,
				x1, y1, imgSelectOptions;

			controller.set( 'canSkipCrop', ! wp.media.profiliniMediaManager.mustBeCropped( flexWidth, flexHeight, xInit, yInit, realWidth, realHeight ) );

			if ( realWidth / realHeight > ratio ) {
				yInit = realHeight;
				xInit = yInit * ratio;
			} else {
				xInit = realWidth;
				yInit = xInit / ratio;
			}

			x1 = ( realWidth - xInit ) / 2;
			y1 = ( realHeight - yInit ) / 2;

			imgSelectOptions = {
				handles: true,
				keys: true,
				instance: true,
				persistent: true,
				imageWidth: realWidth,
				imageHeight: realHeight,
				minWidth: xImg > xInit ? xInit : xImg,
				minHeight: yImg > yInit ? yInit : yImg,
				x1: x1,
				y1: y1,
				x2: xInit + x1,
				y2: yInit + y1
			};

			if ( flexHeight === false && flexWidth === false ) {
				imgSelectOptions.aspectRatio = xInit + ':' + yInit;
			}

			if ( true === flexHeight ) {
				delete imgSelectOptions.minHeight;
				imgSelectOptions.maxWidth = realWidth;
			}

			if ( true === flexWidth ) {
				delete imgSelectOptions.minWidth;
				imgSelectOptions.maxHeight = realHeight;
			}

			return imgSelectOptions;
		},
        mustBeCropped: function( flexW, flexH, dstW, dstH, imgW, imgH ) {
			if ( true === flexW && true === flexH ) {
				return false;
			}

			if ( true === flexW && dstH === imgH ) {
				return false;
			}

			if ( true === flexH && dstW === imgW ) {
				return false;
			}

			if ( dstW === imgW && dstH === imgH ) {
				return false;
			}

			if ( imgW <= dstW ) {
				return false;
			}

			return true;
		},
        onSkippedCrop: function() {
			var attachment = this._frame.state().get( 'selection' ).first();

            // saves the selected image as avatar for the given user
            wp.ajax.post( 'uni_profilini_crop_image', {
    			nonce: attachment.get( 'nonces' ).profilini_edit,
    			id: attachment.id,
                user_id: wp.media.profiliniMediaManager.profiliniData.uid,
    			context: 'avatar_skipped_crop'
    		}).done( function( html ) {
				wp.media.profiliniMediaManager.setImageFromAttachment( attachment );
			});
		},
        setImageFromAttachment: function( attachment ) {
			wp.media.profiliniMediaManager.profiliniData.attachment = attachment;
			wp.media.profiliniMediaManager.set( attachment ? attachment.id : -1 );
		},
		init: function() {
			$(document.body).on( 'click', '.js-uni-profilini-avatar-add', function( event ) {
				event.preventDefault();
				event.stopPropagation();

                var $link = $(this),
                    user_id = $link.data('uid');

                if ( user_id ) {
                    wp.media.profiliniMediaManager.profiliniData.uid = user_id;
				    wp.media.profiliniMediaManager.frame().open();
                }
			}).on( 'click', '.js-uni-profilini-avatar-remove', function( event ) {
			    event.preventDefault();
				event.stopPropagation();

                var $link = $(this),
                    user_id = $link.data('uid');

                if ( user_id ) {
                    wp.media.profiliniMediaManager.profiliniData.uid = user_id;
				    wp.media.profiliniMediaManager.remove();
                }
				return false;
			});
		}
	};

	$( wp.media.profiliniMediaManager.init );

});