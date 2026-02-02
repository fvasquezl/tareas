# Prompt de Proyecto: Sistema de Gestión de Tareas Laravel 12

## Descripción General del Proyecto

Crea una aplicación web completa de gestión de tareas usando **Laravel 12** con las siguientes características principales:

- Sistema de autenticación de usuarios
- Panel administrativo moderno con **Filament v5**
- CRUD completo de tareas con relaciones
- Sistema de prioridades usando Enums
- Dashboard con estadísticas en tiempo real
- Base de datos SQLite para desarrollo
- Frontend con Tailwind CSS v4
- Entorno de desarrollo Dockerizado con Laravel Sail
- Testing completo con Pest PHP

---

## Stack Tecnológico

### Backend
- **PHP**: 8.2+
- **Laravel Framework**: v12
- **Laravel Sail**: v1 (Docker)
- **Filament**: v5 (Panel Admin)
- **Laravel Tinker**: v2 (REPL/Debugging)

### Frontend
- **Tailwind CSS**: v4
- **Vite**: v7 (Build tool)
- **Axios**: v1 (HTTP client)

### Base de Datos
- **SQLite**: File-based database
- **Ubicación**: `database/database.sqlite`
- **Testing**: `database/testing.sqlite`

### Testing
- **PHPUnit**: v11
- **Pest PHP**: v3
- **Faker**: v1

---

## Arquitectura de la Aplicación

### Modelos de Datos

#### 1. Modelo User
```php
Propiedades:
- id: bigint (PK)
- name: string
- email: string (unique)
- email_verified_at: timestamp (nullable)
- password: string (hashed)
- remember_token: string (nullable)
- created_at, updated_at: timestamps

Relaciones:
- hasMany(Task) → Un usuario puede tener muchas tareas

Fillable: ['name', 'email', 'password']
Hidden: ['password', 'remember_token']

Casts:
- email_verified_at → datetime
- password → hashed
```

#### 2. Modelo Task
```php
Propiedades:
- id: bigint (PK)
- title: string (requerido, max 255)
- description: text (nullable)
- due_date: date (nullable)
- priority: string ('low', 'medium', 'high') → Cast a Enum
- completed: boolean (default: false)
- user_id: bigint (FK → users.id, cascadeOnDelete)
- created_at, updated_at: timestamps

Relaciones:
- belongsTo(User) → Cada tarea pertenece a un usuario

Fillable: ['title', 'description', 'due_date', 'priority', 'completed', 'user_id']

Casts:
- due_date → date
- completed → boolean
- priority → TaskPriority::class (enum)

Índices de Base de Datos:
- Composite index: (completed, due_date) → Para consultas de tareas pendientes/completadas
- Simple index: priority → Para filtrar por prioridad
```

### Enumeraciones

#### TaskPriority Enum
```php
Tipo: Backed Enum (string)

Casos:
- Low → 'low' → Color: 'success' (verde) → Label: 'Baja'
- Medium → 'medium' → Color: 'warning' (amarillo) → Label: 'Media'
- High → 'high' → Color: 'danger' (rojo) → Label: 'Alta'

Métodos:
- getLabel(): string → Retorna etiqueta en español
- getColor(): string → Retorna color de Filament
```

---

## Estructura del Panel Administrativo Filament

### Configuración del Panel
```php
ID: 'admin'
Path: '/admin'
Login: Habilitado (autenticación requerida)
Color primario: Amber
Discovery automático: Resources, Pages, Widgets

Widgets del Dashboard:
- TasksOverview (estadísticas personalizadas)
- AccountWidget (widget de cuenta de Filament)
- FilamentInfoWidget (información del sistema)
```

### Resource: TaskResource

#### Estructura de Navegación
```
Icon: heroicon-o-clipboard-document-list
Label: "Tareas"
Model Label: "tarea" / "tareas"

Páginas:
- /admin/tasks → ListTasks (lista con creación inline)
- /admin/tasks/create → CreateTask
- /admin/tasks/{id}/edit → EditTask (con eliminación)
```

#### Formulario (TaskForm Schema)

Campos del formulario:
1. **TextInput - title**
   - Label: "Título"
   - Requerido: Sí
   - Max length: 255 caracteres
   - Validación: required|string|max:255

2. **Textarea - description**
   - Label: "Descripción"
   - Filas: 3
   - Ancho: Completo
   - Nullable: Sí

3. **DatePicker - due_date**
   - Label: "Fecha límite"
   - Tipo: Native
   - Nullable: Sí
   - Formato: d/m/Y

