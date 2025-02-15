<?php

declare(strict_types=1);

namespace Tests\Deptrac\Deptrac\Core\Ast\AstMap\File;

use Deptrac\Deptrac\Core\Ast\AstMap\File\FileToken;
use PHPUnit\Framework\TestCase;

final class FileTokenTest extends TestCase
{
    public function testPathNormalization(): void
    {
        $fileName = new FileToken('/path/to/file.php');
        $this->assertSame('/path/to/file.php', $fileName->path);
        $this->assertSame('/path/to/file.php', $fileName->toString());

        $fileName = new FileToken('\\path\\to\\file.php');
        $this->assertSame('/path/to/file.php', $fileName->path);
        $this->assertSame('/path/to/file.php', $fileName->toString());
    }
}
