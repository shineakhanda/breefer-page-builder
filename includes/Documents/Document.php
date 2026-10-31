<?php
/**
 * Builder document storage and sanitisation.
 *
 * @package Breefer
 */

namespace Breefer\Documents;

defined( 'ABSPATH' ) || exit;

/**
 * Reads, writes and validates the JSON element tree stored per post.
 */
final class Document {

	/**
	 * Document meta key.
	 */
	public const META = '_breefer_data';

	/**
	 * Whether the post renders through Breefer.
	 */
	public const ENABLED = '_breefer_enabled';

	/**
	 * Document format version.
	 */
	public const VERSION = 1;

	/**
	 * Maximum node depth.
	 */
	private const MAX_DEPTH = 20;

	/**
	 * Maximum nodes per document.
	 */
	private const MAX_NODES = 800;

	/**
	 * Read a document for a post.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	public static function get( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META, true );

		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );

			if ( is_array( $decoded ) && isset( $decoded['root'] ) ) {
				return self::sanitize( $decoded );
			}
		}

		return self::empty_document();
	}

	/**
	 * Persist a document.
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $document Document.
	 * @return bool
	 */
	public static function save( int $post_id, array $document ): bool {
		$clean = self::sanitize( $document );
		$json  = wp_json_encode( $clean );

		if ( ! is_string( $json ) ) {
			return false;
		}

		// update_post_meta() returns false when the value is unchanged, which is
		// not a failure for an autosave that sends the same tree twice.
		update_post_meta( $post_id, self::META, wp_slash( $json ) );

		return true;
	}

	/**
	 * Whether the post renders through Breefer.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public static function is_enabled( int $post_id ): bool {
		return '1' === get_post_meta( $post_id, self::ENABLED, true );
	}

	/**
	 * Enable or disable Breefer rendering for a post.
	 *
	 * @param int  $post_id Post id.
	 * @param bool $enabled Enabled.
	 * @return void
	 */
	public static function set_enabled( int $post_id, bool $enabled ): void {
		if ( $enabled ) {
			update_post_meta( $post_id, self::ENABLED, '1' );
			return;
		}

		delete_post_meta( $post_id, self::ENABLED );
	}

	/**
	 * Empty document.
	 *
	 * @return array<string, mixed>
	 */
	public static function empty_document(): array {
		return array(
			'version' => self::VERSION,
			'root'    => array(
				'id'       => 'root',
				'type'     => 'container',
				'settings' => array(
					'direction' => 'column',
					'gap'       => 24,
					'padding'   => 48,
					'maxWidth'  => 1080,
				),
				'children' => array(),
			),
		);
	}

	/**
	 * Starter document used when a new template is created.
	 *
	 * @return array<string, mixed>
	 */
	public static function seed_document(): array {
		return array(
			'version' => self::VERSION,
			'root'    => array(
				'id'       => 'root',
				'type'     => 'container',
				'settings' => array(
					'direction' => 'column',
					'gap'       => 24,
					'padding'   => 72,
					'maxWidth'  => 960,
					'align'     => 'center',
				),
				'children' => array(
					array(
						'id'       => self::new_id(),
						'type'     => 'heading',
						'settings' => array(
							'text'       => __( 'Build something fast.', 'breefer' ),
							'level'      => 'h1',
							'fontSize'   => 52,
							'fontWeight' => 700,
							'color'      => '#1e1735',
							'align'      => 'center',
						),
						'children' => array(),
					),
					array(
						'id'       => self::new_id(),
						'type'     => 'text',
						'settings' => array(
							'content'  => __( 'This page is rendered by Breefer as static HTML. Edit it, add widgets, and save.', 'breefer' ),
							'fontSize' => 18,
							'color'    => '#7d7395',
							'align'    => 'center',
						),
						'children' => array(),
					),
					array(
						'id'       => self::new_id(),
						'type'     => 'button',
						'settings' => array(
							'text'       => __( 'Get started', 'breefer' ),
							'url'        => '#',
							'background' => '#7c5cfc',
							'color'      => '#ffffff',
							'radius'     => 99,
							'fontSize'   => 14,
							'align'      => 'center',
						),
						'children' => array(),
					),
				),
			),
		);
	}

