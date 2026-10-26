=== Mox Icons - Icon Pack for Elementor ===
Contributors: moxcreative
Tags: elementor, icons, icon pack, svg icons, web design
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add popular icon libraries to Elementor's native icon picker with local assets and per-library toggles by Moxcreative.

== Description ==

**Mox Icons** adds popular, high-quality icon libraries directly to Elementor's native icon picker. No extra widget needed; just open any icon selector in Elementor and browse your new icons.

= Included Libraries =

* **Heroicons Outline** - 324 clean stroke icons by Tailwind Labs (MIT License)
* **Heroicons Solid** - 324 filled icons by Tailwind Labs (MIT License)
* **Material Design Icons** - 7,447 community icons by Pictogrammers (Apache 2.0)
* **Simple Line Icons** - 189 minimal line icons by Sabbir Ahmed (MIT License)
* **Lucide** - 1,962 open-source stroke icons (ISC License)
* **Tabler Icons** - 5,093 outline interface icons (MIT License)
* **Phosphor Icons** - 1,512 regular-weight icons (MIT License)
* **Bootstrap Icons** - 2,078 official Bootstrap icons (MIT License)
* **Iconoir** - 1,383 modern interface icons (MIT License)
* **Feather Icons** - 287 simple stroke icons (MIT License)
* **Fluent UI System Color Icons** - 890 colorful interface icons by Microsoft (MIT License)
* **Pixelarticons** - 1,172 pixel-style interface icons by Gerrit Halfmann (MIT License)
* **Material Symbols Light** - 3,978 outline rounded icons by Google (Apache 2.0)
* **Myna UI Icons** - 2,892 clean UI icons by Praveen Juge (MIT License)
* **Health Icons** - 3,391 health and medical icons by Resolve to Save Lives (MIT License)

= Features =

* Native Elementor icon picker integration
* Toggle each library ON or OFF from the Mox Icons admin menu
* All assets bundled locally; no CDN, no external requests
* Only the libraries you enable are registered with Elementor
* Pages load only the icon rules they actually use, not whole icon sets
* Translation ready

= Requirements =

* WordPress 6.0+
* Elementor 3.5.0+
* PHP 7.4+

== Installation ==

1. Upload the `mox-icons-pack-for-elementor` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress
3. Go to **Mox Icons** in the admin menu to enable or disable individual icon packs
4. Open Elementor editor; new icon tabs will appear in the icon picker

== Frequently Asked Questions ==

= Does this work with Elementor Free? =

Yes, Mox Icons works with both Elementor Free and Elementor Pro.

= Will disabled libraries affect performance? =

No. A disabled library is not registered with Elementor at all, so it adds nothing to the editor or to your pages.

= How much weight does an enabled library add to a page? =

Only the icons you actually placed. Each icon contributes roughly 0.3 to 2 KB of CSS, so a page with a dozen icons carries a few kilobytes, whatever the size of the libraries they came from.

Material Design Icons is a webfont rather than CSS, so it still loads its stylesheet and font file in full when one of its icons appears on a page.

= My icons are not styled after updating to 1.1.5 =

This can happen if something other than Elementor's own icon controls outputs Mox Icons classes, for example a custom template or a shortcode that writes the markup by hand. Those elements never reach the plugin, so no rules are emitted for them. Load the complete stylesheet for that library instead:

`add_filter( 'mox_icon_use_full_stylesheet', function ( $full, $library ) { return 'healthicons' === $library ? true : $full; }, 10, 2 );`

= Are the icons licensed for commercial use? =

Yes. The bundled libraries use open licenses such as MIT, Apache 2.0, and ISC. Third-party credit and license information is shown in the Mox Icons admin page.

== Source ==

The icon stylesheets in `assets/libs/` are generated or copied from these public packages. Each library folder also carries the full license text.

* Heroicons 2.2.0 - https://github.com/tailwindlabs/heroicons
* Material Design Icons 7.4.47 - `@mdi/font`, https://github.com/Templarian/MaterialDesign-Webfont. `materialdesignicons.min.css` is the minified stylesheet from that package with its `@font-face` rule reduced to the WOFF2 file; the readable source is `css/materialdesignicons.css` in the same package.
* Simple Line Icons - `@iconify-json/simple-line-icons` 1.2.1
* Lucide - `lucide-static` 1.17.0
* Tabler Icons - `@tabler/icons` 3.44.0
* Phosphor Icons - `@phosphor-icons/core` 2.1.1
* Bootstrap Icons - `bootstrap-icons` 1.13.1
* Iconoir - `iconoir` 7.11.0
* Feather Icons - `feather-icons` 4.29.2
* Fluent UI System Color Icons - `@iconify-json/fluent-color` 1.2.21
* Pixelarticons - `@iconify-json/pixelarticons` 1.2.7
* Material Symbols Light - `@iconify-json/material-symbols-light` 1.2.93
* Myna UI Icons - `@iconify-json/mynaui` 1.2.19
* Health Icons - `@iconify-json/healthicons` 1.2.13

For the SVG libraries, each icon is written as a CSS rule holding its SVG as a data URI, so the stylesheets are generated output rather than minified code.

== Screenshots ==

1. Settings page with library toggles
2. Heroicons Outline in Elementor icon picker
3. Material Design Icons in Elementor icon picker

== Changelog ==

= 1.1.7 =
* Fixed Material Design Icons going blank on a page where an empty Elementor document, such as a template, footer or popup with no elements yet, is rendered after the content that uses the icons. The library stylesheet was taken off the page and never put back.
* Fixed Material Design Icons sitting off-centre in Elementor's Social Icons widget, with a stray empty character beside the icon in some browsers. The widget copies the icon class onto its link, which made the link draw a second glyph.
* Tested with WordPress 7.1.3 and Elementor 4.3.4.

