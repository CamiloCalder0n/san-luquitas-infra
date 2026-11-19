<?php

namespace App\Http\Controllers;

use App\Http\Requests\NovedadRequest;
use App\Models\Novedad;
use Illuminate\Http\Request;

class NovedadController extends Controller
{
    public function index(Request $request)
    {
        $categoria = $request->query('categoria');

        if (! is_string($categoria) || ! array_key_exists($categoria, Novedad::CATEGORIAS)) {
            $categoria = null;
        }

        $novedades = Novedad::query()
            ->when($categoria, fn ($consulta) => $consulta->where('categoria', $categoria))
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(6)
            ->withQueryString();

        return view('novedades.index', compact('novedades', 'categoria'));
    }

    public function create()
    {
        return view('novedades.create', ['novedad' => new Novedad]);
    }

    public function store(NovedadRequest $request)
    {
        $novedad = Novedad::create($request->validated());

        return redirect()->route('novedades.show', $novedad)->with('ok', 'Novedad creada.');
    }

    public function show(Novedad $novedad)
    {
        return view('novedades.show', compact('novedad'));
    }

    public function edit(Novedad $novedad)
    {
        return view('novedades.edit', compact('novedad'));
    }

    public function update(NovedadRequest $request, Novedad $novedad)
    {
        $novedad->update($request->validated());

        return redirect()->route('novedades.show', $novedad)->with('ok', 'Novedad actualizada.');
    }

    public function destroy(Novedad $novedad)
    {
        $novedad->delete();

        return redirect()->route('novedades.index')->with('ok', 'Novedad eliminada.');
    }
}
