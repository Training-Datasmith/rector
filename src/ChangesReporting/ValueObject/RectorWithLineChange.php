<?php

declare (strict_types=1);
namespace Rector\Changes_Reporting\Value_Object;

use Rector\Contract\Rector\Rector_Interface;
use Rector\Post_Rector\Contract\Rector\Post_Rector_Interface;
use Rector_Prefix202603\Symplify\Easy_Parallel\Contract\Serializable_Interface;
use Rector_Prefix202603\Webmozart\Assert\Assert;
final class Rector_With_Line_Change implements Serializable_Interface
{
    /**
     * @var class-string<RectorInterface|PostRectorInterface>
     * @readonly
     */
    private string $rector_class;
    /**
     * @readonly
     */
    private int $line;
    /**
     * @var string
     */
    private const KEY_RECTOR_CLASS = 'rector_class';
    /**
     * @var string
     */
    private const KEY_LINE = 'line';
    /**
     * @param class-string<RectorInterface|PostRectorInterface> $rectorClass
     */
    public function __construct(string $rector_class, int $line)
    {
        $this->rector_class = $rector_class;
        $this->line = $line;
    }
    /**
     * @return class-string<RectorInterface|PostRectorInterface>
     */
    public function get_rector_class(): string
    {
        return $this->rector_class;
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        /** @var class-string<RectorInterface> $rectorClass */
        $rector_class = $json[self::KEY_RECTOR_CLASS];
        Assert::string($rector_class);
        $line = $json[self::KEY_LINE];
        Assert::integer($line);
        return new self($rector_class, $line);
    }
    /**
     * @return array{rector_class: class-string<RectorInterface|PostRectorInterface>, line: int}
     */
    public function jsonSerialize(): array
    {
        return [self::KEY_RECTOR_CLASS => $this->rector_class, self::KEY_LINE => $this->line];
    }
}