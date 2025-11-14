<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Exceptions\ApiException;

class PostsService extends AbstractService
{
    public function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $pageSize = min(50, max(1, (int) ($filters['pageSize'] ?? 10)));
        $orderField = 'modified' === ($filters['order'] ?? '') ? 'modified' : 'created';
        $includeContent = !empty($filters['includeContent']);

        $select = $this->baseSelect();
        $this->applyFilters($select, $filters);
        $select->page($page, $pageSize)->order("table.contents.{$orderField}", 'DESC');

        $rows = $this->db->fetchAll($select);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->formatPost($row, $includeContent);
        }

        $countSelect = $this->db->select(['total' => 'COUNT(1)'])
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish');

        $this->applyFilters($countSelect, $filters);
        $countRow = $this->db->fetchRow($countSelect);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => (int) ($countRow['total'] ?? 0),
            ],
        ];
    }

    public function getArchive(): array
    {
        $select = $this->db->select(
            'table.contents.cid AS id',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.created'
        )
            ->from('table.contents')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish')
            ->order('table.contents.created', 'DESC');

        $rows = $this->db->fetchAll($select);
        $archive = [];
        foreach ($rows as $row) {
            $year = date('Y', (int) $row['created']);
            if (!isset($archive[$year])) {
                $archive[$year] = [];
            }

            $archive[$year][] = [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'created' => (int) $row['created'],
            ];
        }

        return $archive;
    }

    public function find(string $identifier, array $options = []): array
    {
        $select = $this->baseSelect();
        if (ctype_digit($identifier)) {
            $select->where('table.contents.cid = ?', (int) $identifier);
        } else {
            $select->where('table.contents.slug = ?', $identifier);
        }

        $row = $this->db->fetchRow($select);
        if (!$row) {
            throw new ApiException('post not found', 404);
        }

        return $this->formatPost(
            $row,
            !empty($options['includeContent']),
            isset($options['markdown']) ? (bool) $options['markdown'] : false,
            isset($options['html']) ? (bool) $options['html'] : false
        );
    }

    public function create(array $payload, array $user): array
    {
        $title = trim($payload['title'] ?? '');
        $content = $payload['content'] ?? '';

        if (!$title || !$content) {
            throw new ApiException('title and content are required');
        }

        $now = time();
        $slug = $this->prepareSlug($payload['slug'] ?? '', $title);

        $insert = $this->db->insert('table.contents')->rows([
            'title' => $title,
            'slug' => $slug,
            'text' => $content,
            'created' => $now,
            'modified' => $now,
            'type' => 'post',
            'status' => $payload['status'] ?? 'publish',
            'authorId' => (int) $user['id'],
            'allowComment' => !empty($payload['allowComment']) ? 1 : 0,
        ]);

        $cid = $this->db->query($insert);
        $this->syncMetas($cid, $payload['category'] ?? [], 'category');
        $this->syncMetas($cid, $payload['tags'] ?? [], 'tag');

        if (!empty($payload['banner'])) {
            $this->setField($cid, 'banner', $payload['banner']);
        }

        return $this->find((string) $cid, ['includeContent' => true]);
    }

    public function update(int $cid, array $payload): array
    {
        $select = $this->db->select()->from('table.contents')->where('cid = ?', $cid)->limit(1);
        $post = $this->db->fetchRow($select);

        if (!$post) {
            throw new ApiException('post not found', 404);
        }

        $update = [
            'modified' => time(),
        ];

        if (!empty($payload['title'])) {
            $update['title'] = $payload['title'];
        }

        if (isset($payload['content'])) {
            $update['text'] = $payload['content'];
        }

        if (isset($payload['status'])) {
            $update['status'] = $payload['status'];
        }

        if (isset($payload['allowComment'])) {
            $update['allowComment'] = $payload['allowComment'] ? 1 : 0;
        }

        if (!empty($payload['slug'])) {
            $update['slug'] = $this->prepareSlug($payload['slug'], $payload['title'] ?? $post['title'], $cid);
        }

        if (count($update) > 1) {
            $this->db->query($this->db->update('table.contents')->rows($update)->where('cid = ?', $cid));
        }

        if (array_key_exists('category', $payload)) {
            $this->syncMetas($cid, $payload['category'], 'category');
        }

        if (array_key_exists('tags', $payload)) {
            $this->syncMetas($cid, $payload['tags'], 'tag');
        }

        if (!empty($payload['banner'])) {
            $this->setField($cid, 'banner', $payload['banner']);
        }

        return $this->find((string) $cid, ['includeContent' => true]);
    }

    public function delete(int $cid): void
    {
        $this->db->query($this->db->delete('table.contents')->where('cid = ?', $cid));
        $this->db->query($this->db->delete('table.relationships')->where('cid = ?', $cid));
        $this->db->query($this->db->delete('table.fields')->where('cid = ?', $cid));
    }

    private function baseSelect()
    {
        return $this->db->select(
            'table.contents.cid AS id',
            'table.contents.title',
            'table.contents.slug',
            'table.contents.text',
            'table.contents.created',
            'table.contents.modified',
            'table.contents.commentsNum',
            'table.contents.authorId',
            'table.contents.status',
            'table.users.screenName AS authorName',
            'table.users.mail AS authorMail',
            'table.users.url AS authorUrl'
        )
            ->from('table.contents')
            ->join('table.users', 'table.users.uid = table.contents.authorId', 'LEFT')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.status = ?', 'publish');
    }

    private function applyFilters($select, array $filters): void
    {
        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $select->where('table.contents.title LIKE ? OR table.contents.text LIKE ?', $keyword, $keyword);
        }

        if (!empty($filters['authorId'])) {
            $select->where('table.contents.authorId = ?', (int) $filters['authorId']);
        }

        if (!empty($filters['category'])) {
            $cids = $this->cidsByMeta($filters['category'], 'category');
            $this->applyCidFilter($select, $cids);
        }

        if (!empty($filters['tag'])) {
            $cids = $this->cidsByMeta($filters['tag'], 'tag');
            $this->applyCidFilter($select, $cids);
        }
    }

    private function applyCidFilter($select, array $cids): void
    {
        if (empty($cids)) {
            $select->where('1 = 0');
        } else {
            $select->where('table.contents.cid IN ?', $cids);
        }
    }

    private function formatPost(array $row, bool $includeContent = false, bool $includeMarkdown = false, bool $includeHtml = false): array
    {
        $cid = (int) $row['id'];
        $banner = $this->getField($cid, 'banner') ?: $this->extractFirstImage($row['text']);

        $post = [
            'id' => $cid,
            'title' => $row['title'],
            'slug' => $row['slug'],
            'summary' => $this->summarize($row['text']),
            'bannerImage' => $banner,
            'created' => (int) $row['created'],
            'modified' => (int) $row['modified'],
            'categories' => $this->metas($cid, 'category'),
            'tags' => $this->metas($cid, 'tag'),
            'author' => [
                'id' => (int) $row['authorId'],
                'name' => $row['authorName'],
                'avatar' => $this->avatar($row['authorMail']),
                'url' => $row['authorUrl'],
            ],
            'views' => isset($row['views']) ? (int) $row['views'] : 0,
            'commentsCount' => (int) $row['commentsNum'],
        ];

        if ($includeContent) {
            $post['content'] = $row['text'];
        }

        if ($includeMarkdown) {
            $post['markdown'] = $row['text'];
        }

        if ($includeHtml) {
            $post['html'] = $this->renderHtml($row['text']);
        }

        return $post;
    }

    private function summariseText(string $text, int $length = 160): string
    {
        $clean = trim(strip_tags($text));
        $clean = preg_replace('/\s+/', ' ', $clean);
        if (mb_strlen($clean) <= $length) {
            return $clean;
        }

        return mb_substr($clean, 0, $length) . '...';
    }

    private function summarize(string $text): string
    {
        return $this->summariseText($text);
    }

    private function extractFirstImage(string $text): ?string
    {
        if (preg_match('/!\[[^\]]*]\(([^)]+)\)/', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $text, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function metas(int $cid, string $type): array
    {
        $select = $this->db->select(
            'table.metas.mid AS id',
            'table.metas.name',
            'table.metas.slug'
        )
            ->from('table.relationships')
            ->join('table.metas', 'table.relationships.mid = table.metas.mid', 'LEFT')
            ->where('table.relationships.cid = ?', $cid)
            ->where('table.metas.type = ?', $type);

        $rows = $this->db->fetchAll($select);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
            ];
        }

        return $items;
    }

    private function cidsByMeta($values, string $type): array
    {
        $metaIds = $this->resolveMetaIds($values, $type);
        if (empty($metaIds)) {
            return [];
        }

        $select = $this->db->select('cid')
            ->from('table.relationships')
            ->where('mid IN ?', $metaIds);

        $rows = $this->db->fetchAll($select);
        return array_map('intval', array_column($rows, 'cid'));
    }

    private function resolveMetaIds($values, string $type): array
    {
        if (is_string($values)) {
            $values = array_filter(array_map('trim', explode(',', $values)));
        }

        if (!is_array($values)) {
            return [];
        }

        $ids = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            if (ctype_digit($value)) {
                $select = $this->db->select('mid')->from('table.metas')
                    ->where('mid = ?', (int) $value)
                    ->where('type = ?', $type);
            } else {
                $select = $this->db->select('mid')->from('table.metas')
                    ->where('slug = ?', $value)
                    ->where('type = ?', $type);
            }

            $meta = $this->db->fetchRow($select);
            if ($meta) {
                $ids[] = (int) $meta['mid'];
            }
        }

        return array_values(array_unique($ids));
    }

    private function syncMetas(int $cid, $values, string $type): void
    {
        $metaIds = $this->resolveMetaIds($values, $type);

        $existing = $this->db->fetchAll(
            $this->db->select('table.relationships.mid')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid', 'LEFT')
                ->where('table.relationships.cid = ?', $cid)
                ->where('table.metas.type = ?', $type)
        );

        foreach ($existing as $row) {
            $this->db->query(
                $this->db->delete('table.relationships')
                    ->where('cid = ?', $cid)
                    ->where('mid = ?', $row['mid'])
            );
        }

        foreach ($metaIds as $mid) {
            $this->db->query($this->db->insert('table.relationships')->rows([
                'cid' => $cid,
                'mid' => $mid,
            ]));
        }
    }

    private function avatar(?string $mail): string
    {
        $hash = md5(strtolower(trim($mail ?? '')));
        return 'https://www.gravatar.com/avatar/' . $hash . '?s=120&d=identicon';
    }

    private function renderHtml(string $text): string
    {
        if (class_exists('\Markdown')) {
            return \Markdown::convert($text);
        }

        if (class_exists('\Typecho\Markdown')) {
            return \Typecho\Markdown::convert($text);
        }

        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    private function prepareSlug(string $slug, string $title, int $ignore = 0): string
    {
        $slug = trim($slug);
        if (!$slug) {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        }

        $slug = trim($slug, '-');

        $check = $this->db->select('cid')->from('table.contents')->where('slug = ?', $slug);
        if ($ignore) {
            $check->where('cid <> ?', $ignore);
        }

        $exists = $this->db->fetchRow($check);
        if ($exists) {
            $slug .= '-' . dechex(time());
        }

        return $slug ?: (string) time();
    }

    private function setField(int $cid, string $name, string $value): void
    {
        $select = $this->db->select('cid')->from('table.fields')
            ->where('cid = ?', $cid)
            ->where('name = ?', $name)
            ->limit(1);

        $field = $this->db->fetchRow($select);
        $rows = [
            'str_value' => $value,
            'type' => 'str',
        ];

        if ($field) {
            $this->db->query($this->db->update('table.fields')->rows($rows)->where('cid = ?', $cid)->where('name = ?', $name));
        } else {
            $this->db->query($this->db->insert('table.fields')->rows(array_merge($rows, [
                'cid' => $cid,
                'name' => $name,
            ])));
        }
    }

    private function getField(int $cid, string $name): ?string
    {
        $select = $this->db->select('str_value', 'text_value')
            ->from('table.fields')
            ->where('cid = ?', $cid)
            ->where('name = ?', $name)
            ->limit(1);

        $field = $this->db->fetchRow($select);
        if (!$field) {
            return null;
        }

        return $field['str_value'] ?: $field['text_value'];
    }
}
