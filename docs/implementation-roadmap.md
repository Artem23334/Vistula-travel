# Vistula Travel — Implementation Roadmap

> **Status:** Phase 0 (Architecture). This is a *plan*, not a build log. Nothing described here has been implemented.
> **Source of truth:** the Spec (`Vistula_Travel_Project_Specification.docx`) §27 "Project Phases", reconciled with Prompt 01's request for a phased roadmap.
> **Companions:** [`project-architecture.md`](project-architecture.md) · [`data-model.md`](data-model.md) · [`technical-decisions.md`](technical-decisions.md)
> **Written:** 2026-09-21

---

## 0. How to read this document

Every stage lists: its **goal**, its **key activities**, the **decisions it finalises** (`TD-nn` / `OQ-nn` from [`technical-decisions.md`](technical-decisions.md)), and its **exit criteria**. A stage is closed only when its own exit criteria pass **and** the smoke suite of every earlier stage still passes (Spec §30.8, principle 8: "test after each major phase"). Nothing in a later stage should force rework of an earlier one if the architecture in the other three documents is followed — that reversibility is the point of the data-access layer (TD-13) and the theme/plugin split (TD-11).

## 1. Reconciling this roadmap with the Spec's own phase list (resolves F-07)

The Spec (§27) defines **19 phases, numbered 0–18**. Analysis surfaced two gaps that phase list doesn't account for and that this roadmap fills as **two additional stages**, both inserted early, before any content type is built:

| Gap | Why it needs its own stage | Inserted as |
|---|---|---|
| **Theme Foundation.** The Spec requires `header.php`/`footer.php` to be correct *before* anything else, to avoid the theme-compat regression from the previous project (Spec §29 lesson 2), and requires the theme/plugin split, data-access layer and field framework to be settled before any CPT is registered (TD-11, TD-12, TD-13). None of this is "Design System" (visual tokens) or "WordPress Foundation" (core install) — it's the skeleton both of those sit on. | **Stage 2** |
| **Global Settings.** Company name, phone, email, address, hours, social links and the default CTA (Spec §11) are consumed by the header, footer, contact page and JSON-LD from the very first template that renders. Building them *after* Tours CMS would mean hard-coding placeholders and then retrofitting — exactly the "hard-coding" principle 5 forbids. | **Stage 3** |

Resulting map (this roadmap has **21 stages, numbered 0–20**):

| This roadmap | Spec §27 | Notes |
|---|---|---|
| Stage 0 — Architecture | Phase 0 | This deliverable. |
| Stage 1 — WordPress Foundation | Phase 1 | Core install, base config, no content types yet. |
| **Stage 2 — Theme Foundation** | *(not in Spec — inserted)* | Theme/plugin split, data-access layer, git repo, CI. |
| **Stage 3 — Global Settings** | *(folded into Spec Phase 2 — split out)* | Company info, CTA, page-roles registry. |
| Stage 4 — Design System | Phase 2 | Visual tokens, CSS base, fonts, a11y baseline, brand identity. |
| Stage 5 — Tours CMS | Phase 3 | The `tour` CPT and its taxonomy; demo Tours seeded as **drafts** — not publishable yet (no Destination exists). |
| Stage 6 — Homepage | Phase 4 | Uses real Tour/Testimonial data where published; Tours sections stay placeholder-like until Stage 8 — see §3.4. |
| Stage 7 — Tour Experience | Phase 5 | Single tour template built/previewed against Stage 5's draft Tours; booking panel shell. |
| Stage 8 — Destinations | Phase 6 | The `destination` CPT; completes Tour↔Destination links and makes the demo Tours publishable. |
| Stage 9 — About / FAQ / Contact | Phase 7 | FAQ CPT content + contact form. |
| Stage 10 — Travel Guide | Phase 8 | Core Posts, guide taxonomy. |
| Stage 11 — Multilingual | Phase 9 | Plugin decision and rollout. |
| Stage 12 — Booking | Phase 10 | Solution selection and integration. |
| Stage 13 — Payment | Phase 11 | Provider selection and integration. |
| Stage 14 — JavaScript / UX | Phase 12 | Progressive enhancement layer. |
| Stage 15 — SEO / Accessibility / Performance | Phase 13 | Hardening pass, not first introduction. |
| Stage 16 — Testing | Phase 14 | Full regression across every earlier stage. |
| Stage 17 — Git / Production Preparation | Phase 15 | Deployment mechanism, staging. |
| Stage 18 — Hosting / Domain / DNS / SSL | Phase 16 | Go-live infrastructure. |
| Stage 19 — Final QA | Phase 17 | Launch checklist (architecture §21.4). |
| Stage 20 — Portfolio Documentation | Phase 18 | Case-study write-up. |

