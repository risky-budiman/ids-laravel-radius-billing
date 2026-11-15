# Changelog

## [v1.5.103] - 2026-11-15
- feat(tickets): credit unassigned tickets to the staff who resolve them ([dfd4cf1])
- fix(backup): pass SystemRoot to mysqldump on Windows (error 2004 / 10106) ([7a2bc90])
- fix(backup): find mysqldump automatically; "Backup sekarang" failed from the web ([bafc364])
- feat(audit): log changes to cash/bank, prices, gateways and settings ([12d5b88])
- feat(backup): daily full-database backup; fix settings ignored after config:cache ([6623e8b])
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])



## [v1.5.102] - 2026-11-15
- fix(backup): pass SystemRoot to mysqldump on Windows (error 2004 / 10106) ([7a2bc90])
- fix(backup): find mysqldump automatically; "Backup sekarang" failed from the web ([bafc364])
- feat(audit): log changes to cash/bank, prices, gateways and settings ([12d5b88])
- feat(backup): daily full-database backup; fix settings ignored after config:cache ([6623e8b])
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])



## [v1.5.101] - 2026-11-15
- fix(backup): find mysqldump automatically; "Backup sekarang" failed from the web ([bafc364])
- feat(audit): log changes to cash/bank, prices, gateways and settings ([12d5b88])
- feat(backup): daily full-database backup; fix settings ignored after config:cache ([6623e8b])
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])



## [v1.5.100] - 2026-11-15
- feat(audit): log changes to cash/bank, prices, gateways and settings ([12d5b88])
- feat(backup): daily full-database backup; fix settings ignored after config:cache ([6623e8b])
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])



## [v1.5.99] - 2026-11-15
- feat(backup): daily full-database backup; fix settings ignored after config:cache ([6623e8b])
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])



## [v1.5.98] - 2026-11-15
- fix(accounting): cash flow double-counted split journals; Excel export was a placeholder ([8137b1c])
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])



## [v1.5.97] - 2026-11-15
- fix(update): web updater refuses releases that need composer/npm; reports failed migrations ([f72affe])
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])



## [v1.5.96] - 2026-11-15
- fix(security): login brute-force limit could be bypassed via X-Forwarded-For ([cb4dccc])
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])



## [v1.5.95] - 2026-11-15
- fix(payments): no double-recorded payments; underpaid gateway callbacks held ([ab841d5])
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])



## [v1.5.94] - 2026-11-15
- fix(isolir): RADIUS group is internal; PPP profile optional and auto-created ([9dd076f])
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])



## [v1.5.93] - 2026-11-15
- feat(isolir): payment bypass list and MikroTik script filled from system data ([b3b925c])
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])



## [v1.5.92] - 2026-11-15
- feat(isolir): RADIUS isolation with configurable isolation IP allocation ([bea7dd4])
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])



## [v1.5.91] - 2026-11-15
- fix(billing): overdue customers were never suspended; renewal invoices failed ([1697e16])
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])



## [v1.5.90] - 2026-11-15
- fix(finance): statement balances follow mutations; atomic balance updates ([ef994c4])
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])



## [v1.5.89] - 2026-11-15
- feat(reports): monthly/yearly ticket recap and bank statement (rekening koran) ([8b783b6])
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])



## [v1.5.88] - 2026-11-15
- refactor(tickets): shared rules for web and API; stop duplicate notifications ([f557971])
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])



## [v1.5.87] - 2026-11-15
- fix(billing): paying arrears no longer reactivates dismantled customers ([01c386c])
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])



## [v1.5.86] - 2026-11-15
- fix(ui): remove duplicate select chevrons and duplicate plus signs ([cc06e2c])
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])



## [v1.5.85] - 2026-11-15
- refactor: single lifecycle/payment path for web and API; fix FUP ([6ef3fe9])
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])



## [v1.5.84] - 2026-11-15
- test: make the full suite green (118 passing) and add CI ([3e6084c])
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])



## [v1.5.83] - 2026-11-14
- fix(tickets): admin ticket detail crashed with a PHP syntax error ([1518108])
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])



## [v1.5.82] - 2026-11-14
- fix(ui): restore solid background for invoice payment modals ([fcb18fe])
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])



## [v1.5.81] - 2026-11-14
- feat(ui): apply design system to remaining admin pages ([6822470])
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])



## [v1.5.80] - 2026-11-14
- feat(ui): restyle finance pages and add Indonesian error pages ([f3790ae])
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])



## [v1.5.79] - 2026-11-14
- feat(ui): restyle customer forms, invoice create, ticket detail and form inputs ([b1b9f77])
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])



