<?php
/*
Plugin Name: CC Video Lazy Loader
Description: Replace videos with image placeholders. Play on click.
Version: 1.0.0
Author: David Cavins
Licence: GPLv3
*/

/**
 * Creates instance of CC_Video_Lazy_Loader
 * This is where most of the running gears are.
 *
 * @package CC Video Lazy Loader
 * @since 1.0.0
 */

function cc_video_lazy_loader_class_init(){
	// Get the class fired up
	require( dirname( __FILE__ ) . '/class-cc-video-lazy-loader.php' );
	add_action( 'init', array( 'CC_Video_Lazy_Loader', 'get_instance' ), 12 );
}
add_action( 'init', 'cc_video_lazy_loader_class_init' );