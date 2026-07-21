<?php

namespace App\Models;

use CodeIgniter\Model;

class PromotionModel extends Model
{
    protected $table='promotion';
    protected $allowedFields = [
        'nom',
        'reduction_pourcentage',
        'date_debut',
        'date_fin',
        'actif'
    ];
    public function getPromotionActive (string $typeOperation = 'transfert'): ?array{
        $now=date('Y-m-d H:i:s');
        return $this->where('actif',1)
                    ->where('type_operation_code',$typeOperation)
                    ->where('date_debut <=', $now)
                    ->first();
    }
}