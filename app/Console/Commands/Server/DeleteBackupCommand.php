<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Console\Commands\Server;

use Pterodactyl\Models\Node;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Repositories\Daemon\BackupRepository;

class DeleteBackupCommand extends Command
{
    /**
     * @var string
     */
    protected $description = 'Deletes backups that do not have a server.';

    /**
     * @var string
     */
    protected $signature = 'p:backup:delete';

    /**
     * @var \Pterodactyl\Repositories\Daemon\BackupRepository
     */
    protected $backupRepository;

    /**
     * DeleteBackupCommand constructor.
     * @param BackupRepository $backupRepository
     */
    public function __construct(BackupRepository $backupRepository)
    {
        parent::__construct();

        $this->backupRepository = $backupRepository;
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $this->line('');

        // Delete from DB
        $backups = DB::table('backups')->get();
        foreach ($backups as $backup) {
            $server = DB::table('servers')->where('id', '=', $backup->server_id)->get();
            if (count($server) < 1) {
                DB::table('backups')->where('id', '=', $backup->id)->delete();
            }
        }

        // Delete from nodes
        $nodes = DB::table('nodes')->get();
        foreach ($nodes as $node) {
            $uuids = [];
            $serversInNode = DB::table('servers')->where('node_id', '=', $node->id)->get();

            foreach ($serversInNode as $server) {
                array_push($uuids, $server->uuid);
            }

            $data = json_decode(json_encode($node), true);
            $nodeModal = new Node($data);

            try {
                $response = $this->backupRepository->setNode($nodeModal)->deleteCommand([
                    'uuids' => json_encode($uuids),
                    'backup_folder' => $node->backup_folder
                ]);

                if (json_decode($response->getBody())->success != "true") {
                    $this->error($node->name . ' node failed! Message: ' . json_decode($response->getBody())->error);
                } else {
                    $this->info($node->name . ' node ok!');
                }
            } catch (RequestException $e) {
                $this->error('Failed to connect to ' . $node->name . ' node!');
            }
        }

        $this->line('');
        $this->info('Unnecessary backups deleted.');
        $this->line('');
    }
}
