<?php
declare(strict_types=1);

namespace Controllers\Site;

use Controllers\Controller;
use Core\Csrf;
use Core\Logger;
use Core\ModuleRegistry;
use Core\RateLimiter;
use Core\Sanitizer;
use Core\Session;
use Core\Translator;
use Core\Validator;
use Core\Response;
use Models\Comment;

/**
 * Ziyaretçi yorumu gönderimi — admin onaylı.
 */
final class CommentController extends Controller
{
    protected string $viewPrefix = 'site';

    public function store(): Response
    {
        Csrf::verifyOrFail($this->request);
        ModuleRegistry::requireActive('comments');

        $type   = (string) $this->request->post('content_type', '');
        $entity = (int) $this->request->post('entity_id', 0);

        // Honeypot + zaman tuzağı
        if (trim((string) $this->request->post('website', '')) !== '') {
            return back('#comments');
        }

        $data = [
            'author_name'  => mb_substr(trim((string) $this->request->post('author_name', '')), 0, 120),
            'author_email' => mb_substr(mb_strtolower(trim((string) $this->request->post('author_email', ''))), 0, 190),
            'body'         => mb_substr(Sanitizer::textarea((string) $this->request->post('body', '')), 0, 2000),
        ];

        $throttle = RateLimiter::throttle('comment:' . $this->request->ip(), 'comment_form');
        if (!$throttle['allowed']) {
            Session::flash('error', t('comment.too_many'));
            return back('#comments');
        }

        $v = Validator::make($data, [
            'author_name'  => 'required|string|min:2|max:120',
            'author_email' => 'required|email|max:190',
            'body'         => 'required|string|min:5|max:2000',
        ], [
            'author_name'  => t('comment.field.name'),
            'author_email' => t('comment.field.email'),
            'body'         => t('comment.field.body'),
        ]);

        if (!in_array($type, array_keys(Comment::TYPES), true) || $entity <= 0) {
            $v = null;
            $errors = ['body' => t('comment.invalid_target')];
        } else {
            $errors = $v->fails() ? $v->flatErrors() : [];
        }

        if ($errors !== []) {
            return $this->withErrors($this->request->all(), $errors, '#comments');
        }

        // Aynı IP'den aynı içeriğe 24 saat içinde ikinci yorum engellenir
        $dupe = \Core\Database::value(
            'SELECT COUNT(*) FROM comments WHERE content_type = :t AND entity_id = :e AND ip = :ip AND created_at > :t1',
            [
                't'  => $type,
                'e'  => $entity,
                'ip' => $this->request->ip(),
                't1' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            ],
            0
        );
        if ((int) $dupe > 0) {
            Session::flash('error', t('comment.duplicate'));
            return back('#comments');
        }

        Comment::create([
            'content_type' => $type,
            'entity_id'    => $entity,
            'parent_id'    => null,
            'author_name'  => $data['author_name'],
            'author_email' => $data['author_email'],
            'body'         => $data['body'],
            'locale'       => Translator::lang(),
            'is_approved'  => 0,
            'is_spam'      => 0,
            'ip'           => $this->request->ip(),
            'user_agent'   => mb_substr($this->request->userAgent(), 0, 255),
        ]);

        Logger::info('Yorum gönderildi', ['type' => $type, 'entity' => $entity]);

        Session::flash('success', t('comment.pending'));
        return back('#comments');
    }
}
