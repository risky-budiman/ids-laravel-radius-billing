# Changelog

Riwayat perubahan per versi. Disusun otomatis dari riwayat commit oleh `php artisan app:generate-changelog --write`.

## [v1.5.133] - 2026-11-16

### Perbaikan
- **customers:** List header showed raw Blade directives; import/export and add buttons follow role again

## [v1.5.132] - 2026-11-16

### Perbaikan
- **mitra:** Partner accounts can log in and reach their own page

## [v1.5.131] - 2026-11-16

### Perbaikan
- **users:** Editing an administrator no longer demotes them; API staff roles match the web form

## [v1.5.130] - 2026-11-16

### Perbaikan
- **olt:** Credentials encrypted, hidden from the edit form; OLT changes for administrators only

## [v1.5.129] - 2026-11-16

### Perbaikan
- **dashboard:** Finance figures and activity log only for the roles allowed to see them

## [v1.5.128] - 2026-11-16

### Perbaikan
- **import:** Admin-only import/export, passwords opt-in, update mode no longer wipes or reshuffles data

## [v1.5.127] - 2026-11-16

### Perbaikan
- **olt:** ONU write actions check the serial first; speed changes use the package T-CONT profile

## [v1.5.126] - 2026-11-16

### Fitur baru
- **roles:** Customer Service role with package-change requests that need approval

## [v1.5.125] - 2026-11-16

### Perbaikan
- **settings:** App settings for administrators only, known keys only; tidy both settings pages

## [v1.5.124] - 2026-11-16

### Perbaikan
- **accounting:** Cash flow classification as settings; customer collections count as operating

## [v1.5.123] - 2026-11-16

### Peningkatan
- Customer search in ticket and stock-in forms; warehouse stock counts in bulk

## [v1.5.122] - 2026-11-16

### Perbaikan
- **accounting:** Inventory purchases and usage reach the books correctly

## [v1.5.121] - 2026-11-15

### Fitur baru
- **finance:** Write off uncollectible invoices

## [v1.5.120] - 2026-11-15

### Fitur baru
- **billing:** Configurable grace days before suspension

## [v1.5.119] - 2026-11-15

### Perbaikan
- **inventory:** Activation materials cannot overdraw stock; damaged and lost units

## [v1.5.118] - 2026-11-15

### Perbaikan
- **ba:** Printed installation report fits one A4 page without a blank first sheet

## [v1.5.117] - 2026-11-15

### Perbaikan
- **customers:** Editing no longer turns customers awaiting activation into suspended or wipes coordinates

## [v1.5.116] - 2026-11-15

### Perbaikan
- **activation:** Modem photo optional and small enough to upload; OCR reads and matches serials

## [v1.5.115] - 2026-11-15

### Fitur baru
- **portal:** WhatsApp OTP login for the customer portal and customer app

## [v1.5.114] - 2026-11-15

### Perbaikan
- **payments:** Moota matching no longer guesses; resent mutations cannot pay another invoice

## [v1.5.113] - 2026-11-15

### Perbaikan
- **inventory:** Consistent available stock, mandatory serials, archive instead of delete, stock opname

## [v1.5.112] - 2026-11-15

### Perbaikan
- **changelog:** One entry per version with its own changes, grouped by type

## [v1.5.111] - 2026-11-15

### Fitur baru
- **activation:** Installation report (BA) with digital signatures, mandatory installation invoice and payment deadline

## [v1.5.110] - 2026-11-15

### Fitur baru
- **api:** Activation & dismantle wizard for the technicians' mobile app

## [v1.5.109] - 2026-11-15

### Fitur baru
- **billing:** Automatic dismantle request for long-suspended customers

## [v1.5.108] - 2026-11-15

### Fitur baru
- **reports:** Business KPI dashboard — MRR, ARPU, churn, new vs lost

## [v1.5.107] - 2026-11-15

### Fitur baru
- **i18n:** Indonesian interface — menus, labels, validation and messages

## [v1.5.106] - 2026-11-15

### Fitur baru
- **tickets:** WhatsApp alerts for unclaimed and late tickets

## [v1.5.105] - 2026-11-15

### Fitur baru
- **billing:** Receivables aging report and "to be suspended" list

## [v1.5.104] - 2026-11-15

