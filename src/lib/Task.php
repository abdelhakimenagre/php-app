<?php
declare(strict_types=1);

final class Task
{
    public const PRIORITIES = ['high', 'medium', 'low'];
    public const MAX_TITLE_LENGTH = 120;

    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $priority,
        public readonly bool $done,
        public readonly string $createdAt
    ) {
    }

    public static function create(string $title, string $priority): self
    {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidArgumentException('Write a title for the task.');
        }
        if (mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new InvalidArgumentException('Keep the title under ' . self::MAX_TITLE_LENGTH . ' characters.');
        }
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new InvalidArgumentException('Choose a priority: high, medium or low.');
        }

        return new self(bin2hex(random_bytes(8)), $title, $priority, false, date('c'));
    }

    public function toggled(): self
    {
        return new self($this->id, $this->title, $this->priority, !$this->done, $this->createdAt);
    }

    public function priorityRank(): int
    {
        return (int) array_search($this->priority, self::PRIORITIES, true);
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'priority'  => $this->priority,
            'done'      => $this->done,
            'createdAt' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['title'] ?? ''),
            (string) ($data['priority'] ?? 'medium'),
            (bool) ($data['done'] ?? false),
            (string) ($data['createdAt'] ?? date('c'))
        );
    }
}
