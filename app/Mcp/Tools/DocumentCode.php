<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DocumentCode extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Analiza código PHP/Laravel y genera documentación completa siguiendo estándares IEEE:
        - PHPDoc blocks para todos los métodos
        - Type hints estrictos
        - Descripción detallada de parámetros y retornos
        - Ejemplos de uso cuando aplica
        - Documentación de excepciones
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $file = $request->argument('file');
        $includeExamples = $request->argument('include_examples', true);

        if (! file_exists(base_path($file))) {
            return Response::text("❌ Error: El archivo '{$file}' no existe.");
        }

        $content = file_get_contents(base_path($file));
        $analysis = $this->analyzeCode($content, $file);

        $output = "# Análisis de Documentación: {$file}\n\n";

        // Resumen
        $output .= "## 📊 Resumen Ejecutivo\n\n";
        $output .= "**Total de métodos:** {$analysis['total_methods']}\n";
        $output .= "**Sin PHPDoc:** {$analysis['missing_phpdoc']}\n";
        $output .= "**Sin type hints:** {$analysis['missing_types']}\n";
        $output .= "**Calidad de documentación:** {$analysis['doc_quality']}%\n\n";

        // Issues encontrados
        if (! empty($analysis['issues'])) {
            $output .= "## 🔍 Issues Encontrados\n\n";

            foreach ($analysis['issues'] as $priority => $issues) {
                if (empty($issues)) {
                    continue;
                }

                $icon = match ($priority) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                };

                $output .= "### {$icon} Prioridad ".ucfirst($priority)."\n\n";

                foreach ($issues as $issue) {
                    $output .= "**Línea {$issue['line']}**: {$issue['description']}\n";
                    $output .= "```php\n{$issue['code']}\n```\n\n";
                }
            }
        }

        // Sugerencias de documentación
        $output .= "## ✍️ Documentación Sugerida\n\n";
        $output .= $this->generateDocumentationSuggestions($analysis, $includeExamples);

        // Template de PHPDoc
        $output .= "## 📝 Template PHPDoc Recomendado\n\n";
        $output .= "```php\n";
        $output .= $this->getPhpDocTemplate();
        $output .= "```\n\n";

        // Checklist
        $output .= "## ✅ Checklist de Documentación\n\n";
        $output .= "- [ ] Todos los métodos públicos tienen PHPDoc\n";
        $output .= "- [ ] Todos los parámetros están documentados con tipos\n";
        $output .= "- [ ] Los tipos de retorno están especificados\n";
        $output .= "- [ ] Las excepciones están documentadas con @throws\n";
        $output .= "- [ ] Métodos complejos tienen ejemplos de uso\n";
        $output .= "- [ ] Properties de clase están documentadas\n";
        $output .= "- [ ] Type hints estrictos en todo el código\n";
        $output .= "- [ ] Descripciones claras y concisas\n";

        return Response::text($output);
    }

    /**
     * Analyze code for documentation issues.
     *
     * @param  string  $content  The file content
     * @param  string  $file  The file path
     * @return array<string, mixed>
     */
    protected function analyzeCode(string $content, string $file): array
    {
        $lines = explode("\n", $content);
        $issues = ['high' => [], 'medium' => [], 'low' => []];

        $totalMethods = 0;
        $missingPhpDoc = 0;
        $missingTypes = 0;

        // Analizar métodos
        foreach ($lines as $lineNum => $line) {
            $lineNumber = $lineNum + 1;

            // Detectar métodos públicos
            if (preg_match('/^\s*public\s+function\s+(\w+)\s*\(/', $line, $matches)) {
                $totalMethods++;
                $methodName = $matches[1];

                // Check for PHPDoc above
                $hasPhpDoc = false;
                for ($i = $lineNum - 1; $i >= max(0, $lineNum - 10); $i--) {
                    if (str_contains($lines[$i], '/**') || str_contains($lines[$i], '* @')) {
                        $hasPhpDoc = true;
                        break;
                    }
                    if (trim($lines[$i]) === '') {
                        continue;
                    }
                    break;
                }

                if (! $hasPhpDoc && $methodName !== '__construct') {
                    $missingPhpDoc++;
                    $issues['high'][] = [
                        'line' => $lineNumber,
                        'description' => "Método '{$methodName}' sin PHPDoc",
                        'code' => trim($line),
                    ];
                }

                // Check for return type
                if (! preg_match('/\):\s*\w+/', $line)) {
                    $missingTypes++;
                    $issues['medium'][] = [
                        'line' => $lineNumber,
                        'description' => "Método '{$methodName}' sin tipo de retorno declarado",
                        'code' => trim($line),
                    ];
                }
            }

            // Detectar properties sin documentar
            if (preg_match('/^\s*(public|protected|private)\s+(\$\w+)/', $line, $matches)) {
                $hasPropertyDoc = false;
                for ($i = $lineNum - 1; $i >= max(0, $lineNum - 5); $i--) {
                    if (str_contains($lines[$i], '/**') || str_contains($lines[$i], '* @var')) {
                        $hasPropertyDoc = true;
                        break;
                    }
                    if (trim($lines[$i]) === '') {
                        continue;
                    }
                    break;
                }

                if (! $hasPropertyDoc && ! str_contains($line, 'const ')) {
                    $issues['low'][] = [
                        'line' => $lineNumber,
                        'description' => 'Property sin documentación @var',
                        'code' => trim($line),
                    ];
                }
            }
        }

        // Calculate quality
        $docQuality = $totalMethods > 0
            ? round((($totalMethods - $missingPhpDoc) / $totalMethods) * 100)
            : 100;

        return [
            'total_methods' => $totalMethods,
            'missing_phpdoc' => $missingPhpDoc,
            'missing_types' => $missingTypes,
            'doc_quality' => $docQuality,
            'issues' => $issues,
        ];
    }

    /**
     * Generate documentation suggestions.
     *
     * @param  array<string, mixed>  $analysis
     */
    protected function generateDocumentationSuggestions(array $analysis, bool $includeExamples): string
    {
        $output = '';

        if ($analysis['missing_phpdoc'] > 0) {
            $output .= "### Para métodos sin PHPDoc:\n\n";
            $output .= "Agrega documentación completa siguiendo este patrón:\n\n";
            $output .= "```php\n";
            $output .= "/**\n";
            $output .= " * Brief description of what the method does\n";
            $output .= " *\n";
            $output .= " * Longer description if needed, explaining complex logic,\n";
            $output .= " * business rules, or important considerations.\n";
            $output .= " *\n";
            $output .= " * @param Type \$param Description of the parameter\n";
            $output .= " * @param array<string, mixed> \$options Configuration options\n";
            $output .= " * @return Type Description of what is returned\n";
            $output .= " * @throws ExceptionType When this exception is thrown\n";

            if ($includeExamples) {
                $output .= " *\n";
                $output .= " * @example\n";
                $output .= " * \$result = \$instance->methodName('value', ['option' => true]);\n";
            }

            $output .= " */\n";
            $output .= "public function methodName(string \$param, array \$options = []): ReturnType\n";
            $output .= "{\n";
            $output .= "    // Implementation\n";
            $output .= "}\n";
            $output .= "```\n\n";
        }

        if ($analysis['missing_types'] > 0) {
            $output .= "### Para métodos sin type hints:\n\n";
            $output .= "Agrega tipos estrictos en:\n";
            $output .= "- Parámetros: `function method(string \$param, ?int \$optional = null)`\n";
            $output .= "- Retornos: `function method(): ReturnType` o `function method(): void`\n";
            $output .= "- Usa tipos nullable cuando aplica: `?Type`\n";
            $output .= "- Usa union types PHP 8: `string|int|null`\n";
            $output .= "- Usa array shapes: `array<string, mixed>` o `array<int, User>`\n\n";
        }

        $output .= "### Estándares de Calidad:\n\n";
        $output .= "1. **Descripción corta**: Primera línea explica QUÉ hace el método\n";
        $output .= "2. **Descripción larga**: Párrafo explicando CÓMO y POR QUÉ (si es complejo)\n";
        $output .= "3. **@param**: Todos los parámetros con tipo y descripción\n";
        $output .= "4. **@return**: Tipo de retorno y qué representa\n";
        $output .= "5. **@throws**: Todas las excepciones que puede lanzar\n";
        $output .= "6. **@example**: Para métodos públicos complejos\n\n";

        return $output;
    }

    /**
     * Get PHPDoc template.
     */
    protected function getPhpDocTemplate(): string
    {
        return <<<'PHP'
/**
 * Brief one-line description of the method
 *
 * Optional longer description that provides more context about what
 * this method does, when to use it, and any important considerations.
 * Use this space to explain complex business logic or algorithms.
 *
 * @param string $requiredParam Description of this required parameter
 * @param int|null $optionalParam Description of this optional parameter (default: null)
 * @param array<string, mixed> $options Configuration array with specific keys
 * @return bool True on success, false on failure
 * @throws \InvalidArgumentException When the required parameter is empty
 * @throws \RuntimeException When the operation cannot be completed
 *
 * @example
 * $service = new MyService();
 * $result = $service->processData('input', 42, ['option' => 'value']);
 * if ($result) {
 *     // Success handling
 * }
 */
public function processData(
    string $requiredParam,
    ?int $optionalParam = null,
    array $options = []
): bool {
    // Implementation
}
PHP;
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
                ->description('Ruta del archivo a documentar (relativa al proyecto)'),
            'include_examples' => $schema->boolean()
                ->description('Incluir ejemplos de uso en las sugerencias (por defecto: true)'),
        ];
    }
}
