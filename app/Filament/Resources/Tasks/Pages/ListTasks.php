<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\Paginator;

class ListTasks extends Page
{
    protected static string $resource = TaskResource::class;

    public function getView(): string
    {
        return 'filament.resources.tasks.pages.list-tasks-cards';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTasks(): Paginator
    {
        return Task::with('user')
            ->orderBy('due_date', 'asc')
            ->simplePaginate(12);
    }

    public function updatePriority(int $taskId, string $priority): void
    {
        $task = Task::find($taskId);

        if ($task) {
            $task->update(['priority' => $priority]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Prioridad actualizada')
                ->body('La prioridad de la tarea ha sido actualizada.')
                ->send();
        }
    }

    public function toggleCompleted(int $taskId): void
    {
        $task = Task::find($taskId);

        if ($task) {
            $task->update(['completed' => ! $task->completed]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body($task->completed ? 'Tarea marcada como completada.' : 'Tarea marcada como pendiente.')
                ->send();
        }
    }
}
