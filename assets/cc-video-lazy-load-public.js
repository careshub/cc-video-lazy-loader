(function ( $ ) {
	"use strict";

	// Go when the DOM is ready.
	$(function() {
		window.ccvll = window.ccvll || {}

		$( ".play-commons-video" ).each( function( index, element ) {
			fetch_video_poster_images( element );
		});

		$( ".play-commons-video" ).on( "click", function( event ) {
			event.preventDefault();
			var iframe_src    = $( this ).data( "lazy-iframe-src" ),
				video_host    = $( this ).data( "video-host" ),
				vid_container = $( this ).parents( '.video-container' ),
				video_id      = $( this ).data( "video-id" );

			$( this ).parent(".video-lazyload-placeholder").fadeOut( 'fast', function() {
				// Apply the iframe.
				vid_container.html( iframe_src );
				// Attach the Froogaloop event to this if Vimeo
				if ( 'vimeo' ==  video_host && typeof $f == 'function' ) {
					$( "#vimeo-" + video_id ).on( 'load', function() {
						Froogaloop( this ).addEvent( 'ready', ready );
					} );
				}
			});
		});

	});

	function fetch_video_poster_images( element ) {
		var video_host = $( element ).data( "video-host" ),
			video_id   = $( element ).data( "video-id" );

		if ( 'vimeo' == video_host ) {
			$.ajax({
				type:'GET',
				url: 'http://vimeo.com/api/v2/video/' + video_id + '.json',
				jsonp: 'callback',
				dataType: 'jsonp',
				success: function( data ){
					// Apply the video's poster image.
					$( element ).find( ".video-poster-placeholder" ).attr( "src", data[0].thumbnail_large );
				}
			});
		} else if ( 'youtube' == video_host ) {
			// YouTube's API is like work, now.
			// $.ajax({
			//     type:'GET',
			//     url: 'http://gdata.youtube.com/feeds/api/videos/' + video_id + '?v=2&alt=json',
			//     jsonp: 'callback',
			//     dataType: 'jsonp',
			//     success: function( data ){
			//     	console.table( data );
			//         // $( element ).find( ".video-poster-placeholder" ).attr( "src", data[0].thumbnail_large );
			//     }
			// });
			$( element ).find( ".video-poster-placeholder" ).attr( "src", "http://img.youtube.com/vi/" + video_id + "/0.jpg" );
		}
	}

	// KISS Tracking of Vimeo videos
	function ready( playerID ){

		// Add event listeners
		// http://developer.vimeo.com/player/js-api#events
		Froogaloop( playerID ).addEvent( "play", function(){
			var stringified = String( playerID ),
			videoName = jQuery( '#' + stringified ).attr( 'title' );
			// console.log( 'Recording Event: played for video with videoName: ' + videoName );
			_kmq.push(['record', 'Played Video', {'Played Video Name':videoName}]);
		} );
		Froogaloop( playerID ).addEvent( "pause", function() {
			var stringified = String( playerID ),
			videoName = jQuery( '#' + stringified ).attr( 'title' );
			// console.log( 'Recording Event: pause for video with videoName: ' + videoName );
			_kmq.push(['record', 'Paused Video', {'Paused Video Name':videoName}]);
		} );
		Froogaloop( playerID ).addEvent( "finish", function() {
			var stringified = String( playerID ),
			videoName = jQuery( '#' + stringified ).attr( 'title' );
			// console.log( 'Recording Event: pause for video with videoName: ' + videoName );
			_kmq.push(['record', 'Paused Video', {'Paused Video Name':videoName}]);
		} );
	}

}(jQuery));