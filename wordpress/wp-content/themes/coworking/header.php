<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
        <link rel="profile" href="http://gmpg.org/xfn/11">
	    <?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	    <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	    <?php endif; ?>
        
        <!-- wp_header -->
        <?php wp_head(); ?>

	</head>
<body <?php body_class(); ?>>

<?php do_action( 'uni_coworking_theme_header' ); ?>