# Servidor MCP Laravel - Documentación

Este proyecto incluye un servidor MCP (Model Context Protocol) con tools personalizados para monitoreo, análisis y optimización de la aplicación Laravel.

## Prompts Disponibles

### 1. LaravelExpert
Activa el modo "Laravel Expert" - Un desarrollador Senior PHP/Laravel con 10+ años de experiencia trabajando con empresas Fortune 500.

**Características:**
- 10+ años de experiencia con PHP/Laravel
- Experiencia en Fortune 500 (Google, Amazon, Microsoft, Apple, Facebook)
- Arquitecto de sistemas de alto tráfico (100M+ usuarios)
- Especialista en TDD, SOLID, y Domain-Driven Design

**Skills principales:**
1. **Programación nivel Senior (Tier 1)**: SOLID, Design Patterns, DDD, Clean Architecture
2. **Test-Driven Development**: Tests primero, coverage mínimo 85%
3. **Documentación perfecta**: PHPDoc completo, estándar IEEE

**Parámetros:**
- `context` (string): Contexto de la tarea (feature, bugfix, refactor, architecture)
- `task` (string): Descripción específica de la tarea

**Workflow:**
1. Análisis profundo de requerimientos
2. Tests PRIMERO (RED phase)
3. Implementación (GREEN phase)
4. Refactorización (REFACTOR phase)
5. Documentación completa

**Ejemplo de uso:**
```json
{
  "context": "feature",
  "task": "Implementar sistema de notificaciones en tiempo real"
}
```

## Tools Disponibles

### 1. ReadLaravelLogs
Monitorea y analiza logs de Laravel en tiempo real.

**Parámetros:**
- `file` (string): Nombre del archivo de log (por defecto: `laravel.log`)
- `lines` (integer): Número de líneas a leer (por defecto: 100)
- `level` (enum): Filtrar por nivel: `error`, `warning`, `info`, `debug`
- `search` (string): Buscar patrón específico

**Ejemplo de uso:**
```json
{
  "file": "laravel.log",
  "lines": 50,
  "level": "error"
}
```

### 2. AnalyzeCode
Analiza archivos PHP/Laravel para detectar problemas de seguridad y malas prácticas.

**Parámetros:**
- `file` (string, requerido): Ruta del archivo a analizar
- `type` (enum): Tipo de análisis: `full`, `security`, `best-practices`, `performance`

**Detecta:**
- SQL Injection potencial
- XSS vulnerabilities
- Mass assignment issues
- Uso incorrecto de `env()`
- Falta de type hints
- Problemas N+1

**Ejemplo de uso:**
```json
{
  "file": "app/Http/Controllers/TaskController.php",
  "type": "security"
}
```

### 3. OptimizationSuggestions
Proporciona sugerencias de optimización para la aplicación.

**Parámetros:**
- `category` (enum): Categoría a analizar: `all`, `cache`, `database`, `assets`, `config`

**Analiza:**
- Configuración de cache
- Rutas y config sin cachear
- Driver de cache subóptimo
- Assets sin compilar
- Debug mode en producción
- Autoloader sin optimizar

**Ejemplo de uso:**
```json
{
  "category": "cache"
}
```

### 4. GenerateCodeWithTests
Genera código PHP/Laravel siguiendo TDD estricto con tests primero.

**Parámetros:**
- `type` (enum, requerido): Tipo de componente: `service`, `controller`, `model`, `class`
- `name` (string, requerido): Nombre del componente (sin sufijos)
- `description` (string, requerido): Descripción de la funcionalidad
- `include_feature_test` (boolean): Incluir feature test además del unit test (por defecto: true)

**Genera:**
- Feature Test (fase RED)
- Unit Test (fase RED)
- Implementación que pasa los tests (fase GREEN)
- Documentación PHPDoc completa
- Comandos para ejecutar tests
- Checklist de calidad

**Ejemplo de uso:**
```json
{
  "type": "service",
  "name": "PaymentProcessor",
  "description": "Procesa pagos con validación y logging",
  "include_feature_test": true
}
```

### 5. DocumentCode
Analiza código y genera documentación completa siguiendo estándares IEEE.

