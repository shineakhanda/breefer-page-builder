# Breefer usage guide

Breefer is a visual page builder for WordPress and WooCommerce. You design on a
canvas, and the plugin renders your design to plain, fast HTML on the frontend.

**Contents**

1. [Install and first run](#1-install-and-first-run)
2. [The editor at a glance](#2-the-editor-at-a-glance)
3. [Placing widgets freely (Canva-style)](#3-placing-widgets-freely-canva-style)
4. [Flow layouts: containers, grid, rows](#4-flow-layouts-containers-grid-rows)
5. [Responsive design](#5-responsive-design)
6. [Design system (global colours and type)](#6-design-system-global-colours-and-type)
7. [Section library and page kits](#7-section-library-and-page-kits)
8. [Theme builder](#8-theme-builder)
9. [WooCommerce](#9-woocommerce)
10. [Widget reference](#10-widget-reference)
11. [Motion, effects, visibility and custom CSS](#11-motion-effects-visibility-and-custom-css)
12. [Keyboard shortcuts](#12-keyboard-shortcuts)
13. [Shortcode, block and developer hooks](#13-shortcode-block-and-developer-hooks)
14. [Troubleshooting](#14-troubleshooting)
15. [License](#15-license)

---

## 1. Install and first run

**Requirements:** WordPress 6.2+, PHP 8.2+. WooCommerce is optional.

1. In WordPress go to **Plugins → Add New → Upload Plugin**, choose
   `breefer-<version>.zip`, install and **Activate**.
2. Open **Breefer** in the admin menu. The dashboard shows environment checks.
3. Create something:
   - **Breefer → New page** starts a page in the builder.
   - **Breefer → Add template** creates a reusable template (headers, footers,
     product pages and so on).
   - Any existing page or post has a **Breefer** meta box and an
     **Edit with Breefer** link.

Saving from the builder turns Breefer on for that page. Your original content is
kept and comes back if you switch Breefer off in the meta box.

> Developing from source? Run `npm install` then `npm run build` in the plugin
> folder to rebuild the editor. The packaged ZIP already contains the build.

## 2. The editor at a glance

| Area | What it does |
| --- | --- |
| **Top bar** | Title, device switcher (desktop / tablet / mobile), undo/redo, preview, save and publish. |
| **Left panel** | **Widgets** (search and click to add), **Sections** (ready-made blocks) and **Navigator** (tree of the page). |
| **Canvas** | Your page. Click to select, drag to move. Zoom and Snap controls sit at the top of the canvas. |
| **Right panel** | Settings for the selected item: content, layout, style, motion and visibility. Select the page (root) to set the template type or page layout. |

Changes autosave on an interval (set in **Breefer → Settings → Editor**), and
`Ctrl+S` saves immediately.

## 3. Placing widgets freely (Canva-style)

Any widget can be placed anywhere, overlapping others, with no layout rules.

### Move

Drag a widget by its body. The first time you move a widget that sits in the
normal layout flow it becomes a **free** widget (it is taken out of the flow at
exactly the place it was, and its parent keeps its height so nothing jumps).
Containers in the flow are moved with the **⠿ handle** on the toolbar, or turned
free with the **Flow / Free** button.

- **Snapping:** edges and centres snap to the parent and to sibling widgets, with
  pink guide lines. Hold **Alt** to move without snapping. Toggle **Snap** at the
  top of the canvas.
- **Hold Shift** while dragging to lock to one axis.
- **Arrow keys** nudge a free widget by 1px (Shift = 10px).

### Resize and rotate

- Eight handles resize from every edge and corner. **Hold Shift on a corner** to
  keep the aspect ratio.
- The **⟳ knob** above the selection rotates the widget. **Hold Shift** to snap to
  15° steps. You can also type a value in **Layout → Rotation**.
- A size tag under the selection shows width × height (and angle).

### Toolbar

Select a widget and use the floating toolbar:

| Control | Action |
| --- | --- |
| ⠿ | Drag to reorder inside the flow (a line or box shows where it will drop). |
| **Free / Flow** | Switch between free positioning and normal layout flow. |
| ⇤ ↔ ⇥ ⤒ ↕ ⤓ | Align to the parent: left, centre, right, top, middle, bottom. |
| ⤊ ↑ ↓ ⤋ | Bring to front, forward, backward, send to back (free widgets). |
| **Lock** | Prevent accidental moves and resizes. |
| **Copy / Delete** | Duplicate (a free copy is offset slightly) or remove. |

### Free canvas container

For a page where everything is free, add a **Container** and set **Layout → Free
canvas (place anywhere)**, then give it a **Min height**. Widgets you add to it
are placed freely and can be dragged anywhere inside. The **Sections → Page kits →
Free canvas** preset gives you a starting artboard.

Use the **Shape** widget (boxes, circles with radius 200, pills) for decoration,
overlays and backgrounds.

### Edit text in place

Double-click a **Heading**, **Text** or **Button** to type directly on the
canvas. **Enter** (or clicking away) saves, **Esc** cancels.

### Zoom

Use **− / % / +** at the top of the canvas, `Ctrl` + mouse wheel, or
`Ctrl +` / `Ctrl -`. Click the percentage (or press `Ctrl 0`) to fit the page to
the available width.

## 4. Flow layouts: containers, grid, rows

A **Container** holds other widgets. Choose its **Layout**:

- **Flex:** a row or a column with gap, alignment and justify controls. Rows stack
  automatically on phones.
- **Grid:** a number of equal columns (3 by default; 2 on tablets and 1 on phones
  unless you set a value for that device).
- **Free canvas:** children are placed freely (see above).

Every widget also has **Width**, **Height**, **Margin**, **Padding**, **Position**,
offsets, **Z-index**, background, gradient, border, radius and shadow controls.

## 5. Responsive design

Use the device switcher in the top bar. Changes you make while **Tablet** or
**Mobile** is active are stored for that device only; other devices keep
inheriting (mobile inherits from tablet, which inherits from desktop). The
**Reset** link next to a control removes a device override.

Positions, sizes and rotation are per device too, so a free layout can be
arranged differently on a phone. Use **Hide on desktop / tablet / mobile** under
Visibility for device-specific content.

## 6. Design system (global colours and type)

**Breefer → Settings → Design system** defines site-wide:

- 8 global colours (primary, secondary, accent, text, muted, surface, border,
  background),
- body and heading font stacks (system fonts only, so nothing is loaded from
  third parties),
- base size, heading scale, line height, heading weight,
- corner radius, content width and spacing unit.

In the editor every colour control shows global colour swatches. Click a swatch to
link the colour to the token. Change the token in Settings and every linked
widget on the site updates. **Clear** returns to a fixed colour.

## 7. Section library and page kits

Open the **Sections** tab and click a preset to append it:

- **Page kits:** Free canvas, Landing page, Store home, About page.
- **Sections:** Hero, logo strip, features, stats, testimonials, pricing, FAQ,
  call to action, contact.
- **WooCommerce:** best sellers, new arrivals carousel, sale banner with
  countdown, category list, product page, shop archive, cart, checkout, account.
- **Theme builder:** header, shop header (with cart), footer, single post,
  archive, search results, 404.

Presets use your global colours, so they match your brand immediately.

## 8. Theme builder

Replace parts of your theme with Breefer templates. Open **Breefer → Theme
Builder**, click **Add new** next to a role, and design it. You can also open any
template, select the page (root), and choose the **Template type**.

| Role | Replaces |
| --- | --- |
| Header / Footer | The theme header and footer. Choose where it shows with **Display on**: entire site, front page, blog posts, pages, archives and search, WooCommerce pages, or everything except WooCommerce. |
| Single post / Single page | The layout of posts or pages. Add a **Post field → Content** widget where the content should appear. Pages built individually in Breefer keep their own layout. |
| Archive / blog | Categories, tags, authors, dates and the blog index. Use **Posts grid → Source: Main query** for the loop and pagination. |
| Search results, 404 page | Those screens. |

The most specific, most recently edited template of each role wins. Blank
**Canvas** pages deliberately have no header or footer. Works with classic and
block themes.

## 9. WooCommerce

With WooCommerce active, extra roles appear: **Shop & product archives**, **Single
product**, **Cart**, **Checkout** and **My account**.

- **Product page:** use *Product field* (title, price, gallery with zoom and
  lightbox, add to cart, description, attributes, tags), *Sale & stock badges*,
  *Product tabs*, *Related products* (related, upsells, cross-sells) and *Sticky
  add to cart*.
- **Shop page:** *Archive title*, *Result count & sorting* and *Product grid* with
  **Products: Main query**.
- **Cart / Checkout / My account:** add the matching widget. WooCommerce keeps
  running the real flow, so totals, coupons, payment gateways and validation are
  untouched, and both the block and shortcode versions of the pages work.
- **Product grid:** latest, featured, on sale, best selling, top rated or specific
  IDs, as a grid or a swipe carousel, with AJAX add to cart.
- **Mini cart:** icon with a live count and optional subtotal; updates after AJAX
  add to cart.

**Breefer → Settings → WooCommerce** has toggles for AJAX add to cart, live cart
updates and the sticky bar. Breefer declares compatibility with High-Performance
Order Storage and the Cart/Checkout blocks, and loads WooCommerce assets only on
pages that need them.

## 10. Widget reference

| Group | Widgets |
| --- | --- |
| Layout | Container (flex, grid, free canvas), Spacer, Divider |
| Basic | Heading, Text, Button, Image, Shape, Tabs, Accordion |
| Content | Icon, Icon list, Icon box, Video, Star rating, Progress bar |
| Pro | Counter, Testimonial, Price table, Call to action, Alert, Social icons, Share buttons, Countdown, Flip box, Image gallery, Logo marquee, Map, Contact form, HTML & shortcode |
| Theme | Site logo, Site title, Navigation menu, Search form, Post field, Posts grid, Breadcrumbs, Archive title, Login form |
| WooCommerce | Product grid, Product field, Mini cart, Product categories, Cart, Checkout, My account, Product tabs, Sale & stock badges, Related products, Result count & sorting, Sticky add to cart |

Notes:

- **Contact form:** one field per line as `Label | type | *` (`*` = required;
  types: text, email, tel, number, url, textarea). Messages are emailed to the
  address you set (or the site admin). A hidden honeypot field and a per-visitor
  rate limit protect against spam.
- **Countdown:** enter the end time in site time, `YYYY-MM-DD HH:MM`.
- **Image gallery / Logo marquee:** one image URL per line (gallery supports
  `URL | caption`).
- **Social icons:** `network|URL` per line. **Share buttons:** one network per line.
- **HTML & shortcode:** markup is sanitised (scripts are removed). Put anything
  that needs scripts behind a shortcode.
- Widgets that show live site data (products, menus, posts, forms) are rendered
  by the server inside the canvas, so you see real content while designing.

## 11. Motion, effects, visibility and custom CSS

Select any widget and use the right panel:

- **Motion & effects:** entrance animation (fade, slide, zoom) with delay, hover
  effect (lift, grow, glow ring), opacity, clip overflow. Animations respect the
  visitor's *reduced motion* setting and play only on the live site.
- **Visibility:** hide per device, or show only to logged-in or logged-out
  visitors.
- **Anchor ID** for `#links`, and **CSS classes** for your own styling.
- **Custom CSS:** write rules using the word `selector` for the widget, for example
  `selector:hover { opacity: .8 }`. Plain declarations apply to the widget itself.
  CSS containing `@import`, `javascript:`, backslashes or comments is rejected.

## 12. Keyboard shortcuts

| Keys | Action |
| --- | --- |
| `Ctrl S` | Save |
| `Ctrl Z` / `Ctrl Shift Z` / `Ctrl Y` | Undo / redo |
| `Ctrl D` | Duplicate |
| `Delete` | Delete the selection |
| Arrow keys (`Shift` = ×10) | Nudge a free widget |
| `Ctrl ]` / `Ctrl [` | Bring forward / send backward (`Shift` = front / back) |
| `Ctrl +` / `Ctrl -` / `Ctrl 0` | Zoom in / out / fit |
| Mouse wheel + `Ctrl` | Zoom |
| `Alt` while dragging | Bypass snapping |
| `Shift` while dragging / resizing | Lock axis / keep aspect ratio |
| `Esc` | Deselect (or cancel text editing) |

## 13. Shortcode, block and developer hooks

- Insert a template anywhere with the shortcode `[breefer id="123"]` or the
  **Breefer Canvas** block in the block editor.
- `breefer/render/widget` filters a widget's HTML.
- `breefer/render/document` filters a whole rendered document.
- `breefer/icons` registers extra icons.
- `breefer/forms/mail` filters the contact form email before it is sent, and the
  `breefer/forms/submitted` action fires after it is sent.

## 14. Troubleshooting

- **The editor says the bundle is missing:** run `npm install && npm run build`
  in the plugin folder (not needed for the packaged ZIP).
- **A widget can't be dragged:** it may be locked (toolbar shows **Unlock**). Hold
  the pointer on the widget itself, not on an empty part of its container;
  flow containers move with the ⠿ handle.
- **Free widgets look different on mobile:** positions are per device. Switch to
  Mobile and arrange them for that screen.
- **My header or footer doesn't appear:** check the template's **Display on**
  condition, and that the page isn't using the blank *Canvas* layout.
- **The theme's old header still shows on a classic theme:** the theme may print
  its header in a non-standard way. Hide it with CSS or use a block theme.
- **WooCommerce widgets look empty in the editor:** cart, checkout and account
  render live on the site; the editor shows a stand-in.
- **Form emails don't arrive:** configure SMTP (many hosts block PHP mail) and
  check the spam folder.

## 15. License

Breefer is free software released under the **GNU General Public License,
version 3 or (at your option) any later version**. See the `LICENSE` file or
<https://www.gnu.org/licenses/gpl-3.0.html>.
