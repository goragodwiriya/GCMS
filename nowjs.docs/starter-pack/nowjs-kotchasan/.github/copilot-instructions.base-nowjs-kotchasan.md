# Base Copilot instructions for Now.js + Kotchasan projects

## Stack assumptions
- These instructions are intended for projects built on the Now.js frontend framework and the Kotchasan PHP framework.
- Treat this file as reusable base guidance across projects on this stack.
- Keep repo-specific paths, modules, themes, integrations, and business rules in the local repo instruction file.

## Documentation-first workflow
- Read available documentation and stored knowledge sources first before making assumptions.
- Validate important behavior against the real source code before concluding.
- If documentation and source code differ, trust the source code.
- When a mismatch is found, update the documentation and the knowledge base/project memory so future work stays aligned.

## Knowledge capture
- Save reusable implementation knowledge whenever discovered.
- Important examples include module structures, routing conventions, controller/model/view flows, form-save patterns, widget patterns, CSS/layout patterns, data/category patterns, and any recurring implementation approach that should become a shared standard across future projects on this stack.

## Kotchasan guidance
- Prefer Kotchasan documentation as the primary conceptual reference for backend/framework behavior, then confirm against the actual source code when behavior matters.
- Follow Kotchasan MVC conventions consistently: controller, model, view, routing, request/response, validation, and database/query builder usage should align with framework patterns.
- When implementing backend features, prefer existing Kotchasan abstractions and helpers before introducing custom infrastructure.

## Now.js frontend guidance
- Prefer imported technical docs in English first for framework semantics and API behavior; use localized docs as supplementary context.
- Verify significant frontend behavior against the real source code in `Now/` when needed.
- Prefer existing Now.js managers, `data-*` conventions, and component patterns before introducing custom JavaScript patterns.

## Now.js CSS framework guidance
- Prefer the built-in Now.js CSS framework before creating new custom CSS.
- Use the framework CSS entry point and existing framework layers instead of bypassing them with ad hoc styles.
- Treat `variables.css` as the design-token source of truth for colors, spacing, typography, radii, shadows, z-index, and component variables.
- Prefer variable-based theming over hard-coded colors.
- Use existing layout utilities, shared utility classes, and component styles before adding new CSS.
- Prefer existing `icon-*` classes from the framework icon set instead of introducing new icon libraries, unless a project explicitly requires it.
- Keep generic reusable utilities in framework-level CSS. Keep project/theme presentation in theme-specific CSS.

## Editing guidance
- Prefer existing framework conventions over inventing parallel abstractions.
- When creating new pages or modules, start from established patterns already present in the stack.
- If you discover a recurring pattern that should be standardized, capture it in project knowledge.
