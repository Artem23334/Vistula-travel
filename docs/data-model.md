# Vistula Travel — Data Model

> **Status:** Phase 0 (Architecture). This is a *design*, not an implementation. Nothing here has been registered in WordPress yet.
> **Source of truth:** the Spec (`Vistula_Travel_Project_Specification.docx`). `(Spec §n)` marks Spec-derived items; **⚠** marks a proposed refinement or addition that needs owner approval.
> **Companions:** [`project-architecture.md`](project-architecture.md) · [`technical-decisions.md`](technical-decisions.md) · [`implementation-roadmap.md`](implementation-roadmap.md)

---

## 0. Conventions

### 0.1 Column legend used in field tables
| Column | Values |
|---|---|
| **Type** | `text`, `textarea`, `richtext` (limited blocks/wysiwyg), `number`, `decimal`, `bool`, `enum` (select from a code-defined list), `time` (`HH:MM`), `date`, `url`, `email`, `image` (attachment ID), `gallery` (ordered attachment IDs), `rel` (post-ID relationship), `list` (one string per row), `group` (repeater of sub-fields) |
| **Req** | **R** = required to publish · **O** = optional · **—** = not editable |
| **Rep** | ✔ = repeatable / multi-value |
| **i18n** | **T** = translatable text (differs per language) · **N** = language-neutral (identical in every language; must be *copied/synchronised*, never re-typed) · **S** = structural (relationship/ID that must map to the *translated* target) |
| **Class** | **A** client-editable · **B** developer-controlled · **C** system-generated · **D** external (see architecture §0.2) |

### 0.2 Naming and storage rules
1. **Meta keys** are `snake_case`, prefixed by entity (`tour_`, `destination_`, `faq_`, `testimonial_`, `post_`). Keys are an API: **renaming after launch is a data migration**.
2. **System-generated (class C) keys** are prefixed with an underscore (`_duration_minutes`) so they stay out of the Custom Fields UI.
3. **Enumerations are stored as stable machine keys** (`easy`), never as translated text. Labels come from code via gettext (TD-10, architecture M-6).
4. **Money** is stored as a decimal string with two places in PLN, never as a float. Display formatting is per locale.
5. **Times** are stored `HH:MM` (24 h) in `Europe/Warsaw`. Dates are ISO `YYYY-MM-DD`.
6. **Booleans** are stored as `'1'` / `''`. Flag meta is sparse — absent means false — to keep queries cheap.
7. **Field definitions live in Git** (code or exported JSON), not only in the database (TD-12).
8. **Templates read data only through the data-access layer** (`vistula_tour( $id )`, `vistula_destination( $id )`, `vistula_setting( $key )`, …) — never a field framework's or `get_post_meta()`'s raw API (TD-13). The layer returns typed arrays with defaults applied, and is the place where translation hooks attach (architecture M-4).

### 0.3 Framework-neutral
This model does not depend on a specific field plugin. Field framework selection is TO EVALUATE (TD-12); the requirements it must meet are listed in [`technical-decisions.md`](technical-decisions.md).

---

## 1. Entity overview

```
 CORE                     CUSTOM POST TYPES              TAXONOMIES
 page  (roles)            tour ⭐                         tour_category  (hierarchical)
 post  (Travel Guide)     destination ⭐                  tour_type
 attachment (Media)       faq          (non-public)      faq_group
 nav_menu                 testimonial  (non-public)      category / post_tag (core, for posts)

 NOT WP CONTENT (external): Booking · Customer · Payment
 CONFIG: Global Settings · Language registry · Enumerations · Image sizes · Page roles
```

| Type | Implementation | Public? | Status |
|---|---|---|---|
| Tour | CPT `tour` | ✔ | DECIDED |
| Destination | CPT `destination` | ✔ | DECIDED |
| Blog / Travel Guide | core `post` | ✔ | DECIDED |
| Tour Category | taxonomy `tour_category` | archive | PROVISIONAL |
| Tour Type | taxonomy `tour_type` | archive | PROVISIONAL |
| FAQ | CPT `faq` + taxonomy `faq_group` | ✘ | PROVISIONAL |
| Testimonial | CPT `testimonial` | ✘ | PROVISIONAL |
| Itinerary Item | repeatable group inside Tour | ✘ | PROVISIONAL |
| Booking / Customer | external booking system | ✘ | PROVISIONAL |
| Media | attachments + extra fields | — | DECIDED |
| Language | code registry | — | PROVISIONAL |
| Company / Global Settings | plugin settings + accessor | — | PROVISIONAL |

---

## 2. Tour (`tour`) — DECIDED (type), PROVISIONAL (fields)

### 2.1 Purpose
The sellable product: a guided tour or experience. It must be manageable in bulk (dozens–hundreds) with **zero PHP changes** to add, edit or remove one (Spec §5); every listing, filter, related block and card derives from queries over this type.

### 2.2 WordPress implementation
| Setting | Value |
|---|---|
| Post type key | `tour` |
| `public` / `show_ui` / `show_in_rest` | true / true / true (block editor + REST for later JS) |
| `has_archive` | **false** — listing is a real Page (architecture §4.3, TD-18) |
| Rewrite | `slug: tours`, `with_front: false` (**required**: otherwise the posts-permalink prefix `/travel-guide/` would leak into tour URLs) |
| Supports | `title`, `editor`, `excerpt`, `thumbnail`, `revisions`, `page-attributes` (manual order via `menu_order`) |
| Taxonomies | `tour_category`, `tour_type` |
| Capabilities | default post capabilities (client = *Editor*) |
| Slug/base | configurable in one config file so translated bases can be added later |