**Sequencing note (resolves F-08):** the Spec (§13) puts CSS after the data model and templates, but §27 puts *Global Design System* (Phase 2) before *Tours CMS* (Phase 3). This roadmap follows §27's ordering but keeps Stage 4 deliberately narrow — tokens, base styles, layout primitives, header/footer chrome — with **no tour- or destination-specific component styling**, which only happens with real data in Stages 5–9 (TD-37).

---

## 2. Stage 0 — Architecture *(this deliverable)*

**Goal:** produce the four documents in `docs/` — done.
**Exit criteria:** the four documents exist, are internally consistent, and every DECIDED/PROVISIONAL/TO EVALUATE item is traceable to a `TD`/`OQ`/`R`/`F` ID. **Gate to Stage 1:** owner review of open questions (§4 below) — not required to *answer* all of them, since every OQ has a default, but the owner should at least skim them once.

---

## 3. Stages

### Stage 1 — WordPress Foundation *(Spec Phase 1)*
**Goal:** a bare, correctly-configured WordPress install with nothing tour-specific yet.
**Key activities:** local environment stood up (tooling per OQ-17); WordPress core installed at the target version; `wp-config.php` environment-driven (no secrets in Git, TD-42); permalinks set to a clean structure; timezone `Europe/Warsaw`; default content (sample page/post, Hello Dolly) removed; mail catcher installed (R-07); baseline security settings (TD-40 partial — file-edit disabled, XML-RPC decision).
**Decisions finalised:** none new — this stage executes TD-01, TD-42 (repo skeleton created), OQ-17 (env only).
**Exit criteria:** WordPress loads with no PHP notices/warnings in debug log; `wp-config.php` reads from environment; git repo initialised with the layout from architecture §19.1; first commit pushed.

