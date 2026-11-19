<?php

namespace App\Http\Controllers;

use App\Models\Novedad;

class InicioController extends Controller
{
    public function __invoke()
    {
        $ultimas = Novedad::query()
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->take(3)
            ->get();

        return view('inicio', compact('ultimas'));
    }
}