= 1.1.6 =
* Fixed Material Design Icons, and Simple Line Icons placed with 1.1.4 or earlier, losing their stylesheet when Elementor served the page from its element cache. The icons showed on the first visit and were blank on the following ones.
* Fixed icons losing their styles in a template, header, footer or loop item that Elementor had cached while it was shown next to another document using the same icon.
* Material Design Icons now ships only its WOFF2 font, which every supported browser uses; the TTF and WOFF copies are removed, which makes the installed plugin about 1.9 MB smaller.
* Removed the invisible `blank` entry from the Material Design Icons picker and the stylesheet references to font and source-map files that were never bundled. The library lists 7,447 icons.
* Icon library definitions are now built once per request instead of once for every icon rendered.
* Readme: corrected where the settings page is found and added a Source section listing the package each bundled library is built from.
* Fixed the `mox_icon_use_full_stylesheet` filter doing nothing for icon classes written outside Elementor's icon controls, which is the case it exists for. A library the filter returns true for now loads its complete stylesheet on every front-end page.

= 1.1.5 =
* Pages now load only the icon rules they use. Previously a single icon made Elementor load that library's whole stylesheet, which for Health Icons was about 4 MB. A page with five icons from four libraries now carries about 4 KB of icon CSS instead of about 12 MB.
* Reduced the bundled icon stylesheets by about 22 per cent (26.3 MB to 20.6 MB) by escaping only what a CSS data URI requires.
* Added the `mox_icon_use_full_stylesheet` filter for sites that output Mox Icons classes outside Elementor's icon controls.
* Rebuilt Simple Line Icons from the Iconify icon set as SVG rules, replacing the webfont. The old build was registered on the unnamespaced `icon` and `icon-*` classes, which many themes and icon fonts also style; a collision replaced the font and the glyphs disappeared. Icons now use `mox-sli` / `mox-sli-*` and render from SVG, so no font file is involved at all. All 189 icon names are unchanged, and the old `icon-*` classes still resolve, so icons placed with earlier versions keep working.
* Simple Line Icons now loads per page like the other SVG libraries: about 1 KB for one icon instead of a 20 KB stylesheet plus a 166 KB font file.
* Fixed icon manifests being cached without a version, which could pair a fresh stylesheet with a stale icon list after an update.
* Fixed uninstall leaving plugin settings behind on the other sites of a multisite network.
* Settings page tabs are now keyboard and screen-reader accessible, work with JavaScript disabled, and text colours meet WCAG AA contrast.
* Added the missing license files for Heroicons, Material Design Icons and Simple Line Icons, and every bundled library now ships the full text of its license rather than a link to it.
* The icon picker cache adapter now reports on the console when it cannot attach to Elementor, instead of failing silently.
* Internal: all 15 libraries are now described in one place and registered through a single adapter class.

= 1.1.4 =
* Fixed large icon libraries failing to open when Elementor's persistent browser icon cache reaches its storage quota; Mox catalogs now use an editor-session memory cache.
* Added Material Symbols Light (3,978 outline rounded icons by Google)
* Added Myna UI Icons (2,892 icons by Praveen Juge)
* Added Health Icons (3,391 health and medical icons by Resolve to Save Lives)
* Removed Material Line Icons (its SMIL animations cannot render when icons are used as CSS mask images)

= 1.1.2 =
* Added Fluent UI System Color Icons (890 icons)
* Added Pixelarticons (1,172 icons)
* Added Material Line Icons (1,279 icons)
* Improved generated Iconify SVG data so bundled icons render consistently from local CSS assets

= 1.1.1 =
* Fixed critical error on activation for some shared hosting environments by adding PHP version check and file existence checks.
* Inlined generated SVG library assets into CSS to avoid shipping thousands of individual icon files, improving upload, activation, and deletion reliability on shared hosting.

= 1.1.0 =
* Added Lucide (1,962 icons)
* Added Tabler Icons (5,093 icons)
* Added Phosphor Icons (1,512 icons)
* Added Bootstrap Icons (2,078 icons)
* Added Iconoir (1,383 icons)
* Added Feather Icons (287 icons)
* Added third-party license files and expanded Icon Info credits

= 1.0.0 =
* Initial release
* Heroicons Outline (324 icons)
* Heroicons Solid (324 icons)
* Material Design Icons (7,448 icons)
* Simple Line Icons (189 icons)

== Upgrade Notice ==

= 1.1.7 =
Fixes Material Design Icons going blank next to an empty Elementor template and sitting off-centre in the Social Icons widget.

= 1.1.6 =
Fixes icons going blank on pages served from Elementor's element cache. Recommended for everyone on 1.1.5.

= 1.1.5 =
Pages now load only the icon rules they use instead of whole icon sets, and Simple Line Icons is rebuilt from SVG so it no longer depends on a webfont that themes could override. Icons placed with earlier versions keep working.

= 1.1.4 =
Adds Material Symbols Light, Myna UI Icons, and Health Icons libraries.

= 1.1.2 =
Adds Fluent UI Color Icons, Pixelarticons, and Material Line Icons.

= 1.1.1 =
Fixes activation critical error on shared hosting and improves upload/deletion reliability by reducing the generated file count.

= 1.1.0 =
Adds six safe, permissively licensed icon libraries and expanded license credits.

= 1.0.0 =
Initial release.
