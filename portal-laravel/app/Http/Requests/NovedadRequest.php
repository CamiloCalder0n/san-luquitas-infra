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
            'contenido' => ['required', 'string', 'max:10000'],
            'categoria' => ['required', Rule::in(array_keys(Novedad::CATEGORIAS))],
            'fecha_publicacion' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'categoria.in' => 'La categoría debe ser noticia, comunicado o evento.',
            'date_format' => 'El campo :attribute debe ser una fecha válida (año de 4 dígitos).',
            'after_or_equal' => 'El campo :attribute debe ser del año 2000 en adelante.',
            'before_or_equal' => 'El campo :attribute no puede ser después del año 2100.',
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
