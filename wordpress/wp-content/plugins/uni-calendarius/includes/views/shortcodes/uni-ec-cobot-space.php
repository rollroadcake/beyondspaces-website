<?php do_action('uni_ec_before_cobot_space_shortcode_action', $aAttr['id']);

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

<div class="uni-ec-cobot-space-shortcode-wrapper <?php echo $sThemeClass; ?>">
    <a href="<?php echo esc_url($aData['url']) ?>" class="uni-ec-space-url" target="_blank"><?php echo esc_html($aData['name']) ?></a>
    <div class="uni-ec-space-description">
        <?php echo $Parsedown->text($aData['description']); ?>
    </div>
    <?php if (
        ( isset($aData['address']->company) && !empty($aData['address']->company) )
        || ( isset($aData['address']->name) && !empty($aData['address']->name) )
        || ( isset($aData['address']->full_address) && !empty($aData['address']->full_address) )
    ) { ?>
    <div class="uni-ec-space-details">
        <?php if ( isset($aData['address']->company) && !empty($aData['address']->company) ) { ?>
        <span class="uni-ec-space-company"><?php echo esc_html($aData['address']->company) ?></span>
        <?php } ?>
        <?php if ( isset($aData['address']->name) && !empty($aData['address']->name) ) { ?>
        <span class="uni-ec-space-name"><?php echo esc_html($aData['address']->name) ?></span>
        <?php } ?>
        <?php if ( isset($aData['address']->full_address) && !empty($aData['address']->full_address) ) { ?>
        <span class="uni-ec-space-address"><?php echo esc_html($aData['address']->full_address) ?></span>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<?php do_action('uni_ec_after_cobot_space_shortcode_action', $aAttr['id']); ?>