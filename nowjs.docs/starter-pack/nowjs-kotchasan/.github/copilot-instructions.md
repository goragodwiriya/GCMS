# Copilot instructions index for <project-name>

Use these instruction files together:

1. `.github/copilot-instructions.base-nowjs-kotchasan.md`
   - Reusable base guidance for projects built on Now.js + Kotchasan.
   - Covers docs-first workflow, source validation, knowledge capture, framework conventions, and reusable CSS/frontend/backend guidance.

2. `.github/copilot-instructions.repo-<project-name>.md`
   - Repository-specific guidance for this project.
   - Covers local architecture, build entry points, routing conventions, data/config locations, documentation sources, integrations, and styling guidance specific to the repo.

Working rule:
- Apply the base instructions first.
- Then apply the repo-specific instructions for details that are unique to the project.
- If a rule conflicts, the repo-specific file wins for that repository.
