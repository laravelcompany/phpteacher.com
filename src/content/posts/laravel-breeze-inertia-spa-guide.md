---
title: "Building SPAs the Easy Way - Laravel Breeze and Inertia.js Guide"
description: "Learn how to build modern single-page applications without a dedicated API using Laravel Breeze and Inertia.js. Covers Vue.js integration, authentication scaffolding, and server-side rendering."
pubDate: "2022-10-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laravel", "Breeze", "Inertia.js", "SPA", "Vue.js", "React", "PHP"]
selected: false
---

Single-page applications deliver a smooth, app-like experience. Traditionally, building an SPA meant designing a REST or GraphQL API, handling token authentication, and maintaining separate client and server codebases. Laravel Breeze with Inertia.js eliminates nearly all of that overhead.

Inertia lets you build server-side rendered SPAs using JavaScript components instead of Blade templates. You get the client-side experience without building a dedicated API. It's not a framework — it's a client-side routing library that uses XHR requests to enable page visits without full page reloads.

## Getting Started with Breeze

Create a new Laravel application and install Breeze:

```bash
$ composer create-project laravel/laravel inertia
$ cd inertia/
$ composer require laravel/breeze --dev
$ php artisan migrate
$ php artisan breeze:install vue
$ npm run dev
```

That's it. Visit your app, click Register, and you get a fully functional registration form styled and ready to go.

The `breeze:install vue` command scaffolds Vue.js components for login, registration, password reset, email verification, and profile management. React is also supported:

```bash
$ php artisan breeze:install react
```

## How Inertia Works

Open `routes/web.php`. Instead of rendering Blade templates, your routes return Inertia page components:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';
```

The `Inertia::render()` call returns data to a Vue component. The `Dashboard` route maps to `resources/js/Pages/Dashboard.vue`. Inertia handles the rest — no API endpoints, no JSON serialization, no client-side routing configuration.

## Component Structure

Breeze ships with two layout files. Authenticated users get `AuthenticatedLayout.vue` with navigation and user menus. Unauthenticated users see `GuestLayout.vue` with minimal chrome:

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="...">Dashboard</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="...">
          You're logged in!
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
```

Using the correct layout matters. Put an admin dashboard behind `GuestLayout` and you might expose sensitive data. Use `AuthenticatedLayout` for public pages and authenticated users will see navigation elements they shouldn't.

## Building a Tasks Feature

Let's add a practical feature: user-owned tasks. Create the model, migration, and controller:

```bash
$ php artisan make:model --migration --resource --controller Task
```

Add the resource route in `web.php`:

```php
use App\Http\Controllers\TaskController;

Route::resource('tasks', TaskController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware(['auth', 'verified']);
```

The migration creates a tasks table linked to users:

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
          ->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->timestamps();
});
```

Define the model with a user relationship:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

Add the inverse relationship to `User`:

```php
public function tasks()
{
    return $this->hasMany(Task::class);
}
```

## Task Controller with Inertia

The store method validates and creates tasks, then redirects. Inertia handles the client-side update:

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $request->user()->tasks()->create($validated);

    return redirect(route('tasks.index'));
}
```

## Authorization with Policies

Create a policy so users can only edit their own tasks:

```bash
$ php artisan make:policy TaskPolicy --model=Task
```

```php
public function update(User $user, Task $task)
{
    return $task->user()->is($user);
}

public function delete(User $user, Task $task)
{
    return $this->update($user, $task);
}
```

The update and destroy methods in the controller authorize against this policy:

```php
public function update(Request $request, Task $task)
{
    $this->authorize('update', $task);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);
    $task->update($validated);
    return redirect(route('tasks.index'));
}

public function destroy(Task $task)
{
    $this->authorize('delete', $task);
    $task->delete();
    return redirect(route('tasks.index'));
}
```

## Building the Vue Components

The `resources/js/Pages/Tasks/Index.vue` page handles listing and creating tasks:

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { useForm } from '@inertiajs/inertia-vue3';
import Task from '@/Components/Task.vue';

defineProps(['tasks']);

const form = useForm({
    name: '',
});
</script>

<template>
  <Head title="Tasks" />

  <AuthenticatedLayout>
    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
      <form @submit.prevent="form.post(route('tasks.store'), { onSuccess: () => form.reset() })">
        <input v-model="form.name" type="text" placeholder="New task" class="...">
        <button type="submit" class="...">Add</button>
      </form>

      <div class="mt-6 ... divide-y">
        <Task
          v-for="task in tasks"
          :key="task.id"
          :task="task"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
```

The `Task.vue` component renders each item with edit and delete controls, gated by ownership:

```vue
<script setup>
import { useForm } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';

const props = defineProps(['task']);

const form = useForm({
    name: props.task.name,
});

const editing = ref(false);
</script>

<template>
  <div class="p-6 flex space-x-2">
    <div class="flex-1">
      <Dropdown v-if="task.user.id === $page.props.auth.user.id">
        <template #trigger>
          <button>...</button>
        </template>
      </Dropdown>

      <form v-if="editing"
            @submit.prevent="form.put(route('tasks.update', task.id),
              { onSuccess: editing = false })">
        <input v-model="form.name" class="...">
      </form>
      <p v-else class="...">{{ task.name }}</p>
    </div>
  </div>
</template>
```

The `v-if="task.user.id === $page.props.auth.user.id"` check ensures users only see edit/delete controls for their own tasks. Create a second user and you'll see the difference immediately.

## Why This Matters

Throughout this entire feature, we never built a single API endpoint. No REST routes. No GraphQL schema. No token handling. Inertia handles data flow between the server and Vue components transparently.

When you delete a task, Inertia processes the redirect, fetches the updated list from the `tasks.index` route, and swaps the DOM — all without a full page reload. The result is an SPA experience built with server-side Laravel patterns you already know.

If you're not ready to dive into Vue.js, `php artisan breeze:install` accepts `blade` as an option for traditional server-rendered templates. But after seeing how little JavaScript is required to get the SPA experience, you might find yourself giving Vue or React a serious look.
