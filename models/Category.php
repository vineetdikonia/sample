<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
  protected $table = 'category_master';
  public $timestamps = false;
    
	
  public function scopegetcategoryinfo($query) {
    return $resData = $query->select('category_master.*')->orderBy('category_master.name','ASC')->get();
  }

  /*
   * Where Array
   */ 
  public function scopeWhereArray($query, $array) {
    foreach($array as $where) {
        $query->where($where['field'], $where['operator'], $where['value']);
    }
    return $query;
  }
}

?>
