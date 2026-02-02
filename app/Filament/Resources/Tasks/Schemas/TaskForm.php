<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskPriority;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),

                DatePicker::make('due_date')
                    ->label('Fecha límite')
                    ->native(false),

                Select::make('priority')
                    ->label('Prioridad')
                    ->options(TaskPriority::class)
                    ->default(TaskPriority::Medium->value)
                    ->required(),

                Toggle::make('completed')
                    ->label('Completada')
                    ->default(false),

                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn () => auth()->id()),
            ]);
    }
}
