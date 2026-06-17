<?php
/**
 * YouTube Data API sync support.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manual YouTube video syncs.
 */
class EWO_YouTube_Sync {
	const ACTION                    = 'ewo_youtube_sync_videos';
	const NONCE_ACTION              = 'ewo_youtube_sync_videos';
	const TRANSIENT_LAST_SYNC       = 'ewo_youtube_last_sync';
	const TRANSIENT_LAST_RESULT     = 'ewo_youtube_last_sync_result';
	const TRANSIENT_LAST_ERROR      = 'ewo_youtube_last_sync_error';
	const TRANSIENT_SYNC_LOCK       = 'ewo_youtube_sync_lock';
	const TRANSIENT_UPLOAD_PLAYLIST = 'ewo_youtube_uploads_playlist_id';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_sync_request' ) );
	}

	/**
	 * Handle the manual admin sync request.
	 */
	public function handle_sync_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to sync YouTube videos.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$result = $this->sync();
		$status = is_wp_error( $result ) ? 'error' : 'success';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'ewo-youtube',
					'ewo_youtube_sync' => $status,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Run a manual YouTube upload sync.
	 *
	 * @return array<string, int>|WP_Error
	 */
	public function sync() {
		if ( get_transient( self::TRANSIENT_SYNC_LOCK ) ) {
			return $this->store_error( __( 'A YouTube sync is already running. Try again shortly.', 'ewo-youtube-integration' ) );
		}

		set_transient( self::TRANSIENT_SYNC_LOCK, 1, 5 * MINUTE_IN_SECONDS );

		$api_key    = trim( (string) get_option( EWO_YouTube_Settings::OPTION_API_KEY, '' ) );
		$channel_id = trim( (string) get_option( EWO_YouTube_Settings::OPTION_CHANNEL_ID, '' ) );
		$enabled    = get_option( EWO_YouTube_Settings::OPTION_ENABLE_SYNC, 'no' );

		if ( 'yes' !== $enabled ) {
			delete_transient( self::TRANSIENT_SYNC_LOCK );
			return $this->store_error( __( 'Enable API Sync before syncing videos.', 'ewo-youtube-integration' ) );
		}

		if ( '' === $api_key || '' === $channel_id ) {
			delete_transient( self::TRANSIENT_SYNC_LOCK );
			return $this->store_error( __( 'YouTube API Key and Channel ID are required before syncing.', 'ewo-youtube-integration' ) );
		}

		$uploads_playlist_id = $this->get_uploads_playlist_id( $api_key, $channel_id );

		if ( is_wp_error( $uploads_playlist_id ) ) {
			delete_transient( self::TRANSIENT_SYNC_LOCK );
			return $this->store_error( $uploads_playlist_id->get_error_message() );
		}

		$videos = $this->get_uploads( $api_key, $uploads_playlist_id );

		if ( is_wp_error( $videos ) ) {
			delete_transient( self::TRANSIENT_SYNC_LOCK );
			return $this->store_error( $videos->get_error_message() );
		}

		$created = 0;
		$updated = 0;

		foreach ( $videos as $video ) {
			$post_id = $this->upsert_video_post( $video );

			if ( is_wp_error( $post_id ) ) {
				delete_transient( self::TRANSIENT_SYNC_LOCK );
				return $this->store_error( $post_id->get_error_message() );
			}

			if ( 'created' === $post_id['status'] ) {
				++$created;
			} else {
				++$updated;
			}
		}

		$result = array(
			'created' => $created,
			'updated' => $updated,
			'total'   => count( $videos ),
		);

		set_transient( self::TRANSIENT_LAST_SYNC, time(), 30 * DAY_IN_SECONDS );
		set_transient( self::TRANSIENT_LAST_RESULT, $result, 30 * DAY_IN_SECONDS );
		delete_transient( self::TRANSIENT_LAST_ERROR );
		delete_transient( self::TRANSIENT_SYNC_LOCK );

		return $result;
	}

	/**
	 * Get the channel uploads playlist ID.
	 *
	 * @param string $api_key    YouTube API key.
	 * @param string $channel_id YouTube channel ID.
	 * @return string|WP_Error
	 */
	private function get_uploads_playlist_id( $api_key, $channel_id ) {
		$cache_key = self::TRANSIENT_UPLOAD_PLAYLIST . '_' . md5( $channel_id );
		$cached    = get_transient( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		$response = $this->request(
			'https://www.googleapis.com/youtube/v3/channels',
			array(
				'part' => 'contentDetails',
				'id'   => $channel_id,
				'key'  => $api_key,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
			return new WP_Error( 'ewo_youtube_missing_uploads_playlist', __( 'Could not find the channel uploads playlist.', 'ewo-youtube-integration' ) );
		}

		$playlist_id = sanitize_text_field( $response['items'][0]['contentDetails']['relatedPlaylists']['uploads'] );

		set_transient( $cache_key, $playlist_id, 12 * HOUR_IN_SECONDS );

		return $playlist_id;
	}

	/**
	 * Get latest uploaded videos from an uploads playlist.
	 *
	 * @param string $api_key     YouTube API key.
	 * @param string $playlist_id Uploads playlist ID.
	 * @return array<int, array<string, string>>|WP_Error
	 */
	private function get_uploads( $api_key, $playlist_id ) {
		$response = $this->request(
			'https://www.googleapis.com/youtube/v3/playlistItems',
			array(
				'part'       => 'snippet',
				'playlistId' => $playlist_id,
				'maxResults' => 20,
				'key'        => $api_key,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$videos = array();

		foreach ( $response['items'] ?? array() as $item ) {
			$snippet  = $item['snippet'] ?? array();
			$video_id = $snippet['resourceId']['videoId'] ?? '';

			if ( '' === $video_id || empty( $snippet['title'] ) ) {
				continue;
			}

			$videos[] = array(
				'video_id'     => sanitize_text_field( $video_id ),
				'title'        => sanitize_text_field( $snippet['title'] ),
				'published_at' => sanitize_text_field( $snippet['publishedAt'] ?? '' ),
				'thumbnail'    => esc_url_raw( $this->get_best_thumbnail_url( $snippet['thumbnails'] ?? array() ) ),
				'url'          => esc_url_raw( 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ) ),
			);
		}

		return $videos;
	}

	/**
	 * Make a YouTube API request.
	 *
	 * @param string               $endpoint Endpoint URL.
	 * @param array<string,string> $args     Query arguments.
	 * @return array<string,mixed>|WP_Error
	 */
	private function request( $endpoint, $args ) {
		$url      = add_query_arg( $args, $endpoint );
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return new WP_Error( 'ewo_youtube_invalid_response', __( 'YouTube returned an invalid response.', 'ewo-youtube-integration' ) );
		}

		if ( 200 !== $status_code ) {
			$message = $body['error']['message'] ?? __( 'YouTube API request failed.', 'ewo-youtube-integration' );
			return new WP_Error( 'ewo_youtube_api_error', sanitize_text_field( $message ) );
		}

		return $body;
	}

	/**
	 * Create or update a synced video post.
	 *
	 * @param array<string,string> $video Video metadata.
	 * @return array<string,int|string>|WP_Error
	 */
	private function upsert_video_post( $video ) {
		$existing = get_posts(
			array(
				'post_type'      => 'ewo_youtube_video',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ewo_youtube_video_id',
				'meta_value'     => $video['video_id'],
			)
		);

		$post_date = $this->get_post_date( $video['published_at'] );
		$post_data = array(
			'post_title'   => $video['title'],
			'post_type'    => 'ewo_youtube_video',
			'post_status'  => 'publish',
			'post_date'    => $post_date,
			'post_date_gmt' => get_gmt_from_date( $post_date ),
		);

		if ( ! empty( $existing ) ) {
			$post_data['ID'] = (int) $existing[0];
			$post_id         = wp_update_post( wp_slash( $post_data ), true );
			$status          = 'updated';
		} else {
			$post_id = wp_insert_post( wp_slash( $post_data ), true );
			$status  = 'created';
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, 'ewo_youtube_video_id', $video['video_id'] );
		update_post_meta( $post_id, 'ewo_youtube_title', $video['title'] );
		update_post_meta( $post_id, 'ewo_youtube_thumbnail', $video['thumbnail'] );
		update_post_meta( $post_id, 'ewo_youtube_published_at', $video['published_at'] );
		update_post_meta( $post_id, 'ewo_youtube_url', $video['url'] );
		update_post_meta( $post_id, 'ewo_youtube_video_type', 'long_form' );
		update_post_meta( $post_id, 'ewo_youtube_synced_at', current_time( 'mysql', true ) );

		return array(
			'post_id' => (int) $post_id,
			'status'  => $status,
		);
	}

	/**
	 * Get the best available thumbnail URL.
	 *
	 * @param array<string,array<string,string>> $thumbnails Thumbnail data.
	 * @return string
	 */
	private function get_best_thumbnail_url( $thumbnails ) {
		foreach ( array( 'maxres', 'standard', 'high', 'medium', 'default' ) as $size ) {
			if ( ! empty( $thumbnails[ $size ]['url'] ) ) {
				return $thumbnails[ $size ]['url'];
			}
		}

		return '';
	}

	/**
	 * Convert YouTube publish date to a WordPress local post date.
	 *
	 * @param string $published_at ISO 8601 publish date.
	 * @return string
	 */
	private function get_post_date( $published_at ) {
		$timestamp = strtotime( $published_at );

		if ( ! $timestamp ) {
			return current_time( 'mysql' );
		}

		return get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ) );
	}

	/**
	 * Store the latest sync error.
	 *
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private function store_error( $message ) {
		$message = sanitize_text_field( $message );

		set_transient( self::TRANSIENT_LAST_ERROR, $message, 30 * DAY_IN_SECONDS );

		return new WP_Error( 'ewo_youtube_sync_error', $message );
	}
}
