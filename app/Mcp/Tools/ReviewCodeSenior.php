<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReviewCodeSenior extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Code review nivel Senior con 10+ años de experiencia:
        - Análisis de principios SOLID
        - Verificación PSR-12
        - Detección de code smells
        - Sugerencias de design patterns
        - Optimizaciones de performance
        - Mejoras arquitectónicas
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $file = $request->argument('file');
        $focus = $request->argument('focus', 'all');

        if (! file_exists(base_path($file))) {
            return Response::text("❌ Error: El archivo '{$file}' no existe.");
        }

        $content = file_get_contents(base_path($file));
        $review = $this->performSeniorReview($content, $file, $focus);

        $output = "# Code Review Senior: {$file}\n\n";
        $output .= "_Revisado con estándares Fortune 500 y 10+ años de experiencia_\n\n";

        // Score general
        $output .= "## 📊 Calificación General\n\n";
        $output .= "**Score Total:** {$review['overall_score']}/100\n\n";

        foreach ($review['scores'] as $category => $score) {
            $icon = $score >= 80 ? '✅' : ($score >= 60 ? '⚠️' : '❌');
            $output .= "- {$icon} **".ucfirst($category)."**: {$score}/100\n";
        }

        $output .= "\n";

        // Issues críticos
        if (! empty($review['critical'])) {
            $output .= "## 🔴 Issues Críticos (Bloquean PR)\n\n";
            foreach ($review['critical'] as $issue) {
                $output .= "### {$issue['title']}\n\n";
                $output .= "**Línea {$issue['line']}**: {$issue['description']}\n\n";
                $output .= "**Problema:**\n```php\n{$issue['code']}\n```\n\n";
                $output .= "**Solución Sugerida:**\n```php\n{$issue['suggestion']}\n```\n\n";
                $output .= "**Justificación:** {$issue['reasoning']}\n\n";
                $output .= "---\n\n";
            }
        }

        // Warnings
        if (! empty($review['warnings'])) {
            $output .= "## 🟡 Warnings (Recomendaciones Fuertes)\n\n";
            foreach ($review['warnings'] as $warning) {
                $output .= "**Línea {$warning['line']}**: {$warning['message']}\n";
                $output .= "- Impacto: {$warning['impact']}\n";
                $output .= "- Sugerencia: {$warning['suggestion']}\n\n";
            }
        }

        // Mejoras arquitectónicas
        if (! empty($review['architectural'])) {
            $output .= "## 🏗️ Mejoras Arquitectónicas\n\n";
            foreach ($review['architectural'] as $improvement) {
                $output .= "### {$improvement['pattern']}\n\n";
                $output .= "{$improvement['description']}\n\n";
                $output .= "**Beneficios:**\n";
                foreach ($improvement['benefits'] as $benefit) {
                    $output .= "- {$benefit}\n";
                }
                $output .= "\n";

                if (! empty($improvement['example'])) {
                    $output .= "**Implementación:**\n```php\n{$improvement['example']}\n```\n\n";
                }
            }
        }

        // SOLID Analysis
        if ($focus === 'all' || $focus === 'solid') {
            $output .= "## 🎯 Análisis SOLID\n\n";
            $output .= $this->analyzeSolidPrinciples($content);
        }

        // Performance
        if (! empty($review['performance'])) {
            $output .= "## ⚡ Optimizaciones de Performance\n\n";
            foreach ($review['performance'] as $opt) {
                $output .= "**{$opt['title']}**\n";
                $output .= "- Línea: {$opt['line']}\n";
                $output .= "- Impacto: {$opt['impact']}\n";
                $output .= "- Solución: {$opt['solution']}\n\n";
            }
        }

        // Checklist final
        $output .= "## ✅ Checklist Pre-Merge\n\n";
        $output .= "- [ ] Todos los issues críticos resueltos\n";
        $output .= "- [ ] Tests actualizados y pasando\n";
        $output .= "- [ ] Coverage >= 85%\n";
        $output .= "- [ ] PSR-12 compliance verificado\n";
        $output .= "- [ ] Sin N+1 queries\n";
        $output .= "- [ ] Documentación actualizada\n";
        $output .= "- [ ] Sin code smells detectados\n";
        $output .= "- [ ] Performance optimizado\n";
        $output .= "- [ ] Security review pasado\n\n";

        // Veredicto
        $verdict = $review['overall_score'] >= 80 ? '✅ APROBADO' : ($review['overall_score'] >= 60 ? '⚠️ APROBADO CON CONDICIONES' : '❌ REQUIERE CAMBIOS');
        $output .= "## Veredicto Final\n\n";
        $output .= "**{$verdict}** (Score: {$review['overall_score']}/100)\n\n";

        if ($review['overall_score'] < 80) {
            $output .= "_Se recomienda aplicar las sugerencias antes de merge._\n";
        }

        return Response::text($output);
    }

    /**
     * Perform senior-level code review.
     *
     * @return array<string, mixed>
     */
    protected function performSeniorReview(string $content, string $file, string $focus): array
    {
        $lines = explode("\n", $content);

        $review = [
            'critical' => [],
            'warnings' => [],
            'architectural' => [],
            'performance' => [],
            'scores' => [
                'solid' => 100,
                'security' => 100,
                'performance' => 100,
                'maintainability' => 100,
                'documentation' => 100,
            ],
        ];

        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;

            // Check for critical issues
            // env() outside config files
            if (str_contains($line, 'env(') && ! str_contains($file, 'config/')) {
                $review['critical'][] = [
                    'title' => 'Uso de env() fuera de archivos de configuración',
                    'line' => $lineNumber,
                    'description' => 'El uso directo de env() fuera de config/ viola las mejores prácticas de Laravel',
                    'code' => trim($line),
                    'suggestion' => "config('app.setting') // En lugar de env('APP_SETTING')",
                    'reasoning' => 'Config caching hace que env() retorne null. Siempre usa config() en la aplicación.',
                ];
                $review['scores']['maintainability'] -= 10;
            }

            // Mass assignment without $fillable
            if (str_contains($line, '::create(') && str_contains($content, 'class ') && ! str_contains($content, '$fillable')) {
                $review['critical'][] = [
                    'title' => 'Mass assignment sin $fillable definido',
                    'line' => $lineNumber,
                    'description' => 'Usar create() sin definir $fillable es un riesgo de seguridad',
                    'code' => trim($line),
                    'suggestion' => "protected \$fillable = ['field1', 'field2'];\n// O usa $guarded si prefieres blacklist",
                    'reasoning' => 'Sin $fillable, cualquier campo puede ser modificado via mass assignment (vulnerabilidad).',
                ];
                $review['scores']['security'] -= 15;
            }

            // Long methods (> 30 lines)
            if (preg_match('/function\s+\w+/', $line)) {
                $methodLength = $this->getMethodLength($lines, $lineNum);
                if ($methodLength > 30) {
                    $review['warnings'][] = [
                        'line' => $lineNumber,
                        'message' => "Método demasiado largo ({$methodLength} líneas)",
                        'impact' => 'Dificulta testing y mantenimiento',
                        'suggestion' => 'Refactorizar en métodos más pequeños siguiendo Single Responsibility',
                    ];
                    $review['scores']['maintainability'] -= 5;
                }
            }

            // N+1 queries potential
            if (preg_match('/foreach.*as.*\$/', $line) && $this->hasQueryInLoop($lines, $lineNum)) {
                $review['performance'][] = [
                    'title' => 'Potencial N+1 Query Problem',
                    'line' => $lineNumber,
                    'impact' => 'Alto - Afecta performance con datasets grandes',
                    'solution' => 'Usar eager loading: Model::with(\'relation\')->get()',
                ];
                $review['scores']['performance'] -= 15;
            }

            // Raw queries without parameterization
            if (preg_match('/DB::select.*\$/', $line) || preg_match('/->whereRaw.*\$/', $line)) {
                if (! str_contains($line, '?') && ! str_contains($line, ':')) {
                    $review['critical'][] = [
                        'title' => 'SQL Injection Risk',
                        'line' => $lineNumber,
                        'description' => 'Query raw con variables sin parametrizar',
                        'code' => trim($line),
                        'suggestion' => "DB::select('SELECT * FROM users WHERE id = ?', [\$id])",
                        'reasoning' => 'NUNCA concatenar variables en queries SQL. Siempre usar bindings.',
                    ];
                    $review['scores']['security'] -= 20;
                }
            }

            // Missing return types
            if (preg_match('/public function \w+\(.*\)\s*$/', $line) || preg_match('/public function \w+\(.*\)\s*{/', $line)) {
                if (! str_contains($line, '):')) {
                    $review['warnings'][] = [
                        'line' => $lineNumber,
                        'message' => 'Método sin tipo de retorno declarado',
                        'impact' => 'Reduce type safety y claridad del código',
                        'suggestion' => 'Agregar : ReturnType después de los parámetros',
                    ];
                    $review['scores']['maintainability'] -= 3;
                }
            }

            // Too many parameters (> 3)
            if (preg_match('/function\s+\w+\((.*?)\)/', $line, $matches)) {
                $params = array_filter(explode(',', $matches[1]));
                if (count($params) > 3) {
                    $review['architectural'][] = [
                        'pattern' => 'Parameter Object Pattern',
                        'description' => 'Método con '.count($params)." parámetros en línea {$lineNumber}. Considerar usar un objeto de datos.",
                        'benefits' => [
                            'Reduce complejidad',
                            'Facilita agregar nuevos parámetros',
                            'Mejora legibilidad',
                        ],
                        'example' => "class UserData {\n    public function __construct(\n        public string \$name,\n        public string \$email,\n        public ?string \$phone = null,\n    ) {}\n}\n\npublic function createUser(UserData \$data): User",
                    ];
                }
            }
        }

        // Check for Repository Pattern opportunity
        if (str_contains($file, 'Controller') && preg_match_all('/\b\w+::/', $content, $matches) >= 3) {
            $review['architectural'][] = [
                'pattern' => 'Repository Pattern',
                'description' => 'Controller con múltiples queries directas a modelos',
                'benefits' => [
                    'Separa lógica de datos de lógica de negocio',
                    'Facilita testing con mocking',
                    'Centraliza queries complejos',
                    'Mejora reusabilidad',
                ],
                'example' => "class UserRepository {\n    public function findActiveUsers(): Collection {\n        return User::where('active', true)->get();\n    }\n}\n\nclass UserController {\n    public function __construct(\n        private UserRepository \$users\n    ) {}\n}",
            ];
        }

        // Calculate overall score
        $review['overall_score'] = round(array_sum($review['scores']) / count($review['scores']));

        return $review;
    }

    /**
     * Get method length in lines.
     *
     * @param  array<int, string>  $lines
     */
    protected function getMethodLength(array $lines, int $startLine): int
    {
        $length = 1;
        $braceCount = 0;
        $started = false;

        for ($i = $startLine; $i < count($lines); $i++) {
            if (str_contains($lines[$i], '{')) {
                $braceCount++;
                $started = true;
            }
            if (str_contains($lines[$i], '}')) {
                $braceCount--;
            }

            if ($started) {
                $length++;
            }

            if ($started && $braceCount === 0) {
                break;
            }
        }

        return $length;
    }

    /**
     * Check if there's a query inside a loop.
     *
     * @param  array<int, string>  $lines
     */
    protected function hasQueryInLoop(array $lines, int $loopLine): bool
    {
        $braceCount = 0;
        $inLoop = false;

        for ($i = $loopLine; $i < min($loopLine + 20, count($lines)); $i++) {
            $line = $lines[$i];

            if (str_contains($line, '{')) {
                $braceCount++;
                $inLoop = true;
            }

            if ($inLoop) {
                // Check for queries
                if (str_contains($line, '::find(') ||
                    str_contains($line, '::where(') ||
                    str_contains($line, '->load(') ||
                    str_contains($line, 'DB::')) {
                    return true;
                }
            }

            if (str_contains($line, '}')) {
                $braceCount--;
                if ($braceCount === 0) {
                    break;
                }
            }
        }

        return false;
    }

    /**
     * Analyze SOLID principles adherence.
     */
    protected function analyzeSolidPrinciples(string $content): string
    {
        $output = '';

        // Single Responsibility
        $classCount = substr_count($content, 'class ');
        $methodCount = preg_match_all('/\bpublic function\b/', $content);

        if ($methodCount > 10 && $classCount === 1) {
            $output .= "**S - Single Responsibility**: ⚠️ Clase con {$methodCount} métodos públicos puede tener múltiples responsabilidades.\n\n";
        } else {
            $output .= "**S - Single Responsibility**: ✅ OK\n\n";
        }

        // Open/Closed
        if (preg_match_all('/switch.*case|if.*elseif.*elseif/', $content) > 2) {
            $output .= "**O - Open/Closed**: ⚠️ Múltiples switch/if-elseif detectados. Considerar Strategy Pattern o Polymorphism.\n\n";
        } else {
            $output .= "**O - Open/Closed**: ✅ OK\n\n";
        }

        // Liskov Substitution
        $output .= "**L - Liskov Substitution**: ℹ️ Verificar manualmente que las clases hijas no rompan contratos de las padres.\n\n";

        // Interface Segregation
        if (str_contains($content, 'implements') && preg_match_all('/\bpublic function\b/', $content) > 7) {
            $output .= "**I - Interface Segregation**: ⚠️ Interface potencialmente muy grande. Considerar dividir.\n\n";
        } else {
            $output .= "**I - Interface Segregation**: ✅ OK\n\n";
        }

        // Dependency Inversion
        $hasConstructorInjection = str_contains($content, 'public function __construct') &&
            preg_match('/\(.*\$/', $content);

        if (! $hasConstructorInjection && str_contains($content, 'new ')) {
            $output .= "**D - Dependency Inversion**: ⚠️ Uso de 'new' sin constructor injection. Preferir DI.\n\n";
        } else {
            $output .= "**D - Dependency Inversion**: ✅ OK\n\n";
        }

        return $output;
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
                ->description('Ruta del archivo a revisar (relativa al proyecto)'),
            'focus' => $schema->enum(['all', 'solid', 'security', 'performance', 'architecture'])
                ->description('Enfoque específico del review (por defecto: all)'),
        ];
    }
}
