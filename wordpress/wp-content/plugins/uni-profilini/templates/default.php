<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$wrapper = "
	background-color:#fff;
	width:100%;
    height:100%;
	-webkit-text-size-adjust:none !important;
	margin:0;
	padding: 0;
";

$profilini_user_id = ( isset($_GET['uni_profilini_user']) ) ? intval($_GET['uni_profilini_user']) : 0;
?>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_bloginfo('charset');?>" />
        <title><?php echo get_bloginfo('name'); ?></title>
	</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    	<div id="body" style="<?php echo $wrapper; ?>">
            <?php if ( ! empty($profilini_user_id) ) {
                $attach_id = get_user_meta($profilini_user_id, '_uni_profilini_avatar_id', true);
                echo wp_get_attachment_image( $attach_id, 'full' );
            } else {
                esc_html_e( 'No avatar added yet!', 'uni-profilini' );
            } ?>
        </div>
        <?php if( is_customize_preview() ) { wp_footer(); } ?>
    </body>
</html>