### Peningkatan
- **billing:** Make billable customer statuses an explicit, tested rule

## [v1.5.103] - 2026-11-15

### Fitur baru
- **billing:** WhatsApp payment reminders H-3 / H-1 / due day

## [v1.5.102] - 2026-11-15

### Fitur baru
- **tickets:** Credit unassigned tickets to the staff who resolve them

## [v1.5.101] - 2026-11-15

### Perbaikan
- **backup:** Pass SystemRoot to mysqldump on Windows (error 2004 / 10106)

## [v1.5.100] - 2026-11-15

### Perbaikan
- **backup:** Find mysqldump automatically; "Backup sekarang" failed from the web

## [v1.5.99] - 2026-11-15

### Fitur baru
- **audit:** Log changes to cash/bank, prices, gateways and settings

## [v1.5.98] - 2026-11-15

### Fitur baru
- **backup:** Daily full-database backup; fix settings ignored after config:cache

## [v1.5.97] - 2026-11-15

### Perbaikan
- **accounting:** Cash flow double-counted split journals; Excel export was a placeholder

## [v1.5.96] - 2026-11-15

### Perbaikan
- **update:** Web updater refuses releases that need composer/npm; reports failed migrations

## [v1.5.95] - 2026-11-15

### Keamanan
- **security:** Login brute-force limit could be bypassed via X-Forwarded-For

## [v1.5.94] - 2026-11-15

### Perbaikan
- **payments:** No double-recorded payments; underpaid gateway callbacks held

## [v1.5.93] - 2026-11-15

### Perbaikan
- **isolir:** RADIUS group is internal; PPP profile optional and auto-created

## [v1.5.92] - 2026-11-15

### Fitur baru
- **isolir:** Payment bypass list and MikroTik script filled from system data

## [v1.5.91] - 2026-11-15

### Fitur baru
- **isolir:** RADIUS isolation with configurable isolation IP allocation

## [v1.5.90] - 2026-11-15

### Perbaikan
- **billing:** Overdue customers were never suspended; renewal invoices failed

## [v1.5.89] - 2026-11-15

### Perbaikan
- **finance:** Statement balances follow mutations; atomic balance updates

## [v1.5.88] - 2026-11-15

### Fitur baru
- **reports:** Monthly/yearly ticket recap and bank statement (rekening koran)

## [v1.5.87] - 2026-11-15

### Peningkatan
- **tickets:** Shared rules for web and API; stop duplicate notifications

## [v1.5.86] - 2026-11-15

### Perbaikan
- **billing:** Paying arrears no longer reactivates dismantled customers

## [v1.5.85] - 2026-11-15

### Perbaikan
- **ui:** Remove duplicate select chevrons and duplicate plus signs

## [v1.5.84] - 2026-11-15

### Peningkatan
- Single lifecycle/payment path for web and API; fix FUP

## [v1.5.83] - 2026-11-14

### Peningkatan
- Make the full suite green (118 passing) and add CI

## [v1.5.82] - 2026-11-14

### Perbaikan
- **tickets:** Admin ticket detail crashed with a PHP syntax error

## [v1.5.81] - 2026-11-14

### Perbaikan
- **ui:** Restore solid background for invoice payment modals

## [v1.5.80] - 2026-11-14

### Fitur baru
- **ui:** Apply design system to remaining admin pages

## [v1.5.79] - 2026-11-14

### Fitur baru
- **ui:** Restyle finance pages and add Indonesian error pages

## [v1.5.78] - 2026-11-14

### Fitur baru
- **ui:** Restyle customer forms, invoice create, ticket detail and form inputs

## [v1.5.77] - 2026-11-14

### Fitur baru
- **ui:** Redesign customer detail page with real billing history

## [v1.5.76] - 2026-11-14

### Fitur baru
- **print:** Global print system and redesigned accounting reports

## [v1.5.75] - 2026-11-14

### Fitur baru
- **invoice:** Modern custom template engine and correct thermal size

## [v1.5.74] - 2026-11-14

### Fitur baru
- **ui:** Redesign admin login and invoice print page

## [v1.5.73] - 2026-11-14

### Fitur baru
- **ui:** Redesign customer portal, login and public invoice page

## [v1.5.72] - 2026-11-14

### Keamanan
- **security:** Harden RADIUS CoA, login rate limits and debug routes