4. **Select - priority**
   - Label: "Prioridad"
   - Opciones: TaskPriority enum (Baja/Media/Alta)
   - Default: Medium
   - Requerido: Sí

5. **Toggle - completed**
   - Label: "Completada"
   - Default: false
   - Tipo: Boolean

6. **Select - user_id**
   - Label: "Usuario"
   - Relación: user
   - Display: name
   - Searchable: Sí
   - Preload: Sí
   - Default: Usuario autenticado actual
   - Requerido: Sí

#### Tabla (TasksTable Configuration)

**Columnas:**
1. title
   - Searchable: Sí
   - Sortable: Sí
   - Label: "Título"

2. user.name
   - Searchable: Sí
   - Sortable: Sí
   - Label: "Usuario"
   - Tipo: Relación

3. priority
   - Display: Badge con color
   - Formato: Enum label
   - Label: "Prioridad"

4. completed
   - Display: IconColumn (checkmark/x)
   - Tipo: Boolean
   - Label: "Completada"

5. due_date
   - Formato: d/m/Y
   - Sortable: Sí
   - Label: "Fecha límite"

6. created_at
   - Formato: DateTime
   - Toggleable: Sí (oculto por defecto)
   - Label: "Fecha de creación"

**Filtros:**
1. **TernaryFilter - completed**
   - Opciones: Todas / Completadas / Pendientes
   - Placeholder: "Todas las tareas"

2. **SelectFilter - priority**
   - Opciones: Baja / Media / Alta (desde enum)
   - Placeholder: "Todas las prioridades"

3. **Filter - due_date** (Rango de fechas)
   - Campos:
     - from: DatePicker
     - until: DatePicker
   - Query: whereBetween / whereDate

4. **SelectFilter - user**
   - Relación: user
   - Display: name
   - Searchable: Sí
   - Preload: Sí

**Acciones de Registro:**
- EditAction: Editar tarea individual

**Acciones Masivas (Bulk Actions):**
1. **Marcar como completadas**
   - Actualiza completed = true
   - Color: Success
   - Requiere confirmación
   - Icono: heroicon-o-check

2. **Marcar como pendientes**
   - Actualiza completed = false
   - Color: Warning
   - Requiere confirmación
   - Icono: heroicon-o-x-mark

3. **DeleteBulkAction**
   - Eliminación masiva estándar
   - Requiere confirmación

**Ordenamiento por defecto:**
- Campo: due_date
- Dirección: Ascendente (tareas más urgentes primero)

#### Widget: TasksOverview

Widget de estadísticas para el dashboard:

**Estadísticas mostradas:**

1. **Total de tareas**
   - Descripción: "Total de tareas"
   - Valor: Conteo total de registros
   - Color: Primary
   - Icono: heroicon-o-clipboard-document-list

2. **Tareas completadas**
   - Descripción: "Tareas completadas"
   - Valor: Conteo + porcentaje del total
   - Color: Success
   - Icono: heroicon-o-check-circle

3. **Tareas pendientes**
   - Descripción: "Tareas pendientes"
   - Valor: Conteo
   - Extra: Número de tareas vencidas (si hay)
   - Color: Warning si hay vencidas, Info si no
   - Icono: heroicon-o-clock

4. **Alta prioridad**
   - Descripción: "Alta prioridad sin completar"
   - Valor: Conteo de tareas High priority + incompletas
   - Color: Danger
   - Icono: heroicon-o-exclamation-triangle

---

## Migraciones de Base de Datos

### Migración: create_users_table
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

// También crea password_reset_tokens y sessions
```

### Migración: create_tasks_table
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->date('due_date')->nullable();
    $table->string('priority')->default('medium');
    $table->boolean('completed')->default(false);
    $table->foreignId('user_id')
          ->constrained()
          ->cascadeOnDelete();
    $table->timestamps();

    // Índices
    $table->index(['completed', 'due_date']); // Composite
    $table->index('priority'); // Simple
});
```

### Otras migraciones estándar
- create_cache_table
- create_jobs_table

---

## Factories (Generación de Datos de Prueba)

### UserFactory
```php
Estado por defecto:
- name: Faker::name()
- email: Faker::unique()->safeEmail()
- email_verified_at: now()
- password: Hash::make('password')
- remember_token: Str::random(10)

Estados personalizados:
- unverified(): email_verified_at = null
```

### TaskFactory
```php
Estado por defecto:
- title: Faker::sentence(3)
- description: Faker::paragraph()
- due_date: Fecha aleatoria entre hoy y +30 días
- priority: TaskPriority caso aleatorio
- completed: Boolean (30% probabilidad true)
- user_id: User::factory()

Estados personalizados:
- completed(): completed = true
- pending(): pending = false
- highPriority(): priority = TaskPriority::High
```

