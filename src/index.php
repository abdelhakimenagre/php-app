<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

session_start();
$repo = new TaskRepository(DATA_FILE);

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// ---------- Handle form actions (POST), then redirect ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::isValid($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('The form expired. Go back and reload the page.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $id = (string) ($_POST['id'] ?? '');

    try {
        switch ($action) {
            case 'add':
                $repo->add(Task::create((string) ($_POST['title'] ?? ''), (string) ($_POST['priority'] ?? 'medium')));
                flash('success', 'Task added.');
                break;
            case 'toggle':
                $repo->toggle($id);
                break;
            case 'delete':
                $repo->delete($id);
                flash('success', 'Task deleted.');
                break;
            case 'clear':
                $count = $repo->clearDone();
                flash('success', $count . ' finished task(s) cleared.');
                break;
            default:
                flash('error', 'Unknown action.');
        }
    } catch (InvalidArgumentException $ex) {
        flash('error', $ex->getMessage());
    }

    $filter = in_array($_POST['filter'] ?? '', ['open', 'done'], true) ? $_POST['filter'] : 'all';
    header('Location: /?filter=' . $filter);
    exit;
}

// ---------- Prepare data for the page ----------
$filter = in_array($_GET['filter'] ?? '', ['open', 'done'], true) ? $_GET['filter'] : 'all';
$tasks = $repo->sorted($filter);
$stats = $repo->stats();
$csrf = Csrf::token();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$filters = ['all' => 'All', 'open' => 'To do', 'done' => 'Finished'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<main class="board">
    <header class="top">
        <div>
            <h1><?= e(APP_NAME) ?></h1>
            <p class="sub">A small task list, delivered by a CI/CD pipeline.</p>
        </div>
        <div class="meter" aria-label="Progress">
            <span class="meter-value"><?= $stats['percent'] ?>%</span>
            <span class="meter-text"><?= $stats['done'] ?> of <?= $stats['total'] ?> finished</span>
            <span class="meter-bar"><span style="width: <?= $stats['percent'] ?>%"></span></span>
        </div>
    </header>

    <?php if ($flash !== null): ?>
        <p class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <form method="post" class="add">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <label class="sr-only" for="title">New task</label>
        <input id="title" name="title" type="text" maxlength="<?= Task::MAX_TITLE_LENGTH ?>"
               placeholder="What needs doing?" required autofocus>
        <label class="sr-only" for="priority">Priority</label>
        <select id="priority" name="priority">
            <option value="high">High</option>
            <option value="medium" selected>Medium</option>
            <option value="low">Low</option>
        </select>
        <button type="submit">Add task</button>
    </form>

    <nav class="filters" aria-label="Filter tasks">
        <?php foreach ($filters as $key => $label): ?>
            <a href="/?filter=<?= $key ?>" class="<?= $filter === $key ? 'active' : '' ?>">
                <?= e($label) ?>
                <span class="count"><?= $key === 'all' ? $stats['total'] : $stats[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($tasks === []): ?>
        <p class="empty">
            <?= $filter === 'done' ? 'Nothing finished yet. Tick a task when it is done.' : 'No tasks here. Add one above to get started.' ?>
        </p>
    <?php else: ?>
        <ul class="tasks">
            <?php foreach ($tasks as $task): ?>
                <li class="task prio-<?= e($task->priority) ?><?= $task->done ? ' is-done' : '' ?>">
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= e($task->id) ?>">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                        <button class="check" type="submit"
                                aria-label="<?= $task->done ? 'Mark as to do' : 'Mark as finished' ?>">
                            <?= $task->done ? '✓' : '' ?>
                        </button>
                    </form>
                    <div class="body">
                        <span class="title"><?= e($task->title) ?></span>
                        <span class="meta">
                            <?= e(ucfirst($task->priority)) ?> priority, added
                            <?= e(date('d M Y, H:i', (int) strtotime($task->createdAt))) ?>
                        </span>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= e($task->id) ?>">
                        <input type="hidden" name="filter" value="<?= e($filter) ?>">
                        <button class="delete" type="submit">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($stats['done'] > 0): ?>
        <form method="post" class="clear">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="clear">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <button type="submit">Clear finished tasks</button>
        </form>
    <?php endif; ?>

    <footer class="foot">
        Version <?= e(APP_VERSION) ?>, PHP <?= e(PHP_VERSION) ?>, host <?= e(gethostname() ?: 'unknown') ?>
    </footer>
</main>
</body>
</html>
