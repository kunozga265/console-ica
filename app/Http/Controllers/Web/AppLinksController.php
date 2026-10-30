<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

/**
 * Domain association files that let iOS (Universal Links) and Android (App Links)
 * open ica-app.com check-in and sermon links straight in the mobile app.
 */
class AppLinksController extends Controller
{
    public function apple()
    {
        $c = config('services.app_links');
        $components = array_map(fn ($p) => ['/' => $p], $c['paths']);

        return response()->json([
            'applinks' => [
                'apps' => [], // required (empty) by older iOS versions
                'details' => array_map(fn ($id) => [
                    'appID'      => $id,           // iOS 12 and earlier
                    'appIDs'     => [$id],         // iOS 13+
                    'paths'      => $c['paths'],
                    'components' => $components,
                ], $c['ios_app_ids']),
            ],
        ]);
    }

    public function android()
    {
        $c = config('services.app_links');

        return response()->json($c['android_sha256'] ? [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target'   => [
                'namespace'                => 'android_app',
                'package_name'             => $c['android_package'],
                'sha256_cert_fingerprints' => array_values($c['android_sha256']),
            ],
        ]] : []);
    }
}
