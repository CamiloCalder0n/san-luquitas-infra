<?php

namespace App\Http\Requests;

use App\Models\Novedad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NovedadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El portal no tiene login (limitación aceptada por el equipo).
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'resumen' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string'],
            'categoria' => ['required', Rule::in(array_keys(Novedad::CATEGORIAS))],
            'fecha_publicacion' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'categoria.in' => 'La categoría debe ser noticia, comunicado o evento.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'titulo' => 'título',
            'resumen' => 'resumen',
            'contenido' => 'contenido',
            'categoria' => 'categoría',
            'fecha_publicacion' => 'fecha de publicación',
        ];
    }
}
