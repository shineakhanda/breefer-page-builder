# Breefer — page builder for WordPress & WooCommerce

A performance-first visual page builder. Design on a free-form canvas (drag, resize,
rotate, overlap, snap — like an image editor), build whole themes (header, footer,
posts, archives, 404) and WooCommerce stores (shop, product, cart, checkout,
account), and ship plain static HTML with no framework runtime on the frontend.

- **Free-form canvas:** place anything anywhere, resize from 8 handles, rotate,
  snap guides, align, layers, lock, zoom, double-click text editing.
- **Design system:** global colours, type scale and radius that restyle the whole site.
- **Theme builder:** header, footer, single post/page, archive, search and 404 with
  display conditions; classic and block themes.
- **50+ widgets:** pro content (counters, pricing, forms, galleries…), theme and
  WooCommerce widgets, rendered live inside the editor.
- **WooCommerce:** product grids and carousels, product pages, cart, checkout,
  account, sticky add-to-cart, live mini cart, HPOS and Cart/Checkout block compatible.
- **Section library:** page kits and ready-made sections that follow your brand colours.

## Install

Upload the plugin ZIP in **Plugins → Add New → Upload Plugin** and activate it.
Requires WordPress 6.2+ and PHP 8.2+.

## Documentation

See the [usage guide](docs/USAGE.md).

## Development

```bash
npm install
npm run dev          # Vite dev server
npm run build        # production editor bundle into build/
npm run typecheck && npm run lint && npm test
powershell -ExecutionPolicy Bypass -File bin/package.ps1   # build the installable ZIP
```

## License

GNU General Public License v3.0 or later — see [LICENSE](LICENSE).
