<?php

declare (strict_types=1);
namespace Rector\Value_Object\Error;

use Rector\Parallel\Value_Object\Bridge_Item;
use Rector_Prefix202603\Nette\Utils\Strings;
use Rector_Prefix202603\Symplify\Easy_Parallel\Contract\Serializable_Interface;
/**
 * @see \Rector\Tests\ValueObject\Error\SystemErrorTest
 */
final class System_Error implements Serializable_Interface
{
    /**
     * @readonly
     */
    private string $message;
    /**
     * @readonly
     */
    private ?string $relative_file_path;
    /**
     * @readonly
     */
    private ?int $line;
    /**
     * @readonly
     */
    private ?string $rector_class;
    public function __construct(string $message, ?string $relative_file_path = null, ?int $line = null, ?string $rector_class = null)
    {
        $this->message = $message;
        $this->relative_file_path = $relative_file_path;
        $this->line = $line;
        $this->rector_class = $rector_class;
    }
    public function get_message(): string
    {
        return $this->message;
    }
    public function get_line(): ?int
    {
        return $this->line;
    }
    public function get_relative_file_path(): ?string
    {
        return $this->relative_file_path;
    }
    public function get_absolute_file_path(): ?string
    {
        if ($this->relative_file_path === null) {
            return null;
        }
        return \realpath($this->relative_file_path);
    }
    /**
     * @return array{
     *     message: string,
     *     relative_file_path: string|null,
     *     absolute_file_path: string|null,
     *     line: int|null,
     *     rector_class: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [Bridge_Item::MESSAGE => $this->message, Bridge_Item::RELATIVE_FILE_PATH => $this->relative_file_path, Bridge_Item::ABSOLUTE_FILE_PATH => $this->get_absolute_file_path(), Bridge_Item::LINE => $this->line, Bridge_Item::RECTOR_CLASS => $this->rector_class];
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        return new self($json[Bridge_Item::MESSAGE], $json[Bridge_Item::RELATIVE_FILE_PATH], $json[Bridge_Item::LINE], $json[Bridge_Item::RECTOR_CLASS]);
    }
    public function get_rector_class(): ?string
    {
        return $this->rector_class;
    }
    public function get_rector_short_class(): ?string
    {
        $rector_class = $this->rector_class;
        if (!in_array($rector_class, [null, ''], \true)) {
            return (string) Strings::after($rector_class, '\\', -1);
        }
        return null;
    }
}