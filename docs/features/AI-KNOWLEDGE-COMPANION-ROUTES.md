# Routes required for AI Knowledge Companion

Ensure the following routes are registered (e.g. in `routes/web.php` or your main web route file).

## Admin Settings (T1)

- **GET** `/admin/settings` → `App\Http\Controllers\Admin\SettingsController@index`  
  Name: `admin.settings.index`  
  Middleware: `auth`, `role:super_admin`

- **PUT** `/admin/settings` → `App\Http\Controllers\Admin\SettingsController@update`  
  Name: `admin.settings.index` (redirect after update) or `admin.settings.update`  
  Middleware: `auth`, `role:super_admin`

Example (add inside your existing `Route::middleware(['auth', ...])` group):

```php
Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
});
```

## Knowledge documents (T2, T6)

- **GET** `/admin/knowledge-documents` → `KnowledgeDocumentController@index` — name: `admin.knowledge-documents.index`
- **GET** `/admin/knowledge-documents/create` → `KnowledgeDocumentController@create` — name: `admin.knowledge-documents.create`
- **POST** `/admin/knowledge-documents` → `KnowledgeDocumentController@store` — name: `admin.knowledge-documents.store`
- **GET** `/admin/knowledge-documents/import` → `KnowledgeDocumentController@importForm` — name: `admin.knowledge-documents.import` (T6: CSV import form)
- **POST** `/admin/knowledge-documents/import` → `KnowledgeDocumentController@import` — name: `admin.knowledge-documents.import.store` (T6: body: file, title, metadata; max 2MB CSV; rows → narrative; one doc with admin-provided metadata)
- **GET** `/admin/knowledge-documents/{knowledge_document}/edit` → `KnowledgeDocumentController@edit` — name: `admin.knowledge-documents.edit`
- **PUT** `/admin/knowledge-documents/{knowledge_document}` → `KnowledgeDocumentController@update` — name: `admin.knowledge-documents.update`
- **DELETE** `/admin/knowledge-documents/{knowledge_document}` → `KnowledgeDocumentController@destroy` — name: `admin.knowledge-documents.destroy`

Middleware: `auth`, `role:super_admin`

## Portal AI companion (T4, T5, T7)

- **GET** `/portal/ai-companion` → `AiCompanionController@index` — show chat page; passes `messages` (last N from DB). Redirects to dashboard if companion disabled or consultation not released.
- **POST** `/portal/ai-companion/chat` → `AiCompanionController@chat` — body: `{ "message": "..." }`; response: `{ "reply": "..." }`. Returns 403 if disabled or not released; 422 if message missing or > 2000 chars. Chat uses **retrieval by metadata** (T5) and **conversation persistence** (T7): user/assistant messages stored; last 20 loaded per request.
- **POST** `/portal/ai-companion/clear-history` → `AiCompanionController@clearHistory` — deletes all messages for the authenticated applicant; response: `{ "message": "History cleared." }`.

Middleware: `auth:applicant` (or your portal auth middleware).
