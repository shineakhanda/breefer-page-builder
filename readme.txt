=== Breefer ===
Contributors: breefer
Tags: page builder, woocommerce, react, custom blocks, performance
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 0.6.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A performance-first, developer-friendly page builder with native WooCommerce templates, custom React blocks, and zero frontend framework runtime.

== Description ==

Breefer is a page builder built around a single constraint: the published page should cost as little as possible.

The editor is a React application. The frontend is not. Breefer renders a JSON element tree to semantic HTML in PHP and ships only the CSS those nodes actually use.

**Available in this build**

* Full-screen React editor with a live canvas, widget library, inspector, navigator, drag-to-reorder, undo/redo, autosave and keyboard shortcuts.
* Breefer is the default editor for new pages and posts, with admin-bar links and “Edit with Breefer” row actions. Existing content can still use the WordPress editor.
* Theme, full-width and blank canvas page layouts that work with classic and block themes.
* Manual resize handles plus width, height, position, offsets and z-index controls for deliberate overlap.
* Per-breakpoint desktop, tablet and mobile values, automatic mobile stacking for row containers, plus hide-on-device controls.
* A searchable built-in SVG icon library (50 icons), extendable through the `breefer/icons` filter.
* Save and load over the `breefer/v1` REST API with nonce and capability checks.
* More than fifty server-side widgets across layout, content, pro, theme-builder and WooCommerce groups (see below).
* A global design system: site-wide colours, type scale, radius and content width exposed as CSS custom properties. Any colour control can point at a global colour, so one change restyles the whole site.
* A section library: page kits (landing, store home, about), content sections, WooCommerce layouts and theme-builder layouts you can insert in one click.
* A theme builder: header, footer, single post, single page, archive, search and 404 templates with display conditions, working on classic and block themes.
* Container grid layout, sticky position, entrance animations, hover effects, opacity, clip overflow, anchor IDs, CSS classes, logged-in/out visibility and scoped custom CSS on every node.
* Live editor previews: widgets that depend on site data are rendered by the server inside the canvas, so you see real products, menus and posts while designing.
* WooCommerce store building: native product loop with AJAX add-to-cart, dynamic single-product fields, mini cart, category list, and shop/single-product template hijacks.
* Container gradients, background images, borders and shadows.
* Per-node CSS generated at render time. A basic page ships zero JavaScript.
* Enable Breefer on any page or post from the editor meta box; saving from the builder switches it on.
* Reusable template post type, the `[breefer id="123"]` shortcode, and a “Breefer Canvas” block for the block editor.
* Multisite-safe activation, validated settings, and complete data removal on uninstall.

**WooCommerce store building**

Create a template, open the root settings panel and set “WooCommerce template” to Shop archive or Single product. Add Product grid, Product field, Mini cart or Product categories, then save. The most recently edited template of each type replaces WooCommerce’s matching template while keeping the theme header and footer. Product grids use WooCommerce’s native loop and AJAX add-to-cart, and product fields pull live title, price, image, description, gallery, SKU, rating and add-to-cart forms.

**Widgets**

* Pro: Counter, Testimonial, Price table, Call to action, Alert, Social icons, Share buttons, Countdown, Flip box, Image gallery, Logo marquee, Map, Contact form (emails submissions, honeypot and rate limit), HTML & shortcode.
* Theme builder: Site logo, Site title, Navigation menu, Search form, Post field, Posts grid (custom or main query with pagination), Breadcrumbs (with structured data), Archive title, Login form.
* WooCommerce: Product grid (latest, featured, sale, best selling, top rated, IDs, main query, carousel), Product field (title, price, gallery with zoom and lightbox, add to cart, attributes and more), Mini cart with live fragments, Product categories, Cart, Checkout, My account, Product tabs, Sale and stock badges, Related / upsell / cross-sell products, Result count and sorting, Sticky add-to-cart bar.

**WooCommerce optimisation**

* Shop, category, single product, cart, checkout and My account templates, with the native WooCommerce flows (and Cart/Checkout blocks) rendered inside your design.
* Declares compatibility with High-Performance Order Storage and the Cart/Checkout blocks.
* Cart count and subtotal update through WooCommerce cart fragments after AJAX add-to-cart.
* WooCommerce scripts and styles load only on pages that need them.

**Roadmap to Elementor parity**

* Popups, loop builder and motion effects tied to scroll.
* Dynamic tags, ACF fields and custom font uploads.
* Custom React/Next.js blocks with Sucrase and deferred island hydration.
* Critical CSS extraction, render caching, cache-plugin purges, and the SEO/translation compatibility layer.

**Performance budget**

| Metric | Target |
| --- | --- |
| Frontend JS, basic page | 0 KB |
| Frontend CSS, basic page | under 20 KB gzipped |
| Lighthouse Performance, 10 widgets | 95 or higher |
| TTFB delta vs plain WordPress | under 5 ms |

Breefer enforces this by design: no frontend CSS framework, no jQuery, per-node scoped CSS, and no page-builder runtime on the frontend.

**Developer API**

Filter rendered widget markup:

`add_filter( 'breefer/render/widget', function ( $html, $widget, $settings ) { return $html; }, 10, 3 );`

Filter a whole rendered document:

`add_filter( 'breefer/render/document', function ( $result, $document, $post_id ) { return $result; }, 10, 3 );`

