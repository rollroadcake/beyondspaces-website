<?php do_action('uni_ec_before_calendar_action', $aAttr['id']);

$sChosenThemeSlug   = get_post_meta($aAttr['id'], '_uni_ec_cal_theme', true);
$sChosenView        = ( $aAttr['view'] !== null ) ? $aAttr['view'] : '';
$aThemes = UniCalendar()->get_calendars_themes();
if ( $aAttr['theme'] !== null ) {
    $sChosenThemeSlug = $aAttr['theme'];
    $sThemeClass = $aThemes[$sChosenThemeSlug]['class_name']; 
} else if ( $aAttr['theme'] === null && isset($sChosenThemeSlug) && !empty($sChosenThemeSlug) && isset($aThemes[$sChosenThemeSlug]) ) {
    $sThemeClass = $aThemes[$sChosenThemeSlug]['class_name'];
} else {
    $sThemeClass = $aThemes['flat_cyan']['class_name'];
}
?>

    <div id="uni-calendar-<?php echo esc_attr($aAttr['id']) ?>" class="uni-ec-main-wrapper uni-ec-shortcode-wrapper <?php echo esc_attr($sThemeClass); ?>" data-chosen-theme-class="<?php echo esc_attr($sThemeClass); ?>">
	    <div class="js-uni-calendars-container" data-cal_id="<?php echo esc_attr($aAttr['id']) ?>" data-chosen-theme="<?php echo esc_attr($sChosenThemeSlug); ?>" data-chosen-view="<?php echo esc_attr($sChosenView); ?>"></div>
	</div>

<?php do_action('uni_ec_after_calendar_action', $aAttr['id']); ?>