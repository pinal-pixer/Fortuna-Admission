<?php
declare( strict_types=1 );

namespace GutSlider\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tunes the players of the videos carousel as the block renders.
 *
 * Applied while rendering, so the saved block markup (and block validation)
 * is untouched and blocks saved long ago benefit too:
 *
 * - YouTube rejects embeds whose request carries no Referer header and shows
 *   "Video player configuration error (Error 153)". Sites that send a
 *   site-wide `Referrer-Policy: no-referrer` strip that header, so each
 *   YouTube iframe gets its own `referrerpolicy`.
 * - "Autoplay on load" saved `autoplay=1` on every player, so all videos tried
 *   to start at once (and browsers block unmuted autoplay). Only the first
 *   video autoplays now, muted; a video behind a custom thumbnail waits for
 *   its play button.
 * - Players after the first load lazily.
 * - Every player used the same title ("video-carousel-slide"); screen readers
 *   now hear the video's own name, and thumbnail play buttons get a label.
 *
 * @package GutSlider\Blocks
 * @since   3.1.1
 */
final class VideoEmbeds {

	/**
	 * Constructor.
	 *
	 * @since 3.1.1
	 */
	public function __construct() {
		add_filter( 'render_block_gutsliders/videos-carousel', array( $this, 'tune_players' ), 10, 2 );
		add_filter( 'render_block_gutsliders/videos-carousel', array( $this, 'add_extras' ), 11, 2 );
	}

