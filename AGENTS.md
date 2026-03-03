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

Login: `https://<your-ddev-site>` — use your local Mautic admin credentials

Use Cursor IDE browser MCP or browser-use subagent to verify UI flows.
Every feature spec lists specific browser smoke tests that MUST pass.

### Browser Testing Recipes (follow these exactly to avoid retries)

**1. After EVERY navigation, hide the Symfony debug toolbar — it blocks clicks:**
```
browser_navigate → url: javascript:void(document.querySelector('.sf-toolbar')&&(document.querySelector('.sf-toolbar').style.display='none'))
```

**2. Navigate directly to URLs instead of clicking through menus:**
```
Pipelines list:  https://<your-ddev-site>/s/mautomic/pipelines
Pipeline detail: https://<your-ddev-site>/s/mautomic/pipelines/view/{id}
Pipeline edit:   https://<your-ddev-site>/s/mautomic/pipelines/edit/{id}
Deals list:      https://<your-ddev-site>/s/mautomic/deals
Deal new:        https://<your-ddev-site>/s/mautomic/deals/new
Deal detail:     https://<your-ddev-site>/s/mautomic/deals/view/{id}
Deal edit:       https://<your-ddev-site>/s/mautomic/deals/edit/{id}
Tasks list:      https://<your-ddev-site>/s/mautomic/tasks
Task new:        https://<your-ddev-site>/s/mautomic/tasks/new
Task detail:     https://<your-ddev-site>/s/mautomic/tasks/view/{id}
```

**3. Submit Mautic forms via JS (Save & Close dropdown is unreliable via accessibility refs):**
```
browser_navigate → url: javascript:void(document.querySelector('form')?.submit())
```

**4. Use compact interactive snapshots to reduce noise:**
```
browser_snapshot → interactive: true, compact: true
```

**5. To verify text content exists on a page**, take a screenshot (visual check) or grep the snapshot file:
```
browser_take_screenshot
```

**6. For dropdown menus (Options → Delete)**, click the dropdown button, then immediately take a fresh snapshot to get new refs. Dropdown item refs go stale quickly.

**7. Resize browser to avoid overlap issues:**
```
browser_resize → width: 1280, height: 1024
```

**8. Recommended flow for each smoke test:**
```
browser_navigate (to URL)
browser_navigate (hide toolbar JS)
browser_snapshot (interactive, compact)
browser_fill / browser_click (interact)
browser_navigate (form submit JS) — if saving a form
browser_take_screenshot — verify result
```

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
| PHPStan needs cache | Build first: `ddev exec bash -c 'APP_ENV=test APP_DEBUG=1 php bin/console > /dev/null 2>&1'` |
| Mautic 7 patterns | When in doubt, look at `app/bundles/LeadBundle/` or `app/bundles/EmailBundle/` for reference. |

## Coding Standards

- PHP 8.2+ with `declare(strict_types=1)`
- PSR-12 / Symfony style, 4-space indent
- Short array syntax `[]`, ordered imports
- Explicit types, minimal DocBlocks
- Translation key pattern: `mautomic_crm.{entity}.{context}`
- Table prefix: `mautomic_`
- Run PHPStan level 6 and CS Fixer before every PR
