<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index(Request $request)
    {
        // Filtros opcionales desde query string
        $city = $request->query('city');
        $min  = $request->query('min_price');
        $max  = $request->query('max_price');

        // retornar todos los espacios
        $spaces = Space::all();

        return response()->json($spaces);
    }

    public function show($id)
    {
        // Lógica para mostrar un espacio específico
    }

    public function store(Request $request)
    {
        // Lógica para crear un nuevo espacio
    }

    public function update(Request $request, $id)
    {
        // Lógica para actualizar un espacio existente
    }

    public function destroy($id)
    {
        // Lógica para eliminar un espacio
    }
}
