# AGENTS.md — MautomicCrmBundle

This is the entry point for all AI agent work on the MautomicCrmBundle plugin.
**Do NOT modify any files in the Mautic installation** — only files in this plugin directory.

## Repositories

| Repo | GitHub | Purpose |
|------|--------|---------|
| **mautic-crm-bundle** (this repo) | [mautomic-com/mautic-crm-bundle](https://github.com/mautomic-com/mautic-crm-bundle) | Plugin source code |
| mautic-crm-harness | [mautomic-com/mautic-crm-harness](https://github.com/mautomic-com/mautic-crm-harness) | Specs, backlog, harness scripts |

## Harness Location

The harness repo must be checked out as a sibling of the Mautic installation:

```
projects/mautic/
├── mautic-crm-harness/           # Harness repo
└── mautic-001/                   # Mautic 7 installation (DO NOT MODIFY)
    └── plugins/
        └── MautomicCrmBundle/    # THIS REPO (you are here)
```

Harness path from this directory: `../../../mautic-crm-harness/`

## Before Starting Any Work

1. Read the harness `AGENTS.md`: `../../../mautic-crm-harness/AGENTS.md`
2. Read the feature spec you're implementing: `../../../mautic-crm-harness/docs/exec-plans/backlog/`
3. Read known pitfalls in harness AGENTS.md "Known Patterns & Pitfalls" section

## Mandatory Workflow

**Every feature follows this workflow. No exceptions.**

### 1. Read feature spec → 2. Create branch → 3. Build → 4. Run checks → 5. Browser smoke test → 6. Push + PR → 7. CI green → 8. Report

Full details in `../../../mautic-crm-harness/AGENTS.md` under "Agent Workflow: Feature Implementation".

## Quick Commands (from Mautic root: `../../`)

```bash
# Harness scripts (recommended)
../mautic-crm-harness/harness/test-local.sh .       # Unit + Functional tests
../mautic-crm-harness/harness/lint-local.sh .        # PHPStan + CS
../mautic-crm-harness/harness/lint-local.sh . --fix  # Auto-fix CS

# Direct DDEV commands
ddev exec php bin/phpunit plugins/MautomicCrmBundle/Tests/Unit/ --testdox
ddev exec php bin/phpunit -c app/phpunit.xml.dist plugins/MautomicCrmBundle/Tests/Functional/ --testdox
ddev exec php bin/phpstan analyse plugins/MautomicCrmBundle/ --level 6
ddev exec bin/php-cs-fixer fix plugins/MautomicCrmBundle/ --dry-run --diff --config=.php-cs-fixer.php
```

## Browser Smoke Testing

Login: `https://mautic-001.ddev.site` — credentials: `admin` / `Maut1cR0cks!`

Use Cursor IDE browser MCP or browser-use subagent to verify UI flows.
Every feature spec lists specific browser smoke tests that MUST pass.

## Plugin Architecture

```
MautomicCrmBundle/
├── Config/config.php          # Routes, menu items, categories
├── Config/services.php        # Symfony DI (autowire, autoconfigure)
├── Controller/                # CRUD (extend AbstractStandardFormController)
│   └── Api/                   # REST API controllers
├── Entity/                    # Doctrine entities + repositories
├── Event/                     # Custom event objects
├── Form/Type/                 # Symfony form types
├── Model/                     # Business logic (extend FormModel)
├── Resources/views/           # Twig templates
├── Security/Permissions/      # Permission definitions
├── Tests/Unit/                # PHPUnit unit tests
├── Tests/Functional/          # PHPUnit functional tests (MauticMysqlTestCase)
├── Translations/en_US/        # Translation strings
└── .github/workflows/         # CI pipeline
```

## Key Entities

| Entity   | Table              | Service Alias                         |
|----------|--------------------|---------------------------------------|
| Pipeline | mautomic_pipelines | mautic.mautomic_crm.model.pipeline    |
| Stage    | mautomic_stages    | (child of Pipeline)                   |
| Deal     | mautomic_deals     | mautic.mautomic_crm.model.deal        |
| Task     | mautomic_tasks     | mautic.mautomic_crm.model.task        |
| Note     | mautomic_notes     | mautic.mautomic_crm.model.note        |

## Known Patterns & Pitfalls

**Read these before writing any code. These are hard-won lessons from Phase 1.**

| Pattern | What to do |
|---------|------------|
| Detail view buttons | Split `page_actions` into `preHeader` block (close) and `actions` block (edit/delete). Never put `close` with edit/delete in same include — `close` hides everything else. |
| `UserListType` on entities | Use `IdToEntityModelTransformer` + `multiple: false`. Raw UserListType returns array, entity setters expect User object. See LeadBundle's LeadType for canonical example. |
| Route naming | Routes: `mautic_mautomic_crm_{entity}_index/action`. routeBase: `mautomic_crm_{entity}`. |
| API controllers | Constructor injection only, not `initialize()`. Set model via `ModelFactory`. |
| API `entityNameMulti` | Must match `name` in config.php `api_routes` (e.g., `mautomic_pipelines` not `pipelines`). |
| Nullable int form fields | If form field can submit empty, setter should accept `?int` with `??= 0` fallback. |
| Pipeline/Stage on Deal | DB columns NOT NULL. Default in `DealModel::getEntity()`, enforce in `saveEntity()`. |
| Cache after entity changes | Clear test cache: `ddev exec rm -rf var/cache/test` |
| Functional test config | Use `-c app/phpunit.xml.dist` for KERNEL_CLASS env var. |
| Mautic 7 patterns | When in doubt, look at `app/bundles/LeadBundle/` or `app/bundles/EmailBundle/` for reference. |

## Coding Standards

- PHP 8.2+ with `declare(strict_types=1)`
- PSR-12 / Symfony style, 4-space indent
- Short array syntax `[]`, ordered imports
- Explicit types, minimal DocBlocks
- Translation key pattern: `mautomic_crm.{entity}.{context}`
- Table prefix: `mautomic_`
- Run PHPStan level 6 and CS Fixer before every PR
