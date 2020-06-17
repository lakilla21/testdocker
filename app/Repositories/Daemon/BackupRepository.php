<?php

namespace Pterodactyl\Repositories\Daemon;

use Psr\Http\Message\ResponseInterface;

class BackupRepository extends BaseRepository
{
    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/backup/create', [
            'json' => $data,
        ]);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function restore(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/backup/restore', [
            'json' => $data,
        ]);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/backup/delete', [
            'json' => $data,
        ]);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteAsAdmin(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/backup/delete/admin', [
            'json' => $data,
        ]);
    }

    /**
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deleteCommand(array $data): ResponseInterface
    {
        return $this->getHttpClient()->request('POST', 'server/backup/delete/command', [
            'json' => $data,
        ]);
    }
}
