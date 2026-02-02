<x-filament-panels::page>
    <div class="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->getTasks() as $record)
            <div class="fi-card bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                {{-- Header del Card --}}
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
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
                        <a href="{{ route('filament.admin.resources.tasks.edit', ['record' => $record]) }}"
                           class="fi-icon-btn shrink-0 flex items-center justify-center rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 h-8 w-8">
                            <svg class="shrink-0 h-3.5 w-3.5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                            </svg>
                        </a>
                    </div>

                    {{-- Información adicional --}}
                    <div class="mt-4 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        @if ($record->due_date)
                            <div class="flex items-center gap-1.5">
                                <svg class="shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" />
                                </svg>
                                {{ $record->due_date->format('d/m/Y') }}
                            </div>
                        @endif

                        <div class="flex items-center gap-1.5">
                            <svg class="shrink-0 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-5.5-2.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0ZM10 12a5.99 5.99 0 0 0-4.793 2.39A6.483 6.483 0 0 0 10 16.5a6.483 6.483 0 0 0 4.793-2.11A5.99 5.99 0 0 0 10 12Z" clip-rule="evenodd" />
                            </svg>
                            {{ $record->user->name }}
                        </div>
                    </div>
                </div>

                {{-- Footer del Card con controles --}}
                <div class="border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
                        {{-- Select de Prioridad --}}
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                Prioridad
                            </label>
                            <select
                                wire:change="updatePriority({{ $record->id }}, $event.target.value)"
                                class="fi-select w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500">
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

                    {{-- Badge de Prioridad visual --}}
                    <div class="mt-3 flex items-center gap-2">
                        <span @class([
                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $record->priority->value === 'low',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' => $record->priority->value === 'medium',
                            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $record->priority->value === 'high',
                        ])>
                            {{ $record->priority->getLabel() }}
                        </span>

                        @if ($record->completed)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <svg class="shrink-0 mr-1 h-2.5 w-2.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                                </svg>
                                Completada
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="text-center py-12">
                    <svg class="shrink-0 mx-auto h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay tareas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando una nueva tarea.</p>
                </div>
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
