<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class City extends Model {
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'int';
    protected $guarded = [];
    protected $casts = ['latitude'=>'float','longitude'=>'float','population'=>'integer','elevation'=>'integer'];
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function scopeSearch($query, string $term) {
        $term = trim($term);
        if ($term === '') return $query;
        $like = '%' . addcslashes($term, '%_') . '%';
        return $query->where(function ($q) use ($like) {
            $q->where('name','like',$like)->orWhere('name_en','like',$like)
              ->orWhere('ascii_name','like',$like)->orWhere('alternate_names','like',$like);
        });
    }
}