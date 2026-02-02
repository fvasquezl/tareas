<x-filament-panels::page>
    <div class="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->getTasks() as $record)
            <article class="task-card">
                <x-filament::section>
                    {{-- Header del Card --}}
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-950 dark:text-white truncate">
                                {{ $record->title }}
                            </h3>
                            @if ($record->description)
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $record->description }}
                                </p>
                            @endif
                        </div>

                        {{-- Botón de Editar --}}
                        <x-filament::icon-button
                            :href="route('filament.admin.resources.tasks.edit', ['record' => $record])"
                            icon="heroicon-o-pencil-square"
                            color="gray"
                            size="sm"
                        />
                    </div>

                    {{-- Información adicional --}}
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                        @if ($record->due_date)
                            <div class="flex items-center gap-1.5">
                                <x-filament::icon
                                    icon="heroicon-o-calendar"
                                    class="h-4 w-4"
                                />
                                {{ $record->due_date->format('d/m/Y') }}
                            </div>
                        @endif

                        <div class="flex items-center gap-1.5">
                            <x-filament::icon
                                icon="heroicon-o-user-circle"
                                class="h-4 w-4"
                            />
                            {{ $record->user->name }}
                        </div>
                    </div>

                    {{-- Controles --}}
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            {{-- Select de Prioridad --}}
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Prioridad
                                </label>
                                <select
                                    wire:change="updatePriority({{ $record->id }}, $event.target.value)"
                                    class="fi-input block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="low" {{ $record->priority->value === 'low' ? 'selected' : '' }}>
                                        Baja
                                    </option>
                                    <option value="medium" {{ $record->priority->value === 'medium' ? 'selected' : '' }}>
                                        Media
                                    </option>
                                    <option value="high" {{ $record->priority->value === 'high' ? 'selected' : '' }}>
                                        Alta
                                    </option>
                                </select>
                            </div>

                            {{-- Toggle de Completada --}}
                            <div class="flex flex-col items-center">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Completada
                                </label>
                                <button
                                    wire:click="toggleCompleted({{ $record->id }})"
                                    type="button"
                                    @class([
                                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                        'bg-primary-600' => $record->completed,
                                        'bg-gray-200 dark:bg-gray-700' => !$record->completed,
                                    ])
                                    role="switch"
                                    aria-checked="{{ $record->completed ? 'true' : 'false' }}">
                                    <span
                                        @class([
                                            'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                            'translate-x-5' => $record->completed,
                                            'translate-x-0' => !$record->completed,
                                        ])>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Badges --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <x-filament::badge
                                :color="$record->priority->getColor()"
                            >
                                {{ $record->priority->getLabel() }}
                            </x-filament::badge>

                            @if ($record->completed)
                                <x-filament::badge
                                    color="success"
                                    icon="heroicon-o-check-circle"
                                >
                                    Completada
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            </article>
        @empty
            <div class="col-span-full">
                <x-filament::empty-state
                    icon="heroicon-o-clipboard-document-list"
                    heading="No hay tareas"
                    description="Comienza creando una nueva tarea."
                />
            </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if ($this->getTasks()->hasPages())
        <div class="mt-6">
            {{ $this->getTasks()->links() }}
        </div>
    @endif
</x-filament-panels::page>
