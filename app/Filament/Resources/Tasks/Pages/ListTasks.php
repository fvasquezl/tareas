<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('card')
                    ->view('filament.resources.tasks.columns.task-card'),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->paginated([12, 24, 48])
            ->defaultPaginationPageOption(12);
    }

    protected function getListeners(): array
    {
        return [
            'updateTaskPriority',
            'toggleTaskCompleted',
        ];
    }

    public function updateTaskPriority($taskId, $priority): void
    {
        $task = \App\Models\Task::find($taskId);

        if ($task) {
            $task->update(['priority' => $priority]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Prioridad actualizada')
                ->body('La prioridad de la tarea ha sido actualizada.')
                ->send();
        }
    }

    public function toggleTaskCompleted($taskId): void
    {
        $task = \App\Models\Task::find($taskId);

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
