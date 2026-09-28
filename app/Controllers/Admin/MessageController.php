<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Csrf;
use Core\Response;
use Models\Message;

/**
 * Gelen kutusu — iletişim mesajları.
 */
final class MessageController extends Controller
{
    protected string $viewPrefix = 'admin.messages';
    protected string $indexRoute = '/admin/mesajlar';

    public function index(): Response
    {
        $filter = (string) $this->request->query('filter', '');
        $term   = $this->term();

        $paginator = Message::inbox($filter, $term, $this->page());

        return $this->view('index', [
            'paginator' => $paginator,
            'rows'      => $paginator->items,
            'filter'    => $filter,
            'term'      => $term,
            'badge'     => Message::badge(),
            'counts'    => [
                'all'      => \Core\Database::value('SELECT COUNT(*) FROM messages', [], 0),
                'unread'   => Message::unreadCount(),
                'starred'  => \Core\Database::value('SELECT COUNT(*) FROM messages WHERE is_starred = 1', [], 0),
                'archived' => \Core\Database::value('SELECT COUNT(*) FROM messages WHERE is_archived = 1', [], 0),
            ],
        ], 'layouts.admin');
    }

    public function show(array $params): Response
    {
        $id  = (int) $params['id'];
        $row = Message::findAny($id);
        if ($row === null) {
            $this->notFound();
        }

        if ((int) $row['is_read'] === 0) {
            Message::markRead($id, true);
            $row['is_read'] = 1;
        }

        return $this->view('show', [
            'row'   => $row,
            'badge' => Message::badge(),
        ], 'layouts.admin');
    }

    public function action(array $params): Response
    {
        Csrf::verifyOrFail($this->request);
        $id  = (int) $params['id'];
        $op  = (string) $this->request->post('islem', '');
        $row = Message::findAny($id);

        if ($row === null) {
            return $this->fail(t('admin.not_found'));
        }

        switch ($op) {
            case 'okundu':   Message::markRead($id, true);  break;
            case 'okunmadi': Message::markRead($id, false); break;
            case 'yildiz':   Message::setStarred($id, !(int) $row['is_starred']); break;
            case 'arsiv':
            case 'arsivden-cikar':
                Message::setArchived($id, $op === 'arsiv');
                return $this->ok(t('admin.updated', ['item' => '#' . $id]));
            case 'sil':
                Message::delete($id);
                return $this->ok(t('admin.deleted', ['item' => $row['name'] ?? ('#' . $id)]));
            case 'not':
                Message::saveNote($id, (string) $this->request->post('admin_note', ''));
                return $this->ok(t('admin.note_saved'));
        }

        return $this->fail(t('admin.unknown_action'));
    }

    public function readAll(): Response
    {
        Csrf::verifyOrFail($this->request);
        $n = Message::markAllRead();
        return $this->ok(t('admin.messages_all_read', ['n' => $n]));
    }
}
