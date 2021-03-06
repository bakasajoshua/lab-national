<?php

namespace App\Http\Controllers;

use App\Viralsample;
use App\ViralsampleView;
use App\Viralpatient;
use App\Viralbatch;
use App\Facility;
use App\Lookup;

use Illuminate\Http\Request;

class ViralsampleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function nhrl_samples()
    {
        $samples = Viralsample::where('synched', 5)->with(['batch.facility', 'patient'])->get(); 
        $data['samples'] = $samples;
        return view('tables.confirm_viralsamples', $data)->with('pageTitle', 'Confirm Samples');
    }

    public function list_poc()
    {
        $data = Lookup::get_lookups();
        $samples = ViralsampleView::with(['facility'])->where(['site_entry' => 2])->get();
        $data['samples'] = $samples;
        $data['pre'] = 'viral';
        return view('tables.poc_samples', $data)->with('pageTitle', 'EID POC Samples');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = Lookup::viralsample_form();
        return view('forms.viralsamples', $data)->with('pageTitle', 'Add Sample');
    }

    public function create_poc()
    {
        $data = Lookup::viralsample_form();
        $data['poc'] = true;
        return view('forms.viralsamples', $data)->with('pageTitle', 'Add POC Sample');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $viralsamples_arrays = Lookup::viralsamples_arrays();
        $submit_type = $request->input('submit_type');

        $batch = session('viral_batch');

        if($submit_type == "cancel"){
            $batch->premature();
            $this->clear_session();
            session(['toast_message' => "The batch {$batch->id} has been released."]);
            return redirect("viralbatch/{$batch->id}");
        }

        $highpriority = $request->input('highpriority');

        if($highpriority == 1)
        {
            $facility_id = $request->input('facility_id');

            $batch = new Viralbatch;
            $batch->user_id = auth()->user()->id;
            $batch->lab_id = auth()->user()->lab_id;

            if(auth()->user()->user_type_id == 1 || auth()->user()->user_type_id == 4){
                $batch->received_by = auth()->user()->id;
                $batch->site_entry = 0;
            }

            if(auth()->user()->user_type_id == 5) $batch->site_entry = 1;

            $data = $request->only($viralsamples_arrays['batch']);
            $batch->fill($data);

            $batch->save();
            $message = 'The high priority sample has been saved in batch no ' . $batch->id . '.';

            session(['toast_message' => $message]);
            return redirect()->route('viralsample.create');
        }


        if(!$batch){
            $facility_id = $request->input('facility_id');
            $facility = Facility::find($facility_id);
            session(['viral_facility_name' => $facility->name, 'viral_batch_total' => 0]);

            $batch = new Viralbatch;
            $batch->user_id = auth()->user()->id;
            $batch->lab_id = auth()->user()->lab_id;

            if(auth()->user()->user_type_id == 1 || auth()->user()->user_type_id == 4){
                $batch->received_by = auth()->user()->id;
                $batch->site_entry = 0;
            }

            if(auth()->user()->user_type_id == 5){
                $batch->site_entry = 1;
            }

            $data = $request->only($viralsamples_arrays['batch']);
            $batch->fill($data);

            $batch->save();
            session(['viral_batch' => $batch]);
        }

        $new_patient = $request->input('new_patient');

        if($new_patient == 0){

            $patient_id = $request->input('patient_id');
            $repeat_test = Viralsample::where(['patient_id' => $patient_id, 'batch_id' => $batch->id])->first();

            if($repeat_test){
                session(['toast_message' => 'The sample already exists in the batch and has therefore not been saved again']);
                session(['toast_error' => 1]);
                return redirect()->route('viralsample.create');
            }

            $viralpatient = Viralpatient::find($patient_id);
            $data = $request->only($viralsamples_arrays['patient']);
            $viralpatient->fill($data);
            $viralpatient->save();
        }

        else{
            $data = $request->only($viralsamples_arrays['patient']);
            $viralpatient = new Viralpatient;
            $viralpatient->fill($data);
            $viralpatient->save();
        }

        $data = $request->only($viralsamples_arrays['sample']);
        $viralsample = new Viralsample;
        $viralsample->fill($data);
        $viralsample->patient_id = $viralpatient->id;
        $viralsample->age = Lookup::calculate_viralage($request->input('datecollected'), $request->input('dob'));
        $viralsample->batch_id = $batch->id;
        $viralsample->save();

        session(['toast_message' => "The sample has been created in batch {$batch->id}."]);

        $submit_type = $request->input('submit_type');

        if($submit_type == "release"){
            $this->clear_session();
            $batch->premature();
            return redirect("viralbatch/{$batch->id}");
        }

        $sample_count = session('viral_batch_total') + 1;
        session(['viral_batch_total' => $sample_count]);

        if($sample_count == 10){
            $this->clear_session();
            $batch->full_batch();
            session(['toast_message' => "The batch {$batch->id} is full and no new samples can be added to it."]);
            return redirect("viralbatch/{$batch->id}");
        }

        session(['toast_message' => 'The sample has been created.']);
        return redirect()->route('viralsample.create');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Viralsample  $viralsample
     * @return \Illuminate\Http\Response
     */
    public function show(ViralsampleView $viralsample)
    {
        $data = Lookup::get_viral_lookups();
        $data['samples'] = $viralsample;
        
        return view('tables.viralsample_search', $data)->with('pageTitle', 'Batches');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Viralsample  $viralsample
     * @return \Illuminate\Http\Response
     */
    public function edit(Viralsample $viralsample)
    {
        $viralsample->load(['patient', 'batch.facility']);
        $data = Lookup::viralsample_form();
        $data['viralsample'] = $viralsample;
        return view('forms.viralsamples', $data)->with('pageTitle', 'Edit Sample');
    }

    /**
     * Show the form for editing the specified resource (poc).
     *
     * @param  \App\Sample  $sample
     * @return \Illuminate\Http\Response
     */
    public function edit_poc(Viralsample $sample)
    {
        $sample->load(['patient', 'batch.facility_lab']);
        // if($sample->batch->site_entry != 2) abort(409, 'This sample is not a POC sample.');
        $data = Lookup::get_lookups();
        $data['sample'] = $sample;
        $data['pre'] = 'viral';
        return view('forms.poc_result', $data)->with('pageTitle', 'Edit Result');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Viralsample  $viralsample
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Viralsample $viralsample)
    {
        $viralsamples_arrays = Lookup::viralsamples_arrays();
        $data = $request->only($viralsamples_arrays['sample']);
        $viralsample->fill($data);

        $viralsample->age = Lookup::calculate_viralage($request->input('datecollected'), $request->input('dob'));

        $batch = Viralbatch::find($viralsample->batch_id);
        $data = $request->only($viralsamples_arrays['batch']);
        $batch->fill($data);
        $batch->pre_update();

        $data = $request->only($viralsamples_arrays['patient']);

        $new_patient = $request->input('new_patient');

        if($new_patient == 0){            
            $viralpatient = Viralpatient::find($viralsample->patient_id);
        }
        else{
            $viralpatient = new Viralpatient;
        }
        $viralpatient->fill($data);
        $viralpatient->pre_update();

        $viralsample->patient_id = $viralpatient->id;
        $viralsample->pre_update();

        session(['toast_message' => 'The sample has been updated.']);

        $site_entry_approval = session()->pull('site_entry_approval');

        if($site_entry_approval){
            session(['toast_message' => 'The site entry sample has been approved.']);
            return redirect('viralbatch/site_approval/' . $batch->id);
        }

        return redirect('viralbatch/' . $batch->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Sample  $sample
     * @return \Illuminate\Http\Response
     */
    public function save_poc(Request $request, Viralsample $sample)
    {
        $sample->fill($request->except(['_token', 'lab_id', 'result_2']));

        if(!$sample->result) $sample->result = $request->input('result_2');

        if(!$sample->result){
            session(['toast_message' => 'Please set a result value.']);
            session(['toast_error' => 1]);
            return back();
        }

        $sample->pre_update();
        \App\MiscViral::check_batch($sample->batch_id);
        \App\Common::check_worklist(ViralsampleView::class, $sample->worksheet_id);

        $batch = $sample->batch;
        $batch->lab_id = $request->input('lab_id');
        $batch->pre_update();
        session(['toast_message' => 'The sample has been updated.']);

        return redirect('viralsample/list_poc');        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Viralsample  $viralsample
     * @return \Illuminate\Http\Response
     */
    public function destroy(Viralsample $viralsample)
    {
        if($viralsample->worksheet_id == NULL && $viralsample->result == NULL){
            $viralsample->delete();
            session(['toast_message' => 'The sample has been deleted.']);
        }  
        else{
            session(['toast_message' => 'The sample has not been deleted.']);
            session(['toast_error' => 1]);
        }      
        return back();
    }

    public function new_patient(Request $request)
    {
        $facility_id = $request->input('facility_id');
        $patient = $request->input('patient');

        $viralpatient = Viralpatient::where(['facility_id' => $facility_id, 'patient' => $patient])->first();
        $data;
        if($viralpatient){
            $data[0] = 0;
            $data[1] = $viralpatient->toArray();

            $viralsample = Viralsample::select('id')->where(['patient_id' => $viralpatient->id])->where('result', '>', 1000)->where('repeatt', 0)->first();
            if($viralsample){
                $data[2] = ['previous_nonsuppressed' => 1];
            }
            else{
                $data[2] = ['previous_nonsuppressed' => 0];
            } 
        }
        else{
            $data[0] = 1;
        }
        return $data;
    }

    public function runs(Viralsample $sample)
    {
        // $samples = $sample->child;
        $samples = Viralsample::runs($sample)->orderBy('run', 'asc')->get();
        $patient = $sample->patient;
        return view('tables.sample_runs', ['patient' => $patient, 'samples' => $samples]);
    }

    /**
     * Print the specified resource.
     *
     * @param  \App\Batch  $batch
     * @return \Illuminate\Http\Response
     */
    public function individual(Viralsample $sample)
    {
        $batch = $sample->batch;
        $sample->load(['patient']);
        $samples[0] = $sample;
        $batch->load(['facility', 'lab', 'receiver', 'creator']);
        $data = Lookup::get_viral_lookups();
        $data['batch'] = $batch;
        $data['samples'] = $samples;

        return view('exports.viralsamples', $data)->with('pageTitle', 'Individual Samples');
    }

    public function release_redraw(Viralsample $sample)
    {
        if($sample->run == 1){
            session(['toast_message' => 'The sample cannot be released as a redraw.']);
            session(['toast_error' => 1]);
            return back();
        }
        else if($sample->run == 2){
            $prev_sample = $sample->parent;
        }
        else{
            $run = $sample->run - 1;
            $prev_sample = Viralsample::where(['parentid' => $sample->parentid, 'run' => $run])->get()->first();
        }
        
        $sample->delete();

        $prev_sample->labcomment = "Failed Test";
        $prev_sample->repeatt = 0;
        $prev_sample->result = 5;
        $prev_sample->approvedby = auth()->user()->id;
        $prev_sample->approvedby2 = auth()->user()->id;
        $prev_sample->dateapproved = date('Y-m-d');
        $prev_sample->dateapproved2 = date('Y-m-d');

        $prev_sample->save();
        \App\MiscViral::check_batch($prev_sample->batch_id);
        session(['toast_message' => 'The sample has been released as a redraw.']);
        return back();
    }

    public function release_redraws(Request $request)
    {
        $viralsamples = $request->input('samples');
        // DB::table('viralsamples')->whereIn('id', $viralsamples)->update(['repeatt' => 0, 'result' => "Collect New Sample"]);

        $viralsamples = Viralsample::whereIn('id', $viralsamples)->get();

        foreach ($viralsamples as $key => $viralsample) {
            $this->release_redraw($viralsample);
        }

        return back();
    }

    public function approve_nhrl(Request $request)
    {
        $viralsamples = $request->input('samples');
        $submit_type = $request->input('submit_type');
        $user = auth()->user();

        $batches = Viralsample::selectRaw("distinct batch_id")->whereIn('id', $viralsamples)->get();

        if($submit_type == "release"){
            Viralsample::whereIn('id', $viralsamples)->update(['synched' => 0, 'approvedby' => $user->id]);
            session(['toast_message' => 'The samples have been sent to NASCOP.']);
        }
        else{
            Viralsample::whereIn('id', $viralsamples)->delete();
            session(['toast_message' => 'The samples have been sent to deleted.']);
        }

        foreach ($batches as $key => $value) {
            \App\MiscViral::check_batch($value->batch_id);
        } 
        return back();
    }

    public function search(Request $request)
    {
        $user = auth()->user();
        $search = $request->input('search');
        $facility_user = false;

        if($user->user_type_id == 5) $facility_user=true;
        $string = "(viralbatches.facility_id='{$user->facility_id}' OR viralbatches.user_id='{$user->id}')";

        $samples = Viralsample::select('viralsamples.id')
            ->whereRaw("viralsamples.id like '" . $search . "%'")
            ->when($facility_user, function($query) use ($string){
                return $query->join('viralbatches', 'viralsamples.batch_id', '=', 'viralbatches.id')->whereRaw($string);
            })
            ->paginate(10);
        return $samples;
    }

    private function clear_session(){
        session()->forget('viral_batch');
        session()->forget('viral_facility_name');
        session()->forget('viral_batch_total');

        // session()->forget('viral_batch_no');
        // session()->forget('viral_batch_dispatch');
        // session()->forget('viral_batch_dispatched');
        // session()->forget('viral_batch_received');
        // session()->forget('viral_facility_id');
        // session()->forget('viral_facility_name');
    }
}
