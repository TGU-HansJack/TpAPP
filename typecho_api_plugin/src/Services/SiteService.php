<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Support\Compatibility;

class SiteService extends AbstractService
{
    public function info(): array
    {
        $options = Compatibility::options();

        return [
            'title' => $options->title,
            'description' => $options->description,
            'subtitle' => $options->subTitle ?? '',
            'siteUrl' => $options->siteUrl ?? $options->index,
            'icp' => $options->icp ?? '',
            'theme' => $options->theme,
            'timezone' => $options->timezone,
            'language' => $options->lang ?? 'zh_CN',
            'avatar' => $options->logoUrl ?? '',
        ];
    }

    public function author(int $uid): array
    {
        $users = $this->app->make(UserService::class);
        $user = $users->findById($uid);
        if (!$user) {
            throw new ApiException('author not found', 404);
        }

        return $user;
    }

    public function updateConfig(array $values): array
    {
        $allowed = [
            'title',
            'description',
            'subTitle',
            'icp',
            'logoUrl',
        ];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $values)) {
                $this->saveOption($key, $values[$key]);
            }
        }

        return $this->info();
    }

    private function saveOption(string $name, $value): void
    {
        $encoded = is_array($value) ? serialize($value) : $value;
        $select = $this->db->select('name')->from('table.options')->where('name = ?', $name)->limit(1);
        $exists = $this->db->fetchRow($select);

        if ($exists) {
            $this->db->query($this->db->update('table.options')->rows(['value' => $encoded])->where('name = ?', $name));
        } else {
            $this->db->query($this->db->insert('table.options')->rows([
                'name' => $name,
                'value' => $encoded,
                'user' => 0,
            ]));
        }

        $options = Compatibility::options();
        $options->{$name} = $value;
    }
}
