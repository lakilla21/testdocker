<?php

namespace Pterodactyl\Http\Controllers\Server;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\AlertsMessageBag;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Repositories\Daemon\BackupRepository;
use Pterodactyl\Traits\Controllers\JavascriptInjection;

class BackupController extends Controller
{
    use JavascriptInjection;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var \Pterodactyl\Repositories\Daemon\BackupRepository
     */
    protected $backupRepository;

    /**
     * @var array
     */
    private $folders = [
        // Only servers which you don't want save all folder and file
        // Egg Id => '/folder to save in server's folder'
        // Default save: /
        // If you want to save not all server's folder, you can add folder to save with egg id
        // For example: 1 => '/data',
    ];

    /**
     * BackupController constructor.
     * @param AlertsMessageBag $alert
     * @param BackupRepository $backupRepository
     * @param Repository $cache
     */
    public function __construct(AlertsMessageBag $alert, BackupRepository $backupRepository, Repository $cache)
    {
        $this->alert = $alert;
        $this->backupRepository = $backupRepository;
        $this->cache = $cache;
    }

    /**
     * @param Request $request
     * @return View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): View
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-backup', $server);
        $this->setRequest($request)->injectJavascript();

        $saves = DB::table('backups')->where('server_id', '=', $server->id)->get();
        $logs = DB::table('backup_logs')->where('server_id', '=', $server->id)->get();

        return view('server.backup.backup', [
            'saves' => $saves,
            'logs' => $logs
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-backup', $server);
        } catch (AuthorizationException $e) {
            return response()->json(["success" => false, "error" => "You don't have a permission to this action!"]);
        }

        $name = trim(strip_tags($request->input('name')));

        if (strlen($name) > 20) {
            return response()->json(["success" => false, "error" => "Too long name (max 20)"]);
        }

        $name = $name . '-' . date('Y-m-d');

        $nameCheck = DB::table('backups')->where('server_id', '=', $server->id)->where('name', '=', $name)->get();
        if (count($nameCheck) > 0) {
            return response()->json(['success' => false, 'error' => 'Name is already exists!']);
        }

        $old_backups = DB::table('backups')->where('server_id', '=', $server->id)->get();
        if ($server->backup_limit != -1) {
            if ($server->backup_limit <= count($old_backups)) {
                return response()->json(['success' => false, 'error' => 'You have maximum ' . $server->backup_limit . ' backups.']);
            }
        }

        $node = $server->getRelation('node');

        $fileName = str::random(10);

        isset($this->folders[$server->egg_id]) ? $folder = $this->folders[$server->egg_id] : $folder = '/';

        try {
            $response = $this->backupRepository->setServer($server)->create([
                'name' => $fileName,
                'folder' => $folder,
                'backup_folder' => $node->backup_folder
            ]);
        } catch (GuzzleException $e) {
            return response()->json(["success" => false, "error" => "Failed to create the backup. Please try again later..."]);
        }

        if (json_decode($response->getBody())->success != "true") {
            return response()->json(['success' => false, 'error' => 'Failed to create backup!']);
        }

        DB::table('servers')->where('id', '=', $server->id)->update(['suspended' => 1]);

        DB::table('backups')->insert(
            ['server_id' => $server->id, 'name' => $name, 'file' => $fileName, 'date' => date('Y-m-d H:i:s')]
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @param $uuid
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function download(Request $request, $uuid, $id)
    {
        $server = $request->attributes->get('server');
        $this->authorize('view-backup', $server);

        $id = (int) $id;

        $check = DB::table('backups')->where('id', '=', $id)->where('server_id', '=', $server->id)->get();
        if (count($check) < 1) {
            return view('server.backup.download', ['errorCode' => '404', 'message' => 'Backup not found!']);
        }

        $token = str::random(30);
        $node = $server->getRelation('node');

        $this->cache->put('Server:Backup:Downloads:' . $token, ['server' => $server->uuid, 'path' => $check[0]->file, 'name' => $check[0]->name, 'backup_folder' => $node->backup_folder], 5);

        return redirect(sprintf('%s://%s:%s/v1/server/backup/download/%s', $node->scheme, $node->fqdn, $node->daemonListen, $token));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function restore(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-backup', $server);
        } catch (AuthorizationException $e) {
            return response()->json(["success" => false, "error" => "You don't have a permission to this action!"]);
        }

        $id = (int) $request->input('id');

        $check = DB::table('backups')->where('id', '=', $id)->where('server_id', '=', $server->id)->get();
        if (count($check) < 1)
            return response()->json(['success' => false, 'error' => 'Backup not found!']);

        $node = $server->getRelation('node');

        isset($this->folders[$server->egg_id]) ? $folder = $this->folders[$server->egg_id] : $folder = '/';

        try {
            $response = $this->backupRepository->setServer($server)->restore([
                'name' => $check[0]->file,
                'folder' => $folder,
                'backup_folder' => $node->backup_folder
            ]);
        } catch (GuzzleException $e) {
            return response()->json(["success" => false, "error" => "Failed to restore the backup. Please try again later..."]);
        }

        if (json_decode($response->getBody())->success != "true") {
            return response()->json(['success' => false, 'error' => 'Failed to restore backup!']);
        }

        DB::table('servers')->where('id', '=', $server->id)->update(['suspended' => 1]);

        DB::table('backups')->where('id', '=', $id)->where('server_id', '=', $server->id)->update([
            'restore' => 1
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $server = $request->attributes->get('server');

        try {
            $this->authorize('view-backup', $server);
        } catch (AuthorizationException $e) {
            return response()->json(["success" => false, "error" => "You don't have a permission to this action!"]);
        }

        $id = (int) $request->input('id');

        $check = DB::table('backups')->where('server_id', '=', $server->id)->where('id', '=', $id)->get();
        if (count($check) < 1) {
            return response()->json(['success' => false, 'error' => 'Backup not found!']);
        }

        $node = $server->getRelation('node');

        try {
            $response = $this->backupRepository->setServer($server)->delete([
                'name' => $check[0]->file,
                'backup_folder' => $node->backup_folder
            ]);
        } catch (GuzzleException $e) {
            return response()->json(["success" => false, "error" => "Failed to delete the backup. Please try again later..."]);
        }

        if (json_decode($response->getBody())->success != "true") {
            return response()->json(['success' => false, 'error' => 'Failed to delete backup!']);
        }

        DB::table('backups')->where('server_id', '=', $server->id)->where('id', '=', $id)->delete();

        return response()->json([
            'success' => true
        ]);
    }
}
