var UniCalendar = UniCalendar || {};
UniCalendar.fullcalendar = {};

if ( typeof UniCalendar.timeFormat !== 'undefined' && UniCalendar.timeFormat === '24' ) {
    var uniTimeFormat = 'HH:mm',
        uniDateTimeFormat = 'HH:mm YYYY-MM-DD';
} else {
    var uniTimeFormat = 'h:mm a',
        uniDateTimeFormat = 'h:mm a YYYY-MM-DD';
}

(function( $, UniCalendar ) {
	'use strict';

	/**
	 * A mixin for collections/models.
	 * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
	 */
	var AdminAjaxSyncableMixin = {
		url: UniCalendar.ajax_url,
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
            meta: {},
            eventsCats: {}
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
	 *   Single calendar events view
	 */
	UniCalendar.CalendarFrontEndView = Backbone.View.extend( {
        template: _.template( $( '#js-uni-calendar-all-events-tmpl' ).html() ),
		initialize: function( attrs ) {
		    this.options = attrs;
            this.$el = this.options.cal_container;

            $(window).on("resize", this.adaptiveView.bind(this));
		},
		events:{},
		render: function() {
			var html = this.template( this.model.toJSON() ),
                viewOptions = this.options;
			this.$el.html( html );

            // render a calendar
            var thisView = this,
                thisCalId = this.model.id,
                calModelSettings = this.model.attributes,
                $calEl = $('[data-ec_cal_id='+thisCalId+']'),
                uniWindowWidth = $(window).width();

            $calEl.data('cal-width', uniWindowWidth);

            UniCalendar.fullcalendar[thisCalId] = $calEl;

            //console.log(calModelSettings.meta.uni_input_cal_type);
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
                    }
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
                    if ( viewOptions.chosenView ) {
                        calendarObjArgs.defaultView = viewOptions.chosenView;
                    } else {
                        calendarObjArgs.defaultView = calModelSettings.meta.uni_input_gcal_view;
                    }
                }

                // make it responsive
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_gcal_view == 'basicWeek' || calModelSettings.meta.uni_input_gcal_view == 'agendaWeek'
                        || calModelSettings.meta.uni_input_gcal_view == 'uniCustomView' ) {
                        calendarObjArgs.defaultView = 'listWeek';
                    } else if ( calModelSettings.meta.uni_input_gcal_view == 'month' ) {
                        calendarObjArgs.defaultView = 'listMonth';
                    }
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_gcal_title_format;
                    calendarObjArgs.listDayAltFormat = calModelSettings.meta.uni_input_gcal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_gcal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_gcal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_gcal_column_format;
                    calendarObjArgs.listDayFormat = calModelSettings.meta.uni_input_gcal_column_format;
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
					editable: false,
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
                        var $elFcTime = element.find(".fc-time span").css({'color':calEvent.textColor}),
                            $elFcContent = element.find(".fc-content");
                        if ( calEvent.meta.event_bg_image ) {
                            var $elFcBg = element.find(".fc-bg");
                            element.addClass('uni-ec-event-with-bg-image');
                            $elFcBg.css({'background-image':'url('+calEvent.meta.event_bg_image+')'});
                        }
                        // adds users
                        if ( calModelSettings.meta.uni_input_cal_user_grid_enable ) {
                            var arrayUsers = [],
                                listUsers = '';
                            $.each(calEvent.meta.event_user, function (value, key) {
                                if ( UniCalendar.data.allUsers[key] ) {
                                    arrayUsers.push( UniCalendar.data.allUsers[key].name );
                                }
                            });
                            if ( arrayUsers.length > 0 ) {
                                listUsers = arrayUsers.join(', ');
                                listUsers = '<div class="uni-fc-users">'+uni_ec_i18n.users_prefix+' '+listUsers+'</div>';
                                $elFcContent.append(listUsers);
                                element.find(".uni-fc-users").css({'color':calEvent.textColor});
                            }
                        }
                    },
                    eventAfterRender: function(event, element, view) {
                        if ( view.name == 'agendaWeek' ) {
                            element.css({height: element.outerHeight(), width: element.outerWidth()});
                        }
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        if (calEvent.meta.event_click_behavior && calEvent.meta.event_click_behavior == 'uri' && calEvent.url) {
                            window.open(calEvent.url);
                            return false;
                        } else {
                            thisView.viewBuiltInEvents( calEvent, thisCalId, viewOptions.chosenTheme );
                        }
                    },
                    eventMouseover: function(calEvent, jsEvent, view) {
                        thisView.mouseoverEvent( calEvent, jsEvent, view );
                    },
                    eventMouseout: function(calEvent, jsEvent, view) {
                        thisView.mouseoutEvent( calEvent, jsEvent, view );
                    },
                    windowResize: function(view) {
                        if ( view.name == 'agendaWeek' ) {
                            $(this).fullCalendar( 'rerenderEvents' );
                        }
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
                    if ( viewOptions.chosenView ) {
                        calendarObjArgs.defaultView = viewOptions.chosenView;
                    } else {
                        calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cal_view;
                    }
                }

                // make it responsive
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_cal_view == 'basicWeek' || calModelSettings.meta.uni_input_cal_view == 'agendaWeek'
                        || calModelSettings.meta.uni_input_cal_view == 'uniCustomView' ) {
                        calendarObjArgs.defaultView = 'listWeek';
                    } else if ( calModelSettings.meta.uni_input_cal_view == 'month' ) {
                        calendarObjArgs.defaultView = 'listMonth';
                    }
                }

                if ( typeof calModelSettings.meta.uni_input_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_cal_title_format;
                    calendarObjArgs.listDayAltFormat = calModelSettings.meta.uni_input_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_cal_column_format;
                    calendarObjArgs.listDayFormat = calModelSettings.meta.uni_input_cal_column_format;
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
					editable: false,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'mb' );
                    },
                    eventAfterRender: function(event, element, view) {
                        if ( view.name == 'agendaWeek' ) {
                            element.css({height: element.outerHeight(), width: element.outerWidth()});
                        }
                    },
                    eventMouseover: function(calEvent, jsEvent, view) {
                        thisView.mouseoverEvent( calEvent, jsEvent, view );
                    },
                    eventMouseout: function(calEvent, jsEvent, view) {
                        thisView.mouseoutEvent( calEvent, jsEvent, view );
                    },
                    windowResize: function(view) {
                        if ( view.name == 'agendaWeek' ) {
                            $(this).fullCalendar( 'rerenderEvents' );
                        }
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
                    if ( viewOptions.chosenView ) {
                        calendarObjArgs.defaultView = viewOptions.chosenView;
                    } else {
                        calendarObjArgs.defaultView = calModelSettings.meta.uni_input_mb_cal_view;
                    }
                }

                // make it responsive
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_mb_cal_view == 'basicWeek' || calModelSettings.meta.uni_input_mb_cal_view == 'agendaWeek'
                        || calModelSettings.meta.uni_input_mb_cal_view == 'uniCustomView' ) {
                        calendarObjArgs.defaultView = 'listWeek';
                    } else if ( calModelSettings.meta.uni_input_mb_cal_view == 'month' ) {
                        calendarObjArgs.defaultView = 'listMonth';
                    }
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_mb_cal_title_format;
                    calendarObjArgs.listDayAltFormat = calModelSettings.meta.uni_input_mb_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_mb_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_mb_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_mb_cal_column_format;
                    calendarObjArgs.listDayFormat = calModelSettings.meta.uni_input_mb_cal_column_format;
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
					editable: false,
					eventLimit: true,
                    slotEventOverlap: false,
                    events: function(start, end, timezone, callback) {
                        thisView.getEvents( thisCalId, start, end, timezone, callback );
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'cobot' );
                    },
                    eventAfterRender: function(event, element, view) {
                        if ( view.name == 'agendaWeek' ) {
                            element.css({height: element.outerHeight(), width: element.outerWidth()});
                        }
                    },
                    eventMouseover: function(calEvent, jsEvent, view) {
                        thisView.mouseoverEvent( calEvent, jsEvent, view );
                    },
                    eventMouseout: function(calEvent, jsEvent, view) {
                        thisView.mouseoutEvent( calEvent, jsEvent, view );
                    },
                    windowResize: function(view) {
                        if ( view.name == 'agendaWeek' ) {
                            $(this).fullCalendar( 'rerenderEvents' );
                        }
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
                    if ( viewOptions.chosenView ) {
                        calendarObjArgs.defaultView = viewOptions.chosenView;
                    } else {
                        calendarObjArgs.defaultView = calModelSettings.meta.uni_input_cobot_cal_view;
                    }
                }

                // make it responsive
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_cobot_cal_view == 'basicWeek' || calModelSettings.meta.uni_input_cobot_cal_view == 'agendaWeek'
                        || calModelSettings.meta.uni_input_cobot_cal_view == 'uniCustomView' ) {
                        calendarObjArgs.defaultView = 'listWeek';
                    } else if ( calModelSettings.meta.uni_input_cobot_cal_view == 'month' ) {
                        calendarObjArgs.defaultView = 'listMonth';
                    }
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_header !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_header === 'yes' ) {
                    calendarObjArgs.header = false;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_title_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_title_format !== '' ) {
                    calendarObjArgs.titleFormat = calModelSettings.meta.uni_input_cobot_cal_title_format;
                    calendarObjArgs.listDayAltFormat = calModelSettings.meta.uni_input_cobot_cal_title_format;
                }

                if ( typeof calModelSettings.meta.uni_input_cobot_cal_column_format !== 'undefined'
                    && calModelSettings.meta.uni_input_cobot_cal_column_format !== '' ) {
                    calendarObjArgs.columnFormat = calModelSettings.meta.uni_input_cobot_cal_column_format;
                    calendarObjArgs.listDayFormat = calModelSettings.meta.uni_input_cobot_cal_column_format;
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
                    eventRender: function(calEvent, element) {
                        //console.log(calEvent);
                        //console.log(element);
                        var $elFcTime = element.find(".fc-time span").css({'color':calEvent.textColor});
                        // adds address
                        if ( calModelSettings.meta.uni_input_tickera_cal_address_grid_enable ) {
                            var $elFcContent = element.find(".fc-content"),
                                tickeraAddress = '';

                            tickeraAddress = '<div class="uni-fc-address">'+calEvent.meta.event_location+'</div>';
                            $elFcContent.append(tickeraAddress);
                        }
                    },
                    eventAfterRender: function(event, element, view) {
                        if ( view.name == 'agendaWeek' ) {
                            element.css({height: element.outerHeight(), width: element.outerWidth()});
                        }
                    },
                    eventClick: function(calEvent, jsEvent, view) {
                        thisView.viewThirdPartyEvents( calEvent, thisCalId, 'tickera' );
                    },
                    eventMouseover: function(calEvent, jsEvent, view) {
                        thisView.mouseoverEvent( calEvent, jsEvent, view );
                    },
                    eventMouseout: function(calEvent, jsEvent, view) {
                        thisView.mouseoutEvent( calEvent, jsEvent, view );
                    },
                    windowResize: function(view) {
                        if ( view.name == 'agendaWeek' ) {
                            $(this).fullCalendar( 'rerenderEvents' );
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
                    if ( viewOptions.chosenView ) {
                        calendarObjArgs.defaultView = viewOptions.chosenView;
                    } else {
                        calendarObjArgs.defaultView = calModelSettings.meta.uni_input_tickera_cal_view;
                    }
                }

                // make it responsive
                if ( uniWindowWidth <= 680 ) {
                    if ( calModelSettings.meta.uni_input_tickera_cal_view == 'basicWeek' || calModelSettings.meta.uni_input_tickera_cal_view == 'agendaWeek'
                        || calModelSettings.meta.uni_input_tickera_cal_view == 'uniCustomView' ) {
                        calendarObjArgs.defaultView = 'listWeek';
                    } else if ( calModelSettings.meta.uni_input_tickera_cal_view == 'month' ) {
                        calendarObjArgs.defaultView = 'listMonth';
                    }
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

            // inits fullcalendar
            UniCalendar.fullcalendar[thisCalId].fullCalendar( calendarObjArgs );

            // builds and displays filter if it is enabled
            if (
                (
                    calModelSettings.meta.uni_input_cal_type == 'built-in'
                    && typeof calModelSettings.meta.uni_input_cal_filter !== 'undefined' && calModelSettings.meta.uni_input_cal_filter === 'yes'
                    && typeof calModelSettings.meta.uni_input_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'mb'
                    && typeof calModelSettings.meta.uni_input_mb_cal_filter !== 'undefined' && calModelSettings.meta.uni_input_mb_cal_filter === 'yes'
                    && typeof calModelSettings.meta.uni_input_mb_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_mb_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'cobot'
                    && typeof calModelSettings.meta.uni_input_cobot_cal_filter !== 'undefined' && calModelSettings.meta.uni_input_cobot_cal_filter === 'yes'
                    && typeof calModelSettings.meta.uni_input_cobot_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_cobot_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'tickera'
                    && typeof calModelSettings.meta.uni_input_tickera_cal_filter !== 'undefined' && calModelSettings.meta.uni_input_tickera_cal_filter === 'yes'
                    && typeof calModelSettings.meta.uni_input_tickera_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_tickera_cal_cats.length > 0
                )
                && $('#js-uni-calendar-filter-categories-'+calModelSettings.id).length === 0 ) {

                    if ( calModelSettings.meta.uni_input_cal_type == 'built-in' ) {
                        var arrayFilterCats = calModelSettings.meta.uni_input_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'mb' ) {
                        var arrayFilterCats = calModelSettings.meta.uni_input_mb_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'cobot' ) {
                        var arrayFilterCats = calModelSettings.meta.uni_input_cobot_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'tickera' ) {
                        var arrayFilterCats = calModelSettings.meta.uni_input_tickera_cal_cats;
                    }

                    thisView.$el.prepend('<div id="js-uni-calendar-filter-categories-'+calModelSettings.id+'" class="uni-ec-calendar-filter-container uni-clear"></div>');
                    var $elFilter = thisView.$el.find('#js-uni-calendar-filter-categories-'+calModelSettings.id),
                        listItems;

                    $elFilter.html('<ul class="uni-ec-filter-cats-list uni-clear"></ul>');
                    var $elList = $elFilter.find('ul');
                    $elList.data('id', calModelSettings.id);
                    listItems = '<li class="uni-ec-filter-cat-li all"><a href="#" data-slug="-1" class="uni-ec-filter-cat-link uni-ec-active">'+uni_ec_i18n.filter_all+'</a></li>';

                    $.each(arrayFilterCats, function(i, el){
                        if ( UniCalendar.data.calCats[el] ) {
                            listItems += '<li class="uni-ec-filter-cat-li '+UniCalendar.data.calCats[el].slug+'"><a href="#" data-slug="'+UniCalendar.data.calCats[el].slug+'" class="uni-ec-filter-cat-link">'+UniCalendar.data.calCats[el].title+'</a></li>';
                        }
                    });
                    $elList.html(listItems);

                    $(document).on('click', ".uni-ec-filter-cat-link", function(e){
                        thisView.filterCatItems(e);
                    });

            }
            //console.log(calModelSettings.meta);
            // builds and displays legend if it is enabled
            if (
                (
                    calModelSettings.meta.uni_input_cal_type == 'built-in'
                    && typeof calModelSettings.meta.uni_input_cal_legend_enable !== 'undefined' && calModelSettings.meta.uni_input_cal_legend_enable === 'yes'
                    && typeof calModelSettings.meta.uni_input_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'mb'
                    && typeof calModelSettings.meta.uni_input_mb_cal_legend_enable !== 'undefined' && calModelSettings.meta.uni_input_mb_cal_legend_enable === 'yes'
                    && typeof calModelSettings.meta.uni_input_mb_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_mb_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'cobot'
                    && typeof calModelSettings.meta.uni_input_cobot_cal_legend_enable !== 'undefined' && calModelSettings.meta.uni_input_cobot_cal_legend_enable === 'yes'
                    && typeof calModelSettings.meta.uni_input_cobot_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_cobot_cal_cats.length > 0
                )
                ||
                (
                    calModelSettings.meta.uni_input_cal_type == 'tickera'
                    && typeof calModelSettings.meta.uni_input_tickera_cal_legend_enable !== 'undefined' && calModelSettings.meta.uni_input_tickera_cal_legend_enable === 'yes'
                    && typeof calModelSettings.meta.uni_input_tickera_cal_cats !== 'undefined' && calModelSettings.meta.uni_input_tickera_cal_cats.length > 0
                )
                && $('#js-uni-calendar-legend-categories-'+calModelSettings.id).length === 0 ) {

                    if ( calModelSettings.meta.uni_input_cal_type == 'built-in' ) {
                        var legendPosition = calModelSettings.meta.uni_input_cal_legend_position,
                            arrayLegendCats = calModelSettings.meta.uni_input_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'mb' ) {
                        var legendPosition = calModelSettings.meta.uni_input_mb_cal_legend_position,
                            arrayLegendCats = calModelSettings.meta.uni_input_mb_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'cobot' ) {
                        var legendPosition = calModelSettings.meta.uni_input_cobot_cal_legend_position,
                            arrayLegendCats = calModelSettings.meta.uni_input_cobot_cal_cats;
                    } else if ( calModelSettings.meta.uni_input_cal_type == 'tickera' ) {
                        var legendPosition = calModelSettings.meta.uni_input_tickera_cal_legend_position,
                            arrayLegendCats = calModelSettings.meta.uni_input_tickera_cal_cats;
                    }

                    if ( legendPosition == 'below' ) {
                        thisView.$el.append('<div id="js-uni-calendar-legend-categories-'+calModelSettings.id+'" class="uni-ec-calendar-legend-container legend-'+legendPosition+' uni-clear"></div>');
                    } else if ( legendPosition == 'above' ) {
                        thisView.$el.prepend('<div id="js-uni-calendar-legend-categories-'+calModelSettings.id+'" class="uni-ec-calendar-legend-container legend-'+legendPosition+' uni-clear"></div>');
                    }

                    var $elLegend = thisView.$el.find('#js-uni-calendar-legend-categories-'+calModelSettings.id),
                        listLegendItems = '';

                    $elLegend.html('<ul class="uni-ec-legend-cats-list uni-clear"></ul>');
                    var $elLegendList = $elLegend.find('ul');
                    $elLegendList.data('id', calModelSettings.id);

                    $.each(arrayLegendCats, function(i, el){
                        if ( UniCalendar.data.calCats[el] ) {
                            if ( UniCalendar.data.calCats[el].bgColor ) {
                                listLegendItems += '<li class="uni-ec-legend-cat-li '+UniCalendar.data.calCats[el].slug+'"><span class="uni-ec-legend-cat-dot" style="background-color:'+UniCalendar.data.calCats[el].bgColor+';"></span><span class="uni-ec-legend-cat-title">'+UniCalendar.data.calCats[el].title+'</span></li>';
                            } else {
                                listLegendItems += '<li class="uni-ec-legend-cat-li '+UniCalendar.data.calCats[el].slug+'"><span class="uni-ec-legend-cat-dot"></span><span class="uni-ec-legend-cat-title">'+UniCalendar.data.calCats[el].title+'</span></li>';
                            }
                        }
                    });
                    $elLegendList.html(listLegendItems);

            }

			return this;
		},
        mouseoverEvent: function( calEvent, jsEvent, view ) {

            if ( view.name == 'agendaWeek' ) {

                var $el = $(jsEvent.currentTarget),   /* event jQuery obj */
                    $elEventContainer = $el.parent(),
                    $timeEl = $(".fc-slats .fc-axis.fc-time.fc-widget-content").first(), /* el that sets height of one-block event */
                    initialPropsObj = $el.data("ec-css");

                // sets additional class ...why? to set bigger z-index via stylesheet
                $elEventContainer.addClass("fc-event-container-hover");

                // does nice animation
                var eventOuterHeight = $el.outerHeight(),
                    eventOuterWidth = $el.outerWidth(),
                    timeElOuterHeight = $timeEl.outerHeight();

                if ( typeof initialPropsObj === 'undefined' ) {
                    $el.data("ec-css", {width: eventOuterWidth, height: eventOuterHeight, marginTop: 0, marginLeft: 0});
                }

                if ( eventOuterHeight + 1 < 2*timeElOuterHeight ) { // short
                    $el.animate({
                        width: eventOuterWidth+30+"px",
                        marginLeft: "-"+15+"px",
                        height: eventOuterHeight+30+"px",
                        marginTop: "-"+15+"px",
                    }, 100, 'swing');
                } else {  // long
                    $el.animate({
                        width: eventOuterWidth+30+"px",
                        marginLeft: "-"+15+"px",
                    }, 100, 'swing');
                }

            }

        },
        mouseoutEvent: function( calEvent, jsEvent, view ) {

            if ( view.name == 'agendaWeek' ) {

                var $el = $(jsEvent.currentTarget),   /* event jQuery obj */
                    $elEventContainer = $el.parent(),
                    $timeEl = $(".fc-slats .fc-axis.fc-time.fc-widget-content").first(), /* el that sets height of one-block event */
                    initialPropsObj = $el.data("ec-css");

                // removes additional class
                $elEventContainer.removeClass("fc-event-container-hover");

                // does nice animation
                var eventOuterHeight = initialPropsObj.height,
                    eventOuterWidth = initialPropsObj.width,
                    timeElOuterHeight = $timeEl.outerHeight();

                if ( eventOuterHeight + 1 < 2*timeElOuterHeight ) {
                    $el.animate({
                        width: eventOuterWidth+"px",
                        marginLeft: initialPropsObj.marginLeft+"px",
                        height: eventOuterHeight+"px",
                        marginTop: initialPropsObj.marginTop+"px",
                        }, 100, 'swing');
                } else {
                    $el.animate({
                        width: eventOuterWidth+"px",
                        marginLeft: initialPropsObj.marginLeft+"px",
                        }, 100, 'swing');
                }

            }

        },
        viewBuiltInEvents: function( calEvent, thisCalId, chosenTheme ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisCalId);

            //
            if ( typeof UniCalendar.eventInfoFormView !== 'undefined' ) {
                UniCalendar.eventInfoFormView.remove();
            }

            UniCalendar.eventInfoFormView = new UniCalendar.EventInfoFormViewBuiltIn( { calEvent: calEvent, calModel: calModel, allUsers: UniCalendar.data.allUsers, chosenTheme: chosenTheme } );
            UniCalendar.eventInfoFormView.render();
        },
        viewThirdPartyEvents: function( calEvent, thisCalId, sThirdPartyServiceName ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get(thisCalId);

            //
            if ( typeof UniCalendar.eventInfoFormView !== 'undefined' ) {
                UniCalendar.eventInfoFormView.remove();
            }

            UniCalendar.eventInfoFormView = new UniCalendar.EventInfoFormViewThirdParty( { calEvent: calEvent, calModel: calModel, service: sThirdPartyServiceName } );
            UniCalendar.eventInfoFormView.render();
        },
        filterCatItems: function(e) {
            e.preventDefault();

            var $elLink = $(e.target),
                thisCalId = $elLink.closest('.js-uni-calendars-container').data('cal_id');

            $('.uni-ec-filter-cat-link').removeClass('uni-ec-active');
            $elLink.addClass('uni-ec-active');

            UniCalendar.fullcalendar[thisCalId].fullCalendar('refetchEvents');
        },
        getEvents: function( thisCalId, start, end, timezone, callback ) {
            var thisView = this;
            var calModel = UniCalendar.calendars.get( thisCalId );

            // removes modal indow view before get events data
            if ( typeof UniCalendar.eventInfoFormView !== 'undefined' ) {
                UniCalendar.eventInfoFormView.remove();
            }

            // filter
            var filterData = {};
            if ( $('#js-uni-calendar-filter-categories-'+thisCalId).length > 0 ) {
                filterData['uni_calendar_event_cat'] = $('#js-uni-calendar-filter-categories-'+thisCalId).find('a.uni-ec-filter-cat-link.uni-ec-active').data('slug');
            }

            // block
            var $container = this.$el;
            $container.block({
    	            message: null,
                    overlayCSS: { background: '#fff', opacity: 0.6 }
                });

            calModel.save(
                { uniAction: 'uni_ec_get_events_front', postData: {start: start.unix(), end : end.unix(), filter: filterData} },
                {
                    success: function( model, response, options ) {
                        $container.unblock();
                        if ( response.status === 'error' ) {
                            console.log(response.message);
                        }

                        if ( response.status === 'success' ) {
                            // adds array of events to the calendar
                            callback( calModel.calEvents.toJSON() );
                        }
                    },
                    error: function( model, response, options ) {
                        $container.unblock();
                        console.log( response );
                    }
                }
            );
		},
        adaptiveView: function() {

            var calModelSettings = this.model.attributes,
                uniWindowWidth = $(window).width(),
                thisCalId = this.model.id,
                currentCalWidth = UniCalendar.fullcalendar[thisCalId].data('cal-width'),
                chosenView = '',
                newView = '';

            // gets chosen value
            if ( calModelSettings.meta.uni_input_cal_type == 'gcal' ) {
                //chosenView = calModelSettings.meta.uni_input_gcal_view;
                chosenView = 'month';
            } else if ( calModelSettings.meta.uni_input_cal_type == 'built-in' ) {
                chosenView = calModelSettings.meta.uni_input_cal_view;
            } else if ( calModelSettings.meta.uni_input_cal_type == 'mb' ) {
                chosenView = calModelSettings.meta.uni_input_mb_cal_view;
            } else if ( calModelSettings.meta.uni_input_cal_type == 'cobot' ) {
                chosenView = calModelSettings.meta.uni_input_cobot_cal_view;
            }
            // finds out the new view
            if ( chosenView == 'basicWeek' || chosenView == 'agendaWeek' ) {
                newView = 'listWeek';
            } else if ( chosenView === 'month' ) {
                newView = 'listMonth';
            }
            // changes view
            if ( uniWindowWidth <= 680 && currentCalWidth > 680 && chosenView !== newView ) {
                UniCalendar.fullcalendar[thisCalId].fullCalendar( 'changeView', newView );
                UniCalendar.fullcalendar[thisCalId].data('cal-width', uniWindowWidth);
            } else if ( uniWindowWidth > 680 && currentCalWidth <= 680 ) {
                UniCalendar.fullcalendar[thisCalId].fullCalendar( 'changeView', chosenView );
                UniCalendar.fullcalendar[thisCalId].data('cal-width', uniWindowWidth);
            }

        },
        remove: function() {
            this.$el.empty().off();
            $(window).off("resize", this.updateCSS);
            document.off('click', '.uni-ec-filter-cat-link');
            this.stopListening();
            return this;
        }
	} );

	/**
	 * Single event info view for built-in events
	 */
    UniCalendar.EventInfoFormViewBuiltIn = Backbone.View.extend( {
		el: '#js-uni-ec-calendar-info-modal-form',

        template: _.template( $( '#js-uni-calendar-info-event-tmpl' ).html() ),
		initialize: function( attrs ) {
		    this.options = attrs;
		},
		events:
			{
				"click .js-uni-event-modal-close-btn": "closeForm",
			},
		render: function() {
			var html = this.template( this.options );
			this.$el.html( html );

            var $cal = $('#uni-calendar-'+this.options.calModel.id),
                calThemeClass = $cal.data('chosen-theme-class'),
                $modalTitleBar = this.$el.find('.uni-ec-title-bar');

            $modalTitleBar.removeClass();
            $modalTitleBar.addClass('uni-ec-bar uni-ec-title-bar').addClass(calThemeClass);

            new Tether({
                element: this.$el,
                target: $cal,
                attachment: 'middle center',
                targetAttachment: 'middle center',
                constraints: [
                    {
                        to: 'scrollParent',
                        pin: true
                    }
                ]
            });

            $(".uni-ec-form-section-nice-scroll").niceScroll({autohidemode:false});
            this.$el.scrollintoview();

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-event-modal-close-btn');
            this.remove();
		},
        remove: function() {
            this.$el.empty().off();
            this.stopListening();
            return this;
        }
	});

    /**
	 * Single event info view for third-party services
	 */
    UniCalendar.EventInfoFormViewThirdParty = Backbone.View.extend( {
		el: '#js-uni-ec-calendar-info-modal-form',

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

            var $cal = $('#uni-calendar-'+this.options.calModel.id),
                calThemeClass = $cal.data('chosen-theme-class'),
                $modalTitleBar = this.$el.find('.uni-ec-title-bar');

            $modalTitleBar.removeClass();
            $modalTitleBar.addClass('uni-ec-bar uni-ec-title-bar').addClass(calThemeClass);

            new Tether({
                element: this.$el,
                target: $cal,
                attachment: 'middle center',
                targetAttachment: 'middle center',
                constraints: [
                    {
                        to: 'scrollParent',
                        pin: true
                    }
                ]
            });

            $(".uni-ec-form-section-nice-scroll").niceScroll({autohidemode:'cursor'});
            this.$el.scrollintoview();

			return this;
		},
        closeForm: function() {
            this.$el.off('click', '.js-uni-event-modal-close-btn');
            this.remove();
		},
        remove: function() {
            this.$el.empty().off();
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

            if ( typeof UniCalendar.calendars !== 'undefined' ) {
                // Render the calendar
                var UniCalendarsContainer = $(".js-uni-calendars-container");
                UniCalendar.CalendarView = {};
                $.each(UniCalendarsContainer, function(i, el){
                    var $el = $(el),
                        cal_id = $el.data('cal_id'),
                        chosenTheme = $el.data('chosen-theme'),
                        chosenView = $el.data('chosen-view'),
                        calModel = UniCalendar.calendars.get( cal_id );

                    UniCalendar.CalendarView[cal_id] = new UniCalendar.CalendarFrontEndView(
                        {
                            collection: UniCalendar.calendars,
                            model: calModel,
                            cal_container: $el,
                            chosenTheme: chosenTheme,
                            chosenView: chosenView
                        }
                    );
                    UniCalendar.CalendarView[cal_id].render();
                });
                $('.uni-ec-shortcode-wrapper:last').after('<!-- info modal form --><div id="js-uni-ec-calendar-info-modal-form" class="uni-ec-main-wrapper"></div>');
            }
	};

	$( document ).ready( function() {

		UniCalendar.init();

	} );

})( jQuery, UniCalendar );