## [v1.5.78] - 2026-11-14
- feat(ui): redesign customer detail page with real billing history ([d8ae937])
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])



## [v1.5.77] - 2026-11-14
- feat(print): global print system and redesigned accounting reports ([eccd032])
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])



## [v1.5.76] - 2026-11-14
- feat(invoice): modern custom template engine and correct thermal size ([024a741])
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])



## [v1.5.75] - 2026-11-14
- feat(ui): redesign admin login and invoice print page ([59feac7])
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])



## [v1.5.74] - 2026-11-14
- feat(ui): redesign customer portal, login and public invoice page ([e02ec73])
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])



## [v1.5.73] - 2026-11-14
- fix(security): harden RADIUS CoA, login rate limits and debug routes ([57219ef])
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])



## [v1.5.72] - 2026-11-14
- fix(security): stop invoice enumeration on the public payment portal ([1f1ac37])
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])



## [v1.5.71] - 2026-11-14
- fix(security): make payment webhooks fail-closed ([87c7d2a])
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])



## [v1.5.70] - 2026-11-14
- fix(security): restrict admin API to staff tokens and per-role groups ([c63fa31])
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])



## [v1.5.69] - 2026-11-14
- feat(ui): restyle invoices, tickets and subscribers list pages ([fbc7516])
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])



## [v1.5.68] - 2026-11-14
- feat(ui): redesign dashboard with clean card layout ([ea49987])
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])



## [v1.5.67] - 2026-11-14
- feat(ui): redesign app shell with shared design system ([24a57fc])
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])



## [v1.5.66] - 2026-11-14
- fix(auth): fix foreign key constraint on activity log during customer portal login ([c6fdd4c])
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])



## [v1.5.65] - 2026-11-01
- feat(whatsapp): implement anti-ban staggered delay and rate limiting for invoice and broadcast messaging ([fb53694])
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])



## [v1.5.64] - 2026-11-01
- fix(portal): make invoice portal branding and company details fully dynamic (v1.5.62) ([550b181])
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])



## [v1.5.63] - 2026-11-01
- fix(whatsapp): map id_pelanggan to real customer_code instead of pppoe username ([19fff77])
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])



## [v1.5.61] - 2026-11-01
- fix(payment): register universal pay and portal routes, and resolve dynamic payment links in whatsapp notifications ([4fd1d0a])
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])



## [v1.5.60] - 2026-11-01
- feat(whatsapp): add single delete, bulk delete, and message detail modal ([eed7b7a])
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])



## [v1.5.59] - 2026-11-01
- fix(invoice): prevent duplicate invoice number collision with unique collision-proof generator and duplicate period check ([bf098a7])
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])



## [v1.5.58] - 2026-11-01
- fix(security): resolve mixed content not secure by trusting proxies, forcing https scheme, and upgrading insecure requests ([078889c])
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])



## [v1.5.57] - 2026-10-31
- feat(olt): separate onu name and desc, add last reason from alarm history ([120ab94])
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])



## [v1.5.56] - 2026-10-30
- fix(view): extract alpine component to script block to prevent html attribute tag break ([975ca5e])
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])



## [v1.5.55] - 2026-10-30
- fix(olt): optimize hsgq cli query per-port and fix relative fetch route in show_port ([379b32e])
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])



## [v1.5.54] - 2026-10-30
- feat(olt): add automated ONU discovery and optical diagnostics for HSGQ EPON (v1.5.53) ([d5ec73a])
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])



## [v1.5.53] - 2026-10-30
- feat(olt): add pure SNMP integration for HSGQ EPON OLT (v1.5.52) ([cee7200])
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])



## [v1.5.52] - 2026-10-30
- feat: adopt Facebook ServiceWorker notification pattern with OS chime (v1.5.51) ([e54dbc0])
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])



## [v1.5.51] - 2026-10-30
- feat: add interactive audio activator pill and auto-unlock on interaction (v1.5.50) ([f80f3ba])
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])



## [v1.5.50] - 2026-10-30
- feat: add one-time browser permission banner and silent audio unlock on interaction (v1.5.49) ([3f9536d])
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])



## [v1.5.49] - 2026-10-30
- feat: add authentic WAV notification audio file and HTML5 audio player (v1.5.48) ([6e0d7b6])
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])



## [v1.5.48] - 2026-10-30
- fix: implement native DOM toast with smooth transition to guarantee popup visibility (v1.5.47) ([d117f3d])
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])



