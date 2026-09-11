<?php
/** CONTROLLER — Planning (tâches) */

require_once __DIR__ . '/../models/Task.php';

class TaskController
{
    public function index(): void
    {
        require_login();
        $userId = current_user_id();
        $filters = [
            'status'   => clean_str($_GET['status'] ?? ''),
            'priority' => clean_str($_GET['priority'] ?? ''),
        ];
        $tasks = Task::allForUser($userId, $filters);
        render('planning', ['title' => 'Planning', 'tasks' => $tasks, 'filters' => $filters]);
    }

    public function store(): void
    {
        require_login();
        csrf_require();
        $userId   = current_user_id();
        $title    = clean_str($_POST['title'] ?? '');
        $dueDate  = clean_str($_POST['due_date'] ?? '') ?: null;
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high'], true) ? $_POST['priority'] : 'medium';

        if ($title === '') {
            json_response(['success' => false, 'error' => 'Le titre est requis.'], 422);
        }
        $id = Task::create($userId, $title, $dueDate, $priority);
        json_response(['success' => true, 'id' => $id]);
    }

    public function update(): void
    {
        require_login();
        csrf_require();
        $userId   = current_user_id();
        $id       = (int)($_POST['id'] ?? 0);
        $title    = clean_str($_POST['title'] ?? '');
        $dueDate  = clean_str($_POST['due_date'] ?? '') ?: null;
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high'], true) ? $_POST['priority'] : 'medium';

        if (!$id || !Task::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Tâche introuvable.'], 404);
        }
        if ($title === '') {
            json_response(['success' => false, 'error' => 'Le titre est requis.'], 422);
        }
        Task::update($id, $userId, $title, $dueDate, $priority);
        json_response(['success' => true]);
    }

    public function toggle(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !Task::toggleStatus($id, $userId)) {
            json_response(['success' => false, 'error' => 'Tâche introuvable.'], 404);
        }
        json_response(['success' => true]);
    }

    public function destroy(): void
    {
        require_login();
        csrf_require();
        $userId = current_user_id();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id || !Task::find($id, $userId)) {
            json_response(['success' => false, 'error' => 'Tâche introuvable.'], 404);
        }
        Task::delete($id, $userId);
        json_response(['success' => true]);
    }
}
