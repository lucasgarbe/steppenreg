<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalDiskPermissionsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/local-disk-permissions');
        File::deleteDirectory($this->root);

        config(['filesystems.disks.local.root' => $this->root]);
        Storage::forgetDisk('local');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_private_files_are_group_readable_so_the_web_user_can_serve_worker_written_exports(): void
    {
        Storage::disk('local')->put('filament_exports/1/headers.csv', 'name', Filesystem::VISIBILITY_PRIVATE);

        $this->assertGroupHasPermissions($this->root.'/filament_exports', 0050);
        $this->assertGroupHasPermissions($this->root.'/filament_exports/1', 0050);
        $this->assertGroupHasPermissions($this->root.'/filament_exports/1/headers.csv', 0040);
    }

    private function assertGroupHasPermissions(string $path, int $expectedGroupBits): void
    {
        clearstatcache();

        $this->assertSame($expectedGroupBits, fileperms($path) & $expectedGroupBits, sprintf('%s is %o', $path, fileperms($path) & 0777));
    }
}
