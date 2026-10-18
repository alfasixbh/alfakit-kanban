<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitKanban;

use Illuminate\Support\ServiceProvider;

/**
 * Loads the plugin's translations under the `alfakit-kanban::` namespace. The frontend
 * strings (`alfakit-kanban::kanban.ui`) reach the renderers through the panel's
 * `frontendTranslations()`, registered by `KanbanPlugin`.
 */
class AlfakitKanbanServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'alfakit-kanban');
    }
}
