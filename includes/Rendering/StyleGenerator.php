<?php
/**
 * Per-node CSS generator.
 *
 * @package Breefer
 */

namespace Breefer\Rendering;

use Breefer\Utils\DesignSystem;

defined( 'ABSPATH' ) || exit;

/**
 * Turns node settings into scoped CSS rules.
 */
final class StyleGenerator {

	/**
	 * Build the CSS for one node, including breakpoint overrides.
	 *
	 * @param array<string, mixed> $node Node.
	 * @return string
	 */
	public function for_node( array $node ): string {
		$css = $this->render_rules( $node );

		$id   = isset( $node['id'] ) ? sanitize_html_class( (string) $node['id'] ) : '';
		$s    = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
		$base = '.breefer-canvas .br-node-' . $id;

		if ( '' === $css && '' === $this->custom_css( $s['customCss'] ?? null, $base ) ) {
			return '';
		}

		if ( '' !== $id ) {
			if ( ! empty( $s['hideDesktop'] ) ) {
				$css .= '@media(min-width:1025px){' . $base . '{display:none}}';
			}

			if ( ! empty( $s['hideTablet'] ) ) {
				$css .= '@media(min-width:768px) and (max-width:1024px){' . $base . '{display:none}}';
			}

			if ( ! empty( $s['hideMobile'] ) ) {
				$css .= '@media(max-width:767px){' . $base . '{display:none}}';
			}
		}

		$tablet = $this->breakpoint_node( $node, '_tablet' );

		if ( $tablet !== $node ) {
			$rules = $this->render_rules( $tablet );

			if ( '' !== $rules ) {
				$css .= '@media(max-width:1024px){' . $rules . '}';
			}
		}

		$mobile = $this->breakpoint_node( $tablet, '_mobile' );

		if ( $mobile !== $tablet ) {
			$rules = $this->render_rules( $mobile );

			if ( '' !== $rules ) {
				$css .= '@media(max-width:767px){' . $rules . '}';
			}
		}

		$custom = $this->custom_css( $s['customCss'] ?? null, $base );

		if ( '' !== $custom ) {
			$css .= $custom;
		}

		// Grids collapse to two columns on tablets and one on phones unless set.
		if ( 'container' === ( $node['type'] ?? '' ) && 'grid' === ( $s['layout'] ?? '' ) ) {
			$grid_columns = $this->columns( $s['columns'] ?? null, 3 );

			if ( ! isset( $s['columns_tablet'] ) && $grid_columns > 2 ) {
				$css .= '@media(max-width:1024px){' . $base . '{grid-template-columns:repeat(2,minmax(0,1fr))}}';
			}

			if ( ! isset( $s['columns_mobile'] ) && $grid_columns > 1 ) {
				$css .= '@media(max-width:767px){' . $base . '{grid-template-columns:minmax(0,1fr)}}';
			}
		}

		// Row containers stack on phones unless the user set a mobile direction.
		$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
		$type     = isset( $node['type'] ) ? sanitize_key( (string) $node['type'] ) : '';

		if ( 'container' === $type && ! isset( $settings['direction_mobile'] ) ) {
			$mobile_settings = isset( $mobile['settings'] ) && is_array( $mobile['settings'] ) ? $mobile['settings'] : array();

			if ( 'row' === $this->direction( $mobile_settings['direction'] ?? null ) ) {
				$css .= '@media(max-width:767px){' . $base . '{flex-direction:column}}';
			}
		}

		return $css;
	}