### Stage 2 — Theme Foundation *(inserted — see §1)*
**Goal:** the presentation/domain split and the abstractions everything else depends on exist, before a single CPT or template is written.
**Key activities:** `vistula-core` plugin skeleton (namespace, autoloading, activation hooks); `vistula-travel` theme skeleton with `header.php`/`footer.php`/`get_header()`/`get_footer()`/`wp_head()`/`wp_footer()` verified against theme-compat (Spec §29 lesson 2); **field-framework spike** — install candidate frameworks in a throwaway branch, register one test field group each way, confirm each supports repeaters, relationship fields and clean export-to-code (TD-12); data-access layer stubbed (`vistula_setting()`, `vistula_page_url()`, `vistula_tour()` returning empty/placeholder shapes) so later stages have a contract to code against (TD-13); page-roles registry mechanism built (empty) (TD-18); rewrite-flush test confirming a Page can own a listing URL while a CPT owns child URLs (TD-18); **multilingual plugin compatibility spike** — install one candidate, create a test CPT with a relationship field and a repeater, confirm it survives translation (R-02, informs TD-20 later); Polish-diacritic collation test against the target database (TD-27); CI pipeline (PHPCS ≥ WPCS 3.4.1, PHP lint) wired to the repo (TD-44 start); comments disabled site-wide, author archives `noindex` (TD-41).
**Decisions finalised:** **TD-11** (theme/plugin split) confirmed working end-to-end; **TD-12** (field framework) chosen from the spike; **OQ-04** approved; **OQ-23** (out-of-scope list) confirmed with the owner; **OQ-24** (repo visibility — starts private); **OQ-17** (local tooling) locked in.
**Exit criteria:** an empty theme renders with no theme-compat deprecation notices; a throwaway CPT with one relationship field and one repeater survives (a) the chosen field framework and (b) the chosen multilingual-plugin candidate; CI passes on an empty diff; diacritic search behaviour is documented (informs Stage 5's TD-27 default).

### Stage 3 — Global Settings *(inserted — see §1; Spec §11)*
**Goal:** the single source of truth for company information exists and is consumed everywhere it's needed later.
**Key activities:** the `vistula-core` settings page built per architecture §6 (Company · Contact · Hours · Social · CTA · Footer & legal · Integrations · SEO defaults tabs); `vistula_setting()` implemented for real against the Settings API option; page-roles registry populated with placeholder Pages (Home, Tours, Destinations, Travel Guide, About, FAQ, Contact — Booking/legal pages added once they exist); demo-site disclosure field and flag (TD-50) built (content decided later, at Stage 13).
**Decisions finalised:** **TD-17** (settings storage/access) implemented; **OQ-14** (Book Now CTA target) — defaults to the Tours page role until Stage 12.
**Exit criteria:** changing the phone number in Settings updates a test template tag; no template calls `get_option()` directly (enforced by the Stage 2 CI + a grep-based check); page-roles resolve to real (placeholder) Pages.

### Stage 4 — Design System *(Spec Phase 2)*
**Goal:** the visual language and accessibility/typography baseline, with **no tour- or destination-specific components yet** (§1 sequencing note).
**Key activities:** `theme.json` tokens (palette, type scale, spacing, layout widths) — **blocked on brand identity (OQ-26)**, so a neutral placeholder palette is used if no assets exist by this stage, and the contrast/token audit (architecture §14.1) is re-run once real brand colours arrive; base CSS (`@layer` order, low-specificity component classes, no build step per TD-37); self-hosted subsetted fonts covering Latin Extended-A + Cyrillic (TD-38), with **real Russian/Ukrainian sample strings tested for wrap/overflow at this stage** (R-09) even though translation itself is Stage 11; header/footer chrome styled (logo, primary nav, language-switcher placeholder, mobile hamburger with `aria-expanded`); skip link, landmarks, focus-visible tokens, target-size baseline (architecture §14.1); a dev-only `noindex` style-guide template.
**Decisions finalised:** **OQ-26** (brand identity) — placeholder if unresolved, revisited before Stage 19; **OQ-18** (WCAG target) confirmed as AA; **TD-39** implemented as a baseline (verification continues through Stage 15).
**Exit criteria:** style-guide page passes an axe-core scan; header/footer render with no theme-compat fallback; mobile menu is keyboard-operable; contrast tokens measured ≥ 4.5:1 (text) / 3:1 (UI); RU/UK sample strings in nav/buttons don't overflow or truncate.

### Stage 5 — Tours CMS *(Spec Phase 3)*
**Goal:** the `tour` CPT, its taxonomies and its full field set exist and are editable, with **zero PHP required to add a tour** (Spec §5).
**Key activities:** register `tour` CPT and `tour_category`/`tour_type` taxonomies per [`data-model.md`](data-model.md) §2 and §4; implement every field group (General · Pricing · Details · Content · Itinerary · Booking · Relationships) in the chosen field framework, with field definitions exported to Git (TD-12); publish-time validation (data-model §2.6); admin list columns and filters; the six demo tours seeded via WP-CLI scripts (TD-43) **as drafts / incomplete seed records** — the `destination` CPT does not exist until Stage 8, so `tour_destinations` is deliberately left unset and **no attempt is made to publish a tour at this stage**; the tour-filtering query builder (`Vistula\Queries::tours()`) built and tested against the draft data (admin/preview context, not the live front end); image sizes registered and validated against real uploaded photography, adjusting the provisional targets in architecture §7.2 if needed (TD-23).
**Decisions finalised:** **TD-04** (CPT confirmed working), **TD-06** (itinerary repeater confirmed), **TD-09** (taxonomy split — or reverts to the Spec's flat list per owner preference), **TD-10**, **TD-14** (relationship storage), **TD-15** (PLN), **TD-16** (duration model), **TD-23** (image derivative sizes frozen after real-photo validation), **TD-26** (filtering); **OQ-07** (private tours), **OQ-08** (taxonomy/nav — tours half), **OQ-09** (price model), **OQ-11** (guide languages), **OQ-25** (disabled-tour states).
**Exit criteria:** all six demo tours save as drafts and pass every publish-time validation rule from data-model §2.6 **except** the ≥1-destination requirement, which is expected — and required — to fail until Stage 8; the CMS acceptance script's non-destination items (add/edit/disable a tour, change price/image/gallery — architecture §20.2) pass against draft data; the tours-filtering query logic is verified correct with no `posts_per_page = -1` anywhere, even though it has nothing published to show yet; a re-run of the Stage 2 diacritic test against real destination names is scheduled as a Stage 8 follow-up (destinations don't exist yet).

### Stage 6 — Homepage *(Spec Phase 4)*
**Goal:** the homepage renders from real, queried data per architecture §13.1 — no hard-coded tour/destination names.
**Key activities:** front-page template and each section (Hero, Popular Tours *(query built correctly against `tour_is_popular`, but empty on the live front end until Stage 8 publishes the demo Tours)*, Why Choose Us, Destinations *(placeholder grid until Stage 8)*, How It Works, Featured Experience, Reviews, Travel Guide *(placeholder until Stage 10)*, CTA); `testimonial` CPT built (data-model §8) with demo-disclosure labelling on every testimonial card (R-12); homepage editable fields (headline, sub-headline, "Why choose us" items, "How it works" steps) wired to a settings/page-meta panel.
**Decisions finalised:** **TD-08** (Testimonial CPT); **OQ-13** (testimonial demo labelling, no photos, ratings visible but no `Review` markup).
**Exit criteria:** the Popular Tours query correctly targets `tour_is_popular` and renders the loop/empty-state correctly (verified against the Stage 5 drafts in preview, since nothing is published yet); Reviews pulls from `testimonial_featured`; no section contains a name, price or quote typed directly into a template.

### Stage 7 — Tour Experience *(Spec Phase 5)*
**Goal:** the single-tour template (hero, summary, price box, details, highlights/included/excluded/bring, itinerary, gallery, FAQ, related tours, testimonials, booking-panel shell).
**Key activities:** all template parts under `template-parts/tour/` built and verified using **WordPress's native draft preview** against the Stage 5 seed tours (they remain unpublished until Stage 8, so this stage does not require live published pages); related-tours query (same destination → same category, TD-14 note) implemented against placeholder/empty data until Stage 8 provides real destination links; meeting-point map link (text address + external link by default, TD-48/OQ-16 — an embedded map only if that decision changes); itinerary rendered grouped by day with correct plural forms even before translation exists (M-2 groundwork); booking panel is a static shell gated by `tour_booking_enabled`, showing "Enquire" (mailto/contact-form link) since no booking system exists yet (architecture §10.6).
**Decisions finalised:** **OQ-16** (maps — text + link by default).
**Exit criteria:** every field in the Tour data model renders somewhere on the page or is confirmed intentionally hidden; `TouristTrip` JSON-LD validates in a rich-results test; the booking-panel shell correctly toggles between "Book" (inert, pending Stage 12) and "Enquire".

### Stage 8 — Destinations *(Spec Phase 6)*
**Goal:** the `destination` CPT exists, the Tour↔Destination relationship is completed on both sides, and — only as a result of that — the six demo Tours seeded in Stage 5 become publishable.
**Key activities:** register `destination` CPT (data-model §5); seed the six demo destinations, resolving relationships by slug; **go back to the six Stage 5 draft tours and set `tour_destinations` on each one** — this is the step that completes the relationship, not something assumed done in Stage 5; destination single template (hero, intro, highlights, things-to-do, **tours here** query, **guides about {place}** query *(placeholder until Stage 10)*, tips, map link, FAQ); destination grid on the Destinations listing page and the homepage; re-run the Stage 2 diacritic search test against real destination names (Wrocław, Łódź-style edge cases) and finalise TD-27; **run the data-model §2.6 publish-time validation on all six tours now that a destination exists, and publish only the ones that pass** (a tour with any other missing required field stays a draft even at this stage — that's a content gap, not a sequencing one).
**Decisions finalised:** **TD-27** (diacritic folding — build the folded index field, or confirm it's unnecessary, based on the empirical test).
**Exit criteria:** every one of the six demo tours has ≥1 valid, published destination and passes publish-time validation, and is switched from draft to published as a result — not before; the now-published tours show their destination(s), and every destination correctly lists its tours via the derived query (F-06 resolved in practice, not just on paper); the Stage 6 Popular Tours section and Stage 7 single-tour template now render live instead of empty/preview-only, with no template changes required (confirms the draft-first sequencing didn't leak assumptions into earlier templates); destination search matches unaccented input if TD-27 requires it.

### Stage 9 — About / FAQ / Contact *(Spec Phase 7)*
**Goal:** the three remaining top-level pages, and the FAQ content model in production use.
**Key activities:** `faq` CPT populated (data-model §7) and rendered as accessible `<details>` accordions on the FAQ page, grouped by `faq_group`; curated FAQ selections attached to tours/destinations; About page as block content; Contact page built with the custom form handler (nonce, sanitisation/escaping, honeypot + time-trap + rate-limit, Post/Redirect/Get, Reply-To not From) per architecture §12; contact-page cache exclusion or nonce-refresh strategy decided and tested (R-06); mail catcher verified end-to-end locally (form → catcher inbox); legal-page placeholders (Privacy, Cookies, Terms, Cancellation) created as draft Pages with demo-content notices, content to be finalised before Stage 19 (OQ-19).
**Decisions finalised:** **TD-07** (FAQ placement confirmed — footer + FAQ page + curated per tour/destination); **TD-34** (custom handler chosen or form plugin, per the Stage 2 spike outcome if a form plugin was trialled); **TD-35** (spam-protection method); **OQ-12** (FAQ nav placement); **OQ-15** (contact-form data handling — email-only vs stored enquiry).
**Exit criteria:** the CMS acceptance script's "manage FAQ" and contact-form items pass; a submitted contact form arrives in the local mail catcher with correct headers (Reply-To = visitor, From = site domain); the form still succeeds behind a simulated page cache.

### Stage 10 — Travel Guide *(Spec Phase 8)*
**Goal:** the blog, using core Posts, fully wired to Tours and Destinations.
**Key activities:** guide categories reconciled per OQ-08 (Travel Tips · Things To Do · Polish Food · Culture, dropping a separate *Destinations* category per F-02); `post_tours`/`post_destinations` relationship fields added to the Post editor; guide cards on Home, Tour and Destination pages now populated (removing the Stage 6/8 placeholders); `Article` JSON-LD; comments confirmed disabled; a handful of demo articles written and seeded.
**Decisions finalised:** **OQ-08** (guide taxonomy — completes the reconciliation started in Stage 5).
**Exit criteria:** a guide article correctly appears on its related tour's and destination's pages; the Travel Guide category archive works; no orphaned "Poland Travel" nav item remains unresolved.

### Stage 11 — Multilingual *(Spec Phase 9)*
**Goal:** Polish, English, Russian and Ukrainian are live, honouring the M-1…M-15 contract built into every earlier stage.
**Key activities:** the multilingual plugin from the Stage 2 spike installed for real (or re-evaluated against the criteria in architecture §8.7 if the spike surfaced problems) — **TD-20**; default language and URL-prefix scheme fixed — **TD-21**, **OQ-01**; every Tour, Destination, Post, FAQ, Testimonial, menu and settings field translated or explicitly marked default-language-only; language switcher built (M-8); `hreflang`/`x-default`/`og:locale` wired through the SEO ownership model from Stage 15's home (built now, verified then); RU/UK content reviewed by a human speaker (OQ-20); fallback policy (TD-22) tested by deliberately leaving one tour untranslated.
**Decisions finalised:** **TD-19/20/21/22** all move from PROVISIONAL/TO EVALUATE to DECIDED; **OQ-01** (default language), **OQ-02** (`uk` code and `/uk/` prefix), **OQ-20** (translation approach and coverage).
**Exit criteria:** every published content type exists correctly in at least the default language and English; the language switcher never links to a 404 or a mismatched page; plural forms render correctly in at least one tested RU/UK string ("1 day/2 days/5 days" equivalents); the itinerary's day-grouping (the hardest repeater case, per data-model §9.4) survives translation.

### Stage 12 — Booking *(Spec Phase 10)*
**Goal:** the booking flow — **Tour → Book → Date/Time → People → Customer Information → Booking → Confirmation** — is live behind the three theme touchpoints defined in architecture §10.5, using a chosen external/plugin solution. This stage's flow ends at a created **booking**, in an unpaid/pending state; it deliberately does **not** include payment (that's Stage 13 — see the boundary note below).
**Key activities:** evaluate the solution classes (architecture §10.3) against the must-have criteria; integrate the adapter in `vistula-core`; wire `tour_booking_enabled`/`tour_booking_provider_ref`; replace the Stage 7 booking-panel shell and the Stage 3 CTA target with the real flow, ending on a booking-confirmation view that clearly states the booking is **pending/unpaid**; implement the conceptual status model (architecture §10.4) as far as the chosen provider supports it, including whatever "awaiting payment" status it exposes; notification emails in the customer's language (M-10), worded for a pending booking, not a paid one; accessibility pass on the booking widget itself (R-20).
**Boundary with Stage 13:** this stage's exit criteria are satisfied whether or not the chosen provider has payment wired up yet — a booking is a complete, testable unit on its own. Payment is added in Stage 13 without reopening this stage's template or flow work.
**Decisions finalised:** **TD-30** (solution class), **TD-31** (confirmed — no duplicate availability data in WordPress); **OQ-05** (which provider/solution), **OQ-10** (confirmed: booking system owns availability).
**Exit criteria:** a full **Tour → Book → Date/Time → People → Customer Information → Booking → Confirmation** flow completes for at least one seeded tour, in at least two languages, ending in a correctly-labelled **pending/unpaid** booking — no payment step is required to pass this stage; the booking widget passes a keyboard-only and screen-reader smoke test.

### Stage 13 — Payment *(Spec Phase 11)*
**Goal:** the flow — **Booking → Checkout → Payment Provider → Sandbox Payment → Verified Webhook → Paid Booking → Confirmation** — is live in the chosen provider's **test mode**, honouring the non-negotiables in architecture §11.1. This stage picks up exactly where Stage 12 leaves off: an existing pending/unpaid booking becomes paid.
**Key activities:** evaluate and integrate the payment provider per architecture §11.3; a checkout step added ahead of the Stage 12 confirmation view; webhook handler with signature verification and idempotency; test-mode indicator and demo-site disclosure switched on (TD-50); refund flow tested against the provider's sandbox; confirm no card numbers, CVV, or other payment credentials/secrets ever touch WordPress storage or Git (a scripted secret-scan gate, not just a manual check — this rule is unchanged from the original architecture).
**Decisions finalised:** **TD-33** (provider); **OQ-06** (confirmed: test mode + visible disclosure, unless the owner explicitly opts into real payments with the legal groundwork from OQ-19 first).
**Exit criteria:** a sandbox payment completes against a Stage-12-style pending booking, and the booking status moves from pending to **paid only** via the verified webhook, never the browser redirect alone; a forced webhook replay doesn't double-process; the secret-scan gate passes on the full history; no card number, CVV, or payment credential appears anywhere in WordPress's database or the Git history.

### Stage 14 — JavaScript / UX *(Spec Phase 12)*
**Goal:** a thin progressive-enhancement layer on top of everything that already works without it.
**Key activities:** the tour-filter AJAX enhancement (architecture §9.1, "Enhancement"); mobile-menu and FAQ-accordion polish (already functional via native `<details>`/`<button>` from Stage 4 — this stage only adds animation/UX polish, never core functionality); any booking/payment-specific JS the chosen providers require, conditionally enqueued per template only.
**Decisions finalised:** none new — this stage executes TD-37's "vanilla, progressive enhancement" principle against real UI.
**Exit criteria:** every enhanced interaction still works with JavaScript disabled; total non-booking-page JS stays inside the ~30 KB gzip budget (architecture §15.1).

### Stage 15 — SEO / Accessibility / Performance *(Spec Phase 13)*
**Goal:** a hardening and verification pass — not the first introduction of these concerns, which have been architectural from Stage 4 onward.
**Key activities:** SEO plugin vs in-house module decided and the single meta-output owner implemented (TD-28); structured data finalised across all types (TD-29), explicitly excluding `Review`/`AggregateRating` on testimonials; XML sitemap and `robots.txt` verified; full WCAG 2.2 AA audit (automated + manual keyboard/screen-reader passes, architecture §14.2); Lighthouse/Core Web Vitals measured against the budgets in architecture §15.1 and optimised; analytics/consent tooling decided (TD-49, OQ-22) and, if adopted, gated behind consent; AVIF decision finalised if not already closed in Stage 2 (TD-24).
**Decisions finalised:** **TD-24**, **TD-28**, **TD-29**, **TD-49**; **OQ-22** (analytics/consent).
**Exit criteria:** Lighthouse mobile ≥ 90 performance / 100 a11y & best-practices / ≥ 95 SEO on the key templates; axe-core reports zero critical issues; rich-results test validates every structured-data type in use.

### Stage 16 — Testing *(Spec Phase 14)*
**Goal:** the full test suite (architecture §20) runs across every stage's output together, as regression, not per-feature.
**Key activities:** the complete Playwright E2E suite (navigation, filters, search, contact form, tour/destination CRUD, language switch, booking/payment sandbox flow); visual regression across desktop/tablet/mobile per language; the CMS acceptance script run in full; the content-integrity WP-CLI audit run against all seeded content; security checklist (TD-40) executed in full, including 2FA enforcement and header checks; the break/fix teaching exercises (Spec §24, deferred from earlier stages) authored as a Portfolio-stage appendix; legal-page content finalised with the caveat from OQ-19 that professional review is recommended before any real commercial use.
**Decisions finalised:** **TD-40** (security baseline confirmed against a real install); **OQ-19** (legal-page content finalised as demo content, professional-review caveat retained).
**Exit criteria:** the full E2E and visual suites pass on Chrome/Firefox/Safari/Edge and a representative Android device; the debug log is clean of theme-compat and PHP notices across every template; the security checklist has no open items.

### Stage 17 — Git / Production Preparation *(Spec Phase 15)*
**Goal:** the deployment mechanism and (optionally) a staging environment are working before production is touched.
**Key activities:** deployment mechanism chosen and scripted (TD-46 — SFTP rejected per architecture §21.3, GitHub Actions → SSH/rsync recommended); staging environment stood up if adopted (TD-45, OQ-21), `noindex` + HTTP auth confirmed; `wp search-replace`-based migration procedure documented and dry-run tested; production mail transport (SPF/DKIM/DMARC) configured and test-sent from staging (TD-36 finish); repository visibility decision executed (OQ-24 finish) after a final secret scan.
**Decisions finalised:** **TD-45**, **TD-46**; **OQ-21** (staging — yes/no); **OQ-24** (repo goes public, if chosen).
**Exit criteria:** a full deploy of theme + plugin to staging succeeds via the scripted mechanism; a database migration round-trip (local → staging) preserves every relationship field; production mail delivery test succeeds from staging's real transport.

### Stage 18 — Hosting / Domain / DNS / SSL *(Spec Phase 16)*
**Goal:** production infrastructure exists and is verified before any content goes live on it.
**Key activities:** host selected against the requirements in architecture §21.2 (PHP 8.3, WebP-capable image lib, SSH/WP-CLI, EU region, real cron); domain purchased/configured; DNS records set; SSL/TLS certificate issued and auto-renewal confirmed; canonical host (apex vs `www`) decided and redirect configured; HSTS enabled only after HTTPS is confirmed stable.
**Decisions finalised:** **OQ-17** (hosting/domain — finalised).
**Exit criteria:** the production domain resolves over HTTPS with no mixed-content warnings; the canonical redirect works both directions.

### Stage 19 — Final QA *(Spec Phase 17)*
**Goal:** the launch checklist (architecture §21.4) passes on production before it's publicly announced.
**Key activities:** the full Stage 16 test suite re-run against production; "Discourage search engines" flag confirmed off; sitemap submitted to Search Console/Bing; `robots.txt` verified; uptime and 404/redirect monitoring enabled; backup **and a tested restore** confirmed; payment mode (test vs live) confirmed correct for a public portfolio site (OQ-06); demo-site disclosure visible; legal pages and any cookie-consent banner live.
**Decisions finalised:** none new — this stage verifies every prior decision holds under real production conditions.
**Exit criteria:** every item in the architecture §21.4 launch checklist is checked; a fresh visitor journey (browse → filter → view tour → attempt booking → contact form) completes without error in at least two languages.

### Stage 20 — Portfolio Documentation *(Spec Phase 18)*
**Goal:** the project is presentable as a professional case study (Spec §30.16).
**Key activities:** screenshots across breakpoints and languages; project description covering the brief, the reinterpretation from the original bike-tour brief (Spec §2), the technology stack, key features; a "challenges and solutions" section drawing directly from this roadmap's decision log and risk register (e.g. how F-05/TD-31 avoided a dual-source-of-truth bug, how the multilingual compatibility spike in Stage 2 prevented a Stage 11 rebuild); the deferred break/fix diagnostic exercises (Spec §24) written up as a teaching appendix; links to the live site (with its demo disclosure) and the GitHub repository.
**Exit criteria:** the portfolio write-up is published and cross-links to the live demo site and the repository.

---

## 4. Gate summary — what still needs an answer, and when

This table exists so the owner can act on only what's actually blocking the *next* stage, rather than the whole open-questions list at once. Full detail is in [`technical-decisions.md` §3](technical-decisions.md).

| Before starting… | Needs an answer on |
|---|---|
| Stage 2 | OQ-04 (theme/plugin split), OQ-23 (out-of-scope confirmation) |
| Stage 4 | OQ-26 (brand identity — placeholder acceptable), OQ-18 (a11y target — default AA acceptable) |
| Stage 5 | OQ-07 (private tours), OQ-08 (taxonomy/nav — tours half), OQ-09 (price model), OQ-25 (disabled-tour meaning) |
| Stage 9 | OQ-12 (FAQ placement), OQ-15 (contact-form data handling) |
| Stage 11 | OQ-01 (default language), OQ-02 (`uk`/`/uk/`), OQ-20 (translation approach) |
| Stage 12 | OQ-05 (booking solution), OQ-10 (confirmed default: booking system owns availability) |
| Stage 13 | OQ-06 (real vs. test-mode payments) |
| Stage 17 | OQ-21 (staging — yes/no), OQ-24 (repo visibility) |
| Stage 18 | OQ-17 (hosting/domain/DNS) |
| Stage 19 | OQ-19 (legal-page scope and professional review) |

Every one of these already has a working default in [`technical-decisions.md`](technical-decisions.md), so **no stage is blocked** by an unanswered question — the roadmap can proceed on defaults and be corrected at the named gate if the owner responds later.

---

*End of `implementation-roadmap.md`. This completes the four Phase 0 deliverables.*
