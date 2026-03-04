# Kconecta CRM - Roadmap

## Baseline (2026-03-04)
- Repository connected and deployed in Dokploy.
- Production DB exists and is populated with current local snapshot.
- Core schema migrations are up to date.
- Public branding in home metadata is aligned with `Kconecta`.

## Phase 1 - Stabilize Production (Now)
- Complete manual end-to-end login validation in browser.
- Validate critical routes and admin actions with real user flow.
- Add a simple release checklist per deploy (build, migrate, smoke test, rollback pointer).
- Ensure deploy automation refreshes runtime immediately after `main` updates.

## Phase 2 - Security Hardening
- Rotate production credentials and application secrets.
- Remove legacy plaintext-password fallback from authentication flow.
- Enforce password reset policy for default or imported accounts.

## Phase 3 - Data Governance
- Define source of truth for data seeding (`seeders` vs SQL snapshots).
- Prevent accidental local-to-production overwrite by requiring explicit approval path.
- Schedule automated backups and test restore procedure regularly.

## Phase 4 - Operational Reliability
- Add health checks and alerting for app + DB.
- Track migration drift between repo and production runtime.
- Document incident response and rollback steps in ops runbook.
