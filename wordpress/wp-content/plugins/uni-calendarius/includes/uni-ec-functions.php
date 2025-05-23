<?php
//
function uni_ec_check_calendar_shortcode_added() {

    global $post;
    if ( isset( $post->ID ) ) {
        $iPageID = $post->ID;
    } else {
        $iPageID = 0;
    }
    $aInitialCalendarIds = apply_filters( 'uni_ec_init_cals_ids_filter', array(), $iPageID );
    $aPluginsShortcodes = UniCalendar()->get_calendars_shortcodes();
    $bShortcodeFound = false;
    if ( isset($post) ) {
    foreach ( $aPluginsShortcodes as $sShortcodeName ) {
        if ( has_shortcode($post->post_content, $sShortcodeName) ) {
            $bShortcodeFound = true;
            continue;
        }
    }
    }

    if ( $bShortcodeFound || (!empty($aInitialCalendarIds) && is_array($aInitialCalendarIds) ) ) {
        return true;
    } else {
        return false;
    }

}

//
function uni_ec_templates_cal_wrapper() {
    $bOutputTmpl = uni_ec_check_calendar_shortcode_added();
    if ( $bOutputTmpl ) {
    ?>
    <!-- Calendar All Events template -->
    <script type="text/template" id="js-uni-calendar-all-events-tmpl">
        <div id="js-uni-calendar-all-events-<%= id %>">

            <div class="js-uni-ec-calendar uni-ec-calendar-main-container" data-ec_cal_id="<%= id %>"></div>

        </div>
    </script>
    <?php
    }
}
add_action('wp_footer', 'uni_ec_templates_cal_wrapper');

//
function uni_ec_templates_builtin() {
    $bOutputTmpl = uni_ec_check_calendar_shortcode_added();
    if ( $bOutputTmpl ) {
    ?>
    <!-- Event Info modal template for built-in -->
    <script type="text/template" id="js-uni-calendar-info-event-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof chosenTheme !== 'undefined' && typeof UniCalendar.data.calThemes[chosenTheme] !== 'undefined' ) { print(UniCalendar.data.calThemes[chosenTheme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
                <h3 style="color:<%= calEvent.textColor %>;"><%= calEvent.title %></h3>
            </div>

            <div class="uni-ec-form-content">

                <div class="uni-ec-form-section uni-ec-form-section-modal">

                    <% if ( calEvent.meta.event_all_day_enable && calEvent.meta.event_all_day_enable === 'yes' ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-duration">
                            <span class="uni-ec-info-section-duration-allday">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                                <?php esc_html_e('All day', 'uni-calendar') ?>
                            </span>
                    </div>
                    <% } else { %>
                    <div class="uni-ec-info-section uni-ec-info-section-availability">
                            <span class="uni-ec-info-section-duration-timed">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                                <%= moment.utc(calEvent.start._i).format(uniTimeFormat) %>
                                &nbsp;&ndash;&nbsp;
                                <%= moment.utc(calEvent.end._i).format(uniTimeFormat) %>
                            </span>
                    </div>
                    <% } %>

                    <div class="uni-ec-info-section uni-ec-info-section-description uni-ec-form-section-nice-scroll">
                        <%= calEvent.meta.event_desc %>
                    </div>

                    <% if ( calEvent.meta.event_user ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-users">
                        <% _.each(calEvent.meta.event_user, function (value, key, list) {
                            if ( allUsers[value] ) {
                        %>
                            <span class="uni-ec-info-section-user-url">
                                <i class="fa fa-user" aria-hidden="true"></i>
                                <%= allUsers[value].name %>
                            </span>
                        <%
                            }
                        }); %>
                    </div>
                    <% } %>

                    <% if ( calEvent.meta.event_click_behavior && calEvent.meta.event_click_behavior == 'modal_uri' ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-link">
                        <a class="uni-ec-info-section-link-url" href="<%= calEvent.meta.event_link_uri %>">
                            <%= calEvent.meta.event_link_text %>
                        </a>
                    </div>
                    <% } %>

                </div>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn uni-ec-form-section-modal">
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>
    <?php
    }
}
add_action('wp_footer', 'uni_ec_templates_builtin');