---

## Seeder de Base de Datos

### DatabaseSeeder
```php
Crea los siguientes datos:

1. Usuario de prueba:
   - Name: "Test User"
   - Email: "test@example.com"
   - Password: "password" (hasheado)

2. Tareas asociadas al usuario:
   - 10 tareas aleatorias (mix de estados)
   - 5 tareas completadas
   - 3 tareas de alta prioridad

Ejecutar: vendor/bin/sail artisan db:seed
```

---

## Sistema de Testing

### Configuración de PHPUnit
```xml
Test Suites:
- Unit: tests/Unit
- Feature: tests/Feature

Entorno de Testing:
- APP_ENV: testing
- DB_CONNECTION: sqlite
- DB_DATABASE: database/testing.sqlite
- CACHE_STORE: array
- SESSION_DRIVER: array
- QUEUE_CONNECTION: sync
- MAIL_MAILER: array
```

### Estructura de Tests

#### Feature Tests (tests/Feature/TaskTest.php)
```php
Usando sintaxis Pest:

test('puede crear una tarea', function() {
    // Crea tarea y verifica existencia en DB
});

test('tarea pertenece a un usuario', function() {
    // Verifica relación belongsTo
});

test('usuario tiene muchas tareas', function() {
    // Verifica relación hasMany
});

test('prioridad se castea a enum', function() {
    // Verifica casting automático
});
```

#### Comandos de Testing
```bash
# Todos los tests
vendor/bin/sail artisan test --compact

# Test específico
vendor/bin/sail artisan test --compact tests/Feature/TaskTest.php

# Filtrar por nombre
vendor/bin/sail artisan test --compact --filter=testName
```

---

## Frontend y Assets

### Vista Principal: welcome.blade.php
```php
Estructura:
- Página de bienvenida con logo de Laravel
- Navegación condicional:
  - Si autenticado: Link a Dashboard
  - Si no: Links a Login y Register
- Estilos: Tailwind CSS inline
- Responsive design (mobile-first)
- Usa @vite para inyectar CSS/JS

Ruta: GET / → view('welcome')
```

### Configuración de CSS: app.css
```css
@import 'tailwindcss';

@source directives (archivos a escanear):
- ../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php
- ../storage/framework/views/*.php
- ../resources/**/*.blade.php
- ../resources/**/*.js

@theme {
    --font-family: 'Instrument Sans';
}
```

### JavaScript: app.js
```javascript
import './bootstrap';

// Bootstrap.js contiene configuración básica
// (actualmente vacío, listo para expansión)
```

### Configuración de Vite: vite.config.js
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**']
        }
    }
});
```

---

## Estructura de Rutas

### Web Routes (routes/web.php)
```php
Route::get('/', function () {
    return view('welcome');
});

// Filament registra automáticamente:
// - /admin (panel administrativo)
// - /admin/login
// - /admin/register (si está habilitado)
```

### Console Routes (routes/console.php)
```php
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

---

## Service Providers

### bootstrap/providers.php
```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
```

### AppServiceProvider
```php
// Listo para configuración personalizada
// register() → Registrar servicios
// boot() → Bootstrapping de servicios
```

### AdminPanelProvider
```php
// Configura el panel de Filament
// Ver sección "Estructura del Panel Administrativo Filament"
```

---

## Configuración de Laravel Sail

### Comandos Esenciales

#### Inicialización
```bash
# Iniciar contenedores (detached)
vendor/bin/sail up -d

# Detener contenedores
vendor/bin/sail stop

# Reconstruir contenedores
vendor/bin/sail build --no-cache

# Abrir en navegador
vendor/bin/sail open
```

#### Desarrollo
```bash
# Entorno completo de desarrollo
vendor/bin/sail composer run dev
# Ejecuta: server + queue + logs + vite

# Solo servidor
vendor/bin/sail artisan serve

# Solo frontend
vendor/bin/sail npm run dev

# Build de producción
vendor/bin/sail npm run build
```

#### Base de Datos
```bash
# Ejecutar migraciones
vendor/bin/sail artisan migrate

# Fresh migration + seed
vendor/bin/sail artisan migrate:fresh --seed

# Rollback
vendor/bin/sail artisan migrate:rollback

# Estado de migraciones
vendor/bin/sail artisan migrate:status
```

