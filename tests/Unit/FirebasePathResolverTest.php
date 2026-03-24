<?php

namespace Tests\Unit;

use App\Services\Firebase\FirebasePathResolver;
use Tests\TestCase;

class FirebasePathResolverTest extends TestCase
{
    public function test_it_resolves_relative_project_paths(): void
    {
        $resolved = FirebasePathResolver::credentialsPath('storage/app/secrets/firebase.json');

        $this->assertSame(
            base_path('storage/app/secrets/firebase.json'),
            $resolved,
        );
    }

    public function test_it_keeps_absolute_windows_paths_unchanged(): void
    {
        $path = 'C:\\firebase\\service-account.json';

        $this->assertSame($path, FirebasePathResolver::credentialsPath($path));
    }
}