	/**
	 * Validate and normalise a document tree.
	 *
	 * @param mixed $raw Raw document.
	 * @return array<string, mixed>
	 */
	public static function sanitize( mixed $raw ): array {
		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			$raw     = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $raw ) ) {
			return self::empty_document();
		}

		$count = 0;
		$root  = self::sanitize_node( $raw['root'] ?? null, 0, $count );

		if ( null === $root ) {
			$root = self::empty_document()['root'];
		}

		return array(
			'version' => self::VERSION,
			'root'    => $root,
		);
	}

	/**
	 * Sanitize one node.
	 *
	 * @param mixed $node  Node array.
	 * @param int   $depth Current depth.
	 * @param int   $count Running node count.
	 * @return array<string, mixed>|null
	 */
	private static function sanitize_node( mixed $node, int $depth, int &$count ): ?array {
		if ( ! is_array( $node ) || $depth > self::MAX_DEPTH || $count >= self::MAX_NODES ) {
			return null;
		}

		++$count;

		$id = isset( $node['id'] ) ? sanitize_key( (string) $node['id'] ) : '';

		if ( '' === $id ) {
			$id = self::new_id();
		}

		$type = isset( $node['type'] ) ? sanitize_key( (string) $node['type'] ) : '';

		if ( '' === $type ) {
			return null;
		}

		$settings = array();

		if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
			$settings = self::sanitize_settings( $node['settings'], 0 );
		}

		$children = array();

		if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
			$index = 0;

			foreach ( $node['children'] as $child ) {
				if ( $index >= 200 ) {
					break;
				}

				$clean = self::sanitize_node( $child, $depth + 1, $count );

				if ( null !== $clean ) {
					$children[] = $clean;
					++$index;
				}
			}
		}

		return array(
			'id'       => $id,
			'type'     => $type,
			'settings' => $settings,
			'children' => $children,
		);
	}

	/**
	 * Sanitize a settings map.
	 *
	 * @param array<string|int, mixed> $settings Settings.
	 * @param int                      $depth    Depth.
	 * @return array<string, mixed>
	 */
	private static function sanitize_settings( array $settings, int $depth ): array {
		$clean = array();

		foreach ( $settings as $key => $value ) {
			$key = preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key );

			if ( ! is_string( $key ) || '' === $key || strlen( $key ) > 40 ) {
				continue;
			}

			$value = self::sanitize_value( $key, $value, $depth );

			if ( null !== $value ) {
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}

	/**
	 * Sanitize one setting value.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Raw value.
	 * @param int    $depth Depth.
	 * @return mixed
	 */
	private static function sanitize_value( string $key, mixed $value, int $depth ): mixed {
		if ( is_bool( $value ) || is_int( $value ) ) {
			return $value;
		}

		if ( is_float( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$normalized = strtolower( $key );

			if ( in_array( $normalized, array( 'url', 'link', 'href', 'backgroundimage', 'image' ), true ) ) {
				return esc_url_raw( $value );
			}

			if ( 1 === preg_match( '/color$|^(background|gradientfrom|gradientto)$/', $normalized ) ) {
				if ( 1 === preg_match( '/^global:[a-z]+$/', $value ) ) {
					return $value;
				}

				$hex = sanitize_hex_color( $value );

				return is_string( $hex ) ? $hex : '';
			}

			// Multi-line settings (one entry per line) must keep their newlines.
			if ( in_array( $normalized, array( 'items', 'features', 'fields', 'links', 'customcss', 'images', 'networks' ), true ) ) {
				return substr( sanitize_textarea_field( $value ), 0, 8000 );
			}

			if ( in_array( $normalized, array( 'content', 'text', 'title', 'description', 'label', 'html' ), true ) ) {
				return wp_kses_post( $value );
			}

			return sanitize_text_field( $value );
		}

		if ( is_array( $value ) && $depth < 2 ) {
			return self::sanitize_settings( $value, $depth + 1 );
		}

		return null;
	}

	/**
	 * Find a node by id anywhere in a document.
	 *
	 * @param array<string, mixed> $document Document.
	 * @param string               $id       Node id.
	 * @return array<string, mixed>|null
	 */
	public static function find_node( array $document, string $id ): ?array {
		if ( ! isset( $document['root'] ) || ! is_array( $document['root'] ) ) {
			return null;
		}

		return self::search_node( $document['root'], $id );
	}

	/**
	 * Recursive id search.
	 *
	 * @param array<string, mixed> $node Node.
	 * @param string               $id   Node id.
	 * @return array<string, mixed>|null
	 */
	private static function search_node( array $node, string $id ): ?array {
		if ( isset( $node['id'] ) && $id === $node['id'] ) {
			return $node;
		}

		if ( empty( $node['children'] ) || ! is_array( $node['children'] ) ) {
			return null;
		}

		foreach ( $node['children'] as $child ) {
			if ( is_array( $child ) ) {
				$found = self::search_node( $child, $id );

				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}
	/**
	 * Whether a document contains any of the given widget types.
	 *
	 * @param array<string, mixed> $document Document.
	 * @param array<int, string>   $types    Widget types.
	 * @return bool
	 */
	public static function contains_any_type( array $document, array $types ): bool {
		foreach ( $types as $type ) {
			if ( self::contains_type( $document, (string) $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a document contains a widget type.
	 *
	 * @param array<string, mixed> $document Document.
	 * @param string               $type     Widget type.
	 * @return bool
	 */
	public static function contains_type( array $document, string $type ): bool {
		if ( ! isset( $document['root'] ) || ! is_array( $document['root'] ) ) {
			return false;
		}

		return self::node_contains_type( $document['root'], $type );
	}

	/**
	 * Recursive type search.
	 *
	 * @param array<string, mixed> $node Node.
	 * @param string               $type Widget type.
	 * @return bool
	 */
	private static function node_contains_type( array $node, string $type ): bool {
		if ( isset( $node['type'] ) && $type === $node['type'] ) {
			return true;
		}

		if ( empty( $node['children'] ) || ! is_array( $node['children'] ) ) {
			return false;
		}

		foreach ( $node['children'] as $child ) {
			if ( is_array( $child ) && self::node_contains_type( $child, $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate a node id.
	 *
	 * @return string
	 */
	public static function new_id(): string {
		return 'n' . strtolower( substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 8 ) );
	}
}
