<?php

namespace App;

use App\BaseModel;

class Sample extends BaseModel
{
    // protected $dates = ['datecollected', 'datetested', 'datemodified', 'dateapproved', 'dateapproved2', 'dateinitiatedontreatment', 'datesynched'];

    public function tat($datedispatched)
    {
        return \App\Misc::working_days($this->datecollected, $datedispatched);
    }

    public function patient()
    {
    	return $this->belongsTo('App\Patient');
    }

    public function batch()
    {
        return $this->belongsTo('App\Batch');
    }

    public function worksheet()
    {
        return $this->belongsTo('App\Worksheet');
    }

    // Parent sample
    public function parent()
    {
        return $this->belongsTo('App\Sample', 'parentid');
    }

    // Child samples
    public function child()
    {
        return $this->hasMany('App\Sample', 'parentid');
    }

    public function creator()
    {
        return $this->belongsTo('App\User', 'createdby');
    }

    public function canceller()
    {
        return $this->belongsTo('App\User', 'cancelledby');
    }

    public function reviewer()
    {
        return $this->belongsTo('App\User', 'reviewedby');
    }

    public function approver()
    {
        return $this->belongsTo('App\User', 'approvedby');
    }

    public function scopeRuns($query, $sample)
    {
        if($sample->parentid == 0){
            return $query->whereRaw("parentid = {$sample->id} or id = {$sample->id}");
        }
        else{
            return $query->whereRaw("parentid = {$sample->parentid} or id = {$sample->parentid}");
        }
    }

}
