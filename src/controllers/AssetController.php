<?php

namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\models\Asset;

class AssetController {

    // Handle GET /assets
    public static function getAssets(Request $request, Response $response) {
        // Obtener todos los parámetros de la query string (ej: ?type={bitcoin}) como un array asociativo.
        $filters = $request->getQueryParams();

        // Validar que el filtro de tipo, si existe, sea uno de los valores permitidos.
        if (isset($filters['type'])) {
            $validAssetNames = ['Gold', 'Silver', 'YPF', 'Petroleum', 'Bitcoin', 'Apple', 'Soybean'];
            if (!in_array($filters['type'], $validAssetNames)) {
                $response->getBody()->write(json_encode(['error' => 'El valor para el filtro type es invalido.']));
                return $response->withStatus(400);
            }
        }

        // Validar que los filtros de precio, si existen, sean numéricos.
        if (isset($filters['min_price']) && !is_numeric($filters['min_price'])) {
            $response->getBody()->write(json_encode(['error' => 'El valor del filtro min_price debe ser un número.']));
            return $response->withStatus(400);
        }

        if (isset($filters['max_price']) && !is_numeric($filters['max_price'])) {
            $response->getBody()->write(json_encode(['error' => 'El valor del filtro max_price debe ser un número.']));
            return $response->withStatus(400);
        }

        // Llamar a un método en el modelo asset que devuelvo los assets filtrados.
        $assets = Asset::getFiltered($filters);

        $response->getBody()->write(json_encode($assets));
        return $response->withStatus(200);
    }

    // Handle GET /assets/{asset_id}/history/{quantity}
    public static function getAssetHistory(Request $request, Response $response, array $args) {
        // Obtener el ID del activo y la cantidad de movimientos a mostrar desde la URL.
        $asset_id = $args['asset_id'];
        $quantity = $args['quantity'];

        // --- Validación de la cantidad  ---

        // 1. Validamos que la cantidad sea un string que contenga solo dígitos.
        //    Esto rechaza decimales ("3.14"), negativos ("-1") y texto ("abc").
        if (!ctype_digit((string)$quantity)) {
            $response->getBody()->write(json_encode(['error' => 'El valor para quantity debe ser un número entero entre 1 y 5.']));
            return $response->withStatus(400);
        }

        // 2. Ahora que sabemos que es un entero, lo convertimos y validamos el rango.
        $limit = (int)$quantity;
        if ($limit < 1 || $limit > 5) {
            $response->getBody()->write(json_encode(['error' => 'El valor para quantity debe ser un numero entero entre 1 y 5.']));
            return $response->withStatus(400);
        }

        // Llamar al modelo de asset para obtener el historial del activo.
        $history = Asset::getHistoryForAsset($asset_id, $limit);
        if ($history === false || empty($history)) {
            $response->getBody()->write(json_encode(['error' => 'No se registraron transferencias de este activo.']));
            return $response->withStatus(404);
        }
        // Devolver la respuesta.
        $response->getBody()->write(json_encode($history));
        return $response->withStatus(200);
    }

    // Handle PUT /assets
    public static function updateAssets(Request $request, Response $response) {
        // Autorización: Verificar que el usuario sea administrador.
        // El middleware ya nos dio los datos del usuario.
        $loggedInUser = $request->getAttribute('user');
        if (!$loggedInUser || !$loggedInUser['is_admin']) {
            $response->getBody()->write(json_encode(['error' => 'Acceso denegado. Se requiere ser administrador.']));
            return $response->withStatus(401);
        }

        // Obtener todos los activos existentes.
        $assets = Asset::getAll();

        // Iterar sobre cada activo para actualizar su precio.
        foreach ($assets as $asset) {
            // Calcular el nuevo precio usando la lógica de variación del modelo.
            $lastUpdateTimestamp = strtotime($asset['last_update']);
            $newPrice = Asset::variarPrecioPorTiempo($asset['current_price'], $lastUpdateTimestamp);
            Asset::updatePrice($asset['id'], $newPrice);
        }

        $response->getBody()->write(json_encode(['message' => 'Precios de los activos actualizados con exito.']));
        return $response->withStatus(200);
    }
}
