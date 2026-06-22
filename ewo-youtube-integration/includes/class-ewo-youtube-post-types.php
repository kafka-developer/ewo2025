<?php
/**
 * YouTube custom post type registration.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers YouTube content custom post types.
 */
class EWO_YouTube_Post_Types {
	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register all YouTube custom post types.
	 */
	public function register() {
		$this->register_video_post_type();
		$this->register_playlist_post_type();
		$this->register_community_post_type();
	}

	/**
	 * Register the video post type.
	 */
	private function register_video_post_type() {
		register_post_type(
			'ewo_video',
			array(
				'labels'          => array(
					'name'               => esc_html__( 'YouTube Videos', 'ewo-youtube-integration' ),
					'singular_name'      => esc_html__( 'YouTube Video', 'ewo-youtube-integration' ),
					'add_new_item'       => esc_html__( 'Add New YouTube Video', 'ewo-youtube-integration' ),
					'edit_item'          => esc_html__( 'Edit YouTube Video', 'ewo-youtube-integration' ),
					'new_item'           => esc_html__( 'New YouTube Video', 'ewo-youtube-integration' ),
					'view_item'          => esc_html__( 'View YouTube Video', 'ewo-youtube-integration' ),
					'search_items'       => esc_html__( 'Search YouTube Videos', 'ewo-youtube-integration' ),
					'not_found'          => esc_html__( 'No YouTube videos found.', 'ewo-youtube-integration' ),
					'not_found_in_trash' => esc_html__( 'No YouTube videos found in Trash.', 'ewo-youtube-integration' ),
					'menu_name'          => esc_html__( 'YouTube Videos', 'ewo-youtube-integration' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'capability_type' => 'post',
				'has_archive'     => false,
				'rewrite'         => false,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			)
		);
	}

	/**
	 * Register the playlist post type.
	 */
	private function register_playlist_post_type() {
		register_post_type(
			'ewo_playlist',
			array(
				'labels'          => array(
					'name'               => esc_html__( 'YouTube Playlists', 'ewo-youtube-integration' ),
					'singular_name'      => esc_html__( 'YouTube Playlist', 'ewo-youtube-integration' ),
					'add_new_item'       => esc_html__( 'Add New YouTube Playlist', 'ewo-youtube-integration' ),
					'edit_item'          => esc_html__( 'Edit YouTube Playlist', 'ewo-youtube-integration' ),
					'new_item'           => esc_html__( 'New YouTube Playlist', 'ewo-youtube-integration' ),
					'view_item'          => esc_html__( 'View YouTube Playlist', 'ewo-youtube-integration' ),
					'search_items'       => esc_html__( 'Search YouTube Playlists', 'ewo-youtube-integration' ),
					'not_found'          => esc_html__( 'No YouTube playlists found.', 'ewo-youtube-integration' ),
					'not_found_in_trash' => esc_html__( 'No YouTube playlists found in Trash.', 'ewo-youtube-integration' ),
					'menu_name'          => esc_html__( 'YouTube Playlists', 'ewo-youtube-integration' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'capability_type' => 'post',
				'has_archive'     => false,
				'rewrite'         => false,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			)
		);
	}

	/**
	 * Register the community post type.
	 */
	private function register_community_post_type() {
		register_post_type(
			'ewo_community',
			array(
				'labels'          => array(
					'name'               => esc_html__( 'YouTube Community Posts', 'ewo-youtube-integration' ),
					'singular_name'      => esc_html__( 'YouTube Community Post', 'ewo-youtube-integration' ),
					'add_new_item'       => esc_html__( 'Add New YouTube Community Post', 'ewo-youtube-integration' ),
					'edit_item'          => esc_html__( 'Edit YouTube Community Post', 'ewo-youtube-integration' ),
					'new_item'           => esc_html__( 'New YouTube Community Post', 'ewo-youtube-integration' ),
					'view_item'          => esc_html__( 'View YouTube Community Post', 'ewo-youtube-integration' ),
					'search_items'       => esc_html__( 'Search YouTube Community Posts', 'ewo-youtube-integration' ),
					'not_found'          => esc_html__( 'No YouTube community posts found.', 'ewo-youtube-integration' ),
					'not_found_in_trash' => esc_html__( 'No YouTube community posts found in Trash.', 'ewo-youtube-integration' ),
					'menu_name'          => esc_html__( 'YouTube Community', 'ewo-youtube-integration' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => true,
				'capability_type' => 'post',
				'has_archive'     => false,
				'rewrite'         => false,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			)
		);
	}
}
