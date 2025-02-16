<?php
//
add_action( 'admin_menu', 'uni_ec_calendars_admin_menu' );
add_filter( 'parent_file', 'uni_ec_set_current_menu' );
function uni_ec_calendars_admin_menu() {
        add_menu_page(
            esc_html__('Calendarius', 'uni-calendar'),
            esc_html__('Calendarius', 'uni-calendar'),
            'manage_options',
            'uni-events-calendars',
            'uni_ec_calendars_option_page',
            'dashicons-calendar-alt',
            81.2 );

       $aSubmenuPages = array(

            // Parent page
            array(
                'parent_slug'   => 'uni-events-calendars',
                'page_title'    => esc_html__('Manage calendars', 'uni-calendar'),
                'menu_title'    => esc_html__('Manage calendars', 'uni-calendar'),
                'capability'    => 'manage_options',
                'menu_slug'     => 'uni-events-calendars',
                'function'      => 'uni_ec_calendars_option_page'
            ),

            // Calendar events categories
            array(
                'parent_slug'   => 'uni-events-calendars',
                'page_title'    => esc_html__('Calendarius event categories', 'uni-calendar'),
                'menu_title'    => esc_html__('Event categories', 'uni-calendar'),
                'capability'    => 'manage_options',
                'menu_slug'     => 'edit-tags.php?taxonomy=uni_calendar_event_cat&post_type=uni_calendar_event',
                'function'      => null
            ),

            // Calendar general settings
            array(
                'parent_slug'   => 'uni-events-calendars',
                'page_title'    => esc_html__('Calendarius plugin settings', 'uni-calendar'),
                'menu_title'    => esc_html__('Plugin settings', 'uni-calendar'),
                'capability'    => 'manage_options',
                'menu_slug'     => 'uni-events-calendars-settings',
                'function'      => 'uni_ec_calendars_settings_page'
            )

        );

        // Add each submenu item to custom admin menu.
        foreach( $aSubmenuPages as $aSubmenu ){

            add_submenu_page(
                $aSubmenu['parent_slug'],
                $aSubmenu['page_title'],
                $aSubmenu['menu_title'],
                $aSubmenu['capability'],
                $aSubmenu['menu_slug'],
                $aSubmenu['function']
            );

        }

        add_action( 'admin_init', 'uni_ec_register_settings' );
}

//
function uni_ec_register_settings() {
    register_setting( 'uni-calendar-settings-group', 'uni_calendar_time_format' );
    register_setting( 'uni-calendar-settings-group', 'uni_calendar_enable_auto_transfer' );
    register_setting( 'uni-calendar-settings-group', 'uni_calendar_day_of_auto_transfer' );
}

// highlight our menu
function uni_ec_set_current_menu($parent_file){

        global $submenu_file, $current_screen, $pagenow;

        if($current_screen->post_type == 'uni_calendar_event') {

            if($pagenow == 'post.php'){
                $submenu_file = 'edit.php?post_type='.$current_screen->post_type;
            }

            if($pagenow == 'edit-tags.php'){
                $submenu_file = 'edit-tags.php?taxonomy=uni_calendar_event_cat&post_type='.$current_screen->post_type;
            }

            $parent_file = 'uni-events-calendars';

        }

        return $parent_file;

}

//
function uni_ec_calendars_option_page() {
        ?>
        <div id="uni-calendar-wrapper" class="wrap">
            <div id="icon-tools" class="icon32"></div>

            <h2><?php esc_html_e('Calendarius', 'uni-calendar') ?></h2>

            <!-- Main content -->
            <div id="js-uni-calendar-main-content" class="uni-ec-main-wrapper">
                <div id="js-uni-calendars-container"></div>
            </div>

        </div>
        <?php
}

