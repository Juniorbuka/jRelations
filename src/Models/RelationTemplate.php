<?php

namespace Jevo\JRelations\Models;

use Illuminate\Database\Eloquent\Model;

class RelationTemplate extends Model
{
    protected $table = 'resource_relation_templates';

    protected $primaryKey = 'template_id';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['template_id'];
}