	/**
	 * Settings added after the block's markup was saved: the YouTube / Vimeo hover
	 * preview flag (Reels, Video Cards) and VideoObject structured data.
	 *
	 * @since 3.1.1
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block.
	 * @return string Block HTML.
	 */
	public function add_extras( $block_content, $block = array() ): string {
		$block_content = (string) $block_content;
		$attrs         = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if ( ! empty( $attrs['embedPreview'] ) && in_array( $attrs['preset'] ?? '', array( 'reels', 'cards' ), true ) && class_exists( '\WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $block_content );
			if ( $processor->next_tag( array( 'class_name' => 'wp-block-gutsliders-videos-carousel' ) ) ) {
				$processor->set_attribute( 'data-embed-preview', 'true' );
				$block_content = $processor->get_updated_html();
			}
		}

		if ( ! empty( $attrs['videoSchema'] ) ) {
			$block_content .= $this->get_schema( $attrs );
		}

		return $block_content;
	}

	/**
	 * VideoObject structured data for the carousel's videos (Google video results).
	 * Videos without a still image are left out: Google requires one.
	 *
	 * @since 3.1.1
	 *
	 * @param array $attrs Block attributes.
	 * @return string `<script type="application/ld+json">` or ''.
	 */
	private function get_schema( array $attrs ): string {
		$items   = isset( $attrs['slideItems'] ) && is_array( $attrs['slideItems'] ) ? array_values( $attrs['slideItems'] ) : array();
		$custom  = ! empty( $attrs['enableCustomPoster'] );
		$privacy = ! empty( $attrs['videoPlayer']['privacy'] );
		$videos  = array();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$type  = $item['videoType'] ?? 'youtube';
			$id    = isset( $item['videoID'] ) ? (string) $item['videoID'] : '';
			$file  = 'file' === $type && ! empty( $item['video']['url'] ) ? (string) $item['video']['url'] : '';
			$thumb = ( $custom || $file ) && ! empty( $item['thumbnail']['url'] ) ? (string) $item['thumbnail']['url'] : '';

			if ( ! $thumb && 'youtube' === $type && '' !== $id ) {
				$thumb = 'https://i.ytimg.com/vi/' . rawurlencode( $id ) . '/hqdefault.jpg';
			}
			if ( ! $thumb && 'vimeo' === $type && ! empty( $item['remoteThumb'] ) ) {
				$thumb = (string) $item['remoteThumb'];
			}
			if ( ! $thumb || ( '' === $id && '' === $file ) ) {
				continue;
			}

			$name  = $this->get_video_name( $item, $index );
			$video = array(
				'@type'        => 'VideoObject',
				'name'         => $name,
				'description'  => ! empty( $item['description'] ) ? wp_strip_all_tags( (string) $item['description'] ) : $name,
				'thumbnailUrl' => array( $thumb ),
				'uploadDate'   => $this->get_upload_date( $item ),
			);

			if ( $file ) {
				$video['contentUrl'] = $file;
			} elseif ( 'vimeo' === $type ) {
				$video['embedUrl'] = 'https://player.vimeo.com/video/' . rawurlencode( $id );
			} else {
				$video['embedUrl'] = ( $privacy ? 'https://www.youtube-nocookie.com' : 'https://www.youtube.com' ) . '/embed/' . rawurlencode( $id );
			}

			$duration = $this->get_iso_duration( isset( $item['duration'] ) ? (string) $item['duration'] : '' );
			if ( $duration ) {
				$video['duration'] = $duration;
			}

			$videos[] = $video;
		}

		if ( ! $videos ) {
			return '';
		}

		$data = array(
			'@context' => 'https://schema.org',
			'@graph'   => $videos,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
	}

	/**
	 * A video's upload date (ISO 8601): the one set on the video, else the page's date.
	 *
	 * @since 3.1.1
	 *
	 * @param array $item Video item.
	 * @return string Date.
	 */
	private function get_upload_date( array $item ): string {
		if ( ! empty( $item['uploadDate'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $item['uploadDate'] ) ) {
			try {
				return ( new \DateTimeImmutable( $item['uploadDate'] . ' 00:00:00', wp_timezone() ) )->format( 'c' );
			} catch ( \Exception $e ) {
				unset( $e );
			}
		}
		$date = get_the_date( 'c' );
		return $date ? (string) $date : gmdate( 'c' );
	}

	/**
	 * "4:12" or "1:02:03" as an ISO 8601 duration (PT4M12S); '' when unreadable.
	 *
	 * @since 3.1.1
	 *
	 * @param string $duration Duration as shown on the card.
	 * @return string ISO duration.
	 */
	private function get_iso_duration( string $duration ): string {
		if ( ! preg_match( '/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', trim( $duration ), $match ) ) {
			return '';
		}
		$hours   = (int) $match[1];
		$minutes = (int) $match[2];
		$seconds = (int) $match[3];
		return 'PT' . ( $hours ? $hours . 'H' : '' ) . ( $minutes ? $minutes . 'M' : '' ) . $seconds . 'S';
	}

	/**
	 * Adjust the block's iframes and play buttons.
	 *
	 * @since 3.1.1
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block.
	 * @return string Block HTML.
	 */
	public function tune_players( $block_content, $block = array() ): string {
		$block_content = (string) $block_content;

		if ( false === stripos( $block_content, '<iframe' ) && false === stripos( $block_content, '<video' ) && false === stripos( $block_content, 'play-btn' ) ) {
			return $block_content;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		$attrs         = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
		$items         = isset( $attrs['slideItems'] ) && is_array( $attrs['slideItems'] ) ? array_values( $attrs['slideItems'] ) : array();
		$custom_poster = ! empty( $attrs['enableCustomPoster'] );

		$processor = new \WP_HTML_Tag_Processor( $block_content );
		$slide     = -1;
		$player    = 0;

		while ( $processor->next_tag() ) {
			$tag = $processor->get_tag();

			// Each slide holds one video; count slides to find its item.
			if ( 'DIV' === $tag && $processor->has_class( 'swiper-slide' ) ) {
				++$slide;
				continue;
			}

			$item = isset( $items[ $slide ] ) && is_array( $items[ $slide ] ) ? $items[ $slide ] : array();
			$name = $this->get_video_name( $item, max( 0, $slide ) );

			if ( 'BUTTON' === $tag && $processor->has_class( 'play-btn' ) && null === $processor->get_attribute( 'aria-label' ) ) {
				/* translators: %s: video name. */
				$processor->set_attribute( 'aria-label', sprintf( __( 'Play video: %s', 'slider-blocks' ), $name ) );
				continue;
			}

			// Self-hosted players (Classic): a name for screen readers.
			if ( 'VIDEO' === $tag && $processor->has_class( 'video-carousel-slide' ) ) {
				if ( null === $processor->get_attribute( 'aria-label' ) ) {
					$processor->set_attribute( 'aria-label', $name );
				}
				continue;
			}

			if ( 'IFRAME' !== $tag ) {
				continue;
			}

			$src = (string) $processor->get_attribute( 'src' );

			if ( 'video-carousel-slide' === $processor->get_attribute( 'title' ) ) {
				$processor->set_attribute( 'title', $name );
			}

			// youtube.com or the privacy-enhanced youtube-nocookie.com.
			if ( preg_match( '#youtube(?:-nocookie)?\.com/embed#i', $src ) && null === $processor->get_attribute( 'referrerpolicy' ) ) {
				$processor->set_attribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
			}

			if ( preg_match( '/[?&]autoplay=1\b/', $src ) ) {
				$has_thumbnail = $custom_poster && ! empty( $item['thumbnail']['url'] );

				if ( 0 === $player && ! $has_thumbnail ) {
					// Browsers only start playback by themselves when it's muted.
					$src = (string) preg_replace( '/([?&])mute=0\b/', '$1mute=1', $src );
					if ( ! preg_match( '/[?&]mute=1\b/', $src ) ) {
						$src .= ( false === strpos( $src, '?' ) ? '?' : '&' ) . 'mute=1';
					}
				} else {
					$src = (string) preg_replace( '/([?&])autoplay=1\b/', '$1autoplay=0', $src );
				}
				$processor->set_attribute( 'src', $src );
			}

			if ( $player > 0 && null === $processor->get_attribute( 'loading' ) ) {
				$processor->set_attribute( 'loading', 'lazy' );
			}

			++$player;
		}

		return $processor->get_updated_html();
	}

	/**
	 * A human name for a video: its title, its editor name, else "Video N".
	 *
	 * @since 3.1.1
	 *
	 * @param array $item  Video item.
	 * @param int   $index Position in the carousel.
	 * @return string Name.
	 */
	private function get_video_name( array $item, int $index ): string {
		foreach ( array( 'title', 'panelTitle' ) as $key ) {
			if ( ! empty( $item[ $key ] ) && is_string( $item[ $key ] ) ) {
				return wp_strip_all_tags( $item[ $key ] );
			}
		}

		/* translators: %d: video position in the carousel. */
		return sprintf( __( 'Video %d', 'slider-blocks' ), $index + 1 );
	}
}
