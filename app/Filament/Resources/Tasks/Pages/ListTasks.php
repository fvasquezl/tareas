<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;

class ListTasks extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string $resource = TaskResource::class;

    public function getView(): string
    {
        return 'filament.resources.tasks.pages.list-tasks-infolist';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record(new \stdClass)
            ->schema([
                RepeatableEntry::make('tasks')
                    ->label('')
                    ->state(Task::with('user')->orderBy('due_date', 'asc')->get())
                    ->schema([
                        ViewEntry::make('card')
                            ->view('filament.resources.tasks.infolists.task-card'),
                    ])
                    ->columns([
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->contained(false),
            ]);
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
        $task = Task::find($taskId);

        if ($task) {
            $task->update(['priority' => $priority]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Prioridad actualizada')
                ->body('La prioridad de la tarea ha sido actualizada.')
                ->send();

            $this->dispatch('$refresh');
        }
    }

    public function toggleTaskCompleted($taskId): void
    {
        $task = Task::find($taskId);

        if ($task) {
            $task->update(['completed' => ! $task->completed]);

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body($task->completed ? 'Tarea marcada como completada.' : 'Tarea marcada como pendiente.')
                ->send();

            $this->dispatch('$refresh');
        }
    }
}