## [v1.5.71] - 2026-11-14

### Keamanan
- **security:** Stop invoice enumeration on the public payment portal

## [v1.5.70] - 2026-11-14

### Keamanan
- **security:** Make payment webhooks fail-closed

## [v1.5.69] - 2026-11-14

### Keamanan
- **security:** Restrict admin API to staff tokens and per-role groups

## [v1.5.68] - 2026-11-14

### Fitur baru
- **ui:** Restyle invoices, tickets and subscribers list pages

## [v1.5.67] - 2026-11-14

### Fitur baru
- **ui:** Redesign dashboard with clean card layout

## [v1.5.66] - 2026-11-14

### Fitur baru
- **ui:** Redesign app shell with shared design system

## [v1.5.65] - 2026-11-01

### Perbaikan
- **auth:** Fix foreign key constraint on activity log during customer portal login

## [v1.5.64] - 2026-11-01

### Fitur baru
- **whatsapp:** Implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging

## [v1.5.63] - 2026-11-01

### Perbaikan
- **portal:** Make invoice portal branding and company details fully dynamic (v1.5.62)

## [v1.5.61] - 2026-11-01

### Perbaikan
- **whatsapp:** Map id_pelanggan to real customer_code instead of pppoe username

## [v1.5.60] - 2026-11-01

### Perbaikan
- **payment:** Register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications

## [v1.5.59] - 2026-11-01

### Fitur baru
- **whatsapp:** Add single delete, bulk delete, and message detail modal

## [v1.5.58] - 2026-11-01

### Perbaikan
- **invoice:** Prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check

## [v1.5.57] - 2026-10-31

### Keamanan
- **security:** Resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests

## [v1.5.56] - 2026-10-30

### Fitur baru
- **olt:** Separate onu name and desc, add last reason from alarm history

## [v1.5.55] - 2026-10-30

### Perbaikan
- **view:** Extract alpine component to script block to prevent html attribute tag break

## [v1.5.54] - 2026-10-30

### Perbaikan
- **olt:** Optimize hsgq cli query per-port and fix relative fetch route in show_port

## [v1.5.53] - 2026-10-30

### Fitur baru
- **olt:** Add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53)

## [v1.5.52] - 2026-10-30

### Fitur baru
- **olt:** Add pure SNMP integration for HSGQ EPON OLT (v1.5.52)

## [v1.5.51] - 2026-10-30

### Fitur baru
- Adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51)

## [v1.5.50] - 2026-10-30

### Fitur baru
- Add interactive audio activator pill and auto-unlock on interaction (v1.5.50)

## [v1.5.49] - 2026-10-30

### Fitur baru
- Add one-time browser permission banner and silent audio unlock on interaction (v1.5.49)

## [v1.5.48] - 2026-10-30

### Fitur baru
- Add authentic WAV notification audio file and HTML5 audio player (v1.5.48)

## [v1.5.47] - 2026-10-30

### Perbaikan
- Implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47)

## [v1.5.46] - 2026-10-30

### Perbaikan
- Move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46)

## [v1.5.45] - 2026-10-30

### Fitur baru
- Make toast banner and dropdown fully responsive with close button (v1.5.45)

## [v1.5.44] - 2026-10-30

### Perbaikan
- Resolve audio context promise and auto-unlock sound on user gesture (v1.5.44)

## [v1.5.43] - 2026-10-30

### Perbaikan
- Debounce notifications and remove duplicate listener bindings (v1.5.43)

## [v1.5.42] - 2026-10-30

### Perbaikan
- Resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42)

## [v1.5.41] - 2026-10-30

### Fitur baru
- Add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41)

## [v1.5.40] - 2026-10-30

### Fitur baru
- Implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39)

## [v1.5.38] - 2026-10-30

### Peningkatan
- Clean up imports and facades in InvoiceController

## [v1.5.37] - 2026-10-30

### Fitur baru
- Add full activity logging for invoice bulk delete and journal management

## [v1.5.36] - 2026-10-30

### Perbaikan
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3

## [v1.5.34] - 2026-09-16

### Peningkatan
- Update ACS WAN Config

## [v1.5.33] - 2026-09-16

### Peningkatan
- Update Syetem Update

## [v1.5.32] - 2026-09-15

### Fitur baru
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan

