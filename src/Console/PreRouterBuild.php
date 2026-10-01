<?php

namespace Vis\Builder\Console;

use Vis\Builder\Services\RouteMapBuilder;
use Illuminate\Console\Command;

class PreRouterBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prerouter:build {--type=all : Type to build (all, tree, news, category, product)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build pre-router cache map';

    /**
     * Execute the console command.
     */
    public function handle(RouteMapBuilder $builder): int
    {
        // Перевіряємо, чи активний прероутер
        if (! config('prerouter.enabled', false)) {
            $this->warn('PreRouter is disabled. Enable it in config/prerouter.php or set PREROUTER_ENABLED=true');
            return Command::FAILURE;
        }

        $type = $this->option('type');

        $this->info('Building pre-router cache...');

        match ($type) {
            'tree' => $builder->buildTree(),
            'news' => $builder->buildNews(),
            'category' => $builder->buildCategories(),
            'product' => $builder->buildProducts(),
            default => $builder->rebuildAll(),
        };

        $this->info('Pre-router cache built successfully!');

        return Command::SUCCESS;
    }
}