## [v1.5.47] - 2026-10-30
- fix: move toast to root layout bottom-right to prevent clipping by navbar (v1.5.46) ([a03906e])
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])



## [v1.5.46] - 2026-10-30
- feat: make toast banner and dropdown fully responsive with close button (v1.5.45) ([86cb7a2])
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])



## [v1.5.45] - 2026-10-30
- fix: resolve audio context promise and auto-unlock sound on user gesture (v1.5.44) ([4e8c206])
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])



## [v1.5.44] - 2026-10-30
- fix: debounce notifications and remove duplicate listener bindings (v1.5.43) ([654154d])
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])



## [v1.5.43] - 2026-10-30
- fix: resolve alpine syntax error in navbar, broadcast directly to local reverb (v1.5.42) ([8777ad9])
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])



## [v1.5.42] - 2026-10-30
- feat: add visual toast, robust echo listener, and test-realtime endpoint (v1.5.41) ([f1d5dfd])
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])



## [v1.5.41] - 2026-10-30
- feat: implement real-time WebSocket notifications and tickets with Laravel Reverb (v1.5.39) ([194f242])
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])



## [v1.5.40] - 2026-10-30
- Clean up imports and facades in InvoiceController ([51ed219])
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])



## [v1.5.38] - 2026-10-30
- Add full activity logging for invoice bulk delete and journal management ([5025a59])
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])



## [v1.5.37] - 2026-10-30
- Fix invoice bulk delete cleanup, add journal filtering and bulk delete, update composer php 8.3 ([21ce7e1])
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])



## [v1.5.36] - 2026-10-30
- Update ACS WAN Config ([432118d])
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])



## [v1.5.35] - 2026-10-30
- Fix bulk delete invoice to automatically clean up associated customer receivable journals & bank transactions
- Add advanced filtering (billing code, billing name, journal type, orphaned) and multi-selection bulk delete to General Ledger
- Update composer platform PHP to 8.3.26

## [v1.5.34] - 2026-09-16
- Update Syetem Update ([6b99a74])
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])



## [v1.5.33] - 2026-09-16
- Add Fitur Backup & Retore, Add Fitur Import & Export data pelanggan ([d4f551c])
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])



## [v1.5.32] - 2026-09-15
- Update API Endpoint ([997ac06])
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])



## [v1.5.31] - 2026-08-28
- Update API endpoint ([f963520])
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])



## [v1.5.30] - 2026-08-23
- Update API endpoint ([56bbda3])
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])



## [v1.5.29] - 2026-08-23
- Update API endpoint ([6deb691])
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])



## [v1.5.28] - 2026-08-23
- Update API endpoint ([8ce1648])
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])



## [v1.5.27] - 2026-08-23
- Update API endpoint ([6e6d7e1])
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])



## [v1.5.26] - 2026-08-23
- Closing Priod API ([a3f1795])
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])



## [v1.5.25] - 2026-08-23
- Closing Priod API ([57d67d8])
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])



## [v1.5.24] - 2026-08-23
- Update API Finance ([2166c5b])
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])



## [v1.5.23] - 2026-08-22
- Update API Finance ([a5c7bb6])
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])
- Optimize SNMP discovery speed and fast multi-table scan ([4d7e458])



## [v1.5.22] - 2026-08-22
- Update endpoint GenieACS Portal Admin ([0f67c0d])
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])
- Optimize SNMP discovery speed and fast multi-table scan ([4d7e458])
- Optimize SNMP discovery speed and fast multi-table scan ([c0fb86a])



## [v1.5.21] - 2026-08-21
- Update Fitur API admin portal ([ec25389])
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])
- Optimize SNMP discovery speed and fast multi-table scan ([4d7e458])
- Optimize SNMP discovery speed and fast multi-table scan ([c0fb86a])
- Auto discover ports on view and instant live SNMP query ([eae9da3])



## [v1.5.20] - 2026-08-21
- Add interactive device tag editor and management in GenieACS ([ccb38b7])
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])
- Optimize SNMP discovery speed and fast multi-table scan ([4d7e458])
- Optimize SNMP discovery speed and fast multi-table scan ([c0fb86a])
- Auto discover ports on view and instant live SNMP query ([eae9da3])
- Fix Update ([85d228a])



