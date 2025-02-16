window.Parsley.setLocale("en");

var UniCalendar = UniCalendar || {};
var UniCalendarsGrid;

if ( typeof UniCalendar.timeFormat !== 'undefined' && UniCalendar.timeFormat === '24' ) {
    var uniTimeFormat = 'HH:mm',
        uniDateTimeFormat = 'HH:mm YYYY-MM-DD',
        uniAmPm = false;
} else {
    var uniTimeFormat = 'h:mm a',
        uniDateTimeFormat = 'h:mm a YYYY-MM-DD',
        uniAmPm = true;
}

(function( $, UniCalendar ) {
	'use strict';

	/**
	 * A mixin for collections/models.
	 * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
	 */
	var AdminAjaxSyncableMixin = {
		url: ajaxurl,
		action: 'uni_ec_request',
		sync: function( method, object, options ) {
			if ( typeof options.data === 'undefined' ) {
				options.data = {};
			}
            //console.log(options);

			options.data.nonce = UniCalendar.nonce; // From localized script.
			options.data.action_type = method;
            options.data.cheaters_always_disable_js = 'true_bro';

			// If no action defined, set default.
            if ( typeof this.attributes !== 'undefined' && this.attributes.uniAction ) {
                options.data.action = this.attributes.uniAction;
            }
			else if ( undefined === options.data.action && undefined !== this.action ) {
				options.data.action = this.action;
			}

			// Reads work just fine.
			if ( 'read' === method ) {
				return Backbone.sync( method, object, options );
			}

			var json = this.toJSON();
			var formattedJSON = {};

			if ( json instanceof Array ) {
				formattedJSON.models = json;
			} else {
				formattedJSON.model = json;
			}

			_.extend( options.data, formattedJSON );

			// Need to use "application/x-www-form-urlencoded" MIME type.
			options.emulateJSON = true;

			// Force a POST with "create" method if not a read, otherwise admin-ajax.php does nothing.
			return Backbone.sync.call( this, 'create', object, options );
		}
	};

	/**
	 * A model for all your syncable models to extend.
	 * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
	 */
	var BaseModel = Backbone.Model.extend( _.defaults( {}, AdminAjaxSyncableMixin ) );

	/**
	 * A collection for all your syncable collections to extend.
	 * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
	 */
	var BaseCollection = Backbone.Collection.extend( _.defaults( {}, AdminAjaxSyncableMixin ) );

	/**
	 * Single calendar model.
	 */
	UniCalendar.CalendarModel = BaseModel.extend( {
	    initialize: function( options ) {
            this.calEvents = new UniCalendar.EventsCollection(options.calEvents);
        },
		action: 'uni_ec_get_calendars',
		defaults: {
			id: null,
			title: null,
            meta: {}
		},
        parse: function( res ) {
            res.calEvents && this.calEvents.reset( res.calEvents );

            return res;
        }
	} );

	/**
	 * Collection of calendars.
	 */
	UniCalendar.CalendarsCollection = BaseCollection.extend( {
		action: 'uni_ec_get_calendars',
		model: UniCalendar.CalendarModel
	} );

	/**
	 * Single event model.
	 */
	UniCalendar.EventModel = BaseModel.extend( {
		action: 'uni_ec_get_event',
		defaults: {
			id: null,
			title: null,
            start: null,
            end: null,
            className: null,
            backgroundColor: null,
            borderColor: null,
            textColor: null,
            url: null,
            meta: {}
		},
        toJSON: function() {
            return _.pick(this.attributes, 'id', 'title', 'start', 'end', 'className', 'backgroundColor', 'borderColor', 'textColor', 'url', 'meta');
        }
	} );

	/**
	 * Collection of events.
	 */
	UniCalendar.EventsCollection = BaseCollection.extend( {
		action: 'uni_ec_get_events',
		model: UniCalendar.EventModel
	} );




	/**
	 * Calendar add form view
	 */
    UniCalendar.CalendarAddFormView = Backbone.View.extend( {
		el: '#js-uni-calendars-container',

        template: _.template( $( '#js-uni-calendar-add-calendar-tmpl' ).html() ),
		initialize: function() {
		},
		events:
			{
			    "click .js-uni-calendar-confirm-cancel-btn": "closeForm",
				"click .js-uni-calendar-confirm-add-calendar-btn": "proceedAddition"
			},
		render: function() {
			var html = this.template();
			this.$el.html( html );

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-calendar-confirm-cancel-btn');
            this.$el.off('click', '.js-uni-calendar-confirm-add-calendar-btn');
            this.remove();
            $('#uni-ec-admin-notice').remove();
            build_all_calendars_table();
		},
        proceedAddition: function() {
            var thisView = this;
            var newCal = new UniCalendar.CalendarModel([]); // an empty array of calEvents

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('.uni-ec-form-content');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( newCal.meta, validationResults.uniInputsData );
                //console.log(newCal);
                newCal.save(
                    { uniAction: 'uni_ec_add_calendar', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' ) {
                                UniCalendar.calendars.fetch({reset: true});
                                thisView.closeForm();
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        remove: function() {
            this.$el.empty().off();
            this.stopListening();
            return this;
        }
	});

	/**
	 * Calendar edit form view
	 */
    UniCalendar.CalendarEditFormView = Backbone.View.extend( {
		el: '#js-uni-calendars-container',

        template: _.template( $( '#js-uni-calendar-edit-calendar-tmpl' ).html() ),
		initialize: function() {
		    this.listenTo( this.model, 'sync', this.render );
		},
		events:
			{
			    "click .js-uni-calendar-confirm-cancel-btn": "closeForm",
				"click .js-uni-calendar-confirm-edit-calendar-btn": "proceedEdit",
                "click .js-uni-calendar-token-btn": "proceedToken",
                "click .js-uni-calendar-info-btn": "fetchInfo"
			},
		render: function() {
		    var additionalObj = {
                userRoles: UniCalendar.data.userRoles,
                calCats: UniCalendar.data.calCats
		    };
		    var tmplVars = uni_merge_options( this.model.toJSON(), additionalObj );
			var html = this.template( tmplVars );
			this.$el.html( html );

            //
            var arrConstrainedEls = this.$el.find('[data-constrained]');
            this.$el.find('[data-constrainer]').on("change", function(e){
                uniEcConstrainedFields( arrConstrainedEls );
            });
            uniEcConstrainedFields( arrConstrainedEls );

            //
            $(".js-uni-ec-datepicker-date").periodpicker({
                lang: UniCalendar.locale,
                inline: false,
                draggable: false,
                clearButtonInButton: true,
                withoutBottomPanel: true,
                yearsLine: false,
                norange: true,
                cells: [1, 1],
                resizeButton: false,
                fullsizeButton: false,
                fullsizeOnDblClick: false,
                timepicker: false,
                formatDate: 'YYYY-MM-DD',
                formatDecoreDateWithYear: 'YYYY-MM-DD'
            });

                //
                $(".js-uni-ec-datepicker-time").timepickeralone({
                    lang: UniCalendar.locale,
                    withoutBottomPanel: true,
                    inline: false,
                    inputFormat: uniTimeFormat,
                    hours: true,
                    minutes: true,
                    seconds: false,
                    twelveHoursFormat: uniAmPm,
                    ampm: uniAmPm,
                    steps:[1,5,1,1],
                    defaultTime: '',
                    //ampm:true
                });

                //
                $(".js-uni-ec-datepicker-minutes").timepickeralone({
                    lang: UniCalendar.locale,
                    withoutBottomPanel: true,
                    inline: false,
                    inputFormat: 'mm',
                    hours: false,
                    minutes: true,
                    seconds: false,
                    twelveHoursFormat: false,
                    ampm: false,
                    steps:[1,5,5,1],
                    defaultTime: '00',
                    inline:true
                });

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-calendar-confirm-cancel-btn');
            this.$el.off('click', '.js-uni-calendar-confirm-edit-calendar-btn');
            this.remove();
            $('#uni-ec-admin-notice').remove();
            build_all_calendars_table();
		},
        proceedEdit: function() {
            var thisView = this;
            var thisCalModel = thisView.model;

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('.uni-ec-form-content');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( thisCalModel.meta, validationResults.uniInputsData );
                // edit the calendar
                thisCalModel.save(
                    { uniAction: 'uni_ec_edit_calendar', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'success' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-success"><p>'+response.message+'</p></div>');
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        proceedToken: function(e) {

            e.preventDefault();

            var thisView = this;
            var thisCalModel = thisView.model;

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            var $elBtn = $(e.currentTarget),
                service = $elBtn.data('service'),
                operationtype = $elBtn.data('operationtype');

            var operationData = {
                service: service,
                operationtype: operationtype
            }

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('.uni-ec-form-content');
                $container.block({
    	            message: null,
                    overlayCSS: { background: '#fff', opacity: 0.6 }
                });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( thisCalModel.meta, validationResults.uniInputsData );
                updatedMeta = uni_merge_options( updatedMeta, operationData );
                // edit the calendar
                thisCalModel.save(
                    { uniAction: 'uni_ec_get_calendar_access_token', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'success' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-success"><p>'+response.message+'</p></div>');
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        fetchInfo: function(e) {

            e.preventDefault();

            var thisView = this;
            var thisCalModel = thisView.model;

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            var $elBtn = $(e.currentTarget),
                service = $elBtn.data('service'),
                operationtype = $elBtn.data('operationtype');

            var operationData = {
                service: service,
                operationtype: operationtype
            }

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('.uni-ec-form-content');
                $container.block({
    	            message: null,
                    overlayCSS: { background: '#fff', opacity: 0.6 }
                });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( thisCalModel.meta, validationResults.uniInputsData );
                updatedMeta = uni_merge_options( updatedMeta, operationData );
                // edit the calendar
                thisCalModel.save(
                    { uniAction: 'uni_ec_get_calendar_fetch_info', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'success' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-success"><p>'+response.message+'</p></div>');
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        remove: function() {
            this.$el.empty().off();
            this.stopListening();

            $("#js-uni-calendars-container").scrollintoview();

            return this;
        }
	});

	/**
	 * Calendar delete promt msg view
	 */
    UniCalendar.CalendarDeletePromtView = Backbone.View.extend( {
		el: '#js-uni-calendars-container',

        template: _.template( $( '#js-uni-calendar-confirm-del-calendar-tmpl' ).html() ),
		initialize: function() {
		    this.listenTo( this.model, 'sync', this.render );
		},
		events:
			{
			    "click .js-uni-calendar-confirm-cancel-btn": "closeForm",
				"click .js-uni-calendar-confirm-del-calendar-btn": "proceedDeletion"
			},
		render: function() {
			var html = this.template( this.model.toJSON() );
			this.$el.html( html );

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-calendar-confirm-cancel-btn');
            this.$el.off('click', '.js-uni-calendar-confirm-del-calendar-btn');
            this.remove();
            $('#uni-ec-admin-notice').remove();
            build_all_calendars_table();
		},
        proceedDeletion: function() {
            var thisView = this;

            // removes admin notice
            $('#uni-ec-admin-notice').remove();
            // block
            var $container = $('.uni-ec-prompt-content');
            $container.block({
                message: null,
                overlayCSS: { background: '#fff', opacity: 0.6 }
            });
            // makes ajax request
            thisView.model.save(
                { uniAction: 'uni_ec_delete_calendar' },
                {
                    success: function( model, response, options ) {
                        $container.unblock();
                        if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                            $('#uni-ec-admin-notice p').html(response.message);
                        } else if ( response.status === 'error' ) {
                            $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                        }

                        if ( response.status === 'success' ) {
                            //UniCalendar.calendars.fetch({reset: true});
                            // fires ajax request on deleting and...
                            UniCalendar.calendars.fetch();
                            // ... just removes the chosen model from the list, so the whole process of removing looks smooth
                            // TODO add 'return the chosen model back to the collection and the list on ajax request fail'
                            UniCalendar.calendars.remove(thisView.model);
                            thisView.closeForm();
                        }
                    },
                    error: function( model, response, options ) {
                        $container.unblock();
                        console.log( response );
                    }
                }
            );
		},
        remove: function() {
            this.$el.empty().off();
            this.stopListening();
            return this;
        }
	});

	/**
	 *   Single calendar events view
	 */
	UniCalendar.CalendarEventsView = Backbone.View.extend( {
		el: '#js-uni-calendars-container',

        template: _.template( $( '#js-uni-calendar-all-events-tmpl' ).html() ),
		initialize: function() {
		},
		events:
			{
			    "click .js-uni-calendar-confirm-back-btn": "closeForm",
                "click .js-uni-calendar-add-event-btn": "proceedAddForListView"
			},
		render: function() {
			var html = this.template( this.model.toJSON() );
			this.$el.html( html );

            UniCalendar.fullcalendar = $('#js-uni-ec-calendar');

            // render a calendar
            var thisView = this,
                thisCalId = this.model.id,
                calModelSettings = this.model.attributes,
                uniWindowWidth = $(window).width();

            if ( calModelSettings.meta.uni_input_cal_type == 'gcal' ) {

                var calendarObjArgs = {
                    googleCalendarApiKey: calModelSettings.meta.uni_input_gcal_api_key,
                    events: {
                        googleCalendarId: calModelSettings.meta.uni_input_gcal_cal_id
                    },
                    locale: UniCalendar.locale,
                    isRTL: ( UniCalendar.isRTL === 'true' ) ? true : false,
                    height: 'auto',
                    header: {
						left: 'prev',
						center: 'title',
						right: 'next'
					},
                    timeFormat: uniTimeFormat,
                    firstDay: 1,
                    displayEventEnd: true,
					columnFormat: 'dddd',
                    titleFormat: 'MMM DD, YYYY',
                    listDayFormat: 'MMM DD, YYYY',
					allDaySlot: false,
					editable: false,
					eventLimit: true,
                    slotEventOverlap: false,
                    eventRender: function(event, element) {
                        if ( event.allDay ) {
                            element.addClass('uni-ec-all-day-event');
                        }
                    },
                    eventLimit: true
                };

                if ( calModelSettings.meta.uni_input_gcal_view == 'uniCustomView' ) {
                    calendarObjArgs.views = {
                        uniCustomView: {
                            type: calModelSettings.meta.uni_input_gcal_type_view,
                            duration: { days: calModelSettings.meta.uni_input_gcal_duration }
                        }
                    };
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_gcal_view;
                } else {
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_gcal_view;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_gcal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_gcal_column_format;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_slot_label_format !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_slot_label_format !== '' ) {
                    calendarObjArgs.slotLabelFormat = calModelSettings.meta.uni_input_gcal_slot_label_format;
                    calendarObjArgs.timeFormat = calModelSettings.meta.uni_input_gcal_slot_label_format;
                } else {
                    calendarObjArgs.slotLabelFormat = uniTimeFormat;
                    calendarObjArgs.timeFormat = uniTimeFormat;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_first_day !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_first_day !== '' ) {
                    calendarObjArgs.firstDay = calModelSettings.meta.uni_input_gcal_first_day;
                }

            } else if ( calModelSettings.meta.uni_input_cal_type == 'built-in' ) {

                var calendarObjArgs = {
                    locale: UniCalendar.locale,
                    isRTL: ( UniCalendar.isRTL === 'true' ) ? true : false,
                    header: {
						left: 'prev',
						center: 'title',
						right: 'next'
					},
                    height: 'auto',
                    timeFormat: uniTimeFormat,
                    firstDay: 1,
                    displayEventEnd: true,
					columnFormat: 'dddd',
                    titleFormat: 'MMM DD, YYYY',
                    listDayFormat: 'MMM DD, YYYY',
					allDaySlot: false,
					editable: true,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventRender: function(calEvent, element) {
                        //console.log(calEvent);
                        //console.log(element);
                        if ( calEvent.allDay ) {
                            element.addClass('uni-ec-all-day-event');
                        }
                        var $elFcTime = element.find(".fc-time span").css({'color':calEvent.textColor});
                        // adds bg image
                        if ( calEvent.meta.event_bg_image ) {
                            var $elFcBg = element.find(".fc-bg");
                            element.addClass('uni-ec-event-with-bg-image');
                            $elFcBg.css({'background-image':'url('+calEvent.meta.event_bg_image+')'});

                        }
                        // adds content
                        /*if ( calModelSettings.meta.uni_input_cal_content_grid_enable ) {
                            var listContent = '';
                            listContent = '<div class="uni-fc-desc">'+calEvent.meta.event_desc+'</div>';
                            $elFcContent.append(listContent);
                            element.find(".uni-fc-desc").css({'color':calEvent.textColor});
                        }*/
                        // adds users
                        if ( calModelSettings.meta.uni_input_cal_user_grid_enable ) {
                            var $elFcContent = element.find(".fc-content"),
                                arrayUsers = [],
                                listUsers = '';
                            $.each(calEvent.meta.event_user, function (value, key) {
                                $.each(UniCalendar.data.allUsers, function (roleName, roleUsers) {
                                    if ( roleUsers[key] ) {
                                        arrayUsers.push( roleUsers[key] );
                                    }
                                });
                            });
                            if ( arrayUsers.length > 0 ) {
                                listUsers = arrayUsers.join(', ');
                                listUsers = '<div class="uni-fc-users">'+uni_ec_i18n.users_prefix+' '+listUsers+'</div>';
                                $elFcContent.append(listUsers);
                                element.find(".uni-fc-users").css({'color':calEvent.textColor});
                            }
                        }
                    },
                    eventDrop: function(event, delta, revertFunc) {
                        thisView.eventChange( 'drop', event, delta, revertFunc, thisCalId );
                    },
                    eventResize: function(event, delta, revertFunc) {
                        thisView.eventChange( 'resize', event, delta, revertFunc, thisCalId );
                    },
                    dayClick: function(date, jsEvent, view) {
                        thisView.proceedAdd( date, thisCalId, view );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.proceedEdit( calEvent, thisCalId, view );
                    }
                };

                if ( calModelSettings.meta.uni_input_cal_view == 'uniCustomView' ) {
                    calendarObjArgs.views = {
                        uniCustomView: {
                            type: calModelSettings.meta.uni_input_cal_type_view,
                            duration: { days: calModelSettings.meta.uni_input_cal_duration }
                        }
                    };
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cal_view;
                } else {
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cal_view;
                }

                // don't show it as list even if list view is chosen
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_cal_view == 'listDay' ) {
                        calendarObjArgs.defaultView = 'agendaDay';
                    } else if ( calModelSettings.meta.uni_input_cal_view == 'listWeek' ) {
                        calendarObjArgs.defaultView = 'agendaWeek';
                    } else if ( calModelSettings.meta.uni_input_cal_view == 'listMonth' || calModelSettings.meta.uni_input_cal_view == 'listYear' ) {
                        calendarObjArgs.defaultView = 'month';
                    }
                }

                if ( typeof calModelSettings.meta.uni_input_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_cal_column_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_slot_label_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_slot_label_format !== '' ) {
                    calendarObjArgs.slotLabelFormat = calModelSettings.meta.uni_input_cal_slot_label_format;
                    calendarObjArgs.timeFormat = calModelSettings.meta.uni_input_cal_slot_label_format;
                } else {
                    calendarObjArgs.slotLabelFormat = uniTimeFormat;
                    calendarObjArgs.timeFormat = uniTimeFormat;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_first_day !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_first_day !== '' ) {
                    calendarObjArgs.firstDay = calModelSettings.meta.uni_input_cal_first_day;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_slot_duration !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_slot_duration !== '00'
                    && calModelSettings.meta.uni_input_cal_slot_duration !== '0' ) {
                    calendarObjArgs.slotDuration = '00:'+calModelSettings.meta.uni_input_cal_slot_duration+':00';
                } else {
                    calendarObjArgs.slotDuration = '00:60:00';
                }

                if ( typeof calModelSettings.meta.uni_input_cal_start_time !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_start_time !== ''
                    && calModelSettings.meta.uni_input_cal_start_time !== '00:00:00' ) {
                    calendarObjArgs.minTime = moment( calModelSettings.meta.uni_input_cal_start_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_cal_end_time !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_end_time !== ''
                    && calModelSettings.meta.uni_input_cal_end_time !== '00:00:00'  ) {
                    calendarObjArgs.maxTime = moment( calModelSettings.meta.uni_input_cal_end_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_cal_default_date !== 'undefined' && calModelSettings.meta.uni_input_cal_default_date ) {
                    calendarObjArgs.defaultDate = calModelSettings.meta.uni_input_cal_default_date;
                } else {
                    calendarObjArgs.defaultDate = moment().valueOf();
                }
                //console.log(calendarObjArgs);

            } else if ( calModelSettings.meta.uni_input_cal_type == 'mb' ) {

                var calendarObjArgs = {
                    locale: UniCalendar.locale,
                    isRTL: ( UniCalendar.isRTL === 'true' ) ? true : false,
                    header: {
						left: 'prev',
						center: 'title',
						right: 'next'
					},
                    height: 'auto',
                    timeFormat: uniTimeFormat,
                    firstDay: 1,
                    displayEventEnd: true,
					columnFormat: 'dddd',
                    titleFormat: 'MMM DD, YYYY',
                    listDayFormat: 'MMM DD, YYYY',
					allDaySlot: false,
					editable: true,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'mb' );
                    }
                };

                if ( calModelSettings.meta.uni_input_mb_cal_view == 'uniCustomView' ) {
                    calendarObjArgs.views = {
                        uniCustomView: {
                            type: calModelSettings.meta.uni_input_mb_cal_type_view,
                            duration: { days: calModelSettings.meta.uni_input_mb_cal_duration }
                        }
                    };
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_mb_cal_view;
                } else {
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_mb_cal_view;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_mb_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_mb_cal_column_format;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_slot_label_format !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_slot_label_format !== '' ) {
                    calendarObjArgs.slotLabelFormat = calModelSettings.meta.uni_input_mb_cal_slot_label_format;
                    calendarObjArgs.timeFormat = calModelSettings.meta.uni_input_mb_cal_slot_label_format;
                } else {
                    calendarObjArgs.slotLabelFormat = uniTimeFormat;
                    calendarObjArgs.timeFormat = uniTimeFormat;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_first_day !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_first_day !== '' ) {
                    calendarObjArgs.firstDay = calModelSettings.meta.uni_input_mb_cal_first_day;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_slot_duration !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_slot_duration !== '00'
                    && calModelSettings.meta.uni_input_mb_cal_slot_duration !== '0' ) {
                    calendarObjArgs.slotDuration = '00:'+calModelSettings.meta.uni_input_mb_cal_slot_duration+':00';
                } else {
                    calendarObjArgs.slotDuration = '00:60:00';
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_start_time !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_start_time !== ''
                    && calModelSettings.meta.uni_input_mb_cal_start_time !== '00:00:00' ) {
                    calendarObjArgs.minTime = moment( calModelSettings.meta.uni_input_mb_cal_start_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_end_time !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_end_time !== ''
                    && calModelSettings.meta.uni_input_mb_cal_end_time !== '00:00:00'  ) {
                    calendarObjArgs.maxTime = moment( calModelSettings.meta.uni_input_mb_cal_end_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_default_date !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_default_date ) {
                    calendarObjArgs.defaultDate = calModelSettings.meta.uni_input_mb_cal_default_date;
                } else {
                    calendarObjArgs.defaultDate = moment().valueOf();
                }
                //console.log(calendarObjArgs);

            } else if ( calModelSettings.meta.uni_input_cal_type == 'cobot' ) {

                var calendarObjArgs = {
                    locale: UniCalendar.locale,
                    isRTL: ( UniCalendar.isRTL === 'true' ) ? true : false,
                    header: {
						left: 'prev',
						center: 'title',
						right: 'next'
					},
                    height: 'auto',
                    timeFormat: uniTimeFormat,
                    firstDay: 1,
                    displayEventEnd: true,
					columnFormat: 'dddd',
                    titleFormat: 'MMM DD, YYYY',
                    listDayFormat: 'MMM DD, YYYY',
					allDaySlot: false,
					editable: true,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'cobot' );
                    }
                };

                if ( calModelSettings.meta.uni_input_cobot_cal_view == 'uniCustomView' ) {
                    calendarObjArgs.views = {
                        uniCustomView: {
                            type: calModelSettings.meta.uni_input_cobot_cal_type_view,
                            duration: { days: calModelSettings.meta.uni_input_cobot_cal_duration }
                        }
                    };
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cobot_cal_view;
                } else {
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cobot_cal_view;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_cobot_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_cobot_cal_column_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_slot_label_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_slot_label_format !== '' ) {
                    calendarObjArgs.slotLabelFormat = calModelSettings.meta.uni_input_cobot_cal_slot_label_format;
                    calendarObjArgs.timeFormat = calModelSettings.meta.uni_input_cobot_cal_slot_label_format;
                } else {
                    calendarObjArgs.slotLabelFormat = uniTimeFormat;
                    calendarObjArgs.timeFormat = uniTimeFormat;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_first_day !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_first_day !== '' ) {
                    calendarObjArgs.firstDay = calModelSettings.meta.uni_input_cobot_cal_first_day;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_slot_duration !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_slot_duration !== '00'
                    && calModelSettings.meta.uni_input_cobot_cal_slot_duration !== '0' ) {
                    calendarObjArgs.slotDuration = '00:'+calModelSettings.meta.uni_input_cobot_cal_slot_duration+':00';
                } else {
                    calendarObjArgs.slotDuration = '00:60:00';
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_start_time !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_start_time !== ''
                    && calModelSettings.meta.uni_input_cobot_cal_start_time !== '00:00:00' ) {
                    calendarObjArgs.minTime = moment( calModelSettings.meta.uni_input_cobot_cal_start_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_end_time !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_end_time !== ''
                    && calModelSettings.meta.uni_input_cobot_cal_end_time !== '00:00:00'  ) {
                    calendarObjArgs.maxTime = moment( calModelSettings.meta.uni_input_cobot_cal_end_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_default_date !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_default_date ) {
                    calendarObjArgs.defaultDate = calModelSettings.meta.uni_input_cobot_cal_default_date;
                } else {
                    calendarObjArgs.defaultDate = moment().valueOf();
                }
                //console.log(calendarObjArgs);

            } else if ( calModelSettings.meta.uni_input_cal_type == 'tickera' ) {

                var calendarObjArgs = {
                    locale: UniCalendar.locale,
                    isRTL: ( UniCalendar.isRTL === 'true' ) ? true : false,
                    header: {
						left: 'prev',
						center: 'title',
						right: 'next'
					},
                    height: 'auto',
                    timeFormat: uniTimeFormat,
                    firstDay: 1,
                    displayEventEnd: true,
					columnFormat: 'dddd',
                    titleFormat: 'MMM DD, YYYY',
                    listDayFormat: 'MMM DD, YYYY',
					allDaySlot: false,
					editable: true,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'tickera' );
                    },
                    eventRender: function(calEvent, element) {
                        //console.log(calEvent);
                        //console.log(element);
                        var $elFcTime = element.find(".fc-time span").css({'color':calEvent.textColor});
                        // adds address
                        if ( calModelSettings.meta.uni_input_tickera_cal_address_grid_enable ) {
                            var $elFcContent = element.find(".fc-content"),
                                tickeraAddress = '';

                            tickeraAddress = '<div class="uni-fc-address">'+calEvent.meta.event_location+'</div>';
                            $elFcContent.append(listUsers);
                        }
                    }
                };

                if ( calModelSettings.meta.uni_input_tickera_cal_view == 'uniCustomView' ) {
                    calendarObjArgs.views = {
                        uniCustomView: {
                            type: calModelSettings.meta.uni_input_tickera_cal_type_view,
                            duration: { days: calModelSettings.meta.uni_input_tickera_cal_duration }
                        }
                    };
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_tickera_cal_view;
                } else {
                    calendarObjArgs.defaultView = calModelSettings.meta.uni_input_tickera_cal_view;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_tickera_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_tickera_cal_column_format;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_slot_label_format !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_slot_label_format !== '' ) {
                    calendarObjArgs.slotLabelFormat = calModelSettings.meta.uni_input_tickera_cal_slot_label_format;
                    calendarObjArgs.timeFormat = calModelSettings.meta.uni_input_tickera_cal_slot_label_format;
                } else {
                    calendarObjArgs.slotLabelFormat = uniTimeFormat;
                    calendarObjArgs.timeFormat = uniTimeFormat;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_first_day !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_first_day !== '' ) {
                    calendarObjArgs.firstDay = calModelSettings.meta.uni_input_tickera_cal_first_day;
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_slot_duration !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_slot_duration !== '00'
                    && calModelSettings.meta.uni_input_tickera_cal_slot_duration !== '0' ) {
                    calendarObjArgs.slotDuration = '00:'+calModelSettings.meta.uni_input_tickera_cal_slot_duration+':00';
                } else {
                    calendarObjArgs.slotDuration = '00:60:00';
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_start_time !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_start_time !== ''
                    && calModelSettings.meta.uni_input_tickera_cal_start_time !== '00:00:00' ) {
                    calendarObjArgs.minTime = moment( calModelSettings.meta.uni_input_tickera_cal_start_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_end_time !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_end_time !== ''
                    && calModelSettings.meta.uni_input_tickera_cal_end_time !== '00:00:00'  ) {
                    calendarObjArgs.maxTime = moment( calModelSettings.meta.uni_input_tickera_cal_end_time, 'hh:mm a' ).format("HH:mm:ss");
                }

                if ( typeof calModelSettings.meta.uni_input_tickera_cal_default_date !== 'undefined'
                    && calModelSettings.meta.uni_input_tickera_cal_default_date ) {
                    calendarObjArgs.defaultDate = calModelSettings.meta.uni_input_tickera_cal_default_date;
                } else {
                    calendarObjArgs.defaultDate = moment().valueOf();
                }
                //console.log(calendarObjArgs);

            }

            //
            UniCalendar.fullcalendar.fullCalendar( calendarObjArgs );

			return this;
		},
        eventChange : function( type_operation, event, delta, revertFunc, thisCalId ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get( thisCalId );
            var eventModel = calModel.calEvents.get(event.id);

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

            var operationData = {
                type_operation: type_operation,
                millisec: delta._milliseconds,
                days: delta._days,
                months: delta._months
            }

                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('#js-uni-ec-calendar');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( eventModel.attributes.meta, operationData );

                eventModel.save(
                    { uniAction: 'uni_ec_change_calendar_event', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                                revertFunc();
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                                revertFunc();
                            }

                            if ( response.status === 'success' ) {
                                //console.log(event);
                                /*console.log(moment(response.start).utc());
                                console.log(moment(response.end).utc());
                                event.start = moment(response.start).utc();
                                event.end   = moment(response.end).utc();
                                console.log(event);
                                UniCalendar.fullcalendar.fullCalendar('updateEvent', event);
                                /*console.log(UniCalendar.calendars.get( thisCalId ).calEvents.get(response.id));*/
                                /*UniCalendar.calendars.get( thisCalId ).calEvents.get(response.id).set({start:response.start, end:moment(response.end, [	"YYYY-MM-DDTHH:mm:ssZ"])});*/
                                UniCalendar.fullcalendar.fullCalendar('refetchEvents');
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                            revertFunc();
                        }
                    }
                );
        },
        proceedAdd: function( date, thisCalId, view ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisCalId);
            var allowedUsers = [];

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

            if( calModel.attributes.meta.uni_input_cal_roles ) {
                $.each(calModel.attributes.meta.uni_input_cal_roles, function(i, val){
                    allowedUsers = uni_merge_options( allowedUsers, UniCalendar.data.allUsers[val] );
                });
            }

            UniCalendar.eventAddEditFormView = new UniCalendar.EventAddFormView( { cal_id: calModel.id, event_date: date, calView: view, calCats: UniCalendar.data.calCats, allowedUsers: allowedUsers } );
            UniCalendar.eventAddEditFormView.render();
		},
        proceedAddForListView: function() {
            var thisView = this;
            var thisCalId = thisView.model.id;
            var calModel = UniCalendar.calendars.get(thisCalId);
            var allowedUsers = [];
            var view = {}
            view.type = $('.js-uni-calendar-add-event-btn').attr('data-view');

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

            if( calModel.attributes.meta.uni_input_cal_roles ) {
                $.each(calModel.attributes.meta.uni_input_cal_roles, function(i, val){
                    allowedUsers = uni_merge_options( allowedUsers, UniCalendar.data.allUsers[val] );
                });
            }

            UniCalendar.eventAddEditFormView = new UniCalendar.EventAddFormView( { cal_id: calModel.id, event_date: '', calView: view, calCats: UniCalendar.data.calCats, allowedUsers: allowedUsers } );
            UniCalendar.eventAddEditFormView.render();
		},
        proceedEdit: function( calEvent, thisCalId, view ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisCalId);
            var allowedUsers = [];

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

            if( calModel.attributes.meta.uni_input_cal_roles ) {
                $.each(calModel.attributes.meta.uni_input_cal_roles, function(i, val){
                    allowedUsers = uni_merge_options( allowedUsers, UniCalendar.data.allUsers[val] );
                });
            }

            UniCalendar.eventAddEditFormView = new UniCalendar.EventEditFormView( { cal_id: calModel.id, calEvent: calEvent, calView: view, calCats: UniCalendar.data.calCats, allowedUsers: allowedUsers } );
            UniCalendar.eventAddEditFormView.render();
		},
        viewThirdPartyEvents: function( calEvent, thisCalId, sThirdPartyServiceName ) {
            var thisView = this;

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

            UniCalendar.eventAddEditFormView = new UniCalendar.EventInfoFormViewThirdParty( { calEvent: calEvent, service: sThirdPartyServiceName } );
            UniCalendar.eventAddEditFormView.render();

        },
        getEvents: function( thisCalId, start, end, timezone, callback ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get( thisCalId );

            //
            if ( typeof UniCalendar.eventAddEditFormView !== 'undefined' ) {
                UniCalendar.eventAddEditFormView.remove();
            }

                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = $('#js-uni-ec-calendar');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });

                calModel.save(
                    { uniAction: 'uni_ec_get_events_admin', postData: {start: start.unix(), end : end.unix()} },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            //if ( response.status === 'success' ) {
                                // adds array of events to the calendar
                                callback( calModel.calEvents.toJSON() );
                                //console.log(calModel.calEvents.toJSON());
                            //}
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-calendar-confirm-back-btn');
            this.remove();
            $('#uni-ec-admin-notice').remove();
            build_all_calendars_table();
		},
        remove: function() {
            this.$el.empty().off();
            this.stopListening();
            return this;
        }
	} );

	/**
	 * Single event add form
	 */
    UniCalendar.EventAddFormView = Backbone.View.extend( {
		el: '#js-uni-ec-calendar-modal-form',

        template: _.template( $( '#js-uni-calendar-add-event-tmpl' ).html() ),
		initialize: function(attrs) {
		    this.options = attrs;
		},
		events:
			{
			    "click .js-uni-event-confirm-save-btn": "saveSettings",
				"click .js-uni-event-confirm-cancel-btn": "closeForm"
			},
		render: function() {
			var html = this.template( this.options );
			this.$el.html( html );

            // timepicker time init
            $(".js-uni-ec-datepicker-time").timepickeralone({
                lang: UniCalendar.locale,
                withoutBottomPanel: true,
                inline: false,
                inputFormat: uniTimeFormat,
                hours: true,
                minutes: true,
                seconds: false,
                twelveHoursFormat: uniAmPm,
                ampm: uniAmPm,
                steps:[1,5,1,1],
                defaultTime: ''
            });

            //
            $(".js-uni-ec-datepicker-date").periodpicker({
                lang: UniCalendar.locale,
                inline: false,
                draggable: false,
                clearButtonInButton: true,
                withoutBottomPanel: true,
                yearsLine: false,
                norange: true,
                cells: [1, 1],
                resizeButton: false,
                fullsizeButton: false,
                fullsizeOnDblClick: false,
                timepicker: false,
                formatDate: 'YYYY-MM-DD',
                formatDecoreDateWithYear: 'YYYY-MM-DD'
            });

            // timepicker date init
            $(".js-uni-ec-datepicker-date-recurring").periodpicker({
                lang: UniCalendar.locale,
                inline: false,
                draggable: false,
                clearButtonInButton: true,
                withoutBottomPanel: true,
                yearsLine: false,
                norange: true,
                cells: [1, 1],
                resizeButton: false,
                fullsizeButton: false,
                fullsizeOnDblClick: false,
                timepicker: false,
                formatDate: 'YYYY-MM-DD',
                formatDecoreDateWithYear: 'YYYY-MM-DD',
                minDate: moment().add(1, "d").format('YYYY-MM-DD'),
                maxDate: moment().add(90, "d").format('YYYY-MM-DD')
            });

            // colour picker init
            $('.js-uni-calendar-colour-field').wpColorPicker();

            // add/upload image
		    var file_frame;
		    $(document).on( 'click', '.js-uni-ec-image-upload', function( e ){

			    e.preventDefault();
                var $link = $(e.target);

                if ( typeof file_frame !== 'undefined' ) {
                    file_frame.close();
                }

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
				    title: uni_ec_i18n.uploader_title,
				    multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
				    var attachment = file_frame.state().get('selection').first().toJSON();
                    //console.log(attachment);
					$link.nextAll("input.js-uni-ec-image-upload-field").val( attachment.id );
				});

                // Finally, open the modal on click
                file_frame.open();

		    });

            if ( typeof this.$el.draggable( "instance" ) === 'undefined' ) { // check whether it hasn't been initiated already
                this.$el.draggable().show();
            }

            //
            var arrConstrainedEls = this.$el.find('[data-constrained]');
            this.$el.find('[data-constrainer]').on("change", function(e){
                uniEcConstrainedFields( arrConstrainedEls );
            });
            uniEcConstrainedFields( arrConstrainedEls );

            this.$el.scrollintoview();

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-event-confirm-save-btn');
            this.$el.off('click', '.js-uni-event-confirm-cancel-btn');
            $(document).off('click', '.js-uni-ec-image-upload');
            this.remove();
            $('#uni-ec-admin-notice').remove();
		},
        saveSettings: function() {
            var thisView = this;
            var newEvent = new UniCalendar.EventModel();
            var calModel = UniCalendar.calendars.get(thisView.options.cal_id);

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = thisView.$el.find('.uni-ec-form-content');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( newEvent.meta, validationResults.uniInputsData );
                //console.log(newEvent);
                newEvent.save(
                    { uniAction: 'uni_ec_add_calendar_event', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' ) {
                                UniCalendar.fullcalendar.fullCalendar( 'refetchEvents' );
                                thisView.closeForm();
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        remove: function() {
            if ( typeof this.$el.draggable( "instance" ) !== 'undefined' ) {
                this.$el.draggable( "destroy" );
            }
            this.$el.empty().off();
            // we don't remove the element but just empty it, so we have to additionaly hide it
            this.$el.hide();
            this.stopListening();
            return this;
        }
	});

	/**
	 * Single event edit form
	 */
    UniCalendar.EventEditFormView = Backbone.View.extend( {
		el: '#js-uni-ec-calendar-modal-form',

        template: _.template( $( '#js-uni-calendar-edit-event-tmpl' ).html() ),
		initialize: function(attrs) {
		    this.options = attrs;
		},
		events:
			{
			    "click .js-uni-event-confirm-save-btn": "saveSettings",
				"click .js-uni-event-confirm-cancel-btn": "closeForm",
                "click .js-uni-event-confirm-delete-btn": "deleteEvent"
			},
		render: function() {
			var html = this.template( this.options );
			this.$el.html( html );

            if ( typeof this.$el.draggable( "instance" ) === 'undefined' ) { // check whether it hasn't been initiated already
                this.$el.draggable().show();
            }

            //
            var arrConstrainedEls = this.$el.find('[data-constrained]');
            this.$el.find('[data-constrainer]').on("change", function(e){
                uniEcConstrainedFields( arrConstrainedEls );
            });
            uniEcConstrainedFields( arrConstrainedEls );

            // timepicker datetime init
            $(".js-uni-ec-datepicker-datetime").periodpicker({
                lang: UniCalendar.locale,
                inline: false,
                draggable: false,
                clearButtonInButton: true,
                withoutBottomPanel: true,
                yearsLine: false,
                norange: true,
                cells: [1, 1],
                resizeButton: false,
                fullsizeButton: false,
                fullsizeOnDblClick: false,
                timepicker: true,
                timepickerOptions: {
                    twelveHoursFormat: uniAmPm,
    		        hours: true,
    		        minutes: true,
    		        seconds: false,
    		        ampm: uniAmPm,
                    steps:[1,1,1,1]
    	        },
                formatDateTime: uniDateTimeFormat,
                formatDecoreDateTimeWithYear: uniDateTimeFormat
            });
                
            // timepicker time init
            $(".js-uni-ec-datepicker-time").timepickeralone({
                lang: UniCalendar.locale,
                withoutBottomPanel: true,
                inline: false,
                inputFormat: uniTimeFormat,
                hours: true,
                minutes: true,
                seconds: false,
                twelveHoursFormat: uniAmPm,
                ampm: uniAmPm,
                steps:[1,5,1,1],
                defaultTime: '',
            });

            // colour picker init
            $('.js-uni-calendar-colour-field').wpColorPicker();

            // add/upload image
		    var file_frame;
		    $(document).on( 'click', '.js-uni-ec-image-upload', function( e ){

			    e.preventDefault();
                var $link = $(e.target);

                if ( typeof file_frame !== 'undefined' ) {
                    file_frame.close();
                }

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: uni_ec_i18n.uploader_title,
				    multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
				    var attachment = file_frame.state().get('selection').first().toJSON();
                    //console.log(attachment);
					$link.nextAll("input.js-uni-ec-image-upload-field").val( attachment.id );
				});

                // Finally, open the modal on click
                file_frame.open();

		    });

            this.$el.scrollintoview();

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-event-confirm-save-btn');
            this.$el.off('click', '.js-uni-event-confirm-cancel-btn');
            $(document).off('click', '.js-uni-ec-image-upload');
            this.remove();
            $('#uni-ec-admin-notice').remove();
            $('#js-uni-calendars-container').scrollintoview();
		},
        saveSettings: function() {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisView.options.cal_id);
            var eventModel = calModel.calEvents.get(thisView.options.calEvent.id);
            //console.log(eventModel.toJSON());

            // validates inputs
            var validationResults = uniEcValidateFields( thisView.$el, '.uni-ec-validation-activated' );

            if ( validationResults.formValid ) {
                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = thisView.$el.find('.uni-ec-form-content');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });
                // modifies .meta property object of the model
                var updatedMeta = uni_merge_options( eventModel.meta, validationResults.uniInputsData );

                eventModel.save(
                    { uniAction: 'uni_ec_edit_calendar_event', meta: updatedMeta },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' ) {
                                //console.log(UniCalendar.calendars.get(thisView.options.cal_id).calEvents.get(thisView.options.calEvent.id).toJSON());
                                UniCalendar.fullcalendar.fullCalendar( 'refetchEvents' );
                                thisView.closeForm();
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
            }
		},
        deleteEvent: function() {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisView.options.cal_id);
            var eventModel = calModel.calEvents.get(thisView.options.calEvent.id);

                // removes admin notice
                $('#uni-ec-admin-notice').remove();
                // block
                var $container = thisView.$el.find('.uni-ec-form-content');
                $container.block({
    	        	            message: null,
                                overlayCSS: { background: '#fff', opacity: 0.6 }
                            });

                //console.log(newEvent);
                eventModel.save(
                    { uniAction: 'uni_ec_delete_calendar_event' },
                    {
                        success: function( model, response, options ) {
                            $container.unblock();
                            if ( response.status === 'error' && $('#uni-ec-admin-notice').length > 0 ) {
                                $('#uni-ec-admin-notice p').html(response.message);
                            } else if ( response.status === 'error' ) {
                                $('#uni-calendar-wrapper').prepend('<div id="uni-ec-admin-notice" class="notice notice-error"><p>'+response.message+'</p></div>');
                            }

                            if ( response.status === 'success' ) {
                                UniCalendar.fullcalendar.fullCalendar( 'refetchEvents' );
                                thisView.closeForm();
                            }
                        },
                        error: function( model, response, options ) {
                            $container.unblock();
                            console.log( response );
                        }
                    }
                );
		},
        remove: function() {
            if ( typeof this.$el.draggable( "instance" ) !== 'undefined' ) {
                this.$el.draggable( "destroy" );
            }
            this.$el.empty().off();
            // we don't remove the element but just empty it, so we have to additionaly hide it
            this.$el.hide();
            this.stopListening();
            return this;
        }
	});

    /**
	 * Single event info view for third-party services
	 */
    UniCalendar.EventInfoFormViewThirdParty = Backbone.View.extend( {
		el: '#js-uni-ec-calendar-modal-form',

		initialize: function( attrs ) {
		    this.options = attrs;
		},
		events:
			{
				"click .js-uni-event-modal-close-btn": "closeForm",
			},
		render: function() {
            var tpl = _.template( $( '#js-uni-calendar-info-event-'+this.options.service+'-tmpl' ).html() );
            var html = tpl( this.options );
			this.$el.html( html );

            if ( typeof this.$el.draggable( "instance" ) === 'undefined' ) { // check whether it hasn't been initiated already
                this.$el.draggable().show();
            }

            $(".uni-ec-form-section-nice-scroll").niceScroll({autohidemode:'cursor'});
            this.$el.scrollintoview();

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-event-modal-close-btn');
            this.remove();
		},
        remove: function() {
            if ( typeof this.$el.draggable( "instance" ) !== 'undefined' ) {
                this.$el.draggable( "destroy" );
            }
            this.$el.empty().off();
            // we don't remove the element but just empty it, so we have to additionaly hide it
            this.$el.hide();
            this.stopListening();
            return this;
        }
	});

	/**
	 * Set initial data into view and start recurring display updates.
	 */
	UniCalendar.init = function() {
		// Instantiate the base data and view.
		UniCalendar.calendars = new UniCalendar.CalendarsCollection();
		UniCalendar.calendars.reset( UniCalendar.data.calendars );
        build_all_calendars_table();
	};

	/**
	 * build_all_calendars_table
	 */
    function build_all_calendars_table() {

        if ( !$('#js-uni-calendars-container') ) {
            $('#js-uni-calendar-main-content').prepend('<div id="js-uni-calendars-container"></div>');
        }
      
        // Set up a grid
        UniCalendarsGrid = new Backgrid.Grid({
            className: "pure-table pure-table-striped uni-ec-calendars-table",
            emptyText: "No Calanders",
            columns: [{
                name: "id",
                label: "ID",
                editable: false,
                cell: Backgrid.IntegerCell.extend({
                    orderSeparator: ''
                })
            },{
                name: "title",
                label: "Title",
                editable: false,
                cell: "string"
            },{
                name: "shortcode",
                label: "Shortcode",
                editable: false,
                sortable: false,
                cell: "string"
            },{
                name: "type_name",
                label: "Type",
                editable: false,
                cell: "string"
            },{
                name: "auto_status",
                label: "Autotransfer",
                editable: false,
                cell: "string"
            },{
                name: "actions",
                label: "Actions",
                editable: false,
                sortable: false,
                cell : Backgrid.StringCell.extend({
                    template: _.template($('#js-calendar-table-item-actions-cell-tmpl').html()),
                    events: {
    			        'click .js-calendar-item-edit-action': 'calendarEdit',
                        'click .js-calendar-item-edit-events-action': 'calendarEditEvents',
                        'click .js-calendar-item-delete-action': 'calendarDelete'
    		        },
                    render: function () {
                        this.$el.html(this.template(this.model.attributes));
                        return this;
                    },
                    calendarEdit: function (e) {
                        e.preventDefault();
                        new UniCalendar.CalendarEditFormView( { collection: this, model: this.model } ).render();
                    },
                    calendarEditEvents: function (e) {
                        e.preventDefault();
                        new UniCalendar.CalendarEventsView( { collection: this, model: this.model } ).render();
                    },
                    calendarDelete: function (e) {
                        e.preventDefault();
                        new UniCalendar.CalendarDeletePromtView( { collection: this, model: this.model } ).render();
                    }
                })
            }],
            collection: UniCalendar.calendars
        });

        // Render the grid
        var $UniCalendarsContainer = $("#js-uni-calendars-container");
        $UniCalendarsContainer.prepend('<button id="js-calendar-item-add-action" type="button" class="btn btn-success">Add New Calendar</button>');
        UniCalendar.calendarAddForm = new UniCalendar.CalendarAddFormView();
        $(document).on("click", "#js-calendar-item-add-action", function(e){
                UniCalendar.calendarAddForm.render();
        });
        $UniCalendarsContainer.append(UniCalendarsGrid.render().el);
    }


	$( document ).ready( function() {

		UniCalendar.init();

	} );

    // conditionally display/hide sets of options
    var uniEcConstrainedFields = function( arrElements ){

        arrElements.each(function(i, el){
            var $el = $(el),
                elData = $el.data();

            if ( typeof elData.constrained !== 'undefined' ) {
                var $constrainer = $('#'+elData.constrained);

                if ( $constrainer[0].type === 'checkbox' ) {
                    var constrainerVal = ( $constrainer.is(':checked') ) ? $constrainer.val() : '';
                } else {
                    var constrainerVal = $constrainer.val();
                }

                var arrFormElsToBeValidated = $el.find('input, select, textarea');
                if ( typeof elData.constvalue !== 'undefined' && constrainerVal === elData.constvalue ) {
                    $el.show();
                    arrFormElsToBeValidated.each(function(i, el){
                        $(el).addClass('uni-ec-validation-activated');
                    });
                } else if ( typeof elData.constvalue !== 'undefined' && constrainerVal !== elData.constvalue ) {
                    $el.hide();
                    arrFormElsToBeValidated.each(function(i, el){
                        $(el).removeClass('uni-ec-validation-activated');
                    });
                }
            }

        });
    };

    // validation
    var uniEcValidateFields = function( containerEl, fieldsSelector ){

        var validationResults = {uniInputsData: {}, formValid:true};

            var arrFormElsVisible = containerEl.find( fieldsSelector );
            arrFormElsVisible.each(function(i, el){
                var elType = el.type || el.tagName.toLowerCase(),
                    thisFieldInstance = $(el).parsley();
                thisFieldInstance.validate();
                if ( thisFieldInstance.isValid() ) {
                    // also build an object with these data
                    if ( elType == 'checkbox' ) {
                        var checkboxes = [],
                            name_of_checkboxes = el.name.slice(0,-2),
                            $thisEl = $(el);
                        if ( $thisEl.hasClass('uni-ec-single-checkbox') && $thisEl.attr("checked") ) { // exception
                            validationResults.uniInputsData[el.name] = el.value;
                        } else {
                            $('input[name="'+ el.name +'"]:checked').each(function() {
                                checkboxes.push( $(this).val() );
                            });
                            validationResults.uniInputsData[name_of_checkboxes] = checkboxes;
                        }
                    } else {
                            validationResults.uniInputsData[el.name] = el.value;
                    }
                } else {
                    validationResults.formValid = false;
                }
            });

        return validationResults;
    };

    function uni_merge_options( obj1, obj2 ){
        var obj3 = {};
        for (var attrname in obj1) { obj3[attrname] = obj1[attrname]; }
        for (var attrname in obj2) { obj3[attrname] = obj2[attrname]; }
        return obj3;
    }

    // Add color picker
    $('.uni-calendar-colour-field').wpColorPicker();

})( jQuery, UniCalendar );