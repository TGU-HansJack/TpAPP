<?php

namespace TypechoApiPlugin\Services;

class AdminService extends AbstractService
{
    public function statistics(): array
    {
        return [
            'postsCount' => $this->countContents('post', 'publish'),
            'draftCount' => $this->countContents('post', 'draft'),
            'pagesCount' => $this->countContents('page', 'publish'),
            'commentsCount' => $this->countTable('table.comments'),
            'categoriesCount' => $this->countMetas('category'),
            'tagsCount' => $this->countMetas('tag'),
            'todayViews' => 0,
        ];
    }

    private function countContents(string $type, string $status): int
    {
        $row = $this->db->fetchRow(
            $this->db->select(['total' => 'COUNT(1)'])
                ->from('table.contents')
                ->where('type = ?', $type)
                ->where('status = ?', $status)
        );

        return (int) ($row['total'] ?? 0);
    }

    private function countMetas(string $type): int
    {
        $row = $this->db->fetchRow(
            $this->db->select(['total' => 'COUNT(1)'])
                ->from('table.metas')
                ->where('type = ?', $type)
        );

        return (int) ($row['total'] ?? 0);
    }

    private function countTable(string $table): int
    {
        $row = $this->db->fetchRow(
            $this->db->select(['total' => 'COUNT(1)'])
                ->from($table)
        );

        return (int) ($row['total'] ?? 0);
    }
}
