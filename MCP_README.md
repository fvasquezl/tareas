# Servidor MCP Laravel - Documentación

Este proyecto incluye un servidor MCP (Model Context Protocol) con tools personalizados para monitoreo, análisis y optimización de la aplicación Laravel.

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

1. **Monitorear errores:**
   > "Lee los últimos errores del log de Laravel"

2. **Analizar código:**
   > "Analiza el archivo app/Models/Task.php en busca de problemas de seguridad"

3. **Sugerencias de optimización:**
   > "Dame sugerencias de optimización para la configuración de cache"

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

- [ ] Tool para análisis de performance con Laravel Telescope
- [ ] Tool para ejecutar tests y reportar resultados
- [ ] Tool para generar documentación automática
- [ ] Tool para migrar base de datos
- [ ] Integración con servicios externos (Sentry, etc.)

## Recursos

- [Documentación Laravel MCP](https://github.com/laravel/mcp)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [Claude Desktop](https://claude.ai/download)
