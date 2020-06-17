<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BackupController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    /**
     * BackupController constructor.
     * @param CacheRepository $cache
     */
    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createCompleted(Request $request): JsonResponse
    {
        $server_uuid = $request->input('server_uuid');
        $name = $request->input('name');

        $server = DB::table('servers')->where('uuid', '=', $server_uuid)->get();
        if (count($server) < 1) {
            return response()->json(['success' => false]);
        }

        DB::table('servers')->where('uuid', '=', $server_uuid)->update(['suspended' => 0]);

        DB::table('backups')->where('server_id', '=', $server[0]->id)->where('file', '=', $name)->update([
            'status' => 1
        ]);

        DB::table('backup_logs')->insert([
            'server_id' => $server[0]->id,
            'type' => 'create',
            'result' => 1
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createFailed(Request $request): JsonResponse
    {
        $server_uuid = $request->input('server_uuid');
        $name = $request->input('name');

        $server = DB::table('servers')->where('uuid', '=', $server_uuid)->get();
        if (count($server) < 1) {
            return response()->json(['success' => false]);
        }

        DB::table('servers')->where('uuid', '=', $server_uuid)->update(['suspended' => 0]);

        DB::table('backups')->where('server_id', '=', $server[0]->id)->where('file', '=', $name)->delete();

        DB::table('backup_logs')->insert([
            'server_id' => $server[0]->id,
            'type' => 'create',
            'result' => 0
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function restoreCompleted(Request $request): JsonResponse
    {
        $server_uuid = $request->input('server_uuid');

        $server = DB::table('servers')->where('uuid', '=', $server_uuid)->get();
        if (count($server) < 1) {
            return response()->json(['success' => false]);
        }

        DB::table('servers')->where('uuid', '=', $server_uuid)->update(['suspended' => 0]);

        DB::table('backups')->where('server_id', '=', $server[0]->id)->update([
            'restore' => 0
        ]);

        DB::table('backup_logs')->insert([
            'server_id' => $server[0]->id,
            'type' => 'restore',
            'result' => 1
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function restoreFailed(Request $request): JsonResponse
    {
        $server_uuid = $request->input('server_uuid');

        $server = DB::table('servers')->where('uuid', '=', $server_uuid)->get();
        if (count($server) < 1) {
            return response()->json(['success' => false]);
        }

        DB::table('servers')->where('uuid', '=', $server_uuid)->update(['suspended' => 0]);

        DB::table('backups')->where('server_id', '=', $server[0]->id)->update([
            'restore' => 0
        ]);

        DB::table('backup_logs')->insert([
            'server_id' => $server[0]->id,
            'type' => 'restore',
            'result' => 0
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $download = $this->cache->pull('Server:Backup:Downloads:' . $request->input('token', ''));

        if (is_null($download)) {
            throw new NotFoundHttpException('No file was found using the token provided.');
        }

        return response()->json([
            'path' => array_get($download, 'path'),
            'server' => array_get($download, 'server'),
            'name' => array_get($download, 'name'),
            'backup_folder' => array_get($download, 'backup_folder')
        ]);
    }

}
