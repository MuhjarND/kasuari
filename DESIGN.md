---
version: alpha
colors:
  primary: "#214a9a"
  background: "#f4f6fb"
  surface: "#ffffff"
  heading: "#0f172a"
  text: "#374151"
  muted: "#6b7280"
typography:
  body:
    fontFamily: "Inter, sans-serif"
  heading:
    fontFamily: "Plus Jakarta Sans, sans-serif"
rounded:
  status: "6px"
---

# KASUARI Design Context

## Overview

Internal court registers are work-focused surfaces in Indonesian. Preserve the
existing blue KASUARI shell, readable dense tables, and existing mobile row layout.
Do not add navigation for a status that belongs to an individual case.

## Colors

The runtime source of truth is `assets/css/kasuari-modern.css` (`:root`).
Brand uses `--indigo` (#214a9a); surfaces use `--surface` (#ffffff) and
`--bg` (#f4f6fb); text uses `--text-h` (#0f172a), `--text-b` (#374151),
and `--text-m` (#6b7280). Bootstrap semantic badge intents are reused for status:
success for a recorded issuance, warning for incomplete data, secondary for unknown.
Status always has a readable text label, not color alone.

## Typography

Use the existing Plus Jakarta Sans and Inter stacks from the application stylesheet.
Status metadata uses 12-13px text with wrapping. Dates follow the existing
`kasuari_tanggal_indonesia` formatter. Sync timestamps show seconds and WIT.

## Layout

Registers use the existing JSTable pagination and filters. Akta summaries stay
inside the current status cell; detail fields stay inside the Status Perkara area.
No additional column, menu, nested card, or new page is required.

## Shapes

Use existing surface geometry; new badges use `--r-sm` (6px).

## Components

`sys/sys_akta_cerai.php` owns the shared akta summary/detail renderer for both
registers and details, including the server-side administrator guard.
Empty dates mean not recorded in SIPP, not proof of an EAC download state.
The source and timestamp are visible in detail. Data from an incomplete sync is
not published. Non-administrators receive no akta markup or metadata.

## Do's and Don'ts

- Preserve current navigation, filtering, and non-akta case status.
- Distinguish no akta sync from a completed snapshot with no issuance record.
- Do not introduce EAC download/print actions or imply EAC integration.

## SIPP Monitoring Portal

The separate SIPP navigation is administrator-only: SIPP, satker, then grouped
modules. It extends the existing shell without changing the case register flow.
Four courts have isolated snapshots and court-specific configuration tokens.
The portal offers read-only records, filters, CSV export, case timelines, date-based
statistics, data completeness checks, and the source menu inventory. Operational
transactions and official document services remain in the source SIPP system.

Use unframed page sections and repeated module links, restrained borders, and
wrapping labels. Tables scroll inside their own container on small screens;
the page and sidebar must not acquire horizontal overflow. Empty data, unavailable
source tables, and incomplete source columns have distinct states. Retained data
must keep its last successful snapshot timestamp and an explicit source warning.
The token replacement confirmation uses the existing Bootstrap modal with keyboard
focus restoration. Never render raw tokens, credentials, or HTML from source rows.

### SIPP UI Revision

The Image Gen concept is a reference for the main workspace, not a replacement
for KASUARI branding. Keep the existing sidebar/logo and collapse module groups
under Modul satker so all four courts remain reachable. Main content is true white
with #e2e7ed rules, #17212f text, #657184 metadata, #2355a4 active tabs/commands,
#147a57 solid ready badges, and #f4ce69 source warnings. No gradients or cards
around sections. Inter follows the existing shell; headings are 26px desktop,
23px mobile, directory content 13px, and metadata 11-12px with zero tracking.

The overview uses a four-cell unframed summary, followed by a grouped directory
with live text/category filters, URL persistence, reset and a no-results state.
Counters come only from dataset states: records are not unique case counts.
Unknown sources stay distinct from completed empty sources. Directory rows become
two-line items on mobile; filters and summary use stable grid tracks.
The source timestamp is hidden only in the compact directory, not record detail.
No new backend operation or SIPP permission is introduced by the UI revision.

### Visual Comparison

- Workspace: Image Gen white matched; removed inherited tinted app-content background.
- Hierarchy: heading, court selector, five underline tabs, four summary cells, then directory.
- Directory: grouped row layout retained; 54px rows allow seconds and readable metadata.
- Color: solid green/amber labels retained; fixed inherited low-contrast header badge text.
- Controls: removed inherited purple gradients/shadows from SIPP commands and token modal.
- Responsive: summary becomes 2x2, directory becomes two-line rows, record tables show
  labeled fields in stacked records instead of horizontal scrolling.
- Intentional differences: retain the real KASUARI shell/branding, existing Bootstrap
  icons, and a collapsed Modul satker folder rather than the generated sidebar/logo.
- Copy: main heading, tabs, summary and directory labels match the design brief;
  values and timestamps always come from actual state. No generated metric is hardcoded.

Reference: output/design-sipp/konsep-sipp-v2.png. The image is a design reference,
not an app background or a replacement for interactive HTML controls.