#### Artisan Commands
```bash
# Crear modelo con migración, factory, seeder
vendor/bin/sail artisan make:model NombreModelo -mfs

# Crear controlador
vendor/bin/sail artisan make:controller NombreController

# Crear Form Request
vendor/bin/sail artisan make:request NombreRequest

# Crear test feature
vendor/bin/sail artisan make:test NombreTest --phpunit

# Crear test unit
vendor/bin/sail artisan make:test NombreTest --phpunit --unit

# Listar rutas
vendor/bin/sail artisan route:list
```

#### Composer & NPM
```bash
# Instalar dependencias PHP
vendor/bin/sail composer install

# Actualizar dependencias PHP
vendor/bin/sail composer update

# Instalar dependencias NPM
vendor/bin/sail npm install

# Actualizar dependencias NPM
vendor/bin/sail npm update
```

#### Code Quality
```bash
# Formatear código con Pint
vendor/bin/sail bin pint --dirty

# Verificar formato sin cambios
vendor/bin/sail bin pint --test
```

#### Testing
```bash
# Ejecutar todos los tests
vendor/bin/sail artisan test --compact

# Test específico
vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php

# Filtrar por nombre
vendor/bin/sail artisan test --compact --filter=testNombre
```

---

## Scripts de Composer Personalizados

### composer.json scripts
```json
"scripts": {
    "setup": [
        "@composer install",
        "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
        "@php artisan key:generate --ansi",
        "@php artisan migrate --graceful --ansi",
        "@php artisan db:seed --ansi",
        "@npm install",
        "@npm run build"
    ],
    "dev": [
        "Composer\\Config::disableProcessTimeout",
        "npx concurrently -c '#93c5fd,#c4b5fd,#fb923c,#fdba74' \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail\" \"npm run dev\" --names=server,queue,logs,vite"
    ],
    "test": [
        "@php artisan config:clear",
        "@php artisan test"
    ]
}
```

#### Ejecutar scripts:
```bash
vendor/bin/sail composer run setup   # Setup inicial completo
vendor/bin/sail composer run dev     # Entorno de desarrollo
vendor/bin/sail composer run test    # Ejecutar tests
```

---

## Instalación y Setup del Proyecto

### Paso 1: Clonar/Crear el Proyecto
```bash
# Si es nuevo proyecto
composer create-project laravel/laravel tareas

# O clonar repositorio existente
git clone <repository-url> tareas
cd tareas
```

### Paso 2: Configurar Entorno
```bash
# Copiar archivo de entorno
cp .env.example .env

# Editar .env:
APP_NAME=Tareas
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

### Paso 3: Instalar Dependencias
```bash
# PHP dependencies
vendor/bin/sail composer install

# NPM dependencies
vendor/bin/sail npm install
```

### Paso 4: Configurar Base de Datos
```bash
# Crear archivo SQLite
touch database/database.sqlite
touch database/testing.sqlite

# Generar key
vendor/bin/sail artisan key:generate

# Ejecutar migraciones
vendor/bin/sail artisan migrate

# Seed (opcional)
vendor/bin/sail artisan db:seed
```

### Paso 5: Instalar Filament
```bash
vendor/bin/sail composer require filament/filament:"^5.0"

# Crear usuario admin
vendor/bin/sail artisan make:filament-user
```

### Paso 6: Compilar Assets
```bash
# Desarrollo
vendor/bin/sail npm run dev

# O producción
vendor/bin/sail npm run build
```

### Paso 7: Iniciar Aplicación
```bash
# Iniciar Sail
vendor/bin/sail up -d

# O usar script de desarrollo completo
vendor/bin/sail composer run dev

# Abrir en navegador
vendor/bin/sail open
```

---

## Extensiones y Mejoras Sugeridas

### Funcionalidades Adicionales

#### 1. Sistema de Etiquetas (Tags)
```php
Nueva entidad: Tag
- id, name, color, created_at, updated_at

Relación: Task belongsToMany Tag (pivot table: task_tag)

En Filament:
- TagsRelationManager en TaskResource
- Select múltiple en formulario
- Filtro por tags en tabla
```

#### 2. Comentarios en Tareas
```php
Nuevo modelo: Comment
- id, task_id, user_id, content, created_at, updated_at

Relaciones:
- Comment belongsTo Task
- Comment belongsTo User
- Task hasMany Comment

En Filament:
- CommentsRelationManager en TaskResource
- Widget de comentarios recientes
```

#### 3. Archivos Adjuntos
```php
Nuevo modelo: Attachment
- id, task_id, filename, path, mime_type, size, created_at

Usar Spatie Media Library:
- composer require spatie/laravel-medialibrary

