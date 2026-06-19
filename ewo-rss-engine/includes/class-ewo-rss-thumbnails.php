<?php
/**
 * Featured-image handling for imported items.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts and attaches thumbnails for imported posts.
 */
class EWO_RSS_Thumbnails {

	/**
	 * Try to determine an image URL for a feed item.
	 *
	 * Checks, in order: enclosure, media:thumbnail/content, then the first
	 * <img> found in the item content.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string Image URL, or empty string if none found.
	 */
	public function get_image_url( $item ) {
		$enclosure = $item->get_enclosure();

		if ( $enclosure ) {
			$thumb = $enclosure->get_thumbnail();
			if ( $thumb ) {
				return esc_url_raw( $thumb );
			}

			$link = $enclosure->get_link();
			$type = (string) $enclosure->get_type();
			if ( $link && ( '' === $type || 0 === strpos( $type, 'image' ) ) ) {
				return esc_url_raw( $link );
			}
		}

		$content = (string) $item->get_content();
		if ( '' === $content ) {
			$content = (string) $item->get_description();
		}

		if ( '' !== $content && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
			return esc_url_raw( $matches[1] );
		}

		return '';
	}

	/**
	 * Sideload an image and set it as the post's featured image.
	 *
	 * @param int    $post_id   Target post ID.
	 * @param string $image_url Remote image URL.
	 * @return bool True on success.
	 */
	public function set_featured_image( $post_id, $image_url ) {
		$post_id   = (int) $post_id;
		$image_url = trim( (string) $image_url );

		if ( 0 === $post_id || '' === $image_url ) {
			return false;
		}

		if ( has_post_thumbnail( $post_id ) ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		return (bool) set_post_thumbnail( $post_id, $attachment_id );
	}
}
