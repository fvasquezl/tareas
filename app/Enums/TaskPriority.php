<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function getLabel(): string
    {
        return match($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::Low => 'success',
            self::Medium => 'warning',
            self::High => 'danger',
        };
    }
}