En Filament:
- FileUpload component en formulario
- AttachmentsRelationManager
```

#### 4. Notificaciones
```php
Implementar:
- Notificaciones de tareas vencidas (diarias)
- Notificaciones de nuevas asignaciones
- Notificaciones de cambios de prioridad

Usar:
- vendor/bin/sail artisan make:notification TaskDueNotification
- Laravel Notification system
- Filament Notifications
```

#### 5. API REST
```php
Crear:
- API Resource Controllers
- API Eloquent Resources
- Sanctum authentication
- API versioning (v1, v2)

Rutas:
- GET /api/v1/tasks
- POST /api/v1/tasks
- GET /api/v1/tasks/{id}
- PUT /api/v1/tasks/{id}
- DELETE /api/v1/tasks/{id}
```

#### 6. Roles y Permisos
```php
Instalar Spatie Permission:
- composer require spatie/laravel-permission

Roles:
- Admin (todos los permisos)
- Manager (crear, ver, editar todas las tareas)
- User (solo sus tareas)

Permisos:
- view tasks
- create tasks
- edit tasks
- delete tasks
- view all tasks
```

#### 7. Recordatorios
```php
Nuevo modelo: Reminder
- id, task_id, remind_at, sent, created_at, updated_at

Job programado:
- vendor/bin/sail artisan make:job SendTaskReminder
- Configurar en Laravel Scheduler
- Enviar emails/notificaciones
```

#### 8. Subtareas
```php
Implementar jerarquía:
- Agregar parent_id a tasks table
- Task belongsTo parent (self-referential)
- Task hasMany children

En Filament:
- Nested table o tree view
- Formulario con select de tarea padre
```

#### 9. Historial de Cambios
```php
Instalar Spatie Activity Log:
- composer require spatie/laravel-activitylog

Registrar:
- Creación de tareas
- Actualizaciones
- Eliminaciones
- Cambios de estado

En Filament:
- Relation Manager para ver historial
- Widget de actividad reciente
```

#### 10. Exportación/Importación
```php
Usar Laravel Excel:
- composer require maatwebsite/excel

Funcionalidades:
- Exportar tareas a Excel/CSV
- Importar tareas desde Excel/CSV
- Plantillas de importación

