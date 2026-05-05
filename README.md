# 📝 List App

A modern, flexible web app for creating, managing, and sharing lists.  
Built to be simple at its core, but powerful enough to support real-world use cases like collaboration, tracking, and personalization.

---

## 🚀 Features

- ✅ Create, edit, and delete lists
- 🗂️ Organize items within lists
- 🔄 Import and export lists as JSON
- 🔗 Share lists with others
- ✏️ Inline editing for fast updates
- ⚡ Responsive and clean UI
- 💾 Persistent storage (database-backed)

---

## 📦 Import / Export

You can easily move your data between devices or share it with others.

### Export
- Export any list as a `.json` file
- Confirmation prompt before exporting

### Import
- Upload a `.json` file to restore or add lists

### Example Format

```json
{
  "name": "Sample List",
  "description": "Example imported list",
  "items": [
    { "title": "Item 1", "completed": false },
    { "title": "Item 2", "completed": true }
  ]
}