### 2.3 Fields

**General**
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `post_title` | Title | text | R | | T | A | ≤ ~70 chars recommended (cards). Spec §5 "title". |
| `post_name` | Slug | text | R (auto) | | T | A/C | Clean, per language where supported. |
| `post_excerpt` | Short description | textarea | R | | T | A | Native excerpt. 120–160 chars guidance; used on cards, meta-description fallback. Spec §5 "short description". |
| `post_content` | Full description | richtext (block editor) | R | | T | A | Spec §5 "full description". |
| `_thumbnail_id` | Featured image | image | R | | N¹ | A | Card (4:3) and default hero. Spec §5 "featured image". |
| `tour_hero_image` | Hero image (override) | image | O | | N¹ | A | ⚠ Optional separate 16:9 source; falls back to featured image. |
| `tour_gallery` | Gallery | gallery | O | ✔ | N¹ | A | Ordered attachment IDs. Recommend 4–12. Spec §5 "gallery". |

¹ The image is shared; **alt text and captions** are per-language on the attachment (architecture §7.6).

**Pricing** (Spec §5)
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `tour_price_from` | Price (from) | decimal ≥ 0 | R | | N | A | Base "from" price. Also feeds the numeric sort/filter key `_price_sort` (C). |
| `tour_price_type` | Price type | enum `per_person` / `per_group` | R | | N | A | Values pending OQ-09. |
| `tour_price_child` | Child price | decimal | O | | N | A | Optional (Spec). |
| `tour_child_age_note` | Child price applies to | text | O | | T | A | ⚠ e.g. "Ages 4–12". Definition pending OQ-09. |
| `tour_price_private` | Private-tour price | decimal | O | | N | A | Optional (Spec). Semantics pending OQ-07/OQ-09. |
| `tour_private_group_max` | Private price covers up to | number | O | | N | A | ⚠ Only meaningful if a private price is set. |
| *(currency)* | — | — | — | | — | B | ⚠ **Refinement:** the Spec lists *currency* as a Tour field. It is instead **one site-wide constant, PLN** (TD-15), because mixed currencies break price sort/filter and BLIK is PLN-only. |

**Details** (Spec §5)
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `tour_destinations` | Destinations | rel → `destination` | R (≥1) | ✔ | S | A | **First item = primary destination.** Replaces free-text "location" (F-13). |
| `tour_duration_value` | Duration | decimal | R | | N | A | e.g. `1`, `2`, `4.5`. |
| `tour_duration_unit` | Duration unit | enum `hours` / `days` | R | | N | A | Structured, not free text, so it can be filtered and localised. |
| `_duration_minutes` | Duration (sort key) | number | — | | N | **C** | hours×60 or days×1440. |
| `_duration_bucket` | Duration bucket | enum `half_day` / `full_day` / `multi_day` | — | | N | **C** | Filter key. Thresholds PROVISIONAL (e.g. ≤ 5 h = half day; 1 day or > 5 h = full day; ≥ 2 days = multi day). |
| `tour_meeting_point` | Meeting point | textarea | R | | T | A | Human description. |
| `tour_meeting_lat` / `tour_meeting_lng` | Meeting point coordinates | decimal | O | | N | A | For a map link; validated ranges. Map provider TO EVALUATE (TD-48). |
| `tour_departure_time` | Departure time | time | R (1-day) | | N | A | Indicative; the booking system's slots take precedence. |
| `tour_return_time` | Return time | time | O | | N | A | For multi-day tours refers to the last day. |
| `tour_group_min` / `tour_group_max` | Group size | number | O / R | | N | A | Marketing display. **Not** booking capacity (F-05). |
| `tour_difficulty` | Difficulty | enum `easy` / `moderate` / `challenging` | R | | N | A | Labels via gettext. |
| `tour_min_age` / `tour_max_age` | Age limits | number | O | | N | A | `min ≤ max` validated. |
| `tour_guide_languages` | Guide languages | enum multi (list from config) | O | ✔ | N | A | ⚠ **Not in the Spec.** Which languages the tour is *conducted* in (independent of site UI language). Proposed because the audience is multilingual (OQ-11). |

**Content** (Spec §5)
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `tour_highlights` | Highlights | list | R | ✔ | T | A | 3–6 items, one line each. |
| `tour_included` | What's included | list | R | ✔ | T | A | |
| `tour_not_included` | What's not included | list | O | ✔ | T | A | |
| `tour_what_to_bring` | What to bring | list | O | ✔ | T | A | |
| `tour_important_info` | Important information | richtext (limited) | O | | T | A | Paragraphs, lists, links only. |
| `tour_faqs` | FAQs | rel → `faq` (ordered) | O | ✔ | S | A | Curated, **ordered**; order is editorial so it is stored on the tour. |

