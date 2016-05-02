<?php
/**
 * CC Video Lazy Loader
 *
 * @package   CC Video Lazy Loader
 * @author    David Cavins
 * @license   GPLv3
 * @copyright 2016 Community Commons
 */

/**
 * @package CC Video Lazy Loader
 * @author  David Cavins
 */
class CC_Video_Lazy_Loader {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-video-lazy-loader';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $attributes = array();

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 *
	 * Did we find video content on this page load?
	 *
	 * @since    1.0.0
	 *
	 * @var      bool
	 */
	protected $video_content_found = false;

	/**
	 * Initialize the plugin.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Change the oembed output for some videos.
		// 'oembed_result' is called when using wp_oembed_get, which we do in template files.
		add_filter( 'oembed_result', array( $this, 'filter_oembed_result' ), 30, 3 );

		// You can't do this because the results are then run through the "the_content" filter and get all messed up--added line breaks, all the quotes html-ized, etc.
		// add_filter( 'embed_oembed_html', array( $this, 'filter_embed_oembed_html' ), 30, 3, 4 );

		// Normal post and page content goes through 'the_content' filter after the embed stuff is created, so we can use that instead of fighting it. This is sad.
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 250 );

		// Add the scripts and styles if needed.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 99 );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Filter the HTML returned by the oEmbed provider.
	 * This is fired when a video in fetched using wp_oembed_get().
	 *
	 * @since 1.0.0
	 *
	 * @param string $data The returned oEmbed HTML.
	 * @param string $url  URL of the content to be embedded.
	 * @param array  $args Optional arguments, usually passed from a shortcode.
	 */
	public function filter_oembed_result( $data, $url, $args ) {
		return $this->build_custom_embed_html( $data, $url, $args );
	}

	/**
	 * Filter the HTML returned by the oEmbed provider.
	 * This is fired when a video in inserted in post content on its own line.
	 * Currently disabled.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data The returned oEmbed HTML.
	 * @param string $url  URL of the content to be embedded.
	 * @param array  $args Optional arguments, usually passed from a shortcode.
	 */
	public function filter_embed_oembed_html( $data, $url, $args, $post_ID ) {
		return $this->build_custom_embed_html( $data, $url, $args );
	}

	/**
	 * Create the custom embed code we're using.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data The returned oEmbed HTML.
	 * @param string $url  URL of the content to be embedded.
	 * @param array  $args Optional arguments, usually passed from a shortcode.
	 */
	public function build_custom_embed_html( $data, $url, $args ) {
		// Only run on vimeo and youtube videos.
		$is_youtube = false !== strpos( $data, 'youtube.com' );
		$is_vimeo = false !== strpos( $data, 'vimeo.com' );
		if ( ! is_admin() && ( $is_youtube || $is_vimeo ) ) {
			if ( $is_youtube ) {
				$video_id = $this->get_youtube_id_from_embed_url( $url );
				$video_host = 'youtube';
				$data = $this->remove_controls_from_youtube_vids( $data );
			} else {
				$video_id = $this->get_vimeo_id_from_embed_url( $url );
				$video_host = 'vimeo';
				$data = $this->add_api_details_to_vimeo_vids( $data, $video_id );

			}

			$data = '<figure class="via-oembed-filter video-container ' . $video_host . '"><div class="video-lazyload-placeholder hide-if-no-js"><a href="#" class="play-commons-video" data-video-host="' . $video_host . '" data-video-id="' . $video_id . '" data-lazy-iframe-src="' . esc_attr( $data ) . '"><span class="video-play-button-overlay"></span><img class="video-poster-placeholder" src="data:image/gif;base64,R0lGODdhAQABAPAAAP///wAAACwAAAAAAQABAEACAkQBADs=" style="margin-bottom:0;"></a></div></figure>';
			$this->video_content_found = true;
		}
		return $data;
	}

