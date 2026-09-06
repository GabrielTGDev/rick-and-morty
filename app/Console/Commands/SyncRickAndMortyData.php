<?php

namespace App\Console\Commands;

use App\Services\DataSyncService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncRickAndMortyData extends Command
{
    protected $signature = 'app:sync-rick-and-morty';
    protected $description = 'Sincroniza localizaciones, episodios y personajes de la API externa de Rick and Morty';
    private ?ProgressBar $progressBar = null;
    private ?ProgressBar $countdownBar = null;
    private int $currentPage = 0;
    private ?int $maxPages = null;

    public function handle(DataSyncService $syncService): int
    {
        $this->info('Iniciando proceso de sincronización...');
        $syncService->setProgressCallback(fn (array $event) => $this->renderApiProgress($event));

        try {
            $locCount = $this->runPhase(
                '1/3. Sincronizando localizaciones',
                fn (callable $progress) => $syncService->syncLocations($progress)
            );
            $this->info("Localizaciones procesadas: {$locCount}");

            $epCount = $this->runPhase(
                '2/3. Sincronizando episodios',
                fn (callable $progress) => $syncService->syncEpisodes($progress)
            );
            $this->info("Episodios procesados: {$epCount}");

            $charCount = $this->runPhase(
                '3/3. Sincronizando personajes y relaciones',
                fn (callable $progress) => $syncService->syncCharacters($progress)
            );
            $this->info("Personajes procesados: {$charCount}");

            $this->newLine();
            $this->info('¡Sincronización completada exitosamente!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->finishProgressBar();
            $this->error("Falló la sincronización: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    private function runPhase(string $label, callable $synchronize): int
    {
        $this->info($label);
        $this->progressBar = null;
        $this->currentPage = 0;
        $this->maxPages = null;

        $count = $synchronize(
            fn (int $page, ?int $totalPages) => $this->advancePhaseProgress($page, $totalPages)
        );

        $this->finishProgressBar();

        return $count;
    }

    private function advancePhaseProgress(int $page, ?int $totalPages): void
    {
        $this->currentPage = $page;
        if ($totalPages !== null) {
            $this->maxPages = $totalPages;
        }

        // Solo actualiza la barra si ya existe
        if ($this->progressBar !== null) {
            if ($this->maxPages !== null && $this->progressBar->getMaxSteps() != $this->maxPages) {
                $this->progressBar->setMaxSteps($this->maxPages);
            }

            $steps = $this->progressBar->getProgress();
            if ($page > $steps) {
                $this->progressBar->advance($page - $steps);
            }
        }
    }

    private function renderApiProgress(array $event): void
    {
        if ($event['event'] === 'attempt') {
            // Crea la barra si no existe y tenemos totalPages
            if ($this->progressBar === null && $this->maxPages !== null) {
                $this->progressBar = $this->output->createProgressBar($this->maxPages);
                $this->progressBar->setFormat('  Progreso: %current%/%max% [%bar%]');
                $this->progressBar->start();
                
                // Restaura el progreso anterior
                if ($this->currentPage > 0) {
                    $this->progressBar->advance($this->currentPage);
                }
            }
        }

        if ($event['event'] === 'success') {
            // Crea la barra en el primer success si aún no existe
            if ($this->progressBar === null && isset($event['totalPages'])) {
                $this->maxPages = $event['totalPages'];
                $this->progressBar = $this->output->createProgressBar($this->maxPages);
                $this->progressBar->setFormat('  Progreso: %current%/%max% [%bar%]');
                $this->progressBar->start();
                
                // Restaura el progreso anterior
                if ($this->currentPage > 0) {
                    $this->progressBar->advance($this->currentPage);
                }
            }
            
            $this->ensureProgressBar();
            $this->progressBar->setMessage(
                "  ✓ {$event['resource']}: respuesta 200"
            );
            $this->progressBar->display();
        }

        if ($event['event'] === 'error') {
            if ($this->progressBar !== null) {
                $this->progressBar->setMessage(
                    "  ⚠ {$event['resource']}: {$event['errorMessage']}"
                );
                $this->progressBar->display();
            }
        }

        if ($event['event'] === 'retry_wait_start') {
            // Cierra la barra de progreso de forma temporal
            if ($this->progressBar !== null) {
                $this->progressBar->finish();
                $this->newLine();
            }
            
            // Crea barra de countdown independiente
            if (isset($event['totalSeconds'])) {
                $this->countdownBar = $this->output->createProgressBar($event['totalSeconds']);
                $this->countdownBar->setFormat('  ⏳ Esperando reintentos: %current%/%max% segundos');
                $this->countdownBar->start();
            }
        }

        if ($event['event'] === 'retry_wait_tick') {
            if ($this->countdownBar !== null && isset($event['remainingSeconds'], $event['totalSeconds'])) {
                $elapsed = $event['totalSeconds'] - $event['remainingSeconds'];
                $current = $this->countdownBar->getProgress();
                if ($elapsed > $current) {
                    $this->countdownBar->advance($elapsed - $current);
                }
            }
        }

        if ($event['event'] === 'exhausted') {
            if ($this->countdownBar !== null) {
                $this->countdownBar->finish();
                $this->newLine();
                $this->countdownBar = null;
            }
            
            // Reinicia la barra de progreso
            $this->progressBar = null;
            $this->ensureProgressBar();
            $this->progressBar->setMessage(
                "  ✗ {$event['resource']}: agotados los {$event['attempts']} intentos"
            );
            $this->progressBar->display();
        }
    }

    private function ensureProgressBar(): void
    {
        if ($this->progressBar === null) {
            // Solo se crea si tenemos maxPages, sino crea una dummy
            $max = $this->maxPages ?? 1;
            $this->progressBar = $this->output->createProgressBar($max);
            $this->progressBar->setFormat('  Progreso: %current%/%max% [%bar%]');
            $this->progressBar->start();
        }
    }

    private function finishProgressBar(): void
    {
        if ($this->progressBar !== null) {
            $this->progressBar->finish();
            $this->newLine();
            $this->progressBar = null;
        }
        if ($this->countdownBar !== null) {
            $this->countdownBar->finish();
            $this->newLine();
            $this->countdownBar = null;
        }
    }
}