En Filament:
- Actions en ListTasks para export/import
- Validación de datos importados
```

### Mejoras de UI/UX

1. **Drag & Drop** para ordenar tareas
2. **Vista de Calendario** para tareas con fechas
3. **Vista Kanban** por estados
4. **Dark Mode** toggle
5. **Búsqueda avanzada** con múltiples criterios
6. **Filtros guardados** (vistas personalizadas)
7. **Dashboard personalizable** (widgets arrastrables)
8. **Shortcuts de teclado** para acciones rápidas

### Mejoras de Rendimiento

1. **Cache de estadísticas** (Redis)
2. **Eager loading** optimizado
3. **Lazy loading** de relaciones
4. **Database indexing** adicional
5. **Query optimization** con Telescope

---

## Estructura de Directorios Completa

```
tareas/
├── app/
│   ├── Console/
│   │   └── Commands/           # Comandos Artisan personalizados
│   ├── Enums/
│   │   └── TaskPriority.php    # Enum de prioridades
│   ├── Exceptions/
│   │   └── Handler.php         # Manejador de excepciones
│   ├── Filament/
│   │   ├── Resources/
│   │   │   └── Tasks/
│   │   │       ├── TaskResource.php
│   │   │       ├── Pages/
│   │   │       │   ├── ListTasks.php
│   │   │       │   ├── CreateTask.php
│   │   │       │   └── EditTask.php
│   │   │       ├── Schemas/
│   │   │       │   └── TaskForm.php
│   │   │       └── Tables/
│   │   │           └── TasksTable.php
│   │   └── Widgets/
│   │       └── TasksOverview.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Controller.php  # Base controller
│   │   └── Middleware/          # Middleware personalizados
│   ├── Models/
│   │   ├── User.php
│   │   └── Task.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── Filament/
│           └── AdminPanelProvider.php
├── bootstrap/
│   ├── app.php                 # Configuración de aplicación
│   ├── providers.php           # Registro de providers
│   └── cache/
├── config/                     # Archivos de configuración
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   └── ...
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   └── TaskFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   └── 2026_01_31_080703_create_tasks_table.php
│   ├── seeders/
│   │   └── DatabaseSeeder.php
│   ├── database.sqlite         # Base de datos principal
│   └── testing.sqlite          # Base de datos de testing
├── public/                     # Assets públicos
│   ├── index.php
│   └── ...
├── resources/
│   ├── css/
│   │   └── app.css             # Tailwind CSS
│   ├── js/
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/
│       └── welcome.blade.php
├── routes/
│   ├── web.php                 # Rutas web
│   └── console.php             # Comandos de consola
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
│   ├── Feature/
│   │   ├── ExampleTest.php
│   │   └── TaskTest.php
│   ├── Unit/
│   │   └── ExampleTest.php
│   ├── Pest.php
│   └── TestCase.php
├── vendor/                     # Dependencias
├── .env                        # Variables de entorno
├── .env.example                # Template de variables
├── artisan                     # CLI de Laravel
├── composer.json               # Dependencias PHP
├── composer.lock
├── package.json                # Dependencias NPM
├── package-lock.json
├── phpunit.xml                 # Configuración de PHPUnit
├── vite.config.js              # Configuración de Vite
├── CLAUDE.md                   # Instrucciones para Claude AI
└── README.md                   # Documentación del proyecto
```

---

## Checklist de Implementación

### Fase 1: Setup Inicial
- [ ] Crear proyecto Laravel 12
- [ ] Configurar Laravel Sail
- [ ] Configurar base de datos SQLite
- [ ] Instalar Filament v5
- [ ] Configurar Tailwind CSS v4
- [ ] Configurar Vite

### Fase 2: Modelos y Migraciones
- [ ] Crear modelo User (viene por defecto)
- [ ] Crear modelo Task con migración
- [ ] Crear enum TaskPriority
- [ ] Definir relaciones entre modelos
- [ ] Agregar índices a base de datos
- [ ] Crear factories para ambos modelos
- [ ] Crear seeder de datos de prueba

### Fase 3: Panel Administrativo Filament
- [ ] Configurar AdminPanelProvider
- [ ] Crear TaskResource
- [ ] Configurar TaskForm (esquema de formulario)
- [ ] Configurar TasksTable (columnas, filtros, acciones)
- [ ] Crear páginas (List, Create, Edit)
- [ ] Crear widget TasksOverview
- [ ] Configurar navegación y permisos

### Fase 4: Frontend
- [ ] Crear vista welcome.blade.php
- [ ] Configurar Tailwind CSS con @source
- [ ] Compilar assets con Vite
- [ ] Agregar navegación condicional
- [ ] Responsive design

### Fase 5: Testing
- [ ] Configurar PHPUnit y Pest
- [ ] Crear tests de modelo Task
- [ ] Crear tests de relaciones
- [ ] Crear tests de recursos Filament
- [ ] Ejecutar suite completa de tests

### Fase 6: Calidad de Código
- [ ] Configurar Laravel Pint
- [ ] Formatear código
- [ ] Revisar convenciones
- [ ] Documentar código

### Fase 7: Deployment
- [ ] Configurar variables de entorno
- [ ] Build de producción
- [ ] Optimizar carga
- [ ] Configurar logs

---

## Variables de Entorno Importantes

```env
# Aplicación
APP_NAME=Tareas
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

# Base de Datos
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail (si se implementa)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Filament
FILAMENT_FILESYSTEM_DISK=public
```

---

## Comandos de Uso Frecuente

### Desarrollo Diario
```bash
# Iniciar entorno completo
vendor/bin/sail up -d && vendor/bin/sail composer run dev

# Ver logs en tiempo real
vendor/bin/sail artisan pail

# Limpiar cache
vendor/bin/sail artisan cache:clear
vendor/bin/sail artisan config:clear
vendor/bin/sail artisan view:clear

# Refrescar base de datos
vendor/bin/sail artisan migrate:fresh --seed
```

### Debug
```bash
# Entrar a Tinker (REPL)
vendor/bin/sail artisan tinker

# Ver rutas
vendor/bin/sail artisan route:list

# Ver configuración
vendor/bin/sail artisan config:show database

# Query directo a DB
vendor/bin/sail artisan db
```

### Mantenimiento
```bash
# Optimizar aplicación
vendor/bin/sail artisan optimize

# Clear de todo
vendor/bin/sail artisan optimize:clear

# Actualizar Filament
vendor/bin/sail composer update filament/filament --with-dependencies
vendor/bin/sail artisan filament:upgrade
```

---

## Control de Versiones con Git y GitHub

### Configuración Inicial del Repositorio

Este proyecto está configurado con Git y alojado en GitHub:

**Repositorio:** https://github.com/fvasquezl/tareas
**Branch principal:** main
**Visibilidad:** Público

#### Inicialización del Repositorio
```bash
# Inicializar git (ya realizado)
git init
git branch -m main

