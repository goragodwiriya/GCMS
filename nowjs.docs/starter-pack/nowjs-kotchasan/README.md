# Now.js + Kotchasan starter pack

Starter pack for new projects built on the Now.js frontend framework and the Kotchasan PHP framework.

## Included files

- `.github/copilot-instructions.md`
  - Short index file that points to the instruction layers.
- `.github/copilot-instructions.base-nowjs-kotchasan.md`
  - Reusable base guidance for all Now.js + Kotchasan projects.
- `.github/copilot-instructions.repo-template.md`
  - Template for the repo-specific instruction file of a new project.

## Recommended setup for a new project

1. Copy the `.github/` folder from this starter pack into the new project.
2. Rename:
   - `.github/copilot-instructions.repo-template.md`
   - to `.github/copilot-instructions.repo-<project-name>.md`
3. Edit:
   - `.github/copilot-instructions.md`
   - replace `<project-name>` with the real project name
4. Edit the new repo-specific file and fill in:
   - architecture
   - build workflow
   - routing conventions
   - data/config locations
   - documentation sources
   - integrations
   - local recurring patterns
5. Import project docs and framework docs into the knowledge base.
6. Save project memory for:
   - docs-first workflow
   - docs preference
   - framework docs source
   - reusable patterns discovered during implementation

## Standard working rule

- Apply the base instructions first.
- Then apply the repo-specific instructions.
- If a rule conflicts, the repo-specific file wins for that repository.

## Recommended additions when starting a real project

- Import frontend docs (`docs/en`, `docs/th` if available)
- Import backend/framework docs (Kotchasan docs)
- Save source-of-truth paths in project memory
- Save recurring module/widget/layout patterns as knowledge as soon as they are discovered

## Suggested copy targets

```text
.github/copilot-instructions.md
.github/copilot-instructions.base-nowjs-kotchasan.md
.github/copilot-instructions.repo-<project-name>.md
```

## Notes

- Keep the index file short.
- Keep base guidance reusable.
- Keep project-specific details out of the base file.
- Promote rules into the base file only when they are truly reusable across multiple projects.
