<?php
/**
 * Social links and share buttons.
 *
 * @package Breefer
 */

namespace Breefer\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Renders profile links ("social") or share links for the current page ("share").
 */
final class SocialLinks extends AbstractWidget {

	/**
	 * Networks: label, badge text and share URL template.
	 *
	 * @var array<string, array{label: string, badge: string, share: string}>
	 */
	private const NETWORKS = array(
		'facebook'  => array(
			'label' => 'Facebook',
			'badge' => 'f',
			'share' => 'https://www.facebook.com/sharer/sharer.php?u={url}',
		),
		'x'         => array(
			'label' => 'X',
			'badge' => 'X',
			'share' => 'https://twitter.com/intent/tweet?url={url}&text={title}',
		),
		'linkedin'  => array(
			'label' => 'LinkedIn',
			'badge' => 'in',
			'share' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
		),
		'instagram' => array(
			'label' => 'Instagram',
			'badge' => 'ig',
			'share' => '',
		),
		'youtube'   => array(
			'label' => 'YouTube',
			'badge' => 'yt',
			'share' => '',
		),
		'tiktok'    => array(
			'label' => 'TikTok',
			'badge' => 'tt',
			'share' => '',
		),
		'github'    => array(
			'label' => 'GitHub',
			'badge' => 'gh',
			'share' => '',
		),
		'pinterest' => array(
			'label' => 'Pinterest',
			'badge' => 'P',
			'share' => 'https://pinterest.com/pin/create/button/?url={url}&description={title}',
		),
		'whatsapp'  => array(
			'label' => 'WhatsApp',
			'badge' => 'wa',
			'share' => 'https://wa.me/?text={title}%20{url}',
		),
		'telegram'  => array(
			'label' => 'Telegram',
			'badge' => 'tg',
			'share' => 'https://t.me/share/url?url={url}&text={title}',
		),
		'email'     => array(
			'label' => 'Email',
			'badge' => '@',
			'share' => 'mailto:?subject={title}&body={url}',
		),
	);

	/**
	 * Either "social" or "share".
	 */
	private string $mode;

	/**
	 * Constructor.
	 *
	 * @param string $mode social|share.
	 */
	public function __construct( string $mode = 'social' ) {
		$this->mode = 'share' === $mode ? 'share' : 'social';
	}

	/**
	 * Type.
	 *
	 * @return string
	 */
	public function type(): string {
		return $this->mode;
	}

	/**
	 * Render.
	 *
	 * @param array<string, mixed> $settings      Settings.
	 * @param string               $children_html Children.
	 * @param string               $node_id       Node id.
	 * @return string
	 */
	public function render( array $settings, string $children_html, string $node_id ): string {
		unset( $children_html, $node_id );

		$entries = $this->lines( $settings, 'networks' );
		$items   = '';
		$page    = $this->current_url();
		$title   = (string) wp_get_document_title();

		foreach ( $entries as $entry ) {
			$parts   = $this->parts( $entry, 2 );
			$network = sanitize_key( $parts[0] );

			if ( ! isset( self::NETWORKS[ $network ] ) ) {
				continue;
			}

			$meta = self::NETWORKS[ $network ];

			if ( 'share' === $this->mode ) {
				if ( '' === $meta['share'] ) {
					continue;
				}

				$href = str_replace(
					array( '{url}', '{title}' ),
					array( rawurlencode( $page ), rawurlencode( $title ) ),
					$meta['share']
				);
			} else {
				$href = $parts[1];

				if ( '' === $href ) {
					continue;
				}
			}

			$items .= sprintf(
				'<li><a class="br-social-link is-%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s"><span aria-hidden="true">%4$s</span></a></li>',
				esc_attr( $network ),
				'email' === $network && 'share' === $this->mode ? esc_url( $href, array( 'mailto' ) ) : esc_url( $href ),
				esc_attr( 'share' === $this->mode
					/* translators: %s: network name. */
					? sprintf( __( 'Share on %s', 'breefer' ), $meta['label'] )
					/* translators: %s: network name. */
					: sprintf( __( 'Visit %s', 'breefer' ), $meta['label'] ) ),
				esc_html( $meta['badge'] )
			);
		}

		if ( '' === $items ) {
			return '';
		}

		$style = $this->choice( $settings, 'style', array( 'filled', 'outline', 'plain' ), 'filled' );
		$shape = $this->choice( $settings, 'shape', array( 'circle', 'rounded', 'square' ), 'circle' );
		$vars  = $this->vars(
			array(
				'--br-social-color' => $this->color( $settings, 'iconColor' ),
				'--br-social-size'  => $this->px( $settings, 'size', 24, 96 ),
				'--br-social-gap'   => $this->px( $settings, 'gap', 0, 60 ),
			)
		);

		return '<ul class="br-social is-' . esc_attr( $style ) . ' is-' . esc_attr( $shape ) . '"' . $vars . '>' . $items . '</ul>';
	}

	/**
	 * URL of the page being viewed.
	 *
	 * @return string
	 */
	private function current_url(): string {
		$post = \Breefer\Utils\Context::post();

		if ( null !== $post && ( is_singular() || \Breefer\Utils\Context::is_preview() ) ) {
			return (string) get_permalink( $post );
		}

		return home_url( '/' );
	}
}
