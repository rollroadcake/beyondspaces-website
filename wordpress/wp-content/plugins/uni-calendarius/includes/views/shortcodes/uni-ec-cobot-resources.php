<?php do_action('uni_ec_before_cobot_resources_shortcode_action', $aAttr['id']);

$sChosenThemeSlug = get_post_meta($aAttr['id'], '_uni_ec_cal_theme', true);
$aThemes = UniCalendar()->get_calendars_themes();
if ( $aAttr['theme'] !== null ) {
    $sChosenThemeSlug = $aAttr['theme'];
    $sThemeClass = $aThemes[$sChosenThemeSlug]['class_name'];
} else if ( $aAttr['theme'] === null && isset($sChosenThemeSlug) && !empty($sChosenThemeSlug) && isset($aThemes[$sChosenThemeSlug]) ) {
    $sThemeClass = $aThemes[$sChosenThemeSlug]['class_name'];
} else {
    $sThemeClass = $aThemes['flat_cyan']['class_name'];
}

$Parsedown = new Parsedown();
?>

<div class="uni-ec-cobot-resources-shortcode-wrapper <?php echo $sThemeClass; ?>">
    <?php foreach ( $aData as $oResource ) {
        $sPrice = number_format( $oResource->price_per_hour, 2, ',', '.' );
    ?>
    <div class="uni-ec-cobot-resource-wrapper<?php if ( !$oResource->can_book ) { echo ' resource-disabled'; } ?>">
        <div class="uni-ec-resource-price-wrapper">
            <span class="uni-ec-resource-price"><?php echo $sPrice; ?> <?php echo esc_html( $oResource->currency ); ?></span>
            <span class="uni-ec-resource-cycle">
                /<?php esc_html_e( 'hour', 'uni-calendar' ); ?>
            </span>
        </div>
        <h3><?php echo esc_html( $oResource->name ); ?></h3>
        <?php if ( isset($oResource->booking_times[0]) ) { ?>
        <div class="uni-ec-resource-booking-times">
            <span>
                <i class="fa fa-clock-o" aria-hidden="true" title="<?php echo esc_html_e( 'Available Booking Times', 'uni-calendar' ); ?>"></i>
                <?php echo $oResource->booking_times[0]->from ?> &mdash; <?php echo $oResource->booking_times[0]->to ?>
            </span>
        </div>
        <?php } ?>
        <div class="uni-ec-resource-description">
            <?php echo $Parsedown->text($oResource->description); ?>
        </div>
    </div>
    <?php } ?>
</div>

<?php do_action('uni_ec_after_cobot_resources_shortcode_action', $aAttr['id']); ?>