	/**
	 * Filter the content to replace video player iframes before output.
	 * This makes me sad.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data The returned oEmbed HTML.
	 * @param string $url  URL of the content to be embedded.
	 * @param array  $args Optional arguments, usually passed from a shortcode.
	 */
	public function filter_the_content( $content ) {
		$matches = array();
		preg_match_all( '|<iframe\s+.*?</iframe>|si', $content, $matches );

		$search = array();
		$replace = array();

		foreach ( $matches[0] as $iframe_code ) {

			$is_youtube = false !== strpos( $iframe_code, 'youtube.com' );
			$is_vimeo = false !== strpos( $iframe_code, 'vimeo.com' );

			// Only work on Youtube and Vimeo iframes
			if ( ! ( $is_youtube || $is_vimeo ) ) {
				continue;
			}

			$iframe_url = $this->get_src_from_iframe_code( $iframe_code );
			$video_id = $this->get_id_from_iframe_src( $iframe_url );

			if ( $is_youtube ) {
				$video_host = 'youtube';
				// $iframe_code = $this->remove_controls_from_youtube_vids( $iframe_code );
				$lazy_iframe_src = $iframe_code;
			} else {
				$video_host = 'vimeo';
				// $iframe_url = $iframe_url . '&player-id=' . $video_id;
				$lazy_iframe_src = $this->add_api_details_to_vimeo_vids( $iframe_code, $video_id );
			}

			$replace_item = '<figure class="via-content-filter video-container ' . $video_host . '">
				<div class="video-lazyload-placeholder hide-if-no-js">
					<a href="#" class="play-commons-video" data-video-host="' . $video_host . '" data-video-id="' . $video_id . '" data-lazy-iframe-src="' . esc_attr( $lazy_iframe_src ) . '">
						<span class="video-play-button-overlay"></span>
						<img class="video-poster-placeholder" src="data:image/gif;base64,R0lGODdhAQABAPAAAP///wAAACwAAAAAAQABAEACAkQBADs=" style="margin-bottom:0;">
					</a>
				</div>
			</figure>';

			$replace_item .= '<noscript>' . $lazy_iframe_src . '</noscript>';

			$search[] = $iframe_code;
			$replace[] = $replace_item;
		}

		$content = str_replace( $search, $replace, $content );

		return $content;
	}

	/**
	 * Add the scripts and styles if needed.
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function enqueue_styles_scripts() {
		wp_enqueue_script( $this->plugin_slug .'-js', plugins_url( 'assets/cc-video-lazy-load-public.js', __FILE__ ), array( 'jquery', 'froogaloop-js' ), self::VERSION, $in_footer = true );
		// Remote version has an issue.
		// wp_enqueue_script( 'froogaloop-js', '//f.vimeocdn.com/js/froogaloop2.min.js', array(), null, $in_footer = true );
		// Local version has the fix.
		wp_enqueue_script( 'froogaloop-js', plugins_url( 'assets/froogaloop.js', __FILE__ ), array(), null, $in_footer = true );

		wp_enqueue_style( $this->plugin_slug .'-styles', plugins_url( 'assets/cc-video-lazy-load-public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Get the src from the iframe.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	function get_src_from_iframe_code( $iframe ) {
		$matches = array();

		preg_match('/src="([^"]+)"/', $iframe, $matches);
		$url = $matches[1];

		$url = explode( '?', $matches[1] );

		// These results look like:
		// https://player.vimeo.com/video/110906282
		// https://www.youtube.com/embed/ANcC5sCCVEc
		return current( $url );
	}
	/**
	 * Get the src from the iframe.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function get_id_from_iframe_src( $url ) {
		// Incoming URLs look like:
		// https://player.vimeo.com/video/110906282
		// https://www.youtube.com/embed/ANcC5sCCVEc
		$url = parse_url( $url, PHP_URL_PATH );
		$url = explode('/', $url);
		return $url[2];
	}

	/**
	 * Append the "don't show controls" args to the YT embed url.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function remove_controls_from_youtube_vids( $html ) {
	    $html = preg_replace("@src=\"(.+?)\?(.+?)\"\s@", "src=\"$1?$2&showinfo=0&rel=0&autohide=1\" ", $html );

		return $html;
	}

	/**
	 * Append the "player_id" args to the vimeo embed url.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function add_api_details_to_vimeo_vids( $html, $video_id ) {
		// In order to use Froogaloop with Vimeo videos, you must pass a player id
		// that matches the iframe's ID as well as the api query arg.
	    $html = preg_replace("@src=\"(.+?)\"\s@", "src=\"$1?player_id=vimeo-{$video_id}&api=1\" ", $html );
	    $html = str_replace( '<iframe', '<iframe id="vimeo-' . $video_id . '"', $html );

		return $html;
	}

	/**
	 * Get the YouTube Video ID from the various url formats.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	function get_youtube_id_from_embed_url( $url ) {
		if ( $qargs = parse_url( $url, PHP_URL_QUERY ) ) {
			// This is a url of the form https://www.youtube.com/watch?v=74Kw0wAru8U
			parse_str( $qargs, $result);
			$id = $result['v'];
		} else {
			// This is a url of the form https://youtu.be/74Kw0wAru8U
			$path = parse_url( $url, PHP_URL_PATH );
			$id = str_replace('/', '', $path );
		}

		return $id;
	}

	/**
	 * Get the Vimeo Video ID from the various url formats.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	function get_vimeo_id_from_embed_url( $url ) {
		// This is a url of the form https://vimeo.com/124966922
		$path = parse_url( $url, PHP_URL_PATH );
		return str_replace('/', '', $path );
	}
}