**Parámetros:**
- `file` (string, requerido): Ruta del archivo a documentar
- `include_examples` (boolean): Incluir ejemplos de uso (por defecto: true)

**Analiza:**
- PHPDoc missing en métodos
- Type hints faltantes
- Properties sin documentar
- Calidad general de documentación

**Genera:**
- Sugerencias de PHPDoc para cada método
- Templates de documentación
- Ejemplos de uso
- Checklist de documentación

**Ejemplo de uso:**
```json
{
  "file": "app/Services/UserService.php",
  "include_examples": true
}
```

### 6. ReviewCodeSenior
Code review nivel senior con estándares Fortune 500.

**Parámetros:**
- `file` (string, requerido): Ruta del archivo a revisar
- `focus` (enum): Enfoque específico: `all`, `solid`, `security`, `performance`, `architecture`

**Analiza:**
- Principios SOLID
- PSR-12 compliance
- Code smells
- Security issues
- Performance bottlenecks
- Oportunidades de design patterns
- N+1 queries
- SQL injection risks

**Genera:**
- Score de calidad (0-100)
- Issues críticos que bloquean PR
- Warnings y recomendaciones
- Mejoras arquitectónicas
- Optimizaciones de performance
- Veredicto final (Aprobado/Rechazado)

**Ejemplo de uso:**
```json
{
  "file": "app/Http/Controllers/TaskController.php",
  "focus": "all"
}
```

## Iniciar el Servidor MCP

### Opción 1: Directamente con Artisan

```bash
vendor/bin/sail artisan mcp:start default
```

### Opción 2: Inspector MCP (para debugging)

```bash
vendor/bin/sail artisan mcp:inspector
```

## Integración con Claude Desktop

Para usar estos tools con Claude Desktop, agrega lo siguiente a tu configuración de MCP:

