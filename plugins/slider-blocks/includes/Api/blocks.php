<?php
/**
 * Default block definitions for GutSlider.
 *
 * Returns the complete list of available slider blocks with their
 * metadata. This file is used by both the API and block registration
 * systems to determine which blocks are available.
 *
 * @package GutSlider\Api
 * @since   1.0.0
 *
 * @return array<int, array<string, mixed>> Array of block definition arrays.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'gutslider_blocks',
	array(
		array(
			'name'        => 'content-slider',
			'title'       => __( 'Static', 'slider-blocks' ),
			'description' => __( 'Create slider with static content like Image, text, button.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'static',
			'active'      => true,
		),
		array(
			'name'        => 'marquee',
			'title'       => __( 'Content Marquee', 'slider-blocks' ),
			'description' => __( 'An endlessly scrolling strip of any content: text, logos, images or testimonials.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'marquee',
			'active'      => true,
		),
		array(
			'name'        => 'any-content',
			'title'       => __( 'Flexible', 'slider-blocks' ),
			'description' => __( 'Create slider with any type of content.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'flexible',
			'active'      => true,
		),
		array(
			'name'        => 'testimonial-slider',
			'title'       => __( 'Testimonial', 'slider-blocks' ),
			'description' => __( 'Create amazing testimonial slider.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'testimonial',
			'active'      => true,
		),
		array(
			'name'        => 'photo-carousel',
			'title'       => __( 'Photo Carousel', 'slider-blocks' ),
			'description' => __( 'Create amazing photos carousel with lightbox.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'photo',
			'active'      => true,
		),
		array(
			'name'        => 'logo-carousel',
			'title'       => __( 'Brand Logos', 'slider-blocks' ),
			'description' => __( 'Create clients logos carousel with amazing styles.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'brand-logos',
			'active'      => true,
		),
		array(
			'name'        => 'before-after',
			'title'       => __( 'Before After', 'slider-blocks' ),
			'description' => __( 'Create photos comparision before after slider.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'before-after',
			'active'      => true,
		),
		array(
			'name'        => 'videos-carousel',
			'title'       => __( 'Videos', 'slider-blocks' ),
			'description' => __( 'Create videos carousel from video sharing platform e.g YouTube', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'videos',
			'active'      => true,
		),
		array(
			'name'        => 'post-slider',
			'title'       => __( 'Blog Post', 'slider-blocks' ),
			'description' => __( 'Create dynamic content slider or carousel using blog posts.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'post-slider',
			'active'      => true,
		),
		array(
			'name'        => 'backdrop-slider',
			'title'       => __( 'Backdrop', 'slider-blocks' ),
			'description' => __( 'One fixed background with static or dynamic content sliding over it.', 'slider-blocks' ),
			'is_pro'      => false,
			'demo_slug'   => 'backdrop',
			'active'      => true,
		),
		array(
			'name'        => 'shader-slider',
			'title'       => __( 'Shader', 'slider-blocks' ),
			'description' => __( 'Interactive shader slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'shader-slider',
			'active'      => true,
		),
		array(
			'name'        => 'shutters-slider',
			'title'       => __( 'Shutters', 'slider-blocks' ),
			'description' => __( 'Interactive shutters slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'shutters-slider',
			'active'      => true,
		),
		array(
			'name'        => 'shape-slider',
			'title'       => __( 'Shape Morph', 'slider-blocks' ),
			'description' => __( 'Hero slider that shows each image through a shape, which shrinks, travels and morphs between slides.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'xray-slider',
			'title'       => __( 'X-Ray', 'slider-blocks' ),
			'description' => __( 'Hero slider with a lens that follows the mouse and shows the next slide, then grows to reveal it.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'grid-reveal-slider',
			'title'       => __( 'Grid Reveal', 'slider-blocks' ),
			'description' => __( 'Hero slider that pulls back to a grid of all the slides between changes, with a grid view to jump to any slide.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'split-slider',
			'title'       => __( 'Split Transitions', 'slider-blocks' ),
			'description' => __( 'Hero slider whose next image arrives cut up (in sliding bands, repeating frames or puzzle pieces) and comes back together.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'card-pile-slider',
			'title'       => __( 'Card Pile', 'slider-blocks' ),
			'description' => __( 'Hero slider with piles of photo cards in the corners; the top card lifts off and grows into the next slide.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'gallery3d-slider',
			'title'       => __( '3D Gallery', 'slider-blocks' ),
			'description' => __( 'Hero slider whose slides sit on a 3D shape (a cube, a tube or a ring) that turns from one to the next.', 'slider-blocks' ),
			'is_pro'      => true,
			// no demo page yet; the dashboard hides the Demo link until there is one
			'demo_slug'   => '',
			'active'      => true,
		),
		array(
			'name'        => 'slicer-slider',
			'title'       => __( 'Slicer', 'slider-blocks' ),
			'description' => __( 'Interactive slicer slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'slicer-slider',
			'active'      => true,
		),
		array(
			'name'        => 'fashion-slider',
			'title'       => __( 'Fashion', 'slider-blocks' ),
			'description' => __( 'Interactive fashion slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'fashion-slider',
			'active'      => true,
		),
		array(
			'name'        => 'triple-slider',
			'title'       => __( 'Triple', 'slider-blocks' ),
			'description' => __( 'Interactive triple slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'triple-slider',
			'active'      => true,
		),
		array(
			'name'        => 'spring-carousel',
			'title'       => __( 'Spring Carousel', 'slider-blocks' ),
			'description' => __( 'Interactive spring carousel for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'spring-carousel',
			'active'      => true,
		),
		array(
			'name'        => 'panorama-carousel',
			'title'       => __( 'Panorama Carousel', 'slider-blocks' ),
			'description' => __( 'Interactive panorama carousel for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'panorama-carousel',
			'active'      => true,
		),
		array(
			'name'        => 'three-d-carousel',
			'title'       => __( '3D Carousel', 'slider-blocks' ),
			'description' => __( 'Interactive 3D carousel for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => '3d-carousel',
			'active'      => true,
		),
		array(
			'name'        => 'card-slider',
			'title'       => __( 'Card', 'slider-blocks' ),
			'description' => __( 'Interactive card slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'card-slider',
			'active'      => true,
		),
		array(
			'name'        => 'marquee-carousel',
			'title'       => __( 'Marquee Carousel', 'slider-blocks' ),
			'description' => __( 'A continuously looping carousel of image slides, built by hand or from your posts.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'marquee-carousel',
			'active'      => true,
		),
		array(
			'name'        => 'material-carousel',
			'title'       => __( 'Material Carousel', 'slider-blocks' ),
			'description' => __( 'Interactive material carousel for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'material',
			'active'      => true,
		),
		array(
			'name'        => 'tinder-slider',
			'title'       => __( 'Tinder', 'slider-blocks' ),
			'description' => __( 'Interactive tinder slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'tinder-slider',
			'active'      => true,
		),
		array(
			'name'        => 'hover-slider',
			'title'       => __( 'Hover', 'slider-blocks' ),
			'description' => __( 'Interactive hover slider for static and dynamic content.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'hover-slider',
			'active'      => true,
		),
		array(
			'name'        => 'product-carousel',
			'title'       => __( 'Woo Product Carousel', 'slider-blocks' ),
			'description' => __( 'Showcase WooCommerce products in a responsive carousel.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'product-carousel',
			'active'      => true,
		),
		array(
			'name'        => 'product-categories-carousel',
			'title'       => __( 'Woo Categories Carousel', 'slider-blocks' ),
			'description' => __( 'Showcase WooCommerce product categories in a responsive carousel.', 'slider-blocks' ),
			'is_pro'      => true,
			'demo_slug'   => 'product-categories-carousel',
			'active'      => true,
		),
	)
);