//
function uni_ec_templates_mb() {
    $bOutputTmpl = uni_ec_check_calendar_shortcode_added();
    if ( $bOutputTmpl ) {
    ?>
    <!-- Event Info modal template for MindBodyOnine -->
    <script type="text/template" id="js-uni-calendar-info-event-mb-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof calEvent.meta.uni_input_cal_theme !== 'undefined' && typeof UniCalendar.data.calThemes[calEvent.meta.uni_input_cal_theme] !== 'undefined' ) { print(UniCalendar.data.calThemes[calEvent.meta.uni_input_cal_theme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
                <h3 style="color:<%= calEvent.textColor %>;"><%= calEvent.title %></h3>
            </div>

            <div class="uni-ec-form-content">

                <div class="uni-ec-form-section uni-ec-form-section-modal">

                    <% if ( calEvent.meta.class_available === true ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-availability">
                            <span class="uni-ec-info-section-availability-active">
                                <i class="fa fa-calendar-check-o" aria-hidden="true"></i>
                                <?php esc_html_e('Active', 'uni-calendar') ?>
                            </span>
                    </div>
                    <% } else { %>
                    <div class="uni-ec-info-section uni-ec-info-section-availability">
                            <span class="uni-ec-info-section-availability-cancelled">
                                <i class="fa fa-calendar-times-o" aria-hidden="true"></i>
                                <?php esc_html_e('Cancelled', 'uni-calendar') ?>
                            </span>
                    </div>
                    <% } %>

                    <div class="uni-ec-info-section uni-ec-info-section-description uni-ec-form-section-nice-scroll">
                        <%= calEvent.meta.event_desc %>
                    </div>

                    <% if ( calEvent.meta.class_instructor ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-users last">
                            <span class="uni-ec-info-section-user-url">
                                <i class="fa fa-user" aria-hidden="true"></i>
                                <%= calEvent.meta.class_instructor %>
                            </span>
                    </div>
                    <% } %>

                </div>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn uni-ec-form-section-modal">
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>
    <?php
    }
}
add_action('wp_footer', 'uni_ec_templates_mb');

//
function uni_ec_templates_cobot() {
    $bOutputTmpl = uni_ec_check_calendar_shortcode_added();
    if ( $bOutputTmpl ) {
    ?>
    <!-- Event Info modal template for Cobot.me -->
    <script type="text/template" id="js-uni-calendar-info-event-cobot-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof chosenTheme !== 'undefined' && typeof UniCalendar.data.calThemes[chosenTheme] !== 'undefined' ) { print(UniCalendar.data.calThemes[chosenTheme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
                <h3 style="color:<%= calEvent.textColor %>;"><%= calEvent.title %></h3>
            </div>

            <div class="uni-ec-form-content">

                <div class="uni-ec-form-section uni-ec-form-section-modal">

                    <div class="uni-ec-info-section uni-ec-info-section-description uni-ec-form-section-nice-scroll">
                        <% if ( calEvent.meta.event_desc ) { %>
                        <%= calEvent.meta.event_desc %>
                        <% } else { %>
                        <?php esc_html_e('No description.', 'uni-calendar') ?>
                        <% } %>
                    </div>

                </div>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn uni-ec-form-section-modal">
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>
    <?php
    }
}
add_action('wp_footer', 'uni_ec_templates_cobot');

//
function uni_ec_templates_tickera() {
    $bOutputTmpl = uni_ec_check_calendar_shortcode_added();
    if ( $bOutputTmpl ) {
    ?>
    <!-- Event Info modal template for Tickera events -->
    <script type="text/template" id="js-uni-calendar-info-event-tickera-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof chosenTheme !== 'undefined' && typeof UniCalendar.data.calThemes[chosenTheme] !== 'undefined' ) { print(UniCalendar.data.calThemes[chosenTheme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
                <h3 style="color:<%= calEvent.textColor %>;"><%= calEvent.title %></h3>
            </div>

            <div class="uni-ec-form-content">

                <div class="uni-ec-form-section uni-ec-form-section-modal">

                    <% if ( calEvent.meta.event_location ) { %>
                    <div class="uni-ec-info-section uni-ec-info-section-location last">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                        <%= calEvent.meta.event_location  %>
                    </div>
                    <% } %>

                    <div class="uni-ec-info-section uni-ec-info-section-description uni-ec-form-section-nice-scroll">
                        <%= calEvent.meta.event_desc %>
                    </div>

                </div>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn centered uni-ec-form-section-modal">
                    <a href="<%= calEvent.meta.event_page_uri %>" class="btn btn-inform" target="_blank"><?php esc_html_e('The event page', 'uni-calendar') ?></a>
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>
    <?php
    }
}
add_action('wp_footer', 'uni_ec_templates_tickera');

?>