# Vistula Travel — Technical Decisions, Open Questions and Risks

> **Status:** Phase 0 (Architecture). Written 2026-09-21.
> **Source of truth:** the Spec (`Vistula_Travel_Project_Specification.docx`).
> **Companions:** [`project-architecture.md`](project-architecture.md) · [`data-model.md`](data-model.md) · [`implementation-roadmap.md`](implementation-roadmap.md)

This is the **decision log**. Nothing here has been silently decided: each item carries a status, and anything unclear is an **Open Question (OQ)** with a default that keeps the project moving but can be overridden.

| Tag | Meaning |
|---|---|
| **DECIDED** | Fixed by the Spec or by technical necessity. Change only with explicit approval. |
| **PROVISIONAL** | Recommended default. Build on it, but it may be revised at the named gate. |
| **TO EVALUATE** | Not decided. Criteria and options are documented; decision at the named stage. |

---

## 1. Decision register

| ID | Decision | Status | Origin | Decide / revisit at |
|---|---|---|---|---|
| TD-01 | WordPress-first; no static HTML prototype | **DECIDED** | Spec §13 | — |
| TD-02 | Classic PHP theme (`header.php`, `footer.php`, `wp_head`, `wp_footer`, `wp_nav_menu`); `theme.json` for tokens | **DECIDED** | Spec §12 | — |
| TD-03 | Custom theme (no page builder, no commercial parent theme) | **DECIDED** | Spec §28 | — |
| TD-04 | Tours and Destinations are CPTs `tour` and `destination` | **DECIDED** (type) / PROVISIONAL (slugs) | Spec §5, §7 | Stage 5 |
| TD-05 | Travel Guide = core Posts | **DECIDED** | Spec §8 + analysis | — |
| TD-06 | Itinerary = repeatable group inside Tour, not a CPT | PROVISIONAL | Analysis | Stage 5 |
| TD-07 | FAQ = non-public CPT + `faq_group` taxonomy; curated per tour/destination | PROVISIONAL | Analysis | Stage 5/9 |
| TD-08 | Testimonial = non-public CPT | PROVISIONAL | Analysis | Stage 6 |
| TD-09 | Two taxonomies: `tour_category` (theme) + `tour_type` (format) | PROVISIONAL | Analysis; OQ-07/08 | Stage 5 |
| TD-10 | Enumerations stored as machine keys; labels via gettext | PROVISIONAL | Analysis | Stage 5 |
| TD-11 | Data model + business logic in plugin `vistula-core`; theme = presentation | **DECIDED** | Analysis; OQ-04 (approved) | — |
| TD-12 | Field framework: **Secure Custom Fields (SCF)**, via Local JSON | **DECIDED** (code-level); runtime spike still pending | Analysis; OQ-03 | Stage 2 (decided) / real-WP verification pending |
| TD-13 | Templates use a data-access layer, never a framework API | **DECIDED**; implemented (`vistula_setting`, `vistula_page_url`, `vistula_tour`, `vistula_destination`, `vistula_resolve_translation`) | Analysis | — |
| TD-14 | Relationships stored on one side; reverse derived by query; shadow taxonomy held in reserve | PROVISIONAL / TO EVALUATE (storage) | Analysis | Stage 5 |
| TD-15 | Single site currency: PLN | PROVISIONAL | Analysis (F-12) | Stage 5 |
| TD-16 | Duration = value + unit → computed minutes + bucket | PROVISIONAL | Analysis | Stage 5 |
| TD-17 | Global Settings = plugin settings page + accessor; not Customizer | PROVISIONAL | Analysis | Stage 3 |
| TD-18 | Listing pages are real Pages; CPT `has_archive=false`; page-roles registry | PROVISIONAL (rewrite test not runnable — see §3 note) | Analysis | Stage 5 (rewrite test, once a CPT exists) |
| TD-19 | Plugin-agnostic multilingual contract (architecture M-1…M-15); language codes `pl en ru uk` | **DECIDED** (languages, no plugin now); Stage 2 code audit against M-1/M-3/M-4 passed | Spec §3; Prompt 01 | Stage 11 |
| TD-20 | Multilingual plugin selection | **TO EVALUATE** — mandatory throwaway-sandbox spike still not run (needs real WordPress) | Prompt 01 | Stage 2 spike (blocked on environment) → Stage 11 |
| TD-21 | Language URLs as subdirectories on one domain | PROVISIONAL | Analysis; OQ-01/02 | Stage 11 |
| TD-22 | Fallback policy (untranslated content not listed; settings fall back to default language) | PROVISIONAL | Analysis | Stage 11 |
| TD-23 | Image strategy: per-role ratios, derivative sets, focal point, `object-fit` policy | PROVISIONAL | Spec §14 + analysis | Stage 5 (validate with real photos) |
| TD-24 | AVIF derivatives | **TO EVALUATE** | Analysis | Stage 2 (host check) / 15 |
| TD-25 | Lazy-load everything except the LCP image (`eager` + `fetchpriority=high`) | **DECIDED** | Best practice | — |
| TD-26 | Search/filter: server-rendered GET forms → `WP_Query`; filtered URLs `noindex` + canonical | PROVISIONAL | Analysis | Stage 5 |
| TD-27 | Diacritic-folded search index (`ł`) | **TO EVALUATE** — code-level review only; live collation test needs a real DB (see §3 note) | Analysis | Stage 5 (test, once destinations/DB exist) |
| TD-28 | SEO plugin vs in-house meta module | **TO EVALUATE** | Spec §19 | Stage 15 (framework decided earlier) |
| TD-29 | Structured data set; **no** `Review`/`AggregateRating` for demo reviews | PROVISIONAL | Analysis | Stage 15 |
| TD-30 | Booking solution | **TO EVALUATE** | Spec §15; OQ-05 | Start of Stage 12 |
| TD-31 | Booking/Customer are not WP content types; availability owned by the booking system | PROVISIONAL | Analysis (F-05); OQ-10 | Stage 12 |
| TD-32 | External payment provider; no card data/credentials/secret keys in WP, theme or Git; webhooks are the source of truth | **DECIDED** | Spec §16, §22 | — |
| TD-33 | Payment provider | **TO EVALUATE** | Spec §16 | Stage 13 |
| TD-34 | Contact form: custom handler vs plugin | **TO EVALUATE** (lean: custom) | Spec §17 | Stage 9 |
| TD-35 | Spam protection method (honeypot/time-trap/rate-limit; CAPTCHA?) | **TO EVALUATE** | Spec §17 | Stage 9 |
| TD-36 | Production mail: real authenticated transport (provider TO EVALUATE) | **DECIDED** (principle) | Spec §17, §29 | Stage 9 / 17 |
| TD-37 | No build step at first; native CSS with tokens; vanilla progressive-enhancement JS | PROVISIONAL | Analysis | Stage 4 |
| TD-38 | Self-hosted, subsetted fonts covering Latin-Ext + Cyrillic | PROVISIONAL | Analysis | Stage 4 |
| TD-39 | Accessibility target WCAG 2.2 AA; native `<details>` accordions | PROVISIONAL | Analysis; OQ-18 | Stage 4 |
| TD-40 | Security hardening baseline (architecture §16.2) | **DECIDED** (principles) / PROVISIONAL (list) | Spec §22 | Stage 16 |
| TD-41 | Comments disabled; author and tag archives `noindex` | **DECIDED**; implemented (`vistula-core/src/Content/comments-and-archives.php`) | Analysis | — |
| TD-42 | Repo `Artem23334/vistula-travel`; layout, trunk-based flow, Conventional Commits, CI | **DECIDED** (name/owner, CI) / PROVISIONAL (rest of layout) | Spec §23 | Stage 2 (CI decided) |
| TD-43 | Demo content is reproducible from WP-CLI seed scripts resolving relations by slug | PROVISIONAL | Analysis (R-15) | Stage 5 |
| TD-44 | Test toolchain: PHPCS/WPCS, PHPUnit, Playwright, axe, Lighthouse CI | **DECIDED** (PHPCS/WPCS/lint — implemented) / PROVISIONAL (PHPUnit, Playwright, axe, Lighthouse) | Analysis | Stage 2 (lint/PHPCS done) → 16 (rest) |
| TD-45 | Environments: Local + Production; **Staging** | **DECIDED** (local/prod) / TO EVALUATE (staging) | Spec §25; OQ-21 | Stage 17 |
| TD-46 | Deployment mechanism | **TO EVALUATE** | Spec §25 | Stage 17 |
| TD-47 | Hosting baseline: PHP 8.3 target, DB ≥ MySQL 8 / MariaDB 10.6, WebP-capable image lib, SSH/WP-CLI, EU region; host itself TO EVALUATE | PROVISIONAL / TO EVALUATE | Analysis | Stage 18 |
| TD-48 | Third-party embeds (maps, video, fonts, widgets): none by default | **TO EVALUATE** (default: none) | Analysis; OQ-16 | Stage 7 |
| TD-49 | Analytics and consent tooling | **TO EVALUATE** | OQ-22 | Stage 15 |
| TD-50 | Visible "demo site" disclosure; payments in test mode on the public portfolio site | PROVISIONAL | Analysis; OQ-06 | Stage 13 |

