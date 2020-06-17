<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Repositories\Daemon\BackupRepository;

class BackupDeletionService
{
    /**
     * @var /Pterodactyl\Repositories\Daemon\BackupRepository
     */
    protected $backupRepository;

    /**
     * BackupDeletionService constructor.
     * @param BackupRepository $backupRepository
     */
    public function __construct(BackupRepository $backupRepository)
    {
        $this->backupRepository = $backupRepository;
    }

    /**
     * @param $server
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($server)
    {
        $node = $server->getRelation('node');

        $backups = DB::table('backups')->where('server_id', '=', $server->id)->get();
        foreach ($backups as $backup) {
            $response = $this->backupRepository->setServer($server)->deleteAsAdmin([
                'name' => $backup->file,
                'backup_folder' => $node->backup_folder,
                'uuid' => $server->uuid
            ]);

            if (json_decode($response->getBody())->success == "true") {
                DB::table('backups')->where('id', '=', $backup->id)->where('server_id', '=', $server->id)->delete();
            }
        }
    }
}

?>
