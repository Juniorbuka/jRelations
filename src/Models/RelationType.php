<?php

namespace Jevo\JRelations\Models;

use EvolutionCMS\Models\SiteTemplate;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class RelationType extends Model
{
    protected $table = 'resource_relation_types';

    public $timestamps = true;

    protected $fillable = [
        'slug',
        'name_uk',
        'name_en',
    ];

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(
            SiteTemplate::class,
            'resource_relation_type_templates',
            'relation_type_id',
            'template_id'
        );
    }
}