## [v1.5.19] - 2026-08-19
- Add interactive tag search and clickable tag filters in GenieACS ([8f218a7])
- Bump version to 1.5.18 with auto-syncing changelog ([dbad885])
- Fix changelog generation for v1.5.17 ([e0bee2c])
- Fix changelog git permissions and log output in updater ([2b4bee8])
- Display device tags and enable search by clean IDPEL username and tags in GenieACS ([87dc9b3])
- Bump version to 1.5.17 and add app:bump-version command ([fb5c9dd])
- Fix Git safe directory and accurate ls-remote detection ([9ee0ed7])
- Replace port polling with direct instant SNMP sync button ([07293a0])
- Add MikroTik-style web auto-updater in server-logs ([a6c4ce2])
- Add MikroTik-style web auto-updater in server-logs ([754b52c])
- Optimize SNMP discovery speed and fast multi-table scan ([4d7e458])
- Optimize SNMP discovery speed and fast multi-table scan ([c0fb86a])
- Auto discover ports on view and instant live SNMP query ([eae9da3])
- Fix Update ([85d228a])
- Fix Update ([f767bf5])



## [v1.5.18] - 2026-08-19
- System maintenance, stability updates, and improvements for v1.5.18 ([RELEASE])



## [v1.5.17] - 2026-08-19
- System maintenance, stability updates, and improvements for v1.5.17 ([RELEASE])



## [1.5.16] - 2026-08-01
- Bugfix Invoice ([HEAD])



## [1.5.15] - 2026-08-01
- Bugfix Akutansi ([HEAD])



## [1.5.14] - 2026-08-01
- Bugfix Closing Priod ([HEAD])



## [1.5.13] - 2026-08-01
- Bugfix Closing Priod ([HEAD])



## [1.5.12] - 2026-08-01
- Bugfix Closing Priod ([HEAD])



## [1.5.11] - 2026-07-31
- Bugfix Dashboard ([HEAD])



## [1.5.10] - 2026-07-31
- Update PPN and Perbaikan Closed Priode, Dashboard, bugfix invoice and update ([HEAD])



## [1.5.8] - 2026-05-04
- Bugfix CoA ([HEAD])



## [1.5.7] - 2026-05-04
- bugfix coa ([HEAD])



## [1.5.6] - 2026-05-03
- Bugfix: Paket ([HEAD])



## [1.5.5] - 2026-05-03
- Bugfix: wirehouse ([HEAD])



## [1.5.4] - 2026-05-03
- Bugfix: customer, data master ([HEAD])



## [1.5.3] - 2026-05-03
- bugfix akutasi ([HEAD])



## [1.5.2] - 2026-05-01



## [1.5.1] - 2026-05-01



## [1.5.0] - 2026-05-01
- Add Fitur ([HEAD])



## [1.4.0] - 2026-05-01



## [1.3.14] - 2026-05-01
- BugFix NOC ([HEAD])



## [1.3.13] - 2026-05-01
- BugFix OLT2 ([HEAD])



## [1.3.12] - 2026-05-01
- BugFix OLT ([HEAD])



## [1.3.11] - 2026-05-01
- BugFix OLT ([HEAD])



## [1.3.10] - 2026-05-01
- BugFix OLT ([HEAD])



## [1.3.9] - 2026-05-01
- BugFix OLT ([HEAD])



## [1.3.8] - 2026-05-01
- BugFix OLT ([HEAD])



## [1.3.7] - 2026-04-29
- BugFix Session Online ([HEAD])



## [1.3.6] - 2026-04-29
- BugFix Jurnal ([HEAD])



## [1.3.5] - 2026-04-29
- BugFix ([HEAD])



## [1.3.4] - 2026-04-29
- BugFix TimeZone ([HEAD])



## [1.3.3] - 2026-04-29
- BugFix Jurnal Umum ([HEAD])



## [1.3.2] - 2026-04-29
- BugFix Dashboard ([HEAD])



## [1.3.1] - 2026-04-29
- BugFix Subribers ([HEAD])



## [1.3.0] - 2026-04-29
- Add Fitur Sales ([HEAD])



## [1.2.0] - 2026-04-29
- Add Fitur FUP ([HEAD])



## [1.1.3] - 2026-04-29
- BugFix Changelog ([HEAD])



## [1.1.2] - 2026-04-29
- BugFix Changelog  ([HEAD])



Semua perubahan penting pada proyek ini akan dicatat di file ini.

## [1.1.1] - 2026-04-29
- Perbaikan sistem otomatisasi changelog ([HEAD])
- Implementasi Fitur Manajemen Reseller & Kemitraan (B2B) ([HEAD])

## [1.1.0] - 2026-04-29
- Release Fitur Reseller & Kemitraan ([HEAD])

## [1.0.2] - 2026-04-29
- Perbaikan Bug Version Release #patch ([HEAD])

## [1.0.0] - 2026-04-28
- Initial Release
