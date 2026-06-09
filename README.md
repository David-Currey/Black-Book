# Dossier

Dossier is a private list and entry tracker for keeping lightweight records about people, accounts, contacts, prospects, vendors, characters, or anything else that benefits from searchable notes and follow-ups.

It started as a simple list app, but now has enough structure to work as a small personal CRM, research tracker, relationship log, or collection database.

## Features

- Create, edit, and delete lists.
- Add entries to each list with a name, category, notes, tags, and custom fields.
- Search globally across lists, entries, tags, custom field values, notes, and reminders.
- Define custom fields per list and fill them in per entry.
- Add reminders and mark follow-ups complete.
- Import and export lists as JSON.
- Import and export lists as CSV.
- Share lists with other Dossier users as viewers or editors.
- Use a responsive, dark, app-focused interface.

## Sharing

List owners can share a list with any existing Dossier user by email.

Roles:

- Viewer: can open the shared list, view entries, search shared content, and export the list.
- Editor: can also update list content, entries, notes, custom fields, tags, and reminders.

Only the list owner can delete the list or manage sharing.

## Import And Export

Dossier supports JSON for full-fidelity backup and transfer, and CSV for spreadsheet-friendly workflows.

JSON exports include:

- List name and description
- Entries
- Tags
- Open reminders
- Custom field definitions and values

CSV exports include:

- `name`
- `category`
- `notes`
- `tags`
- Any custom fields as `custom:<Field Name>` columns

CSV imports create a new list from the uploaded file. Tags can be separated with semicolons in the `tags` column.

## Tech Stack

- Laravel 13
- Livewire 4
- Flux UI
- Tailwind CSS
- Vite
- Pest

## Local Setup

Install PHP and Node dependencies, create the environment file, generate the app key, run migrations, and build assets:

```bash
composer run setup
```

Run the full local development stack:

```bash
composer run dev
```

Run tests:

```bash
composer test
```

Build frontend assets:

```bash
npm run build
```

## Useful Routes

- `/dashboard` - overview and upcoming reminders
- `/search` - global search
- `/lists` - owned and shared lists
- `/lists/{list}` - list details, entries, custom fields, sharing, import/export
- `/people/{person}` - entry details, reminders, tags, custom fields
