<?php

namespace TypechoApiPlugin\Services;

class TaxonomyService extends AbstractService
{
    public function categories(): array
    {
        $select = $this->db->select(
            'mid AS id',
            'name',
            'slug',
            'count'
        )
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->order('table.metas.order', 'ASC');

        $rows = $this->db->fetchAll($select);
        return $this->mapMetas($rows);
    }

    public function tags(): array
    {
        $select = $this->db->select(
            'mid AS id',
            'name',
            'slug',
            'count'
        )
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->order('count', 'DESC');

        $rows = $this->db->fetchAll($select);
        return $this->mapMetas($rows);
    }

    private function mapMetas(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'count' => (int) $row['count'],
            ];
        }

        return $items;
    }
}
