<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Api\V1\Requests\VlRequest;
use App\Api\V1\Requests\VlCompleteRequest;

use App\Lookup;
use App\ViralsampleView;
use App\Viralbatch;
use App\Viralpatient;
use App\Viralsample;

class VlController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('jwt:auth', []);
    }

    public function vl(VlRequest $request)
    {
        $code = $request->input('mflCode');
        $ccc_number = $request->input('patient_identifier');
        $datecollected = $request->input('datecollected');
        $datereceived = $request->input('datereceived');
        $dob = $request->input('dob');
        $lab = $request->input('lab');

        $facility = Lookup::facility_mfl($code);
        $age = Lookup::calculate_viralage($datecollected, $dob);
        // $sex = Lookup::get_gender($gender);

        $sample_exists = ViralsampleView::sample($facility, $ccc_number, $datecollected)->first();
        $fields = Lookup::viralsamples_arrays();

        if($sample_exists){
            return json_encode("VL CCC # {$ccc_number} collected on {$datecollected} already exists in database.");
        }

        $batch = Viralbatch::existing($facility, $datereceived, $lab)->withCount(['sample'])->get()->first();

        if($batch && $batch->sample_count < 10){
            unset($batch->sample_count);
        }
        else if($batch && $batch->sample_count > 9){
            unset($batch->sample_count);
            $batch->full_batch();
            $batch = new Batch;
        }
        else{
            $batch = new Batch;
        }

        

        $batch->lab_id = $lab;
        $batch->user_id = 0;
        $batch->facility_id = $facility;
        $batch->datereceived = $datereceived;
        $batch->site_entry = 0;
        $batch->save();

        $patient = Viralpatient::existing($facility, $ccc_number)->get()->first();

        if(!$patient){
            $patient = new Viralpatient;
        } 

        $patient->fill($request->only($fields['patient']));
        $patient->patient = $ccc_number;
        $patient->facility_id = $facility;
        $patient->save();

        $sample = new Viralsample;
        $sample->fill($request->only($fields['sample']));
        $sample->batch_id = $batch->id;
        $sample->patient_id = $patient->id;
        $sample->age = $age;
        $sample->save();

        $sample->load(['patient', 'batch']);
        return $sample;

    }

    public function complete_result(VlCompleteRequest $request)
    {
        $editted = $request->input('editted');
        $lab = $request->input('lab');
        $code = $request->input('mflCode');
        $specimenlabelID = $request->input('specimenlabelID');
        $patient_identifier = $request->input('patient_identifier');
        $datecollected = $request->input('datecollected');
        $datereceived = $request->input('datereceived');
        $datedispatched = $request->input('datedispatched');
        $dob = $request->input('dob');
        // $sex = Lookup::get_gender($gender);
        
        $justification = $request->input('justification');
        $prophylaxis = $request->input('prophylaxis');

        $facility = Lookup::facility_mfl($code);
        $age = Lookup::calculate_viralage($datecollected, $dob);

        $sample_exists = ViralsampleView::sample($facility, $patient_identifier, $datecollected)->first();
        $fields = Lookup::viralsamples_arrays();

        if($sample_exists && !$editted){

            return json_encode("VL CCC # {$patient_identifier} collected on {$datecollected} already exists in database.");
        }

        if(!$editted){
            $batch = Viralbatch::existing($facility, $datereceived, $lab)->withCount(['sample'])->get()->first();

            if($batch && $batch->sample_count < 10){
                unset($batch->sample_count);
            }
            else if($batch && $batch->sample_count > 9){
                unset($batch->sample_count);
                $batch->full_batch();
                $batch = new Batch;
            }
            else{
                $batch = new Batch;
            }

            $batch->lab_id = $lab;
            $batch->user_id = 0;
            $batch->facility_id = $facility;
            $batch->datereceived = $datereceived;
            $batch->datedispatched = $datedispatched;
            $batch->site_entry = 0;
            $batch->synched = 5;
            $batch->save();            
        }

        $patient = Viralpatient::existing($facility, $patient_identifier)->get()->first();

        if(!$patient){
            $patient = new Viralpatient;
        } 

        $patient->fill($request->only($fields['patient'])); 
        $patient->patient = $patient_identifier;
        $patient->facility_id = $facility;
        $patient->save();

        if($editted){
            $sample = Viralsample::find($sample_exists->id);
        }
        else{
            $sample = new Viralsample;
            $sample->batch_id = $batch->id;
            $sample->patient_id = $patient->id;
        }

        $sample->fill($request->only($fields['sample_api']));
        $sample->age = $age;
        $sample->justification = Lookup::justification($justification);
        $sample->prophylaxis = Lookup::viral_regimen($prophylaxis);
        $sample->comment = $specimenlabelID;
        $sample->dateapproved = $sample->dateapproved2 = $sample->datetested;
        $sample->synched = 5;
        $sample->save();

        $sample->load(['patient', 'batch']);
        return $sample;
    }




}
