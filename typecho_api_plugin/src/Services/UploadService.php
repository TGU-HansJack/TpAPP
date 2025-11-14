<?php

namespace TypechoApiPlugin\Services;

use TypechoApiPlugin\Exceptions\ApiException;
use TypechoApiPlugin\Support\Compatibility;

class UploadService extends AbstractService
{
    public function handle(array $files, array $payload): array
    {
        if (!empty($files['file']) && $files['file']['error'] === UPLOAD_ERR_OK) {
            return $this->storeFile($files['file']['tmp_name'], $files['file']['name']);
        }

        if (!empty($payload['base64']) && !empty($payload['filename'])) {
            $data = base64_decode($payload['base64']);
            if (false === $data) {
                throw new ApiException('无法解析 base64 数据');
            }

            $tmp = tempnam(sys_get_temp_dir(), 'typecho_api');
            file_put_contents($tmp, $data);
            $result = $this->storeFile($tmp, $payload['filename']);
            @unlink($tmp);
            return $result;
        }

        throw new ApiException('未检测到上传内容');
    }

    private function storeFile(string $path, string $original): array
    {
        $ext = pathinfo($original, PATHINFO_EXTENSION);
        $filename = uniqid('upload_', true) . ($ext ? '.' . $ext : '');

        $targetDir = Compatibility::rootDir() . '/usr/uploads/api/' . date('Y/m');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetPath = $targetDir . '/' . $filename;
        if (!@rename($path, $targetPath)) {
            if (!copy($path, $targetPath)) {
                throw new ApiException('保存文件失败');
            }
            @unlink($path);
        }

        $options = Compatibility::options();
        $baseUrl = rtrim($options->siteUrl ?? $options->index, '/');
        $relative = '/usr/uploads/api/' . date('Y/m') . '/' . $filename;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $targetPath) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }

        return [
            'url' => $baseUrl . $relative,
            'size' => filesize($targetPath),
            'mime' => $mime,
            'path' => $relative,
        ];
    }
}