## [v1.5.31] - 2026-08-28

### Peningkatan
- Update API Endpoint

## [v1.5.30] - 2026-08-23

### Peningkatan
- Update API endpoint

## [v1.5.29] - 2026-08-23

### Peningkatan
- Update API endpoint

## [v1.5.28] - 2026-08-23

### Peningkatan
- Update API endpoint

## [v1.5.27] - 2026-08-23

### Peningkatan
- Update API endpoint

## [v1.5.26] - 2026-08-23

### Peningkatan
- Update API endpoint

## [v1.5.25] - 2026-08-23

### Peningkatan
- Closing Priod API

## [v1.5.24] - 2026-08-23

### Peningkatan
- Closing Priod API

## [v1.5.23] - 2026-08-22

### Peningkatan
- Update API Finance

## [v1.5.22] - 2026-08-22

### Peningkatan
- Update API Finance

## [v1.5.21] - 2026-08-21

### Peningkatan
- Update endpoint GenieACS Portal Admin

## [v1.5.20] - 2026-08-21

### Fitur baru
- Update Fitur API admin portal

## [v1.5.19] - 2026-08-19

### Fitur baru
- Add interactive tag search and clickable tag filters in GenieACS
- Add interactive device tag editor and management in GenieACS

## [v1.5.18] - 2026-08-19

### Perbaikan
- Fix changelog git permissions and log output in updater
- Fix changelog generation for v1.5.17

### Peningkatan
- Display device tags and enable search by clean IDPEL username and tags in GenieACS
- Bump version to 1.5.18 with auto-syncing changelog

## [v1.5.17] - 2026-08-19

### Fitur baru
- Add Endpoint Apps Portal
- Add MikroTik-style web auto-updater in server-logs
- Bump version to 1.5.17 and add app:bump-version command

### Perbaikan
- Fix Payment Gateway
- Fix API Mobile Apps
- Fix Ticket
- Fix package speed mapping and clean credentials
- Fix web portal payment gateway route mapping
- Fix payment gateway
- Fix payment gateway timeout and SSL validation on local environment
- Fix Update
- Fix Git safe directory and accurate ls-remote detection

### Peningkatan
- Finaly Subcribers
- Finaly WA Gateway
- Finaly Ticket
- Finaly NAS
- Update Ticket and GenieACS
- Auto discover ports on view and instant live SNMP query
- Optimize SNMP discovery speed and fast multi-table scan
- Replace port polling with direct instant SNMP sync button

## [v1.5.16] - 2026-08-03

### Peningkatan
- Finaly Payment Gateway

## [v1.5.15] - 2026-08-01

### Perbaikan
- Bugfix Invoice

## [v1.5.14] - 2026-08-01

### Perbaikan
- Bugfix Akutansi

## [v1.5.13] - 2026-08-01

### Perbaikan
- Bugfix Closing Priod

## [v1.5.12] - 2026-08-01

### Fitur baru
- Add Edit mutasi Bank

### Perbaikan
- Bugfix Closing Priod

## [v1.5.11] - 2026-07-31

### Fitur baru
- Add Search Jurnal

### Perbaikan
- Bugfix Dashboard

## [v1.5.10] - 2026-07-31

### Perbaikan
- Update PPN and Perbaikan Closed Priode, Dashboard, bugfix invoice and update

### Peningkatan
- End

## [v1.5.9] - 2026-07-10

### Perbaikan
- Perbaikan Avtivasi
- Perbaikan Ticket, Customer, Sales role
- Perbaikan pada menu sales

### Peningkatan
- Update invoice

## [v1.5.8] - 2026-05-04

### Peningkatan
- Update WA Ticket

## [v1.5.7] - 2026-05-04

### Perbaikan
- Perbaikan tampilan dashboard
- Perbaikan Bug
- Perbaikan logika cycle
- Bugfix coa
- Bugfix CoA

## [v1.5.6] - 2026-05-03

### Perbaikan
- Perbaikan Tampilan

## [v1.5.5] - 2026-05-03

### Peningkatan
- Paket

## [v1.5.4] - 2026-05-03

### Peningkatan
- Wirehouse

## [v1.5.3] - 2026-05-03

### Perbaikan
- Emergency alias for discovery fix
- Bugfix akutasi

### Peningkatan
- Biugfix
- Customer, data master

