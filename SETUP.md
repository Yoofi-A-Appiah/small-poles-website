# Small Poles WordPress Theme — Setup Guide

## 1. Get hosting (~5 min)

Recommended: **Hostinger** Business plan (~$3–4/mo) — includes one-click WordPress install, free SSL, and good performance for West Africa.

Alternative: **DigitalOcean** $6/mo droplet with Nginx if you want full server control.

---

## 2. Install WordPress

Via Hostinger: hPanel → Websites → Auto-Installer → WordPress  
Domain: `smallpole.com` (point DNS A record to your server IP)

---

## 3. Upload the theme

1. Zip this entire `wordpress-theme/` folder and rename it `smallpoles.zip`
2. WordPress Admin → Appearance → Themes → Add New → Upload Theme
3. Upload `smallpoles.zip` → Activate

---

## 4. Configure WordPress settings

**Settings → Reading:**
- Your homepage displays: A static page
- Homepage: create a page titled "Home"
- Posts page: create a page titled "News" (slug: `news`)

**Settings → Permalinks:**
- Choose "Post name" (e.g. `/world-cup-2026-preview/`)
- Save — this makes URLs clean for SEO

---

## 5. Create required pages

Create these pages in Pages → Add New (leave content blank, WordPress will use the right template):

| Title | Slug | Notes |
|-------|------|-------|
| Home | `home` | Set as static homepage in Reading settings |
| News | `news` | Set as posts page in Reading settings |
| Features | `features` | Template: `page-features.php` — leave content blank |
| How It Works | `how-it-works` | Template: `page-how-it-works.php` — leave content blank |
| Market | `market` | Template: `page-market.php` — leave content blank |
| Scoring | `scoring` | Template: `page-scoring.php` — leave content blank |

---

## 6. Create post categories

Posts → Categories — create these (exact slugs matter for the badge colors):

| Name | Slug |
|------|------|
| World Cup | `world-cup` |
| GPL Analysis | `gpl-analysis` |
| Platform | `platform` |
| Predictions | `predictions` |

---

## 7. Write your first articles

Posts → Add New. Suggested World Cup launch articles:

1. **"World Cup 2026: Africa's 9 Teams Ranked"** — Category: World Cup
2. **"GPL Stars to Watch at World Cup 2026"** — Category: World Cup  
3. **"SmallPoles Beta: What to Expect"** — Category: Platform

Each post:
- Add a featured image (1200×675px recommended)
- Write a custom excerpt (2 sentences, shown in cards)
- Assign the correct category

---

## 8. Add featured images to posts

For articles without photos, generate gradient cover images using Canva or just use the green gradient default built into the cards.

---

## 9. SEO plugin

Install **Yoast SEO** (free) or **RankMath** (free):  
Plugins → Add New → search → Install → Activate

This gives you:
- Meta title/description per post
- XML sitemap auto-generated at `smallpole.com/sitemap_index.xml`
- Structured data for articles (Google News eligible)

---

## File structure

```
wordpress-theme/
├── style.css               ← Theme stylesheet (3,900+ lines)
├── functions.php           ← Theme setup & helper functions
├── header.php              ← Nav + announcement bar
├── footer.php              ← Footer
├── front-page.php          ← Landing page (home)
├── archive.php             ← News listing page
├── single.php              ← Single blog post
├── page.php                ← Generic page fallback
├── page-features.php       ← Features page (/features/)
├── page-how-it-works.php   ← How It Works page (/how-it-works/)
├── page-market.php         ← Market page (/market/)
├── page-scoring.php        ← Scoring page (/scoring/)
├── index.php               ← Required WordPress fallback
├── 404.php                 ← 404 page
└── assets/
    ├── js/main.js          ← Scroll reveal, mobile nav, Tally embed
    └── (all images)
```
