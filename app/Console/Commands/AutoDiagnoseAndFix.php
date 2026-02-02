<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class AutoDiagnoseAndFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:auto
                            {--mode=critical : Mode: recent, critical, all}
                            {--lines=500 : Number of log lines to analyze}
                            {--fix : Apply suggested fixes automatically}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-diagnose system errors and delegate to Laravel Expert';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        // Step 1: Monitor and detect errors
        $errors = $this->monitorSystem();

        if (empty($errors)) {
            $this->components->success('✅ Sistema Saludable - No se detectaron errores');

            return self::SUCCESS;
        }

        // Step 2: Show errors summary
        $this->displayErrorsSummary($errors);

        // Step 3: Analyze each error
        if ($this->option('detailed')) {
            $this->analyzeErrors($errors);
        }

        // Step 4: Show expert recommendations
        $this->showExpertRecommendations($errors);

        // Step 5: Optional auto-fix
        if ($this->option('fix')) {
            if (confirm('¿Quieres aplicar las correcciones sugeridas?', default: false)) {
                $this->applyFixes($errors);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Display command header.
     */
    protected function displayHeader(): void
    {
        $this->newLine();
        $this->components->twoColumnDetail(
            '<fg=blue>🔍 Laravel Expert Auto-Diagnose System</>',
            '<fg=gray>v1.0</>'
        );
        $this->newLine();
    }

    /**
     * Monitor system for errors.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function monitorSystem(): array
    {
        $errors = [];

        $result = spin(
            fn () => $this->executeMonitoring(),
            'Escaneando logs del sistema...'
        );

        if (! empty($result)) {
            $errors = $result;
            $count = count($errors);
            $this->components->warn("Detectados {$count} error(es)");
        }

        return $errors;
    }

    /**
     * Execute monitoring tool.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function executeMonitoring(): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            return [];
        }

        $mode = $this->option('mode');
        $lines = (int) $this->option('lines');

        // Read and parse logs
        $content = shell_exec("tail -n {$lines} ".escapeshellarg($logPath));

        if (empty($content)) {
            return [];
        }

        return $this->parseErrors($content, $mode);
    }

    /**
     * Parse errors from log content.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseErrors(string $content, string $mode): array
    {
        $errors = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (preg_match('/\[(.*?)\]\s+(local|production)\.(\w+):\s+(.+)/', $line, $matches)) {
                $level = strtolower($matches[3]);

                // Filter by mode
                if ($mode === 'critical' && ! in_array($level, ['emergency', 'alert', 'critical', 'error'])) {
                    continue;
                }

                $errors[] = [
                    'timestamp' => $matches[1],
                    'level' => $matches[3],
                    'message' => $matches[4],
                    'priority' => $this->determinePriority($level, $matches[4]),
                ];
            }
        }

        // Sort by priority
        usort($errors, fn ($a, $b) => $this->getPriorityWeight($a['priority']) <=> $this->getPriorityWeight($b['priority']));

        // Return only last 5 errors
        return array_slice($errors, 0, 5);
    }

    /**
     * Determine error priority.
     */
    protected function determinePriority(string $level, string $message): string
    {
        if (str_contains($message, 'SQLSTATE') || str_contains($message, 'syntax error')) {
            return 'critical';
        }

        return match ($level) {
            'emergency', 'alert', 'critical' => 'critical',
            'error' => 'high',
            'warning' => 'medium',
            default => 'low',
        };
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
     * Display errors summary.
     *
     * @param  array<int, array<string, mixed>>  $errors
     */
    protected function displayErrorsSummary(array $errors): void
    {
        $this->newLine();
        $this->components->info('📊 Resumen de Errores Detectados');
        $this->newLine();

        $criticalCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'critical'));
        $highCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'high'));
        $mediumCount = count(array_filter($errors, fn ($e) => $e['priority'] === 'medium'));

        $this->components->twoColumnDetail('🔴 Críticos', (string) $criticalCount);
        $this->components->twoColumnDetail('🟠 Altos', (string) $highCount);
        $this->components->twoColumnDetail('🟡 Medios', (string) $mediumCount);

        $this->newLine();

        // Display errors table
        $tableData = array_map(function ($error, $index) {
            $icon = match ($error['priority']) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                default => '⚪',
            };

            return [
                '#'.($index + 1),
                $icon.' '.strtoupper($error['priority']),
                $error['level'],
                $this->truncate($error['message'], 60),
            ];
        }, $errors, array_keys($errors));

        table(
            ['#', 'Prioridad', 'Nivel', 'Mensaje'],
            $tableData
        );
    }

    /**
     * Analyze errors in detail.
     *
     * @param  array<int, array<string, mixed>>  $errors
     */
    protected function analyzeErrors(array $errors): void
    {
        $this->newLine();
        $this->components->info('🔬 Análisis Detallado');
        $this->newLine();

        foreach ($errors as $index => $error) {
            $this->line('Error #'.($index + 1).": {$error['message']}");
            $this->line("Nivel: {$error['level']} | Prioridad: {$error['priority']}");
            $this->newLine();
        }
    }

    /**
     * Show expert recommendations.
     *
     * @param  array<int, array<string, mixed>>  $errors
     */
    protected function showExpertRecommendations(array $errors): void
    {
        $this->newLine();
        $this->components->info('🎯 Recomendaciones del Laravel Expert');
        $this->newLine();

        $hasCritical = count(array_filter($errors, fn ($e) => $e['priority'] === 'critical')) > 0;

        if ($hasCritical) {
            warning('⚠️  Se detectaron errores CRÍTICOS que requieren atención inmediata');
            $this->newLine();
        }

        note('
📝 Workflow Recomendado:

1. Para cada error crítico/alto:
   → Ejecutar: diagnose:analyze <archivo>
   → Revisar: diagnose:review <archivo>

2. Activar Laravel Expert:
   → Desde Claude Desktop con MCP configurado
   → Prompt: "Analiza y corrige los errores detectados"

3. Implementar solución con TDD:
   → Tests primero (RED)
   → Implementación (GREEN)
   → Refactorización (REFACTOR)

4. Verificar corrección:
   → php artisan test
   → diagnose:auto --mode=critical
        ');

        $this->newLine();
        $this->components->info('💡 Tip: Usa --detailed para ver análisis detallado');
        $this->components->info('💡 Tip: Usa --fix para aplicar correcciones automáticas');
    }

    /**
     * Apply automatic fixes.
     *
     * @param  array<int, array<string, mixed>>  $errors
     */
    protected function applyFixes(array $errors): void
    {
        $this->newLine();
        $this->components->warn('🤖 Modo Auto-Fix activado');
        $this->newLine();

        info('Esta funcionalidad requiere integración con Claude Desktop MCP.');
        info('Por ahora, sigue las recomendaciones manuales mostradas arriba.');

        $this->newLine();
    }

    /**
     * Truncate string.
     */
    protected function truncate(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length).'...';
    }
}
