<?php
declare(strict_types=1);

/**
 * Stores tasks in a JSON file (no database needed).
 */
final class TaskRepository
{
    public function __construct(private readonly string $file)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!file_exists($this->file)) {
            file_put_contents($this->file, '[]');
        }
    }

    /** @return Task[] */
    public function all(): array
    {
        $raw = file_get_contents($this->file);
        $data = json_decode($raw === false ? '[]' : $raw, true);

        if (!is_array($data)) {
            return [];
        }

        return array_map(static fn (array $row): Task => Task::fromArray($row), $data);
    }

    /** @return Task[] open tasks first, then by priority, then newest first */
    public function sorted(string $filter = 'all'): array
    {
        $tasks = array_filter($this->all(), static function (Task $t) use ($filter): bool {
            return match ($filter) {
                'open'  => !$t->done,
                'done'  => $t->done,
                default => true,
            };
        });

        usort($tasks, static function (Task $a, Task $b): int {
            return [$a->done, $a->priorityRank(), $b->createdAt]
               <=> [$b->done, $b->priorityRank(), $a->createdAt];
        });

        return $tasks;
    }

    public function add(Task $task): void
    {
        $tasks = $this->all();
        $tasks[] = $task;
        $this->save($tasks);
    }

    public function toggle(string $id): void
    {
        $tasks = array_map(
            static fn (Task $t): Task => $t->id === $id ? $t->toggled() : $t,
            $this->all()
        );
        $this->save($tasks);
    }

    public function delete(string $id): void
    {
        $tasks = array_filter($this->all(), static fn (Task $t): bool => $t->id !== $id);
        $this->save(array_values($tasks));
    }

    public function clearDone(): int
    {
        $all = $this->all();
        $open = array_values(array_filter($all, static fn (Task $t): bool => !$t->done));
        $this->save($open);

        return count($all) - count($open);
    }

    /** @return array{total:int, open:int, done:int, percent:int} */
    public function stats(): array
    {
        $all = $this->all();
        $done = count(array_filter($all, static fn (Task $t): bool => $t->done));
        $total = count($all);

        return [
            'total'   => $total,
            'open'    => $total - $done,
            'done'    => $done,
            'percent' => $total === 0 ? 0 : (int) round($done * 100 / $total),
        ];
    }

    /** @param Task[] $tasks */
    private function save(array $tasks): void
    {
        $json = json_encode(
            array_map(static fn (Task $t): array => $t->toArray(), $tasks),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        file_put_contents($this->file, $json, LOCK_EX);
    }
}
