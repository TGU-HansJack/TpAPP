<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;

class CommentsService extends AbstractService
{
    public function list(int $cid): array
    {
        $select = $this->db->select(
            'coid AS id',
            'cid',
            'author',
            'mail',
            'url',
            'text',
            'created',
            'parent'
        )
            ->from('table.comments')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'approved')
            ->order('created', 'ASC');

        $rows = $this->db->fetchAll($select);

        $map = [];
        $tree = [];

        foreach ($rows as $row) {
            $row['children'] = [];
            $map[$row['id']] = $row;
        }

        foreach ($map as $id => &$comment) {
            if (!empty($comment['parent']) && isset($map[$comment['parent']])) {
                $map[$comment['parent']]['children'][] = &$comment;
            } else {
                $tree[] = &$comment;
            }
        }

        return $this->formatTree($tree);
    }

    public function create(array $payload, ?array $user): array
    {
        $content = trim($payload['content'] ?? '');
        if ('' === $content) {
            throw new ApiException('评论内容不能为空');
        }

        $cid = (int) ($payload['postId'] ?? 0);
        if (!$cid) {
            throw new ApiException('postId 必须提供');
        }

        $post = $this->db->fetchRow($this->db->select('cid')->from('table.contents')->where('cid = ?', $cid)->limit(1));
        if (!$post) {
            throw new ApiException('文章不存在', 404);
        }

        $author = $payload['author'] ?? ($user['nickname'] ?? 'Guest');
        $mail = $payload['email'] ?? ($user['email'] ?? null);
        $url = $payload['url'] ?? ($user['url'] ?? null);

        $parent = isset($payload['parentId']) ? (int) $payload['parentId'] : 0;
        $status = 'approved';

        $options = $this->app->options();
        foreach ($options->sensitiveWords() as $word) {
            if (stripos($content, $word) !== false) {
                throw new ApiException('评论包含敏感词: ' . $word);
            }
        }

        $insert = $this->db->insert('table.comments')->rows([
            'cid' => $cid,
            'created' => time(),
            'author' => $author,
            'mail' => $mail,
            'url' => $url,
            'text' => $content,
            'ip' => $this->app->request()->ip(),
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'status' => $status,
            'parent' => $parent,
            'type' => 'comment',
        ]);

        $coid = $this->db->query($insert);
        $this->updateCommentsCount($cid);

        $row = $this->db->fetchRow($this->db->select()->from('table.comments')->where('coid = ?', $coid)->limit(1));
        return $this->formatComment($row);
    }

    public function approve(int $id): void
    {
        $this->db->query($this->db->update('table.comments')->rows(['status' => 'approved'])->where('coid = ?', $id));
    }

    public function delete(int $id): void
    {
        $row = $this->db->fetchRow($this->db->select('cid')->from('table.comments')->where('coid = ?', $id)->limit(1));
        if (!$row) {
            return;
        }

        $this->db->query($this->db->delete('table.comments')->where('coid = ?', $id));
        $this->db->query($this->db->delete('table.comments')->where('parent = ?', $id));
        $this->updateCommentsCount((int) $row['cid']);
    }

    private function formatTree(array $tree): array
    {
        $result = [];
        foreach ($tree as $comment) {
            $children = $comment['children'] ?? [];
            unset($comment['children']);
            $formatted = $this->formatComment($comment);
            if (!empty($children)) {
                $formatted['children'] = $this->formatTree($children);
            }
            $result[] = $formatted;
        }

        return $result;
    }

    private function formatComment(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'postId' => (int) $row['cid'],
            'author' => $row['author'],
            'email' => $row['mail'],
            'url' => $row['url'],
            'content' => $row['text'],
            'created' => (int) $row['created'],
            'parentId' => (int) $row['parent'],
            'children' => $row['children'] ?? [],
        ];
    }

    private function updateCommentsCount(int $cid): void
    {
        $select = $this->db->select(['total' => 'COUNT(1)'])
            ->from('table.comments')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'approved');

        $row = $this->db->fetchRow($select);
        $this->db->query(
            $this->db->update('table.contents')
                ->rows(['commentsNum' => (int) ($row['total'] ?? 0)])
                ->where('cid = ?', $cid)
        );
    }
}
