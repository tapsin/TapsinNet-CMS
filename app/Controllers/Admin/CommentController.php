<?php
declare(strict_types=1);

namespace Controllers\Admin;

use Controllers\Controller;
use Core\Csrf;
use Core\Response;
use Models\{Comment, Message};

/**
 * Yorum onay kuyruğu.
 */
final class CommentController extends Controller
{
    protected string $viewPrefix = 'admin.comments';
    protected string $indexRoute = '/admin/yorumlar';

    public function index(): Response
    {
        $filter = (string) $this->request->query('filter', 'pending');
        $paginator = Comment::queue($filter, $this->page());

        return $this->view('index', [
            'paginator' => $paginator,
            'rows'      => $paginator->items,
            'filter'    => $filter,
            'badge'     => Message::badge(),
            'counts'    => [
                'pending'  => \Core\Database::value('SELECT COUNT(*) FROM comments WHERE is_approved=0 AND is_spam=0 AND deleted_at IS NULL', [], 0),
                'approved' => \Core\Database::value('SELECT COUNT(*) FROM comments WHERE is_approved=1 AND deleted_at IS NULL', [], 0),
                'spam'     => \Core\Database::value('SELECT COUNT(*) FROM comments WHERE is_spam=1 AND deleted_at IS NULL', [], 0),
            ],
        ], 'layouts.admin');
    }

    public function action(array $params): Response
    {
        Csrf::verifyOrFail($this->request);
        $id  = (int) $params['id'];
        $op  = (string) $this->request->post('islem', '');

        if (Comment::findAny($id) === null) {
            return $this->fail(t('admin.not_found'));
        }

        switch ($op) {
            case 'onayla':    Comment::setApproved($id, true);  return $this->ok(t('admin.comment_approved'));
            case 'onaykaldir':Comment::setApproved($id, false); return $this->ok(t('admin.comment_unapproved'));
            case 'spam':      Comment::setSpam($id, true);     return $this->ok(t('admin.comment_spam'));
            case 'spamdegil': Comment::setSpam($id, false);    return $this->ok(t('admin.comment_unspammed'));
            case 'not':       Comment::saveNote($id, (string) $this->request->post('admin_note', ''));
                             return $this->ok(t('admin.note_saved'));
            case 'sil':       Comment::delete($id);            return $this->ok(t('admin.deleted', ['item' => '#' . $id]));
        }

        return $this->fail(t('admin.unknown_action'));
    }
}
