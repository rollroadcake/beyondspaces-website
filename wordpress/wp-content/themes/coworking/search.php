<?php get_header();
// with sidebar
if ( ot_get_option( 'uni_blog_archive_with_sidebar' ) == 'on' ) {
?>
	<section class="uni-container">

        <h1 class="blockTitle"><?php printf( esc_html__( 'Search results for: "%s"', 'coworking' ), get_search_query() ); ?></h1>

		<div class="wrapper uni-clear">
			<div class="contentLeft">
            <?php if (have_posts()) : while (have_posts()) : the_post();
                $sAdditionalPostClasses = 'postItemV2 uni-clear uni-no-featured-image';
                if ( has_post_thumbnail() ) {
                    $sAdditionalPostClasses = 'postItemV2 uni-clear';
                }
            ?>
    			<div id="post-<?php the_ID(); ?>" <?php post_class( $sAdditionalPostClasses ) ?>>
                    <?php if ( has_post_thumbnail() ) { ?>
    				<a href="<?php the_permalink() ?>" class="postItemV2Img">
                        <?php the_post_thumbnail( 'unithumb-coworking-blogpostv2', array( 'alt' => the_title_attribute('echo=0') ) ); ?>
    				</a>
                    <?php } ?>
					<div class="postItemV2Content">
						<div class="postItemMeta">
							<time datetime="<?php the_time('Y-m-d'); ?>"><?php the_time( get_option( 'date_format' ) ); ?></time>,
                            <?php
                            $aTags = wp_get_post_terms( $post->ID, 'category' );
                            if ( $aTags && !is_wp_error( $aTags ) ) :
                            $s = count($aTags);
                            $i = 1;
                            foreach ( $aTags as $oTerm ) {
                                echo '<a href="'.esc_url( get_term_link( $oTerm->slug, 'category' ) ).'" class="postItemCategory">'.esc_html( $oTerm->name ).'</a>';
                                if ($i < $s) echo ', ';
                                $i++;
                            }
                            endif;
                            ?>
						</div>
						<h3><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h3>
						<p><?php uni_coworking_theme_excerpt(32, '', true) ?></p>
					</div>
    			</div>
            <?php
            endwhile;
            else :
            ?>

                <?php get_template_part( 'no-results', 'archive' ); ?>

            <?php
            endif;
            ?>
			</div>

            <?php get_sidebar() ?>

		</div>

		<div class="pagination clear">
	    <?php
        $big = 999999999;
		echo paginate_links( array(
			'base'         => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'       => '?paged=%#%',
			'add_args'     => '',
			'current'      => max( 1, get_query_var( 'paged' ) ),
			'total'        => $wp_query->max_num_pages,
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

	</section>
<?php
// without sidebar
} else {
?>
	<section class="uni-container">

        <h1 class="blockTitle"><?php printf( esc_html__( 'Search results for: "%s"', 'coworking' ), get_search_query() ); ?></h1>

		<div class="blogWrap">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class('postItem') ?>>
				<a href="<?php the_permalink() ?>" class="postItemImg">
                <?php if ( has_post_thumbnail() ) { ?>
                    <?php the_post_thumbnail( 'unithumb-coworking-relativepost', array( 'alt' => the_title_attribute('echo=0') ) ); ?>
                <?php } else { ?>
				    <img src="https://via.placeholder.com/370x250/5FC7AE/FFFFFF" alt="<?php the_title_attribute() ?>" width="370" height="250">
                <?php } ?>
				</a>
				<div class="postItemMeta">
					<time datetime="<?php the_time('Y-m-d'); ?>"><?php the_time( get_option( 'date_format' ) ); ?></time>,
                <?php
                $aTags = wp_get_post_terms( $post->ID, 'category' );
                if ( $aTags && !is_wp_error( $aTags ) ) :
                $s = count($aTags);
                $i = 1;
                foreach ( $aTags as $oTerm ) {
                    echo '<a href="'.esc_url( get_term_link( $oTerm->slug, 'category' ) ).'" class="postItemCategory">'.esc_html( $oTerm->name ).'</a>';
                    if ($i < $s) echo ', ';
                    $i++;
                }
                endif;
                ?>
				</div>
				<h3><a href="<?php the_permalink() ?>"><?php the_title() ?></a></h3>
				<p><?php uni_coworking_theme_excerpt(12, '', true) ?></p>
			</div>
        <?php
        endwhile;
        else :
        ?>

            <?php get_template_part( 'no-results', 'archive' ); ?>

        <?php
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
			'total'        => $wp_query->max_num_pages,
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

	</section>
<?php
}
get_footer(); ?>