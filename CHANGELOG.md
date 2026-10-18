# Changelog

All notable changes to `alfasix/alfakit-kanban` are documented in this file.

## Unreleased

- Initial release: `KanbanPage` for resources and `KanbanWidget`, columns from a backed enum, a `BelongsTo` relationship or a closure (`Column` with colour, icon, description, WIP limits that warn or block, collapsible columns and empty states), cards with title, description, badges, assignees, due dates, progress, cover images and infolist entries (`getCard()`, `getCardSchema()`), a card click action and menu, quick create per column, swimlanes, search, table filters and polling. Moves are optimistic and saved in one request (status and order), authorised by the `update` policy and validated against columns, `getTransitions()` / `canMoveTo()`, strict WIP limits, lanes and the tenant, with `beforeCardMoved()` / `afterCardMoved()` hooks and a `CardMoved` event. Rendered by the `kanban` component in `@alfakit/react`, `@alfakit/vue` and `@alfakit/svelte` with pointer (mouse, pen, touch) and keyboard dragging, live-region announcements and right-to-left support.
