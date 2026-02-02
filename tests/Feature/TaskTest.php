<?php

use App\Enums\TaskPriority;
use App\Models\Task;
use App\Models\User;

uses()->group('tasks');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('puede crear una tarea', function () {
    $task = Task::factory()->create([
        'user_id' => $this->user->id,
        'title' => 'Test Task',
    ]);

    expect($task)
        ->title->toBe('Test Task')
        ->user_id->toBe($this->user->id);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'user_id' => $this->user->id,
    ]);
});

test('tarea pertenece a un usuario', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);

    expect($task->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($this->user->id);
});

test('usuario tiene muchas tareas', function () {
    Task::factory(3)->create(['user_id' => $this->user->id]);

    expect($this->user->tasks)
        ->toHaveCount(3);
});

test('prioridad se castea a enum', function () {
    $task = Task::factory()->create([
        'priority' => TaskPriority::High,
    ]);

    expect($task->priority)
        ->toBeInstanceOf(TaskPriority::class)
        ->toBe(TaskPriority::High);
});

test('tarea tiene prioridad por defecto medium', function () {
    $task = new Task([
        'title' => 'Test Task',
        'user_id' => $this->user->id,
    ]);
    $task->save();

    $task->refresh();
    expect($task->priority)->toBe(TaskPriority::Medium);
});

test('tarea tiene completed false por defecto', function () {
    $task = new Task([
        'title' => 'Test Task',
        'user_id' => $this->user->id,
    ]);
    $task->save();

    $task->refresh();
    expect($task->completed)->toBeFalse();
});

test('puede filtrar tareas completadas', function () {
    Task::factory(3)->create(['completed' => true, 'user_id' => $this->user->id]);
    Task::factory(2)->create(['completed' => false, 'user_id' => $this->user->id]);

    $completedTasks = Task::where('completed', true)->count();
    $pendingTasks = Task::where('completed', false)->count();

    expect($completedTasks)->toBe(3)
        ->and($pendingTasks)->toBe(2);
});

test('puede filtrar tareas por prioridad', function () {
    Task::factory(2)->create(['priority' => TaskPriority::High, 'user_id' => $this->user->id]);
    Task::factory(1)->create(['priority' => TaskPriority::Medium, 'user_id' => $this->user->id]);

    $highPriorityTasks = Task::where('priority', TaskPriority::High->value)->count();

    expect($highPriorityTasks)->toBe(2);
});

test('eliminar usuario elimina sus tareas', function () {
    $task = Task::factory()->create(['user_id' => $this->user->id]);
    $taskId = $task->id;

    $this->user->delete();

    expect(Task::find($taskId))->toBeNull();
});

test('due_date se castea a date', function () {
    $task = Task::factory()->create([
        'due_date' => '2026-12-31',
    ]);

    expect($task->due_date)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->format('Y-m-d')->toBe('2026-12-31');
});

test('enum priority tiene labels correctos', function () {
    expect(TaskPriority::Low->getLabel())->toBe('Baja')
        ->and(TaskPriority::Medium->getLabel())->toBe('Media')
        ->and(TaskPriority::High->getLabel())->toBe('Alta');
});

test('enum priority tiene colores correctos', function () {
    expect(TaskPriority::Low->getColor())->toBe('success')
        ->and(TaskPriority::Medium->getColor())->toBe('warning')
        ->and(TaskPriority::High->getColor())->toBe('danger');
});
