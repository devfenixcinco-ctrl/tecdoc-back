<?php

namespace App\Http\Controllers;

use App\Models\Manufacturers;
use App\Services\ClientRegistry;
use App\Services\TecdocClient;
use Illuminate\Http\Request;
use RuntimeException;

class ManufacturersController extends Controller
{
    public function syncManufacturers(TecdocClient $tecdoc, ClientRegistry $clients)
    {
        $client = $clients->default();

        try {
            $response = $tecdoc->call($client, 'getManufacturers2', [
                'country'            => $clients->get($client)['country'],
                'linkingTargetType'  => 'VOLB',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Error al consultar TecDoc'], 500);
        }

        $manufacturers = data_get($response, 'data.array');

        if (empty($manufacturers)) {
            return response()->json(['message' => 'No se encontraron fabricantes'], 200);
        }

        foreach ($manufacturers as $item) {
            Manufacturers::updateOrCreate(
                ['id' => $item['manuId']],
                [
                    'name'         => $item['manuName'],
                    'linkingType' => $item['linkingTargetTypes'],
                ]
            );
        }

        return response()->json([
            'message' => 'Fabricantes sincronizados correctamente',
            'total'   => count($manufacturers),
        ]);
    }

    public function getManufacturers($type = null)
    {
        if ($type) {
            $manufacturers = Manufacturers::whereLike('linkingType', "%$type%" )->get();
        } else {
            $manufacturers = Manufacturers::all();
        }

        return response()->json($manufacturers);
    }
}
