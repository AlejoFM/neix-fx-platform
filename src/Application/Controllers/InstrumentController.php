<?php

namespace App\Application\Controllers;

use App\Application\Services\InstrumentService;

class InstrumentController
{
    private InstrumentService $instrumentService;

    public function __construct(InstrumentService $instrumentService)
    {
        $this->instrumentService = $instrumentService;
    }

    public function getAll(): void
    {
        header('Content-Type: application/json');

        try {
            $instruments = $this->instrumentService->getAllInstruments();
            $data = array_map(fn($inst) => $inst->toArray(), $instruments);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener instrumentos',
            ]);
        }
    }
}
