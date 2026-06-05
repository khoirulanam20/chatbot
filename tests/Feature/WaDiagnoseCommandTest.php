<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaDiagnoseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_wa_diagnose_runs_successfully(): void
    {
        $this->artisan('wa:diagnose')
            ->assertExitCode(0);
    }
}
