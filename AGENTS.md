# AGENTS.md — MautomicCrmBundle Plugin

This is the Mautic CRM plugin. For full development guidance, read the harness repo.

## Quick Reference

- **Harness repo**: `../../../mautic-crm-harness/` (sibling of the Mautic installation)
- **Architecture**: See harness `ARCHITECTURE.md`
- **Coding standards**: See harness `docs/CODING_STANDARDS.md`
- **Testing patterns**: See harness `docs/references/mautic-testing-patterns.md`
- **Entity patterns**: See harness `docs/references/mautic-entity-patterns.md`

## Directory Layout

```
projects/mautic/
├── mautic-001/                    # Mautic 7 installation (this workspace)
│   └── plugins/MautomicCrmBundle/ # This plugin (you are here)
└── mautic-crm-harness/           # Development harness (sibling of mautic-001)
```

## Before Making Changes

1. Read the relevant product spec in harness `docs/product-specs/`
2. Check for active execution plans in harness `docs/exec-plans/active/`
3. Follow Mautic 7 patterns exactly (see harness `docs/design-docs/core-beliefs.md`)

## After Making Changes

```bash
# Run tests locally via DDEV (from the Mautic root)
ddev exec php bin/phpunit plugins/MautomicCrmBundle/Tests/Unit/ --testdox
ddev exec php bin/phpunit -c app/phpunit.xml.dist plugins/MautomicCrmBundle/Tests/Functional/ --testdox

# Or use the harness script
../../../mautic-crm-harness/harness/test-local.sh /path/to/mautic-001
```

## Plugin Namespace

`MauticPlugin\MautomicCrmBundle`

## Key Service Aliases

- `mautic.mautomic_crm.model.pipeline` → `PipelineModel`
- `mautic.mautomic_crm.model.deal` → `DealModel`
- `mautic.mautomic_crm.model.task` → `TaskModel`
- `mautic.mautomic_crm.model.note` → `NoteModel`
