import { createId } from '../lib/ids';
import type { BuilderNode } from '../types';

type Settings = Record<string, unknown>;

export interface SectionPreset {
  id: string;
  title: string;
  description: string;
  group: 'section' | 'woocommerce' | 'theme' | 'kit';
  /** Build fresh nodes (new ids) on every insert. */
  build: () => BuilderNode[];
}

const node = (
  type: string,
  settings: Settings = {},
  children: BuilderNode[] = [],
): BuilderNode => ({
  id: createId(),
  type,
  settings,
  children,
});

/** A full-width band holding a centred content column. */
const band = (settings: Settings, children: BuilderNode[]): BuilderNode =>
  node(
    'container',
    {
      direction: 'column',
      gap: 24,
      padding: 72,
      maxWidth: 0,
      alignItems: 'center',
      justify: 'flex-start',
      ...settings,
    },
    [
      node(
        'container',
        {
          direction: 'column',
          gap: 24,
          padding: 0,
          maxWidth: 1160,
          alignItems: 'stretch',
          justify: 'flex-start',
        },
        children,
      ),
    ],
  );

const grid = (columns: number, children: BuilderNode[], settings: Settings = {}): BuilderNode =>
  node(
    'container',
    {
      layout: 'grid',
      columns,
      gap: 24,
      padding: 0,
      maxWidth: 0,
      alignItems: 'stretch',
      ...settings,
    },
    children,
  );

const row = (children: BuilderNode[], settings: Settings = {}): BuilderNode =>
  node(
    'container',
    {
      direction: 'row',
      gap: 16,
      padding: 0,
      maxWidth: 0,
      alignItems: 'center',
      justify: 'space-between',
      wrap: true,
      ...settings,
    },
    children,
  );

const heading = (text: string, settings: Settings = {}): BuilderNode =>
  node('heading', {
    text,
    level: 'h2',
    fontSize: 40,
    fontWeight: 700,
    color: 'global:text',
    align: 'center',
    lineHeight: 1.15,
    ...settings,
  });

const lead = (content: string, settings: Settings = {}): BuilderNode =>
  node('text', {
    content,
    fontSize: 18,
    lineHeight: 1.7,
    color: 'global:muted',
    align: 'center',
    ...settings,
  });

const button = (text: string, settings: Settings = {}): BuilderNode =>
  node('button', {
    text,
    url: '#',
    background: 'global:primary',
    color: '#ffffff',
    radius: 99,
    padding: 14,
    fontSize: 15,
    ...settings,
  });

const iconBox = (icon: string, title: string, description: string): BuilderNode =>
  node('iconbox', {
    icon,
    title,
    description,
    background: 'global:surface',
    iconColor: 'global:primary',
    titleColor: 'global:text',
    textColor: 'global:muted',
    padding: 28,
    radius: 18,
  });

const intro = (title: string, text: string): BuilderNode[] => [heading(title), lead(text)];

// ------------------------------------------------------------------ Sections

const hero = (): BuilderNode[] => [
  band(
    {
      padding: 120,
      gradientFrom: 'global:primary',
      gradientTo: 'global:secondary',
      gradientAngle: 135,
      animation: 'fadeup',
    },
    [
      heading('Build a site people love to visit', {
        level: 'h1',
        fontSize: 60,
        color: '#ffffff',
        fontWeight: 800,
      }),
      lead('Design pages, headers and whole storefronts visually. Ship fast, static HTML.', {
        color: '#ece8ff',
        fontSize: 20,
      }),
      row(
        [
          button('Get started', { background: 'global:accent', color: '#1e1735' }),
          button('See how it works', { background: '#ffffff', color: '#1e1735' }),
        ],
        { justify: 'center' },
      ),
    ],
  ),
];

const logos = (): BuilderNode[] => [
  band({ padding: 40 }, [
    lead('Trusted by teams you know', { fontSize: 14 }),
    node('marquee', { images: '', logoHeight: 40, gap: 56, speed: 30 }),
  ]),
];

const features = (): BuilderNode[] => [
  band({}, [
    ...intro(
      'Everything you need to sell and publish',
      'Powerful building blocks that stay out of your way.',
    ),
    grid(3, [
      iconBox('bolt', 'Blazing fast', 'Static HTML, scoped CSS and no framework runtime.'),
      iconBox(
        'shield',
        'Secure by design',
        'Every value is sanitised and escaped on save and render.',
      ),
      iconBox('heart', 'Made for stores', 'Native WooCommerce widgets and checkout templates.'),
    ]),
  ]),
];

