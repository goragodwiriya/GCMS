# Reusable KB MCP setup for VS Code

This guide explains how to use a knowledge-base MCP consistently across multiple projects in VS Code.

## Goals

- Make the agent retrieve prior knowledge before starting meaningful work.
- Make the agent store reusable findings after completing meaningful work.
- Separate global reusable policy from project-specific instructions.
- Keep framework knowledge organized so it can be reused across repositories.

## Recommended structure

Use two layers together:

1. Global/reusable rule layer
   - Put a reusable KB policy in `.agent/rules/`.
   - This layer defines *when* knowledge tools must be used.

2. Project-specific instruction layer
   - Put project conventions in `.github/copilot-instructions.md` or split instruction files.
   - This layer defines *what knowledge matters* for this repository.

## What each layer should contain

### `.agent/rules/`

Use this for cross-project behavior such as:

- retrieve knowledge at the start of a task
- retrieve knowledge before architecture decisions
- store knowledge after important implementation work
- store framework patterns, debugging fixes, and reusable conventions

This should stay generic and portable.

### `.github/copilot-instructions*.md`

Use this for repository-specific details such as:

- framework used in the repo
- routing conventions
- folder layout
- build commands
- documentation sources
- what should be captured into the KB for that project

This should stay local to each repository.

## Recommended workflow

### For every new project

1. Copy the reusable rule file into `.agent/rules/`
2. Add or update `.github/copilot-instructions.md`
3. Add project-specific notes:
   - architecture
   - framework docs source
   - common patterns
   - naming conventions
4. Tell the agent which knowledge areas matter most in that repo

## Suggested knowledge taxonomy

To keep framework knowledge organized, store knowledge using predictable categories such as:

- `architecture`
- `routing`
- `controllers`
- `models`
- `views`
- `forms`
- `validation`
- `queries`
- `frontend-components`
- `css-patterns`
- `build-and-deploy`
- `debugging`
- `framework-quirks`

If your KB supports tags or collections, use one collection per framework and one per repository.

Example:

- Framework collection: `Kotchasan`
- Framework collection: `Now.js`
- Repo collection: `nowjs-gcms`

## Best practice for framework knowledge capture

When the agent discovers a reusable framework rule, save it in a framework-oriented format:

- Context: where this applies
- Pattern: the convention or API usage
- Example: minimal example path or symbol
- Caveat: common mistake or edge case

Good examples to store:

- router naming conventions
- controller/model/view relationships
- common query-builder patterns
- template rendering conventions
- frontend component initialization patterns
- CSS utility usage rules

## Placement recommendation in VS Code

### Best default

- Put reusable behavior in `.agent/rules/`
- Put repository specifics in `.github/copilot-instructions.md`

This is the best balance between reuse and local accuracy.

### If you want only one place

If your environment does not use `.agent/rules/`, you can move the reusable KB policy into a shared instruction file and copy it across projects. This works, but the reusable policy becomes harder to maintain separately.

## Portable setup pattern

Use this pattern in every repository:

- `.agent/rules/kb-mcp.rules.md` → reusable KB behavior
- `.github/copilot-instructions.md` → repo entry point
- optional split files under `.github/` for base + repo instructions

## Maintenance tips

- Keep the rules generic.
- Keep project instructions specific.
- Update the KB when framework behavior is confirmed from source.
- Prefer storing patterns, not one-off trivia.
- When docs and source disagree, store the source-validated behavior.

## Quick recommendation

If your goal is consistent KB usage across many projects, keep both:

- a reusable rule file for *behavior enforcement*
- a repo-specific instruction file for *knowledge scope*

That gives you repeatability without losing repository context.
