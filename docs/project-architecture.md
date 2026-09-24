# Vistula Travel — Project Architecture

> **Status:** Phase 0 (Architecture) — analysis only. No website code has been written.
> **Source of truth:** `Vistula_Travel_Project_Specification.docx` ("the Spec"). Section references look like `(Spec §5)`.
> **Companion documents:** [`data-model.md`](data-model.md) · [`technical-decisions.md`](technical-decisions.md) · [`implementation-roadmap.md`](implementation-roadmap.md)
> **Written:** 2026-09-21

---

## 0. How to read this document

### 0.1 Decision status tags
| Tag | Meaning |
|---|---|
| **DECIDED** | Fixed by the Spec or by unambiguous technical necessity. Change only with explicit approval. |
| **PROVISIONAL** | Recommended default. Safe to build on, but may be revised at a named gate. |
| **TO EVALUATE** | Not decided. Options and evaluation criteria are documented; a decision is made in a later phase. |

Every decision has an ID (`TD-nn`) in [`technical-decisions.md`](technical-decisions.md). Open questions are `OQ-nn`, risks are `R-nn`.

### 0.2 Ownership classification
| Class | Meaning | Examples |
|---|---|---|
| **A** | Client editable through WordPress | Tour text, prices, photos, phone number, FAQ |
| **B** | Developer controlled (code, config, Git) | Templates, CSS, field definitions, image sizes, CPT registration |
| **C** | System generated | Permalinks, sitemap, `srcset`, computed duration, canonical URLs |
| **D** | External provider controlled | Payment processing, booking availability (if external), mail delivery, DNS |