const stats = (): BuilderNode[] => [
  band({ background: 'global:surface' }, [
    grid(4, [
      node('counter', {
        end: 12000,
        suffix: '+',
        title: 'Customers',
        separator: true,
        numberColor: 'global:primary',
        align: 'center',
        duration: 2000,
      }),
      node('counter', {
        end: 340,
        suffix: '+',
        title: 'Templates',
        separator: true,
        numberColor: 'global:primary',
        align: 'center',
        duration: 2000,
      }),
      node('counter', {
        end: 99,
        suffix: '%',
        title: 'Satisfaction',
        numberColor: 'global:primary',
        align: 'center',
        duration: 2000,
      }),
      node('counter', {
        end: 24,
        suffix: '/7',
        title: 'Support',
        numberColor: 'global:primary',
        align: 'center',
        duration: 2000,
      }),
    ]),
  ]),
];

const testimonials = (): BuilderNode[] => [
  band({}, [
    ...intro('Loved by builders', 'A few words from people shipping with Breefer.'),
    grid(3, [
      node('testimonial', {
        content: 'We rebuilt our storefront in a weekend. Conversions are up 32%.',
        name: 'Alex Morgan',
        role: 'Founder, Acme Co.',
        rating: 5,
        layout: 'card',
      }),
      node('testimonial', {
        content: 'The theme builder replaced three plugins and made the site faster.',
        name: 'Priya Shah',
        role: 'Head of Growth, Northwind',
        rating: 5,
        layout: 'card',
      }),
      node('testimonial', {
        content: 'Our designers finally ship without waiting on developers.',
        name: 'Jonas Weber',
        role: 'Creative Director, Studio J',
        rating: 5,
        layout: 'card',
      }),
    ]),
  ]),
];

const pricing = (): BuilderNode[] => [
  band({ background: 'global:surface' }, [
    ...intro('Simple, honest pricing', 'Start free. Upgrade when you grow.'),
    grid(
      3,
      [
        node('pricetable', {
          title: 'Starter',
          subtitle: 'For side projects',
          price: '0',
          period: '/ month',
          features: '1 site\nCore widgets\nCommunity support\n- Theme builder',
          buttonText: 'Start free',
          buttonUrl: '#',
        }),
        node('pricetable', {
          title: 'Pro',
          subtitle: 'For growing teams',
          price: '29',
          period: '/ month',
          features: 'Unlimited sites\nTheme builder\nWooCommerce widgets\nPriority support',
          buttonText: 'Choose Pro',
          buttonUrl: '#',
          featured: true,
          badge: 'Most popular',
        }),
        node('pricetable', {
          title: 'Agency',
          subtitle: 'For client work',
          price: '79',
          period: '/ month',
          features: 'Everything in Pro\nWhite label\nRole manager\nDedicated support',
          buttonText: 'Talk to us',
          buttonUrl: '#',
        }),
      ],
      { alignItems: 'center' },
    ),
  ]),
];

const faq = (): BuilderNode[] => [
  band({ maxWidth: 0 }, [
    ...intro('Frequently asked questions', 'Quick answers to common questions.'),
    node('accordion', {
      items:
        'Does it slow my site down? | No. Pages render to static HTML with a few KB of CSS and an optional tiny script.\nCan I use my own theme? | Yes. Breefer works with classic and block themes, and can also replace your header and footer.\nDoes it work with WooCommerce? | Yes. It has native product, cart, checkout and account widgets.',
      openFirst: true,
      titleColor: 'global:text',
      textColor: 'global:muted',
      background: 'global:surface',
      borderColor: 'global:border',
      padding: 16,
      radius: 12,
      fontSize: 16,
    }),
  ]),
];

const cta = (): BuilderNode[] => [
  band({ padding: 64 }, [
    node('cta', {
      title: 'Ready to launch your next idea?',
      description: 'Join thousands of teams designing faster with Breefer.',
      buttonText: 'Get started free',
      buttonUrl: '#',
      button2Text: 'Talk to sales',
      button2Url: '#',
      style: 'filled',
      layout: 'center',
    }),
  ]),
];