# Verificar configuración
git config user.name
git config user.email

# Configurar usuario si es necesario
git config --global user.name "Tu Nombre"
git config --global user.email "tu@email.com"
```

#### Primer Commit
```bash
# Agregar todos los archivos
git add .

# Crear commit inicial
git commit -m "Initial commit: Laravel 12 Task Management Application

Setup complete Laravel 12 application with:
- Filament admin panel for task management
- Task model with priority, status, and due date
- SQLite database configuration
- Laravel Sail (Docker) development environment
- Tailwind CSS v4 frontend
- PHPUnit testing setup

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

### GitHub CLI (gh)

Este proyecto utiliza GitHub CLI para gestión automatizada del repositorio.

#### Instalación de GitHub CLI
```bash
# Arch Linux
sudo pacman -S github-cli

# Si hay conflictos con mailcap
sudo pacman -S github-cli --overwrite '*'

# Ubuntu/Debian
sudo apt install gh

# macOS
brew install gh
```

#### Autenticación
```bash
# Iniciar sesión en GitHub
gh auth login

# Verificar estado de autenticación
gh auth status

# Cerrar sesión
gh auth logout
```

#### Comandos Útiles de GitHub CLI
```bash
# Ver información del repositorio
gh repo view

# Abrir repositorio en el navegador
gh repo view --web

# Ver issues
gh issue list

# Crear un issue
gh issue create

# Ver pull requests
gh pr list

# Crear un pull request
gh pr create

# Ver checks/actions
gh run list

# Ver workflow runs
gh run view
```

### Workflow de Desarrollo con Git

#### Comandos Básicos Diarios
```bash
# Ver estado actual
git status

# Ver diferencias sin staging
git diff

# Ver diferencias staged
git diff --cached

# Agregar archivos específicos
git add ruta/al/archivo.php

# Agregar todos los archivos modificados
git add .

# Crear commit
git commit -m "Descripción del cambio"

# Subir cambios al repositorio remoto
git push

# Bajar cambios del repositorio remoto
git pull
```

#### Trabajo con Branches
```bash
# Ver branches locales
git branch

# Ver branches remotos
git branch -r

# Crear nuevo branch
git checkout -b feature/nueva-funcionalidad

# Cambiar de branch
git checkout main

# Subir branch al remoto
git push -u origin feature/nueva-funcionalidad

# Eliminar branch local
git branch -d feature/nombre

# Eliminar branch remoto
git push origin --delete feature/nombre

# Mergear branch a main
git checkout main
git merge feature/nombre
```

#### Historial y Logs
```bash
# Ver historial de commits
git log

# Ver historial compacto
git log --oneline

# Ver historial gráfico
git log --graph --oneline --all

# Ver cambios en un commit específico
git show <commit-hash>

# Ver quién modificó cada línea
git blame archivo.php
```

#### Deshacer Cambios
```bash
# Descartar cambios en archivo (sin staging)
git checkout -- archivo.php

# Descartar todos los cambios sin staging
git checkout -- .

# Quitar archivo del staging
git reset HEAD archivo.php

# Deshacer último commit (mantener cambios)
git reset --soft HEAD~1

# Deshacer último commit (eliminar cambios)
git reset --hard HEAD~1

# Revertir un commit específico
git revert <commit-hash>
```

### Convenciones de Commits

Este proyecto sigue el formato de commits convencionales:

#### Estructura del Mensaje
```
<tipo>: <descripción breve>

<descripción detallada opcional>

<footer opcional>
```

#### Tipos de Commits
- **feat**: Nueva funcionalidad
- **fix**: Corrección de bug
- **docs**: Cambios en documentación
- **style**: Cambios de formato (no afectan código)
- **refactor**: Refactorización de código
- **test**: Agregar o modificar tests
- **chore**: Tareas de mantenimiento

#### Ejemplos
```bash
# Nueva funcionalidad
git commit -m "feat: agregar filtro de tareas por fecha de vencimiento"

# Corrección de bug
git commit -m "fix: corregir validación de fecha en TaskForm"

# Documentación
git commit -m "docs: actualizar README con instrucciones de instalación"

# Refactorización
git commit -m "refactor: extraer lógica de estadísticas a TaskService"

# Tests
git commit -m "test: agregar tests para TaskPriority enum"

# Mantenimiento
git commit -m "chore: actualizar dependencias de Filament a v5.1"
```

### Archivos Ignorados (.gitignore)

El proyecto incluye un `.gitignore` configurado para Laravel:

