# QuoteFlow AI UI System

## Reference interpretation

The supplied screenshot contributes visual principles only: compact grouped navigation, a slim utility header, bright neutral canvas, crisp cards, emerald actions, dark navy hierarchy, muted blue-gray text, pastel icon tiles, and restrained shadows. QuoteFlow will not copy its logo, wording, assets, exact dimensions, or product-specific card composition.

The dashboard replaces messaging-focused content with quotation creation, AI Copilot, approvals, expiring work, deposits, outstanding invoices, plan/AI usage, and recent activity. Navigation follows the smaller information architecture in the product brief.

## Semantic tokens

Tokens are CSS custom properties exposed through Tailwind theme values. Values below are the light baseline; dark values are separately authored and contrast-tested.

| Token | Light baseline | Purpose |
|---|---:|---|
| `background` | `#F6F8FA` | App canvas |
| `surface` | `#FFFFFF` | Cards and controls |
| `surface-muted` | `#F1F5F7` | Secondary regions |
| `surface-elevated` | `#FFFFFF` | Menus/dialogs |
| `foreground` | `#0B172A` | Primary text |
| `foreground-muted` | `#53657D` | Secondary text |
| `foreground-subtle` | `#7E8DA3` | Metadata |
| `border` | `#DDE4EA` | Standard borders |
| `border-strong` | `#BAC6D2` | Emphasized boundaries |
| `ring` | `#087F64` | Focus ring |
| `primary` | `#078A68` | Main actions |
| `primary-hover` | `#066E56` | Hover/pressed |
| `primary-soft` | `#E4F7F0` | Active navigation/background |
| `primary-foreground` | `#FFFFFF` | Text on primary |
| `success` / soft | `#087F5B` / `#E6F8F0` | Success |
| `warning` / soft | `#B25E09` / `#FFF4DE` | Warning |
| `danger` / soft | `#C0364B` / `#FDECEF` | Destructive/error |
| `info` / soft | `#2563EB` / `#EAF1FF` | Informational |

Dark mode uses deep blue-gray surfaces rather than inversion: canvas `#08111F`, surface `#101C2C`, muted surface `#162438`, foreground `#F4F7FA`, muted text `#AAB8C8`, border `#2A3A4E`, and a lighter primary `#34C99A`. Status meaning is always paired with text or iconography.

## Scales

- Typography: self-hosted `Inter` if its OFL license is packaged; fallback `ui-sans-serif, system-ui, sans-serif`. Sizes: 12, 14, 16, 18, 22, 28, 36px with 1.2–1.6 line height by role.
- Spacing: 4px base; 4, 8, 12, 16, 20, 24, 32, 40, 48, 64.
- Radius: controls 9px, cards 12px, dialogs 16px, pills full.
- Shadows: border-first; `sm` and `md` only, using low-opacity cool black.
- Controls: 36px compact, 40px default, 44px touch-priority.
- Sidebar: 248px expanded, 76px collapsed; drawer below 1024px.
- Main padding: 16px mobile, 24px tablet, 28–32px desktop; readable forms cap at 880px, dashboards at 1600px.

## Components and behavior

Build accessible primitives for shell/navigation, command palette, page headers, buttons, fields, currency/percentage/date inputs, searchable selects, tags, uploads, structured text, overlays, feedback, cards, timelines, responsive tables, and empty/loading/error states. Domain components include the line editor, totals, optional selections, quotation timeline, AI findings, risk badges, and public quotation sections.

Desktop quotation editing uses a two-pane editor/preview when space permits; tablet collapses preview behind a tab; mobile uses ordered full-width steps with a sticky totals/action region that does not cover content. Drag-and-drop always has move up/down keyboard controls.

## Dashboard composition

1. Page title, one-line context, and date-range selector.
2. Original emerald welcome/action panel with CSS/SVG geometry, paired with a create-quotation/AI Copilot workflow card and conditional plan usage.
3. Responsive KPI cards for created, sent, viewed, approved, approval rate, quoted/approved values, deposits, outstanding invoices, and average approval time.
4. Action-required, expiring, overdue, and recent-activity sections.
5. Currency-aware grouping and honest empty/partial-error/no-permission states.

## Accessibility and visual QA

Meet WCAG 2.1 AA, preserve 44px mobile targets, provide skip links, manage drawer/dialog focus, announce autosave and async results, support reduced motion, and prevent color-only status. Verify at 1440px, 1024px, 768px, and 390px in light/dark modes with keyboard-only navigation, automated checks, screenshots, and horizontal-overflow inspection.
