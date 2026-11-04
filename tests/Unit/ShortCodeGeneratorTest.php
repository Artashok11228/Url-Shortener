<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_codes_of_the_configured_length_from_base62(): void
    {
        config(['shortener.code_length' => 9]);

        $code = (new ShortCodeGenerator)->generate();

        $this->assertSame(9, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $code);
    }

    public function test_consecutive_codes_differ(): void
    {
        $generator = new ShortCodeGenerator;

        $codes = collect(range(1, 20))->map(fn () => $generator->generate());

        $this->assertCount(20, $codes->unique());
    }

    public function test_skips_codes_that_are_already_taken_including_soft_deleted_ones(): void
    {
        // A one-character code has only 62 possibilities, so we can take all but one.
        config(['shortener.code_length' => 1, 'shortener.code_max_attempts' => 500]);

        $taken = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXY');
        foreach ($taken as $code) {
            Link::factory()->create(['code' => $code]);
        }
        Link::query()->where('code', 'a')->first()->delete();

        $this->assertSame('Z', (new ShortCodeGenerator)->generate());
    }

    public function test_gives_up_after_the_configured_number_of_attempts(): void
    {
        config(['shortener.code_length' => 1, 'shortener.code_max_attempts' => 3]);

        foreach (str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') as $code) {
            Link::factory()->create(['code' => $code]);
        }

        $this->expectException(RuntimeException::class);

        (new ShortCodeGenerator)->generate();
    }
}
