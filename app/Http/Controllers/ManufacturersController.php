<?php

namespace App\Http\Controllers;

use App\Models\Manufacturers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ManufacturersController extends Controller
{
    public function syncManufacturers()
    {
        $response = Http::withHeaders([
            'X-Api-Key'    => '2BeBXg6QnEsEd55snsUcjnfBBhiQ28ttQfNDxhVJLqGxrx7dJ2Af',
            'Content-Type' => 'application/json',
        ])->post('https://webservice.tecalliance.services/pegasus-3-0/services/TecdocToCatDLB.jsonEndpoint?js', [
            'getManufacturers2' => [
                'country'            => 'CO',
                'lang'               => 'es',
                'linkingTargetType'  => 'VOLB',
                'provider'           => 25100,
            ],
        ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Error al consultar TecDoc'], 500);
        }

        $manufacturers = $response->json('data.array');

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