const contact = (): BuilderNode[] => [
  band({}, [
    grid(
      2,
      [
        node(
          'container',
          { direction: 'column', gap: 16, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            heading('Get in touch', { align: 'left', fontSize: 36 }),
            lead('Tell us about your project and we will reply within one business day.', {
              align: 'left',
            }),
            node('map', { address: 'Eiffel Tower, Paris', zoom: 13, height: 260 }),
          ],
        ),
        node('form', {
          fields: 'Name | text | *\nEmail | email | *\nMessage | textarea | *',
          submitText: 'Send message',
        }),
      ],
      { gap: 48 },
    ),
  ]),
];

// --------------------------------------------------------------- WooCommerce

const productShowcase = (): BuilderNode[] => [
  band({}, [
    ...intro('Best sellers', 'The products our customers keep coming back for.'),
    node('wooproducts', {
      source: 'best',
      limit: 4,
      columns: 4,
      layout: 'grid',
      gap: 24,
      hoverZoom: true,
      accent: 'global:primary',
    }),
  ]),
];

const productCarousel = (): BuilderNode[] => [
  band({ background: 'global:surface' }, [
    ...intro('New arrivals', 'Fresh in this week. Swipe to browse.'),
    node('wooproducts', {
      source: 'latest',
      limit: 10,
      columns: 4,
      layout: 'carousel',
      gap: 24,
      hoverZoom: true,
      accent: 'global:primary',
    }),
  ]),
];

const saleBanner = (): BuilderNode[] => [
  band({ padding: 48 }, [
    node(
      'container',
      {
        direction: 'column',
        gap: 20,
        padding: 48,
        maxWidth: 0,
        alignItems: 'center',
        gradientFrom: 'global:accent',
        gradientTo: 'global:primary',
        gradientAngle: 120,
        radius: 24,
      },
      [
        heading('Summer sale — up to 40% off', { color: '#1e1735', fontSize: 40 }),
        node('countdown', {
          expiredText: 'The sale has ended.',
          numberColor: 'global:secondary',
          align: 'center',
        }),
        button('Shop the sale', { background: 'global:secondary', color: '#ffffff' }),
      ],
    ),
  ]),
];

const categoryGrid = (): BuilderNode[] => [
  band({}, [
    ...intro('Shop by category', 'Find exactly what you are looking for.'),
    node('woocategories', {}),
  ]),
];

const productPage = (): BuilderNode[] => [
  band({ padding: 40 }, [
    node('breadcrumbs', { separator: '›', homeLabel: 'Home' }),
    grid(
      2,
      [
        node('woofield', { field: 'images' }),
        node(
          'container',
          { direction: 'column', gap: 14, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            node('woostatus', { show: 'both', showPercent: true }),
            node('woofield', { field: 'title', tag: 'h1' }),
            node('woofield', { field: 'rating' }),
            node('woofield', { field: 'price' }),
            node('woofield', { field: 'short_description' }),
            node('woofield', { field: 'add_to_cart' }),
            node('woofield', { field: 'meta' }),
            node('share', { networks: 'facebook\nx\nwhatsapp\nemail', style: 'outline', size: 36 }),
          ],
        ),
      ],
      { gap: 48, alignItems: 'flex-start' },
    ),
    node('wootabs', {}),
    node('woorelated', { source: 'related', heading: 'You may also like', limit: 4, columns: 4 }),
    node('woosticky', { position: 'bottom' }),
  ]),
];

const shopPage = (): BuilderNode[] => [
  band({ padding: 40 }, [
    node('breadcrumbs', { separator: '›', homeLabel: 'Home' }),
    node('archivetitle', { tag: 'h1', showDescription: true, align: 'left' }),
    node('wooordering', {}),
    node('wooproducts', {
      source: 'main',
      limit: 12,
      columns: 4,
      gap: 24,
      hoverZoom: true,
      accent: 'global:primary',
    }),
  ]),
];

const cartPage = (): BuilderNode[] => [
  band({ padding: 40 }, [
    node('heading', {
      text: 'Your cart',
      level: 'h1',
      fontSize: 36,
      color: 'global:text',
      align: 'left',
    }),
    node('woocarttable', {}),
    node('woorelated', {
      source: 'crosssell',
      heading: 'Complete your order',
      limit: 4,
      columns: 4,
    }),
  ]),
];

