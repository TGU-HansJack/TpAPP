<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Exceptions\ApiException;

class PagesService extends AbstractService
{
    public function all(): array
    {
        $select = $this->db->select(
            'cid AS id',
            'title',
            'slug',
            'text',
            'created',
            'modified'
        )
            ->from('table.contents')
            ->where('type = ?', 'page')
            ->where('status = ?', 'publish')
            ->order('table.contents.order', 'ASC');

        $rows = $this->db->fetchAll($select);
        $pages = [];
        foreach ($rows as $row) {
            $pages[] = $this->format($row);
        }

        return $pages;
    }

    public function find(string $slug): array
    {
        $select = $this->db->select(
            'cid AS id',
            'title',
            'slug',
            'text',
            'created',
            'modified'
        )
            ->from('table.contents')
            ->where('type = ?', 'page')
            ->where('status = ?', 'publish')
            ->where('slug = ?', $slug)
            ->limit(1);

        $row = $this->db->fetchRow($select);
        if (!$row) {
            throw new ApiException('page not found', 404);
        }

        return $this->format($row);
    }

    private function format(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'content' => $row['text'],
            'created' => (int) $row['created'],
            'modified' => (int) $row['modified'],
        ];
    }
}