	/**
	 * Build unconditional rules for one configuration.
	 *
	 * @param array<string, mixed> $node Node.
	 * @return string
	 */
	private function render_rules( array $node ): string {
		$id   = isset( $node['id'] ) ? sanitize_html_class( (string) $node['id'] ) : '';
		$type = isset( $node['type'] ) ? sanitize_key( (string) $node['type'] ) : '';
		$s    = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();

		if ( '' === $id || '' === $type ) {
			return '';
		}

		$base = '.breefer-canvas .br-node-' . $id;
		$css  = '';

		$wrapper = array(
			'margin'     => $this->px( $s['margin'] ?? null, 0, 400 ),
			'color'      => $this->color( $s['color'] ?? null ),
			'text-align' => $this->align( $s['align'] ?? null ),
			'width'      => $this->percent_max( $s['boxWidth'] ?? null, 1, 200 ),
			'height'     => $this->px( $s['boxHeight'] ?? null, 0, 4000 ),
		);

		$position = isset( $s['position'] ) && is_string( $s['position'] ) ? $s['position'] : '';

		if ( in_array( $position, array( 'relative', 'absolute', 'sticky' ), true ) ) {
			$wrapper['position'] = $position;
		}

		$wrapper['top']    = $this->px_signed( $s['offsetTop'] ?? null );
		$wrapper['left']   = $this->px_signed( $s['offsetLeft'] ?? null );
		$wrapper['right']  = $this->px_signed( $s['offsetRight'] ?? null );
		$wrapper['bottom'] = $this->px_signed( $s['offsetBottom'] ?? null );
		$wrapper['z-index'] = $this->z_index( $s['zIndex'] ?? null );

		$rotate = isset( $s['rotate'] ) && is_numeric( $s['rotate'] ) ? max( -360, min( 360, (float) $s['rotate'] ) ) : 0;

		if ( 0.0 !== $rotate ) {
			$wrapper['transform'] = 'rotate(' . $this->trim( $rotate ) . 'deg)';
		}

		if ( ! empty( $s['overflowHidden'] ) ) {
			$wrapper['overflow'] = 'hidden';
		}

		$opacity = $this->number( $s['opacity'] ?? null, 0, 1 );

		if ( null !== $opacity && '1' !== $opacity ) {
			$wrapper['opacity'] = $opacity;
		}

		$hover = isset( $s['hover'] ) && is_string( $s['hover'] ) ? $s['hover'] : '';

		if ( in_array( $hover, array( 'lift', 'grow', 'glow' ), true ) ) {
			$wrapper['transition'] = 'transform .25s ease,box-shadow .25s ease';
		}

		// These widgets own their own padding and background on the inner element.
		if ( ! in_array( $type, array( 'button', 'iconbox', 'tabs', 'accordion' ), true ) ) {
			$wrapper['padding']          = $this->px( $s['padding'] ?? null, 0, 400 );
			$wrapper['background-color'] = $this->color( $s['background'] ?? null );
			$wrapper['border-radius']    = $this->px( $s['radius'] ?? null, 0, 200 );
			$wrapper['min-height']       = $this->px( $s['minHeight'] ?? null, 0, 2000 );

			$gradient_from = $this->color( $s['gradientFrom'] ?? null );
			$gradient_to   = $this->color( $s['gradientTo'] ?? null );

			if ( null !== $gradient_from && null !== $gradient_to ) {
				$angle = is_numeric( $s['gradientAngle'] ?? null )
					? max( 0, min( 360, (float) $s['gradientAngle'] ) )
					: 135;

				$wrapper['background-image'] = 'linear-gradient(' . $this->trim( $angle ) . 'deg,' . $gradient_from . ',' . $gradient_to . ')';
			} else {
				$background_image = $this->background_url( $s['backgroundImage'] ?? null );

				if ( null !== $background_image ) {
					$wrapper['background-image']    = 'url("' . $background_image . '")';
					$wrapper['background-size']     = 'cover';
					$wrapper['background-position'] = 'center';
					$wrapper['background-repeat']   = 'no-repeat';
				}
			}

			$border_width = $this->px( $s['borderWidth'] ?? null, 0, 40 );
			$border_color = $this->color( $s['borderColor'] ?? null );

			if ( null !== $border_width && '0px' !== $border_width && null !== $border_color ) {
				$wrapper['border'] = $border_width . ' solid ' . $border_color;
			}

			$shadow = $this->shadow( $s['shadow'] ?? null );

			if ( null !== $shadow ) {
				$wrapper['box-shadow'] = $shadow;
			}
		}

		if ( 'container' === $type ) {
			$max_width = $this->px( $s['maxWidth'] ?? null, 0, 3000 );

			if ( null !== $max_width && '0px' !== $max_width ) {
				$wrapper['max-width'] = $max_width;

				if ( ! isset( $s['boxWidth'] ) || ! is_numeric( $s['boxWidth'] ) ) {
					$wrapper['width'] = '100%';
				}

				if ( ! isset( $s['margin'] ) ) {
					$wrapper['margin-left']  = 'auto';
					$wrapper['margin-right'] = 'auto';
				}
			}

			$layout = isset( $s['layout'] ) && is_string( $s['layout'] ) ? $s['layout'] : 'flex';

			$wrapper['display']         = 'grid' === $layout ? 'grid' : ( 'free' === $layout ? 'block' : 'flex' );
			$wrapper['gap']             = $this->px( $s['gap'] ?? null, 0, 200 );
			$wrapper['align-items']     = $this->align_items( $s['alignItems'] ?? null );
			$wrapper['justify-content'] = $this->justify( $s['justify'] ?? null );

			if ( 'block' === $wrapper['display'] ) {
				$wrapper['position'] = $wrapper['position'] ?? 'relative';
				unset( $wrapper['gap'], $wrapper['align-items'], $wrapper['justify-content'] );
			} elseif ( 'grid' === $wrapper['display'] ) {
				$wrapper['grid-template-columns'] = 'repeat(' . $this->columns( $s['columns'] ?? null, 3 ) . ',minmax(0,1fr))';
			} else {
				$wrapper['flex-direction'] = $this->direction( $s['direction'] ?? null );

				if ( ! empty( $s['wrap'] ) ) {
					$wrapper['flex-wrap'] = 'wrap';
				}
			}
		}

		$css .= $this->rule( $base, $wrapper );
		switch ( $hover ) {
			case 'lift':
				$css .= $this->rule( $base . ':hover', array( 'transform' => 'translateY(-6px)', 'box-shadow' => '0 24px 48px -24px rgba(30,23,53,.35)' ) );
				break;
			case 'grow':
				$css .= $this->rule( $base . ':hover', array( 'transform' => 'scale(1.03)' ) );
				break;
			case 'glow':
				$css .= $this->rule( $base . ':hover', array( 'box-shadow' => '0 0 0 4px color-mix(in srgb,var(--br-color-primary,#7c5cfc) 28%,transparent)' ) );
				break;
		}

		switch ( $type ) {
			case 'heading':
				$css .= $this->rule(
					$base . ' .br-heading',
					array(
						'font-size'      => $this->px( $s['fontSize'] ?? null, 8, 300 ),
						'font-weight'    => $this->weight( $s['fontWeight'] ?? null ),
						'line-height'    => $this->number( $s['lineHeight'] ?? null, 0.7, 3 ),
						'letter-spacing' => $this->px( $s['letterSpacing'] ?? null, -20, 40 ),
						'margin'         => '0',
					)
				);
				break;

			case 'text':
				$css .= $this->rule(
					$base . ' .br-text',
					array(
						'font-size'   => $this->px( $s['fontSize'] ?? null, 8, 200 ),
						'line-height' => $this->number( $s['lineHeight'] ?? null, 0.7, 3 ),
					)
				);
				break;

			case 'button':
				$padding   = $this->px( $s['padding'] ?? 14, 0, 100 );
				$padding_x = $this->px( (float) ( $s['padding'] ?? 14 ) * 2, 0, 200 );

				$css .= $this->rule(
					$base . ' .br-btn',
					array(
						'display'          => 'inline-block',
						'background-color' => $this->color( $s['background'] ?? null ),
						'color'            => $this->color( $s['color'] ?? null ),
						'border-radius'    => $this->px( $s['radius'] ?? null, 0, 200 ),
						'padding'          => null !== $padding ? $padding . ' ' . ( $padding_x ?? $padding ) : null,
						'font-size'        => $this->px( $s['fontSize'] ?? null, 8, 80 ),
						'font-weight'      => '600',
						'text-decoration'  => 'none',
						'line-height'      => '1.2',
					)
				);
				break;

			case 'image':
				$width = $this->percent( $s['width'] ?? null );

				$css .= $this->rule(
					$base . ' img',
					array(
						'width'         => $width,
						'max-width'     => '100%',
						'height'        => 'auto',
						'border-radius' => $this->px( $s['radius'] ?? null, 0, 400 ),
					)
				);
				break;

			case 'divider':
				$thickness     = $this->px( $s['thickness'] ?? 1, 1, 40 ) ?? '1px';
				$divider_color = $this->color( $s['color'] ?? null ) ?? '#e8e1f7';

				$css .= $this->rule(
					$base . ' .br-divider',
					array(
						'border'     => '0',
						'border-top' => $thickness . ' solid ' . $divider_color,
						'width'      => $this->percent( $s['width'] ?? null ),
						'margin'     => '0 auto',
					)
				);
				break;

			case 'spacer':
				$css .= $this->rule(
					$base,
					array(
						'height' => is_numeric( $s['boxHeight'] ?? null ) ? null : $this->px( $s['height'] ?? null, 0, 600 ),
					)
				);
				break;

			case 'iconbox':
				$css .= $this->rule(
					$base . ' .br-iconbox',
					array(
						'background-color' => $this->color( $s['background'] ?? null ),
						'border-radius'    => $this->px( $s['radius'] ?? null, 0, 200 ),
						'padding'          => $this->px( $s['padding'] ?? null, 0, 200 ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-iconbox-icon',
					array(
						'color'     => $this->color( $s['iconColor'] ?? null ),
						'font-size' => $this->px( $s['iconSize'] ?? null, 8, 200 ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-iconbox-title',
					array(
						'color'     => $this->color( $s['titleColor'] ?? null ),
						'font-size' => $this->px( $s['titleSize'] ?? null, 8, 100 ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-iconbox-text',
					array(
						'color'     => $this->color( $s['textColor'] ?? null ),
						'font-size' => $this->px( $s['textSize'] ?? null, 8, 100 ),
					)
				);
				break;

			case 'icon':
				$css .= $this->rule(
					$base . ' .br-icon',
					array(
						'color'     => $this->color( $s['iconColor'] ?? null ),
						'font-size' => $this->px( $s['iconSize'] ?? null, 8, 300 ),
					)
				);
				break;

			case 'iconlist':
				$css .= $this->rule(
					$base . ' .br-icon-list',
					array(
						'list-style'    => 'none',
						'margin'        => '0',
						'padding'       => '0',
						'display'       => 'flex',
						'flex-direction' => 'column',
						'gap'           => $this->px( $s['gap'] ?? null, 0, 100 ),
						'font-size'     => $this->px( $s['fontSize'] ?? null, 8, 100 ),
						'color'         => $this->color( $s['textColor'] ?? null ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-icon-list li',
					array(
						'display'     => 'flex',
						'align-items' => 'center',
						'gap'         => $this->px( $s['iconGap'] ?? 10, 0, 60 ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-icon-list-icon',
					array(
						'flex'      => 'none',
						'color'     => $this->color( $s['iconColor'] ?? null ),
						'font-size' => $this->px( $s['iconSize'] ?? null, 8, 100 ),
					)
				);
				break;

			case 'video':
				$css .= $this->rule(
					$base . ' .br-video',
					array(
						'border-radius' => $this->px( $s['radius'] ?? null, 0, 200 ),
						'overflow'      => 'hidden',
						'aspect-ratio'  => $this->ratio( $s['ratio'] ?? null ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-video iframe, ' . $base . ' .br-video video',
					array(
						'width'   => '100%',
						'height'  => '100%',
						'border'  => '0',
						'display' => 'block',
					)
				);
				break;

			case 'stars':
				$css .= $this->rule(
					$base . ' .br-stars',
					array(
						'display' => 'inline-flex',
						'gap'     => '4px',
						'color'   => $this->color( $s['color'] ?? null ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-star',
					array(
						'width'  => $this->px( $s['size'] ?? null, 8, 100 ),
						'height' => $this->px( $s['size'] ?? null, 8, 100 ),
					)
				);
				break;

			case 'progress':
				$css .= $this->rule(
					$base . ' .br-progress-head',
					array(
						'font-size' => $this->px( $s['fontSize'] ?? null, 8, 100 ),
						'color'     => $this->color( $s['textColor'] ?? null ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-progress-track',
					array(
						'height'           => $this->px( $s['height'] ?? null, 2, 60 ),
						'border-radius'    => $this->px( $s['radius'] ?? null, 0, 99 ),
						'background-color' => $this->color( $s['trackColor'] ?? null ),
					)
				);
				$css .= $this->rule(
					$base . ' .br-progress-fill',
					array(
						'border-radius'    => $this->px( $s['radius'] ?? null, 0, 99 ),
						'background-color' => $this->color( $s['color'] ?? null ),
					)
				);
				break;

			case 'tabs':
				$border      = $this->color( $s['borderColor'] ?? null ) ?? '#e8e1f7';
				$background  = $this->color( $s['background'] ?? null ) ?? '#f7f4fe';
				$active      = $this->color( $s['activeColor'] ?? null ) ?? '#7c5cfc';
				$text        = $this->color( $s['textColor'] ?? null ) ?? '#4c4463';
				$radius      = $this->px( $s['radius'] ?? null, 0, 40 );
				$nav_radius  = null !== $radius ? $radius . ' ' . $radius . ' 0 0' : null;
				$font_size   = $this->px( $s['fontSize'] ?? null, 8, 40 );
				$panel_pad   = $this->px( $s['padding'] ?? null, 0, 200 );

				$css .= $this->rule(
					$base . ' .br-tabs-nav',
					array(
						'display'     => 'flex',
						'flex-wrap'   => 'wrap',
						'gap'         => '4px',
						'border-bottom' => '1px solid ' . $border,
					)
				);
				$css .= $this->rule(
					$base . ' .br-tabs-nav button',
					array(
						'appearance'   => 'none',
						'border'       => '1px solid transparent',
						'border-bottom' => '0',
						'border-radius' => $nav_radius,
						'padding'      => '10px 16px',
						'background'   => 'transparent',
						'color'        => $text,
						'font-size'    => $font_size,
						'font-weight'  => '600',
						'cursor'       => 'pointer',
					)
				);
				$css .= $this->rule(
					$base . ' .br-tabs-nav button.is-active',
					array(
						'background'  => $background,
						'color'       => $active,
						'border-color' => $border,
					)
				);
				$css .= $this->rule(
					$base . ' .br-tab-panel',
					array(
						'padding'   => $panel_pad,
						'font-size' => $font_size,
						'color'     => $text,
					)
				);
				$css .= $this->rule( $base . ' .br-tab-panel[hidden]', array( 'display' => 'none' ) );
				break;

			case 'accordion':
				$border     = $this->color( $s['borderColor'] ?? null ) ?? '#e8e1f7';
				$background = $this->color( $s['background'] ?? null ) ?? '#f7f4fe';
				$title      = $this->color( $s['titleColor'] ?? null ) ?? '#1e1735';
				$text       = $this->color( $s['textColor'] ?? null ) ?? '#4c4463';
				$radius     = $this->px( $s['radius'] ?? null, 0, 40 );
				$font_size  = $this->px( $s['fontSize'] ?? null, 8, 40 );
				$content_pad = $this->px( $s['padding'] ?? null, 0, 200 ) ?? '16px';

				$css .= $this->rule(
					$base . ' .br-accordion',
					array(
						'display'        => 'flex',
						'flex-direction' => 'column',
						'gap'            => '8px',
					)
				);
				$css .= $this->rule(
					$base . ' .br-accordion-item',
					array(
						'border'       => '1px solid ' . $border,
						'border-radius' => $radius,
						'background'   => $background,
						'overflow'     => 'hidden',
					)
				);
				$css .= $this->rule(
					$base . ' summary',
					array(
						'padding'    => '12px 16px',
						'cursor'     => 'pointer',
						'font-weight' => '600',
						'color'      => $title,
						'font-size'  => $font_size,
					)
				);
				$css .= $this->rule(
					$base . ' .br-accordion-content',
					array(
						'padding'   => '0 16px ' . $content_pad,
						'color'     => $text,
						'font-size' => $font_size,
					)
				);
				$css .= $this->rule( $base . ' .br-accordion-content > :first-child', array( 'margin-top' => '0' ) );
				$css .= $this->rule( $base . ' .br-accordion-content > :last-child', array( 'margin-bottom' => '0' ) );
				break;
		}

		return $css;
	}

	/**
	 * Copy breakpoint values over their base keys.
	 *
	 * @param array<string, mixed> $node   Node.
	 * @param string               $suffix Suffix, e.g. _tablet.
	 * @return array<string, mixed>
	 */
	private function breakpoint_node( array $node, string $suffix ): array {
		if ( ! isset( $node['settings'] ) || ! is_array( $node['settings'] ) ) {
			return $node;
		}

		$settings = $node['settings'];

		foreach ( $settings as $key => $value ) {
			if ( ! is_string( $key ) || ! str_ends_with( $key, $suffix ) ) {
				continue;
			}

			$base = substr( $key, 0, -strlen( $suffix ) );

			if ( '' !== $base ) {
				$settings[ $base ] = $value;
			}
		}

		$node['settings'] = $settings;

		return $node;
	}

	/**
	 * Grid column count.
	 *
	 * @param mixed $value   Value.
	 * @param int   $default Default.
	 * @return int
	 */
	private function columns( mixed $value, int $default ): int {
		return is_numeric( $value ) ? max( 1, min( 12, (int) $value ) ) : $default;
	}

	/**
	 * Scope and sanitize author supplied CSS.
	 *
	 * "selector" is replaced by the node selector. Input containing anything that
	 * could load remote code or break out of the stylesheet is dropped entirely.
	 *
	 * @param mixed  $value Raw CSS.
	 * @param string $base  Node selector.
	 * @return string
	 */
	public function custom_css( mixed $value, string $base ): string {
		if ( ! is_string( $value ) || '' === trim( $value ) || strlen( $value ) > 6000 ) {
			return '';
		}

		$css = wp_strip_all_tags( $value );

		if ( 1 === preg_match( '/@import|@charset|expression\s*\(|javascript:|vbscript:|behavior\s*:|-moz-binding|<|\\|\/\*/i', $css ) ) {
			return '';
		}

		if ( substr_count( $css, '{' ) !== substr_count( $css, '}' ) ) {
			return '';
		}

		$css = trim( $css );

		// Bare declarations apply to the node itself.
		if ( ! str_contains( $css, '{' ) ) {
			return $base . '{' . $css . '}';
		}

		return str_replace( 'selector', $base, $css );
	}
	/**
	 * Allowed aspect ratios.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function ratio( mixed $value ): string {
		$allowed = array( '16 / 9', '4 / 3', '1 / 1', '9 / 16' );

		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : '16 / 9';
	}

	/**
	 * Shadow presets.
	 *
	 * @param mixed $value Preset slug.
	 * @return string|null
	 */
	private function shadow( mixed $value ): ?string {
		$presets = array(
			'soft'   => '0 10px 30px -18px rgba(30,23,53,.35)',
			'medium' => '0 20px 50px -28px rgba(30,23,53,.45)',
			'strong' => '0 30px 70px -34px rgba(30,23,53,.55)',
		);

		return is_string( $value ) && isset( $presets[ $value ] ) ? $presets[ $value ] : null;
	}

	/**
	 * Absolute background image URL.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function background_url( mixed $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$url = esc_url_raw( $value );

		if ( '' === $url || 1 !== preg_match( '~^https?://~i', $url ) ) {
			return null;
		}

		return str_replace( array( '"', "'" ), '', $url );
	}

	/**
	 * Build a rule with filtered declarations.
	 *
	 * @param string                $selector     Selector.
	 * @param array<string, ?string> $declarations Declarations.
	 * @return string
	 */
	private function rule( string $selector, array $declarations ): string {
		$parts = array();

		foreach ( $declarations as $property => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}

			$parts[] = $property . ':' . $value;
		}

		if ( array() === $parts ) {
			return '';
		}

		return $selector . '{' . implode( ';', $parts ) . '}';
	}

	/**
	 * Pixels.
	 *
	 * @param mixed $value Value.
	 * @param float $min   Minimum.
	 * @param float $max   Maximum.
	 * @return string|null
	 */
	private function px( mixed $value, float $min, float $max ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$number = max( $min, min( $max, (float) $value ) );

		return $this->trim( $number ) . 'px';
	}

	/**
	 * Percentage with a custom maximum.
	 *
	 * @param mixed $value Value.
	 * @param float $min   Minimum.
	 * @param float $max   Maximum.
	 * @return string|null
	 */
	private function percent_max( mixed $value, float $min, float $max ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return $this->trim( max( $min, min( $max, (float) $value ) ) ) . '%';
	}

	/**
	 * Signed pixel offset.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function px_signed( mixed $value ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return $this->trim( max( -3000, min( 3000, (float) $value ) ) ) . 'px';
	}

	/**
	 * Z-index.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function z_index( mixed $value ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return (string) max( 0, min( 9999, (int) $value ) );
	}

	/**
	 * Percentage.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function percent( mixed $value ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$number = max( 1, min( 100, (float) $value ) );

		return $this->trim( $number ) . '%';
	}

	/**
	 * Unitless number.
	 *
	 * @param mixed $value Value.
	 * @param float $min   Minimum.
	 * @param float $max   Maximum.
	 * @return string|null
	 */
	private function number( mixed $value, float $min, float $max ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		return $this->trim( max( $min, min( $max, (float) $value ) ) );
	}

	/**
	 * Hex colour.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function color( mixed $value ): ?string {
		return DesignSystem::resolve_color( $value );
	}

	/**
	 * Text alignment.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function align( mixed $value ): ?string {
		return is_string( $value ) && in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : null;
	}

	/**
	 * Flex direction.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function direction( mixed $value ): string {
		return 'row' === $value ? 'row' : 'column';
	}

	/**
	 * Align items.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function align_items( mixed $value ): string {
		return is_string( $value ) && in_array( $value, array( 'flex-start', 'center', 'flex-end', 'stretch' ), true )
			? $value
			: 'stretch';
	}

	/**
	 * Justify content.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function justify( mixed $value ): string {
		return is_string( $value ) && in_array( $value, array( 'flex-start', 'center', 'flex-end', 'space-between' ), true )
			? $value
			: 'flex-start';
	}

	/**
	 * Font weight.
	 *
	 * @param mixed $value Value.
	 * @return string|null
	 */
	private function weight( mixed $value ): ?string {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$weight = (int) $value;
		$weight = (int) ( round( $weight / 100 ) * 100 );

		return (string) max( 100, min( 900, $weight ) );
	}

	/**
	 * Trim trailing zeros.
	 *
	 * @param float $number Number.
	 * @return string
	 */
	private function trim( float $number ): string {
		return rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' );
	}
}