const checkoutPage = (): BuilderNode[] => [
  band({ padding: 40 }, [
    node('heading', {
      text: 'Checkout',
      level: 'h1',
      fontSize: 36,
      color: 'global:text',
      align: 'left',
    }),
    node('woocheckout', {}),
  ]),
];

const accountPage = (): BuilderNode[] => [
  band({ padding: 40 }, [
    node('heading', {
      text: 'My account',
      level: 'h1',
      fontSize: 36,
      color: 'global:text',
      align: 'left',
    }),
    node('wooaccount', {}),
  ]),
];

// ------------------------------------------------------------ Theme builder

const headerBar = (woo: boolean): BuilderNode[] => [
  band(
    {
      padding: 16,
      background: 'global:background',
      borderWidth: 1,
      borderColor: 'global:border',
      position: 'sticky',
      offsetTop: 0,
      zIndex: 50,
    },
    [
      row(
        [
          node('sitelogo', { logoWidth: 140 }),
          node('navmenu', { layout: 'horizontal', depth: 2, gap: 28, fontSize: 15 }),
          row(
            [
              node('searchform', {
                placeholder: 'Search…',
                hideButton: true,
                postType: woo ? 'product' : 'all',
                style: 'pill',
              }),
              ...(woo
                ? [node('woocart', { mode: 'dropdown', showTotal: false, iconSize: 26 })]
                : []),
            ],
            { justify: 'flex-end', gap: 16, wrap: false },
          ),
        ],
        { justify: 'space-between' },
      ),
    ],
  ),
];

const footerColumns = (): BuilderNode[] => [
  band({ background: 'global:secondary', padding: 64 }, [
    grid(
      4,
      [
        node(
          'container',
          { direction: 'column', gap: 12, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            node('sitetitle', { tag: 'p', showTagline: true, color: '#ffffff' }),
            node('social', {
              networks:
                'facebook|https://facebook.com\nx|https://x.com\ninstagram|https://instagram.com',
              style: 'outline',
              size: 36,
              iconColor: '#ffffff',
            }),
          ],
        ),
        node(
          'container',
          { direction: 'column', gap: 10, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            node('heading', {
              text: 'Company',
              level: 'h4',
              fontSize: 16,
              color: '#ffffff',
              align: 'left',
            }),
            node('navmenu', {
              layout: 'vertical',
              depth: 1,
              linkColor: '#cfc8e6',
              hoverColor: '#ffffff',
            }),
          ],
        ),
        node(
          'container',
          { direction: 'column', gap: 10, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            node('heading', {
              text: 'Support',
              level: 'h4',
              fontSize: 16,
              color: '#ffffff',
              align: 'left',
            }),
            node('text', {
              content: 'help@example.com',
              color: '#cfc8e6',
              fontSize: 15,
              align: 'left',
            }),
          ],
        ),
        node(
          'container',
          { direction: 'column', gap: 10, padding: 0, maxWidth: 0, alignItems: 'stretch' },
          [
            node('heading', {
              text: 'Stay in touch',
              level: 'h4',
              fontSize: 16,
              color: '#ffffff',
              align: 'left',
            }),
            node('form', {
              fields: 'Email | email | *',
              submitText: 'Subscribe',
              subject: 'New subscriber',
            }),
          ],
        ),
      ],
      { gap: 40 },
    ),
    node('text', {
      content: '© Your company. All rights reserved.',
      color: '#9d94bd',
      fontSize: 14,
      align: 'center',
    }),
  ]),
];

const postLayout = (): BuilderNode[] => [
  band({ padding: 56 }, [
    node(
      'container',
      { direction: 'column', gap: 20, padding: 0, maxWidth: 760, alignItems: 'stretch' },
      [
        node('breadcrumbs', {}),
        node('postfield', { field: 'categories' }),
        node('postfield', { field: 'title', tag: 'h1' }),
        row(
          [
            node('postfield', { field: 'author', showAvatar: true }),
            node('postfield', { field: 'date' }),
          ],
          { justify: 'flex-start', gap: 20 },
        ),
        node('postfield', { field: 'featured_image', imageSize: 'large' }),
        node('postfield', { field: 'content' }),
        node('share', { networks: 'facebook\nx\nlinkedin\nemail', style: 'outline', size: 36 }),
      ],
    ),
  ]),
];