```gitignore
# Archivos de log y sistema
*.log
.DS_Store
Thumbs.db

# Variables de entorno
.env
.env.backup
.env.production

# IDEs
/.idea
/.vscode
/.fleet
/.nova
/.zed

# Dependencias
/node_modules
/vendor

# Build files
/public/build
/public/hot
/public/storage

# Cache y testing
/.phpunit.cache
.phpactor.json
.phpunit.result.cache
/storage/*.key
/storage/pail
```

**Importante:** Nunca commitear:
- Archivo `.env` (contiene datos sensibles)
- Base de datos SQLite (`database.sqlite`)
- Carpetas `vendor/` y `node_modules/`
- Archivos de cache y logs

### Comandos Git Avanzados

#### Stashing (Guardar cambios temporalmente)
```bash
# Guardar cambios sin commit
git stash

# Ver stashes guardados
git stash list

# Recuperar último stash
git stash pop

# Recuperar stash específico
git stash apply stash@{0}

# Eliminar stash
git stash drop stash@{0}
```

#### Tags (Versiones)
```bash
# Crear tag
git tag -a v1.0.0 -m "Primera versión estable"

# Listar tags
git tag

# Subir tag al remoto
git push origin v1.0.0

# Subir todos los tags
git push --tags

# Eliminar tag local
git tag -d v1.0.0

# Eliminar tag remoto
git push origin --delete v1.0.0
```

#### Configuración del Remoto
```bash
# Ver remotos configurados
git remote -v

# Agregar remoto
git remote add origin https://github.com/fvasquezl/tareas.git

# Cambiar URL del remoto
git remote set-url origin https://github.com/fvasquezl/tareas.git

# Eliminar remoto
git remote remove origin
```

### Workflow Recomendado para Nuevas Features

```bash
# 1. Asegurarse de estar en main actualizado
git checkout main
git pull origin main

# 2. Crear branch para la feature
git checkout -b feature/nombre-descriptivo

# 3. Realizar cambios y commits
# ... hacer modificaciones ...
git add .
git commit -m "feat: descripción del cambio"

# 4. Subir branch al remoto
git push -u origin feature/nombre-descriptivo

# 5. Crear pull request (usando GitHub CLI)
gh pr create --title "Título del PR" --body "Descripción detallada"

# 6. Después de aprobación, mergear
gh pr merge

# 7. Volver a main y actualizar
git checkout main
git pull origin main

# 8. Eliminar branch local
git branch -d feature/nombre-descriptivo
```

### Solución de Problemas Comunes

#### Conflictos al hacer pull
```bash
# 1. Hacer pull (aparecerán conflictos)
git pull

# 2. Resolver conflictos manualmente en los archivos
# Los conflictos se marcan con:
# <<<<<<< HEAD
# tus cambios
# =======
# cambios remotos
# >>>>>>> branch

# 3. Marcar como resueltos
git add archivo-con-conflicto.php

# 4. Completar el merge
git commit -m "merge: resolver conflictos con main"
```

#### Push rechazado
```bash
# Si el push es rechazado porque hay cambios remotos:
git pull --rebase origin main
git push
```

#### Recuperar commit eliminado
```bash
# Ver historial completo (incluyendo eliminados)
git reflog

# Recuperar commit
git checkout <commit-hash>
git checkout -b branch-recuperado
```

### Integración Continua (Preparación)

El proyecto está listo para configurar CI/CD con GitHub Actions:

```yaml
# .github/workflows/laravel.yml (ejemplo)
name: Laravel Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run tests
        run: |
          composer install
          php artisan test
```

---

## Recursos Adicionales

### Documentación Oficial
- Laravel 12: https://laravel.com/docs/12.x
- Filament v5: https://filamentphp.com/docs
- Tailwind CSS v4: https://tailwindcss.com/docs
- Pest PHP: https://pestphp.com/docs

### Paquetes Útiles
- Spatie Laravel Permission: https://spatie.be/docs/laravel-permission
- Spatie Laravel Medialibrary: https://spatie.be/docs/laravel-medialibrary
- Laravel Excel: https://docs.laravel-excel.com
- Laravel Telescope: https://laravel.com/docs/telescope

---

## Notas Finales

Este proyecto sigue las mejores prácticas de Laravel 12:

1. **Arquitectura limpia** con separación de responsabilidades
2. **Type safety** con PHP 8.2+ y enums
3. **Testing completo** con Pest
4. **UI moderna** con Filament v5
5. **Código formateado** con Laravel Pint
6. **Desarrollo en contenedores** con Sail
7. **Build moderno** con Vite

El proyecto está listo para escalar y agregar nuevas funcionalidades siguiendo los patrones establecidos.
