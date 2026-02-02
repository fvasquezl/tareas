# Configuración Claude Desktop + Laravel Expert MCP

Guía rápida para configurar el sistema completo.

## 📋 Requisitos

- Claude Desktop instalado
- Laravel Sail corriendo
- PHP 8.2+

## 🚀 Configuración en 3 Pasos

### Paso 1: Ubicar el archivo de configuración

Según tu sistema operativo:

**macOS:**
```bash
open ~/Library/Application\ Support/Claude/
# Editar: claude_desktop_config.json
```

**Linux:**
```bash
nano ~/.config/Claude/claude_desktop_config.json
```

**Windows:**
```powershell
notepad %APPDATA%\Claude\claude_desktop_config.json
```

### Paso 2: Agregar la configuración MCP

Copia y pega esta configuración (ajusta la ruta según tu sistema):

```json
{
  "mcpServers": {
    "laravel-expert": {
      "command": "php",
      "args": [
        "/home/fvasquez/Code/Sail/tareas/artisan",
        "mcp:start",
        "default"
      ],
      "cwd": "/home/fvasquez/Code/Sail/tareas",
      "env": {
        "APP_ENV": "local"
      }
    }
  }
}
```

**⚠️ IMPORTANTE:** Cambia `/home/fvasquez/Code/Sail/tareas` por la ruta COMPLETA de tu proyecto.

**Para encontrar tu ruta:**
```bash
cd /ruta/a/tu/proyecto
pwd
# Copia el resultado
```

### Paso 3: Reiniciar Claude Desktop

1. Cierra completamente Claude Desktop
2. Ábrelo de nuevo
3. Verifica que el servidor MCP esté conectado (icono en la barra)

## ✅ Verificación

En Claude Desktop, escribe:

```
"¿Qué tools MCP tienes disponibles?"
```

Deberías ver:
- MonitorAndDelegate
- GenerateCodeWithTests
- DocumentCode
- ReviewCodeSenior
- ReadLaravelLogs
- AnalyzeCode
- OptimizationSuggestions

Y el prompt:
- LaravelExpert

## 🎯 Uso Desde Claude Desktop

### Monitoreo Automático

```
"Monitorea el sistema y dime qué errores hay"
→ Usa MonitorAndDelegate automáticamente
→ Muestra errores priorizados
→ Sugiere delegación específica
```

### Generar Código con TDD

```
"Genera un servicio PaymentProcessor que procese pagos con Stripe,
con tests completos"
→ Usa GenerateCodeWithTests
→ Genera Feature + Unit tests primero
→ Luego la implementación
```

### Code Review Senior

```
"Revisa el código de app/Http/Controllers/TaskController.php
enfocándote en seguridad"
→ Usa ReviewCodeSenior
→ Análisis SOLID
→ Security issues
→ Score 0-100
```

### Documentar Código

```
"Documenta el archivo app/Services/TaskService.php con ejemplos de uso"
→ Usa DocumentCode
→ Genera PHPDoc completo
→ Agrega type hints
```

### Activar Modo Experto

```
"Activa Laravel Expert para implementar un sistema de notificaciones
en tiempo real"
→ Usa LaravelExpert prompt
→ Análisis con 10+ años experiencia
→ Propuesta arquitectónica
→ Solución con TDD
```

## 🔧 Uso desde Terminal (Sin Claude Desktop)

Si no quieres usar Claude Desktop, puedes usar los comandos directamente:

### Diagnóstico Automático

```bash
# Escanear errores críticos
vendor/bin/sail artisan diagnose:auto --mode=critical

# Ver análisis detallado
vendor/bin/sail artisan diagnose:auto --mode=all --detailed

# Con sugerencias de fix
vendor/bin/sail artisan diagnose:auto --fix
```

### Otros Comandos

```bash
# Listar todos los comandos MCP
vendor/bin/sail artisan list mcp

# Ver tools disponibles
vendor/bin/sail artisan list diagnose
```

## 🆘 Troubleshooting

### Error: "MCP server not connected"

1. Verifica que la ruta en `claude_desktop_config.json` sea correcta
2. Asegúrate de que Laravel Sail esté corriendo
3. Reinicia Claude Desktop completamente

### Error: "Permission denied"

```bash
# Dar permisos de ejecución al artisan
chmod +x artisan

# Verificar permisos de storage
vendor/bin/sail artisan storage:link
chmod -R 775 storage
```

### Error: "Class not found"

```bash
# Limpiar y regenerar autoloader
vendor/bin/sail composer dump-autoload
vendor/bin/sail artisan optimize:clear
```

### Los tools no aparecen

1. Verifica que el servidor MCP esté corriendo:
   ```bash
   vendor/bin/sail artisan mcp:start default
   ```

2. Revisa los logs de Claude Desktop:
   - macOS: `~/Library/Logs/Claude/`
   - Linux: `~/.config/Claude/logs/`

## 📚 Recursos

- [MCP README completo](./MCP_README.md) - Documentación detallada de todos los tools
- [Model Context Protocol](https://modelcontextprotocol.io/) - Especificación oficial
- [Laravel MCP Package](https://github.com/laravel/mcp) - Documentación del paquete

## 💡 Tips Avanzados

### Alias para Comandos Frecuentes

Agrega a tu `~/.bashrc` o `~/.zshrc`:

```bash
alias diagnose="vendor/bin/sail artisan diagnose:auto"
alias mcp-start="vendor/bin/sail artisan mcp:start default"
```

Uso:
```bash
diagnose --mode=critical
diagnose --detailed
```

### Monitoreo Continuo

Script para monitoreo cada 5 minutos:

```bash
# watch-errors.sh
while true; do
    vendor/bin/sail artisan diagnose:auto --mode=critical
    sleep 300
done
```

### Integración con Git Hooks

Agregar a `.git/hooks/pre-commit`:

```bash
#!/bin/bash
vendor/bin/sail artisan diagnose:auto --mode=critical

if [ $? -ne 0 ]; then
    echo "❌ Errores críticos detectados. Corrige antes de commit."
    exit 1
fi
```

---

**¿Necesitas ayuda?** Abre un issue en el repositorio o consulta la documentación completa.
