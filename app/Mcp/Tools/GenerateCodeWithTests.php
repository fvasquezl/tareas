<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateCodeWithTests extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Genera código PHP/Laravel siguiendo TDD estricto:
        1. Primero genera los tests (Feature + Unit)
        2. Luego implementa el código que pasa los tests
        3. Incluye documentación completa
        4. Sigue principios SOLID y Laravel best practices
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $type = $request->argument('type'); // controller, model, service, etc.
        $name = $request->argument('name');
        $description = $request->argument('description');
        $includeFeatureTest = $request->argument('include_feature_test', true);

        $output = "# Generación de Código con TDD\n\n";
        $output .= "**Tipo:** {$type}\n";
        $output .= "**Nombre:** {$name}\n";
        $output .= "**Descripción:** {$description}\n\n";

        // Paso 1: Generar Tests (RED phase)
        $output .= "## Paso 1: Tests (RED Phase)\n\n";
        $output .= "### 📝 Tests a crear primero:\n\n";

        if ($includeFeatureTest) {
            $featureTest = $this->generateFeatureTest($type, $name, $description);
            $output .= "#### Feature Test\n";
            $output .= "**Archivo:** `tests/Feature/{$name}Test.php`\n\n";
            $output .= "```php\n{$featureTest}\n```\n\n";
        }

        $unitTest = $this->generateUnitTest($type, $name, $description);
        $output .= "#### Unit Test\n";
        $output .= "**Archivo:** `tests/Unit/{$name}Test.php`\n\n";
        $output .= "```php\n{$unitTest}\n```\n\n";

        // Paso 2: Implementación (GREEN phase)
        $output .= "## Paso 2: Implementación (GREEN Phase)\n\n";
        $implementation = $this->generateImplementation($type, $name, $description);
        $output .= "**Archivo:** `{$this->getFilePath($type, $name)}`\n\n";
        $output .= "```php\n{$implementation}\n```\n\n";

        // Paso 3: Comandos para ejecutar
        $output .= "## Paso 3: Ejecutar Tests\n\n";
        $output .= "```bash\n";
        $output .= "# Ejecutar tests específicos\n";
        $output .= "vendor/bin/sail artisan test --filter={$name}Test\n\n";
        $output .= "# Ver coverage\n";
        $output .= "vendor/bin/sail artisan test --coverage --min=85\n";
        $output .= "```\n\n";

        // Paso 4: Checklist
        $output .= "## ✅ Checklist de Calidad\n\n";
        $output .= "- [ ] Tests escritos ANTES del código\n";
        $output .= "- [ ] Tests fallan inicialmente (RED)\n";
        $output .= "- [ ] Implementación pasa todos los tests (GREEN)\n";
        $output .= "- [ ] Código refactorizado y optimizado\n";
        $output .= "- [ ] Coverage >= 85%\n";
        $output .= "- [ ] PHPDoc completo\n";
        $output .= "- [ ] Type hints estrictos\n";
        $output .= "- [ ] PSR-12 compliance\n";
        $output .= "- [ ] Sin N+1 queries\n";
        $output .= "- [ ] Validación de seguridad\n";

        return Response::text($output);
    }

    protected function generateFeatureTest(string $type, string $name, string $description): string
    {
        return <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for {$name}
 *
 * @group {$type}
 */
class {$name}Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: {$description}
     *
     * @return void
     */
    public function test_can_perform_main_action(): void
    {
        // Arrange
        \$user = \User::factory()->create();

        // Act
        \$response = \$this->actingAs(\$user)->post('/api/endpoint', [
            'data' => 'test data',
        ]);

        // Assert
        \$response->assertStatus(200);
        \$this->assertDatabaseHas('table_name', [
            'field' => 'expected_value',
        ]);
    }

    /**
     * Test: Validation fails with invalid data
     *
     * @return void
     */
    public function test_validation_fails_with_invalid_data(): void
    {
        \$user = \User::factory()->create();

        \$response = \$this->actingAs(\$user)->post('/api/endpoint', [
            'data' => '', // Invalid
        ]);

        \$response->assertStatus(422);
        \$response->assertJsonValidationErrors(['data']);
    }

    /**
     * Test: Unauthorized access is prevented
     *
     * @return void
     */
    public function test_unauthorized_access_is_prevented(): void
    {
        \$response = \$this->post('/api/endpoint');

        \$response->assertStatus(401);
    }
}
PHP;
    }

    protected function generateUnitTest(string $type, string $name, string $description): string
    {
        return <<<PHP
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {$name}
 *
 * @group {$type}
 * @group unit
 */
class {$name}Test extends TestCase
{
    /**
     * Test: Instance can be created
     *
     * @return void
     */
    public function test_instance_can_be_created(): void
    {
        \$instance = new {$name}();

        \$this->assertInstanceOf({$name}::class, \$instance);
    }

    /**
     * Test: Method returns expected result
     *
     * @return void
     */
    public function test_method_returns_expected_result(): void
    {
        \$instance = new {$name}();

        \$result = \$instance->methodName('input');

        \$this->assertEquals('expected', \$result);
    }

    /**
     * Test: Method throws exception on invalid input
     *
     * @return void
     */
    public function test_method_throws_exception_on_invalid_input(): void
    {
        \$this->expectException(\InvalidArgumentException::class);

        \$instance = new {$name}();
        \$instance->methodName('');
    }
}
PHP;
    }

    protected function generateImplementation(string $type, string $name, string $description): string
    {
        return match ($type) {
            'service' => $this->generateService($name, $description),
            'controller' => $this->generateController($name, $description),
            'model' => $this->generateModel($name, $description),
            default => $this->generateClass($name, $description),
        };
    }

    protected function generateService(string $name, string $description): string
    {
        return <<<PHP
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * {$name} Service
 *
 * {$description}
 *
 * @package App\Services
 * @author Laravel Expert (Fortune 500 Certified)
 */
class {$name}
{
    /**
     * Execute the main service action
     *
     * @param array<string, mixed> \$data The input data
     * @return array<string, mixed> The result data
     * @throws \InvalidArgumentException When data is invalid
     */
    public function execute(array \$data): array
    {
        \$this->validate(\$data);

        return DB::transaction(function () use (\$data) {
            Log::info("{$name}: Processing request", ['data' => \$data]);

            // TODO: Implement business logic
            \$result = \$this->processData(\$data);

            Log::info("{$name}: Request processed successfully");

            return \$result;
        });
    }

    /**
     * Validate input data
     *
     * @param array<string, mixed> \$data
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validate(array \$data): void
    {
        if (empty(\$data)) {
            throw new \InvalidArgumentException('Data cannot be empty');
        }

        // Add more validation as needed
    }

    /**
     * Process the data
     *
     * @param array<string, mixed> \$data
     * @return array<string, mixed>
     */
    protected function processData(array \$data): array
    {
        // Implementation here
        return [
            'success' => true,
            'data' => \$data,
        ];
    }
}
PHP;
    }

    protected function generateController(string $name, string $description): string
    {
        return <<<PHP
<?php

namespace App\Http\Controllers;

use App\Http\Requests\\{$name}Request;
use Illuminate\Http\JsonResponse;

/**
 * {$name} Controller
 *
 * {$description}
 *
 * @package App\Http\Controllers
 */
class {$name}Controller extends Controller
{
    /**
     * Handle the incoming request
     *
     * @param {$name}Request \$request
     * @return JsonResponse
     */
    public function __invoke({$name}Request \$request): JsonResponse
    {
        // TODO: Implement controller logic

        return response()->json([
            'success' => true,
            'data' => \$request->validated(),
        ]);
    }
}
PHP;
    }

    protected function generateModel(string $name, string $description): string
    {
        return <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * {$name} Model
 *
 * {$description}
 *
 * @package App\Models
 */
class {$name} extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected \$fillable = [
        // Add fillable attributes
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            // Add casts
        ];
    }
}
PHP;
    }

    protected function generateClass(string $name, string $description): string
    {
        return <<<PHP
<?php

namespace App;

/**
 * {$name}
 *
 * {$description}
 *
 * @package App
 */
class {$name}
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize
    }

    /**
     * Execute main action
     *
     * @param mixed \$input
     * @return mixed
     */
    public function execute(mixed \$input): mixed
    {
        // TODO: Implementation
        return \$input;
    }
}
PHP;
    }

    protected function getFilePath(string $type, string $name): string
    {
        return match ($type) {
            'service' => "app/Services/{$name}.php",
            'controller' => "app/Http/Controllers/{$name}Controller.php",
            'model' => "app/Models/{$name}.php",
            default => "app/{$name}.php",
        };
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->enum(['service', 'controller', 'model', 'class'])
                ->description('Tipo de componente a generar'),
            'name' => $schema->string()
                ->description('Nombre del componente (sin sufijos)'),
            'description' => $schema->string()
                ->description('Descripción de la funcionalidad'),
            'include_feature_test' => $schema->boolean()
                ->description('Incluir feature test además del unit test (por defecto: true)'),
        ];
    }
}
