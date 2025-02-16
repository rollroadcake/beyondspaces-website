<?php
// list of all shortcodes


// Calendarius
add_shortcode( 'uni-calendar', 'uni_ec_calendar_display' );

//
function uni_ec_calendar_display( $atts, $content = null ) {
        $aAttr = shortcode_atts( array(
            'id' => null,
            'theme' => null,
            'view' => null
        ), $atts );

        if ( $aAttr['id'] != null ) {
	        ob_start();
            $sTemplatePath = apply_filters( 'uni_ec_calendar_shortcode_tmpl_path_filter', UniCalendar()->plugin_path().'/includes/views/single-calendar.php', $aAttr['id']);
	        include( $sTemplatePath );
	        return ob_get_clean();
        } else {
            return;
        }
}

// cobot
add_shortcode( 'uni-ec-cobot-space', 'uni_ec_cobot_display_space' );
add_shortcode( 'uni-ec-cobot-plans', 'uni_ec_cobot_display_plans' );
add_shortcode( 'uni-ec-cobot-resources', 'uni_ec_cobot_display_resources' );

//
function uni_ec_cobot_get_space_by_cal( $iCalId, $sSpaceSubdomain ) {
        $Api = new Uni_Ec_Api_cobot( $iCalId );
        if ( empty($sSpaceSubdomain) && !empty($Api->aSpaceInfo) ) {
            return $Api->aSpaceInfo;
        } else {
            $aResult = $Api->get_space( $sSpaceSubdomain );
            if ( $aResult['status'] === 'success' ) {
                if ( is_object($aResult['response']) ) {
                    foreach ( $aResult['response'] as $sKey => $Value ) {
                        $aData[$sKey] = $Value;
                    }
                } else {
                    $aData = $aResult['response'];
                }
                return $aData;
            } else {
                return array();
            }
        }
}
//
function uni_ec_cobot_display_space( $atts, $content = null ) {
        $aAttr = shortcode_atts( array(
            'id' => null,
            'subdomain' => null,
            'theme' => null
        ), $atts );

        if ( $aAttr['id'] !== null ) {
            $aData = uni_ec_cobot_get_space_by_cal( $aAttr['id'], $aAttr['subdomain'] );
            if ( !empty($aData) ) {
                ob_start();
                $sTemplatePath = apply_filters( 'uni_ec_cobot_space_shortcode_tmpl_path_filter', UniCalendar()->plugin_path().'/includes/views/shortcodes/uni-ec-cobot-space.php', $aAttr['id'] );
    	        include( $sTemplatePath );
    	        return ob_get_clean();
            } else {
                return;
            }
        } else {
            return;
        }
}

//
function uni_ec_cobot_get_plans_by_cal( $iCalId, $sSpaceSubdomain ) {
        $Api = new Uni_Ec_Api_cobot( $iCalId );
        $aResult = $Api->get_plans( $sSpaceSubdomain );
        if ( empty($sSpaceSubdomain) && !empty($Api->aSpaceInfo) ) {
            return $Api->aSpaceInfo['plans'];
        } else {
            $aResult = $Api->get_plans( $sSpaceSubdomain );
            if ( $aResult['status'] === 'success' ) {
                return $aResult['response'];
            } else {
                return array();
            }
        }
}
//
function uni_ec_cobot_display_plans( $atts, $content = null ) {
        $aAttr = shortcode_atts( array(
            'id' => null,
            'subdomain' => null,
            'theme' => null
        ), $atts );

        if ( $aAttr['id'] !== null ) {
            $aData = uni_ec_cobot_get_plans_by_cal( $aAttr['id'], $aAttr['subdomain'] );
            if ( !empty($aData) ) {
                $aSpaceData = uni_ec_cobot_get_space_by_cal( $aAttr['id'], $aAttr['subdomain'] );
                $sPriceDisplayType = $aSpaceData['price_display'];
                ob_start();
                $sTemplatePath = apply_filters( 'uni_ec_cobot_plans_shortcode_tmpl_path_filter', UniCalendar()->plugin_path().'/includes/views/shortcodes/uni-ec-cobot-plans.php', $aAttr['id'] );
    	        include( $sTemplatePath );
    	        return ob_get_clean();
            } else {
                return;
            }
        } else {
            return;
        }
}

//
function uni_ec_cobot_get_resources_by_cal( $iCalId ) {
        $Api = new Uni_Ec_Api_cobot( $iCalId );
        $aResult = $Api->get_resources();
        if ( !empty($Api->aSpaceInfo) ) {
            return $Api->aSpaceInfo['resources'];
        } else {
            $aResult = $Api->get_resources();
            if ( $aResult['status'] === 'success' ) {
                return $aResult['response'];
            } else {
                return array();
            }
        }
        return $aResult;
}
//
function uni_ec_cobot_display_resources( $atts, $content = null ) {
        $aAttr = shortcode_atts( array(
            'id' => null,
            'theme' => null
        ), $atts );

        if ( $aAttr['id'] !== null ) {
            $aData = uni_ec_cobot_get_resources_by_cal( $aAttr['id'] );
            if ( !empty($aData) ) {
                ob_start();
                $sTemplatePath = apply_filters( 'uni_ec_cobot_resources_shortcode_tmpl_path_filter', UniCalendar()->plugin_path().'/includes/views/shortcodes/uni-ec-cobot-resources.php', $aAttr['id'] );
    	        include( $sTemplatePath );
    	        return ob_get_clean();
            } else {
                return;
            }
        } else {
            return;
        }
}
?>