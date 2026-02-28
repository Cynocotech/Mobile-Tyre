# SEO & professional checklist – Mobile Tyre site

## Done (in code)

- **robots.txt** – Allows all crawlers, references sitemap.
- **sitemap.xml** – Home, services, contact, check-vehicle, blog and all 8 blog post URLs with priorities and changefreq.
- **Canonical URLs** – Set on every page (index, services, contact, check-vehicle, blog, all blog posts).
- **Meta** – Unique title and description on each page; index has keywords, robots, geo.
- **Open Graph & Twitter** – Every page has og:type, og:url, og:title, og:description, og:image, og:image:alt, og:locale, og:site_name and matching Twitter Card meta. Blog posts use og:type=article.
- **Favicon & icons** – All pages use logo as favicon; apple-touch-icon (180×180) on every page. Contact page has H1 icon + phone/WhatsApp icon buttons; Blog and Check vehicle have H1 icons.
- **Structured data** – AutoRepair + WebPage + BreadcrumbList + FAQPage on index; Service + OG/Twitter on services; ContactPage + BreadcrumbList + Organization/contactPoint on contact; WebPage + BreadcrumbList on check-vehicle; CollectionPage + BreadcrumbList on blog; Article + publisher on all 8 blog posts.
- **Preconnect** – index.html preconnects to unpkg and Tailwind CDN for faster load.
- **lang="en-GB"** – Set on all pages.

## Recommended next steps

1. **Social image** – Create a 1200×630 px image (logo + tagline or hero photo), upload to your site, and set `og:image` and `twitter:image` to that URL in `index.html` (and optionally other pages).
2. **Google Search Console** – Add the property, submit `sitemap.xml` (e.g. `https://no5tyreandmot.co.uk/mobile-tyre/sitemap.xml`).
3. **Google Business Profile** – Create/claim listing for “No5 Tyre & MOT” with area served (London), phone, hours, link to this site.
4. **HTTPS** – Ensure the live site is served over HTTPS (you have .htaccess for redirect).
5. **Core Web Vitals** – Test with PageSpeed Insights; consider lazy-loading below-the-fold images if not already.
6. **Privacy / terms** – Add simple Privacy Policy and Terms pages and link in footer if you collect data (quote form, contact form).

## Optional

- **BreadcrumbList** – Already on contact, check-vehicle, blog; can add on services and blog posts for breadcrumb rich results.
- **Review snippet** schema if you add testimonials or review text (with proper markup).
- **Dedicated favicon** – 32×32 or square 180×180 PNG from your logo for a clearer browser tab / home screen icon (current logo is rectangular).