**Ubicación del archivo:**
- macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`
- Windows: `%APPDATA%\Claude\claude_desktop_config.json`
- Linux: `~/.config/Claude/claude_desktop_config.json`

**Configuración:**

```json
{
  "mcpServers": {
    "laravel-tareas": {
      "command": "php",
      "args": [
        "/ruta/completa/al/proyecto/artisan",
        "mcp:start",
        "default"
      ],
      "cwd": "/ruta/completa/al/proyecto"
    }
  }
}
```

## Uso desde Claude Desktop

Una vez configurado, puedes usar los tools desde Claude Desktop:

**Ejemplos de prompts:**

### Monitoreo y Análisis
1. **Monitorear errores:**
   > "Lee los últimos errores del log de Laravel"

2. **Analizar código:**
   > "Analiza el archivo app/Models/Task.php en busca de problemas de seguridad"

3. **Sugerencias de optimización:**
   > "Dame sugerencias de optimización para la configuración de cache"

### Laravel Expert Mode
4. **Activar modo experto:**
   > "Activa el prompt LaravelExpert con contexto 'feature' y tarea 'crear sistema de notificaciones'"

5. **Generar código con TDD:**
   > "Genera un servicio PaymentProcessor con tests que procese pagos con Stripe"

6. **Documentar código:**
   > "Documenta el archivo app/Services/TaskService.php con ejemplos de uso"

7. **Code review senior:**
   > "Revisa el código de app/Http/Controllers/TaskController.php con enfoque en seguridad"

## Respuestas de los Tools

Todos los tools retornan resultados en formato Markdown con:
- Resúmenes ejecutivos
- Issues priorizados (🔴 Alta, 🟡 Media, 🟢 Baja)
- Sugerencias accionables
- Comandos para resolver problemas
- Ejemplos de código cuando aplica

## Desarrollo de Nuevos Tools

Para crear un nuevo tool MCP:

```bash
vendor/bin/sail artisan make:mcp-tool NombreDelTool
```

Esto creará un archivo en `app/Mcp/Tools/NombreDelTool.php` con la estructura base.

### Estructura de un Tool

```php
<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MiTool extends Tool
{
    protected string $description = 'Descripción del tool';

    public function handle(Request $request): Response
    {
        // Tu lógica aquí
        return Response::text('Resultado en Markdown');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'parametro' => $schema->string()
                ->description('Descripción del parámetro'),
        ];
    }
}
```

## Troubleshooting

### El servidor no inicia

Verifica permisos y que Laravel esté correctamente configurado:
```bash
vendor/bin/sail artisan config:clear
vendor/bin/sail artisan cache:clear
```

### Tools no aparecen en Claude Desktop

1. Verifica la ruta completa en la configuración
2. Reinicia Claude Desktop completamente
3. Revisa los logs del servidor MCP

### Error de permisos al leer logs

Asegúrate de que el usuario tenga permisos de lectura:
```bash
vendor/bin/sail bash
chmod -R 755 storage/logs
```

## Próximas Mejoras

### Completado ✅
- [x] Prompt LaravelExpert con estándares Fortune 500
- [x] Tool para generar código con TDD (GenerateCodeWithTests)
- [x] Tool para documentar código automáticamente (DocumentCode)
- [x] Tool para code review nivel senior (ReviewCodeSenior)

### En Roadmap
- [ ] Tool para análisis de performance con Laravel Telescope
- [ ] Tool para ejecutar tests y reportar resultados automáticamente
- [ ] Tool para migrar base de datos con rollback inteligente
- [ ] Tool para análisis de dependencias y actualizaciones
- [ ] Integración con servicios externos (Sentry, New Relic, etc.)
- [ ] Tool para generar factories y seeders basados en modelos existentes
- [ ] Tool para detectar y corregir vulnerabilidades OWASP Top 10

## Sistema Laravel Expert

El sistema Laravel Expert es un conjunto integrado de herramientas MCP diseñado para proporcionar asistencia de nivel senior en desarrollo Laravel.

### Filosofía

El sistema está diseñado basándose en:
- **10+ años de experiencia** en PHP/Laravel profesional
- **Estándares Fortune 500** (Google, Amazon, Microsoft, Apple, Facebook)
- **TDD estricto** con coverage mínimo del 85%
- **SOLID principles** y Clean Architecture
- **Documentación IEEE** con PHPDoc completo

### Workflow Recomendado

1. **Activar Modo Experto**
   ```
   Usa el prompt LaravelExpert con el contexto y tarea específicos
   ```

2. **Generar Código con Tests**
   ```
   Usa GenerateCodeWithTests para crear componentes siguiendo TDD:
   - Tests Feature y Unit primero (RED)
   - Implementación que pasa tests (GREEN)
   - Refactorización (REFACTOR)
   ```

3. **Documentar Código**
   ```
   Usa DocumentCode para agregar PHPDoc completo:
   - Type hints estrictos
   - Ejemplos de uso
   - Documentación de excepciones
   ```

4. **Code Review**
   ```
   Usa ReviewCodeSenior antes de merge:
   - Verifica SOLID principles
   - Detecta security issues
   - Sugiere mejoras arquitectónicas
   - Score de calidad 0-100
   ```

### Estándares de Calidad

Todos los tools siguen estos estándares inquebrantables:

#### Código Limpio
- PSR-12 compliance obligatorio
- Single Responsibility Principle
- Funciones pequeñas (< 20 líneas)
- No code duplication (DRY)
- Early returns sobre nested ifs

#### Seguridad
- Validación exhaustiva de inputs
- Prevención de SQL Injection, XSS, CSRF
- Rate limiting en APIs
- Autenticación y autorización robustas
- Logging de acciones sensibles

#### Performance
- Eager loading (NO N+1 queries)
- Database indexing estratégico
- Query optimization
- Caching inteligente

#### Testing
- Coverage mínimo: 85%
- Feature + Unit tests
- Tests ANTES del código
- Mocking y Fakes apropiados

### Restricciones del Sistema

El sistema está configurado para RECHAZAR:
- ❌ Código sin tests
- ❌ Código sin documentación
- ❌ "Quick fixes" o shortcuts
- ❌ Código legacy/deprecated
- ❌ Ignorar edge cases
- ❌ Assumptions sin validación

## Recursos

- [Documentación Laravel MCP](https://github.com/laravel/mcp)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [Claude Desktop](https://claude.ai/download)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Laravel Best Practices](https://laravel.com/docs)
