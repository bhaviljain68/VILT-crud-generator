# VILT CRUD Generator for Laravel

> <br>**Generate beautiful, robust, and fully customizable CRUD scaffolding for your VueJS + Inertia + Laravel + Tailwind (VILT) stack in seconds.**
<br>

---

## ✨ Features

- **One-Command CRUD:** Instantly generate Model, Controller, FormRequest, Resource, and Inertia Vue pages (TypeScript or JavaScript) for any table/model.
- **Editable Stubs:** All PHP and Vue stubs are fully publishable and editable. Tweak the generated code and design to fit your project's style and requirements.
- **TypeScript or JavaScript:** Choose between TypeScript or plain JavaScript for your Vue pages.
- **FormRequest & Validation:** Generate FormRequest classes for robust validation, or keep validation inline in controllers.
- **Resource & Collection:** Optionally generate API Resource and ResourceCollection classes for clean API responses.
- **Smart Schema Introspection:** Reads your database schema to auto-generate fields, validation, and Vue form components.
- **Configurable & Extensible:** Publish config and stubs, map DB types to custom Vue components, and control every aspect of the generated code.
- **Modern UI:** Vue pages use a clean, modern layout and leverage your own UI components.
- **Safe by Default:** System and sensitive columns (timestamps, soft deletes, passwords, tokens, etc.) are excluded from forms and fillables.
- **Multi-Model Support:** Generate scaffolding for multiple models/tables in a single command.
- **Out-of-the-box for Laravel Vue Starter Kit:** Designed to work seamlessly with the [Laravel Vue Starter Kit](https://laravel.com/docs/12.x/starter-kits#vue), using its default layouts and UI components.
- **Flash Message Support:** All generated pages display success/error flash messages using Inertia's shared props.
- **TypeScript Types for Inertia:** Publishes a `resources/js/types/inertia.d.ts` file for improved TypeScript support.
- **Advanced DB Type Mapping:** The config supports mapping a wide range of DB column types (MySQL, PostgreSQL, SQLite) to custom Vue components.
- **Automatic Route Insertion:** Resource routes are automatically inserted into your routes file, inside a protected block.
- **Support for Separate or Single FormRequest Files:** Can generate either separate Store/Update FormRequest files or a single file, based on config or CLI options (single file by default).
- **Improved Error Handling:** Controller stubs include error catching and flash error messages for create, update, and delete actions.
- **Consistent Use of Custom UI Components:** All generated pages use custom BackButton, NumberInput, DateInput, and Button components.
- **Modern, Maintainable Vue Pages:** All Vue pages use `<script setup>` and a clean, modern layout.
- **Interactive & Non-Interactive CLI:** The generator command can be run interactively (with prompts for model/table, options, etc.) or non-interactively by passing arguments and flags directly.

---

## 🚀 Quick Start

1. **Install via Composer:**

```sh
composer require artisanalbyte/vilt-crud-generator --dev
```

2. **Publish Config, Stubs, and UI Components:**

```sh
php artisan vilt-crud:publish --all
```

3. **Generate CRUD for a Model (Interactive):**

```sh
php artisan vilt-crud:generate
```

This will launch an interactive prompt to select the model/table and options.

4. **Generate CRUD for a Model (Non-Interactive):**

```sh
php artisan vilt-crud:generate Post --form-request --resource-collection
```

5. **Generate for Multiple Models:**

```sh
php artisan vilt-crud:generate User Project Comment
```

6. **Options:**
- `--form-request` : Generate FormRequest classes for validation
- `--resource-collection` : Generate Resource & Collection classes
- `--no-ts` : Generate Vue pages in JavaScript instead of TypeScript
- `--force` : Overwrite existing files

---

## 📦 Publishable Assets & File Tree

You can publish only what you need, or everything at once:

| Command                                 | What it Publishes                |
|------------------------------------------|----------------------------------|
| `php artisan vilt-crud:publish --all`    | Config, stubs, UI components, TS |
| `php artisan vilt-crud:publish`          | Config, UI components, TS        |
| `php artisan vilt-crud:publish --stubs`  | Stubs only                       |

**Published File Tree Example:**

```
resources/
  js/
    components/
      ui/
        input/
          NumberInput.vue
          DateInput.vue
        button/
          BackButton.vue
    types/
      inertia.d.ts
  stubs/
    vilt-crud-generator/
      controller.stub
      form-request.stub
      model.stub
      resource.stub
      resource-collection.stub
      pages-no-ts/
        create.vue.stub
        edit.vue.stub
        index.vue.stub
        show.vue.stub
      pages-ts/
        create.vue.stub
        edit.vue.stub
        index.vue.stub
        show.vue.stub
config/
  vilt-crud-generator.php
```

---

## 🛠️ Customization & Flexibility

### 1. **Editable Stubs**
All generated code is based on stub templates. You can publish and edit these stubs:

```sh
php artisan vilt-crud:publish --stubs
```

Edit the published stubs in `resources/stubs/vilt-crud-generator/` to:
- Change controller logic
- Adjust model fillables, casts, or relationships
- Redesign Vue pages (layout, fields, UI components)
- Add or remove validation rules

**Every time you run the generator, your custom stubs are used!**

### 2. **Configurable UI & Field Mapping**
- Map DB column types to your own Vue components in `config/vilt-crud-generator.php`.
- Exclude system columns, sensitive columns, tweak default behaviors, and more.

#### **System Columns**
- Defined in `systemColumns` (e.g. `created_at`, `updated_at`, `deleted_at`).
- Excluded from forms and fillables by default.

#### **Sensitive Columns**
- Defined in `sensitiveColumns` (e.g. `password`, `token`, `api_key`, `secret`, etc.).
- Excluded from forms to protect sensitive data.

### 3. **Modern, Maintainable Code**
- Clean, PSR-4 autoloaded PHP code
- Vue pages use `<script setup>` and your preferred UI kit
- All generated code is readable, idiomatic, and ready for production

### 4. **Default UI Components**
This package provides three custom Vue components out of the box:
- `BackButton.vue` (navigation)
- `NumberInput.vue` (numeric input)
- `DateInput.vue` (date input)

All other layouts and components used are the defaults from the Laravel Vue Starter Kit.

---

## 📚 Example: Generating a CRUD for `Post`

```sh
php artisan vilt-crud:generate Post --form-request --resource-collection
```

This will generate:
- `app/Models/Post.php`
- `app/Http/Controllers/PostController.php`
- `app/Http/Requests/PostStoreRequest.php`, `PostUpdateRequest.php`
- `app/Http/Resources/PostResource.php`, `PostCollection.php`
- `resources/js/Pages/Posts/Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue`

All files are based on your published stubs and config.

---

## 🧩 How It Works

- **Schema Introspection:** Reads your DB schema to generate fields, validation, and UI components.
- **Stub Rendering:** All code is generated from stubs, which you can edit and re-use.
- **Smart Defaults:** Sensible defaults for fillable, hidden, casts, and relationships.
- **Safe Overwrites:** Use `--force` to overwrite existing files.

---

## 🤖 Disclaimer

This package was made with the assistance of AI, but is not "Vibe Coded" by any means. All code is reviewed, tested, and maintained by a human developer.

---

## 💡 Personal Note & Vision
> <br/>"I built this package for myself because I found writing CRUD boilerplate to be boring and not the best use of my time. Using AI to generate boilerplate required more than a few steps, and the results were often inconsistent with my project's style. With this package, I can get the boilerplate out of the way instantly and get straight to building the parts of my application that matter. My vision is to empower developers to focus on creativity and problem-solving, not repetitive code. I hope this tool saves you as much time and tedium as it has saved me. Happy building!"<br/><br/>
>

---

## 📄 License

MIT © 2025 artisanalbyte