const archiveLayout = (): BuilderNode[] => [
  band({ padding: 56 }, [
    node('archivetitle', { tag: 'h1', showDescription: true, align: 'left' }),
    node('postsgrid', {
      queryMode: 'main',
      postType: 'post',
      count: 9,
      columns: 3,
      gap: 28,
      showButton: true,
      buttonText: 'Read more',
    }),
  ]),
];

const searchLayout = (): BuilderNode[] => [
  band({ padding: 56 }, [
    node('archivetitle', { tag: 'h1', align: 'left' }),
    node('searchform', { placeholder: 'Search again…', buttonText: 'Search' }),
    node('postsgrid', { queryMode: 'main', count: 9, columns: 3, gap: 28 }),
  ]),
];

const notFound = (): BuilderNode[] => [
  band({ padding: 120 }, [
    node('heading', {
      text: '404',
      level: 'h1',
      fontSize: 120,
      color: 'global:primary',
      align: 'center',
      fontWeight: 800,
    }),
    heading('We could not find that page'),
    lead('It may have moved. Try searching instead.'),
    node('searchform', { placeholder: 'Search…', buttonText: 'Search' }),
  ]),
];

// ----------------------------------------------------------------- Page kits

const kitLanding = (): BuilderNode[] => [
  ...hero(),
  ...logos(),
  ...features(),
  ...stats(),
  ...testimonials(),
  ...pricing(),
  ...faq(),
  ...cta(),
];

const kitStore = (): BuilderNode[] => [
  ...hero(),
  ...categoryGrid(),
  ...productShowcase(),
  ...saleBanner(),
  ...productCarousel(),
  ...features(),
  ...testimonials(),
  ...cta(),
];

const kitFree = (): BuilderNode[] => [
  node(
    'container',
    {
      layout: 'free',
      padding: 0,
      maxWidth: 0,
      minHeight: 640,
      gradientFrom: 'global:surface',
      gradientTo: '#ffffff',
      gradientAngle: 160,
      overflowHidden: true,
    },
    [
      node('shape', {
        background: 'global:primary',
        radius: 200,
        boxWidth: 30,
        boxHeight: 360,
        position: 'absolute',
        offsetLeft: 700,
        offsetTop: 60,
        opacity: 0.18,
        zIndex: 1,
      }),
      node('shape', {
        background: 'global:accent',
        radius: 24,
        boxWidth: 16,
        boxHeight: 160,
        position: 'absolute',
        offsetLeft: 820,
        offsetTop: 380,
        rotate: 18,
        zIndex: 2,
      }),
      node('heading', {
        text: 'Design it your way',
        level: 'h1',
        fontSize: 64,
        fontWeight: 800,
        color: 'global:text',
        align: 'left',
        lineHeight: 1.05,
        position: 'absolute',
        offsetLeft: 80,
        offsetTop: 140,
        boxWidth: 55,
        zIndex: 3,
      }),
      node('text', {
        content:
          'Drag anything anywhere. Resize from the handles, rotate with the ⟳ knob and overlap freely.',
        fontSize: 20,
        color: 'global:muted',
        align: 'left',
        position: 'absolute',
        offsetLeft: 80,
        offsetTop: 340,
        boxWidth: 45,
        zIndex: 4,
      }),
      node('button', {
        text: 'Get started',
        url: '#',
        background: 'global:primary',
        color: '#ffffff',
        radius: 99,
        padding: 16,
        fontSize: 16,
        position: 'absolute',
        offsetLeft: 80,
        offsetTop: 460,
        zIndex: 5,
      }),
    ],
  ),
];

const kitAbout = (): BuilderNode[] => [
  ...hero(),
  ...stats(),
  ...features(),
  ...testimonials(),
  ...contact(),
];

