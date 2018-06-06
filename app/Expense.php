<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'supplier_id',
        'description',
        'date',
        'amount',
        'patient_history_id'
    ];

    /**
     * Proveedor al que se le asocia el gasto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    /**
     * Servicio al que se asocia este gasto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patientHistory()
    {
        return $this->belongsTo(PatientHistory::class, 'patient_history_id')->withTrashed();
    }
}
