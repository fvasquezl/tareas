<?php

namespace App\Filament\Widgets;

use App\Enums\TaskPriority;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalTasks = Task::count();
        $completedTasks = Task::where('completed', true)->count();
        $pendingTasks = Task::where('completed', false)->count();
        $overdueTasks = Task::where('completed', false)
            ->whereDate('due_date', '<', now())
            ->count();

        $highPriorityTasks = Task::where('priority', TaskPriority::High->value)
            ->where('completed', false)
            ->count();

        return [
            Stat::make('Total de Tareas', $totalTasks)
                ->description('Todas las tareas en el sistema')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Tareas Completadas', $completedTasks)
                ->description($totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) . '% del total' : 'Sin tareas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Tareas Pendientes', $pendingTasks)
                ->description($overdueTasks > 0 ? "{$overdueTasks} vencidas" : 'Ninguna vencida')
                ->descriptionIcon('heroicon-o-clock')
                ->color($overdueTasks > 0 ? 'danger' : 'warning'),

            Stat::make('Alta Prioridad', $highPriorityTasks)
                ->description('Tareas urgentes pendientes')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
