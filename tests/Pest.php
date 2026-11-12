<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests use the Laravel application TestCase so HTTP helpers
| like get()/postJson() work. Unit tests stay on PHPUnit\Framework\TestCase
| unless a file opts into Laravel.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');