function uni_ec_underscore_templates_admin() {
    $aAllowedHtml = uni_ec_allowed_html_with_a();
    $sAllowedHtmlDesc = '';
    if ( is_array($aAllowedHtml) && !empty($aAllowedHtml) ) {
        foreach ( $aAllowedHtml as $Key => $Value ) {
            $sAllowedHtmlDesc .= $Key . ', ';
            if ( is_array($Value) && !empty($Value) ) {
                $sAllowedHtmlDesc .= '(';
                foreach ( $Value as $ChildKey => $ChildValue ) {
                    $sAllowedHtmlDesc .= $ChildKey . ', ';
                }
                $sAllowedHtmlDesc = rtrim($sAllowedHtmlDesc, ', ');
                $sAllowedHtmlDesc .= '), ';
            }
        }
        $sAllowedHtmlDesc = rtrim($sAllowedHtmlDesc, ', ');
    }
    ?>
    <!-- Calendar Table Item 'actions' cell -->
    <script type="text/template" id="js-calendar-table-item-actions-cell-tmpl">
        <% if ( undefined == meta.uni_input_cal_type ) { %>
        <button class="js-calendar-item-edit-action uni-ec-calendar-item-action-btn btn btn-warning" type="button"><?php esc_html_e('Edit', 'uni-calendar') ?></button>
        <% } else { %>
        <button class="js-calendar-item-edit-action uni-ec-calendar-item-action-btn btn btn-success" type="button"><?php esc_html_e('Edit', 'uni-calendar') ?></button>
        <button class="js-calendar-item-edit-events-action uni-ec-calendar-item-action-btn btn btn-success" type="button"><?php esc_html_e('View/Edit calendar\'s events', 'uni-calendar') ?></button>
        <% } %>
        <button class="js-calendar-item-delete-action uni-ec-calendar-item-action-btn btn" type="button"><?php esc_html_e('Delete', 'uni-calendar') ?></button>
    </script>

    <!-- Confirmation modal delete calendar window -->
    <script type="text/template" id="js-uni-calendar-confirm-del-calendar-tmpl">
        <div class="uni-ec-universal-container">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php esc_html_e('Are you sure?', 'uni-calendar') ?></h3>
            </div>

            <div class="uni-ec-prompt-content">
                <p><?php echo sprintf( esc_html__('You are about to delete Calendar #%s. This operation cannot be undone. Please, confirm the deletion.', 'uni-calendar'), '<%= id %>' ) ?></p>
                <button class="js-uni-calendar-confirm-cancel-btn btn btn-success" type="button"><?php esc_html_e('Cancel', 'uni-calendar') ?></button>
                <button class="js-uni-calendar-confirm-del-calendar-btn btn" type="button"><?php esc_html_e('Delete', 'uni-calendar') ?></button>
            </div>
        </div>
    </script>

    <!-- Calendar Add template -->
    <script type="text/template" id="js-uni-calendar-add-calendar-tmpl">
        <div class="uni-ec-universal-container">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php esc_html_e('Add New Calendar', 'uni-calendar') ?></h3>
            </div>

            <div class="uni-ec-form-content">
                <fieldset class="uni-ec-form-section">
                    <h5><?php esc_html_e('Title of the calendar', 'uni-calendar') ?></h5>
                    <input id="uni_input_title" type="text" placeholder="<?php esc_html_e('Calendar title', 'uni-calendar') ?>" value="" name="uni_input_title" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                </fieldset>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn">
                    <button class="js-uni-calendar-confirm-cancel-btn btn" type="button"><?php esc_html_e('Cancel', 'uni-calendar') ?></button>
                    <button class="js-uni-calendar-confirm-add-calendar-btn btn btn-success" type="button"><?php esc_html_e('Add', 'uni-calendar') ?></button>
                </div>
            </div>
        </div>
    </script>

    <!-- Calendar Edit template -->
    <script type="text/template" id="js-uni-calendar-edit-calendar-tmpl">
        <div class="uni-ec-universal-container">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php echo sprintf( esc_html__('Edit Calendar #%s settings', 'uni-calendar'), '<%= id %>' ) ?></h3>
            </div>

            <div class="uni-ec-form-content">
                <fieldset class="uni-ec-form-section first">
                    <legend><?php esc_html_e('Basic settings', 'uni-calendar') ?></legend>
                    <h5><?php esc_html_e('Title of the calendar', 'uni-calendar') ?></h5>
                    <input id="uni_input_title" type="text" placeholder="<?php esc_html_e('Calendar title', 'uni-calendar') ?>" value="<%= title %>" name="uni_input_title" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Choose type of the calendar', 'uni-calendar') ?></h5>
                    <select id="uni_input_cal_type" name="uni_input_cal_type" class="uni-ec-validation-activated" data-parsley-required="true" data-constrainer="yes">
                    <?php $aRegCals = UniCalendar()->registered_calendars();
                        foreach ( $aRegCals as $sTypeSlug => $aCalData ) {
                    ?>
                        <option value="<?php echo esc_attr($sTypeSlug); ?>"<% if ( meta.uni_input_cal_type && meta.uni_input_cal_type === '<?php echo esc_attr($sTypeSlug); ?>' ) print(' selected') %>><?php echo esc_html($aCalData['name']); ?></option>
                    <?php
                        }
                    ?>
                    </select>

                    <h5><?php esc_html_e('Theme of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Choose a theme for the calendar.', 'uni-calendar') ?></p>
                    <select id="uni_input_cal_theme" name="uni_input_cal_theme" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <?php
                        $aThemes = UniCalendar()->get_calendars_themes();
                        if ( $aThemes && is_array($aThemes) ) {
                            foreach ( $aThemes as $sThemeSlug => $aTheme ) {
                                echo '<option value="'.$sThemeSlug.'"<% if ( meta.uni_input_cal_theme && meta.uni_input_cal_theme === "'.$sThemeSlug.'" ) print(" selected") %>>'.$aTheme['display_name'].'</option>';
                            }
                        }
                        ?>
                    </select>
                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-hidden" data-constrained="uni_input_cal_type" data-constvalue="built-in">

                    <legend><?php esc_html_e('View settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('View of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The calendar has a number of different "views", or ways of displaying days and events. Choose one of the predefined views or create your own. Default is "Agenda Week".', 'uni-calendar') ?></p>
                    <select id="uni_input_cal_view" name="uni_input_cal_view" data-parsley-required="true" data-parsley-trigger="change focusout submit" data-constrainer="yes">
                        <option value="agendaWeek"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'agendaWeek' ) print(' selected') %>><?php esc_html_e('Agenda Week', 'uni-calendar') ?></option>
                        <option value="agendaDay"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'agendaDay' ) print(' selected') %>><?php esc_html_e('Agenda Day', 'uni-calendar') ?></option>
                        <option value="basicWeek"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'basicWeek' ) print(' selected') %>><?php esc_html_e('Basic Week', 'uni-calendar') ?></option>
                        <option value="basicDay"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'basicDay' ) print(' selected') %>><?php esc_html_e('Basic Day', 'uni-calendar') ?></option>
                        <option value="month"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'month' ) print(' selected') %>><?php esc_html_e('Month', 'uni-calendar') ?></option>
                        <option value="listDay"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'listDay' ) print(' selected') %>><?php esc_html_e('List Day', 'uni-calendar') ?></option>
                        <option value="listWeek"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'listWeek' ) print(' selected') %>><?php esc_html_e('List Week', 'uni-calendar') ?></option>
                        <option value="listMonth"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'listMonth' ) print(' selected') %>><?php esc_html_e('List Month', 'uni-calendar') ?></option>
                        <option value="listYear"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'listYear' ) print(' selected') %>><?php esc_html_e('List Year', 'uni-calendar') ?></option>
                        <option value="uniCustomView"<% if ( meta.uni_input_cal_view && meta.uni_input_cal_view === 'uniCustomView' ) print(' selected') %>><?php esc_html_e('Custom view & duration', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cal_view" data-constvalue="uniCustomView">
                        <h5><?php esc_html_e('Type of view', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Default is "agenda".', 'uni-calendar') ?></p>
                        <select id="uni_input_cal_type_view" name="uni_input_cal_type_view" data-parsley-required="true">
                            <option value="agenda"<% if ( meta.uni_input_cal_type_view && meta.uni_input_cal_type_view === 'agenda' ) print(' selected') %>><?php esc_html_e('Agenda', 'uni-calendar') ?></option>
                            <option value="basic"<% if ( meta.uni_input_cal_type_view && meta.uni_input_cal_type_view === 'basic' ) print(' selected') %>><?php esc_html_e('Basic', 'uni-calendar') ?></option>
                        </select>

                        <h5><?php esc_html_e('Calendar period', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Specify the number of days; digits only; default is "4".', 'uni-calendar') ?></p>
                        <input id="uni_input_cal_duration" name="uni_input_cal_duration" type="text" value="<%= meta.uni_input_cal_duration %>" data-parsley-trigger="change focusout submit" data-parsley-type="digits" />
                    </div>

                    <h5><?php esc_html_e('Hide header?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to hide a header of this timetable, including the title (date in specific format) and navigation arrows. It won\'t be posible to navigate next/prev periods. Convenient if you would like to create a static timetable like calendar with no dates, but just days of the week.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cal_header" id="uni_input_cal_header"<% if ( meta.uni_input_cal_header && meta.uni_input_cal_header == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cal_header">Hide header</label>

                    <h5><?php esc_html_e('Title format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed in the header\'s title. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_title_format" type="text" value="<%= meta.uni_input_cal_title_format %>" name="uni_input_cal_title_format">

                    <h5><?php esc_html_e('Column format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed on the calendar\'s column headings. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_column_format" type="text" value="<%= meta.uni_input_cal_column_format %>" name="uni_input_cal_column_format">

                    <h5><?php esc_html_e('Slot label format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the time-text that will be displayed on the vertical axis of the agenda views and on each event. It is a time in a specific format (based on moment.js formats). Leave blank to use default value ("h:mm a").', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_slot_label_format" type="text" value="<%= meta.uni_input_cal_slot_label_format %>" name="uni_input_cal_slot_label_format">

                    <h5><?php esc_html_e('Starting date', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Define starting date. It is "today" by default. Leave it blank if you want it to be every day "today"
                    (so the calendar will display day/week/month of current date) or specify a date. In case of specific date the calendar
                    will always show this day (for "agendaDay" and "basicDay" views) or the week of this specific date (for "agendaWeek"
                    and "basicWeek" views) or the month of this specific date (for "month" view).', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_default_date" type="text" class="js-uni-ec-datepicker-date" value="<%= meta.uni_input_cal_default_date %>" name="uni_input_cal_default_date">

                    <h5><?php esc_html_e('First day of the week', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The day that each week begins. The default value depends on the current language.', 'uni-calendar') ?></p>
                    <select id="uni_input_cal_first_day" name="uni_input_cal_first_day">
                        <option value=""<% if ( !meta.uni_input_cal_first_day ) print(' selected') %>><?php esc_html_e('Not chosen (default values is used)', 'uni-calendar') ?></option>
                        <option value="0"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '0' ) print(' selected') %>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                        <option value="1"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '1' ) print(' selected') %>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                        <option value="2"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '2' ) print(' selected') %>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                        <option value="3"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '3' ) print(' selected') %>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                        <option value="4"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '4' ) print(' selected') %>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                        <option value="5"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '5' ) print(' selected') %>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                        <option value="6"<% if ( meta.uni_input_cal_first_day && meta.uni_input_cal_first_day === '6' ) print(' selected') %>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                    </select>

                    <h5><?php esc_html_e('Slot duration (minutes)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The frequency for displaying time slots. This setting is for "agenda" type calendar views. Default value is "00" and it is equal to 60 min. slot duration.', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_slot_duration" type="text" class="js-uni-ec-datepicker-minutes" value="<%= meta.uni_input_cal_slot_duration %>" name="uni_input_cal_slot_duration">

                    <h5><?php esc_html_e('Min. start time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown starting from this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_start_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_cal_start_time %>" name="uni_input_cal_start_time">

                    <h5><?php esc_html_e('Max. end time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown ending by this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_cal_end_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_cal_end_time %>" name="uni_input_cal_end_time">

                    <legend><?php esc_html_e('Additional settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Disable events autotransfer?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('This setting overrides the same global setting for this calendar only. It means that autotransfer functionality can be enabled globally, but optionally disabled on per calendar basis.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cal_autotransfer_disable" id="uni_input_cal_autotransfer_disable"<% if ( meta.uni_input_cal_autotransfer_disable && meta.uni_input_cal_autotransfer_disable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cal_autotransfer_disable">Disable</label>

                    <h5><?php esc_html_e('Enable displaying of event categories filter?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of event categories filter just above the calendar.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cal_filter" id="uni_input_cal_filter"<% if ( meta.uni_input_cal_filter && meta.uni_input_cal_filter == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cal_filter">Enable</label>

                    <h5><?php esc_html_e('Enable displaying of the legend?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of the legend.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cal_legend_enable" id="uni_input_cal_legend_enable"<% if ( meta.uni_input_cal_legend_enable && meta.uni_input_cal_legend_enable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox" data-constrainer="yes">
                    <label for="uni_input_cal_legend_enable">Enable</label>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cal_legend_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('The position of the legend', 'uni-calendar') ?></h5>
                        <select id="uni_input_cal_legend_position" name="uni_input_cal_legend_position" data-parsley-required="true">
                            <option value="below"<% if ( meta.uni_input_cal_legend_position && meta.uni_input_cal_legend_position === 'below' ) print(' selected') %>><?php esc_html_e('Below the calendar', 'uni-calendar') ?></option>
                            <option value="above"<% if ( meta.uni_input_cal_legend_position && meta.uni_input_cal_legend_position === 'above' ) print(' selected') %>><?php esc_html_e('Above the calendar', 'uni-calendar') ?></option>
                        </select>
                    </div>

                    <h5><?php esc_html_e('Categories for the filter/legend', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Choose categories of events to be used for the filter and/or legend or leave all unchecked to use all of them.', 'uni-calendar') ?></p>
                    <% if ( typeof calCats !== 'undefined' ) { %>
                        <% _.each(calCats, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_cal_cats[]" id="uni_input_cal_cats_<%= key %>"<% if ( meta.uni_input_cal_cats && meta.uni_input_cal_cats.indexOf(key) !== -1 ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_cal_cats_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } else { %>
                        <p><?php esc_html_e('No categories created yet.', 'uni-calendar') ?></p>
                    <% } %>

                    <h5><?php esc_html_e('User roles', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to connect users of the selected roles to the calendar events. The name(s) of the connected user(s) will be displayed in the modal window.', 'uni-calendar') ?></p>
                    <% if ( userRoles ) { %>
                        <% _.each(userRoles, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_cal_roles[]" id="uni_input_cal_roles_<%= key %>"<% if ( meta.uni_input_cal_roles && meta.uni_input_cal_roles.indexOf(key) !== -1 ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_cal_roles_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } %>

                    <h5><?php esc_html_e('Display names of the connected user(s)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Check this checkbox if you want to additionally display names of the connected user(s) in an event item in the calendar grid.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cal_user_grid_enable" id="uni_input_cal_user_grid_enable"<% if ( meta.uni_input_cal_user_grid_enable && meta.uni_input_cal_user_grid_enable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cal_user_grid_enable"><?php esc_html_e('Display', 'uni-calendar') ?></label>

                </fieldset>

                <fieldset class="uni-ec-form-section  uni-ec-hidden" data-constrained="uni_input_cal_type" data-constvalue="gcal">

                    <legend><?php esc_html_e('Settings for a calendar integrated with Google Calendar', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Google Calendar API Key', 'uni-calendar') ?></h5>
                    <p><?php echo sprintf( esc_html__('You have to create an app in %s and obtain API key.', 'uni-calendar'), '<a href="https://console.developers.google.com/">Google Developer Console</a>' ) ?></p>
                    <input id="uni_input_gcal_api_key" type="text" value="<%= meta.uni_input_gcal_api_key %>" name="uni_input_gcal_api_key" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('ID of Google Calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('This Google Calendar must be public', 'uni-calendar') ?></p>
                    <input id="uni_input_gcal_cal_id" type="text" value="<%= meta.uni_input_gcal_cal_id %>" name="uni_input_gcal_cal_id" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <legend><?php esc_html_e('View settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('View of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The calendar has a number of different "views", or ways of displaying days and events. Choose one of the predefined views or create your own. Default is "Agenda Week".', 'uni-calendar') ?></p>
                    <select id="uni_input_gcal_view" name="uni_input_gcal_view" data-parsley-required="true" data-parsley-trigger="change focusout submit" data-constrainer="yes">
                        <option value="basicWeek"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'basicWeek' ) print(' selected') %>><?php esc_html_e('Basic Week', 'uni-calendar') ?></option>
                        <option value="basicDay"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'basicDay' ) print(' selected') %>><?php esc_html_e('Basic Day', 'uni-calendar') ?></option>
                        <option value="month"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'month' ) print(' selected') %>><?php esc_html_e('Month', 'uni-calendar') ?></option>
                        <option value="listDay"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'listDay' ) print(' selected') %>><?php esc_html_e('List Day', 'uni-calendar') ?></option>
                        <option value="listWeek"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'listWeek' ) print(' selected') %>><?php esc_html_e('List Week', 'uni-calendar') ?></option>
                        <option value="listMonth"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'listMonth' ) print(' selected') %>><?php esc_html_e('List Month', 'uni-calendar') ?></option>
                        <option value="listYear"<% if ( meta.uni_input_gcal_view && meta.uni_input_gcal_view === 'listYear' ) print(' selected') %>><?php esc_html_e('List Year', 'uni-calendar') ?></option>
                    </select>

                    <h5><?php esc_html_e('Hide header?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to hide a header of this timetable, including the title (date in specific format) and navigation arrows. It won\'t be posible to navigate next/prev periods. Convenient if you would like to create a static timetable like calendar with no dates, but just days of the week.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_gcal_header" id="uni_input_gcal_header"<% if ( meta.uni_input_gcal_header && meta.uni_input_gcal_header == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_gcal_header">Hide header</label>

                    <h5><?php esc_html_e('Title format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed in the header\'s title. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_gcal_title_format" type="text" value="<%= meta.uni_input_gcal_title_format %>" name="uni_input_gcal_title_format">

                    <h5><?php esc_html_e('Column format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed on the calendar\'s column headings. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_gcal_column_format" type="text" value="<%= meta.uni_input_gcal_column_format %>" name="uni_input_gcal_column_format">

                    <h5><?php esc_html_e('Slot label format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the time-text that will be displayed on the vertical axis of the agenda views and on each event. It is a time in a specific format (based on moment.js formats). Leave blank to use default value ("h:mm a").', 'uni-calendar') ?></p>
                    <input id="uni_input_gcal_slot_label_format" type="text" value="<%= meta.uni_input_gcal_slot_label_format %>" name="uni_input_gcal_slot_label_format">

                    <h5><?php esc_html_e('First day of the week', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The day that each week begins. The default value depends on the current language.', 'uni-calendar') ?></p>
                    <select id="uni_input_gcal_first_day" name="uni_input_gcal_first_day">
                        <option value=""<% if ( !meta.uni_input_gcal_first_day ) print(' selected') %>><?php esc_html_e('Not chosen (default values is used)', 'uni-calendar') ?></option>
                        <option value="0"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '0' ) print(' selected') %>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                        <option value="1"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '1' ) print(' selected') %>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                        <option value="2"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '2' ) print(' selected') %>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                        <option value="3"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '3' ) print(' selected') %>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                        <option value="4"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '4' ) print(' selected') %>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                        <option value="5"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '5' ) print(' selected') %>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                        <option value="6"<% if ( meta.uni_input_gcal_first_day && meta.uni_input_gcal_first_day === '6' ) print(' selected') %>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                    </select>

                </fieldset>

                <fieldset class="uni-ec-form-section  uni-ec-hidden" data-constrained="uni_input_cal_type" data-constvalue="mb">

                    <legend><?php esc_html_e('Settings for a calendar integrated with MindBodyOnline.com', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Studio ID', 'uni-calendar') ?></h5>
                    <input id="uni_input_mb_studio_id" type="text" value="<%= meta.uni_input_mb_studio_id %>" name="uni_input_mb_studio_id" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Source Name', 'uni-calendar') ?></h5>
                    <input id="uni_input_mb_sourcename" type="text" value="<%= meta.uni_input_mb_sourcename %>" name="uni_input_mb_sourcename" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Password (Key)', 'uni-calendar') ?></h5>
                    <input id="uni_input_mb_pass" type="text" value="<%= meta.uni_input_mb_pass %>" name="uni_input_mb_pass" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <legend><?php esc_html_e('View settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('View of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The calendar has a number of different "views", or ways of displaying days and events. Choose one of the predefined views or create your own. Default is "Agenda Week".', 'uni-calendar') ?></p>
                    <select id="uni_input_mb_cal_view" name="uni_input_mb_cal_view" data-parsley-required="true" data-parsley-trigger="change focusout submit" data-constrainer="yes">
                        <option value="agendaWeek"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'agendaWeek' ) print(' selected') %>><?php esc_html_e('Agenda Week', 'uni-calendar') ?></option>
                        <option value="agendaDay"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'agendaDay' ) print(' selected') %>><?php esc_html_e('Agenda Day', 'uni-calendar') ?></option>
                        <option value="basicWeek"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'basicWeek' ) print(' selected') %>><?php esc_html_e('Basic Week', 'uni-calendar') ?></option>
                        <option value="basicDay"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'basicDay' ) print(' selected') %>><?php esc_html_e('Basic Day', 'uni-calendar') ?></option>
                        <option value="month"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'month' ) print(' selected') %>><?php esc_html_e('Month', 'uni-calendar') ?></option>
                        <option value="listDay"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'listDay' ) print(' selected') %>><?php esc_html_e('List Day', 'uni-calendar') ?></option>
                        <option value="listWeek"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'listWeek' ) print(' selected') %>><?php esc_html_e('List Week', 'uni-calendar') ?></option>
                        <option value="listMonth"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'listMonth' ) print(' selected') %>><?php esc_html_e('List Month', 'uni-calendar') ?></option>
                        <option value="listYear"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'listYear' ) print(' selected') %>><?php esc_html_e('List Year', 'uni-calendar') ?></option>
                        <option value="uniCustomView"<% if ( meta.uni_input_mb_cal_view && meta.uni_input_mb_cal_view === 'uniCustomView' ) print(' selected') %>><?php esc_html_e('Custom view & duration', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_mb_cal_view" data-constvalue="uniCustomView">
                        <h5><?php esc_html_e('Type of view', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Default is "agenda".', 'uni-calendar') ?></p>
                        <select id="uni_input_mb_cal_type_view" name="uni_input_mb_cal_type_view" data-parsley-required="true">
                            <option value="agenda"<% if ( meta.uni_input_mb_cal_type_view && meta.uni_input_mb_cal_type_view === 'agenda' ) print(' selected') %>><?php esc_html_e('Agenda', 'uni-calendar') ?></option>
                            <option value="basic"<% if ( meta.uni_input_mb_cal_type_view && meta.uni_input_mb_cal_type_view === 'basic' ) print(' selected') %>><?php esc_html_e('Basic', 'uni-calendar') ?></option>
                        </select>

                        <h5><?php esc_html_e('Calendar period', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Specify the number of days; digits only; default is "4".', 'uni-calendar') ?></p>
                        <input id="uni_input_mb_cal_duration" name="uni_input_mb_cal_duration" type="text" value="<%= meta.uni_input_mb_cal_duration %>" data-parsley-trigger="change focusout submit" data-parsley-type="digits" />
                    </div>

                    <h5><?php esc_html_e('Hide header?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to hide a header of this timetable, including the title (date in specific format) and navigation arrows. It won\'t be posible to navigate next/prev periods. Convenient if you would like to create a static timetable like calendar with no dates, but just days of the week.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_mb_cal_header" id="uni_input_mb_cal_header"<% if ( meta.uni_input_mb_cal_header && meta.uni_input_mb_cal_header == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_mb_cal_header">Hide header</label>

                    <h5><?php esc_html_e('Title format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed in the header\'s title. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_title_format" type="text" value="<%= meta.uni_input_mb_cal_title_format %>" name="uni_input_mb_cal_title_format">

                    <h5><?php esc_html_e('Column format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed on the calendar\'s column headings. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_column_format" type="text" value="<%= meta.uni_input_mb_cal_column_format %>" name="uni_input_mb_cal_column_format">

                    <h5><?php esc_html_e('Slot label format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the time-text that will be displayed on the vertical axis of the agenda views and on each event. It is a time in a specific format (based on moment.js formats). Leave blank to use default value ("h:mm a").', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_slot_label_format" type="text" value="<%= meta.uni_input_mb_cal_slot_label_format %>" name="uni_input_mb_cal_slot_label_format">

                    <h5><?php esc_html_e('Starting date', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Define starting date. It is "today" by default. Leave it blank if you want it to be every day "today"
                    (so the calendar will display day/week/month of current date) or specify a date. In case of specific date the calendar
                    will always show this day (for "agendaDay" and "basicDay" views) or the week of this specific date (for "agendaWeek"
                    and "basicWeek" views) or the month of this specific date (for "month" view).', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_default_date" type="text" class="js-uni-ec-datepicker-date" value="<%= meta.uni_input_mb_cal_default_date %>" name="uni_input_mb_cal_default_date">

                    <h5><?php esc_html_e('First day of the week', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The day that each week begins. The default value depends on the current language.', 'uni-calendar') ?></p>
                    <select id="uni_input_mb_cal_first_day" name="uni_input_mb_cal_first_day">
                        <option value=""<% if ( !meta.uni_input_mb_cal_first_day ) print(' selected') %>><?php esc_html_e('Not chosen (default values is used)', 'uni-calendar') ?></option>
                        <option value="0"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '0' ) print(' selected') %>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                        <option value="1"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '1' ) print(' selected') %>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                        <option value="2"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '2' ) print(' selected') %>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                        <option value="3"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '3' ) print(' selected') %>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                        <option value="4"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '4' ) print(' selected') %>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                        <option value="5"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '5' ) print(' selected') %>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                        <option value="6"<% if ( meta.uni_input_mb_cal_first_day && meta.uni_input_mb_cal_first_day === '6' ) print(' selected') %>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                    </select>

                    <h5><?php esc_html_e('Slot duration (minutes)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The frequency for displaying time slots. This setting is for "agenda" type calendar views. Default value is "00" and it is equal to 60 min. slot duration.', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_slot_duration" type="text" class="js-uni-ec-datepicker-minutes" value="<%= meta.uni_input_mb_cal_slot_duration %>" name="uni_input_mb_cal_slot_duration">

                    <h5><?php esc_html_e('Min. start time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown starting from this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_start_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_mb_cal_start_time %>" name="uni_input_mb_cal_start_time">

                    <h5><?php esc_html_e('Max. end time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown ending by this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_mb_cal_end_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_mb_cal_end_time %>" name="uni_input_mb_cal_end_time">

                    <legend><?php esc_html_e('Cache settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Enable cache of API requests', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Check this checkbox if you want to enable caching of the results of request to MindBody API.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_mb_cache_enable" id="uni_input_mb_cache_enable"<% if ( meta.uni_input_mb_cache_enable && meta.uni_input_mb_cache_enable === 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_mb_cache_enable"><?php esc_html_e('Enable cache', 'uni-calendar') ?></label>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_mb_cache_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('Time until cache expiration in seconds from now. Default 6 hours will be applied if no value is provided.', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Time until expiration (sec)', 'uni-calendar') ?></p>
                        <input id="uni_input_mb_cache_time" type="text" class="js-uni-ec-datepicker-datetime" value="<%= meta.uni_input_mb_cache_time %>" name="uni_input_mb_cache_time" data-parsley-type="integer" data-parsley-trigger="change focusout submit">
                    </div>

                </fieldset>

                <fieldset class="uni-ec-form-section  uni-ec-hidden" data-constrained="uni_input_cal_type" data-constvalue="cobot">

                    <legend><?php esc_html_e('Settings for a calendar integrated with Cobot.me', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Client ID', 'uni-calendar') ?></h5>
                    <input id="uni_input_cobot_client_id" type="text" value="<%= meta.uni_input_cobot_client_id %>" name="uni_input_cobot_client_id" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Client Secret', 'uni-calendar') ?></h5>
                    <input id="uni_input_cobot_client_secret" type="text" value="<%= meta.uni_input_cobot_client_secret %>" name="uni_input_cobot_client_secret" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('User email', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('This should be an email of one of the admins of created space.', 'uni-calendar') ?><p>
                    <input id="uni_input_cobot_usermail" type="text" value="<%= meta.uni_input_cobot_usermail %>" name="uni_input_cobot_usermail" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('User Password', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('This should be a password of one of the admin users whose email address is added in previous field.', 'uni-calendar') ?><p>
                    <input id="uni_input_cobot_pass" type="password" value="<%= meta.uni_input_cobot_pass %>" name="uni_input_cobot_pass" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                   
                    <% if ( meta.uni_input_cobot_client_id && meta.uni_input_cobot_client_secret && meta.uni_input_cobot_usermail && meta.uni_input_cobot_pass && !meta.uni_input_cobot_access_token ) { %>
                        <button class="js-uni-calendar-token-btn btn btn-warning" data-service="cobot" data-operationtype="get" type="button"><?php esc_html_e('Get Cobot.me Access Token', 'uni-calendar') ?></button>
                    <% } else if ( meta.uni_input_cobot_client_id && meta.uni_input_cobot_client_secret && meta.uni_input_cobot_usermail && meta.uni_input_cobot_pass && meta.uni_input_cobot_access_token ) { %>
                        <p><?php esc_html_e('The access token has been received successfully!', 'uni-calendar') ?><p>
                        <button class="js-uni-calendar-token-btn btn" data-service="cobot" data-operationtype="delete" type="button"><?php esc_html_e('Revoke Access Token', 'uni-calendar') ?></button>
                    <% } %>

                    <h5><?php esc_html_e('Subdomain of the chosen space on Cobot.me', 'uni-calendar') ?></h5>
                    <input id="uni_input_cobot_space_subdomain" type="text" value="<%= meta.uni_input_cobot_space_subdomain %>" name="uni_input_cobot_space_subdomain">

                    <% if ( meta.uni_input_cobot_access_token && !meta.uni_input_cobot_space_info ) { %>
                        <button class="js-uni-calendar-info-btn btn btn-warning" data-service="cobot" data-operationtype="get" type="button"><?php esc_html_e('Get an info about the space and save it to the DB', 'uni-calendar') ?></button>
                    <% } else if ( meta.uni_input_cobot_space_info ) { %>
                        <p><?php esc_html_e('The info about the space includes general information, plans and resources. It is saved in the DB. Delete and fetch it again in case you change smth of these in your Cobot account.', 'uni-calendar') ?><p>
                        <button class="js-uni-calendar-info-btn btn" data-service="cobot" data-operationtype="delete" type="button"><?php esc_html_e('Delete the info about the space', 'uni-calendar') ?></button>
                    <% } %>

                    <legend><?php esc_html_e('View settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('View of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The calendar has a number of different "views", or ways of displaying days and events. Choose one of the predefined views or create your own. Default is "Agenda Week".', 'uni-calendar') ?></p>
                    <select id="uni_input_cobot_cal_view" name="uni_input_cobot_cal_view" data-parsley-required="true" data-parsley-trigger="change focusout submit" data-constrainer="yes">
                        <option value="agendaWeek"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'agendaWeek' ) print(' selected') %>><?php esc_html_e('Agenda Week', 'uni-calendar') ?></option>
                        <option value="agendaDay"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'agendaDay' ) print(' selected') %>><?php esc_html_e('Agenda Day', 'uni-calendar') ?></option>
                        <option value="basicWeek"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'basicWeek' ) print(' selected') %>><?php esc_html_e('Basic Week', 'uni-calendar') ?></option>
                        <option value="basicDay"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'basicDay' ) print(' selected') %>><?php esc_html_e('Basic Day', 'uni-calendar') ?></option>
                        <option value="month"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'month' ) print(' selected') %>><?php esc_html_e('Month', 'uni-calendar') ?></option>
                        <option value="listDay"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'listDay' ) print(' selected') %>><?php esc_html_e('List Day', 'uni-calendar') ?></option>
                        <option value="listWeek"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'listWeek' ) print(' selected') %>><?php esc_html_e('List Week', 'uni-calendar') ?></option>
                        <option value="listMonth"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'listMonth' ) print(' selected') %>><?php esc_html_e('List Month', 'uni-calendar') ?></option>
                        <option value="listYear"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'listYear' ) print(' selected') %>><?php esc_html_e('List Year', 'uni-calendar') ?></option>
                        <option value="uniCustomView"<% if ( meta.uni_input_cobot_cal_view && meta.uni_input_cobot_cal_view === 'uniCustomView' ) print(' selected') %>><?php esc_html_e('Custom view & duration', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_mb_cal_view" data-constvalue="uniCustomView">
                        <h5><?php esc_html_e('Type of view', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Default is "agenda".', 'uni-calendar') ?></p>
                        <select id="uni_input_cobot_cal_type_view" name="uni_input_cobot_cal_type_view" data-parsley-required="true">
                            <option value="agenda"<% if ( meta.uni_input_cobot_cal_type_view && meta.uni_input_cobot_cal_type_view === 'agenda' ) print(' selected') %>><?php esc_html_e('Agenda', 'uni-calendar') ?></option>
                            <option value="basic"<% if ( meta.uni_input_cobot_cal_type_view && meta.uni_input_cobot_cal_type_view === 'basic' ) print(' selected') %>><?php esc_html_e('Basic', 'uni-calendar') ?></option>
                        </select>

                        <h5><?php esc_html_e('Calendar period', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Specify the number of days; digits only; default is "4".', 'uni-calendar') ?></p>
                        <input id="uni_input_cobot_cal_duration" name="uni_input_cobot_cal_duration" type="text" value="<%= meta.uni_input_cobot_cal_duration %>" data-parsley-trigger="change focusout submit" data-parsley-type="digits" />
                    </div>

                    <h5><?php esc_html_e('Hide header?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to hide a header of this timetable, including the title (date in specific format) and navigation arrows. It won\'t be posible to navigate next/prev periods. Convenient if you would like to create a static timetable like calendar with no dates, but just days of the week.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cobot_cal_header" id="uni_input_cobot_cal_header"<% if ( meta.uni_input_cobot_cal_header && meta.uni_input_cobot_cal_header == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cobot_cal_header">Hide header</label>

                    <h5><?php esc_html_e('Title format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed in the header\'s title. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_title_format" type="text" value="<%= meta.uni_input_cobot_cal_title_format %>" name="uni_input_cobot_cal_title_format">

                    <h5><?php esc_html_e('Column format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed on the calendar\'s column headings. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_column_format" type="text" value="<%= meta.uni_input_cobot_cal_column_format %>" name="uni_input_cobot_cal_column_format">

                    <h5><?php esc_html_e('Slot label format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the time-text that will be displayed on the vertical axis of the agenda views and on each event. It is a time in a specific format (based on moment.js formats). Leave blank to use default value ("h:mm a").', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_slot_label_format" type="text" value="<%= meta.uni_input_cobot_cal_slot_label_format %>" name="uni_input_cobot_cal_slot_label_format">

                    <h5><?php esc_html_e('Starting date', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Define starting date. It is "today" by default. Leave it blank if you want it to be every day "today"
                    (so the calendar will display day/week/month of current date) or specify a date. In case of specific date the calendar
                    will always show this day (for "agendaDay" and "basicDay" views) or the week of this specific date (for "agendaWeek"
                    and "basicWeek" views) or the month of this specific date (for "month" view).', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_default_date" type="text" class="js-uni-ec-datepicker-date" value="<%= meta.uni_input_cobot_cal_default_date %>" name="uni_input_cobot_cal_default_date">

                    <h5><?php esc_html_e('First day of the week', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The day that each week begins. The default value depends on the current language.', 'uni-calendar') ?></p>
                    <select id="uni_input_cobot_cal_first_day" name="uni_input_cobot_cal_first_day">
                        <option value=""<% if ( !meta.uni_input_cobot_cal_first_day ) print(' selected') %>><?php esc_html_e('Not chosen (default values is used)', 'uni-calendar') ?></option>
                        <option value="0"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '0' ) print(' selected') %>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                        <option value="1"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '1' ) print(' selected') %>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                        <option value="2"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '2' ) print(' selected') %>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                        <option value="3"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '3' ) print(' selected') %>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                        <option value="4"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '4' ) print(' selected') %>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                        <option value="5"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '5' ) print(' selected') %>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                        <option value="6"<% if ( meta.uni_input_cobot_cal_first_day && meta.uni_input_cobot_cal_first_day === '6' ) print(' selected') %>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                    </select>

                    <h5><?php esc_html_e('Slot duration (minutes)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The frequency for displaying time slots. This setting is for "agenda" type calendar views. Default value is "00" and it is equal to 60 min. slot duration.', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_slot_duration" type="text" class="js-uni-ec-datepicker-minutes" value="<%= meta.uni_input_cobot_cal_slot_duration %>" name="uni_input_cobot_cal_slot_duration">

                    <h5><?php esc_html_e('Min. start time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown starting from this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_start_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_cobot_cal_start_time %>" name="uni_input_cobot_cal_start_time">

                    <h5><?php esc_html_e('Max. end time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown ending by this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_cobot_cal_end_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_cobot_cal_end_time %>" name="uni_input_cobot_cal_end_time">

                    <legend><?php esc_html_e('Additional settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Enable displaying of event categories filter?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of event categories filter just above the calendar.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cobot_cal_filter" id="uni_input_cobot_cal_filter"<% if ( meta.uni_input_cobot_cal_filter && meta.uni_input_cobot_cal_filter == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_cobot_cal_filter">Enable</label>

                    <h5><?php esc_html_e('Enable displaying of the legend?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of the legend.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cobot_cal_legend_enable" id="uni_input_cobot_cal_legend_enable"<% if ( meta.uni_input_cobot_cal_legend_enable && meta.uni_input_cobot_cal_legend_enable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox" data-constrainer="yes">
                    <label for="uni_input_cobot_cal_legend_enable">Enable</label>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cobot_cal_legend_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('The position of the legend', 'uni-calendar') ?></h5>
                        <select id="uni_input_cobot_cal_legend_position" name="uni_input_cobot_cal_legend_position" data-parsley-required="true">
                            <option value="below"<% if ( meta.uni_input_cobot_cal_legend_position && meta.uni_input_cobot_cal_legend_position === 'below' ) print(' selected') %>><?php esc_html_e('Below the calendar', 'uni-calendar') ?></option>
                            <option value="above"<% if ( meta.uni_input_cobot_cal_legend_position && meta.uni_input_cobot_cal_legend_position === 'above' ) print(' selected') %>><?php esc_html_e('Above the calendar', 'uni-calendar') ?></option>
                        </select>
                    </div>

                    <h5><?php esc_html_e('Categories for the filter/legend', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Choose categories of events to be used for the filter and/or legend or leave all unchecked to use all of them.', 'uni-calendar') ?></p>
                    <% if ( typeof calCats !== 'undefined' ) { %>
                        <% _.each(calCats, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_cobot_cal_cats[]" id="uni_input_cobot_cal_cats_<%= key %>"<% if ( meta.uni_input_cobot_cal_cats && meta.uni_input_cobot_cal_cats.indexOf(key) !== -1 ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_cobot_cal_cats_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } else { %>
                        <p><?php esc_html_e('No categories created yet.', 'uni-calendar') ?></p>
                    <% } %>

                    <legend><?php esc_html_e('Cache settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Enable cache of API requests', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Check this checkbox if you want to enable caching of the results of request to Cobot.me API.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_cobot_cache_enable" id="uni_input_cobot_cache_enable"<% if ( meta.uni_input_cobot_cache_enable && meta.uni_input_cobot_cache_enable === 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox" data-constrainer="yes">
                    <label for="uni_input_cobot_cache_enable"><?php esc_html_e('Enable cache', 'uni-calendar') ?></label>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cobot_cache_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('Time until cache expiration in seconds from now. Default 6 hours will be applied if no value is provided.', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Time until expiration (sec)', 'uni-calendar') ?></p>
                        <input id="uni_input_cobot_cache_time" type="text" class="js-uni-ec-datepicker-datetime" value="<%= meta.uni_input_cobot_cache_time %>" name="uni_input_cobot_cache_time" data-parsley-type="integer" data-parsley-trigger="change focusout submit">
                    </div>

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-hidden" data-constrained="uni_input_cal_type" data-constvalue="tickera">

                    <legend><?php esc_html_e('View settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('View of the calendar', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The calendar has a number of different "views", or ways of displaying days and events. Choose one of the predefined views or create your own. Default is "Agenda Week".', 'uni-calendar') ?></p>
                    <select id="uni_input_tickera_cal_view" name="uni_input_tickera_cal_view" data-parsley-required="true" data-parsley-trigger="change focusout submit" data-constrainer="yes">
                        <option value="agendaWeek"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'agendaWeek' ) print(' selected') %>><?php esc_html_e('Agenda Week', 'uni-calendar') ?></option>
                        <option value="agendaDay"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'agendaDay' ) print(' selected') %>><?php esc_html_e('Agenda Day', 'uni-calendar') ?></option>
                        <option value="basicWeek"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'basicWeek' ) print(' selected') %>><?php esc_html_e('Basic Week', 'uni-calendar') ?></option>
                        <option value="basicDay"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'basicDay' ) print(' selected') %>><?php esc_html_e('Basic Day', 'uni-calendar') ?></option>
                        <option value="month"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'month' ) print(' selected') %>><?php esc_html_e('Month', 'uni-calendar') ?></option>
                        <option value="listDay"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'listDay' ) print(' selected') %>><?php esc_html_e('List Day', 'uni-calendar') ?></option>
                        <option value="listWeek"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'listWeek' ) print(' selected') %>><?php esc_html_e('List Week', 'uni-calendar') ?></option>
                        <option value="listMonth"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'listMonth' ) print(' selected') %>><?php esc_html_e('List Month', 'uni-calendar') ?></option>
                        <option value="listYear"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'listYear' ) print(' selected') %>><?php esc_html_e('List Year', 'uni-calendar') ?></option>
                        <option value="uniCustomView"<% if ( meta.uni_input_tickera_cal_view && meta.uni_input_tickera_cal_view === 'uniCustomView' ) print(' selected') %>><?php esc_html_e('Custom view & duration', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cal_view" data-constvalue="uniCustomView">
                        <h5><?php esc_html_e('Type of view', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Default is "agenda".', 'uni-calendar') ?></p>
                        <select id="uni_input_tickera_cal_type_view" name="uni_input_tickera_cal_type_view" data-parsley-required="true">
                            <option value="agenda"<% if ( meta.uni_input_tickera_cal_type_view && meta.uni_input_tickera_cal_type_view === 'agenda' ) print(' selected') %>><?php esc_html_e('Agenda', 'uni-calendar') ?></option>
                            <option value="basic"<% if ( meta.uni_input_tickera_cal_type_view && meta.uni_input_tickera_cal_type_view === 'basic' ) print(' selected') %>><?php esc_html_e('Basic', 'uni-calendar') ?></option>
                        </select>

                        <h5><?php esc_html_e('Calendar period', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('Specify the number of days; digits only; default is "4".', 'uni-calendar') ?></p>
                        <input id="uni_input_tickera_cal_duration" name="uni_input_tickera_cal_duration" type="text" value="<%= meta.uni_input_tickera_cal_duration %>" data-parsley-trigger="change focusout submit" data-parsley-type="digits" />
                    </div>

                    <h5><?php esc_html_e('Hide header?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to hide a header of this timetable, including the title (date in specific format) and navigation arrows. It won\'t be posible to navigate next/prev periods. Convenient if you would like to create a static timetable like calendar with no dates, but just days of the week.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_tickera_cal_header" id="uni_input_tickera_cal_header"<% if ( meta.uni_input_tickera_cal_header && meta.uni_input_tickera_cal_header == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_tickera_cal_header">Hide header</label>

                    <h5><?php esc_html_e('Title format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed in the header\'s title. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_title_format" type="text" value="<%= meta.uni_input_tickera_cal_title_format %>" name="uni_input_tickera_cal_title_format">

                    <h5><?php esc_html_e('Column format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the text that will be displayed on the calendar\'s column headings. It is a date in a specific format (based on moment.js formats). Leave blank to use default value.', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_column_format" type="text" value="<%= meta.uni_input_tickera_cal_column_format %>" name="uni_input_tickera_cal_column_format">

                    <h5><?php esc_html_e('Slot label format', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Determines the time-text that will be displayed on the vertical axis of the agenda views and on each event. It is a time in a specific format (based on moment.js formats). Leave blank to use default value ("h:mm a").', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_slot_label_format" type="text" value="<%= meta.uni_input_tickera_cal_slot_label_format %>" name="uni_input_tickera_cal_slot_label_format">

                    <h5><?php esc_html_e('Starting date', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Define starting date. It is "today" by default. Leave it blank if you want it to be every day "today"
                    (so the calendar will display day/week/month of current date) or specify a date. In case of specific date the calendar
                    will always show this day (for "agendaDay" and "basicDay" views) or the week of this specific date (for "agendaWeek"
                    and "basicWeek" views) or the month of this specific date (for "month" view).', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_default_date" type="text" class="js-uni-ec-datepicker-date" value="<%= meta.uni_input_tickera_cal_default_date %>" name="uni_input_tickera_cal_default_date">

                    <h5><?php esc_html_e('First day of the week', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The day that each week begins. The default value depends on the current language.', 'uni-calendar') ?></p>
                    <select id="uni_input_tickera_cal_first_day" name="uni_input_tickera_cal_first_day">
                        <option value=""<% if ( !meta.uni_input_tickera_cal_first_day ) print(' selected') %>><?php esc_html_e('Not chosen (default values is used)', 'uni-calendar') ?></option>
                        <option value="0"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '0' ) print(' selected') %>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                        <option value="1"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '1' ) print(' selected') %>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                        <option value="2"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '2' ) print(' selected') %>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                        <option value="3"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '3' ) print(' selected') %>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                        <option value="4"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '4' ) print(' selected') %>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                        <option value="5"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '5' ) print(' selected') %>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                        <option value="6"<% if ( meta.uni_input_tickera_cal_first_day && meta.uni_input_tickera_cal_first_day === '6' ) print(' selected') %>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                    </select>

                    <h5><?php esc_html_e('Slot duration (minutes)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('The frequency for displaying time slots. This setting is for "agenda" type calendar views. Default value is "00" and it is equal to 60 min. slot duration.', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_slot_duration" type="text" class="js-uni-ec-datepicker-minutes" value="<%= meta.uni_input_tickera_cal_slot_duration %>" name="uni_input_tickera_cal_slot_duration">

                    <h5><?php esc_html_e('Min. start time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown starting from this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_start_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_tickera_cal_start_time %>" name="uni_input_tickera_cal_start_time">

                    <h5><?php esc_html_e('Max. end time (hour)', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown ending by this time. This setting is for "agenda" type calendar views.', 'uni-calendar') ?></p>
                    <input id="uni_input_tickera_cal_end_time" type="text" class="js-uni-ec-datepicker-time" value="<%= meta.uni_input_tickera_cal_end_time %>" name="uni_input_tickera_cal_end_time">

                    <legend><?php esc_html_e('Additional settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Enable displaying of event categories filter?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of event categories filter just above the calendar.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_tickera_cal_filter" id="uni_input_tickera_cal_filter"<% if ( meta.uni_input_tickera_cal_filter && meta.uni_input_tickera_cal_filter == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_tickera_cal_filter">Enable</label>

                    <h5><?php esc_html_e('Enable displaying of the legend?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to enable/disable displaying of the legend.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_tickera_cal_legend_enable" id="uni_input_tickera_cal_legend_enable"<% if ( meta.uni_input_tickera_cal_legend_enable && meta.uni_input_tickera_cal_legend_enable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox" data-constrainer="yes">
                    <label for="uni_input_tickera_cal_legend_enable">Enable</label>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_cal_legend_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('The position of the legend', 'uni-calendar') ?></h5>
                        <select id="uni_input_tickera_cal_legend_position" name="uni_input_tickera_cal_legend_position" data-parsley-required="true">
                            <option value="below"<% if ( meta.uni_input_tickera_cal_legend_position && meta.uni_input_tickera_cal_legend_position === 'below' ) print(' selected') %>><?php esc_html_e('Below the calendar', 'uni-calendar') ?></option>
                            <option value="above"<% if ( meta.uni_input_tickera_cal_legend_position && meta.uni_input_tickera_cal_legend_position === 'above' ) print(' selected') %>><?php esc_html_e('Above the calendar', 'uni-calendar') ?></option>
                        </select>
                    </div>

                    <h5><?php esc_html_e('Categories for the filter/legend', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Choose categories of events to be used for the filter and/or legend or leave all unchecked to use all of them.', 'uni-calendar') ?></p>
                    <% if ( typeof calCats !== 'undefined' ) { %>
                        <% _.each(calCats, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_tickera_cal_cats[]" id="uni_input_tickera_cal_cats_<%= key %>"<% if ( meta.uni_input_tickera_cal_cats && meta.uni_input_tickera_cal_cats.indexOf(key) !== -1 ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_tickera_cal_cats_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } else { %>
                        <p><?php esc_html_e('No categories created yet.', 'uni-calendar') ?></p>
                    <% } %>

                    <h5><?php esc_html_e('Display locations of the events', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Check this checkbox if you want to additionally display locations in an event item in the calendar grid.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_tickera_cal_address_grid_enable" id="uni_input_tickera_cal_address_grid_enable"<% if ( meta.uni_input_tickera_cal_address_grid_enable && meta.uni_input_tickera_cal_address_grid_enable == 'yes' ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_tickera_cal_address_grid_enable"><?php esc_html_e('Display', 'uni-calendar') ?></label>

                </fieldset>

                <div class="uni-ec-form-section uni-ec-form-section-with-btn">
                    <button class="js-uni-calendar-confirm-cancel-btn btn" type="button"><?php esc_html_e('Cancel/Close', 'uni-calendar') ?></button>
                    <button class="js-uni-calendar-confirm-edit-calendar-btn btn btn-success" type="button"><?php esc_html_e('Save changes', 'uni-calendar') ?></button>
                </div>
            </div>
        </div>
    </script>

    <!-- Calendar All Events template -->
    <script type="text/template" id="js-uni-calendar-all-events-tmpl">
        <div id="js-uni-calendar-all-events" class="uni-ec-universal-container <% if ( typeof meta.uni_input_cal_theme !== 'undefined' && typeof UniCalendar.data.calThemes[meta.uni_input_cal_theme] !== 'undefined' ) { print(UniCalendar.data.calThemes[meta.uni_input_cal_theme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php echo sprintf( esc_html__('All events of Calendar "%s"', 'uni-calendar'), '<%= title %>' ) ?></h3>
            </div>

            <div class="uni-ec-prompt-content">
                <button class="js-uni-calendar-confirm-back-btn btn btn-inform" type="button"><?php esc_html_e('Back to all calendars', 'uni-calendar') ?></button>
                <% if( meta.uni_input_cal_view == 'listDay' || meta.uni_input_cal_view == 'listWeek' || meta.uni_input_cal_view == 'listMonth' || meta.uni_input_cal_view == 'listYear' ) { %>
                <button class="js-uni-calendar-add-event-btn btn btn-success" type="button" data-view="<%= meta.uni_input_cal_view %>"><?php esc_html_e('Add event', 'uni-calendar') ?></button>
                <% } %>
            </div>

            <div id="js-uni-ec-calendar" class="uni-ec-calendar-main-container"></div>

            <div id="js-uni-ec-calendar-modal-form"></div>

            <div class="uni-ec-prompt-content">
                <button class="js-uni-calendar-confirm-back-btn btn btn-inform" type="button"><?php esc_html_e('Back to all calendars', 'uni-calendar') ?></button>
            </div>

        </div>
    </script>

    <!-- Event Add template -->
    <script type="text/template" id="js-uni-calendar-add-event-tmpl">
        <div class="uni-ec-universal-container">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php esc_html_e('Add event', 'uni-calendar') ?></h3>
            </div>

            <div class="uni-ec-form-content">

                <input type="hidden" name="uni_input_cal_id" class="uni-ec-validation-activated" value="<%= cal_id %>" />
                <input type="hidden" name="uni_input_cal_view_type" class="uni-ec-validation-activated" value="<%= calView.type %>" />
                <% if ( calView.type === 'month' || calView.type === 'basicWeek' || calView.type === 'basicDay' ) { %>
                <input type="hidden" name="uni_input_event_timestamp" class="uni-ec-validation-activated" value="<%= moment(event_date).format('YYYY-MM-DD') %>" />
                <% } else if ( calView.type === 'listDay' || calView.type === 'listWeek' || calView.type === 'listMonth' || calView.type === 'listYear' ) { %>
                <% } else { %>
                <input type="hidden" name="uni_input_event_timestamp" class="uni-ec-validation-activated" value="<%= moment(event_date).toISOString() %>" />
                <% } %>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal first">

                    <legend><?php esc_html_e('General settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Title', 'uni-calendar') ?>*</h5>
                    <input id="uni_input_title" type="text" placeholder="<?php esc_html_e('Event title', 'uni-calendar') ?>" value="" name="uni_input_title" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Description', 'uni-calendar') ?>*</h5>
                    <p><?php echo sprintf( esc_html__('Allowed HTML tags are: %1$s. Your text will be auto wrapped with %2$s tag. %3$s tag will be added in place of each line break. Pieces of the text divided by an empty line will be wrapped with %2$s tag.', 'uni-calendar'), '<code>'.$sAllowedHtmlDesc.'</code>', '<code>p</code>', '<code>br</code>' ); ?></p>
                    <textarea id="uni_input_event_desc" name="uni_input_event_desc" placeholder="<?php esc_html_e('Event description', 'uni-calendar') ?>" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit"></textarea>

                    <% if ( calView.type === 'listDay' || calView.type === 'listWeek' || calView.type === 'listMonth' || calView.type === 'listYear' ) { %>
                    <h5><?php esc_html_e('Event date', 'uni-calendar') ?>*</h5>
                    <input id="uni_input_event_timestamp" type="text" class="js-uni-ec-datepicker-date uni-ec-validation-activated" value="" name="uni_input_event_timestamp" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    <% } %>

                    <% if ( calView.type === 'month' || calView.type === 'basicWeek' || calView.type === 'basicDay' || calView.type === 'listDay' || calView.type === 'listWeek' || calView.type === 'listMonth' || calView.type === 'listYear' ) { %>
                    <h5><?php esc_html_e('All day event?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Only all day events can be two or more days long.', 'uni-calendar') ?></p>
                    <select name="uni_input_event_all_day_enable" id="uni_input_event_all_day_enable" class="uni-ec-validation-activated" data-constrainer="yes">
                        <option value="no"><?php esc_html_e('No', 'uni-calendar') ?></option>
                        <option value="yes"><?php esc_html_e('Yes', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_all_day_enable" data-constvalue="no">
                        <p><?php esc_html_e('Only non all day events have start and end time.', 'uni-calendar') ?></p>

                        <h5><?php esc_html_e('Start time of the event', 'uni-calendar') ?>*</h5>
                        <input id="uni_input_event_manual_start_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="" name="uni_input_event_manual_start_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                        <h5><?php esc_html_e('End time of the event', 'uni-calendar') ?>*</h5>
                        <input id="uni_input_event_manual_end_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="" name="uni_input_event_manual_end_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>
                    <% } %>

                    <h5><?php esc_html_e('Recurring event?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('It is possible to create several identical events within the chosen range of dates at once.', 'uni-calendar') ?></p>
                    <select name="uni_input_event_recurring_enable" id="uni_input_event_recurring_enable" class="uni-ec-validation-activated" data-constrainer="yes">
                        <option value="no"><?php esc_html_e('No', 'uni-calendar') ?></option>
                        <option value="yes"><?php esc_html_e('Yes', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_recurring_enable" data-constvalue="yes">
                        <h5><?php esc_html_e('Type of recurring', 'uni-calendar') ?></h5>
                        <select name="uni_input_event_recurring_type" id="uni_input_event_recurring_type" class="uni-ec-validation-activated" data-constrainer="yes">
                            <option value="daily"><?php esc_html_e('Daily', 'uni-calendar') ?></option>
                            <option value="custom"><?php esc_html_e('Custom', 'uni-calendar') ?></option>
                        </select>

                        <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_recurring_type" data-constvalue="custom">
                            <input type="checkbox" value="0" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_0" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_0">Sun</label>

                            <input type="checkbox" value="1" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_1" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_1">Mon</label>

                            <input type="checkbox" value="2" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_2" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_2">Tue</label>

                            <input type="checkbox" value="3" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_3" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_3">Wed</label>

                            <input type="checkbox" value="4" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_4" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_4">Thu</label>

                            <input type="checkbox" value="5" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_5" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_5">Fri</label>

                            <input type="checkbox" value="6" name="uni_input_event_recurring_days[]" id="uni_input_event_recurring_days_6" class="categories-checkbox uni-ec-validation-activated">
                            <label for="uni_input_event_recurring_days_6">Sat</label>
                        </div>

                        <p style="display: inline-block;margin-right: 10px;"><?php esc_html_e('Recurring from', 'uni-calendar') ?></p>
                        <input id="uni_input_event_recurring_start_date" type="text" class="js-uni-ec-datepicker-date-recurring uni-ec-validation-activated" value="" name="uni_input_event_recurring_start_date" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                        <p style="display: inline-block;margin-right: 10px;"><?php esc_html_e('until', 'uni-calendar') ?></p>
                        <input id="uni_input_event_recurring_end_date" type="text" class="js-uni-ec-datepicker-date-recurring uni-ec-validation-activated" value="" name="uni_input_event_recurring_end_date" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>

                    <legend><?php esc_html_e('Additional settings', 'uni-calendar') ?></legend>

                    <% if ( typeof calCats !== 'undefined' ) { %>
                    <h5><?php esc_html_e('Category', 'uni-calendar') ?></h5>
                    <select id="uni_input_event_cat" name="uni_input_event_cat" class="uni-ec-validation-activated">
                        <option value="0"><?php esc_html_e('No Category', 'uni-calendar') ?></option>
                        <% _.each(calCats, function (value, key, list) { %>
                            <option value="<%= key %>"><%= value %></option>
                        <% }); %>
                    </select>
                    <% } %>

                    <h5><?php esc_html_e('Users connected', 'uni-calendar') ?></h5>
                    <% if ( allowedUsers !== 'undefined' || allowedUsers.length > 0 ) { %>
                        <% _.each(allowedUsers, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_event_user[]" id="uni_input_event_user_<%= key %>" class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_event_user_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } else { %>
                        <p><?php esc_html_e('No user roles are selected in the Calendar settings.', 'uni-calendar') ?></p>
                    <% } %>

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal">

                    <legend><?php esc_html_e('Click behavior settings', 'uni-calendar') ?></legend>

                    <p><?php esc_html_e('The modal window with the event content will be shown on click by default. However, it is possible to change this default behavior.', 'uni-calendar') ?></p>

                    <h5><?php esc_html_e('Behavior', 'uni-calendar') ?></h5>
                    <select name="uni_input_event_click_behavior" id="uni_input_event_click_behavior" class="uni-ec-validation-activated" data-constrainer="yes">
                        <option value="modal"><?php esc_html_e('Open modal window (default)', 'uni-calendar') ?></option>
                        <option value="uri"><?php esc_html_e('Redirect to custom URI', 'uni-calendar') ?></option>
                        <option value="modal_uri"><?php esc_html_e('Open modal window with a custom link', 'uni-calendar') ?></option>
                    <?php /*if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>
                        <option value="wc"><?php esc_html_e('Open modal window with a link to WC product', 'uni-calendar') ?></option>
                    <?php }*/ ?>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="uri">
                        <h5><?php esc_html_e('Define custom URI', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('The user will be redirected to this URI instead of openning the modal window on the click.', 'uni-calendar') ?></p>
                        <input id="uni_input_event_uri" type="text" class="uni-ec-validation-activated" value="" name="uni_input_event_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="modal_uri">
                        <h5><?php esc_html_e('URI for the link', 'uni-calendar') ?></h5>
                        <input id="uni_input_event_link_uri" type="text" class="uni-ec-validation-activated" value="" name="uni_input_event_link_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                        <h5><?php esc_html_e('Text for the link', 'uni-calendar') ?></h5>
                        <input id="uni_input_event_link_text" type="text" class="uni-ec-validation-activated" value="" name="uni_input_event_link_text" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>

                    <?php /*if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>
                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="wc">
                        <p><?php esc_html_e('The modal window will be shown on click and will contain', 'uni-calendar') ?></p>
                        <input id="uni_input_event_uri" type="text" class="uni-ec-validation-activated" value="" name="uni_input_event_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>
                    <?php }*/ ?>

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal">

                    <legend><?php esc_html_e('Visual appearance', 'uni-calendar') ?></legend>

                    <% if ( calView.type === 'agendaDay' || calView.type === 'agendaWeek' ) { %>
                    <h5><?php esc_html_e('Background image', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Add/upload an image via WP media uploader. This setting holds an ID of a media attachment. Empty the field if no image is needed.', 'uni-calendar') ?></p>
                    <input type="button" class="js-uni-ec-image-upload" value="<?php esc_html_e('Add/Upload image', 'uni-calendar') ?>" />
                    <input id="uni_input_event_bg_image_id" type="text" class="js-uni-ec-image-upload-field uni-ec-validation-activated" value="" name="uni_input_event_bg_image_id">
                    <% } %>

                    <h5><?php esc_html_e('Background colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets background colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_backgroundColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="" name="uni_input_event_backgroundColor">

                    <h5><?php esc_html_e('Border colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets border colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_borderColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="" name="uni_input_event_borderColor">

                    <h5><?php esc_html_e('Text colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets text colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_textColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="" name="uni_input_event_textColor">

                </fieldset>

                <div class="uni-ec-form-section uni-ec-form-section-modal uni-ec-form-section-with-btn">
                    <button class="js-uni-event-confirm-cancel-btn btn" type="button"><?php esc_html_e('Cancel', 'uni-calendar') ?></button>
                    <button class="js-uni-event-confirm-save-btn btn btn-success" type="button"><?php esc_html_e('Create an event', 'uni-calendar') ?></button>
                </div>
            </div>

        </div>
    </script>

    <!-- Event Edit template -->
    <script type="text/template" id="js-uni-calendar-edit-event-tmpl">
        <div class="uni-ec-universal-container">
            <div class="uni-ec-bar uni-ec-title-bar">
                <h3><?php esc_html_e('Edit event', 'uni-calendar') ?></h3>
            </div>

            <div class="uni-ec-form-content">

                <input type="hidden" name="uni_input_cal_id" class="uni-ec-validation-activated" value="<%= cal_id %>" />
                <input type="hidden" name="uni_input_cal_view_type" class="uni-ec-validation-activated" value="<%= calView.type %>" />
                <% if ( calView.type === 'month' || calView.type === 'basicWeek' || calView.type === 'basicDay' || calView.type === 'listDay' || calView.type === 'listWeek' || calView.type === 'listMonth' || calView.type === 'listYear' ) { %>
                <input type="hidden" name="uni_input_event_timestamp" class="uni-ec-validation-activated" value="<%= moment(calEvent.start._i).format('YYYY-MM-DD') %>" />
                    <% if ( calEvent.end === null || calEvent.end == 'null' ) { %>
                    <input type="hidden" name="uni_input_event_timestamp_end" class="uni-ec-validation-activated" value="<%= moment(calEvent.start._i).format('YYYY-MM-DD') %>" />
                    <% } else { %>
                    <input type="hidden" name="uni_input_event_timestamp_end" class="uni-ec-validation-activated" value="<%= moment(calEvent.end._i).format('YYYY-MM-DD') %>" />
                    <% } %>
                <% } else { %>
                <input type="hidden" name="uni_input_event_timestamp" class="uni-ec-validation-activated" value="<%= calEvent.start._i %>" />
                <input type="hidden" name="uni_input_event_timestamp_end" class="uni-ec-validation-activated" value="<%= calEvent.end._i %>" />
                <% } %>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal first">

                    <legend><?php esc_html_e('General settings', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Title', 'uni-calendar') ?>*</h5>
                    <input id="uni_input_title" type="text" placeholder="<?php esc_html_e('Event title', 'uni-calendar') ?>" value="<%= calEvent.title %>" name="uni_input_title" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                    <h5><?php esc_html_e('Description', 'uni-calendar') ?>*</h5>
                    <p><?php echo sprintf( esc_html__('Allowed HTML tags are: %1$s. Your text will be auto wrapped with %2$s tag. %3$s tag will be added in place of each line break. Pieces of the text divided by an empty line will be wrapped with %2$s tag.', 'uni-calendar'), '<code>'.$sAllowedHtmlDesc.'</code>', '<code>p</code>', '<code>br</code>' ); ?></p>
                    <textarea id="uni_input_event_desc" name="uni_input_event_desc" placeholder="<?php esc_html_e('Event description', 'uni-calendar') ?>" class="uni-ec-validation-activated" data-parsley-required="true" data-parsley-trigger="change focusout submit"><%= calEvent.meta.event_desc %></textarea>

                    <% if ( calView.type === 'month' || calView.type === 'basicWeek' || calView.type === 'basicDay' || calView.type === 'listDay' || calView.type === 'listWeek' || calView.type === 'listMonth' || calView.type === 'listYear' ) { %>
                    <h5><?php esc_html_e('All day event?', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Only all day events can be two or more days long.', 'uni-calendar') ?></p>
                    <select name="uni_input_event_all_day_enable" id="uni_input_event_all_day_enable" class="uni-ec-validation-activated" data-constrainer="yes">
                        <option value="no"<% if ( calEvent.meta.event_all_day_enable && calEvent.meta.event_all_day_enable === 'no' ) print(' selected') %>><?php esc_html_e('No', 'uni-calendar') ?></option>
                        <option value="yes"<% if ( calEvent.meta.event_all_day_enable && calEvent.meta.event_all_day_enable === 'yes' ) print(' selected') %>><?php esc_html_e('Yes', 'uni-calendar') ?></option>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_all_day_enable" data-constvalue="no">
                        <p><?php esc_html_e('Only non all day events have start and end time.', 'uni-calendar') ?></p>

                        <% if ( calEvent.meta.event_manual_start_time ) { %>
                        <h5><?php esc_html_e('Start time of the event', 'uni-calendar') ?>*</h5>
                        <input id="uni_input_event_manual_start_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="<%= calEvent.meta.event_manual_start_time %>" name="uni_input_event_manual_start_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <% } else { %>
                        <h5><?php esc_html_e('Start time of the event', 'uni-calendar') ?>*</h5>
                        <input id="uni_input_event_manual_start_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="<%= moment.utc(calEvent.start._i).format(uniTimeFormat) %>" name="uni_input_event_manual_start_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <% } %>

                        <% if ( calEvent.meta.event_manual_end_time ) { %>
                        <h5><?php esc_html_e('End time of the event', 'uni-calendar') ?>*</h5>
                        <input id="uni_input_event_manual_end_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="<%= calEvent.meta.event_manual_end_time %>" name="uni_input_event_manual_end_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <% } else { %>
                        <h5><?php esc_html_e('End time of the event', 'uni-calendar') ?>*</h5>
                        <% if ( calEvent.end === null || calEvent.end == 'null' ) { %>
                        <input id="uni_input_event_manual_end_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="" name="uni_input_event_manual_end_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <% } else { %>
                        <input id="uni_input_event_manual_end_time" type="text" class="js-uni-ec-datepicker-time uni-ec-validation-activated" value="<%= moment.utc(calEvent.end._i).format(uniTimeFormat) %>" name="uni_input_event_manual_end_time" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                        <% } %>
                        <% } %>

                    </div>
                    <% } %>

                    <% if ( typeof calCats !== 'undefined' ) { %>
                    <h5><?php esc_html_e('Category', 'uni-calendar') ?></h5>
                    <select id="uni_input_event_cat" name="uni_input_event_cat" class="uni-ec-validation-activated">
                        <option value="0"><?php esc_html_e('No Category', 'uni-calendar') ?></option>
                        <% _.each(calCats, function (value, key, list) { %>
                            <option value="<%= key %>"<% if ( calEvent.meta.event_cat && calEvent.meta.event_cat == key ) print(' selected') %>><%= value %></option>
                        <% }); %>
                    </select>
                    <% } %>

                    <h5><?php esc_html_e('Users connected', 'uni-calendar') ?></h5>
                    <% if ( allowedUsers !== 'undefined' || allowedUsers.length > 0 ) { %>
                        <% _.each(allowedUsers, function (value, key, list) { %>
                        <input type="checkbox" value="<%= key %>" name="uni_input_event_user[]" id="uni_input_event_user_<%= key %>"<% if ( calEvent.meta.event_user && calEvent.meta.event_user.indexOf(key) !== -1 ) print(' checked') %> class="categories-checkbox uni-ec-validation-activated">
                        <label for="uni_input_event_user_<%= key %>"><%= value %></label>
                        <% }); %>
                    <% } else { %>
                        <p><?php esc_html_e('No user roles are selected in the Calendar settings.', 'uni-calendar') ?></p>
                    <% } %>

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal">

                    <legend><?php esc_html_e('Click behavior settings', 'uni-calendar') ?></legend>

                    <p><?php esc_html_e('The modal window with the event content will be shown on click by default. However, it is possible to change this default behavior.', 'uni-calendar') ?></p>

                    <h5><?php esc_html_e('Behavior', 'uni-calendar') ?></h5>
                    <select name="uni_input_event_click_behavior" id="uni_input_event_click_behavior" class="uni-ec-validation-activated" data-constrainer="yes">
                        <option value="modal"<% if ( calEvent.meta.event_click_behavior && calEvent.meta.event_click_behavior === 'modal' ) print(' selected') %>><?php esc_html_e('Open modal window (default)', 'uni-calendar') ?></option>
                        <option value="uri"<% if ( calEvent.meta.event_click_behavior && calEvent.meta.event_click_behavior === 'uri' ) print(' selected') %>><?php esc_html_e('Redirect to custom URI', 'uni-calendar') ?></option>
                        <option value="modal_uri"<% if ( calEvent.meta.event_click_behavior && calEvent.meta.event_click_behavior === 'modal_uri' ) print(' selected') %>><?php esc_html_e('Open modal window with a custom link', 'uni-calendar') ?></option>
                    <?php /*if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>
                        <option value="wc"><?php esc_html_e('Open modal window with a link to WC product', 'uni-calendar') ?></option>
                    <?php }*/ ?>
                    </select>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="uri">
                        <h5><?php esc_html_e('Define custom URI', 'uni-calendar') ?></h5>
                        <p><?php esc_html_e('The user will be redirected to this URI instead of openning the modal window on the click.', 'uni-calendar') ?></p>
                        <input id="uni_input_event_uri" type="text" class="uni-ec-validation-activated" value="<%= calEvent.meta.event_uri %>" name="uni_input_event_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>

                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="modal_uri">
                        <h5><?php esc_html_e('URI for the link', 'uni-calendar') ?></h5>
                        <input id="uni_input_event_link_uri" type="text" class="uni-ec-validation-activated" value="<%= calEvent.meta.event_link_uri %>" name="uni_input_event_link_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">

                        <h5><?php esc_html_e('Text for the link', 'uni-calendar') ?></h5>
                        <input id="uni_input_event_link_text" type="text" class="uni-ec-validation-activated" value="<%= calEvent.meta.event_link_text %>" name="uni_input_event_link_text" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>

                    <?php /*if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { ?>
                    <div class="uni-ec-form-subsection uni-ec-hidden" data-constrained="uni_input_event_click_behavior" data-constvalue="wc">
                        <p><?php esc_html_e('The modal window will be shown on click and will contain', 'uni-calendar') ?></p>
                        <input id="uni_input_event_uri" type="text" class="uni-ec-validation-activated" value="" name="uni_input_event_uri" data-parsley-required="true" data-parsley-trigger="change focusout submit">
                    </div>
                    <?php }*/ ?>

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal">

                    <legend><?php esc_html_e('Visual appearance', 'uni-calendar') ?></legend>

                    <% if ( calView.type === 'agendaDay' || calView.type === 'agendaWeek' ) { %>
                    <h5><?php esc_html_e('Background image', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Add/upload an image via WP media uploader. This setting holds an ID of a media attachment. Empty the field if no image is needed.', 'uni-calendar') ?></p>
                    <input type="button" class="js-uni-ec-image-upload" value="<?php esc_html_e('Add/Upload image', 'uni-calendar') ?>" />
                    <input id="uni_input_event_bg_image_id" type="text" class="js-uni-ec-image-upload-field uni-ec-validation-activated" value="<%= calEvent.meta.event_bg_image_id %>" name="uni_input_event_bg_image_id">
                    <% } %>

                    <h5><?php esc_html_e('Background colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets background colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_backgroundColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="<%= calEvent.meta.event_backgroundColor %>" name="uni_input_event_backgroundColor">

                    <h5><?php esc_html_e('Border colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets border colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_borderColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="<%= calEvent.meta.event_borderColor %>" name="uni_input_event_borderColor">

                    <h5><?php esc_html_e('Text colour', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Sets text colour for the event individually.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_textColor" type="text" class="js-uni-calendar-colour-field uni-ec-validation-activated" value="<%= calEvent.meta.event_textColor %>" name="uni_input_event_textColor">

                </fieldset>

                <fieldset class="uni-ec-form-section uni-ec-form-section-modal">

                    <legend><?php esc_html_e('Copying', 'uni-calendar') ?></legend>

                    <h5><?php esc_html_e('Make a copy', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Check this checkbox only if you want to create a copy of the event. Changes are made in fields above still will be saved, copy of the event will created then.', 'uni-calendar') ?></p>
                    <input type="checkbox" value="yes" name="uni_input_event_do_copy" id="uni_input_event_do_copy" class="categories-checkbox uni-ec-validation-activated uni-ec-single-checkbox">
                    <label for="uni_input_event_do_copy"><?php esc_html_e('Create a copy of this event', 'uni-calendar') ?></label>

                    <h5><?php esc_html_e('Date and time of a new event', 'uni-calendar') ?></h5>
                    <p><?php esc_html_e('Each day in the calendar will de shown starting from this time.', 'uni-calendar') ?></p>
                    <input id="uni_input_event_copy_to" type="text" class="js-uni-ec-datepicker-datetime uni-ec-validation-activated" value="" name="uni_input_event_copy_to">

                </fieldset>

                <div class="uni-ec-form-section uni-ec-form-section-modal uni-ec-form-section-with-btn">
                    <button class="js-uni-event-confirm-delete-btn btn" type="button"><?php esc_html_e('Delete', 'uni-calendar') ?></button>
                    <button class="js-uni-event-confirm-cancel-btn btn btn-warning" type="button"><?php esc_html_e('Cancel/Close', 'uni-calendar') ?></button>
                    <button class="js-uni-event-confirm-save-btn btn btn-success" type="button"><?php esc_html_e('Save changes', 'uni-calendar') ?></button>
                </div>
            </div>

        </div>
    </script>

    <!-- Event Info modal template for MindBodyOnine -->
    <script type="text/template" id="js-uni-calendar-info-event-mb-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof chosenTheme !== 'undefined' && typeof UniCalendar.data.calThemes[chosenTheme] !== 'undefined' ) { print(UniCalendar.data.calThemes[chosenTheme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
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

                <div class="uni-ec-form-section uni-ec-form-section-with-btn centered uni-ec-form-section-modal">
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>

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

                <div class="uni-ec-form-section uni-ec-form-section-with-btn centered uni-ec-form-section-modal">
                    <button class="js-uni-event-modal-close-btn btn" type="button"><?php esc_html_e('Close', 'uni-calendar') ?></button>
                </div>

            </div>

        </div>
    </script>

    <!-- Event Info modal template for Tickera events -->
    <script type="text/template" id="js-uni-calendar-info-event-tickera-tmpl">
        <div class="uni-ec-universal-container" style="border-color:<%= calEvent.borderColor %>;">
            <div class="uni-ec-bar uni-ec-title-bar <% if ( typeof chosenTheme !== 'undefined' && typeof UniCalendar.data.calThemes[chosenTheme] !== 'undefined' ) { print(UniCalendar.data.calThemes[chosenTheme]['class_name']); } else { print('uni-ec-theme-flat-cyan'); } %>" style="background-color:<%= calEvent.backgroundColor %>;">
                <h3 style="color:<%= calEvent.textColor %>;"><%= calEvent.title %></h3>
            </div>

            <div class="uni-ec-form-content">

                <div class="uni-ec-form-section uni-ec-form-section-modal uni-ec-form-section-nice-scroll">

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
add_action('admin_footer', 'uni_ec_underscore_templates_admin');

//
function uni_ec_calendars_settings_page() {
        ?>

        <div id="uni-calendar-wrapper" class="wrap">
            <div id="icon-tools" class="icon32"></div>

            <h2><?php esc_html_e( 'Calendarius Plugin Settings', 'uni-calendar' ) ?></h2>

            <form method="post" action="options.php">
                <?php settings_fields( 'uni-calendar-settings-group' ); ?>
                <?php do_settings_sections( 'uni-calendar-settings-group' ); ?>

                <h3><?php esc_html_e('General settings', 'uni-calendar') ?></h3>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('12/24 hours format', 'uni-avatar') ?>
                        </th>
                        <td>
                            <select name="uni_calendar_time_format">
                                <option value="12"<?php echo selected( get_option('uni_calendar_time_format'), '12' ); ?>>12 hours</option>
                                <option value="24"<?php echo selected( get_option('uni_calendar_time_format'), '24' ); ?>>24 hours</option>
                            </select>
                            <p class="description"><?php esc_html_e('All the time pickers in admin area will work in the chosen format as well as all the time outputs the formats of which are not set manually will also be shown in the format chosen in this setting.', 'uni-calendar') ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Enable auto transfer of events?', 'uni-avatar') ?>
                        </th>
                        <td>
                            <input type="checkbox" name="uni_calendar_enable_auto_transfer" value="1"<?php echo checked( get_option('uni_calendar_enable_auto_transfer'), 1 ); ?> />
                            <p class="description"><?php esc_html_e('Check this option to enable auto transfer of events from current period to the next period. Auto transfering happens on per week basis.', 'uni-calendar') ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e('Choose day of auto transfer', 'uni-avatar') ?>
                        </th>
                        <td>
                            <select name="uni_calendar_day_of_auto_transfer">
                                <option value="1"<?php selected('1', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Monday', 'uni-calendar') ?></option>
                                <option value="2"<?php selected('2', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Tuesday', 'uni-calendar') ?></option>
                                <option value="3"<?php selected('3', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Wednesday', 'uni-calendar') ?></option>
                                <option value="4"<?php selected('4', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Thursday', 'uni-calendar') ?></option>
                                <option value="5"<?php selected('5', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Friday', 'uni-calendar') ?></option>
                                <option value="6"<?php selected('6', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Saturday', 'uni-calendar') ?></option>
                                <option value="7"<?php selected('7', get_option('uni_calendar_day_of_auto_transfer')) ?>><?php esc_html_e('Sunday', 'uni-calendar') ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('All events of current week wil be transfered to the next week on this day. <strong>Important: disable auto transfer option first if you change the day and auto transfer was enabled. It ensures that the related wp-cron job will be executed as it should be.</strong>', 'uni-calendar') ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>

            </form>

            <?php
            $now = time();
            $cron_array = _get_cron_array();

            $aCronJob = array();
            foreach ( $cron_array as $timestamp => $jobs ) {
        		foreach ( $jobs as $hook => $events ) {
                    if ( $hook === 'uni_calendar_transfer_events_hook' ) {
            			foreach ( $events as $key => $event ) {
            				$aCronJob['name']        = $hook;
            				$aCronJob['timestamp']   = $timestamp;
            				$aCronJob['seconds_due'] = $timestamp - $now;
            			}
                    }
        		}
        	}
            $iCurrentWeekNumber = absint( date('W') );
            $iCurrentYearNumber = absint( date('Y') );
            if ( get_transient('uni_calendars_auto_transfered_w_'.$iCurrentWeekNumber.'_y_'.$iCurrentYearNumber) ) {
                echo sprintf( esc_html__('Events of the current week shold have already been transferred (Current week number is "%s").', 'uni-calendar'), $iCurrentWeekNumber );
                echo '<br>';
            } else {
                echo sprintf( esc_html__('Events of the current week have not been transferred yet (Current week number is "%s").', 'uni-calendar'), $iCurrentWeekNumber );
                echo '<br>';
            }
            if ( !empty($aCronJob) ) {
                echo sprintf( esc_html__('"%s" cron job is scheduled (or was executed) at %s (now is: %s).', 'uni-calendar'), $aCronJob['name'], gmdate('r', $aCronJob['timestamp']), gmdate('r') );
            }
            ?>

        </div>
        <?php
}

//
function uni_ec_save_event_post_meta( $aExceptions, $aSettings, $iPostId ){
    // excludes data
    foreach ( $aExceptions as $sException ) {
        if ( !isset($aSettings[$sException]) ) {
            unset($aSettings[$sException]);
        }
    }

    // saves data
    foreach ( $aSettings as $key => $value ) {
        $sNewKey = str_replace("uni_input_", "_uni_ec_", $key);
        if ( $sNewKey === '_uni_ec_event_timestamp' ) {
            $iTimestamp = strtotime($value);
            update_post_meta($iPostId, $sNewKey, $iTimestamp);
            update_post_meta($iPostId, '_uni_ec_event_timestamp_end', $iTimestamp + 3600); // creates an event one hour long
        } else if ( $sNewKey === '_uni_ec_event_cat' ) {
            if ( !empty($value) ) {
                $iEventCatId = intval($value);
                wp_set_object_terms( $iPostId, $iEventCatId, 'uni_calendar_event_cat', false);
	            clean_object_term_cache( $iPostId, 'uni_calendar_event_cat' );
            }
        } else {
            update_post_meta($iPostId, $sNewKey, $value);
        }
    }
}

//
function uni_ec_current_week_date_range( $bReadableFormat = false ){
    $monday = strtotime("last monday");
    $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;

    $sunday = strtotime(date("Y-m-d",$monday)." +6 days");

    $aDates = array();
    if ( $bReadableFormat ) {
        $aDates['start'] = date("Y-m-d H:00:01",$monday);
        $aDates['end'] = date("Y-m-d 23:59:59",$sunday);
    } else {
        $aDates['start'] = strtotime(date("Y-m-d H:00:01",$monday));
        $aDates['end'] = strtotime(date("Y-m-d 23:59:59",$sunday));
    }

    return $aDates;
}

//
function uni_ec_allowed_html_with_a() {
    $aAllowedHtml = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'target' => array()
        ),
        'br' => array(),
        'em' => array(),
        'span' => array(
            'class' => array(),
            'style' => array()
        ),
        'strong' => array(),
        'p' => array()
    );
    return $aAllowedHtml;
}

//
function uni_ec_strip_tags_deep( $uArray ) {
    // exceptions - use wp_kses for values of these keys
    $aExceptions = array('uni_input_event_desc');
    //
    $aAllowedHtml = uni_ec_allowed_html_with_a();

    if ( is_array($uArray) ) {
        foreach ( $uArray as $Key => $Value ) {
            if ( is_array($Value) ) {
                foreach ( $Value as $ChildKey => $ChildValue ) {
                    if ( in_array( $ChildKey, $aExceptions ) ) { // exceptions
                        $uArray[$Key][$ChildKey] = wp_kses( $ChildValue, $aAllowedHtml );
                    } else {
                        $uArray[$Key][$ChildKey] = stripslashes_deep( strip_tags( $ChildValue ) );
                    }
                }
            } else {
                if ( in_array( $Key, $aExceptions ) ) { // exceptions
                    $uArray[$Key] = wp_kses( $Value, $aAllowedHtml );
                } else {
                    $uArray[$Key] = stripslashes_deep( strip_tags( $Value ) );
                }
            }
        }
    } else {
        $uArray = stripslashes_deep( strip_tags( $uArray ) );
    }

    return $uArray;
}

//
function uni_ec_list_pluck( $list, $field ){
    foreach ( $list as $key => $value ) {
        if ( is_object( $value ) ) {
            $list[ $key ] = $value->$field;
        } else if ( isset($value[ $field ]) ) {
            $list[ $key ] = $value[ $field ];
        } else {
            unset($list[ $key ]);
        }
    }
    return $list;
}

//
if ( !function_exists('uni_is_positive') ) {
    function uni_is_positive( $iNumber ) {
        return is_int( $iNumber ) && ( $iNumber >= 0 );
    }
}

//
if ( !function_exists('uni_is_valid_timestamp') ) {
    function uni_is_valid_timestamp( $timestamp ) {
        return ((string) (int) $timestamp === $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }
}

//
function uni_ec_mb_services_iteration($n){
    $start = 1+strpos($n, ' ');
	$end = strpos($n, '(');
	$length = $end - $start;
	return substr($n, $start, $length);
}
?>