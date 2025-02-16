<?php
/**
 * Single Product Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-thumbnails.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $product;

$attachment_ids = $product->get_gallery_image_ids();

if ( !has_post_thumbnail() && !$attachment_ids ) {
?>

						<div class="galleryThumb">
							<a data-slide-index="0" href="#imgId1" class="galleryThumbItem">
								<?php echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'coworking' ) ), $post->ID ); ?>
							</a>
						</div>
						<div class="productGalleryWrap">
                            <?php echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<img class="current" src="%s" alt="%s" />', wc_placeholder_img_src(), __( 'Placeholder', 'coworking' ) ), $post->ID ); ?>
						</div>

<?php
} else if ( has_post_thumbnail() && !$attachment_ids ) {
			$image_title 	= esc_attr( get_the_title( get_post_thumbnail_id() ) );
			$image_caption 	= get_post( get_post_thumbnail_id() )->post_excerpt;
			$image_link  	= esc_url( wp_get_attachment_url( get_post_thumbnail_id() ) );
			$image       	= get_the_post_thumbnail( $post->ID, 'shop_thumbnail', array(
				'title'	=> $image_title,
				'alt'	=> $image_title
				) );
                    $aImage = wp_get_attachment_image_src( get_post_thumbnail_id(), 'shop_single' );
?>

						<div class="galleryThumb">
							<a data-slide-index="0" href="#imgId1" class="galleryThumbItem">
								<?php echo wp_kses_post( $image ); ?>
							</a>
						</div>
						<div class="productGalleryWrap">
							<img class="current" id="imgId1" src="<?php echo esc_url( $aImage[0] ); ?>" alt="<?php echo esc_attr( $image_title ); ?>" width="540" height="540">
						</div>

<?php
} else if ( $attachment_ids ) {
    $i = $l = 0;
?>

						<div class="galleryThumb">
							<ul>
                <?php foreach ( $attachment_ids as $attachment_id ) {
                    $aImageThumb = wp_get_attachment_image_src( $attachment_id, 'shop_thumbnail' );
                    $image_title = esc_attr( get_the_title( $attachment_id ) );
                ?>
							<li data-slide="<?php echo esc_attr( $i ); ?>">
								<a class="galleryThumbItem<?php if ( $i == 0 ) echo ' active'; ?>" href="#">
									<img src="<?php echo esc_url( $aImageThumb[0] ) ?>" alt="<?php echo esc_attr( $image_title ); ?>" width="118" height="118">
								</a>
							</li>
				<?php $i++; } ?>
							</ul>
						</div>
						<div class="productGalleryWrap">
                            <ul>
                <?php foreach ( $attachment_ids as $attachment_id ) {
                    $l++;
                    $aImage = wp_get_attachment_image_src( $attachment_id, 'shop_single' );
                    $image_title = esc_attr( get_the_title( $attachment_id ) );
                ?>
							    <li><img src="<?php echo esc_url( $aImage[0] ); ?>" alt="<?php echo esc_attr( $image_title ); ?>" width="540" height="540"></li>
				<?php } ?>
                            </ul>
						</div>

<?php } ?>