export const sectionPresets: SectionPreset[] = [
  {
    id: 'hero',
    title: 'Hero',
    description: 'Gradient headline and buttons',
    group: 'section',
    build: hero,
  },
  {
    id: 'logos',
    title: 'Logo strip',
    description: 'Scrolling client logos',
    group: 'section',
    build: logos,
  },
  {
    id: 'features',
    title: 'Features',
    description: 'Three icon boxes',
    group: 'section',
    build: features,
  },
  {
    id: 'stats',
    title: 'Stats',
    description: 'Four animated counters',
    group: 'section',
    build: stats,
  },
  {
    id: 'testimonials',
    title: 'Testimonials',
    description: 'Three quote cards',
    group: 'section',
    build: testimonials,
  },
  {
    id: 'pricing',
    title: 'Pricing',
    description: 'Three plan cards',
    group: 'section',
    build: pricing,
  },
  { id: 'faq', title: 'FAQ', description: 'Accordion questions', group: 'section', build: faq },
  {
    id: 'cta',
    title: 'Call to action',
    description: 'Banner with buttons',
    group: 'section',
    build: cta,
  },
  {
    id: 'contact',
    title: 'Contact',
    description: 'Form with a map',
    group: 'section',
    build: contact,
  },

  {
    id: 'best-sellers',
    title: 'Best sellers',
    description: 'Product grid',
    group: 'woocommerce',
    build: productShowcase,
  },
  {
    id: 'new-arrivals',
    title: 'New arrivals',
    description: 'Swipeable carousel',
    group: 'woocommerce',
    build: productCarousel,
  },
  {
    id: 'sale-banner',
    title: 'Sale banner',
    description: 'Countdown promotion',
    group: 'woocommerce',
    build: saleBanner,
  },
  {
    id: 'categories',
    title: 'Category list',
    description: 'Shop by category',
    group: 'woocommerce',
    build: categoryGrid,
  },
  {
    id: 'product-page',
    title: 'Product page',
    description: 'Gallery, details, tabs, related',
    group: 'woocommerce',
    build: productPage,
  },
  {
    id: 'shop-page',
    title: 'Shop archive',
    description: 'Toolbar and main product loop',
    group: 'woocommerce',
    build: shopPage,
  },
  {
    id: 'cart-page',
    title: 'Cart page',
    description: 'Cart and cross-sells',
    group: 'woocommerce',
    build: cartPage,
  },
  {
    id: 'checkout-page',
    title: 'Checkout page',
    description: 'Native checkout',
    group: 'woocommerce',
    build: checkoutPage,
  },
  {
    id: 'account-page',
    title: 'Account page',
    description: 'Login and orders',
    group: 'woocommerce',
    build: accountPage,
  },

  {
    id: 'header',
    title: 'Header',
    description: 'Logo, menu and search',
    group: 'theme',
    build: () => headerBar(false),
  },
  {
    id: 'header-shop',
    title: 'Shop header',
    description: 'Logo, menu, search and cart',
    group: 'theme',
    build: () => headerBar(true),
  },
  {
    id: 'footer',
    title: 'Footer',
    description: 'Four columns and sign-up',
    group: 'theme',
    build: footerColumns,
  },
  {
    id: 'post-layout',
    title: 'Single post',
    description: 'Title, image, content, share',
    group: 'theme',
    build: postLayout,
  },
  {
    id: 'archive-layout',
    title: 'Archive',
    description: 'Title and post grid',
    group: 'theme',
    build: archiveLayout,
  },
  {
    id: 'search-layout',
    title: 'Search results',
    description: 'Search box and results',
    group: 'theme',
    build: searchLayout,
  },
  {
    id: '404-layout',
    title: '404 page',
    description: 'Friendly not-found page',
    group: 'theme',
    build: notFound,
  },

  {
    id: 'kit-free',
    title: 'Free canvas',
    description: 'Place and overlap anything',
    group: 'kit',
    build: kitFree,
  },
  {
    id: 'kit-landing',
    title: 'Landing page',
    description: 'Hero to call to action',
    group: 'kit',
    build: kitLanding,
  },
  {
    id: 'kit-store',
    title: 'Store home',
    description: 'Categories, products, sale',
    group: 'kit',
    build: kitStore,
  },
  {
    id: 'kit-about',
    title: 'About page',
    description: 'Story, numbers, contact',
    group: 'kit',
    build: kitAbout,
  },
];

export const sectionGroups: Array<{ id: SectionPreset['group']; label: string }> = [
  { id: 'kit', label: 'Page kits' },
  { id: 'section', label: 'Sections' },
  { id: 'woocommerce', label: 'WooCommerce' },
  { id: 'theme', label: 'Theme builder' },
];
