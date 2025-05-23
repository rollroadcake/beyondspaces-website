jQuery( document ).ready( function( $ ) {
    'use strict';

    $(window).on('load', function () {

        var $events_metabox     = $('#events_meta_box'),
            $schedule_metabox   = $('#schedule_meta_box'),
            $about_metabox      = $('#about_meta_box'),
            $plans_metabox      = $('#plans_meta_box'),
            $contact_metabox    = $('#contact_meta_box');

        $events_metabox.hide();
        $schedule_metabox.hide();
        $about_metabox.hide();
        $plans_metabox.hide();
        $contact_metabox.hide();

        function getActiveBlock(slug) {
            switch ( slug ) {

                case 'templ-events.php' :
                    $events_metabox.show();
                    $schedule_metabox.hide();
                    $about_metabox.hide();
                    $plans_metabox.hide();
                    $contact_metabox.hide();
                    break;

                case 'templ-events-tickera.php' :
                    $events_metabox.show();
                    $schedule_metabox.hide();
                    $about_metabox.hide();
                    $plans_metabox.hide();
                    $contact_metabox.hide();
                    break;

                case 'templ-schedule.php' :
                    $events_metabox.hide();
                    $schedule_metabox.show();
                    $about_metabox.hide();
                    $plans_metabox.hide();
                    $contact_metabox.hide();
                    break;

                case 'templ-about.php' :
                    $events_metabox.hide();
                    $schedule_metabox.hide();
                    $about_metabox.show();
                    $plans_metabox.hide();
                    $contact_metabox.hide();
                    break;

                case 'templ-plans.php' :
                    $events_metabox.hide();
                    $schedule_metabox.hide();
                    $about_metabox.hide();
                    $plans_metabox.show();
                    $contact_metabox.hide();
                    break;

                case 'templ-contact.php' :
                    $events_metabox.hide();
                    $schedule_metabox.hide();
                    $about_metabox.hide();
                    $plans_metabox.hide();
                    $contact_metabox.show();
                    break;

                default :
                    $events_metabox.hide();
                    $schedule_metabox.hide();
                    $about_metabox.hide();
                    $plans_metabox.hide();
                    $contact_metabox.hide();
                    break;
            }
        }

        if ( window.data_page ) {
            console.log('test');
            getActiveBlock(window.data_page.page_template);
        }

        $('body').on("change", '.editor-page-attributes__template .components-select-control__input, #page_template', function() {
            getActiveBlock($(this).val());
        });
    });

});