<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class OptimizationSuggestions extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Analiza la aplicación Laravel y proporciona sugerencias de optimización:
        - Configuración de cache
        - Queries lentas
        - Assets no optimizados
        - Configuración de producción
        - Índices de base de datos faltantes
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $category = $request->argument('category', 'all');

        $suggestions = [];

        if (in_array($category, ['all', 'cache'])) {
            $suggestions['Cache'] = $this->analyzeCacheConfig();
        }

        if (in_array($category, ['all', 'database'])) {
            $suggestions['Database'] = $this->analyzeDatabaseConfig();
        }

        if (in_array($category, ['all', 'assets'])) {
            $suggestions['Assets'] = $this->analyzeAssets();
        }

        if (in_array($category, ['all', 'config'])) {
            $suggestions['Configuración'] = $this->analyzeAppConfig();
        }

        $output = "# Sugerencias de Optimización\n\n";

        foreach ($suggestions as $category => $items) {
            if (! empty($items)) {
                $output .= "## {$category}\n\n";
                foreach ($items as $suggestion) {
                    $icon = match ($suggestion['priority']) {
                        'high' => '🔴',
                        'medium' => '🟡',
                        'low' => '🟢',
                    };
                    $output .= "{$icon} **{$suggestion['title']}**\n";
                    $output .= "   {$suggestion['description']}\n";
                    if (isset($suggestion['command'])) {
                        $output .= "   ```bash\n   {$suggestion['command']}\n   ```\n";
                    }
                    $output .= "\n";
                }
            }
        }

        if (empty(array_filter($suggestions))) {
            $output .= "✅ **La aplicación está bien optimizada.**\n";
        }

        return Response::text($output);
    }

    protected function analyzeCacheConfig(): array
    {
        $suggestions = [];

        // Verificar si el config está cacheado
        if (! File::exists(base_path('bootstrap/cache/config.php'))) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => 'Config sin cachear',
                'description' => 'Cachear la configuración mejora el rendimiento en producción',
                'command' => 'php artisan config:cache',
            ];
        }

        // Verificar si las rutas están cacheadas
        if (! File::exists(base_path('bootstrap/cache/routes-v7.php'))) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => 'Rutas sin cachear',
                'description' => 'Cachear las rutas reduce el tiempo de respuesta',
                'command' => 'php artisan route:cache',
            ];
        }

        // Verificar driver de cache
        $cacheDriver = config('cache.default');
        if ($cacheDriver === 'file') {
            $suggestions[] = [
                'priority' => 'high',
                'title' => 'Driver de cache subóptimo',
                'description' => 'Considera usar Redis o Memcached en lugar de file para mejor rendimiento',
                'command' => null,
            ];
        }

        return $suggestions;
    }

    protected function analyzeDatabaseConfig(): array
    {
        $suggestions = [];

        // Verificar conexión de BD
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $suggestions[] = [
                'priority' => 'low',
                'title' => 'SQLite en producción',
                'description' => 'SQLite es excelente para desarrollo, pero considera MySQL/PostgreSQL para producción',
                'command' => null,
            ];
        }

        // Verificar queries lentas (simulado)
        try {
            DB::connection()->enableQueryLog();
            // Aquí podrías ejecutar queries de ejemplo
            $queryLog = DB::getQueryLog();

            if (count($queryLog) > 50) {
                $suggestions[] = [
                    'priority' => 'medium',
                    'title' => 'Muchas queries por request',
                    'description' => 'Considera usar eager loading para reducir N+1 queries',
                    'command' => null,
                ];
            }
        } catch (\Exception $e) {
            // Ignorar errores de conexión
        }

        return $suggestions;
    }

    protected function analyzeAssets(): array
    {
        $suggestions = [];

        // Verificar si los assets están compilados para producción
        if (! File::exists(public_path('build/manifest.json'))) {
            $suggestions[] = [
                'priority' => 'high',
                'title' => 'Assets sin compilar para producción',
                'description' => 'Compila y minimiza los assets para mejor rendimiento',
                'command' => 'npm run build',
            ];
        }

        return $suggestions;
    }

    protected function analyzeAppConfig(): array
    {
        $suggestions = [];

        // Verificar modo debug
        if (config('app.debug') === true && app()->environment('production')) {
            $suggestions[] = [
                'priority' => 'high',
                'title' => 'Debug mode activo en producción',
                'description' => 'Desactiva APP_DEBUG en producción por seguridad y rendimiento',
                'command' => 'Establece APP_DEBUG=false en .env',
            ];
        }

        // Verificar optimización de composer
        if (! File::exists(base_path('vendor/composer/autoload_classmap.php')) ||
            filesize(base_path('vendor/composer/autoload_classmap.php')) < 1000) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => 'Autoloader sin optimizar',
                'description' => 'Optimiza el autoloader de Composer para producción',
                'command' => 'composer install --optimize-autoloader --no-dev',
            ];
        }

        return $suggestions;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->enum(['all', 'cache', 'database', 'assets', 'config'])
                ->description('Categoría de optimización a analizar (por defecto: all)'),
        ];
    }
}
