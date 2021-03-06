<?php

namespace App;

use App\Common;
use App\Sample;
use App\SampleView;

class Misc extends Common
{

	public static function requeue($worksheet_id)
	{
		$samples = Sample::where('worksheet_id', $worksheet_id)->get();

		// Default value for repeatt is 0

		foreach ($samples as $sample) {
			if($sample->parentid == 0){
				if($sample->result == 2 || $sample->result == 3){
					$sample->repeatt = 1;
					$sample->save();
				}
			}
			else{
				$original = $this->check_original($sample->id);

				if($sample->run == 2){
					if( ($sample->result == 3 && $original->result == 3) || 
						($sample->result == 2 && $original->result == 3) || 
						($sample->result != 2 && $original->result == 2) )
					{
						$sample->repeatt = 1;
						$sample->save();
					}
				}

				else if($sample->run == 3){
					$second = $this->check_run($sample->id, 2);

					if( ($sample->result == 3 && $second->result == 2 && $original->result == 3) ||
						($original->result == 2 && $second->result == 1 && $sample->result == 2) ||
						($original->result == 2 && $second->result == 3 && $sample->result == 3) )
					{
						$sample->repeatt = 1;
						$sample->save();
					}
				}
			}
		}
		return true;
	}

	public static function save_repeat($sample_id)
	{
		$original = Sample::find($sample_id);
		if($original->run == 4) return false;

		$sample = new Sample;
		$fields = \App\Lookup::samples_arrays();
		$sample->fill($original->only($fields['sample_rerun']));
		$sample->run++;
		if($sample->parentid == 0) $sample->parentid = $original->id;
		
		$sample->save();
		return $sample;
	}

	public static function check_batch($batch_id, $issample=FALSE)
	{
		if($issample){
			$sample = Sample::find($batch_id);
			$batch_id = $sample->batch_id;
		}
		$double_approval = \App\Lookup::$double_approval; 
		if(in_array(env('APP_LAB'), $double_approval)){
			$where_query = "( receivedstatus=2 OR  (result > 0 AND repeatt = 0 AND approvedby IS NOT NULL AND approvedby2 IS NOT NULL) )";
		}
		else{
			$where_query = "( receivedstatus=2 OR  (result > 0 AND repeatt = 0 AND approvedby IS NOT NULL) )";
		}
		$total = Sample::where('batch_id', $batch_id)->where('parentid', 0)->get()->count();
		$tests = Sample::where('batch_id', $batch_id)
		->whereRaw($where_query)
		->get()
		->count();

		if($total == $tests){
			// DB::table('batches')->where('id', $batch_id)->update(['batch_complete' => 2]);
			\App\Batch::where('id', $batch_id)->update(['batch_complete' => 2]);
			// self::save_tat(\App\SampleView::class, \App\Sample::class, $batch_id);
		}
	}

	public static function check_original($sample_id)
	{
		$lab = auth()->user()->lab_id;

		$sample = Sample::select('samples.*')
		->join('batches', 'samples.batch_id', '=', 'batches.id')
		->where(['batches.lab_id' => $lab, 'samples.id' => $sample_id])
		->get()
		->first();

		return $sample;
	}

	public static function check_previous($sample_id)
	{
		$lab = auth()->user()->lab_id;
		$samples = Sample::select('samples.*')
		->join('batches', 'samples.batch_id', '=', 'batches.id')
		->where(['batches.lab_id' => $lab, 'samples.parentid' => $sample_id])
		->get();

		return $samples;
	}

	public static function check_run($sample_id, $run=2)
	{
		$lab = auth()->user()->lab_id;
		$sample = Sample::select('samples.*')
		->join('batches', 'samples.batch_id', '=', 'batches.id')
		->where(['batches.lab_id' => $lab, 'samples.parentid' => $sample_id, 'run' => $run])
		->get()
		->first();

		return $sample;
	}
	

    public static function get_subtotals($batch_id=NULL, $complete=true)
    {

        $samples = Sample::selectRaw("count(samples.id) as totals, batch_id, result")
            ->join('batches', 'batches.id', '=', 'samples.batch_id')
            ->when($batch_id, function($query) use ($batch_id){
                if (is_array($batch_id)) {
                    return $query->whereIn('batch_id', $batch_id);
                }
                else{
                    return $query->where('batch_id', $batch_id);
                }
            })
            ->when($complete, function($query){
                return $query->where('batch_complete', 2);
            })
            ->where('repeatt', 0)
            ->where('receivedstatus', '!=', 2)
            ->groupBy('batch_id', 'result')
            ->get();

        return $samples;
    }

    public static function get_rejected($batch_id=NULL, $complete=true)
    {
        $samples = Sample::selectRaw("count(samples.id) as totals, batch_id")
            ->join('batches', 'batches.id', '=', 'samples.batch_id')
            ->when($batch_id, function($query) use ($batch_id){
                if (is_array($batch_id)) {
                    return $query->whereIn('batch_id', $batch_id);
                }
                return $query->where('batch_id', $batch_id);
            })
            ->when($complete, function($query){
                return $query->where('batch_complete', 2);
            })
            ->where('receivedstatus', 2)
            ->groupBy('batch_id')
            ->get();

        return $samples;
    }



    public static function get_maxdatemodified($batch_id=NULL, $complete=true)
    {
        $samples = Sample::selectRaw("max(datemodified) as mydate, batch_id")
            ->join('batches', 'batches.id', '=', 'samples.batch_id')
            ->when($batch_id, function($query) use ($batch_id){
                if (is_array($batch_id)) {
                    return $query->whereIn('batch_id', $batch_id);
                }
                return $query->where('batch_id', $batch_id);
            })
            ->when($complete, function($query){
                return $query->where('batch_complete', 2);
            })
            ->where('receivedstatus', '!=', 2)
            ->groupBy('batch_id')
            ->get();

        return $samples;
    }

    public static function get_maxdatetested($batch_id=NULL, $complete=true)
    {
        $samples = Sample::selectRaw("max(datetested) as mydate, batch_id")
            ->join('batches', 'batches.id', '=', 'samples.batch_id')
            ->when($batch_id, function($query) use ($batch_id){
                if (is_array($batch_id)) {
                    return $query->whereIn('batch_id', $batch_id);
                }
                return $query->where('batch_id', $batch_id);
            })
            ->when($complete, function($query){
                return $query->where('batch_complete', 2);
            })
            ->where('receivedstatus', '!=', 2)
            ->groupBy('batch_id')
            ->get();

        return $samples;
    }
}