---

## 2. Decision records (detail)

Format: **Context → Decision → Alternatives → Consequences → Revisit when.**

### TD-02 · Classic theme, not a block theme
- **Context:** the Spec mandates `get_header()`, `get_footer()`, `wp_head()`, `wp_footer()`, `wp_nav_menu()` and warns against theme-compat fallback (lesson 2).
- **Decision:** classic PHP-template theme with `theme.json` for tokens and editor settings.
- **Alternatives:** block/FSE theme (rejected: contradicts the Spec's explicit function list; template logic for server-side filters and relationship queries is more natural in PHP).
- **Consequences:** header/footer discipline is a QA item; the block editor is still used for *content*.
- **Revisit:** not planned.

### TD-11 · Theme vs plugin split — **DECIDED**
- **Context:** data structures (CPTs, taxonomies, fields, settings) placed in a theme vanish when the theme changes, and mix presentation with business rules.
- **Decision:** everything that defines *what the business data is* goes in `vistula-core`; the theme only presents it.
- **Alternatives:** everything in the theme (fewer files, but data becomes theme-bound); must-use plugin (more rigid, harder for a client-hand-off to manage).
- **Consequences:** two code trees in Git; theme cannot function without the plugin — **implemented**: it fails gracefully with an admin notice (`vistula-travel/inc/plugin-dependency.php`) rather than a fatal error, per the consequence stated above.
- **Confirmed:** OQ-04 approved.

### TD-12 · Field framework — **DECIDED**: Secure Custom Fields (SCF), via Local JSON
**Requirements the framework must meet**

| # | Requirement | Met by SCF? |
|---|---|---|
| F1 | Field *definitions* exportable to files in Git (not DB-only) | Yes — Local JSON sync, wired to `vistula-core/config/acf-json/` (`src/Fields/json-sync.php`) |
| F2 | Repeatable groups (itinerary, things-to-do, hours, social links) | Yes — Repeater is included free in SCF (it was a paid ACF Pro feature; SCF ships it in the free/only tier) |
| F3 | Relationship fields with **ordering** | Yes — Relationship field type preserves manual order |
| F4 | Gallery / image fields; conditional logic; validation hooks | Yes — all present in SCF's field-type set |
| F5 | Settings pages (or a clean way to build our own) | Yes — Options Pages included free |
| F6 | REST exposure control (`show_in_rest` per field) | Yes |
| F7 | Documented compatibility with the multilingual plugins we may choose | Yes for the ACF lineage generally (both Polylang and WPML document ACF/SCF field sync); **not yet verified for SCF specifically** — folded into the still-open TD-20 spike |
| F8 | Active maintenance, sane licence and cost, and an exit path | Strong: maintained by the WordPress.org security/plugin team itself (created after the Oct 2024 ACF/WP Engine dispute), GPL, free, no licence cost — the lowest dependency-risk option of the candidates considered |
| F9 | Editor UX a non-technical client can use | Yes — same editor UI ACF is known for |

**Candidates considered (rejected)**

| Candidate | Why not chosen |
|---|---|
| **ACF Pro** | Functionally near-identical to SCF for our needs, but paid (recurring licence cost, F8) where SCF is free and officially maintained. |
| **Meta Box** | Viable, but several needed features (F2/F3/F5 equivalents) sit behind paid extensions; SCF includes them free. |
| **Carbon Fields / CMB2** | Git-friendly by nature (code-defined), but smaller ecosystem and less-documented multilingual-plugin integration (F7) than the ACF lineage; noted as the fallback if SCF's multilingual compatibility fails Stage 11 verification. |
| **Native** (`register_post_meta` + custom UI) | Rejected for now: meets every requirement in principle but at the highest development cost (F9 requires hand-built React/UI), disproportionate for this project's size. |

- **Rationale for the decision itself:** SCF meets every requirement (F1–F9) at zero licence cost and the lowest maintenance-abandonment risk of any candidate, since it is maintained by the same organization that ships WordPress core. The data-access layer (TD-13) means this choice is not architecturally load-bearing even if reversed later.
- **What remains unverified:** the literal runtime spike the roadmap calls for (installing SCF, building a *Tour* field group with an itinerary repeater and an ordered relationship, confirming it survives a multilingual-plugin candidate) **cannot be run in this environment — no PHP/WordPress runtime is available here.** Only the integration plumbing (Local JSON save/load paths) has been prepared and is a no-op until SCF is actually installed. **This verification is a precondition for Stage 5**, not for closing this decision.
- **Revisit when:** the Stage 5/Stage 11 runtime verification above actually runs, in case it surfaces a real incompatibility.

### TD-13 · Data-access layer — **DECIDED**; implemented
- **Context:** three undecided integrations (field framework, multilingual plugin, booking provider) all touch the data model.
- **Decision:** templates call `vistula_tour()`, `vistula_destination()`, `vistula_setting()`, `vistula_page_url()` etc. Nothing else. These return typed arrays with defaults applied and expose a no-op `vistula_resolve_translation` hook.
- **Alternatives:** call `get_field()`/`get_post_meta()` in templates (faster to write; couples every template to one framework and to the multilingual plugin).
- **Consequences:** a small amount of upfront code; every "change of mind" becomes local.
- **Implemented this stage:** all five contract functions exist as stubs in `vistula-core/src/Api/` (`settings.php`, `page-roles.php`, `tours.php`, `destinations.php`, `translation.php`), each returning its caller-supplied default/`null` and each tagged with the stage that gives it a real implementation. No template anywhere calls `get_option()`, `get_post_meta()`, or queries `post_type=tour`/`post_type=destination` directly — verified by a repo-wide grep this stage.
- **Revisit when:** never — it is the cheapest form of risk reduction in this design.

### TD-14 · Relationship storage
- **Decision:** each relationship is stored on one side; the reverse is derived through named query functions (`Queries::tours_for_destination()` …). Order-sensitive curated lists (FAQs, featured tours) are stored on the consumer.
- **Open point:** reverse lookups on a relationship field are a `meta_query` on a serialised ID list. That is adequate at "hundreds of tours". Options to move to if needed: (a) hidden **shadow taxonomy** kept in sync with destinations (clean taxonomy queries; sync + translation complexity); (b) a small lookup table.
- **Revisit when:** listing queries exceed the performance budget, or the multilingual plugin's relationship mapping proves fragile.

### TD-09 · Taxonomy split (⚠ refines the Spec)
- **Context:** the Spec's category list mixes *theme* (Nature, History) with *format* (Private Tours, Weekend Trips); its filter list has category **and** tour type as separate filters.
- **Decision:** `tour_category` (theme) and `tour_type` (format).
- **Alternatives:** single flat `tour_category` exactly as in the Spec (simpler for editors; conflates axes and makes the "tour type" filter redundant).
- **Consequences:** navigation mapping needs reconciling (F-01); the model degrades gracefully to one taxonomy if the owner prefers.
- **Revisit when:** OQ-07/OQ-08 are answered.

### TD-15 · Single currency (PLN) (⚠ refines the Spec)
- **Context:** the Spec lists *currency* as a per-tour field. Mixed currencies break sorting/filtering. BLIK — the leading Polish online payment method — is offered in PLN only.
- **Decision:** one site-wide currency constant; no per-tour currency.
- **Alternatives:** per-tour currency (unneeded complexity); multi-currency display (out of scope, OQ-23).
- **Revisit when:** a non-PLN market is added.

### TD-18 · Listing pages as real Pages
- **Decision:** `Tours`, `Destinations`, `Travel Guide` are Pages with templates; CPTs use `has_archive=false` with a rewrite base equal to the Page slug.
- **Why:** editable intro/SEO in the standard editor; translatable objects; predictable multilingual behaviour.
- **Risk:** slug/rewrite interplay (`/tours/` Page vs `/tours/{slug}/` CPT) must be proven — the **rewrite test needs an actual registered CPT to flush rewrite rules against, and none exists until Stage 5.** Moved from "Stage 2 rewrite test" to "Stage 5", since attempting it now would test nothing real. Fallback unchanged: enable the CPT archive and store intro/SEO in settings.

### TD-19…22 · Multilingual (no plugin now)
- **Decision:** honour the contract in architecture §8.3 from the first template. Language registry in code. Subdirectory URLs. Fallback policy per architecture §8.5.
- **Plugin candidates (not installed):**

| Approach | Examples | Fit with this data model |
|---|---|---|
| **Separate object per language** | Polylang, WPML | Natural fit for structured content (relationships, per-language slugs, per-language SEO). Needs "synchronise N-fields" behaviour. |
| **Render-time string translation** | TranslatePress, Weglot | Simpler for pages; weaker for query-derived content, translated slugs and relationship-driven listings; Weglot is a third-party SaaS (data-protection review needed). |

- **Mandatory spike (Stage 2, throw-away sandbox, not the project repo):** verify that a relationship, an ordered FAQ list, the itinerary repeater, a settings field, a CPT slug, a nav menu and attachment alt text behave under each shortlisted plugin. **Still not run — this genuinely requires a running WordPress instance, which this environment does not have.** The outcome is recorded here before Stage 11.
- **Stage 2 code-level audit performed instead (what *is* checkable without a runtime):** every current theme/plugin file was grepped for M-1 (gettext-wrapped UI strings), M-3 (no hard-coded URLs/IDs — `home_url()`/`vistula_page_url()` used throughout, no literal `/slug/` anywhere) and M-4 (the `vistula_resolve_translation` no-op hook, now implemented in `src/Api/translation.php`). **All three passed** — no violation found. This is a necessary but not sufficient check: it confirms the code doesn't *prevent* multilingual compatibility, not that a specific plugin *works* with it. The mandatory spike above is still required before Stage 11.
- **Revisit when:** the mandatory spike actually runs, and OQ-01/OQ-02.

### TD-23/24 · Images
- **Decision (PROVISIONAL):** hard-cropped derivative sets per role (4:3 card, 16:9 hero, 1:1 square, 1.91:1 OG), several widths per ratio for a real `srcset`, WebP baseline, per-image focal point, `object-fit` only where the container ratio is genuinely variable.
- **TO EVALUATE:** AVIF (host libraries, encode cost, modest gains) and crop-by-region tooling.
- **Revisit when:** real photos are measured (Stage 5) and the host is known (Stage 18).

### TD-26/27 · Search and filtering
- **Decision:** server-rendered GET forms → `pre_get_posts`/`WP_Query`; progressive JS later; filtered URLs `noindex, follow` + canonical to the clean page; indexable landing pages are category/type archives and Destination pages.
- **Diacritics (TD-27):** the live test — whether `Wroclaw` finds `Wrocław` in the chosen database collation — **requires a real database and real destination content, neither of which exists yet.** Moved to Stage 5. **Stage 2 code-level check performed instead:** reviewed all current code for anything that would mis-handle `ą ć ę ł ń ó ś ź ż` before the live test is even possible — none found (no current code does string/slug manipulation at all; the only place that will matter is Stage 5's search query and Stage 8's destination slugs, both of which will rely on WordPress core's `remove_accents()`/`sanitize_title()` — a well-documented, already-correct baseline — plus the deferred folded-index fallback if the live collation test fails).
- **Rejected for now:** external search engines, facet counts.

### TD-28 · SEO ownership
- **Decision:** exactly one component owns meta/OG/`hreflang`/JSON-LD. **Choice between a plugin and a small in-house module is TO EVALUATE** (criteria in architecture §13.1). Meta generation is behind filterable functions so either is possible.

### TD-30/31 · Booking
- **Decision (PROVISIONAL):** do not build a custom engine; evaluate a WordPress plugin versus an external tour-booking service against the criteria in architecture §10.3; availability lives in the booking system.
- **Why not decided now:** depends on OQ-05/06/10 and on RU/UK UI support, PLN/BLIK support, data location and whether a fictional business can be onboarded in test mode.

### TD-32/33/50 · Payment
- **DECIDED:** external provider; hosted checkout or hosted fields; webhook-verified status; secrets in `wp-config`/environment; **no live credentials on local/staging**.
- **TO EVALUATE:** the provider. Candidate families: global PSPs (e.g. Stripe, which documents BLIK and Przelewy24) and Polish PSPs (e.g. Przelewy24, PayU, Tpay).
- **PROVISIONAL:** the public portfolio site runs in **test mode** with a demo disclosure, unless OQ-06 says otherwise.

### TD-34/35/36 · Contact form
- **Decision:** lean towards a small custom handler in `vistula-core` using core APIs (nonce, `admin-post`, sanitise/escape, PRG, rate limit). A plugin is the fallback.
- **Cache vs nonce (R-06):** solution chosen and tested in Stage 9.
- **Mail:** production uses an authenticated transport with SPF/DKIM/DMARC; local uses a mail catcher; delivery is a separate QA item.

### TD-37 · No build step initially
- **Decision:** native CSS (custom properties, `@layer`, low specificity) and vanilla ES-module JS. A bundler is introduced only when a concrete problem justifies it.
- **Why:** fewer moving parts for a portfolio-scale theme; easier debugging (Spec principle 10).

### TD-41 · Comments disabled; author/tag archives noindex — **DECIDED**; implemented
- **Decision:** the site has no commenting, no multi-author byline strategy, and no tag taxonomy in the approved content model, so all three are disabled/noindexed as a baseline content-policy rule.
- **Implemented this stage** in `vistula-core/src/Content/comments-and-archives.php`: comment/trackback support removed from every public post type; `comments_open`/`pings_open` forced closed at the query level (covers content that arrives with comments already open); Comments admin menu, dashboard widget entry point, and admin-bar bubble removed; author and tag archives noindexed via the core `wp_robots` filter (the single, filterable seam SEO output is required to go through, per TD-28).
- **Why in the plugin, not the theme:** this is a business rule ("this site doesn't do comments"), not a presentation choice — it should survive a theme change, per TD-11.
- **Revisit when:** never, unless the business requirements change (e.g. a future decision to add a blog-comment strategy).

### TD-42/43 · Git and seed content
- **DECIDED:** repository `vistula-travel`, owner `Artem23334`, workflow change → test → `git diff` → commit → push.
- **DECIDED (CI portion, this stage):** `composer.json` (PHPCS + WPCS ≥3.4.1 + PHPCompatibilityWP as dev dependencies, no runtime dependency), `phpcs.xml.dist` (WordPress ruleset, PHP 8.2+ compatibility, two-text-domain aware), `.github/workflows/ci.yml` (PHP lint + PHPCS on PHP 8.2/8.3 matrix, plus a basic secret-pattern scan job). **Could not be executed in this environment** — no Composer/network access — so these are unverified against a real `composer install`; see the Stage 2 report for the static checks that *were* possible (JSON/XML/YAML validity).
- **PROVISIONAL:** remaining repo layout (seed/, tools/ — not created yet, nothing needs them until Stage 5), PHPUnit (Stage 16 — nothing meaningful to unit-test yet).
- **Seed content** is code: WP-CLI scripts create tours/destinations and resolve relations **by slug**, so IDs may differ between environments without breaking anything (R-15).

### TD-45/46/47 · Environments and deployment
- **DECIDED:** Local and Production are separate test environments.
- **TO EVALUATE:** a Staging environment (recommended); deployment via GitHub Actions → SSH/rsync (recommended direction) versus Git-on-server/host tooling/manual SFTP.
- **PROVISIONAL hosting baseline:** PHP 8.3, MySQL 8 or MariaDB 10.6+, image library with WebP (AVIF optional), SSH + WP-CLI, real cron, backups with restore, EU region.

---

## 3. Open questions

**How to use this list.** Each question has a **default** that lets work proceed; if you don't answer, the default is assumed and recorded. "Needed by" is the latest stage that can start without an answer.

| ID | Question | Why it matters | Default if unanswered | Needed by |
|---|---|---|---|---|
| **OQ-01** | What is the **default language**, and are all languages URL-prefixed (`/en/…`) or is the default unprefixed? | Every URL, `x-default`, content authoring order | English default; **all** languages prefixed | Stage 2 spike / Stage 11 |
| **OQ-02** | Ukrainian: technical code `uk` (recommended) vs `ua`? URL prefix `/uk/`? UI label "Українська"? | `hreflang`, locale, URLs, switcher | `uk` internally, `/uk/`, native-name label | Stage 11 |
| **OQ-03** | Which **field framework**, and is there budget for a paid licence (e.g. ACF Pro)? | Editing UX, cost, licence | Run the Stage 2 spike; prefer a free/GPL option if it passes | Stage 2 |
| **OQ-04** | Approve the **theme / `vistula-core` plugin split**? | Where the data model lives | Approved | Stage 2 |
| **OQ-05** | **Booking solution class:** WordPress plugin, external service, or (unlikely) custom? Any budget ceiling or preferred vendor? | Largest scope item | Evaluate first two against the criteria | Stage 12 |
| **OQ-06** | Will the public site take **real payments**, or only **test-mode** payments? Is there a real merchant identity? | Legal/financial exposure of a fictional business | Test mode + visible demo disclosure | Stage 13 |
| **OQ-07** | **Private Tours:** a separate product, or an *option* on a group tour (the Spec has an optional private-tour price)? | Taxonomy, price model, booking flow | Option on a tour (`tour_price_private`); `Private Tour` type only for private-only products | Stage 5 |
| **OQ-08** | **Reconcile taxonomy and navigation:** adopt `tour_category` + `tour_type`? Where do *Culture & Food* and *Private Tours* appear in the nav? What is *Poland Travel*? Drop the *Destinations* blog category? Overlap of *Things To Do* (nav, blog category, destination field)? | Content structure, nav, duplicate content | Adopt the split; nav is an editable menu; drop the *Destinations* category; treat *Poland Travel* as *Culture* unless told otherwise | Stage 5 (tours), Stage 10 (guide) |
| **OQ-09** | **Price model:** values for *price type*; child-price age range; what the private price covers (per group up to N?); prices shown VAT-inclusive? any discount/"was" price? | Data model, display, later booking/payment | `per_person` / `per_group`; free-text child age note; private price per group up to N; VAT-inclusive PLN; **no** discount pricing | Stage 5 |
| **OQ-10** | Who owns **availability and capacity** — WordPress or the booking system? | Prevents two sources of truth | Booking system | Stage 12 |
| **OQ-11** | Add a **guide-languages** field per tour (languages the tour is conducted in)? Which languages? | Core to a multilingual audience | Add it, options `pl en ru uk` | Stage 5 |
| **OQ-12** | **FAQ:** confirm the reusable-CPT model; where does FAQ appear in the navigation (header, footer, both)? | Nav, content reuse | CPT; FAQ page in footer and linked from Contact/tour pages | Stage 9 |
| **OQ-13** | **Testimonials for a fictional company:** label as demo? use photos? show star ratings? | Trust, legal, search-engine guidelines | Label as demo; no photos; ratings visible, but **no** review markup | Stage 6 |
| **OQ-14** | What does **Book Now** do before booking exists, and does a standalone `/booking/` page exist? | Header CTA, nav, page roles | CTA targets the Tours page until booking is live; `/booking/` only if the solution needs it | Stage 3 |
| **OQ-15** | **Contact form data:** email-only, or store enquiries (with retention)? Consent checkbox or privacy notice link? CAPTCHA provider? Mail provider? | GDPR, spam, lead loss | Email-only, privacy-notice link, honeypot + time-trap + rate limit; provider decided at Stage 9 | Stage 9 |
| **OQ-16** | **Maps:** embedded (which provider) or a static address + external map link? | Privacy (third-party requests), performance | Text address + external link, no embed | Stage 7 |
| **OQ-17** | **Local environment** (LocalWP, `wp-env`, DDEV, Docker, other), target **PHP / WordPress / DB versions**, **hosting**, **domain name**? | Reproducible setup; deployment | PHP 8.3, current WordPress 7.x, MySQL 8 / MariaDB 10.6+; tooling TBD by you | Stage 2 (env), Stage 18 (host/domain) |
| **OQ-18** | **Accessibility target:** WCAG 2.2 AA? | Testable acceptance criterion | AA | Stage 4 |
| **OQ-19** | **Legal pages and compliance scope:** privacy policy, cookie policy, terms, cancellation/refund policy; jurisdiction (Poland/EU); whether package-travel rules, consumer-withdrawal rules and invoicing apply to a fictional company | Footer "legal information" (Spec §12); booking rules | Clearly-marked **demo** legal pages; seek professional advice before any real operation | Stage 9 / 16 |
| **OQ-20** | **Content sourcing:** photo licences/photographers; who writes and **translates** PL/RU/UK copy (human, machine + review)? How many tours/destinations get full translations for the demo? | Time, legal, quality; mixed-language UX | Own or freely-licensed photos with `docs/credits.md`; EN authored first; machine translation reviewed by a human speaker | Stage 5 / 11 |
| **OQ-21** | Use a **staging** environment? | Safer launch | Yes | Stage 17 |
| **OQ-22** | **Analytics and cookie consent:** none, or a privacy-friendly analytics tool with consent? | GDPR/ePrivacy, performance | None until decided; nothing non-essential loads by default | Stage 15 |
| **OQ-23** | **Out of scope** unless you say otherwise: customer accounts, comments, newsletter, promo codes, gift vouchers, multi-currency display, review submission, staff/guide profiles, live chat | Prevents scope creep | All out of scope | Stage 2 |
| **OQ-24** | **Repository visibility** (public portfolio vs private) and whether demo photos/content are committed | Licensing, secrets hygiene | Private until a secret scan passes; public when portfolio-ready; photos referenced, not committed, unless licences allow | Stage 2 / 17 |
| **OQ-25** | What does **"disable a tour"** mean: hide (draft), *enquire only* (booking off), or seasonal note? | Editor workflow | All three supported; documented in the Client Guide (architecture §10.6) | Stage 5 |
| **OQ-26** | **Brand identity:** logo, colours, typography, tone of voice? Nav says "Tours"; the Spec title says "Tours / Experiences" — which word? | Design System cannot start without it | Placeholder logo + neutral palette defined in Stage 4; "Tours" | Stage 4 |

---

## 4. Risk register

L = likelihood, I = impact (H/M/L).

| ID | Risk | L | I | Mitigation | Where handled |
|---|---|---|---|---|---|
| **R-01** | **Field-framework licensing/lock-in** (ACF Pro cost, the SCF fork, plugin abandonment) | M | M | Field definitions in Git; data-access layer; spike before committing | TD-12/13 · Stage 2 |
| **R-02** | **Multilingual plugin incompatibility** with relationships, itinerary repeater, options, CPT slugs, media alt | M | H | Plugin-agnostic contract; wrapper hooks; throw-away compatibility spike before Stage 11 | TD-19/20 · Stage 2 |
| **R-03** | **Booking + payment scope/complexity** (largest, least defined) | H | H | Prefer existing solutions; define criteria now; separate stages; decide only after OQ-05/06/10 | TD-30…33 · Stages 12–13 |
| **R-04** | **Fictional business vs. real money and legal duties** (payments, invoicing, package-travel rules, consumer law) | M | H | Test-mode payments and demo disclosure by default; professional advice before real operation | OQ-06/19 · TD-50 |
| **R-05** | **Image pipeline:** host lacks WebP/AVIF; crops ruin composition; regeneration cost | M | M | Verify host capability in Stage 2; focal point; measured targets; disable unused sizes | TD-23/24 · Stages 2, 5 |
| **R-06** | **Page cache vs form nonces** — cached pages carry stale nonces, valid submissions fail | M | M | Exclude/handle contact page; alternative anti-spam; test behind the production cache | Stage 9, 17 |
| **R-07** | **Local vs production email:** local mail silently fails and is mistaken for a bug (Spec lessons 5–6) | H | L | Mail catcher locally; separate production delivery test | Stage 9, 17, 19 |
| **R-08** | **Theme-compat regression** (missing/incorrect `header.php`/`footer.php`) | L | M | QA check: no theme-compat deprecation in debug log; smoke test per template | Stage 3 onward |
| **R-09** | **RU/UK text expansion and Cyrillic glyphs** break nav, cards, buttons, filters; fallback fonts cause layout shift | H | M | +30–40 % length tolerance; test with real RU/UK strings from Stage 4; subsetted fonts covering Latin-Ext + Cyrillic | Stage 4, 11 |
| **R-10** | **Relationship queries** slow or fragile at scale | L | M | Confine to named query functions; shadow taxonomy/lookup table held in reserve | TD-14 |
| **R-11** | **SEO plugin, multilingual plugin and theme all emitting** `hreflang`/canonical/schema (duplicates or conflicts) | M | M | Single owner per output; filterable functions; validate in Stage 15 | TD-28 |
| **R-12** | **Fabricated reviews** presented as real or marked up as `Review`/`AggregateRating` (misleading; search-engine policy) | M | M | Demo labelling; no review markup | OQ-13 · TD-29 |
| **R-13** | **GDPR/ePrivacy** exposure via third-party requests (fonts, maps, analytics, CAPTCHA, video) | M | M | No third-party requests by default; self-host fonts; consent tooling if any are adopted | TD-38/48/49 |
| **R-14** | **Translation volume and quality:** 4 languages × content; partial translations produce mixed-language UX | H | M | Fallback policy; scope translations deliberately (OQ-20); human review | Stage 11 |
| **R-15** | **Migration breaks relationships:** ID-based fields and serialised data break when moved by XML export/import or naive find-and-replace | M | H | DB-copy migration with `wp search-replace`; seed by slug; integrity audit after every migration | TD-43 · Stage 17 |
| **R-16** | **Version churn:** WordPress 7.x, PHP support windows (8.1 end-of-life), plugin abandonment | M | M | Pin and document versions; target PHP 8.3; update policy; admission checklist | TD-47 |
| **R-17** | **Scope creep / giant all-at-once implementation** (Spec principle 6) | M | H | Stage gates; each stage has exit criteria; stop-and-review after every phase | Roadmap |
| **R-18** | **Search misses Polish diacritics** (`ł`) | M | L | Empirical test; folded index field | TD-27 |
| **R-19** | **Booking-provider lock-in and data location** (EU processing, export, deletion) | M | M | Evaluation criteria include export, DPA and EU location; adapter boundary | TD-30 |
| **R-20** | **Inaccessible third-party booking/payment widgets** | M | M | Accessibility as a selection criterion; keyboard/screen-reader test of the flow | Stages 12–13, 15 |

---

## 5. Assumptions

| # | Assumption |
|---|---|
| A-01 | Single developer building a **portfolio** project; the "client" is hypothetical, but the editing experience must be realistic. |
| A-02 | Scale: up to a few hundred tours and destinations; modest traffic. |
| A-03 | Prices and reviews are demo content (Spec §4). |
| A-04 | The developer has local WordPress tooling (to be confirmed, OQ-17). |
| A-05 | Original authoring language is English unless OQ-01 says otherwise. |
| A-06 | Third-party plugin capabilities cited here are **as of 2026-09-21** and must be re-checked in the spike. |

---

## 6. Sources checked on 2026-09-21

Facts that change over time were verified where possible; other statements are from general knowledge and are flagged for verification in Stage 2.

| Topic | Source |
|---|---|
| WordPress PHP minimum/recommended (7.4 minimum, 8.3 recommended for WordPress 7.0) | `make.wordpress.org/core/2026/01/09/dropping-support-for-php-7-2-and-7-3/` |
| Reported database floor for WordPress 7.0 (MySQL 8.0 / MariaDB 10.6) — third-party report, **verify** | `dev.to/monstermegs/the-proven-wordpress-hosting-php-requirements-to-secure-now-10g7` |
| WordPress Coding Standards 3.4.1 security release (27 July 2026) | `github.com/WordPress/WordPress-Coding-Standards/releases/tag/3.4.1` |
| Secure Custom Fields (WordPress.org listing, 2026 releases) | `wordpress.org/plugins/secure-custom-fields/` |
| Custom-fields plugin landscape, 2026 overview (third-party) | `fs-code.com/blog/best-wordpress-custom-fields-plugins` |
| ACF free vs Pro (Repeater in Pro) | ACF plugin readme (Pro feature list) |
| European Accessibility Act in Poland: in force 28 June 2025; microenterprise service exemption (< 10 staff, ≤ €2 M) | `dudkowiak.com/?p=13861`; `cm.twobirds.com/en/insights/2025/poland/250625-europejski-akt-o-dostepnosci-w-polsce` |
| BLIK availability and PLN-only presentment currency | `stripe.com/en-lt/payment-method/blik` |
| Przelewy24 with Stripe; PLN/EUR support | `stripe.com/en-cn/resources/more/przelewy24-an-in-depth-guide` |

**To verify empirically in Stage 2 (stated from general knowledge, not checked):** WordPress AVIF/WebP generation on the chosen host · Block Bindings supported attributes in the pinned version · `/tours/` Page vs `/tours/{slug}/` CPT rewrite behaviour · MySQL collation behaviour for `ł` · `srcset` candidate selection for multiple same-ratio sizes · current Google FAQ rich-result policy · SCF's exact feature set vs ACF Pro.

*This is analysis, not legal advice; items touching consumer law, invoicing, accessibility obligations and GDPR should be confirmed with a qualified professional before any real commercial operation.*

---

## 7. Change log
| Date | Change |
|---|---|
| 2026-09-21 | Initial version (Phase 0). |
| 2026-09-24 | Stage 2 (Theme Foundation) implementation. TD-11 confirmed DECIDED (theme now fails gracefully via admin notice if `vistula-core` is inactive). TD-12 decided: **Secure Custom Fields (SCF)** via Local JSON — see the rewritten decision record; runtime spike still pending, no fields created. TD-13 implemented (`vistula_destination()`, `vistula_resolve_translation()` added alongside the existing three functions). TD-18's rewrite test moved Stage 2 → Stage 5 (no CPT exists yet to test against). TD-19–22: Stage 2 code-level M-1/M-3/M-4 audit passed; the mandatory multilingual-plugin spike remains unrun (no WordPress runtime available). TD-26/27 diacritic live-collation test moved Stage 2 → Stage 5 (no database/content exists yet); a code-level review found nothing that would mishandle Polish diacritics in current code. TD-41 decided and implemented (comments disabled, author/tag archives noindexed). TD-42/44 CI portion decided and added (`composer.json`, `phpcs.xml.dist`, `.github/workflows/ci.yml`) — not executable in this environment (no Composer/network access), so unverified against a real `composer install`. |

*End of `technical-decisions.md`.*
