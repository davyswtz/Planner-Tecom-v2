<?php
namespace App\Services;
use App\Models\Tecnico;
class TecnicoService{

    public function getTecnicos(?string $regiao = null)
    {
        $query = Tecnico::query();

        if ($regiao) {
            $query->where('regiao', $regiao);
        }

        return $query->get();
    }

    public function showTecnico(Tecnico $tecnico){
        return $tecnico;
    }

}