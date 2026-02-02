<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\TaskPriority;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (TaskPriority $state): string => $state->getLabel())
                    ->color(fn (TaskPriority $state): string => $state->getColor())
                    ->sortable(),

                IconColumn::make('completed')
                    ->label('Completada')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Fecha límite')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('completed')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Completadas')
                    ->falseLabel('Pendientes'),

                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        TaskPriority::Low->value => TaskPriority::Low->getLabel(),
                        TaskPriority::Medium->value => TaskPriority::Medium->getLabel(),
                        TaskPriority::High->value => TaskPriority::High->getLabel(),
                    ]),

                Filter::make('due_date')
                    ->label('Fecha límite')
                    ->form([
                        DatePicker::make('due_from')
                            ->label('Desde'),
                        DatePicker::make('due_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),

                SelectFilter::make('user')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsCompleted')
                        ->label('Marcar como completadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['completed' => true])),

                    BulkAction::make('markAsPending')
                        ->label('Marcar como pendientes')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['completed' => false])),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }
}
