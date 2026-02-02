<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AnalyzeCode extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Analiza archivos PHP y Laravel para detectar:
        - Problemas de seguridad potenciales
        - Malas prácticas de código
        - Consultas N+1
        - Falta de validación
        - Código no utilizado
        - Mejoras en convenciones de Laravel
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $filePath = $request->argument('file');
        $analysisType = $request->argument('type', 'full');

        if (! File::exists(base_path($filePath))) {
            return Response::text("El archivo '{$filePath}' no existe.");
        }

        $content = File::get(base_path($filePath));

        $issues = [];

        // Análisis de seguridad
        if (in_array($analysisType, ['full', 'security'])) {
            $issues = array_merge($issues, $this->analyzeSecure($content));
        }

        // Análisis de buenas prácticas
        if (in_array($analysisType, ['full', 'best-practices'])) {
            $issues = array_merge($issues, $this->analyzeBestPractices($content));
        }

        // Análisis de consultas N+1
        if (in_array($analysisType, ['full', 'performance'])) {
            $issues = array_merge($issues, $this->analyzeNPlusOne($content));
        }

        $output = "# Análisis de Código: {$filePath}\n\n";
        $output .= "**Tipo de análisis:** {$analysisType}\n\n";

        if (empty($issues)) {
            $output .= "✅ **No se encontraron problemas significativos.**\n";
        } else {
            $output .= '## Problemas Encontrados ('.count($issues).")\n\n";

            $grouped = collect($issues)->groupBy('severity');

            if ($grouped->has('high')) {
                $output .= "### 🔴 Prioridad Alta\n\n";
                foreach ($grouped['high'] as $issue) {
                    $output .= $this->formatIssue($issue);
                }
            }

            if ($grouped->has('medium')) {
                $output .= "### 🟡 Prioridad Media\n\n";
                foreach ($grouped['medium'] as $issue) {
                    $output .= $this->formatIssue($issue);
                }
            }

            if ($grouped->has('low')) {
                $output .= "### 🟢 Prioridad Baja\n\n";
                foreach ($grouped['low'] as $issue) {
                    $output .= $this->formatIssue($issue);
                }
            }
        }

        return Response::text($output);
    }

    protected function analyzeSecure(string $content): array
    {
        $issues = [];

        // SQL Injection
        if (preg_match('/DB::raw\(["\'].*\$/', $content)) {
            $issues[] = [
                'type' => 'Seguridad',
                'severity' => 'high',
                'message' => 'Posible SQL Injection con DB::raw() y variables no sanitizadas',
                'suggestion' => 'Usa query builder o prepared statements',
            ];
        }

        // XSS
        if (preg_match('/\{!!.*\$/', $content)) {
            $issues[] = [
                'type' => 'Seguridad',
                'severity' => 'high',
                'message' => 'Uso de {!! !!} sin escapar puede causar XSS',
                'suggestion' => 'Usa {{ }} para auto-escapar o valida el contenido',
            ];
        }

        // Mass assignment sin $fillable o $guarded
        if (preg_match('/class\s+\w+\s+extends\s+Model/', $content) &&
            ! preg_match('/protected\s+\$fillable/', $content) &&
            ! preg_match('/protected\s+\$guarded/', $content)) {
            $issues[] = [
                'type' => 'Seguridad',
                'severity' => 'medium',
                'message' => 'Modelo sin $fillable o $guarded definido',
                'suggestion' => 'Define $fillable o $guarded para prevenir mass assignment',
            ];
        }

        return $issues;
    }

    protected function analyzeBestPractices(string $content): array
    {
        $issues = [];

        // Uso de env() fuera de config
        if (preg_match('/env\(/', $content) && ! str_contains($content, '/config/')) {
            $issues[] = [
                'type' => 'Buenas Prácticas',
                'severity' => 'medium',
                'message' => 'Uso de env() fuera de archivos de configuración',
                'suggestion' => 'Usa config() en su lugar',
            ];
        }

        // Falta de type hints
        if (preg_match('/function\s+\w+\s*\([^)]*\)\s*{/', $content) &&
            ! preg_match('/function\s+\w+\s*\([^)]*:\s*\w+/', $content)) {
            $issues[] = [
                'type' => 'Buenas Prácticas',
                'severity' => 'low',
                'message' => 'Falta de type hints en métodos',
                'suggestion' => 'Agrega type hints para mejor type safety',
            ];
        }

        return $issues;
    }

    protected function analyzeNPlusOne(string $content): array
    {
        $issues = [];

        // Detectar posibles N+1
        if (preg_match('/@foreach.*->/', $content) && ! preg_match('/->with\(/', $content)) {
            $issues[] = [
                'type' => 'Performance',
                'severity' => 'high',
                'message' => 'Posible problema N+1 en loop',
                'suggestion' => 'Usa eager loading con ->with()',
            ];
        }

        return $issues;
    }

    protected function formatIssue(array $issue): string
    {
        $output = "**{$issue['type']}**: {$issue['message']}\n";
        $output .= "*Sugerencia:* {$issue['suggestion']}\n\n";

        return $output;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'file' => $schema->string()
                ->description('Ruta del archivo a analizar (relativa al proyecto)'),
            'type' => $schema->enum(['full', 'security', 'best-practices', 'performance'])
                ->description('Tipo de análisis a realizar (por defecto: full)'),
        ];
    }
}