**Itinerary** (Spec §5) — see [§9](#9-itinerary-item-repeatable-group)
| Key | Label | Type | Req | Rep | i18n | Class |
|---|---|---|---|---|---|---|
| `tour_itinerary` | Itinerary | group | O | ✔ | mixed | A |

**Booking-related** (Spec §5)
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `tour_booking_enabled` | Booking enabled | bool | O (default off) | | N | A | Off ⇒ the tour shows *Enquire* instead of *Book*. |
| `tour_booking_provider_ref` | Booking provider reference | text | O | | N | A/B | External product/tour ID. Validated by the future adapter. |
| `tour_availability_note` | Availability note | text | O | | T | A | ⚠ e.g. "Seasonal — May to September". |
| *(available dates, capacity)* | — | — | — | | — | **D** | ⚠ **Not stored in WordPress.** Owned by the booking system (F-05, TD-31, OQ-10). |

**Merchandising**
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `tour_is_popular` | Show in "Popular Tours" | bool | O | | N | A | ⚠ Manual selection for the homepage until real booking data exists. |
| `menu_order` | Display order | number | O | | N | A | Manual order within listings. |

**SEO** — title/description/OG overrides belong to the SEO owner (TD-28), **not** to this model.

### 2.4 Taxonomies and relationships
- `tour_category` (theme) and `tour_type` (format): both multi-assign — see [§4](#4-taxonomies).
- Relationships **stored on the tour:** `tour_destinations`, `tour_faqs`.
- Relationships **derived** (no stored field): related tours, testimonials for the tour (`testimonial_tour`), guide articles about the tour (`post_tours`).

### 2.5 Multilingual behaviour
One WP object **per language**. Text fields are translated; **N** fields (price, duration, difficulty, ages, times, coordinates, gallery IDs, booking reference) are *synchronised* from the source and must not be independently editable per language — otherwise a price change has to be made four times. **S** fields (destinations, FAQs) point to the **translated** target. A tour missing in a language is not listed in it (architecture §8.5).

### 2.6 Admin editing experience
- Field groups arranged as panels/tabs: **General · Pricing · Details · Content · Itinerary · Booking · Relationships**.
- Inline help text and character guidance on title/excerpt; units shown beside numbers (PLN, h/days).
- **Publish-time validation** (PROVISIONAL): a tour cannot be *published* without title, short description, featured image, price, price type, duration, ≥1 destination, meeting point, difficulty; the editor sees a clear list of what's missing. Drafts save freely.
- Conditional fields: child-age note only when a child price is set; private-group-max only when a private price is set.
- Admin list columns: thumbnail, price, duration, destination(s), category, booking on/off, popular ★ — with filters by category/destination.
- No developer knowledge required to add a tour.

### 2.7 Frontend usage
Tour card (listings, home, related, destination page) · single tour page (hero, summary, price box, details, highlights, included lists, itinerary, gallery, FAQ, related, testimonials, booking panel) · search results · JSON-LD (`TouristTrip`) · OG data · footer "Tours" list.

---

## 3. Worked example — one Tour as the data layer returns it

What `vistula_tour( $id )` would hand to a template for the first demo tour. Values marked *(Spec)* come from Spec §4; the rest are **illustrative placeholders**, not approved copy. Note that templates receive **typed, defaulted, already-resolved** data — no meta keys, no field-framework calls.

```jsonc
{
  "id": 123,
  "title": "Kraków & Wieliczka",                     // (Spec) T
  "slug": "krakow-wieliczka",
  "url": "https://…/tours/krakow-wieliczka/",        // via get_permalink(), language-aware
  "excerpt": "…",                                    // T
  "price": { "from": "399.00", "currency": "PLN",    // (Spec) from 399 PLN
             "type": "per_person", "child": null, "private": null },
  "duration": { "value": 1, "unit": "days",          // (Spec) 1 day
                "minutes": 1440, "bucket": "full_day" },
  "destinations": [ { "id": 45, "title": "Kraków", "url": "…" } ],   // first = primary
  "categories": ["city-tours", "history"],           // illustrative
  "types": ["group"],                                // illustrative
  "meeting_point": { "text": "…", "lat": null, "lng": null },
  "times": { "departure": "08:00", "return": "18:30" },              // illustrative
  "group": { "min": 2, "max": 12 },                                  // illustrative
  "difficulty": { "key": "easy", "label": "Easy" },  // label from gettext
  "age": { "min": null, "max": null },
  "guide_languages": ["en", "pl"],                   // proposed field, OQ-11
  "highlights": ["…", "…"],
  "included": ["…"], "not_included": ["…"], "what_to_bring": ["…"],
  "important_info_html": "…",
  "itinerary": [ { "day": 1, "time": "09:00", "title": "…", "description": "…" } ],
  "images": { "featured_id": 901, "hero_id": null, "gallery_ids": [902, 903, 904] },
  "faq_ids": [210, 211],
  "booking": { "enabled": false, "provider_ref": null, "note": null }
}
```

---

## 4. Taxonomies

### 4.1 `tour_category` — theme of the tour *(PROVISIONAL, TD-09)*
| Property | Value |
|---|---|
| Hierarchical | **Yes** (UI checkboxes; allows future nesting such as *Nature → Mountains*), initial terms flat |
| Rewrite | `tours/category/{term}/` (`with_front: false`) |
| Multi-assign | Yes |
| Term fields (A) | `sort_order` (number), `term_image` (image, optional), description (native, T) |
| Multilingual | Term per language; slug per language |

**Seed terms ⚠ (reconciliation pending OQ-08):** City Tours · Nature · Mountains · History · Culture & Food.

### 4.2 `tour_type` — format of the tour *(PROVISIONAL, TD-09)*
| Property | Value |
|---|---|
| Hierarchical | No |
| Rewrite | `tours/type/{term}/` |
| Term fields | `sort_order`, description |
| **Seed terms ⚠** | Group Tour · Private Tour · Weekend Trip |

**⚠ Refinement of the Spec's category list.** The Spec (§6) lists *Private Tours* and *Weekend Trips* as **categories**, but they describe **format/duration**, not theme, and the Spec's filter list (§18) has "category" and "tour type" as *separate* filters. Splitting them gives two orthogonal, filterable axes. Consequences: nav submenu items and the *Culture & Food* term need reconciling (F-01, F-09, F-10, OQ-07, OQ-08). If the owner prefers the Spec's flat list, the model degrades gracefully to a single `tour_category`.

### 4.3 `faq_group` — grouping on the FAQ page *(PROVISIONAL)*
Non-hierarchical; used only to group entries on the FAQ page. **Seed terms ⚠ (placeholders):** General · Booking & Payment · Tours & Logistics · Practical Information. Terms are translatable.

### 4.4 Core taxonomies for posts
`category` (Travel Guide sections) and `post_tag` (used sparingly; tag archives `noindex`). Rename/remove "Uncategorized". Seed categories: Travel Tips · Things To Do · Polish Food · Culture · *(Poland Travel? — OQ-08)*. Recommend **not** creating a *Destinations* category (F-02): `post_destinations` covers it.

---

## 5. Destination (`destination`) — DECIDED (type), PROVISIONAL (fields)

### 5.1 Purpose
A place people travel to. A hub that aggregates that place's tours, guide articles, FAQs and media, and ranks for "things to do in {place}" queries.

### 5.2 WordPress implementation
| Setting | Value |
|---|---|
| Post type key | `destination` |
| `public` / `show_ui` / `show_in_rest` | true / true / true |
| `has_archive` | **false** — listing is a Page (TD-18) |
| Rewrite | `slug: destinations`, `with_front: false` |
| Supports | `title`, `editor`, `excerpt`, `thumbnail`, `revisions`, `page-attributes` |
| Taxonomies | none initially |

### 5.3 Fields
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `post_title` | Title | text | R | | T | A | e.g. "Zakopane & the Tatra Mountains" |
| `post_excerpt` | Short description | textarea | R | | T | A | Teaser for cards. |
| `post_content` | Description | richtext | R | | T | A | Spec "description". |
| `_thumbnail_id` | Featured image | image | R | | N | A | |
| `destination_hero_image` | Hero image (override) | image | O | | N | A | 16:9 source, falls back to featured. |
| `destination_gallery` | Gallery | gallery | O | ✔ | N | A | Spec "gallery". |
| `destination_type` | Place type | enum `city` / `region` | R | | N | A | Distinguishes cities from regions like Masuria (F-14). |
| `destination_highlights` | Highlights | list | O | ✔ | T | A | Spec "highlights". |
| `destination_things_to_do` | Things to do | group ✔ (`title` T·R, `description` T·O, `image` N·O) | O | ✔ | mixed | A | Spec "things to do". Short curated entries; guides live in Posts (F-21). |
| `destination_travel_tips` | Travel tips | list | O | ✔ | T | A | Spec "travel tips". |
| `destination_lat` / `destination_lng` | Map centre | decimal | O | | N | A | Spec "map/location information". Embedded maps TO EVALUATE (TD-48); a text location + external map link is always available. |
| `destination_faqs` | FAQs | rel → `faq` (ordered) | O | ✔ | S | A | Spec "FAQ". Curated on the destination. |
| `destination_featured_tours` | Featured tours (override) | rel → `tour` (ordered) | O | ✔ | S | A | Optional editorial override. **Default is the derived query.** |
| `menu_order` | Display order | number | O | | N | A | |
| *(related tours)* | — | — | — | | — | **C** | ⚠ Spec lists this as a field; it is **derived** from `tour_destinations` (F-06). |
| *(related blog posts)* | — | — | — | | — | **C** | Derived from `post_destinations`. |

### 5.4 Relationships
Tours → **derived** from `tour_destinations`. Posts → **derived** from `post_destinations`. FAQ → **stored here** (`destination_faqs`). Media → gallery/hero fields.

### 5.5 Multilingual · admin · frontend
Same pattern as Tour (§2.5–2.7). Admin: panels *General · Highlights & Tips · Things to do · Map · FAQ · Curation*; admin columns show tour count (C). Frontend: destination card (grid/home/footer), single page (hero, intro, highlights, things to do, **tours here** (query), **guides about {place}** (query), tips, map link, FAQ), JSON-LD `TouristDestination`.

---

## 6. Blog Post — the Travel Guide (core `post`) — DECIDED

### 6.1 Purpose
Editorial travel content that drives organic traffic and internal links to tours and destinations.

### 6.2 Are standard Posts correct? **Yes.** *(Spec §8 default, confirmed by analysis.)*
| For core Posts | Against a custom `guide` CPT |
|---|---|
| Native block editor, revisions, RSS, categories/tags, author, sitemap, comment controls, `Article` schema fit | Only justified if "news" and "guide" were different content streams — they are not |
| Best-tested by every multilingual/SEO plugin | Another type to register, translate and template |
| Editors already know it | No functional gain |

No "strong reason for another implementation" exists.

### 6.3 Fields
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `post_title`, `post_excerpt`, `post_content`, featured image, categories, tags, author, date | native | — | R/O | | T | A | Spec §8. Featured image: 16:9 master preferred; card uses the 4:3 crop (focal point helps). |
| `post_tours` | Related tours | rel → `tour` | O | ✔ | S | A | Spec "related tours". |
| `post_destinations` | Related destinations | rel → `destination` | O | ✔ | S | A | Spec says "related destination" (singular). ⚠ Multi-value with the first as primary — an article can span places. |
| `_reading_time` | Reading time | number | — | | N | **C** | Computed from content. |

### 6.4 Rules
- Permalink `/travel-guide/{slug}/` via the posts permalink structure; the *Travel Guide* Page is the posts page.
- **Comments disabled** site-wide; **author archives disabled/`noindex`**; tag archives `noindex` (TD-41).
- Frontend: article, guide cards (home, destination page, tour page), category archives, `Article` JSON-LD, related tours block.

---

## 7. FAQ (`faq`) — PROVISIONAL (TD-07)

### 7.1 Purpose
Reusable question/answer entries shown on the FAQ page (grouped), and selectively on tour and destination pages. *(The Spec names FAQ as a page, a destination field and a translatable type but defines no model — F-19.)*

### 7.2 Why a CPT and not a repeater
An answer such as "What should I wear?" applies to many tours: a repeater would be copied and drift. A CPT makes each answer a single, translatable, reusable record. **Non-public** because single FAQ pages would be thin content.

### 7.3 Implementation
`public: false`, `publicly_queryable: false`, `show_ui: true`, `show_in_rest: true`, `exclude_from_search: true`; supports `title`, `editor` (paragraph/list/link blocks only), `page-attributes`, `revisions`; taxonomy `faq_group`.

### 7.4 Fields
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `post_title` | Question | text | R | | T | A | |
| `post_content` | Answer | richtext (limited) | R | | T | A | Avoid volatile figures (prices, times) in shared answers. |
| `faq_group` | Group | taxonomy | O | ✔ | T | A | Groups the FAQ page. |
| `faq_show_on_page` | Show on the FAQ page | bool (default on) | O | | N | A | Turn off for entries used only on one tour. |
| `menu_order` | Order within group | number | O | | N | A | |

### 7.5 Relationships
Referenced **by** tours (`tour_faqs`) and destinations (`destination_faqs`) as **ordered curated lists** (order is editorial, so it lives on the consumer). Global FAQ page = all entries with `faq_show_on_page`, grouped.

### 7.6 Multilingual · admin · frontend
Translated per language; group terms translated; the FAQ page lists only FAQs that exist in the current language. Admin: simple list table with group filter and drag-order. Frontend: accessible `<details>` accordion (architecture §14); optional `FAQPage` JSON-LD (low value).

---

## 8. Testimonial (`testimonial`) — PROVISIONAL (TD-08)

### 8.1 Purpose
Social proof for the homepage "Reviews" section and tour pages. **These are demo content for a fictional company and must be presented as such** (OQ-13, R-12).

### 8.2 Implementation
`public: false`, `show_ui: true`, `show_in_rest: false`; supports `title` (author name), `editor` (quote), `page-attributes`.

### 8.3 Fields
| Key | Label | Type | Req | Rep | i18n | Class | Notes |
|---|---|---|---|---|---|---|---|
| `post_title` | Author name | text | R | | N | A | First name + initial. |
| `post_content` | Quote | textarea | R | | T | A | In the original language. |
| `testimonial_quote_lang` | Original language | enum (language codes) | R | | N | A | Enables "originally in …" labelling. |
| `testimonial_country` | Author country | enum (ISO 3166-1 alpha-2) | O | | N | A | Localised country name is derived at display time. |
| `testimonial_rating` | Rating | number 1–5 | O | | N | A | **Never** emitted as `Review`/`AggregateRating` markup (TD-29). |
| `testimonial_tour` | Tour | rel → `tour` (single) | O | | S | A | Shown on that tour; derived reverse. |
| `testimonial_travel_month` | Travel month | text `YYYY-MM` | O | | N | A | |
| `testimonial_avatar` | Photo | image | O | | N | A | Requires the person's consent (real people); avoided in demo content. |
| `testimonial_featured` | Show on homepage | bool | O | | N | A | |

### 8.4 Multilingual
Translation is optional. Fallback (TD-22): a testimonial without a translation may appear in its original language, labelled as such; all other content types are never shown untranslated.

---

## 9. Itinerary Item — repeatable group inside Tour *(PROVISIONAL, TD-06)*

### 9.1 Purpose
The ordered "what happens when" of a tour (Spec §5: time, title, description, repeatable).

### 9.2 CPT or repeater? **Repeater.**
An itinerary item is not reusable across tours, has no URL, is never queried independently, and is always edited *with* its tour. A CPT would add thousands of near-empty posts and translation overhead for no benefit. (Rule of thumb in §15.)

### 9.3 Structure of one row
| Sub-field | Type | Req | i18n | Notes |
|---|---|---|---|---|
| `day` | number ≥ 1 | R (default 1) | N | ⚠ **Not in the Spec** — needed for multi-day tours (*Masurian Lakes*, 2 days) (F-24). |
| `time` | time `HH:MM` | O | N | Spec "time". |
| `title` | text | R | T | Spec "title". |
| `description` | textarea / minimal richtext | O | T | Spec "description". |

### 9.4 Behaviour
- Rows are stored in editorial order, and rendered **grouped by `day`** as an ordered list, with a localised heading ("Day 2" / "Dzień 2" / "День 2" / "День 2") via `_n()`/`_x()`.
- Validation: `day` ≤ total days (when unit = days); rows kept in day/time order; missing time is allowed.
- Translation: the row *structure* (day, time, order) must stay in sync; only `title`/`description` are translated. This is the hardest field for a translation plugin — a **compatibility-spike item** (R-02).
- Frontend: itinerary section on the tour page; optional structured-data `itinerary`.

---

## 10. Booking concept — external, not a WP content type *(PROVISIONAL, TD-31)*

**Conceptual attributes** (to be reconciled with the chosen system; **none** are implemented in WordPress in this phase):

| Group | Attributes |
|---|---|
| Identity | booking reference (external ID) · created / updated timestamps |
| What | tour reference (`tour_booking_provider_ref`) · date · time slot |
| Who / how many | adults · children · (private flag) · language of the customer |
| Money | price breakdown · currency (PLN) · payment status · amount paid · refund amount |
| Lifecycle | status (`pending_payment`, `confirmed`, `completed`, `expired`, `cancelled_by_customer`, `cancelled_by_operator`, `refunded`, `partially_refunded`, `no_show`) · cancellation reason |
| Comms | notification log (customer confirmation, admin alert, reminder, cancellation, refund) |

**Ownership:** booking system (class D). If the chosen solution is a WordPress plugin, its own tables/post types are used and **not** re-modelled here. **Not** a public CPT; never exposed via REST.

---

## 11. Customer concept — external, minimal *(PROVISIONAL, TD-31)*

Attributes: name · email · phone · preferred language · consent records. **Data-minimisation rules:** no WP user accounts for customers by default (guest booking); personal data lives only in the booking/payment systems (and, if chosen, an expiring enquiry record — OQ-15); retention periods and erasure handled per OQ-19. Marketing opt-in/newsletter is **out of scope** unless confirmed (OQ-23).

---

## 12. Media — DECIDED

### 12.1 Purpose and implementation
All imagery lives in the **Media Library** (Spec §14); WordPress generates derivatives from the image-size registry (architecture §7). The client replaces images without code changes.

### 12.2 Attachment fields
| Key | Label | Type | Req | i18n | Class | Notes |
|---|---|---|---|---|---|---|
| `_wp_attachment_image_alt` | Alt text | text | R (except decorative) | T (per language, TO EVALUATE) | A | Meaningful; describes purpose. |
| `post_excerpt` | Caption | text | O | T | A | |
| `attachment_credit` | Photographer / source | text | O | N | A | ⚠ Attribution traceability (OQ-20). |
| `attachment_licence` | Licence | enum (`own`, `cc0`, `cc_by`, `cc_by_sa`, `stock_licensed`, `other`) | O | N | A | ⚠ |
| `attachment_focal_x` / `_y` | Focal point | number 0–100 | O (default 50/50) | N | A | Drives `object-position` and crop safety. |

### 12.3 Roles → sizes
See the table in architecture §7.2 (tour card 4:3, hero 16:9, square 1:1, OG 1.91:1, gallery uncropped, logo, avatar).

---

## 13. Company / Global Settings — PROVISIONAL (TD-17)

Stored as one array option via a plugin settings page; read only through `vistula_setting( $key, $lang = null )`. **i18n** marks language-neutral (**N**, one value) vs translatable (**T**, one value per language, falling back to the default language when empty — TD-22).

| Tab | Key | Label | Type | Req | i18n | Notes |
|---|---|---|---|---|---|---|
| Company | `company_name` | Brand name | text | R | N | "Vistula Travel". |
| | `company_legal_name` | Legal name | text | O | N | ⚠ Demo/placeholder values (OQ-19). |
| | `company_tax_id`, `company_registry_id` | Tax ID (NIP) / registry no. | text | O | N | ⚠ Footer "legal information" (Spec §12). Placeholder for a fictional company. |
| | `tagline` | Tagline | text | R | T | "Discover Poland. Your way." (core `blogdescription` may carry it.) |
| | `logo_id`, `logo_dark_id` | Logo (light / dark) | image | R / O | N | SVG preferred (safe-SVG handling, architecture §16.1). |
| | `default_og_image_id` | Default share image | image | R | N | 1200×630. |
| Contact | `phone` | Phone | text (validated) | R | N | Displayed as typed; `tel:` derived (C). |
| | `email` | Public email | email | R | N | |
| | `contact_form_recipient` | Form notifications go to | email | R | N | ⚠ Separate from the public email; never exposed. |
| | `address_street`, `address_postcode`, `address_city`, `address_country` | Address | text | R | N | Structured (feeds JSON-LD). |
| | `geo_lat`, `geo_lng` | Coordinates | decimal | O | N | |
| | `directions_note` | Directions note | text | O | T | |
| Hours | `hours` | Opening hours | group: 7 fixed rows (`open`, `close`, `closed`) | R | N | Day names localised at display. |
| | `hours_note` | Hours note | text | O | T | e.g. "By appointment on public holidays". |
| Social | `social_links` | Social links | group ✔ (`network` enum, `url`) | O | N | Per-language override TO EVALUATE. |
| CTA | `cta_label` | Default CTA label | text | R | T | "Book Now". |
| | `cta_target_role` | CTA target | enum of page roles (`booking`, `tours`, `contact`) | R | N | ⚠ Before booking exists it resolves to `tours` (OQ-14). |
| Footer & legal | `footer_text` | Footer blurb | textarea | O | T | |
| | `copyright_text` | Copyright line | text with `{year}`/`{company}` tokens | O | T | |
| | `demo_notice_enabled`, `demo_notice_text` | Demo-site disclosure | bool / text | R / R | N / T | ⚠ TD-50. |
| SEO defaults | `seo_default_description` | Default meta description | textarea | O | T | Fallback only. |
| Integrations *(non-secret)* | `booking_provider` | Booking provider | enum (`none`) | R | N | Reserved. |
| | `maps_provider` | Maps provider | enum (`none`) | R | N | Default none (privacy, TD-48). |
| | *(secrets)* | — | — | — | — | **Never here** — `wp-config.php`/environment. |

**Page roles registry** (part of Settings; architecture §4.4): `home`, `tours`, `destinations`, `travel_guide`, `about`, `faq`, `contact`, `booking`, `privacy`, `cookies`, `terms`, `cancellation` → Page ID, **S** (mapped per language).

**Fixed configuration (class B, not editable):** currency `PLN` and its per-locale display style (`399 zł` / `PLN 399`), timezone `Europe/Warsaw`, image-size registry, enumerations, language registry.

### 13.1 Home page fields (page-level, class A)
The Spec asks the homepage to use dynamic content "where appropriate" (§10). Structure below; anything a query can supply is **not** typed in.
| Section | Editable (A) | Dynamic (C) |
|---|---|---|
| Hero | Headline (T), sub-headline (T), image (N), CTA label/target (T/N) | — |
| Popular Tours | Section heading (T), count | Tours with `tour_is_popular`, ordered by `menu_order` |
| Why Choose Us | Heading (T); repeatable items (icon key N, title T, text T) | — |
| Destinations | Heading (T) | Published destinations by `menu_order` |
| How It Works | Heading (T); repeatable steps (title T, text T) | — |
| Featured Experience | A **single tour** chosen via `home_featured_tour` (rel, S); optional override blurb (T) | Everything else from the tour |
| Reviews | Heading (T) | Testimonials with `testimonial_featured` |
| Travel Guide | Heading (T) | Latest N posts |
| CTA | Heading (T), label/target from settings | — |

About, Contact and legal pages use **block content**; Contact additionally consumes Global Settings. A team/staff model is **not** in the Spec and is not created (OQ-23).

---

## 14. Language — PROVISIONAL (TD-19, TD-47)

A **configuration registry** in code (class B), later mirrored by the multilingual plugin (D). Templates use `vistula_current_language()` and `vistula_languages()`.

| Property | `pl` | `en` | `ru` | `uk` |
|---|---|---|---|---|
| WP locale | `pl_PL` | `en_GB` (OQ) | `ru_RU` | `uk` |
| `hreflang` / `<html lang>` | `pl` | `en` | `ru` | `uk` |
| Native name | Polski | English | Русский | Українська |
| Plural rule | 3 forms | 2 forms | 3 forms | 3 forms |
| Direction | ltr | ltr | ltr | ltr |
| Default? | OQ-01 | recommended | | |
| URL prefix | `/pl/` | `/en/` | `/ru/` | `/uk/` (OQ-02) |

No flags in the UI. The Spec's "UA" label is not used technically (F-16).

---

## 15. Repeatable fields — analysis

**Rule of thumb — repeater or its own post type?** Use a **repeater** when the item is edited only with its parent, is not reused, needs no URL, and is not queried on its own. Use a **CPT** when it is reused across parents, needs independent translation/ordering/curation, or needs its own listing.

| Field | Structure | Choice | Why |
|---|---|---|---|
| `tour_gallery`, `destination_gallery` | ordered attachment IDs | gallery field | Ordering; media reuse |
| `tour_highlights` / `included` / `not_included` / `what_to_bring`, `destination_highlights` / `travel_tips` | list of one-line strings | **list** field (repeater of a single text sub-field, or one-per-line textarea — implementation follows TD-12) | Simple; returned as `string[]` by the data layer either way |
| `tour_itinerary` | group (`day`, `time`, `title`, `description`) | **repeater** | §9 |
| `destination_things_to_do` | group (`title`, `description`, `image`) | **repeater** | Edited with the destination; not reused |
| `tour_destinations`, `tour_faqs`, `destination_faqs`, `destination_featured_tours`, `post_tours`, `post_destinations` | ordered ID lists | **relationship** | Reuse; referential integrity |
| Settings `hours` (7 rows), `social_links` | groups | repeater | Settings-only |
| Home "Why choose us", "How it works" | groups | repeater | Page-only |
| FAQ entries | — | **CPT**, *not* a repeater | Reused across tours/destinations |
| Testimonials | — | **CPT** | Reused; curated; filterable |

---

## 16. Relationships — ownership matrix

**Principle:** store each relationship on **one** side (the side edited most, or where order is editorial). The reverse is **derived by query** through one function, so storage can change without touching templates (TD-14).

| Relationship | Stored on (field) | Target | Cardinality | Ordered | Reverse (derived) | Required |
|---|---|---|---|---|---|---|
| Tour → Destination | Tour `tour_destinations` | destination | many | ✔ (first = primary) | Destination page "Tours here" | ≥ 1 |
| Tour → FAQ | Tour `tour_faqs` | faq | many | ✔ | — | no |
| Destination → FAQ | Destination `destination_faqs` | faq | many | ✔ | — | no |
| Destination → Tour (curation) | `destination_featured_tours` | tour | many | ✔ | — | no (override only) |
| Post → Tour | Post `post_tours` | tour | many | — | Tour page "Guides" | no |
| Post → Destination | Post `post_destinations` | destination | many | first = primary | Destination page "Guides" | no |
| Testimonial → Tour | Testimonial `testimonial_tour` | tour | one | — | Tour page "Reviews" | no |
| Home → featured Tour | Home `home_featured_tour` | tour | one | — | — | no |
| Tour ↔ Tour (related) | **none (derived)** | — | — | — | Same destination first, then same category; exclude self; stable order; limit 3 | — |

**Reverse-lookup implementation** (TD-14, TO EVALUATE): a relationship field stores IDs (typically a serialised array), so "all tours for destination X" is a `meta_query` on that value. That is fine at hundreds of tours and is confined to `Vistula\Queries::tours_for_destination()`. If it ever proves slow or awkward — or a plugin's multilingual mapping makes it fragile — the same function can be re-pointed at a **synchronised hidden taxonomy** or a lookup table.

---

## 17. Query patterns and performance

| Use | Query outline | Notes |
|---|---|---|
| Popular tours (home) | `tour`, `tour_is_popular = 1`, order `menu_order`, limit 6 | Sparse flag |
| Tours listing + filters | `tour`; tax_query (category/type); meta on `_price_sort` (NUMERIC), `_duration_bucket`, destination relation; paginate | Numeric meta typed; no `-1` |
| Tours for a destination | destination relation (`Queries::tours_for_destination`) | Primary destination ranked first |
| Related tours | same destination → same category, exclude self, limit 3 | No random ordering (cache-friendly) |
| FAQs for a tour | `post__in` = `tour_faqs`, `orderby=post__in` | Preserves editorial order |
| Reviews (home) | `testimonial`, `testimonial_featured = 1`, order `menu_order`, limit 6 | |
| Latest guide posts | `post`, limit 3 (optionally by destination) | |
| Search | `tour`, `destination`, `post`, current language only | Diacritic folding TO EVALUATE (TD-27) |

General: `no_found_rows` when no pagination; prime term/meta caches (defaults) and avoid per-card queries; transients/object cache for repeated expensive lists.

---

## 18. Illustrative seed catalogue (Spec §4, §7)

Spec data is marked **[Spec]**; everything else is **illustrative and needs content approval**. Prices are demo content (Spec §4).

| # | Title [Spec] | Duration [Spec] | From price [Spec] | Destination (proposed) | Category (illustrative) | Type (illustrative) |
|---|---|---|---|---|---|---|
| 1 | Kraków & Wieliczka | 1 day | 399 PLN | Kraków | City Tours, History | Group |
| 2 | Zakopane & Tatra Mountains | 1 day | 349 PLN | Zakopane / Tatra | Mountains, Nature | Group |
| 3 | Gdańsk & Baltic Coast | 1 day | 399 PLN | Gdańsk | City Tours, Nature | Group |
| 4 | Warsaw Experience | 1 day | 249 PLN | Warsaw | City Tours, Culture & Food | Group |
| 5 | Wrocław | 1 day | 299 PLN | Wrocław | City Tours, History | Group |
| 6 | Masurian Lakes Nature Escape | 2 days | 899 PLN | Masuria | Nature | Weekend Trip |

Destinations [Spec §7]: **Warsaw · Kraków · Gdańsk · Wrocław · Zakopane / Tatra Mountains · Masuria** (`destination_type`: five `city`, Masuria `region`; Zakopane is a town-plus-mountain area — treated as `city`, a judgement call).
Observations: *Kraków & Wieliczka* spans two places — Wieliczka is not a listed destination, so it is content within Kraków (F-14). The bare title *"Wrocław"* is terse next to the others — a copy decision. Seed content is created by **WP-CLI scripts that resolve relationships by slug** (TD-43, R-15).

---

## 19. Validation and integrity

**On save/publish (PROVISIONAL):** required-field gate (§2.6); `price_from` numeric ≥ 0; child price ≤ adult when per person (warning); `min_age ≤ max_age`; `group_min ≤ group_max`; `HH:MM` for times; latitude −90…90 / longitude −180…180; itinerary `day` ≥ 1 and ≤ duration days; destination targets exist and are published; `booking_enabled` requires a configured provider (once booking exists); featured image should have alt text (warning).

**Integrity audit (WP-CLI, repeatable):** published tours missing required fields · relationships pointing at deleted/unpublished/wrong-type posts · images without alt · orphan translations / unsynchronised N-fields · literal phone/email found in post content (should be a binding/shortcode) · terms with no items.

---

## 20. Enumerations catalogue (code-defined, translatable labels) — PROVISIONAL

| Enum | Machine keys |
|---|---|
| `tour_price_type` | `per_person`, `per_group` |
| `tour_duration_unit` | `hours`, `days` |
| `_duration_bucket` (C) | `half_day`, `full_day`, `multi_day` |
| `tour_difficulty` | `easy`, `moderate`, `challenging` |
| `destination_type` | `city`, `region` |
| `tour_guide_languages` | `pl`, `en`, `ru`, `uk` (+ candidates such as `de`, pending OQ-11) |
| `social network` | `facebook`, `instagram`, `youtube`, `tiktok`, `x`, `linkedin`, `tripadvisor` (extendable) |
| `attachment_licence` | `own`, `cc0`, `cc_by`, `cc_by_sa`, `stock_licensed`, `other` |
| `cta_target_role` | subset of page roles |

---

## 21. Candidate fields deliberately **not** added (need owner confirmation)

To avoid inventing requirements, these are *not* in the model. Each could be added cheaply later because templates read through the data layer.

Pick-up/transport details · accessibility (e.g. wheelchair) information · meals included · physical-effort details · per-tour cancellation policy text · seasonality (months available) · "best time to visit" (Destination) · discount/sale price and "was" price (adds consumer-law display duties) · promo codes · video URL · tour tags such as "family-friendly" · staff/guide profiles · newsletter sign-up · user accounts · review submission form · multi-currency display. See OQ-11, OQ-23.

---

*End of `data-model.md`. Continue with [`technical-decisions.md`](technical-decisions.md).*
