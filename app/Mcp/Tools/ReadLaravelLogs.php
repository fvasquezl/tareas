<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReadLaravelLogs extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Lee y analiza los logs de Laravel almacenados en storage/logs.
        Puede filtrar por nivel de error (error, warning, info), fecha y buscar patrones específicos.
        Útil para monitoreo de errores y debugging de la aplicación.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $logFile = $request->argument('file', 'laravel.log');
        $lines = (int) $request->argument('lines', 100);
        $level = $request->argument('level');
        $search = $request->argument('search');

        $logPath = storage_path("logs/{$logFile}");

        if (! File::exists($logPath)) {
            return Response::text("El archivo de log '{$logFile}' no existe.");
        }

        $content = File::get($logPath);
        $logLines = explode("\n", $content);

        // Obtener las últimas líneas
        $logLines = array_slice($logLines, -$lines);

        // Filtrar por nivel si se especifica
        if ($level) {
            $logLines = array_filter($logLines, function ($line) use ($level) {
                return stripos($line, ".{$level}:") !== false;
            });
        }

        // Buscar patrón específico
        if ($search) {
            $logLines = array_filter($logLines, function ($line) use ($search) {
                return stripos($line, $search) !== false;
            });
        }

        // Analizar errores
        $analysis = $this->analyzeLogs($logLines);

        $output = "# Análisis de Logs de Laravel\n\n";
        $output .= "**Archivo:** {$logFile}\n";
        $output .= '**Líneas analizadas:** '.count($logLines)."\n\n";

        if (! empty($analysis['errors'])) {
            $output .= "## Errores Encontrados ({$analysis['error_count']})\n\n";
            foreach (array_slice($analysis['errors'], 0, 5) as $error) {
                $output .= "- **{$error['type']}**: {$error['message']}\n";
                $output .= "  *Archivo:* {$error['file']}:{$error['line']}\n\n";
            }
        }

        if (! empty($analysis['warnings'])) {
            $output .= "\n## Advertencias ({$analysis['warning_count']})\n\n";
        }

        $output .= "\n## Logs Recientes\n\n```\n";
        $output .= implode("\n", array_slice($logLines, -20));
        $output .= "\n```\n";

        return Response::text($output);
    }

    /**
     * Analizar logs para extraer información útil.
     */
    protected function analyzeLogs(array $logLines): array
    {
        $errors = [];
        $warnings = [];

        foreach ($logLines as $line) {
            // Detectar errores
            if (preg_match('/\.ERROR: (.+?) in (.+?):(\d+)/', $line, $matches)) {
                $errors[] = [
                    'type' => 'ERROR',
                    'message' => $matches[1] ?? 'Unknown',
                    'file' => $matches[2] ?? 'Unknown',
                    'line' => $matches[3] ?? '0',
                ];
            }

            // Detectar warnings
            if (stripos($line, '.WARNING:') !== false) {
                $warnings[] = $line;
            }
        }

        return [
            'errors' => $errors,
            'error_count' => count($errors),
            'warnings' => $warnings,
            'warning_count' => count($warnings),
        ];
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'file' => $schema->string()
                ->description('Nombre del archivo de log (por defecto: laravel.log)'),
            'lines' => $schema->integer()
                ->description('Número de líneas a leer desde el final (por defecto: 100)'),
            'level' => $schema->enum(['error', 'warning', 'info', 'debug'])
                ->description('Filtrar por nivel de log'),
            'search' => $schema->string()
                ->description('Buscar un patrón específico en los logs'),
        ];
    }
}
