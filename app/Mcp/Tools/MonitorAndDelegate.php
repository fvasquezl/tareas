<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class MonitorAndDelegate extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Sistema de Monitoreo y Delegación Inteligente:
        - Monitorea logs de Laravel automáticamente
        - Detecta errores críticos y excepciones
        - Analiza código relacionado al error
        - Prepara reporte estructurado para LaravelExpert
        - Sugiere tool específico para resolver (GenerateCodeWithTests, ReviewCodeSenior, etc.)
        - Prioriza errores por criticidad
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $mode = $request->argument('mode', 'recent'); // recent, critical, all
        $autoFix = $request->argument('auto_fix', false);
        $lines = $request->argument('lines', 500);

        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            return Response::text('ℹ️ No se encontraron logs. El sistema está limpio o no ha habido actividad.');
        }

        $errors = $this->scanLogsForErrors($logPath, $lines, $mode);

        if (empty($errors)) {
            return Response::text("✅ **Sistema Saludable**\n\nNo se detectaron errores en los últimos {$lines} registros.");
        }

        $output = "# 🔍 Reporte de Monitoreo y Delegación\n\n";
        $output .= "_Sistema de triage automático para Laravel Expert_\n\n";

        // Resumen ejecutivo
        $output .= "## 📊 Resumen Ejecutivo\n\n";
        $output .= '**Total de errores detectados:** '.count($errors)."\n";

        $criticalCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'critical'));
        $highCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'high'));
        $mediumCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'medium'));

        $output .= "- 🔴 Críticos: {$criticalCount}\n";
        $output .= "- 🟠 Altos: {$highCount}\n";
        $output .= "- 🟡 Medios: {$mediumCount}\n\n";

        // Errores priorizados
        $output .= "## 🚨 Errores Detectados (Priorizados)\n\n";

        foreach ($errors as $index => $error) {
            $icon = match ($error['priority']) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                default => '⚪',
            };

            $output .= "### {$icon} Error #".($index + 1)." - {$error['type']}\n\n";
            $output .= '**Prioridad:** '.strtoupper($error['priority'])."\n";
            $output .= "**Timestamp:** {$error['timestamp']}\n\n";

            $output .= "**Mensaje:**\n```\n{$error['message']}\n```\n\n";

            if (! empty($error['file'])) {
                $output .= "**Ubicación:** `{$error['file']}:{$error['line']}`\n\n";
            }

            if (! empty($error['stack_trace'])) {
                $output .= "**Stack Trace:**\n```\n{$error['stack_trace']}\n```\n\n";
            }

            // Análisis del código
            if (! empty($error['code_analysis'])) {
                $output .= "**Análisis de Código:**\n";
                foreach ($error['code_analysis'] as $analysis) {
                    $output .= "- {$analysis}\n";
                }
                $output .= "\n";
            }

            // Causa raíz probable
            $output .= "**Causa Raíz Probable:**\n{$error['root_cause']}\n\n";

            // Delegación al experto
            $output .= "**🎯 Delegación Recomendada:**\n\n";
            $output .= "```bash\n{$error['delegation']}\n```\n\n";

            $output .= "---\n\n";
        }

        // Recomendaciones generales
        $output .= "## 💡 Recomendaciones del Monitor\n\n";

        if ($criticalCount > 0) {
            $output .= "### Acción Inmediata Requerida\n\n";
            $output .= "Se detectaron **{$criticalCount} errores críticos** que requieren atención inmediata:\n\n";
            $output .= "1. Revisar y corregir errores críticos primero\n";
            $output .= "2. Ejecutar tests después de cada corrección\n";
            $output .= "3. Verificar que no haya regresiones\n\n";
        }

        $output .= "### Workflow Recomendado\n\n";
        $output .= "Para cada error detectado:\n\n";
        $output .= "1. **Activar LaravelExpert** con el contexto del error\n";
        $output .= "2. **Usar el tool sugerido** en la sección de delegación\n";
        $output .= "3. **Implementar la solución** siguiendo TDD\n";
        $output .= "4. **Ejecutar tests** para verificar la corrección\n";
        $output .= "5. **Re-ejecutar este monitor** para confirmar que el error se resolvió\n\n";

        // Comandos útiles
        $output .= "## 🛠️ Comandos Útiles\n\n";
        $output .= "```bash\n";
        $output .= "# Limpiar logs después de corregir\n";
        $output .= "vendor/bin/sail artisan log:clear\n\n";
        $output .= "# Ejecutar tests\n";
        $output .= "vendor/bin/sail artisan test --compact\n\n";
        $output .= "# Ver logs en tiempo real\n";
        $output .= "vendor/bin/sail artisan tail\n";
        $output .= "```\n\n";

        // Auto-fix mode
        if ($autoFix) {
            $output .= "## 🤖 Modo Auto-Fix Activado\n\n";
            $output .= "_Los errores han sido analizados y preparados para corrección automática._\n";
            $output .= "_Use los comandos de delegación sugeridos para proceder._\n\n";
        }

        return Response::text($output);
    }

    /**
     * Scan logs for errors and exceptions.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scanLogsForErrors(string $logPath, int $lines, string $mode): array
    {
        $content = shell_exec("tail -n {$lines} ".escapeshellarg($logPath));

        if (empty($content)) {
            return [];
        }

        $errors = [];
        $currentError = null;

        $logLines = explode("\n", $content);

        foreach ($logLines as $line) {
            // Detect error start (Laravel log format)
            if (preg_match('/\[(.*?)\]\s+(local|production)\.(\w+):\s+(.+)/', $line, $matches)) {
                // Save previous error if exists
                if ($currentError !== null) {
                    $errors[] = $this->processError($currentError);
                }

                $timestamp = $matches[1];
                $level = $matches[3];
                $message = $matches[4];

                // Filter by mode
                if ($mode === 'critical' && ! in_array(strtolower($level), ['emergency', 'alert', 'critical', 'error'])) {
                    $currentError = null;

                    continue;
                }

                $currentError = [
                    'timestamp' => $timestamp,
                    'level' => $level,
                    'message' => $message,
                    'stack_trace' => '',
                    'full_content' => $line."\n",
                ];
            } elseif ($currentError !== null) {
                // Append to current error (stack trace, etc.)
                $currentError['full_content'] .= $line."\n";

                // Extract file and line from stack trace
                if (preg_match('/#\d+\s+(.*?):(\d+)/', $line, $matches)) {
                    if (empty($currentError['file'])) {
                        $currentError['file'] = $matches[1];
                        $currentError['line'] = $matches[2];
                    }
                    $currentError['stack_trace'] .= $line."\n";
                }
            }
        }

        // Add last error
        if ($currentError !== null) {
            $errors[] = $this->processError($currentError);
        }

        // Sort by priority
        usort($errors, fn ($a, $b) => $this->getPriorityWeight($a['priority']) <=> $this->getPriorityWeight($b['priority']));

        return $errors;
    }

    /**
     * Process and enrich error information.
     *
     * @param  array<string, mixed>  $error
     * @return array<string, mixed>
     */
    protected function processError(array $error): array
    {
        // Determine priority
        $priority = match (strtolower($error['level'])) {
            'emergency', 'alert', 'critical' => 'critical',
            'error' => 'high',
            'warning' => 'medium',
            default => 'low',
        };

        // Determine error type
        $type = 'Unknown Error';
        if (str_contains($error['message'], 'SQLSTATE')) {
            $type = 'Database Error';
            $priority = 'critical';
        } elseif (str_contains($error['message'], 'Class') && str_contains($error['message'], 'not found')) {
            $type = 'Class Not Found';
            $priority = 'high';
        } elseif (str_contains($error['message'], 'Undefined')) {
            $type = 'Undefined Variable/Property';
            $priority = 'high';
        } elseif (str_contains($error['message'], 'Call to undefined')) {
            $type = 'Undefined Method';
            $priority = 'high';
        } elseif (str_contains($error['message'], 'syntax error')) {
            $type = 'Syntax Error';
            $priority = 'critical';
        } elseif (str_contains($error['message'], 'Permission denied')) {
            $type = 'Permission Error';
            $priority = 'medium';
        } elseif (str_contains($error['message'], 'Connection refused') || str_contains($error['message'], 'timeout')) {
            $type = 'Connection Error';
            $priority = 'high';
        }

        // Analyze code if file is available
        $codeAnalysis = [];
        $file = $error['file'] ?? null;
        $line = $error['line'] ?? null;

        if ($file && $line && file_exists($file)) {
            $codeAnalysis = $this->analyzeErrorContext($file, $line, $error['message']);
        }

        // Determine root cause
        $rootCause = $this->determineRootCause($type, $error['message'], $codeAnalysis);

        // Suggest delegation
        $delegation = $this->suggestDelegation($type, $file, $priority);

        return [
            'timestamp' => $error['timestamp'],
            'priority' => $priority,
            'type' => $type,
            'message' => trim($error['message']),
            'file' => $file,
            'line' => $line,
            'stack_trace' => trim($error['stack_trace'] ?? ''),
            'code_analysis' => $codeAnalysis,
            'root_cause' => $rootCause,
            'delegation' => $delegation,
        ];
    }

    /**
     * Analyze code context around the error.
     *
     * @return array<int, string>
     */
    protected function analyzeErrorContext(string $file, int $line, string $message): array
    {
        $analysis = [];

        try {
            $content = file_get_contents($file);
            $lines = explode("\n", $content);

            // Check surrounding code
            $start = max(0, $line - 5);
            $end = min(count($lines), $line + 5);

            // Common issues
            if (str_contains($message, 'Undefined variable')) {
                $analysis[] = 'Variable no inicializada antes de uso';
                $analysis[] = 'Verificar lógica de asignación y condiciones';
            }

            if (str_contains($message, 'Call to undefined method')) {
                $analysis[] = 'Método no existe en la clase';
                $analysis[] = 'Verificar trait, herencia, o typo en nombre';
            }

            if (str_contains($message, 'SQLSTATE')) {
                $analysis[] = 'Error de base de datos';
                if (str_contains($message, '23000')) {
                    $analysis[] = 'Violación de constraint (unique, foreign key, etc.)';
                }
            }

            // Check for common patterns in the code
            $codeSnippet = implode("\n", array_slice($lines, $start, $end - $start));

            if (str_contains($codeSnippet, 'DB::raw') || str_contains($codeSnippet, 'whereRaw')) {
                $analysis[] = 'Uso de raw queries - verificar sintaxis SQL';
            }

            if (preg_match('/\$\w+\s*=\s*null/', $codeSnippet)) {
                $analysis[] = 'Variable inicializada como null - verificar lógica de asignación';
            }

        } catch (\Exception $e) {
            $analysis[] = 'No se pudo analizar el código (archivo no accesible)';
        }

        return $analysis;
    }

    /**
     * Determine root cause of the error.
     *
     * @param  array<int, string>  $codeAnalysis
     */
    protected function determineRootCause(string $type, string $message, array $codeAnalysis): string
    {
        $rootCause = match ($type) {
            'Database Error' => 'Error en operación de base de datos. Puede ser constraint violation, conexión perdida, o query mal formado.',
            'Class Not Found' => 'Clase no existe o no está importada. Verificar namespace, use statements, y autoloader.',
            'Undefined Variable/Property' => 'Variable o property usada sin inicializar. Verificar lógica de asignación y flujo de ejecución.',
            'Undefined Method' => 'Método no existe en la clase. Verificar typo, trait, herencia, o implementación faltante.',
            'Syntax Error' => 'Error de sintaxis PHP. Código no puede ser parseado. Verificar paréntesis, llaves, comillas.',
            'Permission Error' => 'Permisos insuficientes para acceder archivo/directorio. Verificar ownership y chmod.',
            'Connection Error' => 'No se puede conectar a servicio externo. Verificar red, configuración, y disponibilidad del servicio.',
            default => 'Error general. Revisar mensaje y stack trace para más detalles.',
        };

        if (! empty($codeAnalysis)) {
            $rootCause .= "\n\n**Análisis adicional:** ".implode('. ', $codeAnalysis).'.';
        }

        return $rootCause;
    }

    /**
     * Suggest delegation to appropriate expert tool.
     */
    protected function suggestDelegation(string $type, ?string $file, string $priority): string
    {
        $suggestions = [];

        // Always suggest code review for critical errors
        if ($priority === 'critical' && $file) {
            $suggestions[] = '# Code Review Senior (CRÍTICO)';
            $suggestions[] = "ReviewCodeSenior file='{$file}' focus='security'";
            $suggestions[] = '';
        }

        // Type-specific suggestions
        if (str_contains($type, 'Database')) {
            $suggestions[] = '# Analizar archivo relacionado';
            if ($file) {
                $suggestions[] = "AnalyzeCode file='{$file}' type='security'";
            }
            $suggestions[] = '';
            $suggestions[] = '# Optimizar queries';
            $suggestions[] = "OptimizationSuggestions category='database'";
        } elseif (str_contains($type, 'Class Not Found') || str_contains($type, 'Undefined')) {
            if ($file) {
                $suggestions[] = '# Documentar y verificar código';
                $suggestions[] = "DocumentCode file='{$file}'";
                $suggestions[] = '';
                $suggestions[] = '# Review completo';
                $suggestions[] = "ReviewCodeSenior file='{$file}' focus='all'";
            }
        } else {
            if ($file) {
                $suggestions[] = '# Análisis general';
                $suggestions[] = "AnalyzeCode file='{$file}' type='full'";
            }
        }

        // Always suggest activating expert mode
        $suggestions[] = '';
        $suggestions[] = '# Activar Laravel Expert para solución completa';
        $suggestions[] = "LaravelExpert context='bugfix' task='Corregir: {$type}'";

        return implode("\n", $suggestions);
    }

    /**
     * Get priority weight for sorting.
     */
    protected function getPriorityWeight(string $priority): int
    {
        return match ($priority) {
            'critical' => 0,
            'high' => 1,
            'medium' => 2,
            'low' => 3,
            default => 4,
        };
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->enum(['recent', 'critical', 'all'])
                ->description('Modo de escaneo: recent (últimos errores), critical (solo críticos), all (todos)'),
            'lines' => $schema->integer()
                ->description('Número de líneas del log a analizar (por defecto: 500)'),
            'auto_fix' => $schema->boolean()
                ->description('Preparar errores para corrección automática (por defecto: false)'),
        ];
    }
}
