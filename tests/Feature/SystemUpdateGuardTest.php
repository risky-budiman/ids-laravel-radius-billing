<?php

namespace Tests\Feature;

use App\Http\Controllers\SystemUpdateController;
use Tests\TestCase;

/**
 * Update dari web tidak boleh menerapkan versi yang butuh composer/npm (situs bisa error 500 /
 * tampil rusak); perintah git dipalsukan agar test tidak menyentuh repository.
 */
class SystemUpdateGuardTest extends TestCase
{
    private function controller(string $diff): SystemUpdateController
    {
        return new class($diff) extends SystemUpdateController {
            public array $ran = [];

            public function __construct(private string $diff)
            {
            }

            protected function runCommand(string $command): ?string
            {
                $this->ran[] = $command;

                return match (true) {
                    str_contains($command, 'rev-parse --abbrev-ref') => 'main',
                    str_contains($command, 'diff --name-only') => $this->diff,
                    default => '',
                };
            }
        };
    }

    public function test_update_touching_dependencies_or_assets_is_refused(): void
    {
        $c = $this->controller("app/Models/Customer.php\ncomposer.lock\nresources/css/app.css");

        $res = $c->run();

        $this->assertSame(409, $res->getStatusCode());
        $this->assertFalse($res->getData(true)['success']);
        $this->assertStringContainsString('./update.sh', $res->getData(true)['message']);
        $this->assertEmpty(array_filter($c->ran, fn ($cmd) => str_contains($cmd, 'reset --hard')), 'kode tidak boleh diganti');
    }

    public function test_detached_head_is_refused(): void
    {
        $c = new class extends SystemUpdateController {
            protected function runCommand(string $command): ?string
            {
                return str_contains($command, 'rev-parse --abbrev-ref') ? 'HEAD' : '';
            }
        };

        $this->assertSame(422, $c->run()->getStatusCode());
    }
}
