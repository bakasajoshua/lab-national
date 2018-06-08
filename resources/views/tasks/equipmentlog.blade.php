@extends('layouts.tasks')

@section('css_scripts')
    <link href="{{ asset('css/datapicker/datepicker3.css') }}" rel="stylesheet" type="text/css">
@endsection

@section('custom_css')
	<style type="text/css">
		.input-edit {
            background-color: #FFFFCC;
        }
	</style>
@endsection

@section('content')
@php
    $prevmonth = date('m')-1;
@endphp
<div class="row">
    <div class="col-md-12">
        <div class="hpanel" style="margin-top: 1em;margin-right: 4%;">
        {{ Form::open(['url' => '/equipmentlog', 'method' => 'post', 'class'=>'form-horizontal']) }}
           <div class="alert alert-warning">
                    <center>
                        <font color="#4183D7">
                        Please Fill Out any Equipment Breakdown Details below. If none brokedown , Go to Comments and Write that then Click Submit
                        </font>
                    </center>
            </div>
            <div class="panel-body" style="padding: 20px;box-shadow: none; border-radius: 0px;">
                <table class="table table-striped table-bordered table-hover data-table" style="font-size: 10px;margin-top: 1em;">
                    <thead>
                        <tr>
                            <th>Narrative</th>
                            <th>Serial #</th>
                            <th>Dates of Equipment breaks down/ Run failure</th>
                            <th>Date the engineers are called/ POC Responding</th>
                            <th>Date when the machine is fixed/Repair</th>
                            <th>How long was the down time repair(days)</th>
                            <th>How many samples were not performed due to machine failure</th>
                            <th>Number of failed runs</th>
                            <th>Reagents (Specify the Test) wasted due to failed runs</th>
                            <th>Reason for breakdown/Error code/Failure</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $equipment)
                        <tr>
                            <th>
                                {{ $equipment->name }}
                                <input type="hidden" value="{{ $equipment->ID }}" name="equipmentid[]">
                            </th>
                            <th>
                                {{ $equipment->serialno }}
                                <input type="hidden" value="{{ $equipment->serialno }}" name="serialno[]">
                            </th>
                            <td>
                                <div class="input-group date">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    <input type="text" class="form-control input-sm" value="" name="datebrokendown[]">
                                </div>
                            </td>
                            <td>
                                <div class="input-group date">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    <input type="text" class="form-control input-sm" value="" name="datereported[]">
                                </div>
                            </td>
                            <td>
                                <div class="input-group date">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    <input type="text" class="form-control input-sm" value="" name="datefixed[]">
                                </div>
                            </td>
                            <td>
                                <input class="form-control input-sm input-edit" name="downtime[]" type="text" value="">
                            </td>
                            <td>
                                <input class="form-control input-sm input-edit" name="samplesnorun[]" type="text" value="">
                            </td>
                            <td>
                                <input class="form-control input-sm input-edit" name="failedruns[]" type="text" value=""> 
                            </td>
                            <td>
                                <input class="form-control input-sm input-edit" name="reagentswasted[]" type="text" value=""> 
                            </td>
                            <td>
                                <input class="form-control input-sm input-edit" name="breakdownreason[]" type="text" value="">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <table class="table table-striped table-bordered table-hover data-table" style="font-size: 10px;margin-top: 1em;">
                    <thead>
                        <tr>
                            <th><center>Other Comments</center></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <textarea class="form-control input-sm input-edit" name="otherreasons" id="otherreasons" cols="200"></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <center>
                <button class="btn btn-success" type="submit" name="submit" value="submit" style="margin-bottom: 3em;margin-top: 1em;font-size: 14px;">Submit Lab Equipment Log</button>
            </center>
        {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @component('/forms/scripts')
        @slot('js_scripts')
            <script src="{{ asset('js/datapicker/bootstrap-datepicker.js') }}"></script>
        @endslot


        @slot('val_rules')
           
        @endslot

        $(".date").datepicker({
            todayBtn: "linked",
            format: "yyyy-mm-dd"
        });

    @endcomponent
@endsection