## [v1.5.2] - 2026-05-01

### Perbaikan
- Emergency alias for discovery fix

## [v1.5.1] - 2026-05-01

## [v1.5.0] - 2026-05-01

## [v1.4.0] - 2026-05-01

### Fitur baru
- Add Fitur

## [v1.3.14] - 2026-05-01

## [v1.3.13] - 2026-05-01

### Perbaikan
- BugFix NOC

## [v1.3.12] - 2026-05-01

### Perbaikan
- BugFix OLT2

## [v1.3.11] - 2026-05-01

### Perbaikan
- BugFix OLT

## [v1.3.10] - 2026-05-01

### Perbaikan
- BugFix OLT

## [v1.3.9] - 2026-05-01

### Perbaikan
- BugFix OLT

## [v1.3.8] - 2026-05-01

### Perbaikan
- BugFix OLT

### Peningkatan
- Update

## [v1.3.7] - 2026-04-29

### Peningkatan
- Update

## [v1.3.6] - 2026-04-29

### Perbaikan
- BugFix Session Online

## [v1.3.5] - 2026-04-29

### Perbaikan
- Bugfix
- BugFix Jurnal

## [v1.3.4] - 2026-04-29

### Peningkatan
- Update file

## [v1.3.3] - 2026-04-29

### Perbaikan
- BugFix TimeZone

## [v1.3.2] - 2026-04-29

### Perbaikan
- BugFix Jurnal Umum

## [v1.3.1] - 2026-04-29

### Perbaikan
- BugFix Dashboard

## [v1.3.0] - 2026-04-29

### Fitur baru
- Add Fitur Sales

### Perbaikan
- BugFix Subribers

## [v1.2.0] - 2026-04-29

### Fitur baru
- Add Fitur FUP
- Add roadmap

## [v1.1.3] - 2026-04-29

### Fitur baru
- Add Initialis

## [v1.1.2] - 2026-04-29

### Perbaikan
- BugFix Changelog

## [v1.1.1] - 2026-04-29

### Perbaikan
- BugFix Changelog

## [v1.1.0] - 2026-04-29

### Perbaikan
- BugFix Changelog

## [v1.0.2] - 2026-04-29

### Fitur baru
- Add Fitur Reseller

## [v1.0.1] - 2026-04-29

### Perbaikan
- BugFix Version Realease

## [v1.0.0] - 2026-04-29

### Fitur baru
- Penambahan fitur ticket
- Membuat system priode billing, menambahkan log activity, dan perubahan tampilan login
- Penambahan fitur role dan menu manage staff
- Tambah Fitur Changeloh
- Update Fitur Akun Login
- Update fitur inventory
- Penambahan fitur genieacs
- Menambah fitur maps pada pelanggan
- Add Fitur Whatsapp
- Add Fitur Rekening Bank dan menu NOC
- Add fitur finance, accounting and tax
- Add fitur upload favicon
- Add dokumentasi pengguna
- Update fitur ticket
- Update data customer dan penambahan fitur odp dll
- Udpate fitur melakuakn seting nominal pajak
- Add fitur portal pelanggan

### Perbaikan
- Perubahan database radacct dan perbaikan activasi
- Perbaikan database pada acct
- Perbaikan database dan penambahkan Log user login radius
- Perbaikan sesion dan perbaukan auth log
- Perbaikan kick user pada session
- Perbaikan pada subribers penambahkan beberapa fitur
- Perbaikan pada auth log
- Perbaikan tampilan invoice
- Perbaikan pada bagian invoice
- Perbaikan pada menu paket
- BugFix edit subriber
- Perbaikan Horizon
- Perbaikan Bug Horizon login akses
- Perbaikan migrasion finace
- Perbaikan sidebar
- Perbaikan menu dan tambah rekening
- Perbaikan tambah  rekening baru
- Add fitur & bugfix
- BugFix bagian kecil
- BugFix CoA Finance
- BugFix CoA
- BugFix Version Realease

### Peningkatan
- Billing Laravel Radius IDS
- Update live trafick dashboard
- Menambahkan no urut pada semua menu
- Horizon dan Server log
- Pembaruan roadmap
- Update roadmap
- Update seeder
- Update data customer dengan OCR
- Update inventory scan sn product
- Update seeder tax
