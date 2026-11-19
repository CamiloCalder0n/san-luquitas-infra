<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Novedad extends Model
{
    /** @use HasFactory<\Database\Factories\NovedadFactory> */
    use HasFactory;

    /** Categorías permitidas: valor guardado => texto que se muestra. */
    public const CATEGORIAS = [
        'noticia' => 'Noticia',
        'comunicado' => 'Comunicado',
        'evento' => 'Evento',
    ];

    protected $table = 'novedades';

    protected $fillable = [
        'titulo',
        'resumen',
        'contenido',
        'categoria',
        'fecha_publicacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date',
        ];
    }

    public function nombreCategoria(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? $this->categoria;
    }
}
