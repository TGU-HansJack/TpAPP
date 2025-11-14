<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Application;
use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Support\Compatibility;
use TypechoApiPlugin\Support\UserMetaRepository;

class UserService extends AbstractService
{
    private UserMetaRepository $meta;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->meta = new UserMetaRepository($app);
    }

    public function authenticate(string $username, string $password): array
    {
        $select = $this->db->select()
            ->from('table.users')
            ->where('name = ? OR mail = ?', $username, $username)
            ->limit(1);

        $user = $this->db->fetchRow($select);
        if (!$user || !Compatibility::hashValidate($password, $user['password'])) {
            throw new ApiException('invalid credentials', 401);
        }

        return $this->formatUser($user);
    }

    public function findById(int $uid): ?array
    {
        $select = $this->db->select()->from('table.users')->where('uid = ?', $uid)->limit(1);
        $user = $this->db->fetchRow($select);
        return $user ? $this->formatUser($user) : null;
    }

    public function updateProfile(int $uid, array $params): array
    {
        $select = $this->db->select()->from('table.users')->where('uid = ?', $uid)->limit(1);
        $user = $this->db->fetchRow($select);

        if (!$user) {
            throw new ApiException('user not found', 404);
        }

        $update = [];

        if (array_key_exists('nickname', $params) && null !== $params['nickname']) {
            $update['screenName'] = $params['nickname'];
        }

        if (array_key_exists('email', $params) && null !== $params['email']) {
            $update['mail'] = $params['email'];
        }

        if (array_key_exists('url', $params) && null !== $params['url']) {
            $update['url'] = $params['url'];
        }

        if ($update) {
            $this->db->query($this->db->update('table.users')->rows($update)->where('uid = ?', $uid));
        }

        if (!empty($params['avatar'])) {
            $this->meta->set($uid, 'avatar', $params['avatar']);
        }

        return $this->findById($uid);
    }

    public function updatePassword(int $uid, string $oldPassword, string $newPassword): void
    {
        $select = $this->db->select()->from('table.users')->where('uid = ?', $uid)->limit(1);
        $user = $this->db->fetchRow($select);

        if (!$user) {
            throw new ApiException('user not found', 404);
        }

        if (!Compatibility::hashValidate($oldPassword, $user['password'])) {
            throw new ApiException('旧密码不正确', 400);
        }

        $hash = Compatibility::hash($newPassword);
        $this->db->query($this->db->update('table.users')->rows(['password' => $hash])->where('uid = ?', $uid));
    }

    public function postsByAuthor(int $uid, int $page, int $pageSize): array
    {
        $posts = $this->app->make(PostsService::class);
        return $posts->paginate([
            'page' => $page,
            'pageSize' => $pageSize,
            'authorId' => $uid,
        ]);
    }

    private function formatUser(array $user): array
    {
        $avatar = $this->meta->get((int) $user['uid'], 'avatar');
        if (!$avatar) {
            $hash = md5(strtolower(trim($user['mail'] ?? '')));
            $avatar = 'https://www.gravatar.com/avatar/' . $hash . '?s=200&d=identicon';
        }

        return [
            'id' => (int) $user['uid'],
            'username' => $user['name'],
            'nickname' => $user['screenName'] ?: $user['name'],
            'email' => $user['mail'],
            'url' => $user['url'],
            'avatar' => $avatar,
            'group' => $user['group'],
            'created' => isset($user['created']) ? (int) $user['created'] : 0,
        ];
    }
}
