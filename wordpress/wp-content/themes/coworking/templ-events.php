<?php
/*
*  Template Name: Events Page
*/
get_header();
$sDateFormat = get_option( 'date_format' );
$sTimeFormat = get_option( 'time_format' );
?>

    <?php if (have_posts()) : while (have_posts()) : the_post();
		$aPostCustom = get_post_custom( $post->ID );
    ?>
    <?php
        // background
        if ( !empty($aPostCustom['uni_events_page_header_bg'][0]) ) {
            $iHeaderImageAttachId = intval($aPostCustom['uni_events_page_header_bg'][0]);
            $aPageHeaderImage = wp_get_attachment_image_src( $iHeaderImageAttachId, 'full' );
            $sPageHeaderImage = $aPageHeaderImage[0];
        } else {
            $sPageHeaderImage = 'https://via.placeholder.com/1920x600';
        }
    ?>

	<section class="uni-container">
		<div class="pageHeaderImg" style="background-image: url(<?php echo esc_url( $sPageHeaderImage ); ?>);">
			<h1><?php the_title() ?></h1>
		</div>
		<div class="events">
			<div class="eventsWrap">

                <?php
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'newer' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'order' => 'DESC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'paged' => $paged
                    );
                } else if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'older' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'order' => 'ASC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'paged' => $paged
                    );
                } else if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'new_only_newer' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'post_status' => 'publish',
                        'order' => 'DESC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'meta_query' => array(
                            		array(
                            			'key'     => 'uni_event_date_start',
                            			'value'   => date('Y-m-d', time()),
                            			'compare' => '>=',
                                        'type' => 'DATE',
                            		),
                        ),
                        'paged' => $paged
                    );
                } else if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'new_only_older' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'post_status' => 'publish',
                        'order' => 'ASC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'meta_query' => array(
                            		array(
                            			'key'     => 'uni_event_date_start',
                            			'value'   => date('Y-m-d', time()),
                            			'compare' => '>=',
                                        'type' => 'DATE',
                            		),
                        ),
                        'paged' => $paged
                    );
                } else if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'past_only_newer' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'post_status' => 'publish',
                        'order' => 'DESC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'meta_query' => array(
                            		array(
                            			'key'     => 'uni_event_date_start',
                            			'value'   => date('Y-m-d', time()),
                            			'compare' => '<=',
                                        'type' => 'DATE',
                            		),
                        ),
                        'paged' => $paged
                    );
                } else if ( isset($aPostCustom['uni_events_page_filter'][0]) && $aPostCustom['uni_events_page_filter'][0] == 'past_only_older' ) {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'post_status' => 'publish',
                        'order' => 'ASC',
                        'orderby' => 'meta_value',
                        'meta_key' => 'uni_event_date_start',
                        'meta_type' => 'DATE',
                        'meta_query' => array(
                            		array(
                            			'key'     => 'uni_event_date_start',
                            			'value'   => date('Y-m-d', time()),
                            			'compare' => '<=',
                                        'type' => 'DATE',
                            		),
                        ),
                        'paged' => $paged
                    );
                } else {
                    $aEventsArgs = array(
                        'post_type' => 'uni_event',
                        'paged' => $paged
                    );
                }

                $oEventsQuery = new wp_query( $aEventsArgs );
                if ( $oEventsQuery->have_posts() ) :
                $i = 0;
                while ( $oEventsQuery->have_posts() ) : $oEventsQuery->the_post();
                    $aCustomData = get_post_custom( $post->ID );
                    if ( !empty($aCustomData['uni_event_events_page_bg'][0]) ) {
                        $aEventBg = wp_get_attachment_image_src( get_post_meta($post->ID, 'uni_event_events_page_bg', true), 'full' );
                        $sEventBgUrl = $aEventBg[0];
                    } else {
                        $sEventBgUrl = 'https://via.placeholder.com/1920x600';
                    }
                ?>
				<div id="post-<?php the_ID(); ?>" <?php post_class('eventItem eventItemSimply') ?> style="background-image: url(<?php  echo esc_url( $sEventBgUrl ); ?>);">
                    <div class="eventWhiteOverlay"></div>
					<div class="eventTime">
                            <strong><?php
                                if ( !empty($aCustomData['uni_event_date_start'][0]) && !empty($aCustomData['uni_event_date_end'][0])
                                        && $aCustomData['uni_event_date_start'][0] == $aCustomData['uni_event_date_end'][0] ) {

                                        $iEventDatestamp = strtotime($aCustomData['uni_event_date_start'][0]);
                                        echo date_i18n($sDateFormat, $iEventDatestamp);
                                } else if ( !empty($aCustomData['uni_event_date_start'][0]) && !empty($aCustomData['uni_event_date_end'][0])
                                        && $aCustomData['uni_event_date_start'][0] != $aCustomData['uni_event_date_end'][0] ) {

                                        $iDateStartTimestamp = strtotime($aCustomData['uni_event_date_start'][0]);
                                        $iDateEndTimestamp = strtotime($aCustomData['uni_event_date_end'][0]);
                                        echo sprintf( esc_html__('%1$s&#035;%2$s', 'coworking'), date_i18n($sDateFormat, $iDateStartTimestamp), date_i18n($sDateFormat, $iDateEndTimestamp));
                                } else if ( !empty($aCustomData['uni_event_date_start'][0]) && empty($aCustomData['uni_event_date_end'][0]) )  {

                                        $iDateStartTimestamp = strtotime($aCustomData['uni_event_date_start'][0]);
                                        echo date_i18n($sDateFormat, $iDateStartTimestamp);
                                } else {
                                    esc_html_e('- not specified -', 'coworking');
                                } ?>
                            </strong>
                            <p>
                                <?php if ( !empty($aCustomData['uni_event_time_start'][0]) ) { $iEventTimeStartstamp = strtotime($aCustomData['uni_event_time_start'][0]); echo date_i18n($sTimeFormat, $iEventTimeStartstamp); } ?>
                                <?php if ( !empty($aCustomData['uni_event_time_end'][0]) ) { $iEventTimeEndstamp = strtotime($aCustomData['uni_event_time_end'][0]); echo ' &ndash; ' . date_i18n($sTimeFormat, $iEventTimeEndstamp); } ?>
                            </p>
					</div>
					<h3><?php echo the_title() ?></h3>
					<a href="<?php echo the_permalink() ?>" class="attendBtn">
                        <?php echo ( ot_get_option( 'uni_home_events_event_link_text' ) ) ? esc_html( ot_get_option( 'uni_home_events_event_link_text' ) ) : esc_html__('attend', 'coworking'); ?>
						<svg version="1.1" id="Layer_<?php echo esc_attr($i); ?>" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
							 width="19px" height="9px" viewBox="0 0 19 9" enable-background="new 0 0 19 9" xml:space="preserve">
						<path fill="#39B87E" d="M18.744,4.242L18.997,4.5l-0.253,0.258V5.03h-0.27L14.578,9l-0.736-0.749l3.16-3.221H-0.003V3.97h17.007
							l-3.162-3.22L14.578,0l3.682,3.75l0,0l0.217,0.22h0.268V4.242z"/>
						</svg>
					</a>
				</div>
                <?php
                $i++;
                endwhile;
                endif;
                ?>

			</div>

		    <div class="pagination clear">
    	    <?php
            $big = 999999999;
    		echo paginate_links( array(
    			'base'         => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
    			'format'       => '?paged=%#%',
    			'add_args'     => '',
    			'current'      => max( 1, get_query_var( 'paged' ) ),
    			'total'        => $oEventsQuery->max_num_pages,
    			'prev_text'    => '<i>
    								<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="&#1057;&#1083;&#1086;&#1081;_1" x="0px" y="0px" width="7px" height="11px" viewBox="0 0 7 11" enable-background="new 0 0 7 11" xml:space="preserve">
    									<path fill="#c3c3c3" class="paginationArrowIcon" d="M0.95 4.636L6.049 0L7 0.864L1.899 5.5L7 10.136L6.049 11L0 5.5L0.95 4.636z"/>
    								</svg>
    							</i>'.esc_html__('previous', 'coworking'),
    			'next_text'    => esc_html__('next', 'coworking').'<i>
    								<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="&#1057;&#1083;&#1086;&#1081;_1" x="0px" y="0px" width="7px" height="11px" viewBox="0 0 7 11" enable-background="new 0 0 7 11" xml:space="preserve">
    									<path fill="#c3c3c3" class="paginationArrowIcon" d="M6.05 6.364L0.951 11L0 10.136L5.102 5.5L0 0.864L0.951 0L7 5.5L6.05 6.364z"/>
    								</svg>
    							</i>',
    			'type'         => 'list',
    			'end_size'     => 3,
    			'mid_size'     => 3
    		) );
    	    ?>
		    </div>

		</div>

        <?php if ( get_the_content() ) : ?>
            <div class="wrapper">
                <div class="singlePostWrap singlePostWrap--space uni-clear">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endif; ?>
	</section>

    <?php
    endwhile; endif;
    wp_reset_postdata();
    ?>

<?php get_footer(); ?>