Register custom icons for the editor library:

`add_filter( 'breefer/icons', function ( $icons ) { $icons['rocket'] = array( 'label' => 'Rocket', 'paths' => array( 'M12 2c4 4 4 10 0 14-4-4-4-10 0-14Z' ) ); return $icons; } );`

== Installation ==

1. Upload the `breefer` folder to `/wp-content/plugins/`, or install the ZIP from Plugins > Add New.
2. Activate the plugin through the Plugins screen.
3. Open Breefer → Add template, or open any page and use the Breefer meta box.
4. Build on the canvas and press Save. Pages render through Breefer immediately after the first save.

WooCommerce support activates automatically when WooCommerce is installed.

== Frequently Asked Questions ==

= Does Breefer require WooCommerce? =

No. WooCommerce widgets and template hijacks stay dormant until WooCommerce is active.

= Does the frontend load React? =

Only for widgets that declare an island and are visible. A page with no interactive widgets ships no React at all.

= Can I use my own React components? =

Yes. The custom code block compiles JSX/TSX in the browser with Sucrase, renders the component in a Shadow DOM island, and loads the compiled code only when the block scrolls into view.

= Does it work with my cache plugin? =

Breefer purges through the native hooks of WP Rocket, LiteSpeed Cache, W3 Total Cache, and WP Super Cache when a template is saved.

= Is the plugin multisite compatible? =

Yes. Activation, deactivation, and uninstall run per site, and network-wide activation installs defaults across the network.

= What does Breefer store? =

Templates, revisions, settings, and generated assets inside `/wp-content/uploads/breefer/`. Uninstalling removes all of it.

== Screenshots ==

1. The Breefer dashboard with environment checks and the shared design language.
2. Appearance settings, using the same palette family as Minimal Admin.

== Changelog ==

= 0.6.0 =
* Free-form canvas, like an image editor: drag any widget anywhere, overlap freely, resize from eight handles, rotate, snap to edges and centres with guide lines, align, change layer order, lock, zoom and nudge with the arrow keys.
* New Free canvas container layout and a Shape widget for overlays and decoration.
* Double-click Heading, Text and Button widgets to edit them directly on the canvas.
* Positions, sizes and rotation are stored per device, so a layout can differ on tablet and mobile.
* Fixed editor display bugs: row and grid containers now look the same in the editor as on the site (the old drag wrappers changed their sizing), grid, sticky, opacity and rotation are mirrored, and the selection toolbar is no longer clipped.
* Relicensed under the GNU General Public License v3.0 or later. Added a usage guide.

= 0.5.0 =
* Design system: global colours, typography, radius and width, with global colour swatches in every colour control.
* Section library with page kits, WooCommerce layouts and theme-builder layouts.
* Theme builder: header, footer, post, page, archive, search and 404 templates plus WooCommerce shop, product, cart, checkout and account templates, with display conditions.
* 31 new widgets (pro content, theme builder and WooCommerce) and a server-rendered live preview in the canvas.
* Node-level grid layout, sticky position, animations, hover effects, visibility rules and scoped custom CSS.
* WooCommerce: HPOS and Cart/Checkout block compatibility declarations, live cart fragments, main-query product loop, product gallery with zoom and lightbox, sticky add-to-cart bar.
* Fixed multi-line widget settings (Tabs and Accordion items) losing their line breaks on save.
* Fixed the Woo template canvas for block themes, which now outputs a complete document.

= 0.4.0 =
* Breefer is now the default editor for new pages and posts, with admin-bar links, row actions and a New page menu.
* Theme, full-width and canvas page layouts for classic and block themes.
* Tabs and Accordion widgets, with a 1 KB frontend tab island.
* Navigator tree in the editor sidebar.
* Row containers stack automatically on phones; layout controls inherit across devices.

= 0.3.0 =
* Manual resizing with a drag handle, width/height controls, CSS position, offsets and z-index for overlap.
* WooCommerce store building: product grid with native AJAX add-to-cart, dynamic product field widget, mini cart and product categories.
* Shop and single-product template hijacks, assignable inside the builder and rendered through the active theme.
* Improved per-breakpoint layout: layout controls now inherit across devices like style controls.

= 0.2.0 =
* Responsive editing: desktop, tablet and mobile values with inherited breakpoints and hide-on-device controls.
* Built-in 50-icon SVG library with a searchable picker and the `breefer/icons` extension filter.
* New widgets: Icon, Icon list, Video, Star rating and Progress bar.
* Container gradients, background images, borders and shadow presets.
* Minimal Admin compatibility: the admin shell no longer overlaps the full-screen editor.

= 0.1.0 =
* Builder core: template post type, REST save/load, PHP renderer, eight widgets, full-screen React editor with drag-to-reorder and inspector, page/post integration, shortcode and block editor block.
* Multisite-safe activation, capability management, settings, design tokens and complete uninstall.

== Upgrade Notice ==

= 0.4.0 =
Makes Breefer the default editor for new content, adds theme/full-width/canvas layouts, Tabs, Accordion, the Navigator and mobile stacking.

= 0.3.0 =
Adds manual resize/overlap controls, WooCommerce store widgets and shop/single-product templates.

= 0.2.0 =
Adds responsive editing, the icon library, five widgets, container gradients and Minimal Admin compatibility.

= 0.1.0 =
Initial foundation release.
