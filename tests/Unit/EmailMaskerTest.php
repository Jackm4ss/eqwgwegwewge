<?php

namespace Tests\Unit;

use App\Helpers\EmailMasker;
use PHPUnit\Framework\TestCase;

class EmailMaskerTest extends TestCase
{
    public function test_it_masks_email(): void
    {
        $this->assertSame('us***@gmail.com', EmailMasker::mask('user@gmail.com'));
    }
}
