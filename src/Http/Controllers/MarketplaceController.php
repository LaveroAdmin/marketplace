<?php

namespace LaveroAdmin\Admin\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaveroAdmin\Admin\Layout\Content;
use LaveroAdmin\Admin\Marketplace\Services\MarketplaceService;

class MarketplaceController extends Controller
{
    public function index(Content $content, MarketplaceService $service)
    {
        $extensions = $service->getExtensionsList();

        $stats = [
            'total' => count($extensions),
            'installed' => count(array_filter($extensions, fn($e) => $e['installed'])),
            'not_installed' => count(array_filter($extensions, fn($e) => !$e['installed'])),
        ];

        return $content
            ->title(trans('lavero-admin-marketplace::marketplace.title'))
            ->description(trans('lavero-admin-marketplace::marketplace.description'))
            ->body(view('lavero-admin-marketplace::index', [
                'extensions' => $extensions,
                'stats' => $stats,
            ]));
    }

    public function runAction(Request $request, MarketplaceService $service): JsonResponse
    {
        $action = (string) $request->input('action');
        $key = (string) $request->input('key');

        if (!in_array($action, ['install', 'update', 'import', 'remove'], true) || empty($key)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid action or extension key',
                'output' => '',
            ], 422);
        }

        $result = $service->runAction($action, $key);

        return response()->json($result);
    }

    public function refresh(MarketplaceService $service): JsonResponse
    {
        $extensions = $service->refreshRepositories();

        return response()->json([
            'status'  => true,
            'message' => trans('lavero-admin-marketplace::marketplace.refreshed_success'),
            'count'   => count($extensions),
        ]);
    }

    public function upload(Request $request, MarketplaceService $service): JsonResponse
    {
        if (!$request->hasFile('archive') || !$request->file('archive')->isValid()) {
            return response()->json([
                'status'  => false,
                'message' => trans('lavero-admin-marketplace::marketplace.upload_invalid_file'),
                'output'  => '',
            ], 422);
        }

        $file = $request->file('archive');
        if (strtolower((string) $file->getClientOriginalExtension()) !== 'zip') {
            return response()->json([
                'status'  => false,
                'message' => trans('lavero-admin-marketplace::marketplace.upload_invalid_file'),
                'output'  => '',
            ], 422);
        }

        $result = $service->installFromZipFile($file->getRealPath());

        return response()->json($result, $result['status'] ? 200 : 422);
    }
}
