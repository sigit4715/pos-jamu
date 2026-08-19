<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model {
    protected $fillable=['purchase_id','product_id','product_packaging_id','product_name','unit_name','conversion_quantity','price','quantity','base_quantity','returned_quantity','subtotal'];
    protected function casts(): array { return ['price' => 'decimal:2', 'subtotal' => 'decimal:2', 'quantity' => 'integer', 'conversion_quantity' => 'integer', 'base_quantity' => 'integer', 'returned_quantity' => 'integer']; }
    public function product(){return $this->belongsTo(Product::class);}
    public function purchase(){return $this->belongsTo(Purchase::class);}
    public function packaging(){return $this->belongsTo(ProductPackaging::class, 'product_packaging_id');}
}
