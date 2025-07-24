<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    //
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'files'; // Specify the table name if it differs from the pluralized model name
    protected $fillable = [
        'name',
        'id',
        'type',
        'parent_id',
    ];
    function parent()
    {
        return $this->belongsTo(file::class, 'parent_id');
    }
    function children()
    {
        return $this->hasMany(file::class, 'parent_id');
    }
    function path()
    {
        $path = [];
        $current = $this;
        while ($current) {
            $extensions = ['.pdf', '.png', '.jpeg', '.jpg', '.webp'];

            $shouldUseName = false;

            foreach ($extensions as $ext) {
                if (str_contains($current->name, $ext) || str_contains($current->id, $ext)) {
                    $shouldUseName = true;
                    break;
                }
            }

            array_unshift($path, $shouldUseName ? $current->name : $current->id);
            $current = $current->parent;
        }
        return implode('/', $path);
    }
}