The full classification matrix is in [§22](#22-classification-matrix).

### 0.3 Spec-derived vs. proposed
Statements taken from the Spec are marked `(Spec §n)`. Everything else is a proposal from this analysis. Where a proposal **refines or departs from** the Spec it is marked **⚠ Refinement** so the owner can approve or reject it.

---

## 1. Inspection result and scope

### 1.1 What was inspected
| Item | Finding |
|---|---|
| Uploaded Spec (`Vistula_Travel_Project_Specification.docx`, 30 sections) | Read in full. |
| Project folder / working directory | **Empty.** No WordPress install, no theme, no plugin, no `wp-config.php`, no `.git` repository, no existing docs. |
| Toolchain available in the analysis sandbox | Git and Node.js only. No PHP, WP-CLI or Composer, so nothing could be executed against WordPress. |
| Existing functionality to preserve | None found. |

**Consequence:** this is a greenfield project. Nothing was modified or deleted. All four documents were created fresh in `docs/`.

### 1.2 What this phase delivers
Analysis and design only: information architecture, content model, technical decisions, roadmap. **Not delivered (by instruction):** homepage, frontend pages, CSS, JavaScript, booking, payment, plugins, a static prototype.

### 1.3 Business context (Spec §1–2)
Vistula Travel is a **fictional** Polish tourism company built as a portfolio-grade WordPress case study. Tagline: *"Discover Poland. Your way."* Audience: international tourists, expats, couples, families, small groups. Six launch destinations and six demo tours (Spec §4). Prices are demo content.

The "fictional but commercially realistic" nature drives several open questions (real payments? legal pages? reviews?) — see OQ-06, OQ-13, OQ-19 and R-04.

---

## 2. Specification review

The Spec is strong on principles and deliberately leaves many items to "architecture planning". The findings below are things a developer would trip over. Each is tracked to an open question.

### 2.1 Contradictions and inconsistencies
| # | Finding | Where | Proposed handling | Ref |
|---|---|---|---|---|
| F-01 | **Tour categories ≠ navigation.** Taxonomy lists 7 categories including *Culture & Food* and *Private Tours*; the nav shows only 5 (no Culture & Food, no Private Tours). | §6 vs §9 | Navigation is an editable WP menu (class A). Decide which terms appear. | OQ-08 |
| F-02 | **Travel Guide nav ≠ blog categories.** Nav has *Poland Travel*; the category list has *Culture* and *Destinations* instead. "Poland Travel" appears nowhere else. | §8 vs §9 | Reconcile. Recommend dropping a *Destinations* blog category because the destination↔post relationship already exists. | OQ-08 |
| F-03 | **"Booking" is a top-level nav item** (§9) but not in the header requirements (§12), which list only a *Book Now* CTA. | §9 vs §12 | Treat *Book Now* as a CTA whose target is configurable; whether a standalone `/booking/` page exists depends on the booking solution. | OQ-14 |
| F-04 | **FAQ is a top-level page** (§9) but is missing from the header list (§12). | §9 vs §12 | FAQ page stays; placement (header vs footer-only) is a design decision. | OQ-12 |
| F-05 | **Availability and capacity are listed as Tour fields** (§5) *and* the Spec prefers an external booking solution (§15). Two sources of truth would drift. | §5 vs §15 | WordPress stores only presentation and configuration; inventory is owned by the booking system. | OQ-10 |
| F-06 | **Destination "related tours"** is listed as a Destination field, while Tours are also related to Destinations. A stored two-way link goes stale. | §7 | Store the relationship **once** (on the Tour); derive the reverse by query. | TD-14 |
| F-07 | **Roadmap mismatch.** The Spec has phases 0–18 (§27). The Prompt-01 roadmap has 20 stages and adds *Theme Foundation*. | §27 vs Prompt 01 | Mapping table in the roadmap. | — |
| F-08 | **Sequence tension.** §13 puts CSS *after* data model and templates; §27 puts *Global Design System* (Phase 2) *before* *Tours CMS* (Phase 3). | §13 vs §27 | Design System phase is limited to tokens, base styles, layout primitives and header/footer; tour/destination component styling is done with real data in later phases. | TD-37 |

### 2.2 Ambiguities
| # | Finding | Ref |
|---|---|---|
| F-09 | "Private Tours" is both a category (§6) *and* an optional private-tour price on a normal tour (§5). Is a private tour a separate product or a variant of a group tour? | OQ-07 |
| F-10 | "Weekend Trips" mixes a *format/duration* with a *theme*. | OQ-08 |
| F-11 | "Price type" is unspecified. Child price has no age definition. Private price basis (per group? up to how many people?) is unspecified. VAT-inclusive display is unspecified. | OQ-09 |
| F-12 | "Currency" is a per-tour field, but the site sells in PLN. BLIK — Poland's dominant online payment method — supports PLN only (see [§11](#11-payment-architecture)). | TD-15 |
| F-13 | "Location" (Tour) overlaps with the Destination relationship and with "meeting point". | data-model |
| F-14 | Destinations mix granularity: cities (Warsaw, Kraków, Gdańsk, Wrocław), a town-plus-mountains area (Zakopane / Tatra Mountains), and a region (Masuria). A single tour (*Kraków & Wieliczka*) touches two places. | data-model |
| F-15 | "Disabling a tour" (§5) is undefined: unpublish, "sold out", "booking off", or "seasonal"? | OQ-25 |
| F-16 | **`UA` vs `uk`.** `UA` is Ukraine's *country* code. The language code (for `hreflang`, `<html lang>`, locale) is `uk`. | OQ-02 |
| F-17 | **Default language and URL structure are not specified.** | OQ-01 |
| F-18 | "Tours / Experiences" — naming inconsistency (nav uses "Tours"). | OQ-26 |
| F-19 | FAQ appears as a page and as a Destination field, but no FAQ content model is defined. | OQ-12 |
| F-20 | Reviews/testimonials appear on the homepage and in the translation list but have no content model. Reviews for a fictional company must not be presented as real. | OQ-13 |
| F-21 | Destination "things to do" collides with the blog category and nav item *Things To Do* (duplicate-content risk). | OQ-08 |

### 2.3 Gaps (missing from the Spec)
| # | Gap | Why it matters | Ref |
|---|---|---|---|
| F-22 | **Default language** and language-URL pattern | Affects every URL | OQ-01 |
| F-23 | **Guide language** per tour (in which languages is the tour *conducted*?) — distinct from site UI language | Core to a four-language international audience | OQ-11 |
| F-24 | **Multi-day itinerary:** itinerary items have `time/title/description` but no **day** | *Masurian Lakes* is 2 days | data-model |
| F-25 | **Brand identity** (logo, palette, typography, tone) — "company identity" is in the Definition of Done but no assets are supplied | Design System phase blocker | OQ-26 |
| F-26 | **Legal / compliance content** (privacy policy, cookies, terms, cancellation and refund policy) | Footer requires "legal information" (§12) | OQ-19 |
| F-27 | **Consent** on the contact form and cookie/consent management for third-party resources | GDPR / ePrivacy | OQ-15, OQ-22 |
| F-28 | **Cancellation / refund rules** | Booking phase cannot be designed without them | OQ-09, OQ-19 |
| F-29 | **Real vs. sandbox payments** on the live portfolio site | Fictional business | OQ-06 |
| F-30 | **Hosting target, domain, local environment tooling** | Deployment planning | OQ-17 |
| F-31 | **Accessibility conformance level** (the Spec lists practices but no WCAG level) | Testable acceptance criterion | OQ-18 |
| F-32 | **Analytics** — not mentioned | Consent and performance impact | OQ-22 |
| F-33 | Out-of-scope confirmation: user accounts, comments, newsletter, promo codes, gift vouchers, multi-currency, review submission | Prevents scope creep | OQ-23 |

### 2.4 Technical risks surfaced by the review
Detailed in [`technical-decisions.md` §Risks](technical-decisions.md). The most consequential:
1. **Booking + payment is the largest and riskiest scope** and depends on unresolved business questions (R-03, R-04).
2. **Multilingual compatibility** of relationships, options and slugs depends on a plugin that is intentionally not chosen yet (R-02).
3. **Full-page caching versus form nonces** — the Spec requires nonces *and* good performance; these conflict unless designed for (R-06).
4. **Environment migration** can silently break relationship fields if content is moved by export/import instead of DB copy (R-15).

---

## 3. Architecture overview

### 3.1 Principles applied (Spec §13, §30)
WordPress-first · CMS-driven content · scalable · multilingual from day one · no hard-coded repeated business data · phased delivery with a gate after each phase · smallest-correct-layer debugging · media/SEO/a11y/performance/security treated as architecture, not polish.

### 3.2 Layered model
```
┌────────────────────────────────────────────────────────────────────┐
│ PRESENTATION   theme: vistula-travel        (class B, in Git)      │
│   templates · template-parts · CSS · JS · icons · theme.json       │
│   ▸ renders data; contains NO business data and NO data rules      │
├────────────────────────────────────────────────────────────────────┤
│ DOMAIN / DATA  plugin: vistula-core         (class B, in Git)      │
│   CPTs · taxonomies · field definitions · settings API ·           │
│   image sizes · query builders · data-access layer · form handler  │
│   ▸ survives a theme change; single home for business rules        │
├────────────────────────────────────────────────────────────────────┤
│ CONTENT        WordPress database + uploads   (class A, NOT in Git)│
│   tours · destinations · posts · FAQ · testimonials · settings ·   │
│   media · menus                                                     │
├────────────────────────────────────────────────────────────────────┤
│ INTEGRATIONS   third-party plugins & external services (class D)   │
│   multilingual · SEO · booking · payment · SMTP · cache · backup   │
│   ▸ reached only through documented adapters / boundaries          │
└────────────────────────────────────────────────────────────────────┘
```

### 3.3 Two structural rules
1. **Theme = presentation; plugin = data model.** *(PROVISIONAL, TD-11 / OQ-04.)* If the theme were replaced, all tours, destinations and settings would remain intact and editable.
2. **Templates never call a field framework or `get_option()` directly.** They call a small data-access layer (`vistula_tour()`, `vistula_setting()`, `vistula_page_url()`…). *(PROVISIONAL, TD-13.)* This one abstraction is what keeps three undecided choices reversible: the field framework, the multilingual plugin, and the booking provider.

### 3.4 Theme type
A **classic PHP theme** with `header.php`, `footer.php`, `wp_head()`, `wp_footer()`, `get_header()`, `get_footer()` and `wp_nav_menu()` — **DECIDED** by Spec §12 (and lesson 2 in §29). `theme.json` is used for design tokens and editor settings, which classic themes support. A block (FSE) theme is deliberately *not* chosen. **⚠ Note:** this is the Spec's choice, and it is coherent with server-rendered filtering, hand-built templates and the portfolio goal.

---

## 4. Information architecture

### 4.1 Sitemap
```
HOME                         /
TOURS                        /tours/                     ← page (listing + filters)
 ├─ All Tours                 /tours/
 ├─ City Tours                /tours/category/city-tours/
 ├─ Nature                    /tours/category/nature/
 ├─ Mountains                 /tours/category/mountains/
 ├─ History                   /tours/category/history/
 ├─ Weekend Trips             (category or type — OQ-08)
 └─ [single tour]             /tours/{tour-slug}/
DESTINATIONS                 /destinations/              ← page (grid)
 ├─ Warsaw · Kraków · Gdańsk · Wrocław · Zakopane / Tatra · Masuria
 └─ [single destination]      /destinations/{slug}/
TRAVEL GUIDE                 /travel-guide/              ← posts page
 ├─ Travel Tips · Things To Do · Polish Food · (Poland Travel? Culture?) — OQ-08
 └─ [single article]          /travel-guide/{post-slug}/
ABOUT US                     /about-us/
FAQ                          /faq/
CONTACT                      /contact/
BOOKING                      /booking/                   ← existence depends on OQ-14 / TD-30
Utility: Search results · 404 · Privacy Policy · Cookie Policy · Terms · Booking terms/cancellation
```
Slugs shown are the **default-language** form. Language prefixes and translated base slugs are covered in [§8](#8-multilingual-architecture).

### 4.2 Page inventory and template mapping
| Page | WP object | Template | Editable by client |
|---|---|---|---|
| Home | Page (front page) | `front-page.php` | Intro copy, section headings, which sections show; tours/destinations/posts are **queried**, not typed |
| About Us | Page | `page-templates/about.php` | Yes |
| Tours (listing) | Page + template | `page-templates/tours-listing.php` | Intro + SEO fields; results are a query |
| Destinations (listing) | Page + template | `page-templates/destinations-listing.php` | Same |
| Travel Guide | Posts page (`page_for_posts`) | `home.php` | Intro + SEO |
| FAQ | Page + template | `page-templates/faq.php` | FAQ entries are a CPT |
| Contact | Page + template | `page-templates/contact.php` | Intro; contact details come from Global Settings |
| Booking | TBD | TBD | TBD |
| Single tour / destination / post | CPT / Post | `single-tour.php`, `single-destination.php`, `single.php` | Yes |
| Tour category / type archive | Taxonomy | `taxonomy-tour_category.php`, `taxonomy-tour_type.php` | Term description + image |
| Legal pages | Page | `page.php` | Yes |
| Search / 404 | — | `search.php`, `404.php` | Strings only |

### 4.3 Why listing pages are real Pages **(PROVISIONAL, TD-18)**
A CPT archive (`has_archive`) has no editable intro text, SEO fields or blocks in wp-admin, and multilingual plugins handle archive titles inconsistently. A real Page named *Tours* with a listing template gives editors a normal edit screen, a translatable object and SEO fields. The CPT is registered with `has_archive => false` and a rewrite base of `tours`, so `/tours/` resolves to the Page while `/tours/{slug}/` resolves to a tour. This rewrite behaviour must be **verified in Phase 2** (rewrite flush test) before dependency.

### 4.4 Page roles registry **(PROVISIONAL, TD-18)**
Templates and menus must never hard-code a slug such as `/contact/`. A registry maps a **role** to a Page ID: `home`, `tours`, `destinations`, `travel_guide`, `about`, `faq`, `contact`, `booking`, `privacy`, `cookies`, `terms`. Code calls `vistula_page_url('contact')`. A multilingual plugin can later translate the ID; slugs can be renamed by the client without breaking links. WooCommerce uses the same pattern for shop/cart/checkout.

### 4.5 Content relationships (summary — full model in [`data-model.md`](data-model.md))
```
                        ┌──────────────┐
      ┌────────────────▶│ Destination  │◀──────────────┐
      │ tour_destinations└──────┬───────┘ post_destinations
┌─────┴─────┐                   │ curated faqs         │
│   Tour    │◀──────────────────┼──────────────┐  ┌────┴────┐
└─┬───┬───┬─┘  post_tours       ▼              │  │  Post   │
  │   │   │              ┌──────────┐          │  │ (guide) │
  │   │   └─ tour_category│   FAQ   │◀─ tour_faqs └─────────┘
  │   └───── tour_type    └──────────┘
  └── testimonial.tour ──▶ Testimonial
```
Rules: each relationship is **stored on exactly one side** (the side that is edited most), and the reverse is **derived by query**. Curated, *ordered* lists (FAQs) are stored on the consumer.

### 4.6 Navigation **(PROVISIONAL)**
| Location | Registered as | Managed as |
|---|---|---|
| Primary header | `primary` | WP Menu (A) |
| Footer navigation | `footer_nav` | WP Menu (A) |
| Footer legal links | `footer_legal` | WP Menu (A) |
| Footer "Tours" / "Destinations" lists | — | **Dynamic query** (C) — new tours appear automatically |
| Language switcher | — | Generated (C) via `vistula_language_switcher()` |
| *Book Now* CTA | — | Global Settings (A): label + target |

Header behaviour requirements: logo, primary nav, language switcher, *Book Now*, hamburger on small screens (Spec §12). RU/UK labels are longer than English, so the mobile-menu breakpoint must be chosen with translated labels in mind (R-09).

---

## 5. Content model (summary)
Full field-level specification for every content type is in [`data-model.md`](data-model.md). Summary:

| Type | Implementation | Public URL | Status |
|---|---|---|---|
| Tour | CPT `tour` | Yes | DECIDED (type) |
| Destination | CPT `destination` | Yes | DECIDED (type) |
| Blog / Travel Guide | Core `post` | Yes | DECIDED |
| Tour Category | Taxonomy `tour_category` (hierarchical) | Archive | PROVISIONAL |
| Tour Type | Taxonomy `tour_type` | Archive | PROVISIONAL |
| FAQ | Non-public CPT `faq` + taxonomy `faq_group` | No | PROVISIONAL |
| Testimonial | Non-public CPT `testimonial` | No | PROVISIONAL |
| Itinerary Item | Repeatable field group inside Tour | No | PROVISIONAL |
| Media | Attachments + extra attachment meta | — | DECIDED |
| Company / Global Settings | Plugin settings page + accessor | — | PROVISIONAL |
| Language | Code-level registry (later: plugin) | — | PROVISIONAL |
| Booking | **Not a WP content type**; lives in the booking system | — | PROVISIONAL |
| Customer | **Not a WP entity**; guest data lives in the booking system | — | PROVISIONAL |

"Adding a tour must not require PHP changes" (Spec §5) is satisfied because **no template lists tours by name or ID**: every listing is a `WP_Query` over `post_type=tour` filtered by taxonomy/meta, and every card is one shared template part.

---

## 6. Global settings architecture (Spec §11)

### 6.1 Storage and access **(PROVISIONAL, TD-17)**
- A single **Settings page in the `vistula-core` plugin** (menu "Vistula Travel → Company"), tabbed: Company · Contact · Opening hours · Social · CTA · Footer & legal · Integrations (non-secret IDs only) · SEO defaults.
- Stored as one option (`vistula_settings`, array) via the Settings API, with a per-field `sanitize_callback`.
- **Not the Customizer.** Customizer values are stored as *theme mods*, which are per-theme and would vanish on a theme switch.
- Read **only** through `vistula_setting( 'phone' )` and friends. Templates never call `get_option()`.
- Core **Site Identity** (site title, tagline, site icon) stays where WordPress expects it. The **logo** is a plugin setting (attachment ID) so it is theme-independent.

### 6.2 How "change the phone once, update everywhere" works
| Consumer | Mechanism |
|---|---|
| Header, footer, contact page | Template function `vistula_setting()` |
| `tel:` links | Derived (C): digits and leading `+` extracted from the stored value |
| Schema.org `Organization` JSON-LD | Generated (C) from settings |
| Admin notification / auto-reply emails | Read from settings at send time |
| **Editor-written content** (a phone number inside a page) | **Block Bindings source** `vistula/setting`, plus a shortcode fallback. Verify supported block attributes in the pinned WordPress version. |
| Guard against typed-in numbers | A WP-CLI audit command scans post content for the settings' literal values and reports hard-coded copies. |

### 6.3 Settings catalogue
Field-level detail, including which fields are language-neutral or translatable, is in [`data-model.md` §13](data-model.md). Headline groups: company name, legal name, tax ID and registry ID, logo (light/dark), phone, email, address, geo-coordinates, opening hours, social links, default CTA (label + target), footer text, legal-page roles, default OG image, demo-site disclosure flag.

### 6.4 Secrets are not settings
API keys, SMTP credentials, payment secret keys and webhook secrets are **never** stored in the settings page or the database UI. They live in `wp-config.php` constants or environment variables outside Git (class B/D). Public identifiers (e.g. a publishable key) may appear in Integrations, but only if the provider documents them as public.

---

## 7. Media and image architecture (Spec §14)

### 7.1 Key clarification: source vs. delivered
The Spec's provisional figures (card 1600×1200, hero 1920×1080…) are **source (upload) sizes**. What the browser downloads is a set of **derivatives** generated by WordPress. A tour card is rendered at roughly 360–420 CSS px wide; a 3× phone needs ~1200 px. Delivering a 1600 px file into that slot wastes bandwidth. **⚠ Refinement:** keep the Spec's numbers as *upload guidance*, and add a defined derivative set per role.

### 7.2 Image roles **(PROVISIONAL, TD-23)**
| Role | Ratio | Upload minimum | Upload recommended | Derivative widths (hard-crop) | Format | Target weight¹ |
|---|---|---|---|---|---|---|
| **Tour card** | 4:3 | 1200×900 | 1600×1200 | 480, 800, 1200 | WebP | ≤ 60 KB @480 · ≤ 110 KB @800 · ≤ 180 KB @1200 |
| **Tour / destination hero** | 16:9 | 1600×900 | 1920×1080 | 640, 1024, 1440, 1920 | WebP | ≤ 60 KB @640 · ≤ 140 KB @1024 · ≤ 260 KB @1440 · ≤ 350 KB @1920 |
| **Square thumbnail / mobile hero** | 1:1 | 1200×1200 | 1200×1200 | 320, 640, 1200 | WebP | ≤ 30 / 80 / 170 KB |
| **Open Graph / social share** | 1.91:1 | 1200×630 | 1200×630 | 1200×630 | JPEG or WebP² | ≤ 200 KB |
| **Gallery** | natural | 1200 long edge | 2000 long edge | 800, 1200, 1600 (**uncropped**) | WebP | ≤ 200 KB @1600 |
| **Logo** | n/a | SVG (preferred) or PNG @2× | — | none | SVG / PNG | ≤ 20 KB |
| **Site icon** | 1:1 | 512×512 | 512×512 | core-generated | PNG | — |
| **Avatar (testimonial)** | 1:1 | 320×320 | 320×320 | reuse 1:1 set | WebP | ≤ 30 KB |

¹ Working targets — validate against real photographs in Phase 5, adjust, then freeze.
² Several social crawlers still prefer JPEG/PNG for `og:image`; decide during SEO phase.

**Rule:** the smallest upload minimum equals the largest derivative, so WordPress never upscales.

**Why several widths per ratio:** WordPress builds `srcset` only from sizes whose aspect ratio matches the requested size. A 4:3 card therefore needs *multiple* 4:3 sizes to get a proper `srcset`; a single 4:3 size would produce no candidates.

**Trim unused sizes:** disable core sizes the theme does not use, so each upload doesn't generate unused files.

### 7.3 Crop strategy
1. **Editors upload one good original** per photo. They do not pre-crop.
2. WordPress hard-crops each derivative around the centre.
3. **Focal point** (class A): a small pair of attachment fields `focal_x` / `focal_y` (0–100 %) editable in the Media Library. It feeds CSS `object-position` and lets a face or landmark survive a 4:3 or 1:1 crop. *(Custom crop-by-region on upload is TO EVALUATE, TD-23.)*
4. **Optional override:** Tour and Destination have an optional separate *hero image*; if empty, the featured image is used.

### 7.4 `object-fit` policy (Spec: "do not use `cover` everywhere")
| Situation | Technique |
|---|---|
| Card image whose slot ratio equals the derivative ratio (4:3 in 4:3) | **No `object-fit`.** `width:100%; height:auto`, ratio from the `width`/`height` attributes (no layout shift). |
| Full-bleed hero whose container ratio depends on the viewport | `object-fit: cover` **+** `object-position` from the focal point. Acceptable because the container ratio is genuinely variable. |
| Hero on portrait phones | Prefer `<picture>` with a **1:1** source (reusing the square set) below ~640 px, rather than cropping a 16:9 to a sliver. Final call after design (TO EVALUATE). |
| Gallery | Natural aspect ratio, never cropped. Optionally `contain` inside a lightbox. |
| Logo / icons / partner marks | `object-fit: contain`. |
| Avatars | 1:1 derivative; `cover` is harmless since the source is already 1:1. |

### 7.5 Responsive delivery
- Output through `wp_get_attachment_image()` so `width`, `height`, `srcset` and `sizes` are emitted by WordPress. Each component passes its **own `sizes`**, e.g. card in a 3-column grid: `(min-width:1200px) 384px, (min-width:768px) 33vw, 100vw`. Exact values are finalised after the grid is designed.
- **Loading:** below-the-fold images `loading="lazy"` + `decoding="async"`. The **LCP image** (hero) is **never lazy**: `loading="eager"` and `fetchpriority="high"`, optionally preloaded with `imagesrcset`. **DECIDED** (TD-25).
- **Formats:** WebP derivatives are the baseline. **AVIF** is TO EVALUATE (TD-24): it depends on GD/Imagick support on the host, has higher encode cost, and yields modest extra savings. WordPress core can accept and (host permitting) generate both formats; automatic *conversion* of JPEG uploads requires a filter or plugin. **Verify host capability in Phase 1.**
- **Upload ceiling:** 5 MB; editors are told to export at ~2000–2400 px long edge, quality 80–85.

### 7.6 Alt text, captions, credits
- Alt text lives on the attachment (`_wp_attachment_image_alt`). It is **meaningful**, describes the image's role, and is **empty** (`alt=""`) for purely decorative images (set by the template, not by an editor).
- **Multilingual alt text** needs one alt per language — depends on the multilingual plugin's media handling (TO EVALUATE, R-02).
- Add attachment fields `credit` and `licence` (class A). Real photographs have attribution and licence requirements; demo photography should be traceable.
- Naming convention: `vistula-{destination}-{subject}-{nn}.jpg`.

### 7.7 Replacing an image (Spec §14)
The client replaces the featured image/gallery in the Media Library or the tour edit screen. Templates read the attachment ID; no code edit is needed. If the *file* is replaced in place, derivatives must be regenerated — documented in the Client Guide.

---

## 8. Multilingual architecture (Spec §3)

**DECIDED:** languages are **Polish, English, Russian, Ukrainian**. **DECIDED:** no multilingual plugin during Phase 0 (per instruction). This section defines what the architecture must guarantee so a plugin can be added later **without rebuilding** the site.

### 8.1 Language registry **(PROVISIONAL, TD-19, TD-47)**
| Language | Code (BCP-47 / `hreflang` / `<html lang>`) | WP locale | Native name | URL prefix (proposed) |
|---|---|---|---|---|
| Polish | `pl` | `pl_PL` | Polski | `/pl/` |
| English | `en` | `en_GB` (OQ) | English | `/en/` |
| Russian | `ru` | `ru_RU` | Русский | `/ru/` |
| Ukrainian | **`uk`** | `uk` | Українська | `/uk/` (OQ-02) |

The Spec's label "UA" is a *country* code; technical identifiers use `uk` (F-16). UI labels use **native language names**, not flags and not the ambiguous "UK/UA".
**Default language:** not specified — **OQ-01**. Recommendation: English, because the Spec's audience is international visitors.

### 8.2 URL structure **(PROVISIONAL, TD-21)**
Language **subdirectories on one domain** (`/pl/…`, `/en/…`, `/ru/…`, `/uk/…`). Rationale: one domain, one SSL certificate, consolidated SEO authority, supported by every mainstream multilingual plugin. Rejected: subdomains and per-language ccTLDs (operationally heavy; no ccTLD fits all four).
Do **not** auto-redirect by browser language (harms crawlers and users who share links). Use `hreflang` + `x-default`, and at most a dismissible suggestion banner.

### 8.3 Architectural requirements (the compatibility contract)
| # | Requirement | Reason |
|---|---|---|
| M-1 | Every UI string goes through gettext (`__()`, `_x()`, `_n()`) with one text domain `vistula-travel`. No string concatenation for sentences; use ordered placeholders (`%1$s`). | Word order differs per language. |
| M-2 | **Plural forms.** Polish, Russian and Ukrainian each have three plural forms (English has two): "1 day / 2 days / 5 days" is 1 dzień / 2 dni / 5 dni. Durations and counts use `_n()` with correct `Plural-Forms`. | Common source of wrong text. |
| M-3 | No hard-coded URLs or IDs. Use `get_permalink()`, `vistula_page_url( role )`, `get_term_link()`. | Translated slugs; translated IDs. |
| M-4 | All business data via the data-access layer (`vistula_*`). A hook (`vistula_resolve_translation`) is a no-op now and is where a plugin maps IDs later. | Relationship fields store IDs. |
| M-5 | No raw SQL for content lookups. Use `WP_Query`/`get_posts`/`get_terms`, which multilingual plugins filter. | Language filtering. |
| M-6 | Enumerated values (difficulty, price type…) are **stored as stable machine keys** (`easy`) and labelled in code through gettext; not stored as translated text. | No term/string sync needed. |
| M-7 | Translatable **global settings** are separated from language-neutral ones (see [`data-model.md` §13](data-model.md)). | Phone/email are neutral; CTA label and opening-hours wording are not. |
| M-8 | Language switcher links to the **translation of the current page**; if none exists, the entry is hidden (never a silent link to the homepage). | UX + SEO. |
| M-9 | SEO output (title, description, canonical, OG, JSON-LD, `hreflang`) is produced through **filterable functions**. | One owner for meta (see §13). |
| M-10 | Forms capture the **submitter's language** in a hidden field; auto-replies and booking emails are sent in it. | Multilingual notifications. |
| M-11 | Dates, numbers and currency format per locale via `wp_date()` / `number_format_i18n()` / `intl`. Timezone fixed to `Europe/Warsaw`. | Locale correctness. |
| M-12 | Theme text is **never baked into images**. | Cannot translate. |
| M-13 | Layout tolerates **+30–40 % text length** vs. English (rule of thumb, to be tested with real RU/UK strings): buttons, nav, cards and filter labels wrap rather than truncate; no fixed-width text containers. | Different text lengths (Spec §3). |
| M-14 | Fonts must cover **Latin Extended-A** (ą ć ę ł ń ó ś ź ż) **and Cyrillic incl. Ukrainian** (є і ї ґ). Self-hosted, subsetted WOFF2 (TD-38). | Missing glyphs = fallback fonts, layout shift. |
| M-15 | Set `lang` on `<html>` and on inline foreign-language text (e.g. a Polish place name inside a Russian sentence when it matters for pronunciation). | Screen readers. |

### 8.4 Per-content behaviour
| Content | Translatable? | Notes |
|---|---|---|
| Pages, Posts | Yes, separate object per language | Standard plugin behaviour. |
| Tours, Destinations | Yes, separate object per language | Machine keys, IDs, numbers and dates are **language-neutral** and must be copied/synchronised; only text is translated. |
| Taxonomy terms (category, type, FAQ group) | Yes (name, description) | Slugs per language. |
| FAQ, Testimonials | Yes | See fallback policy for testimonials. |
| Menus | Yes, one per language | |
| Global settings | Mixed | Per-field flag. |
| Interface strings | Gettext `.po/.mo` | `languages/` in theme and plugin. |
| Media alt / caption | Per language (TO EVALUATE) | |
| SEO title / description / OG | Per language | Follows the object. |
| Contact / booking forms and emails | Per language | Labels via gettext; messages via templates. |

### 8.5 Fallback policy **(PROVISIONAL, TD-22)**
| Case | Behaviour |
|---|---|
| Tour/Destination/Post **not translated** into the current language | **Not listed** in that language (no mixed-language cards). Direct URL → 404 or a "not available in this language" notice with a link to the source language. |
| A translatable **setting** is empty for the current language | Fall back to the default language's value. |
| **Testimonial** with no translation | May show in the original language, labelled as such. |
| Menu missing for a language | Fall back to the default-language menu. |

### 8.6 Multilingual SEO
Reciprocal `hreflang` for every translated object (+ `x-default`), self-referencing canonical per language, `og:locale` + `og:locale:alternate`, per-language sitemap entries, translated slugs where the plugin supports them. **Translated base slugs** (`tours` → `wycieczki`) need plugin support; the architecture centralises base slugs in one config (B) so they can be swapped. Cyrillic slugs are legal but percent-encode in URLs; Latin transliteration for RU/UK is TO EVALUATE.

### 8.7 Plugin selection is a later, separate decision (TD-20)
Candidate categories (not installed): Polylang, WPML, TranslatePress, Weglot. Evaluation criteria and a mandatory **compatibility spike** (relationship fields, options, CPT slugs, media alt, menus) are in the roadmap (Stage 2 spike, Stage 11 decision).

---

## 9. Search and filtering (Spec §18)

### 9.1 Tour filtering **(PROVISIONAL, TD-26)**
A real `<form method="get">` (works without JavaScript) that rebuilds the query in `pre_get_posts`:

| Filter | Query parameter | Backed by | Notes |
|---|---|---|---|
| Category | `tour_category` | taxonomy | |
| Type / format | `tour_type` | taxonomy | |
| Destination | `destination` | relationship → `meta_query` (or shadow taxonomy — TD-14) | Wrapped in `Vistula\Queries::tours()`. |
| Duration | `duration` = `half-day` / `full-day` / `multi-day` | `duration_minutes` (numeric, C) | Buckets, not free input. |
| Price | `price_max` (and/or bucket) | `price_from` (numeric) | Numeric meta with `meta_type NUMERIC`. |
| Sort | `orderby` = `featured` / `price_asc` / `price_desc` / `duration` | menu_order / meta | |

- **Scalability:** a few hundred tours are comfortably served by `WP_Query` with numeric meta. The query builder is the only place that knows the storage, so it can move to a lookup table or shadow taxonomy without touching templates.
- **Rules:** no `posts_per_page = -1`; paginate; `no_found_rows` where pagination is not shown.
- **SEO:** filtered/sorted URLs use `noindex, follow` and a canonical to the clean listing. **Indexable landing pages** are the category and type archives and each Destination page (which lists its tours). This avoids thin duplicate pages.
- **Enhancement (Phase 14):** optional JavaScript that fetches the results fragment and updates history; the server-rendered form remains the fallback.
- **Not built initially:** facet counts ("Nature (3)"), which multiply query cost and complexity (TO EVALUATE).
- **Empty state:** a helpful "no tours match" message with a *clear filters* link.

### 9.2 Site search **(PROVISIONAL)**
Native WordPress search restricted to `tour`, `destination`, `post`, with results grouped by type. Extended to cover selected meta (short highlights, destination names) if relevance is poor; a dedicated search plugin is a later option (TO EVALUATE), external engines are out of proportion for this scale.

### 9.3 Polish diacritics **(TO EVALUATE, TD-27, R-18)**
Visitors type *Wroclaw*, *Lodz*, *Krakow*. MySQL Unicode collations match many accented letters to their base letter, but **`ł`/`Ł` is a distinct letter in some collations**, so `Wroclaw` may not match `Wrocław`. This is unverified and must be **tested empirically on the chosen database in Phase 1**. Mitigation if needed: a system-generated, ASCII-folded search-index field built with `remove_accents()` (which handles `ł`), used for both indexing and query normalisation.

---

## 10. Booking architecture (Spec §15) — boundaries only

### 10.1 Principle
Prefer a **reliable existing booking solution** to a custom engine, unless a strong reason emerges (Spec §15). Nothing is implemented in this phase.

### 10.2 What WordPress owns vs. what the booking system owns
| Concern | Owner | Class |
|---|---|---|
| Tour marketing content, price *display*, gallery, itinerary, FAQ | WordPress | A |
| `booking_enabled`, `booking_provider`, `booking_provider_ref` (external product ID) on the tour | WordPress | A/B |
| **Availability, capacity per date/time, slots, blackout dates** | **Booking system** | D (or the plugin's own tables) |
| Booking record, status, customer details, payment link, cancellation, refund | Booking system | D |
| Confirmation / notification emails | Booking system or WP (per solution), **in the customer's language** | D/B |
| The *Book* button, booking panel, thank-you page shell | WordPress theme | B |

This resolves F-05: a tour does **not** hold a second copy of availability. *(PROVISIONAL, TD-31, OQ-10.)*

### 10.3 Solution classes to evaluate (TD-30, OQ-05)
| Class | Description | Strength | Weakness |
|---|---|---|---|
| **A. WordPress plugin** | Booking/e-commerce plugin inside WP (often with a payments add-on) | Data in your DB; theme integration; brand control | Heavier; plugin lock-in; upgrade/security surface; multilingual complexity |
| **B. External tour-booking SaaS** | Hosted engine embedded or linked | Purpose-built availability/capacity; hosted checkout | Cost; limited theming; language support may not cover RU/UK; data location; may require a real merchant identity |
| **C. Custom engine** | Own CPT + availability logic | Total control | Highest effort and risk; **rejected unless justified** (Spec §15) |

**Evaluation criteria (must-haves):** PLN; Polish payment methods (BLIK, bank transfer, cards); capacity per date **and** time slot; group vs private tours; child price; cancellation/refund workflows; UI available in **PL/EN/RU/UK**; emails translatable; webhook/API access; sandbox/test mode; GDPR data-processing terms and EU data location; accessible booking UI (R-20); export of data; acceptable cost for a portfolio project.

### 10.4 Conceptual flow and status model
Flow (Spec §15): Tour → Book → Select date/time → Customer information → Payment → Confirmation.

Conceptual status model (to be reconciled with the chosen system):

`pending_payment → confirmed → completed`
`pending_payment → expired` (timeout) · `confirmed → cancelled_by_customer | cancelled_by_operator → refunded | partially_refunded` · `confirmed → no_show`

**Notifications (all in customer language):** customer confirmation, admin new-booking alert, reminder, cancellation, refund. **Business rules not yet given (OQ-09, OQ-19):** cancellation windows, refund percentages, minimum group size to run a tour.

### 10.5 Integration surface in the theme
Only three touchpoints: (1) a **booking panel** partial on the tour page, driven by `booking_enabled`; (2) the **Book Now** CTA target from settings; (3) a **confirmation** page template (`noindex`). Provider-specific code sits behind a thin adapter in `vistula-core`, so switching provider does not touch templates.

### 10.6 "Disabled" tours (F-15)
Three distinct states, kept separate: **Draft/Private** = hidden everywhere; **Published + booking off** = visible, "enquire" instead of "book"; **Published + booking on**. A short translatable `availability_note` ("Seasonal — May to September") covers the middle state.

---

## 11. Payment architecture (Spec §16) — boundaries only

### 11.1 Non-negotiables (DECIDED, TD-32)
- An **external payment provider** handles all card/bank data. WordPress never sees PANs or CVV.
- **Never** stored in WordPress, the theme or Git: card numbers, CVV, payment credentials, passwords, private API keys.
- Use the provider's **hosted checkout or hosted/tokenised fields**, keeping the site within the lightest PCI scope (SAQ-A-style). Any approach that posts raw card data to WordPress is rejected.

### 11.2 Rules for the future integration
| Rule | Detail |
|---|---|
| **Server-side source of truth** | Payment/booking status changes only from a **verified provider webhook** (signature checked) — never from the browser redirect alone. |
| **Idempotency** | Webhook handlers tolerate replays and out-of-order events. |
| **Secrets** | Server-side secret keys and webhook secrets in `wp-config.php`/environment. Only publishable/public keys may reach the browser. |
| **Test vs. live** | Separate credentials and a visible "test mode" indicator. **Live credentials never exist on local/staging.** |
| **Currency** | PLN. **BLIK (Poland's dominant online payment method) is available in PLN only**, which reinforces the single-currency decision (TD-15). |
| **SCA / 3-D Secure** | Delegated to the provider. |
| **Refunds** | Initiated through the provider's API/dashboard; the booking record is updated by webhook. |
| **Receipts / invoices** | Legal requirements for Polish B2C invoices/receipts must be checked with an accountant (OQ-19). |
| **Logging** | Log provider event IDs and outcomes only; never payloads containing personal data beyond what's needed. |

### 11.3 Provider (TD-33, TO EVALUATE)
Criteria: BLIK, Przelewy24/bank transfer, cards, Apple/Google Pay; PLN; refunds/partial refunds; webhooks; sandbox; EU entity/GDPR; fees; integration route compatible with the selected booking solution; ability to onboard a fictional/demo business in test mode. Candidate categories: global PSPs (e.g. Stripe, which documents BLIK and Przelewy24 support) and Polish PSPs (e.g. Przelewy24, PayU, Tpay). **No provider is chosen.**

### 11.4 Fictional business constraint (R-04, OQ-06)
A demo company should not take real money. Recommended default: the public portfolio site runs the payment flow in **provider test mode** with a visible demo disclosure. **PROVISIONAL, TD-50.**

---

## 12. Contact form architecture (Spec §17)

### 12.1 Fields
Name · Email · Phone · Subject · Message · optional Tour · optional preferred date — plus **⚠ additions:** a privacy-notice link/consent statement (OQ-15), a hidden **language** field (M-10), and honeypot/time-trap fields.

### 12.2 Implementation options (TD-34, TO EVALUATE)
| Option | For | Against |
|---|---|---|
| **Custom handler** (`admin-post.php` or REST) in `vistula-core` | Full control; portfolio value; no plugin dependency; easy to translate | Security burden is ours; must follow WP APIs strictly |
| **Form plugin** (e.g. Fluent Forms, WPForms, CF7 + storage) | Faster; UI; integrations | Another dependency; multilingual string handling; markup/accessibility control |

Leaning: custom, minimal, using only WordPress APIs — consistent with the Spec's requirement to "avoid insecure custom implementations" (§22) as long as we use core primitives.

### 12.3 Required behaviour (Spec §17)
- **Server-side validation** (required, email format, length limits, phone pattern, date not in the past, tour must be an existing published tour).
- **Sanitization on input** (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`) and **escaping on output** (`esc_html`, `esc_attr`).
- **Nonce** (`wp_nonce_field` / `wp_verify_nonce`) **and capability-appropriate action hook**.
- **Spam protection:** honeypot + minimum-time-to-submit + per-IP rate limit (transient). A CAPTCHA provider is TO EVALUATE (TD-35; privacy-friendly options preferred; requires consent handling).
- **Success and error states** with **Post/Redirect/Get** so refresh doesn't resubmit; errors re-render with the user's input preserved; accessible error summary.
- **Email notification** to the business; optional auto-reply to the visitor **in their language**.
- **Header-injection safe:** newlines stripped from subject/email fields; the visitor's address goes in **`Reply-To`**, never `From`.
- **No file uploads.**
- **No storage by default** (data minimisation). Storing enquiries as a private, expiring record is TO EVALUATE (OQ-15) — it protects against lost leads but adds GDPR retention and erasure duties.

### 12.4 Two architecture traps from the Spec's own lessons
1. **Local mail ≠ broken form** (Spec §29 lessons 5–6). Local development uses a **mail catcher** (Mailpit/MailHog-style) so form logic is testable without real delivery. Production delivery is a **separate test** (Stage 17–19).
2. **Full-page caching vs. nonces (R-06).** A cached page serves a stale nonce, and a valid form then fails. Options: exclude the contact page from page cache; or fetch the nonce with a tiny request at load; or rely on honeypot + time-trap + rate limit when cached. Chosen approach is fixed in the Contact stage (TD-34/35) and tested behind the production cache.

### 12.5 Production mail (TD-36)
`wp_mail()` over an authenticated transactional provider (SMTP/API) configured from constants, with SPF, DKIM and DMARC on the sending domain, "From" an address on that domain, and a delivery test as a formal QA item. **DECIDED:** production requires a real transport; **provider TO EVALUATE**.

---

## 13. SEO architecture (Spec §19)

### 13.1 Ownership model
**One component owns each output.** Whichever route is chosen, exactly one system emits `<title>`, meta description, canonical, robots, Open Graph, `hreflang` and JSON-LD; the theme never duplicates them.

**SEO plugin: TO EVALUATE (TD-28)** — the Spec forbids choosing one before the architecture is set. Selection criteria:
- reliable **multilingual** support (`hreflang`, per-language meta, sitemaps) with the eventual multilingual plugin;
- CPT/taxonomy support and per-object overrides;
- extensibility for **custom schema fed by our fields**;
- sitemap control and robots handling; redirects;
- performance footprint; admin UX for a non-technical client; licensing.
Alternative: a small in-house module for meta/OG/JSON-LD plus core sitemaps. Keeping meta generation behind filterable functions (M-9) keeps either route open.

### 13.2 Baseline requirements and implementation
| Requirement (Spec §19) | Approach |
|---|---|
| Semantic HTML, heading hierarchy | Template rule: one `<h1>` per page; components take a heading-level argument. |
| Unique titles / meta descriptions | `add_theme_support('title-tag')`; patterns per type; description fallback = excerpt → trimmed content; editors can override. |
| Canonical URLs | Self-referential; filtered/sorted variants canonicalise to the clean URL. |
| Clean slugs | Core `sanitize_title` (transliterates Polish diacritics, e.g. `Wrocław` → `wroclaw`); pattern `/tours/{slug}/`. |
| Image alt | See §7.6. |
| XML sitemap | Core `wp-sitemap.xml` covers posts, pages, CPTs, taxonomies; SEO plugin may replace. Exclude non-public CPTs and noindex archives. |
| `robots.txt` | Virtual file via `robots_txt` filter; references the sitemap. **Do not** `Disallow` parameterised URLs that carry `noindex` (the crawler must read the tag). |
| Open Graph | `og:title`, `og:description`, `og:image` (1200×630 crop), `og:type`, `og:url`, `og:locale` + alternates, `twitter:card=summary_large_image`. |
| Structured data | See below. |
| Internal linking | Destination ↔ tours ↔ posts relationships and breadcrumbs generate it automatically. |
| Indexability | `noindex` for search results, filtered listings, booking/confirmation pages, thank-you pages, author archives, staging. |

### 13.3 Structured data **(PROVISIONAL, TD-29)**
`Organization` (as `TravelAgency`) from Global Settings · `BreadcrumbList` · `Article` on guide posts · `TouristTrip` on tours (price, currency, itinerary) · `TouristDestination` on destinations. Validate with a rich-results test.
- **Do not** emit `Review` / `AggregateRating` for demo testimonials (R-12): fabricated reviews in markup violate search-engine guidelines and mislead users.
- `FAQPage` markup is low priority: Google limited FAQ rich results to a narrow set of sites, so it earns little; include only if cheap.

### 13.4 Staging
`noindex` + HTTP auth on non-production. The "Discourage search engines" flag being carried over to production is a classic launch defect — it is in the launch checklist.

---

## 14. Accessibility architecture (Spec §20)

**Target: WCAG 2.2 Level AA** *(PROVISIONAL, TD-39, OQ-18 — the Spec lists practices but no level).*

**Legal context.** Poland's Act implementing the European Accessibility Act has applied since 28 June 2025 and covers e-commerce services to consumers; **microenterprises providing services** (fewer than 10 staff and turnover/balance sheet ≤ €2 M) are exempt. A fictional portfolio business likely falls outside, but a booking/payment flow is exactly the type of service the Act targets, so AA is adopted as good practice and as a portfolio quality signal. *(Not legal advice; see OQ-19.)*

### 14.1 Architectural rules
| Area | Rule |
|---|---|
| Landmarks | `<header>`, `<nav aria-label>`, `<main id="main">`, `<footer>`; **skip link** first in `<body>`. |
| Headings | One `<h1>`; no skipped levels; card headings are chosen by context. |
| Keyboard | Everything operable by keyboard; **visible `:focus-visible`** on every interactive element (a design token, not an afterthought); no keyboard traps. |
| Target size | ≥ 24×24 CSS px (WCAG 2.2 SC 2.5.8); aim 44 px on touch. |
| Contrast | Palette tokens verified at 4.5:1 (text) and 3:1 (UI components, focus rings) **before** use. |
| Forms | Programmatic `<label>` on every control; errors linked with `aria-describedby`; `aria-invalid`; error summary with focus management; no placeholder-as-label; `autocomplete` attributes. |
| Mobile menu | A `<button>` with `aria-expanded` / `aria-controls`; `Escape` closes; focus returns to the trigger. |
| Accordions (FAQ) | Native `<details>/<summary>` first (no JS, keyboard and screen-reader friendly). |
| Links | Descriptive link text; repeated "Read more" links get visually hidden context ("…about Kraków & Wieliczka"). |
| Language switcher | Native names; `lang` on each item; `aria-current` for the active one. |
| Images | See §7.6. Text is never inside images. |
| Motion | Respect `prefers-reduced-motion`; no auto-playing carousels. |
| Maps | A text address and directions link is always present; embedded maps are supplemental. |
| Dates | Native `<input type="date">` first; any custom date picker must pass keyboard/screen-reader tests (booking phase, R-20). |
| Third-party widgets | Booking/payment widgets are evaluated for accessibility as a **selection criterion**. |
| Languages | `lang` attribute per language; text expansion tolerated (M-13). |

### 14.2 Verification
Automated (axe-core in CI, Lighthouse) catches a fraction of problems. The QA plan adds **manual keyboard-only passes**, a **screen-reader smoke test** (NVDA + VoiceOver) on the key journeys, zoom to 200–400 %, and forced-colours mode.

---

## 15. Performance architecture (Spec §21)

### 15.1 Budgets **(PROVISIONAL — validated at Stage 15)**
| Metric | Target |
|---|---|
| LCP | ≤ 2.5 s (mobile, 75th percentile) |
| INP | ≤ 200 ms |
| CLS | ≤ 0.1 |
| JavaScript (non-booking pages, gzip) | small, hand-written — target ≤ ~30 KB; no framework, no jQuery in theme code |
| Requests | no third-party requests by default |
| Lighthouse (mobile) | ≥ 90 performance / 100 accessibility & best practice / ≥ 95 SEO as a target, not a gate |

### 15.2 Rules
- **Assets:** conditional enqueue per template (booking JS only on booking templates); `defer`/module scripts; CSS split into base + component files; no unused libraries.
- **Fonts:** self-hosted WOFF2, subsetted (Latin, Latin-Ext, Cyrillic), one variable family if possible, `font-display: swap`, preload only the critical face. **No Google Fonts request** (also a GDPR concern in the EU).
- **Images:** §7 — correct sizes, `srcset`/`sizes`, WebP, explicit dimensions, LCP not lazy, others lazy.
- **Queries:** `WP_Query` with bounded `posts_per_page`; `no_found_rows` when no pagination; `fields => 'ids'` for ID-only needs; numeric meta typed as such; avoid N+1 by relying on WordPress's primed meta/term caches; transients (or object cache) for expensive repeated lists.
- **Caching:** host/page cache, browser caching for fingerprinted assets, optional object cache. Interaction with nonces is handled in §12.4.
- **No layout shift:** dimensions on all images and embeds; reserve space for the language switcher and fonts (`size-adjust` fallback metrics).
- **Third parties (maps, analytics, video, fonts):** none by default; loaded only after a decision (TD-48/49) and, where required, after consent.

---

## 16. Security and privacy architecture (Spec §22)

### 16.1 Application rules
| Rule | Detail |
|---|---|
| Sanitise input / escape output | Sanitise on save (`sanitize_callback` in `register_post_meta`/Settings API); escape late at the point of output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). |
| Nonces + capabilities | Every state-changing request checks both. Admin AJAX/REST endpoints have `permission_callback`. |
| Database | `$wpdb->prepare()` for any SQL; prefer `WP_Query`. |
| REST exposure | `show_in_rest` only where needed; restrict the users endpoint to prevent username enumeration; keep private CPTs out of REST. |
| No secrets in code | `wp-config.php` / environment only; `.gitignore` enforced; optional pre-commit secret scan. |
| Uploads | Editors cannot upload executable types. SVG (for the logo) requires safe handling/sanitisation — not enabled by default. |

### 16.2 Hardening baseline **(PROVISIONAL, TD-40)**
`DISALLOW_FILE_EDIT`; XML-RPC disabled if unused; least-privilege roles (client = *Editor*; developer = *Administrator*); **2FA** for administrators; login rate limiting; auto-updates policy for core and security releases; comments **disabled** (not in scope, spam vector — TD-41); author archives disabled/`noindex` (single-author site); `wp-json/wp/v2/users` restricted; security headers at the server (`Strict-Transport-Security` after verification, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and a **CSP in report-only mode first**); offsite, **restore-tested** backups; separate DB user with minimal rights.

### 16.3 Payment security
As §11.1–11.2. PCI scope minimised by hosted fields/checkout; webhook signature verification; no card data in logs.

### 16.4 Privacy (GDPR / ePrivacy)
Poland/EU law applies to visitor data. Minimise data; publish a privacy policy and cookie policy (OQ-19); no non-essential cookies/third-party requests without consent (OQ-22); processor agreements with hosting, mail and booking/payment providers; retention and erasure for any stored enquiry or booking data; WordPress privacy export/erase hooks implemented for any custom storage. *(Not legal advice.)*

---

## 17. Theme architecture

### 17.1 Directory layout (proposed) **(PROVISIONAL)**
```
wp-content/themes/vistula-travel/
├─ style.css                  ← theme header only (+ tiny base if needed)
├─ theme.json                 ← design tokens: palette, type scale, spacing, layout widths
├─ functions.php              ← thin: requires /inc files
├─ header.php  footer.php  index.php
├─ front-page.php  home.php  single.php  page.php  search.php  404.php  searchform.php
├─ single-tour.php  single-destination.php
├─ taxonomy-tour_category.php  taxonomy-tour_type.php  archive.php  category.php
├─ page-templates/            ← about, tours-listing, destinations-listing, faq, contact, booking
├─ template-parts/
│   ├─ layout/    (site-header, site-footer, nav, language-switcher, breadcrumbs, cta-band)
│   ├─ card/      (tour, destination, post, testimonial)
│   ├─ tour/      (hero, summary, price-box, itinerary, includes, gallery, booking-panel, faq, related)
│   ├─ destination/ (hero, highlights, things-to-do, tours, posts, map-link)
│   ├─ home/      (hero, popular-tours, why-us, destinations, how-it-works, featured, reviews, guide, cta)
│   ├─ filters/   (tour-filters)
│   └─ forms/     (contact)
├─ inc/                       ← setup, enqueue, menus, images, template-tags, hooks — NO data model
├─ assets/  css/ (tokens, base, layout, components/…)  js/  fonts/  icons/ (inline SVG sprite)
└─ languages/                 ← vistula-travel.pot
```
```
wp-content/plugins/vistula-core/
├─ vistula-core.php
├─ src/  PostTypes/ Taxonomies/ Fields/ Settings/ Media/ Queries/ Api/ (data-access) Forms/ Integrations/ (booking, payment adapters — later) Cli/
├─ config/  languages.php  image-sizes.php  enums.php  page-roles.php
├─ acf-json/ or fields/       ← field definitions in code/JSON (version-controlled)
└─ languages/
```

### 17.2 Non-negotiable header/footer rules (Spec §12, §29 lesson 2)
- `header.php` and `footer.php` exist in the theme root; every template calls `get_header()` / `get_footer()`.
- `header.php` outputs `<!DOCTYPE html>`, `<html <?php language_attributes(); ?>>`, `wp_head()`, `wp_body_open()`, the skip link and `wp_nav_menu()`; `footer.php` calls `wp_footer()`.
- **Guard against theme-compat:** if `header.php`/`footer.php` were missing, WordPress falls back to its *theme-compat* templates and logs a deprecation notice. QA item: **no theme-compat deprecation in the debug log**; a smoke test asserts the theme's header markup is present on every template.

### 17.3 Conventions
- **Escaping/i18n:** every printed value escaped; every string translatable; text domain `vistula-travel`.
- **Template parts** receive arguments via `get_template_part( $slug, $name, $args )`; a card never queries by itself.
- **CSS:** native CSS with custom properties fed from `theme.json`; low-specificity, component-scoped classes (BEM-like); `@layer` for order; mobile-first; **no build step initially** (PROVISIONAL, TD-37). A bundler is adopted only when a concrete need appears.
- **JS:** vanilla, progressive enhancement, ES modules, no jQuery in theme code; every interaction works without JS first (menu falls back to a visible list, filters submit as a form, accordions are `<details>`).
- **Style guide:** a dev-only, `noindex`, WordPress-rendered *style guide* template shows components with real data (not a static HTML prototype).

---

## 18. Plugin boundaries

### 18.1 Ownership
| Responsibility | Where | Notes |
|---|---|---|
| CPTs, taxonomies, meta registration, settings, image sizes, queries, data-access, contact handler | **`vistula-core` (own code)** | Survives theme change. |
| Presentation only | **Theme** | |
| Field-editing UI | Field framework (TD-12) | Field *definitions* are in Git, not only in the DB. |
| Multilingual | Third-party (TD-20, later) | Accessed through wrappers/filters only. |
| SEO | Third-party or in-house (TD-28) | Single owner of meta output. |
| Booking / Payment | Third-party or external (TD-30/33) | Through adapters. |
| SMTP / transactional mail | Third-party or host service | Secrets in config. |
| Caching, backup, security hardening | Host and/or plugin | Evaluated at production prep. |

### 18.2 Plugin admission checklist
A plugin is admitted only if: actively maintained (recent release, tested with the current WordPress and PHP), good security history, compatible with the multilingual and SEO choices, no heavy front-end assets by default, clean uninstall, licence and cost acceptable, GDPR-conscious, and an **exit strategy** exists (data can be exported or the functionality replaced). The default answer is "no plugin" when core or ~50 lines of code suffice. Keep the active-plugin count low and documented.

### 18.3 Currently chosen third-party plugins
**None.** Nothing was installed. All third-party items above are TO EVALUATE.

---

## 19. Git / GitHub workflow (Spec §23)

### 19.1 Repository **(DECIDED name/owner per Spec; layout PROVISIONAL, TD-42)**
`Artem23334/vistula-travel`. **Tracked:** `docs/`, the theme, the `vistula-core` plugin, tooling config, CI, `.env.example`, seed-content scripts, README. **Not tracked:** WordPress core, `wp-config.php`, `.env`, the database, `uploads/`, third-party plugin code (pinned by documented version or Composer/WPackagist — TO EVALUATE), `node_modules/`, `.DS_Store`, logs, local credentials.
```
vistula-travel/
├─ docs/  wp-content/{themes/vistula-travel, plugins/vistula-core}/  seed/  tools/  .github/workflows/
├─ .gitignore  .editorconfig  .env.example  composer.json (optional)  README.md
```

### 19.2 Workflow
`change → test → git diff → commit → push` (Spec §23). Trunk-based: `main` is always deployable; short-lived `feature/…` branches; **Conventional Commits** (`feat:`, `fix:`, `docs:`, `chore:`…); one logical change per commit; tag releases (`v0.1.0`…). Even as a solo developer, use pull requests as a review checkpoint and to trigger CI.

### 19.3 Automation **(PROVISIONAL)**
GitHub Actions: PHP lint, **WordPress Coding Standards (PHPCS)**, PHPUnit, optional Stylelint/ESLint, secret scanning. Keep CI light at first. **Note:** the WordPress Coding Standards 3.4.1 security release (27 July 2026) means CI should pin a version ≥ 3.4.1 and never run the *WordPress-Extra* ruleset over untrusted third-party code.

### 19.4 Content is code (where reasonable) **(PROVISIONAL, TD-43)**
The six demo tours and destinations are reproducible from **seed scripts** (WP-CLI) that resolve references **by slug**, not by hard-coded IDs (R-15). Demo photos are committed only if their licences allow it (OQ-20, OQ-24); otherwise referenced in `docs/credits.md` and fetched by script.

---

## 20. Testing strategy (Spec §24)

### 20.1 Layers
| Layer | Tooling (proposed) | Covers |
|---|---|---|
| Static | PHP lint, PHPCS/WPCS | Coding standards, escaping/nonce sniffs |
| Unit / integration | PHPUnit + WP test suite | Duration normalisation, price formatting, query builders, enum labels, sanitizers |
| End-to-end | Playwright | Navigation, menus, filters, search, contact form (with mail catcher), tour CRUD via admin, language switch (later), booking/payment (sandbox) |
| Visual | Playwright screenshots | Desktop / tablet / mobile at agreed breakpoints; per language once translated |
| Accessibility | axe-core, Lighthouse, manual | §14.2 |
| Performance | Lighthouse CI / WebPageTest | §15 budgets |
| Content integrity | WP-CLI audit command | Every published tour has required fields, images have alt, relationships resolve, no hard-coded phone/email, no orphan translations |
| Security | Checklist, header scan, plugin vulnerability check | §16 |
| Production | Separate manual checklist | Email delivery, DNS/SSL, redirects, sitemap/robots, cache behaviour, payment **test-mode → mode switch** |

### 20.2 The Spec's own test lists, mapped
- **Functional:** navigation, menus, filters, search, forms, tours, destinations, languages, booking, payment, links, redirects → E2E + smoke suite.
- **Visual:** desktop / tablet / mobile → visual layer.
- **CMS:** add / edit / delete-or-disable tour, change price, image, gallery, global phone/email, publish article, edit destination, manage FAQ, manage multilingual content → a **CMS acceptance script** run as a human checklist each phase and partly automated.
- **Technical:** console errors, PHP errors (debug log clean), broken images, 404s, missing assets, forms, redirects, booking, payment → automated crawler + log review.
- **Break/fix exercises** (Spec §24, later): a curated set of deliberate faults (bad data, missing image, broken relationship, template error, JS error, language gap) used to practise diagnosis — planned as a teaching appendix in the Portfolio stage.

### 20.3 Policy
Test after every phase (Spec §30.8). A phase is closed only when its exit criteria pass **and** the smoke suite of earlier phases still passes (regression). Browsers: current Chrome, Firefox, Safari (macOS + iOS), Edge; representative low-end Android.

---

## 21. Production and deployment (Spec §25)

### 21.1 Environments
| Env | Purpose | Notes |
|---|---|---|
| **Local** (DECIDED) | Development | Tooling TO CONFIRM (OQ-17). Mail catcher installed. Never holds live secrets. |
| **Staging** (TD-45, TO EVALUATE) | Pre-production rehearsal | Recommended: the Spec insists production is a separate test environment (§29 lesson 7); a staging copy makes the launch a rehearsed event. `noindex` + auth. |
| **Production** (DECIDED) | Live | Real SMTP, HTTPS, live or **test-mode** payments per OQ-06. |

### 21.2 Hosting requirements (TD-47, host TO EVALUATE)
PHP **8.3 recommended** (8.2 minimum modern baseline; verify at build time — WordPress 7.0's minimum is documented as PHP 7.4, with 8.3 recommended, and a higher database floor of MySQL 8.0 / MariaDB 10.6 is reported), HTTPS with HTTP/2 or HTTP/3, GD or Imagick **with WebP** (and AVIF if adopted), ≥ 256 MB PHP memory, real cron for WP-Cron, SSH + WP-CLI, automated offsite backups with restore, staging feature, EU data location (GDPR, latency to Poland), and outbound SMTP or an API mail path.

### 21.3 Deployment path (Spec §25)
`Local → backup → GitHub → hosting → migrate DB → uploads → theme → plugins → domain → DNS → SSL → production → final QA`

Mechanism is TO EVALUATE (TD-46): manual SFTP (error-prone), Git pull on server, **GitHub Actions → SSH/rsync of theme and plugin** (recommended direction), or host-managed Git. Database and uploads move by a documented procedure (`wp search-replace` handles serialised data safely; never a raw find-and-replace on a SQL dump).

### 21.4 Launch checklist (headline items)
Discourage-search-engines flag **off** · `home`/`siteurl` correct · permalinks flushed · no mixed content · canonical host (apex vs `www`) + redirect · HSTS only after HTTPS verified · SPF/DKIM/DMARC live · **real** contact-form delivery test · payment mode confirmed · sitemap submitted to Search Console/Bing · robots.txt correct · 404/redirect monitoring · uptime monitoring · verified backup **and** a rollback plan · legal pages and cookie consent live · demo-site disclosure visible (TD-50).

---

## 22. Classification matrix

| Element | Class | Notes |
|---|---|---|
| Tour text, prices, gallery, itinerary, highlights, included/not included | **A** | Through wp-admin |
| Tour URL slug, taxonomy terms, featured flag/order | **A** | |
| Computed duration (minutes), computed "from" sort key, reading time | **C** | Derived from A |
| `srcset`, `sizes`, image derivatives, WebP/AVIF files | **C** | Generated from A uploads under B config |
| Image size definitions, ratios, crop rules | **B** | `vistula-core` config |
| Focal point, alt text, credit | **A** | Media Library |
| Company name, phone, email, address, hours, social links, default CTA, footer text | **A** | Global Settings |
| `tel:` link, JSON-LD Organization | **C** | Derived from A |
| Legal identity (company legal name, tax ID) | **A** (fictional/demo values) | OQ-19 |
| Navigation menus | **A** | WP Menus |
| Footer tour/destination lists, related tours/posts | **C** | Queries |
| FAQ content, testimonials, blog posts, destination content | **A** | |
| Post-type / taxonomy registration, field definitions, enums, page-role keys | **B** | Git |
| Templates, CSS, JS, fonts, SVG icons | **B** | Git |
| Interface strings (source) | **B** | Gettext |
| Interface strings (translations) | **A/B** | Human translators; files in Git |
| Sitemap, canonical, `hreflang`, OG tags, robots.txt | **C** (+ A overrides) | Generated by the SEO owner |
| SEO title/description overrides | **A** | |
| Language registry | **B** | Later mirrored by the multilingual plugin (D) |
| Translations of content | **A** | Through the plugin UI |
| Availability, capacity, slots, blackout dates | **D** (booking system) | F-05 |
| Booking records, statuses, customer data | **D** | Never in the theme |
| `booking_enabled`, provider reference on tour | **A/B** | |
| Booking / payment emails | **D** or **B** (per solution) | Customer language |
| Payment processing, tokens, refunds, 3-D Secure | **D** | |
| Payment secret keys, webhook secrets, SMTP credentials | **B** (config outside Git) | Never in DB UI |
| Contact form submission → email | **B** logic, **D** delivery | |
| Spam-protection service | **D** | If a CAPTCHA provider is chosen |
| DNS, SSL certificate, hosting, backups | **D** | |
| Nonces, sessions, transients | **C** | |
| Cookie-consent tool | **D** | If chosen |

---

*End of `project-architecture.md`. Continue with [`data-model.md`](data-model.md).*
