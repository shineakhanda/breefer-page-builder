<?php
/**
 * Widget registry.
 *
 * @package Breefer
 */

namespace Breefer\Rendering;

use Breefer\Widgets\AbstractWidget;
use Breefer\Widgets\Accordion;
use Breefer\Widgets\Button;
use Breefer\Widgets\Container;
use Breefer\Widgets\Divider;
use Breefer\Widgets\Heading;
use Breefer\Widgets\Icon;
use Breefer\Widgets\IconBox;
use Breefer\Widgets\IconList;
use Breefer\Widgets\Image;
use Breefer\Widgets\ProgressBar;
use Breefer\Widgets\Spacer;
use Breefer\Widgets\StarRating;
use Breefer\Widgets\Tabs;
use Breefer\Widgets\Text;
use Breefer\Widgets\Video;
use Breefer\Widgets\WooCategories;
use Breefer\Widgets\WooMiniCart;
use Breefer\Widgets\WooProductField;
use Breefer\Widgets\WooProducts;
use Breefer\Widgets\Alert;
use Breefer\Widgets\ArchiveTitle;
use Breefer\Widgets\Breadcrumbs;
use Breefer\Widgets\CallToAction;
use Breefer\Widgets\Counter;
use Breefer\Widgets\Countdown;
use Breefer\Widgets\FlipBox;
use Breefer\Widgets\FormWidget;
use Breefer\Widgets\Gallery;
use Breefer\Widgets\HtmlBlock;
use Breefer\Widgets\LoginForm;
use Breefer\Widgets\MapEmbed;
use Breefer\Widgets\NavMenu;
use Breefer\Widgets\PostField;
use Breefer\Widgets\PostsGrid;
use Breefer\Widgets\PriceTable;
use Breefer\Widgets\SearchForm;
use Breefer\Widgets\Shape;
use Breefer\Widgets\SiteIdentity;
use Breefer\Widgets\SocialLinks;
use Breefer\Widgets\Testimonial;
use Breefer\Widgets\WooPage;
use Breefer\Widgets\WooProductExtras;

defined( 'ABSPATH' ) || exit;

/**
 * Maps widget slugs to server-side renderers.
 */
final class WidgetRegistry {

	/**
	 * Registered widgets.
	 *
	 * @var array<string, AbstractWidget>
	 */
	private array $widgets = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_defaults();
	}

	/**
	 * Register the core widgets.
	 *
	 * @return void
	 */
	private function register_defaults(): void {
		$this->register( new Container() );
		$this->register( new Heading() );
		$this->register( new Text() );
		$this->register( new Button() );
		$this->register( new Image() );
		$this->register( new Divider() );
		$this->register( new Spacer() );
		$this->register( new Shape() );
		$this->register( new IconBox() );
		$this->register( new Icon() );
		$this->register( new IconList() );
		$this->register( new Video() );
		$this->register( new StarRating() );
		$this->register( new ProgressBar() );
		$this->register( new Tabs() );
		$this->register( new Accordion() );
		$this->register( new WooProducts() );
		$this->register( new WooProductField() );
		$this->register( new WooMiniCart() );
		$this->register( new WooCategories() );

		// Pro content widgets.
		$this->register( new Counter() );
		$this->register( new Testimonial() );
		$this->register( new PriceTable() );
		$this->register( new CallToAction() );
		$this->register( new Alert() );
		$this->register( new SocialLinks( 'social' ) );
		$this->register( new SocialLinks( 'share' ) );
		$this->register( new Countdown() );
		$this->register( new FlipBox() );
		$this->register( new Gallery( 'gallery' ) );
		$this->register( new Gallery( 'marquee' ) );
		$this->register( new MapEmbed() );
		$this->register( new FormWidget() );
		$this->register( new HtmlBlock() );

		// Theme builder widgets.
		$this->register( new SiteIdentity( 'sitelogo' ) );
		$this->register( new SiteIdentity( 'sitetitle' ) );
		$this->register( new NavMenu() );
		$this->register( new SearchForm() );
		$this->register( new PostField() );
		$this->register( new PostsGrid() );
		$this->register( new Breadcrumbs() );
		$this->register( new ArchiveTitle() );
		$this->register( new LoginForm() );

		// WooCommerce page and product widgets.
		$this->register( new WooPage( 'woocarttable' ) );
		$this->register( new WooPage( 'woocheckout' ) );
		$this->register( new WooPage( 'wooaccount' ) );

		foreach ( WooProductExtras::TYPES as $kind ) {
			$this->register( new WooProductExtras( $kind ) );
		}
	}

	/**
	 * Register a widget.
	 *
	 * @param AbstractWidget $widget Widget.
	 * @return void
	 */
	public function register( AbstractWidget $widget ): void {
		$this->widgets[ $widget->type() ] = $widget;
	}

	/**
	 * Whether a type is registered.
	 *
	 * @param string $type Widget type.
	 * @return bool
	 */
	public function has( string $type ): bool {
		return isset( $this->widgets[ $type ] );
	}

	/**
	 * Get a widget.
	 *
	 * @param string $type Widget type.
	 * @return AbstractWidget|null
	 */
	public function get( string $type ): ?AbstractWidget {
		return $this->widgets[ $type ] ?? null;
	}

	/**
	 * Registered type slugs.
	 *
	 * @return array<int, string>
	 */
	public function types(): array {
		return array_keys( $this->widgets );
	}

	/**
	 * Render a widget.
	 *
	 * @param string               $type          Widget type.
	 * @param array<string, mixed> $settings      Settings.
	 * @param string               $children_html Rendered children.
	 * @param string               $node_id       Node id.
	 * @return string
	 */
	public function render( string $type, array $settings, string $children_html, string $node_id ): string {
		$widget = $this->get( $type );

		if ( null === $widget ) {
			return '';
		}

		$html = $widget->render( $settings, $children_html, $node_id );

		/**
		 * Filter a rendered widget.
		 *
		 * @param string         $html     Rendered markup.
		 * @param AbstractWidget $widget   Widget instance.
		 * @param array          $settings Widget settings.
		 * @param string         $node_id  Node id.
		 */
		return (string) apply_filters( 'breefer/render/widget', $html, $widget, $settings, $node_id );
	}
}
